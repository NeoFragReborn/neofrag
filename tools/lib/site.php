<?php
declare(strict_types=1);

/**
 * site — l'installation sur laquelle l'outil travaille : sa base, ses réglages, un administrateur.
 *
 * Pourquoi
 * --------
 * Vingt-cinq outils lisaient `config/db.php` chacun à leur façon — deux variantes de `connect()`
 * recopiées dix fois, l'une avec `mysqli_report(OFF)` et l'autre en mode strict, l'une acceptant les
 * variables `NF_DB_*` et l'autre non. Huit ouvraient une session d'administrateur en recopiant les
 * mêmes cinq lignes, et deux d'entre elles s'étaient trompées d'identifiant : l'insertion échouait en
 * silence et tout le balayage repassait en visiteur anonyme (2026-09-21).
 *
 * Usage
 * -----
 *   $db      = nf_connexion();             // refuse (code 2) si l'installation n'est pas configurée
 *   $session = nf_session_admin($db);      // fermée automatiquement à la fin de l'outil
 */

require_once __DIR__.'/outil.php';

/**
 * La configuration de base de l'installation : `config/db.php` ($db[0]), puis les variables
 * d'environnement `NF_DB_HOST`, `NF_DB_PORT`, `NF_DB_USER`, `NF_DB_PASS`, `NF_DB_NAME` par-dessus
 * (déploiement hors de l'installation, intégration continue).
 *
 * @return array{hostname: string, port: int, username: string, password: string, database: string}|null
 */
function nf_config_db(): ?array
{
    $cfg = NULL;

    if (is_file($fichier = nf_racine().'/config/db.php'))
    {
        $db = [];
        require $fichier;

        if (!empty($db[0]) && is_array($db[0]))
        {
            $cfg = $db[0];
        }
    }

    foreach (['hostname' => 'NF_DB_HOST', 'port' => 'NF_DB_PORT', 'username' => 'NF_DB_USER',
              'password' => 'NF_DB_PASS', 'database' => 'NF_DB_NAME'] as $cle => $variable)
    {
        if (($valeur = getenv($variable)) !== FALSE && $valeur !== '')
        {
            $cfg[$cle] = $valeur;
        }
    }

    if ($cfg === NULL || empty($cfg['database']))
    {
        return NULL;
    }

    return [
        'hostname' => (string) ($cfg['hostname'] ?? '127.0.0.1'),
        'port'     => (int) ($cfg['port'] ?? 3306),
        'username' => (string) ($cfg['username'] ?? ''),
        'password' => (string) ($cfg['password'] ?? ''),
        'database' => (string) $cfg['database'],
    ];
}

/**
 * Une connexion à la base de l'installation, en utf8mb4. Refuse de juger (code 2) si
 * l'installation n'est pas configurée ou si la base est injoignable : ce n'est pas un défaut du
 * produit, c'est un prérequis de l'outil.
 */
function nf_connexion(): mysqli
{
    static $connexion = NULL;

    if ($connexion instanceof mysqli)
    {
        return $connexion;
    }

    if (($cfg = nf_config_db()) === NULL)
    {
        nf_refus('config/db.php est absent : cet outil a besoin d\'une installation configurée');
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $connexion = @new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database'], $cfg['port']);

    if ($connexion->connect_errno)
    {
        nf_refus(sprintf('base injoignable (%s@%s:%d/%s) : %s',
            $cfg['username'], $cfg['hostname'], $cfg['port'], $cfg['database'], $connexion->connect_error));
    }

    $connexion->set_charset('utf8mb4');

    return $connexion;
}

/**
 * Une connexion au SERVEUR de base de données, sans base sélectionnée, avec des identifiants
 * donnés : pour les outils qui créent ou détruisent des bases (jetables d'épreuve, base de test).
 * Refuse de juger si la connexion échoue.
 *
 * @param array{hostname: string, port: int, username: string, password: string} $cfg
 */
function nf_connexion_admin(array $cfg, string $role = 'administrateur'): mysqli
{
    mysqli_report(MYSQLI_REPORT_OFF);
    $connexion = @new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], '', $cfg['port']);

    if ($connexion->connect_errno)
    {
        nf_refus(sprintf('connexion %s impossible (%s@%s:%d) : %s',
            $role, $cfg['username'], $cfg['hostname'], $cfg['port'], $connexion->connect_error));
    }

    $connexion->set_charset('utf8mb4');

    return $connexion;
}

/** La première colonne de la première ligne, ou NULL. */
function nf_scalar(mysqli $db, string $sql): mixed
{
    $resultat = $db->query($sql);

    if (!$resultat instanceof mysqli_result)
    {
        return NULL;
    }

    $ligne = $resultat->fetch_row();

    return $ligne ? $ligne[0] : NULL;
}

/** La première colonne de chaque ligne. */
function nf_colonne(mysqli $db, string $sql): array
{
    $valeurs  = [];
    $resultat = $db->query($sql);

    while ($resultat instanceof mysqli_result && ($ligne = $resultat->fetch_row()))
    {
        $valeurs[] = $ligne[0];
    }

    return $valeurs;
}

