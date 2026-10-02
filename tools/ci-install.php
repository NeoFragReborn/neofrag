<?php
declare(strict_types=1);

/**
 * ci-install — installation non interactive, pour la CI ou un montage local rapide.
 *
 * Famille : outil
 *
 * Ce qu'il fait
 * -------------
 * Monte une instance NeoFrag Reborn INSTALLÉE (tous les modules, widgets et thèmes, plus un
 * administrateur) sans passer par l'assistant web, en réutilisant l'Installer standalone (mysqli +
 * système de fichiers, sans booter le framework). L'administrateur créé fournit un membre avec
 * e-mail, ce qui permet à `check-smoke` d'exercer le flux anti_flood. Conçu pour une base FRAÎCHE.
 *
 * Il ÉCRIT `config/` (db, crypt, password, email, neofrag) : c'est une installation, pas un
 * contrôle. Ne pas le lancer sur une installation en service.
 *
 * Paramètres par variables d'environnement (défauts = pile docker-compose) :
 *   NF_DB_HOST (db) · NF_DB_PORT (3306) · NF_DB_USER (neofrag) · NF_DB_PASS (neofragpass) · NF_DB_NAME (neofrag)
 *   NF_ADMIN_EMAIL (ci-admin@example.test) · NF_ADMIN_PASS (CI-Smoke-2026!)
 *
 * Usage
 * -----
 *   php tools/ci-install.php
 *   docker compose exec -T -e NF_DB_HOST=db web php tools/ci-install.php
 */

require __DIR__.'/lib/outil.php';
require nf_racine().'/neofrag/installer.php';

use NF\NeoFrag\Installer;

$root  = nf_racine();
$host  = getenv('NF_DB_HOST') ?: 'db';
$port  = (int) (getenv('NF_DB_PORT') ?: 3306);
$user  = getenv('NF_DB_USER') ?: 'neofrag';
$pass  = getenv('NF_DB_PASS') ?: 'neofragpass';
$name  = getenv('NF_DB_NAME') ?: 'neofrag';
$email = getenv('NF_ADMIN_EMAIL') ?: 'ci-admin@example.test';
$apass = getenv('NF_ADMIN_PASS') ?: 'CI-Smoke-2026!';

echo "[ci-install] config + connexion ($user@$host:$port/$name)…\n";

// 1. Fichiers de config (db/crypt/password/email) + neofrag.php (defines runtime).
Installer::write_config($root.'/config', [
    'hostname' => $host, 'username' => $user, 'password' => $pass, 'database' => $name, 'port' => $port,
]);

file_put_contents($root.'/config/neofrag.php',
    "<?php\n\n"
    ."define('NEOFRAG_DEBUG_BAR', FALSE);\n"
    ."define('NEOFRAG_SAFE_MODE', FALSE);\n"
    ."define('NEOFRAG_DEMO',      FALSE);\n"
    ."define('NEOFRAG_LOGS',      FALSE);\n"
    ."define('NEOFRAG_LOGS_I18N', FALSE);\n"
);

// La connexion passe par l'Installer, comme l'assistant web : c'est une base FRAÎCHE, que
// config/db.php ne désigne pas encore au moment où l'outil démarre.
try
{
    $db = Installer::connect(['hostname' => $host, 'username' => $user, 'password' => $pass, 'database' => $name, 'port' => $port]);
}
catch (\Throwable $e)
{
    nf_refus('connexion DB échouée : '.$e->getMessage());
}

// 2. Schéma + seed du cœur (Tier 0) + migrations. (Les modules apportent leurs tables à l'étape 3.)
echo "[ci-install] schéma + seed + migrations…\n";
Installer::import_sql_file($db, $root.'/install/schema.sql');
Installer::import_sql_file($db, $root.'/install/seed.sql');
Installer::run_migrations($db, $root.'/migrations', NULL);

// 3. Tout bundlé : installe TOUS les modules/widgets/thèmes locaux → des pages publiques réelles
//    (news/forum/…) pour le smoke.
echo "[ci-install] install complète (tous les modules)…\n";
$summary = Installer::install_complete($db, $root);

if (!empty($summary['errors']))
{
    nf_refus('erreurs install : '.implode(' | ', $summary['errors']));
}

// 3b. Contenu du PAQUET PRINCIPAL (fidèle au vrai installeur web, cf. install/index.php) : doc wiki
//     + mise en page de la vitrine. Sans ça, l'accueil vitrine et le wiki sont vides.
echo "[ci-install] contenu vitrine (wiki + mise en page)…\n";

if (is_file($wiki_sql = $root.'/install/wiki.sql') && Installer::table_exists($db, 'nf_wiki_pages'))
{
    Installer::import_sql_file($db, $wiki_sql);
}

if (is_file($vitrine_sql = $root.'/install/vitrine.sql'))
{
    try
    {
        Installer::import_sql_file($db, $vitrine_sql);
    }
    catch (\Throwable $e)
    {
        nf_avertir('[ci-install] vitrine.sql ignoré : '.$e->getMessage());
    }
}

// 4. Admin (fournit un membre avec email pour le flux anti_flood du smoke).
echo "[ci-install] admin…\n";
Installer::create_admin($db, ['username' => 'ci-admin', 'email' => $email, 'password' => $apass]);

echo "[ci-install] OK — install complète + admin {$email}\n";
echo "  → smoke : php tools/check-smoke.php http://localhost:8080 --email={$email}\n";
exit(NF_OK);
