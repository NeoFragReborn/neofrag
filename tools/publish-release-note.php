<?php
declare(strict_types=1);
// Outil d'administration : jamais servi en HTTP (comme tout tools/*.php).
if (PHP_SAPI !== 'cli')
{
	http_response_code(404);
	exit;
}

/**
 * NeoFrag Reborn — publie (ou met à jour) une actualité « note de version » à partir de CHANGELOG.md.
 *
 * Pourquoi un outil CLI et pas un endpoint HTTP : le site n'expose ainsi AUCUNE surface d'écriture
 * supplémentaire. À lancer sur le serveur (ou par un cron de release).
 *
 * Source de vérité unique : réutilise tools/changelog-section.php — le texte de l'actualité est donc
 * identique à celui de la release GitHub (même section, même rendu).
 *
 * Idempotent : repéré par (langue, titre « NeoFrag Reborn X.Y.Z »). Deuxième passage = mise à jour
 * du corps, pas de doublon.
 *
 * Connexion : identique à tools/maintenance.php — config/db.php ($db[0]) surchargé par NF_DB_*.
 *
 * Usage :
 *   php tools/publish-release-note.php [<version>|latest] [--lang=fr] [--title="..."] [--pretend]
 *     <version>    numéro exact (défaut : latest = première section versionnée de CHANGELOG.md)
 *     --lang=xx    langue de l'actualité (défaut : langue la plus utilisée en base, sinon « fr »)
 *     --title=...  titre de l'actualité (défaut : « NeoFrag Reborn X.Y.Z »)
 *     --pretend    n'écrit rien, affiche ce qui serait fait
 *
 * Codes retour : 0 OK · 2 mauvais usage · 3 section introuvable · 4 vendor/ absent (rendu HTML) ·
 *                5 pré-requis base manquant (catégorie ou administrateur) · 1 erreur base.
 *
 * Le champ HTML des actualités est rendu tel quel (édité via TinyMCE) : on y stocke donc le HTML issu
 * du Markdown. La parution (announced_at) est laissée à NULL : le cron de parution annoncera l'actualité
 * une fois (notifications), comme pour toute news programmée à l'heure présente.
 */

require __DIR__ . '/changelog-section.php'; // définit changelog_section() / *_to_html() — sans auto-exécution

const CONFIG_DB = __DIR__ . '/../config/db.php';
const CHANGELOG = __DIR__ . '/../CHANGELOG.md';

exit(main($argv));