function nf_table_existe(mysqli $db, string $table): bool
{
    $resultat = $db->query("SHOW TABLES LIKE '".$db->real_escape_string($table)."'");

    return $resultat instanceof mysqli_result && $resultat->num_rows > 0;
}

/** L'identifiant d'un type d'addon (`module`, `widget`, `theme`, `language`…). */
function nf_type_id(mysqli $db, string $nom): int
{
    return (int) nf_scalar($db, "SELECT id FROM nf_addon_type WHERE name = '".$db->real_escape_string($nom)."'");
}

/** Un réglage du site (`nf_settings`), ou NULL s'il n'existe pas. */
function nf_reglage(mysqli $db, string $nom): ?string
{
    $valeur = nf_scalar($db, "SELECT value FROM nf_settings WHERE name = '".$db->real_escape_string($nom)."'");

    return $valeur === NULL ? NULL : (string) $valeur;
}

function nf_reglage_poser(mysqli $db, string $nom, string $valeur): void
{
    $db->query("UPDATE nf_settings SET value = '".$db->real_escape_string($valeur)
        ."' WHERE name = '".$db->real_escape_string($nom)."'");
}

/** Les thèmes réellement enregistrés sur CE site (`nf_addon`, type `theme`). */
function nf_themes_installes(mysqli $db): array
{
    return array_map('strval', nf_colonne($db,
        "SELECT a.name FROM nf_addon a JOIN nf_addon_type t ON t.id = a.type_id WHERE t.name = 'theme' ORDER BY a.name"));
}

/**
 * Le premier administrateur, celui que l'installateur a créé.
 *
 * Toujours par `nf_user.admin = '1'` — jamais par `nf_users_groups`, dont un identifiant peut ne
 * plus exister dans `nf_user` : c'est ainsi que deux balayages se sont crus connectés sans l'être.
 */
function nf_premier_admin(mysqli $db): ?int
{
    $id = nf_scalar($db, "SELECT id FROM nf_user WHERE admin = '1' AND deleted = '0' ORDER BY id LIMIT 1");

    return $id === NULL ? NULL : (int) $id;
}

/**
 * Ouvre une session d'administrateur temporaire, et l'efface quoi qu'il arrive à la fin de l'outil.
 *
 * Refuse de juger s'il n'y a aucun administrateur : sans session, toutes les pages
 * d'administration redirigeraient et l'outil mesurerait l'accueil en croyant mesurer le tableau
 * de bord. Une page d'administration qui répond 302 n'est pas une page d'administration.
 */
function nf_session_admin(mysqli $db): string
{
    $admin = nf_premier_admin($db) ?? nf_refus('aucun administrateur en base : sans session, toutes les pages redirigeraient');

    $session = bin2hex(random_bytes(16));
    $requete = $db->prepare("INSERT INTO nf_session (id, user_id, remember, data) VALUES (?, ?, '0', '')");
    $requete->bind_param('si', $session, $admin);

    if (!$requete->execute())
    {
        nf_refus('impossible d\'ouvrir une session d\'administrateur : '.$requete->error);
    }

    $requete->close();

    register_shutdown_function(static function () use ($db, $session): void {
        nf_session_fermer($db, $session);
    });

    return $session;
}

function nf_session_fermer(mysqli $db, string $session): void
{
    if ($session !== '')
    {
        @$db->query("DELETE FROM nf_session WHERE id = '".$db->real_escape_string($session)."'");
    }
}

/** Le site est-il en mode démonstration (`NEOFRAG_DEMO` dans `config/neofrag.php`) ? */
function nf_mode_demo(): bool
{
    $conf = (string) @file_get_contents(nf_racine().'/config/neofrag.php');

    return (bool) preg_match("/define\(\s*'NEOFRAG_DEMO'\s*,\s*TRUE\s*\)/i", $conf);
}

/**
 * Bascule le thème par défaut du site le temps de l'outil, et le RÉTABLIT quoi qu'il arrive —
 * fin normale, échec, interruption. Laisser le site sur un thème de passage serait un dégât
 * bien pire que ce que l'outil venait mesurer. L'« époque » du thème est rétablie aussi : c'est
 * elle qui invalide la préférence de thème des visiteurs, et rien n'a réellement changé.
 *
 * @return string  le thème d'origine
 */
function nf_theme_temporaire(mysqli $db): string
{
    static $origine = NULL;

    if ($origine !== NULL)
    {
        return $origine;
    }

    $origine = nf_reglage($db, 'nf_default_theme') ?? '';
    $epoque  = nf_reglage($db, 'nf_theme_epoch') ?? '0';

    $restaurer = static function () use ($db, $origine, $epoque): void {
        if ($origine !== '')
        {
            nf_reglage_poser($db, 'nf_default_theme', $origine);
        }

        nf_reglage_poser($db, 'nf_theme_epoch', $epoque);
    };

    register_shutdown_function($restaurer);

    if (function_exists('pcntl_signal') && function_exists('pcntl_async_signals'))
    {
        pcntl_async_signals(TRUE);

        foreach ([SIGINT, SIGTERM, SIGHUP] as $signal)
        {
            pcntl_signal($signal, static function () use ($restaurer): void {
                $restaurer();
                exit(130);
            });
        }
    }

    return $origine;
}
