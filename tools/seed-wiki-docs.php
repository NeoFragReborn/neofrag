<?php
declare(strict_types=1);

/**
 * NeoFrag Reborn — transfère docs/guide/*.md dans le module wiki (nf_wiki_pages).
 * Le wiki rend le contenu via render_content() (markdown-aware) → on insère le
 * markdown brut. Idempotent (purge puis ré-insère les pages de doc).
 *
 * À lancer : docker compose exec -T web php tools/seed-wiki-docs.php
 */

$root = dirname(__DIR__);

// --- Connexion (config/db.php, surchargeable par NF_DB_*) ---
$cfg = ['hostname' => 'db', 'username' => 'neofrag', 'password' => 'neofragpass', 'database' => 'neofrag'];
$dbfile = $root . '/config/db.php';
if (is_file($dbfile)) { $db = []; require $dbfile; if (!empty($db[0]) && is_array($db[0])) { $cfg = array_merge($cfg, $db[0]); } }
foreach (['HOST' => 'hostname', 'USER' => 'username', 'PASS' => 'password', 'NAME' => 'database'] as $env => $k) {
	$v = getenv('NF_DB_' . $env);
	if ($v !== FALSE && $v !== '') { $cfg[$k] = $v; }
}

$mysqli = @new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database']);
if ($mysqli->connect_errno) { fwrite(STDERR, 'Connexion DB échouée : ' . $mysqli->connect_error . "\n"); exit(1); }
$mysqli->set_charset('utf8mb4');

// --- Structure de la doc ---
$sections = [
	'guide-utilisateur' => [
		'title' => 'Guide utilisateur',
		'intro' => "# Guide utilisateur\n\nTout pour installer et piloter ton site **NeoFrag Reborn**.",
		'pages' => ['installation' => 'Installation', 'concepts' => 'Concepts', 'admin' => 'Administration', 'marketplace' => 'Marketplace'],
	],
	'guide-developpeur' => [
		'title' => 'Guide développeur',
		'intro' => "# Guide développeur\n\nÉtends NeoFrag Reborn : crée tes **thèmes**, **widgets** et **modules**, et maîtrise le framework.",
		'pages' => ['create-a-theme' => 'Créer un thème', 'create-a-widget' => 'Créer un widget', 'create-a-module' => 'Créer un module', 'framework' => 'Le framework'],
	],
];

/**
 * Adapte les liens markdown pour /wiki/ puis convertit en HTML.
 * On stocke le HTML (et pas le markdown) : le wiki rend via render_content() dont
 * l'heuristique markdown est trompée par les balises HTML présentes dans nos blocs
 * de code (<main>, <nav>…). Convertir au seed garantit un rendu correct partout.
 */
function wiki_links(string $md): string
{
	static $converter = NULL;
	if ($converter === NULL)
	{
		require_once dirname(__DIR__) . '/vendor/autoload.php';
		$converter = new \League\CommonMark\GithubFlavoredMarkdownConverter([
			'html_input'         => 'escape',
			'allow_unsafe_links' => FALSE,
			'max_nesting_level'  => 20,
		]);
	}
	// liens internes vers un autre guide : strip .md → lien relatif (résolu sous /wiki/)
	$md = preg_replace('/\]\(\.?\/?([a-z0-9-]+)\.md(#[^)]*)?\)/i', ']($1$2)', $md);
	// liens vers les docs internes (../architecture.md…) : garde juste le texte
	$md = preg_replace('/\[([^\]]+)\]\(\.\.\/[a-z0-9-]+\.md\)/i', '$1', $md);
	// lien d'index
	$md = str_replace('](README.md)', '](.)', $md);
	return (string) $converter->convert($md);
}

function wiki_insert(mysqli $db, string $slug, string $title, string $content, ?int $parent, int $sort): int
{
	if ($parent === NULL) {
		$stmt = $db->prepare('INSERT INTO nf_wiki_pages (slug,title,content,sort_order,published) VALUES (?,?,?,?,1)');
		$stmt->bind_param('sssi', $slug, $title, $content, $sort);
	} else {
		$stmt = $db->prepare('INSERT INTO nf_wiki_pages (slug,title,content,parent_id,sort_order,published) VALUES (?,?,?,?,?,1)');
		$stmt->bind_param('sssii', $slug, $title, $content, $parent, $sort);
	}
	$stmt->execute();
	$id = $db->insert_id;
	$stmt->close();
	return $id;
}

// --- Purge des anciennes pages de doc ---
$all_slugs = array_keys($sections);
foreach ($sections as $sec) { $all_slugs = array_merge($all_slugs, array_keys($sec['pages'])); }
$escaped = array_map(function ($s) use ($mysqli) { return "'" . $mysqli->real_escape_string($s) . "'"; }, $all_slugs);
$mysqli->query('DELETE FROM nf_wiki_pages WHERE slug IN (' . implode(',', $escaped) . ')');

// --- Insertion ---
$top = 0;
$count = 0;
foreach ($sections as $pslug => $sec) {
	$pid = wiki_insert($mysqli, $pslug, $sec['title'], wiki_links($sec['intro']), NULL, ++$top);
	$sub = 0;
	foreach ($sec['pages'] as $slug => $title) {
		$file = "$root/docs/guide/$slug.md";
		if (!is_file($file)) { fwrite(STDERR, "  ⚠ manquant : docs/guide/$slug.md\n"); continue; }
		wiki_insert($mysqli, $slug, $title, wiki_links(file_get_contents($file)), $pid, ++$sub);
		$count++;
		echo "  + $pslug/$slug\n";
	}
}

echo "\n✓ $count pages de doc transférées dans le wiki\n";