function main(array $argv): int
{
	$selector = 'latest';
	$lang     = null;
	$title    = null;
	$pretend  = false;
	$got_sel  = false;

	foreach (array_slice($argv, 1) as $a)
	{
		if ($a === '--pretend')
		{
			$pretend = true;
		}
		else if (str_starts_with($a, '--lang='))
		{
			$lang = substr($a, 7);
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
		else if (str_starts_with($a, '--'))
		{
			fwrite(STDERR, "Option inconnue : {$a}\n" . usage());
			return 2;
		}
		else if (!$got_sel)
		{
			$selector = $a;
			$got_sel  = true;
		}
		else
		{
			fwrite(STDERR, "Argument en trop : {$a}\n" . usage());
			return 2;
		}
	}

	if (!is_file(CHANGELOG))
	{
		fwrite(STDERR, "CHANGELOG.md introuvable.\n");
		return 3;
	}

	$section = changelog_section($selector, (string) file_get_contents(CHANGELOG));

	if ($section === null)
	{
		fwrite(STDERR, "Section introuvable pour le sélecteur « {$selector} ».\n");
		return 3;
	}

	$version = $section['version'];

	if (str_starts_with(strtolower($version), 'non publ'))
	{
		fwrite(STDERR, "Refus : « {$version} » n'est pas une version publiée. Bumpez la version et figez CHANGELOG.md d'abord (cf. docs/RELEASING.md).\n");
		return 2;
	}

	$body_html = changelog_section_to_html($section['body']);

	if ($body_html === null)
	{
		fwrite(STDERR, "vendor/ absent : impossible de rendre le Markdown en HTML. Lancez composer install.\n");
		return 4;
	}

	$title        = $title ?? ('NeoFrag Reborn ' . $version);
	$introduction = '<p>NeoFrag Reborn ' . htmlspecialchars($version, ENT_QUOTES) . " est disponible. Voici les nouveautés :</p>\n" . $body_html;
	$tags         = 'release,' . $version;

	$db   = connect();
	$lang = $lang ?: resolve_lang($db);

	$category_id = first_category($db);
	if ($category_id === null)
	{
		fwrite(STDERR, "Aucune catégorie d'actualité (nf_news_categories) : créez-en une dans l'admin avant de publier.\n");
		return 5;
	}

	// user_id est NOT NULL (FK vers nf_user) : il faut un administrateur pour porter l'actualité.
	$user_id = first_admin($db);
	if ($user_id === null)
	{
		fwrite(STDERR, "Aucun administrateur (nf_user.admin='1') pour porter l'actualité.\n");
		return 5;
	}

	$existing = find_existing($db, $lang, $title);

	if ($pretend)
	{
		$action = $existing !== null ? "MISE À JOUR (news_id={$existing})" : 'CRÉATION';
		fwrite(STDOUT, "-- MODE --pretend : aucune écriture.\n");
		fwrite(STDOUT, "{$action} · version={$version} · lang={$lang} · catégorie={$category_id} · auteur={$user_id}\n");
		fwrite(STDOUT, "titre : {$title}\n");
		fwrite(STDOUT, 'corps HTML : ' . strlen($introduction) . " octets\n");
		return 0;
	}

	if ($existing !== null)
	{
		update_note($db, $existing, $lang, $introduction, $tags);
		fwrite(STDOUT, "Actualité mise à jour (news_id={$existing}, lang={$lang}) : {$title}\n");
	}
	else
	{
		$news_id = insert_note($db, $category_id, $user_id, $lang, $title, $introduction, $tags);
		fwrite(STDOUT, "Actualité créée (news_id={$news_id}, lang={$lang}) : {$title}\n");
	}

	return 0;
}

/** Langue de l'actualité : la plus utilisée en base (news, puis groupes), sinon « fr ». */
function resolve_lang(mysqli $db): string
{
	foreach (['nf_news_lang', 'nf_groups_lang'] as $table)
	{
		$lang = scalar($db, "SELECT lang FROM `{$table}` GROUP BY lang ORDER BY COUNT(*) DESC LIMIT 1");
		if (is_string($lang) && $lang !== '')
		{
			return $lang;
		}
	}

	return 'fr';
}

function first_category(mysqli $db): ?int
{
	$id = scalar($db, 'SELECT category_id FROM `nf_news_categories` ORDER BY category_id LIMIT 1');
	return $id === null ? null : (int) $id;
}

function first_admin(mysqli $db): ?int
{
	$id = scalar($db, "SELECT id FROM `nf_user` WHERE `admin` <> '0' AND `deleted` = '0' ORDER BY id LIMIT 1");
	return $id === null ? null : (int) $id;
}

function find_existing(mysqli $db, string $lang, string $title): ?int
{
	$stmt = $db->prepare(
		'SELECT n.news_id FROM `nf_news` n
		 JOIN `nf_news_lang` nl ON nl.news_id = n.news_id
		 WHERE nl.lang = ? AND nl.title = ? AND n.deleted_at IS NULL
		 ORDER BY n.news_id LIMIT 1'
	);
	$stmt->bind_param('ss', $lang, $title);
	$stmt->execute();
	$stmt->bind_result($news_id);
	$found = $stmt->fetch() ? (int) $news_id : null;
	$stmt->close();

	return $found;
}

function insert_note(mysqli $db, int $category_id, int $user_id, string $lang, string $title, string $introduction, string $tags): int
{
	$stmt = $db->prepare(
		"INSERT INTO `nf_news` (`category_id`, `user_id`, `image_id`, `date`, `published`)
		 VALUES (?, ?, NULL, NOW(), '1')"
	);
	$stmt->bind_param('ii', $category_id, $user_id);
	$stmt->execute();
	$news_id = (int) $db->insert_id;
	$stmt->close();

	// introduction porte le corps (la vue article des news rend l'introduction) ; content laissé vide.
	$content = '';
	$stmt = $db->prepare(
		'INSERT INTO `nf_news_lang` (`news_id`, `lang`, `title`, `introduction`, `content`, `tags`)
		 VALUES (?, ?, ?, ?, ?, ?)'
	);
	$stmt->bind_param('isssss', $news_id, $lang, $title, $introduction, $content, $tags);
	$stmt->execute();
	$stmt->close();

	return $news_id;
}

function update_note(mysqli $db, int $news_id, string $lang, string $introduction, string $tags): void
{
	// On (re)publie sans toucher à la date ni au titre (clé d'idempotence).
	$stmt = $db->prepare("UPDATE `nf_news` SET `published` = '1' WHERE `news_id` = ?");
	$stmt->bind_param('i', $news_id);
	$stmt->execute();
	$stmt->close();

	$stmt = $db->prepare(
		'UPDATE `nf_news_lang` SET `introduction` = ?, `tags` = ? WHERE `news_id` = ? AND `lang` = ?'
	);
	$stmt->bind_param('ssis', $introduction, $tags, $news_id, $lang);
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
	return "Usage : php tools/publish-release-note.php [<version>|latest] [--lang=fr] [--title=\"...\"] [--pretend]\n";
}
