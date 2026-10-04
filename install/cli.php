<?php
declare(strict_types=1);
/**
 * NeoFrag Reborn — installeur en LIGNE DE COMMANDE.
 *
 * Alternative scriptable à l'assistant web (install/index.php), pratique pour un déploiement VPS
 * reproductible. Réutilise exactement la même lib d'installation (neofrag/installer.php) et la
 * même séquence « tout bundlé » que tools/ci-install.php — donc rigoureusement le même résultat que
 * l'assistant web, mais sans navigateur.
 *
 * Usage :
 *   php install/cli.php --db-name=neofrag --db-user=neofrag --db-pass=secret \
 *                       --admin-user=admin --admin-email=admin@site.tld --admin-pass-env=NF_ADMIN_PASS \
 *                       --site-name="Ma communauté" --site-url=https://site.tld --yes
 *
 *   php install/cli.php            # mode interactif (demande ce qui manque)
 *   php install/cli.php --help     # aide complète
 *   php install/cli.php --lang=en  # dans une autre langue (à défaut : celle de $LANG, sinon le français)
 *
 * Sécurité du mot de passe admin : préférer --admin-pass-env=NOM_DE_VARIABLE (lu dans l'environnement,
 * invisible dans la liste des process) ou la saisie interactive masquée, plutôt que --admin-pass=… en clair.
 */

if (PHP_SAPI !== 'cli')
{
	http_response_code(404);
	exit;
}

define('NF_CLI_ROOT', dirname(__DIR__));
chdir(NF_CLI_ROOT);

require NF_CLI_ROOT . '/neofrag/installer.php';

use NF\NeoFrag\Installer;

exit(nf_cli_main($argv));

