<?php
declare(strict_types=1);

/**
 * NeoFrag Reborn — packaging du marketplace.
 *
 * Lit tools/addons-manifest.php, zippe chaque addon optionnel et génère
 * marketplace/catalog.json (métadonnées + checksum). Le contenu de marketplace/
 * alimente le showcase du site vitrine (à la addons.neofr.ag) : navigation +
 * fiches + téléchargement .zip (install ensuite via « Ajouter » ZIP de l'admin).
 *
 * Standalone (aucun bootstrap framework). À lancer depuis la racine du projet :
 *   php tools/package-addons.php
 *   (ou : docker compose exec -T web php tools/package-addons.php)
 */

$root     = dirname(__DIR__);
$manifest = require $root . '/tools/addons-manifest.php';
$out      = $root . '/marketplace';

$type_dir = [
	'module'        => 'modules',
	'widget'        => 'widgets',
	'theme'         => 'themes',
	'authenticator' => 'addons',
];

/** Version de base du CMS (depuis index.php) — pour catalog.base_version + requires.base. */
function base_version(string $root): string
{
	if (preg_match("/NEOFRAG_VERSION',\s*'([^']+)'/", (string) @file_get_contents($root . '/index.php'), $m))
	{
		return $m[1];
	}
	return '1.0.0';
}

$all_widgets = array_merge($manifest['identity']['widget'] ?? [], $manifest['optional']['widget'] ?? []);

/** Tier d'un addon : 1 (identity, livré dans le paquet) / 2 (optional, marketplace seul). */
$tier_of = static function (string $type, string $name) use ($manifest): int {
	return in_array($name, $manifest['identity'][$type] ?? [], TRUE) ? 1 : 2;
};

/** Catégorie d'affichage (groupes de l'UI marketplace). */
$category_of = static function (string $type, string $name) use ($manifest): string {
	if ($type === 'theme')
	{
		return 'theme';
	}
	if (in_array($name, ['shop', 'donations', 'payments', 'ads', 'newsletter'], TRUE))
	{
		return 'monetisation';
	}
	if (in_array($name, $manifest['identity']['module'] ?? [], TRUE) || in_array($name, $manifest['identity']['widget'] ?? [], TRUE))
	{
		return 'identite';
	}
	return 'contenu';
};

/**
 * Widgets fournis par un module : le widget de même nom s'il est packagé (apparié module↔widget).
 * Suffisant pour le Tier 2 (articles→articles, downloads→downloads…). Les appariements spéciaux
 * Tier 1 (teams→about) sont gérés par les presets de l'installeur, pas par ce champ.
 */
$provides_of = static function (string $type, string $name) use ($all_widgets): array {
	return ($type === 'module' && in_array($name, $all_widgets, TRUE)) ? [$name] : [];
};

/** Extrait un champ du tableau __info() (gère 'x' => 'literal' ET 'x' => $this->lang('literal')). */
function info_field(string $src, string $field): ?string
{
	if (preg_match('/[\'"]' . preg_quote($field, '/') . '[\'"]\s*=>\s*(?:\$this->lang\(\s*)?\'((?:\\\\.|[^\'\\\\])*)\'/s', $src, $m))
	{
		return stripcslashes($m[1]);
	}
	return null;
}

/** Zippe un dossier d'addon sous la racine <name>/ (structure attendue par l'install ZIP). */
function zip_addon(string $folder, string $name, string $zip_path): int
{
	@unlink($zip_path);
	$zip = new ZipArchive();
	if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE)
	{
		return 0;
	}
	$it = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);
	foreach ($it as $file)
	{
		$rel   = substr($file->getPathname(), strlen($folder) + 1);
		$rel   = str_replace('\\', '/', $rel);
		$local = $name . '/' . $rel;
		if ($file->isDir())
		{
			$zip->addEmptyDir($local);
		}
		else
		{
			$zip->addFile($file->getPathname(), $local);
		}
	}
	$zip->close();
	return 1;
}

if (!is_dir($out))
{
	mkdir($out, 0775, TRUE);
}

$catalog = [];
$count   = 0;

// Addons packagés = Tier 1 (identité) ∪ Tier 2 (à la carte), regroupés par type. Tier 0 jamais packagé.
$packageable = [];
foreach (['identity', 'optional'] as $tier)
{
	foreach ($manifest[$tier] ?? [] as $type => $names)
	{
		$packageable[$type] = array_merge($packageable[$type] ?? [], $names);
	}
}

foreach ($packageable as $type => $names)
{
	$dir = $type_dir[$type] ?? NULL;
	if (!$dir)
	{
		continue;
	}
	if (!is_dir("$out/$dir"))
	{
		mkdir("$out/$dir", 0775, TRUE);
	}

	foreach ($names as $name)
	{
		$folder = "$root/$dir/$name";
		$main   = "$folder/$name.php";

		if (!is_dir($folder) || !is_file($main))
		{
			fwrite(STDERR, "  skip $type:$name (dossier ou fichier principal introuvable)\n");
			continue;
		}

		$src      = file_get_contents($main);
		$version  = info_field($src, 'version')     ?? '1.0';
		$title    = info_field($src, 'title')       ?? ucfirst($name);
		$desc     = info_field($src, 'description')  ?? '';
		$author   = info_field($src, 'author')      ?? 'NeoFrag Reborn';

		$zip_path = "$out/$dir/$name.zip";
		if (!zip_addon($folder, $name, $zip_path))
		{
			fwrite(STDERR, "  zip KO pour $name\n");
			continue;
		}

		$size = filesize($zip_path);
		$catalog[] = [
			'type'             => $type,
			'name'             => $name,
			'tier'             => $tier_of($type, $name),
			'category'         => $category_of($type, $name),
			'title'            => $title,
			'description'      => $desc,
			'version'          => $version,
			'author'           => $author,
			'file'             => "$dir/$name.zip",
			'size'             => $size,
			'sha256'           => hash_file('sha256', $zip_path),
			'requires'         => ['base' => '>=' . base_version($root), 'addons' => []],
			'provides_widgets' => $provides_of($type, $name),
			// L'install ZIP ne gère pas les authenticators → install par scan disque.
			'install'          => $type === 'authenticator' ? 'scan' : 'zip',
		];
		$count++;
		printf("  packed %-13s %-22s v%-6s %5d Ko\n", $type, $name, $version, (int) round($size / 1024));
	}
}

usort($catalog, function ($a, $b) {
	return [$a['type'], $a['name']] <=> [$b['type'], $b['name']];
});

file_put_contents("$out/catalog.json", json_encode([
	'schema'       => 1,
	'project'      => 'NeoFrag Reborn',
	'base_version' => base_version($root),
	'generated_at' => gmdate('c'),
	'count'        => $count,
	'addons'       => $catalog,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

echo "\n✓ $count addons packagés → marketplace/catalog.json (schema 1, base ".base_version($root).")\n";
