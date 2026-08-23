<?php
declare(strict_types=1);
// Outil d'administration : jamais servi en HTTP (comme tout tools/*.php).
if (PHP_SAPI !== 'cli')
{
	http_response_code(404);
	exit;
}

/**
 * NeoFrag Reborn — publie (ou met à jour) la page wiki « Journal des versions » depuis CHANGELOG.md.
 *
 * Complète la chaîne de publication à source unique (cf. docs/RELEASING.md) : les visiteurs ont
 * l'historique des versions sur le site (module wiki, `/wiki/<slug>`) sans passer par GitHub, et ce
 * texte vient du MÊME CHANGELOG.md que la release GitHub et l'actualité de version.
 *
 * Outil CLI (aucune surface d'écriture HTTP ajoutée), idempotent via le slug (clé UNIQUE) :
 * réexécuter met à jour la même page. Connexion : identique à tools/maintenance.php.
 *
 * Usage :
 *   php tools/publish-wiki-changelog.php [--slug=journal-des-versions] [--title="Journal des versions"] [--pretend]
 *
 * Codes retour : 0 OK · 2 mauvais usage · 3 CHANGELOG illisible · 4 vendor/ absent (rendu HTML) · 1 erreur base.
 */

require __DIR__ . '/changelog-section.php'; // définit changelog_section_to_html() — sans auto-exécution

const CONFIG_DB = __DIR__ . '/../config/db.php';
const CHANGELOG = __DIR__ . '/../CHANGELOG.md';

exit(main($argv));

function main(array $argv): int
{
	$slug    = 'journal-des-versions';
	$title   = 'Journal des versions';
	$pretend = false;

	foreach (array_slice($argv, 1) as $a)
	{
		if ($a === '--pretend')
		{
			$pretend = true;
		}
		else if (str_starts_with($a, '--slug='))
		{
			$slug = substr($a, 7);
		}
		else if (str_starts_with($a, '--title='))
		{
			$title = substr($a, 8);
		}
		else if ($a === '-h' || $a === '--help')
		{
			fwrite(STDOUT, usage());
			return 0;
		}
		else
		{
			fwrite(STDERR, "Argument inconnu : {$a}\n" . usage());
			return 2;
		}
	}

	$slug = trim($slug);
	if ($slug === '' || !preg_match('/^[a-z0-9-]+$/', $slug))
	{
		fwrite(STDERR, "Slug invalide (attendu : [a-z0-9-]+).\n");
		return 2;
	}

	if (!is_file(CHANGELOG))
	{
		fwrite(STDERR, "CHANGELOG.md introuvable.\n");
		return 3;
	}

	// Tout l'historique : du premier en-tête « ## [ … ] » jusqu'à la fin (on écarte l'intro/H1).
	$md      = (string) file_get_contents(CHANGELOG);
	$history = changelog_history($md);
	if ($history === null)
	{
		fwrite(STDERR, "Aucune section « ## [ … ] » dans CHANGELOG.md.\n");
		return 3;
	}

	$html = changelog_section_to_html($history);
	if ($html === null)
	{
		fwrite(STDERR, "vendor/ absent : impossible de rendre le Markdown en HTML. Lancez composer install.\n");
		return 4;
	}

	$db      = connect();
	$user_id = first_admin($db); // colonne nullable : NULL accepté si aucun admin.

	if ($pretend)
	{
		$exists = scalar($db, "SELECT id FROM `nf_wiki_pages` WHERE slug = '" . $db->real_escape_string($slug) . "'");
		fwrite(STDOUT, "-- MODE --pretend : aucune écriture.\n");
		fwrite(STDOUT, ($exists !== null ? "MISE À JOUR (id={$exists})" : 'CRÉATION') . " · slug={$slug} · auteur=" . ($user_id ?? 'NULL') . "\n");
		fwrite(STDOUT, "titre : {$title}\ncorps HTML : " . strlen($html) . " octets\n");
		return 0;
	}

	upsert_page($db, $slug, $title, $html, $user_id);
	fwrite(STDOUT, "Page wiki publiée : /wiki/{$slug} — {$title}\n");
	return 0;
}

/** Renvoie le Markdown depuis le premier en-tête « ## [ … ] » jusqu'à la fin, ou null si aucun. */
function changelog_history(string $changelog): ?string
{
	if (preg_match('/^##\s+\[[^\]]+\]/m', $changelog, $m, PREG_OFFSET_CAPTURE))
	{
		return trim(substr($changelog, $m[0][1]));
	}
	return null;
}

function first_admin(mysqli $db): ?int
{
	$id = scalar($db, "SELECT id FROM `nf_user` WHERE `admin` <> '0' AND `deleted` = '0' ORDER BY id LIMIT 1");
	return $id === null ? null : (int) $id;
}

/** Upsert atomique par slug (clé UNIQUE uk_slug). Republie la page (published=1). */
function upsert_page(mysqli $db, string $slug, string $title, string $html, ?int $user_id): void
{
	$stmt = $db->prepare(
		"INSERT INTO `nf_wiki_pages` (`slug`, `title`, `content`, `published`, `user_id`)
		 VALUES (?, ?, ?, 1, ?)
		 ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `content` = VALUES(`content`),
		                         `published` = 1, `updated_at` = CURRENT_TIMESTAMP()"
	);
	// bind_param n'accepte pas NULL via 'i' directement pour un INT nullable : on passe par une variable.
	$uid = $user_id; // peut être NULL
	$stmt->bind_param('sssi', $slug, $title, $html, $uid);
	$stmt->execute();
	$stmt->close();
}

function scalar(mysqli $db, string $sql)
{
	$res = $db->query($sql);
	if (!$res instanceof mysqli_result)
	{
		return null;
	}
	$row = $res->fetch_row();
	return $row ? $row[0] : null;
}

function connect(): mysqli
{
	$cfg = ['hostname' => '127.0.0.1', 'port' => 3306, 'username' => 'root', 'password' => '', 'database' => 'neofrag'];

	if (is_file(CONFIG_DB))
	{
		$db = [];
		require CONFIG_DB;
		if (!empty($db[0]) && is_array($db[0]))
		{
			$cfg = array_merge($cfg, $db[0]);
		}
	}

	foreach (['hostname' => 'NF_DB_HOST', 'port' => 'NF_DB_PORT', 'username' => 'NF_DB_USER', 'password' => 'NF_DB_PASS', 'database' => 'NF_DB_NAME'] as $key => $var)
	{
		$val = getenv($var);
		if ($val !== false && $val !== '')
		{
			$cfg[$key] = $val;
		}
	}

	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

	try
	{
		$conn = new mysqli($cfg['hostname'], $cfg['username'], (string) $cfg['password'], $cfg['database'], (int) $cfg['port']);
	}
	catch (\mysqli_sql_exception $e)
	{
		fwrite(STDERR, 'Connexion BDD impossible : ' . $e->getMessage() . "\n");
		exit(1);
	}

	$conn->set_charset('utf8mb4');
	return $conn;
}

function usage(): string
{
	return "Usage : php tools/publish-wiki-changelog.php [--slug=journal-des-versions] [--title=\"...\"] [--pretend]\n";
}