function nf_cli_main(array $argv): int
{
	$value_opts = ['db-host', 'db-port', 'db-name', 'db-user', 'db-pass', 'admin-user', 'admin-pass',
	               'admin-pass-env', 'admin-email', 'site-name', 'site-url', 'lang'];

	// La langue des messages : `--lang=xx`, sinon celle du terminal (`LANG=de_DE.UTF-8`), sinon le français.
	nf_install_langue(nf_cli_langue($argv));
	$flag_opts  = ['create-db', 'demo', 'force', 'yes', 'dry-run', 'no-lock', 'help'];

	try
	{
		$opt = nf_cli_parse($argv, $value_opts, $flag_opts);
	}
	catch (\RuntimeException $e)
	{
		return nf_cli_fail($e->getMessage());
	}

	if (isset($opt['help']))
	{
		nf_cli_usage();
		return 0;
	}

	// Les mêmes prérequis que l'assistant web (Installer::PREREQUIS) : jusqu'au 2026-10-04, la ligne de
	// commande n'exigeait que deux extensions, ne regardait pas la version de PHP, et un PHP sans Argon2
	// tombait en erreur fatale à la création du compte administrateur.
	nf_cli_require_prerequis();

	$c = [
		'db_host'     => (string) nf_cli_val($opt, 'db-host', 'localhost'),
		'db_port'     => (int) nf_cli_val($opt, 'db-port', '3306'),
		'db_name'     => (string) nf_cli_val($opt, 'db-name', ''),
		'db_user'     => (string) nf_cli_val($opt, 'db-user', 'root'),
		'db_pass'     => (string) nf_cli_val($opt, 'db-pass', ''),
		'create_db'   => isset($opt['create-db']),
		'admin_user'  => (string) nf_cli_val($opt, 'admin-user', 'admin'),
		'admin_pass'  => (string) nf_cli_val($opt, 'admin-pass', ''),
		'admin_email' => (string) nf_cli_val($opt, 'admin-email', ''),
		'site_name'   => (string) nf_cli_val($opt, 'site-name', ''),
		'site_url'    => (string) nf_cli_val($opt, 'site-url', ''),
		'demo'        => isset($opt['demo']),
		'force'       => isset($opt['force']),
		'yes'         => isset($opt['yes']),
		'dry_run'     => isset($opt['dry-run']),
		'no_lock'     => isset($opt['no-lock']),
	];

	if (isset($opt['admin-pass-env']))
	{
		$env = getenv((string) $opt['admin-pass-env']);
		if ($env !== false && $env !== '')
		{
			$c['admin_pass'] = $env;
		}
	}

	nf_cli_interactive($c);

	if (($err = nf_cli_validate($c)) !== '')
	{
		return nf_cli_fail($err);
	}

	nf_cli_line('');
	nf_cli_line(lang('Installeur CLI — NeoFrag Reborn'));
	nf_cli_line('  ' . lang('Base : %s', $c['db_user'] . '@' . $c['db_host'] . ':' . $c['db_port'] . '/' . $c['db_name']) . ($c['create_db'] ? '  ' . lang('(à créer)') : ''));
	nf_cli_line('  ' . lang('Admin : %s', $c['admin_user'] . ' <' . $c['admin_email'] . '>'));
	nf_cli_line('  ' . lang('Site : %s', $c['site_name'] ?: lang('(nom par défaut)')) . ($c['site_url'] ? '  ·  ' . $c['site_url'] : ''));
	nf_cli_line('  ' . lang('Options : %s', implode(', ', array_filter([
		$c['demo'] ? lang('contenu démo') : '', $c['force'] ? 'force' : '', $c['no_lock'] ? lang('sans verrou') : '',
	])) ?: '—'));

	$config_dir = NF_CLI_ROOT . '/config';
	$db_cfg = ['hostname' => $c['db_host'], 'username' => $c['db_user'], 'password' => $c['db_pass'],
	           'database' => $c['db_name'], 'port' => $c['db_port']];

	// Vérif connexion serveur (toujours, y compris en dry-run).
	$test = Installer::test_db($db_cfg);
	nf_cli_line('  ' . lang('Connexion serveur : %s', $test['ok'] ? 'OK (' . $test['server'] . ')' : lang('ÉCHEC — %s', $test['error'])));

	if ($c['dry_run'])
	{
		nf_cli_line("\n" . lang('[--dry-run] Configuration valide. Aucune écriture effectuée.'));
		return $test['ok'] ? 0 : 1;
	}

	if (!$test['ok'])
	{
		return nf_cli_fail(lang('Connexion au serveur de base de données impossible : %s', $test['error']));
	}

	if (Installer::is_already_installed($config_dir) && !$c['force'])
	{
		return nf_cli_fail(lang('Une installation existe déjà (config/ présent). Relancez avec --force pour réinstaller.'));
	}

	if (!$c['yes'] && !nf_cli_confirm(lang('Lancer l\'installation ?')))
	{
		nf_cli_line(lang('Annulé.'));
		return 0;
	}

	try
	{
		// Créer la base si demandé (connexion serveur sans sélectionner de base).
		if ($c['create_db'])
		{
			nf_cli_line('· ' . lang('Création de la base %s…', $c['db_name']));
			$server = Installer::connect($db_cfg, false);
			$server->query("CREATE DATABASE IF NOT EXISTS `" . $server->real_escape_string($c['db_name']) . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
			$server->close();
		}

		// 1. Fichiers de config (db/crypt/password/email) + neofrag.php (constantes runtime).
		nf_cli_line('· ' . lang('Écriture de config/…'));
		Installer::write_config($config_dir, [
			'hostname' => $c['db_host'], 'username' => $c['db_user'],
			'password' => $c['db_pass'], 'database' => $c['db_name'], 'port' => $c['db_port'],
		]);
		file_put_contents($config_dir . '/neofrag.php', Installer::config_neofrag((bool) $c['demo']));
		if ($c['site_url'] !== '')
		{
			Installer::write_site_url($config_dir, rtrim($c['site_url'], '/'));
		}

		$db = Installer::connect($db_cfg, true);

		if (Installer::table_exists($db, 'nf_settings') && !$c['force'])
		{
			return nf_cli_fail(lang('La base « %s » contient déjà des tables NeoFrag. Relancez avec --force pour réinstaller.', $c['db_name']));
		}

		// 2. Schéma + seed + migrations.
		nf_cli_line('· ' . lang('Schéma, données de base et migrations…'));
		Installer::import_sql_file($db, NF_CLI_ROOT . '/install/schema.sql');
		Installer::import_sql_file($db, NF_CLI_ROOT . '/install/seed.sql');
		Installer::run_migrations($db, NF_CLI_ROOT . '/migrations', null);

		// 3. Tout bundlé : tous les modules/widgets/thèmes.
		nf_cli_line('· ' . lang('Installation des modules, widgets et thèmes…'));
		$summary = Installer::install_complete($db, NF_CLI_ROOT);
		if (!empty($summary['errors']))
		{
			return nf_cli_fail(lang('Erreurs pendant l\'installation : %s', implode(' | ', $summary['errors'])));
		}

		// 3b. Le contenu livré : la documentation du wiki, une mise en page propre au site s'il en porte une
		// (`install/vitrine.sql`, absent des paquets), et la démonstration si elle est demandée.
		nf_cli_line('· ' . ($c['demo'] ? lang('Contenu (documentation du wiki et démonstration)…') : lang('Contenu (documentation du wiki)…')));
		if (is_file($wiki = NF_CLI_ROOT . '/install/wiki.sql') && Installer::table_exists($db, 'nf_wiki_pages'))
		{
			Installer::import_sql_file($db, $wiki);
		}
		$optional = array_merge(['vitrine.sql'], $c['demo'] ? ['demo.sql'] : []);
		foreach ($optional as $file)
		{
			if (is_file($path = NF_CLI_ROOT . '/install/' . $file))
			{
				try
				{
					Installer::import_sql_file($db, $path);
				}
				catch (\Throwable $e)
				{
					nf_cli_line('  ⚠ ' . lang('%s ignoré : %s', $file, $e->getMessage()));
				}
			}
		}

		// 4. Admin + réglages du site.
		nf_cli_line('· ' . lang('Compte administrateur et réglages…'));
		Installer::create_admin($db, ['username' => $c['admin_user'], 'email' => $c['admin_email'], 'password' => $c['admin_pass']]);
		if ($c['site_name'] !== '')
		{
			Installer::set_setting($db, 'nf_name', $c['site_name']);
		}
		// Email « De » sur le domaine d'installation (un MTA ne signe pas pour un domaine qu'il ne possède pas).
		if ($c['site_url'] !== '' && ($host = parse_url($c['site_url'], PHP_URL_HOST)))
		{
			$host = preg_replace('/^www\./', '', strtolower((string) $host));
			if ($host !== '' && strpos($host, '.') !== false && !str_contains($host, 'localhost') && !filter_var($host, FILTER_VALIDATE_IP))
			{
				Installer::set_setting($db, 'nf_contact', 'noreply@' . $host);
			}
		}

		$db->close();
	}
	catch (\Throwable $e)
	{
		return nf_cli_fail(lang('Installation interrompue : %s', $e->getMessage()));
	}

	// 5. Verrou : bloque l'assistant web (index.php n'inclut install/ que si install/db.txt est absent).
	if (!$c['no_lock'])
	{
		@file_put_contents(NF_CLI_ROOT . '/install/db.txt', gmdate('c') . "\n");
	}

	nf_cli_line('');
	nf_cli_line('✅ ' . lang('Installation terminée.'));
	nf_cli_line('   ' . lang('Admin : %s', $c['admin_user'] . ' <' . $c['admin_email'] . '>'));
	if ($c['no_lock'])
	{
		nf_cli_line('   ⚠ ' . lang('Verrou NON posé (--no-lock) : supprime le dossier install/ ou pose install/db.txt avant la prod.'));
	}
	else
	{
		nf_cli_line('   ' . lang('Verrou posé (install/db.txt). Par sécurité, supprime aussi le dossier install/ en prod.'));
	}
	return 0;
}

/** Analyse `--clé=valeur` / `--drapeau`. Lève RuntimeException sur option/valeur invalide. */
function nf_cli_parse(array $argv, array $value_opts, array $flag_opts): array
{
	$out = [];
	foreach (array_slice($argv, 1) as $arg)
	{
		if (substr($arg, 0, 2) !== '--')
		{
			throw new \RuntimeException(lang('Argument inattendu : %s', $arg));
		}
		$arg = substr($arg, 2);
		if (strpos($arg, '=') !== false)
		{
			[$name, $val] = explode('=', $arg, 2);
		}
		else
		{
			$name = $arg;
			$val  = true;
		}
		if (!in_array($name, $value_opts, true) && !in_array($name, $flag_opts, true))
		{
			throw new \RuntimeException(lang('Option inconnue : %s', '--' . $name));
		}
		if (in_array($name, $value_opts, true) && $val === true)
		{
			throw new \RuntimeException(lang('L\'option %s attend une valeur (%s).', '--' . $name, '--' . $name . '=…'));
		}
		$out[$name] = $val;
	}
	return $out;
}

/**
 * La langue demandée, lue AVANT l'analyse des options (dont les erreurs doivent déjà être traduites) :
 * `--lang=xx`, sinon la variable `LANG` du terminal, sinon le français.
 */
function nf_cli_langue(array $argv): string
{
	foreach (array_slice($argv, 1) as $arg)
	{
		if (preg_match('/^--lang=([a-z]{2})$/', (string) $arg, $m))
		{
			return $m[1];
		}
	}

	return preg_match('/^([a-z]{2})(?:[_.@-]|$)/', (string) getenv('LANG'), $m) ? $m[1] : 'fr';
}

function nf_cli_val(array $opt, string $name, string $default): string
{
	return array_key_exists($name, $opt) && is_string($opt[$name]) ? $opt[$name] : $default;
}

/** Demande en interactif ce qui manque (sauf --yes). */
function nf_cli_interactive(array &$c): void
{
	if ($c['yes'])
	{
		return;
	}
	if ($c['db_name'] === '')
	{
		$c['db_name'] = nf_cli_prompt(lang('Nom de la base de données'), 'neofrag');
	}
	if ($c['admin_user'] === '')
	{
		$c['admin_user'] = nf_cli_prompt(lang('Pseudo administrateur'), 'admin');
	}
	while ($c['admin_email'] === '' || !filter_var($c['admin_email'], FILTER_VALIDATE_EMAIL))
	{
		$c['admin_email'] = nf_cli_prompt(lang('Email administrateur'), '');
		if ($c['admin_email'] === '')
		{
			break;
		}
	}
	while (strlen($c['admin_pass']) < 8)
	{
		$c['admin_pass'] = nf_cli_prompt_hidden(lang('Mot de passe administrateur (8 caractères minimum)'));
		if ($c['admin_pass'] === '')
		{
			break;
		}
	}
	if ($c['site_name'] === '')
	{
		$c['site_name'] = nf_cli_prompt(lang('Nom du site'), 'NeoFrag Reborn');
	}
}

function nf_cli_validate(array $c): string
{
	if ($c['db_name'] === '')                                            return lang('Le nom de la base (%s) est obligatoire.', '--db-name');
	if ($c['admin_user'] === '')                                         return lang('Le pseudo administrateur (%s) est obligatoire.', '--admin-user');
	if (!filter_var($c['admin_email'], FILTER_VALIDATE_EMAIL))           return lang('Email administrateur invalide (%s).', '--admin-email');
	if (strlen($c['admin_pass']) < 8)                                    return lang('Mot de passe administrateur trop court (8 caractères minimum ; %s).', '--admin-pass / --admin-pass-env');
	return '';
}

function nf_cli_require_prerequis(): void
{
	foreach (Installer::prerequis() as $mesure)
	{
		if ($mesure['ok'])
		{
			continue;
		}

		if ($mesure['type'] === 'php')
		{
			nf_cli_fail(lang('PHP %s ou plus récent est requis (ce PHP : %s).', Installer::php_minimum(), $mesure['nom']));
		}
		else if ($mesure['type'] === 'extension')
		{
			nf_cli_fail(lang('Extension PHP requise manquante : %s', $mesure['nom']));
		}
		else
		{
			nf_cli_fail(lang('Ce PHP ne sait pas hacher les mots de passe en %s : il faut un PHP compilé avec Argon2.', $mesure['nom']));
		}

		exit(1);
	}
}

function nf_cli_prompt(string $label, string $default): string
{
	fwrite(STDOUT, $label . ($default !== '' ? " [{$default}]" : '') . ' : ');
	$in = trim((string) fgets(STDIN));
	return $in !== '' ? $in : $default;
}

/** Saisie masquée (Unix : stty). Repli visible ailleurs. */
function nf_cli_prompt_hidden(string $label): string
{
	fwrite(STDOUT, $label . ' : ');
	$hidden = DIRECTORY_SEPARATOR === '/' && @shell_exec('command -v stty') !== null;
	if ($hidden)
	{
		@shell_exec('stty -echo');
	}
	$in = trim((string) fgets(STDIN));
	if ($hidden)
	{
		@shell_exec('stty echo');
		fwrite(STDOUT, "\n");
	}
	return $in;
}

function nf_cli_confirm(string $label): bool
{
	// « oui » dans la langue des messages, son initiale, et l'anglais qu'on tape par réflexe.
	$oui = mb_strtolower(lang('oui'));
	fwrite(STDOUT, $label . ' ' . lang('[o/N]') . ' : ');
	$in = mb_strtolower(trim((string) fgets(STDIN)));
	return $in !== '' && in_array($in, [$oui, mb_substr($oui, 0, 1), 'y', 'yes'], true);
}

function nf_cli_line(string $msg): void
{
	fwrite(STDOUT, $msg . "\n");
}

function nf_cli_fail(string $msg): int
{
	fwrite(STDERR, lang('Erreur : %s', $msg) . "\n");
	return 1;
}

function nf_cli_usage(): void
{
	$option = static fn (string $syntaxe, string $description): string => sprintf('  %-23s %s', $syntaxe, $description);

	$lignes = [
		lang('Installeur CLI — NeoFrag Reborn'),
		'',
		'  php install/cli.php [options]',
		'',
		lang('Base de données :'),
		$option('--db-host=HOST', lang('(défaut : %s)', 'localhost')),
		$option('--db-port=PORT', lang('(défaut : %s)', '3306')),
		$option('--db-name=NAME', lang('base à utiliser (obligatoire)')),
		$option('--db-user=USER', lang('(défaut : %s)', 'root')),
		$option('--db-pass=PASS', lang('mot de passe de la base')),
		$option('--create-db', lang('crée la base si absente (droit CREATE requis)')),
		'',
		lang('Administrateur :'),
		$option('--admin-user=NAME', lang('(défaut : %s)', 'admin')),
		$option('--admin-email=EMAIL', lang('(obligatoire)')),
		$option('--admin-pass=PASS', lang('mot de passe (8 caractères minimum) — visible dans les processus, à éviter')),
		$option('--admin-pass-env=VAR', lang('lit le mot de passe dans la variable d\'environnement VAR (recommandé)')),
		'',
		lang('Site :'),
		$option('--site-name="NAME"', lang('nom du site')),
		$option('--site-url=URL', lang('origine canonique (ex. https://site.tld) — fige config/url.php et l\'adresse d\'envoi des e-mails')),
		$option('--demo', lang('importe aussi le contenu de démonstration (demo.sql)')),
		'',
		lang('Divers :'),
		$option('--lang=CODE', lang('langue des messages : fr, en, de, es, it, pt (défaut : celle du terminal)')),
		$option('--yes', lang('non interactif : ne demande rien, pas même de confirmation')),
		$option('--force', lang('réinstalle même si une installation ou une base existe déjà')),
		$option('--dry-run', lang('valide la configuration et teste la connexion, sans rien écrire')),
		$option('--no-lock', lang('ne pose pas le verrou install/db.txt')),
		$option('--help', lang('cette aide')),
		'',
		lang('Exemple :'),
		"  NF_ADMIN_PASS='…' php install/cli.php --db-name=neofrag --db-user=neofrag --db-pass=… \\",
		'      --admin-email=admin@site.tld --admin-pass-env=NF_ADMIN_PASS --site-name="My community" \\',
		'      --site-url=https://site.tld --yes',
		'',
	];

	fwrite(STDOUT, implode("\n", $lignes) . "\n");
}
