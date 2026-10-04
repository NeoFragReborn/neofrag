<?php
declare(strict_types=1);

/**
 * prepare-test-db — prépare la base de données des tests d'intégration.
 *
 * Famille : outil
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Les tests d'intégration du projet se sautent eux-mêmes quand la base est injoignable, pour que
 * la suite unitaire reste verte hors d'un environnement complet. Ce filet, silencieux par nature,
 * a fini par tout retenir : l'hôte par défaut était `db`, le nom du service de l'ancienne pile
 * Docker. Cette pile ayant disparu, les 56 tests étaient sautés sans que rien ne l'indique — ils
 * n'ont donc jamais rien vérifié.
 *
 * Plutôt que de demander à chacun de deviner cinq variables d'environnement, cet outil crée la
 * base, l'utilisateur et le schéma, vérifie que la connexion des tests fonctionne vraiment, puis
 * ÉCRIT `config/db-test.php` — un fichier non versionné que les classes de base des tests relisent.
 * Une variable d'environnement ne survit pas à la fermeture d'un terminal : le 2026-09-20, 18
 * suites se sautaient à nouveau faute de les avoir reposées.
 *
 * Il ne touche JAMAIS la base du site : il crée une base séparée, dont le nom porte le suffixe
 * `_test`, et n'accepte pas de travailler sur celle que config/db.php désigne.
 *
 * Usage
 * -----
 *   php tools/prepare-test-db.php                    utilise config/db.php pour se connecter
 *   php tools/prepare-test-db.php --root-user=root --root-pass=secret
 *   php tools/prepare-test-db.php --name=neofrag_test --user=… --pass=… --host=127.0.0.1 --port=3306
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/sql.php';

[$o] = nf_options([
    'host'      => getenv('NF_TEST_DB_HOST') ?: '127.0.0.1',
    'port'      => (int) (getenv('NF_TEST_DB_PORT') ?: 3306),
    'name'      => getenv('NF_TEST_DB_NAME') ?: 'neofrag_test',
    'user'      => getenv('NF_TEST_DB_USER') ?: 'neofrag_test',
    'pass'      => getenv('NF_TEST_DB_PASS') ?: 'neofrag_test',
    'root-user' => '',
    'root-pass' => '',
]);

// ── Garde-fous, appliqués quels que soient les identifiants employés ─────────
// Cet outil charge un schéma : lancé sur la mauvaise base, il écrase un site.
if (!preg_match('/_test$/', $o['name']))
{
    nf_refus("le nom de la base de test doit se terminer par « _test » (reçu : {$o['name']})");
}

$site = nf_config_db();

if ($site !== NULL && $site['database'] === $o['name'])
{
    nf_refus("« {$o['name']} » est la base du SITE (config/db.php). Choisir un autre nom avec --name");
}

// ── Identifiants d'administration de MySQL ───────────────────────────────────
// Par défaut on réutilise ceux du site : ils suffisent souvent, et évitent d'avoir à taper un mot
// de passe root sur la ligne de commande, où il resterait dans l'historique du shell.
if ($o['root-user'] === '')
{
    if ($site === NULL || $site['username'] === '')
    {
        nf_refus('config/db.php est absent ou inexploitable : préciser --root-user et --root-pass');
    }

    $o['root-user'] = $site['username'];
    $o['root-pass'] = $site['password'];
}

$admin = nf_connexion_admin(['hostname' => $o['host'], 'port' => $o['port'], 'username' => $o['root-user'], 'password' => $o['root-pass']]);

/*
 * L'hôte d'où le serveur nous voit arriver. Sur GitHub Actions, MariaDB tourne dans un conteneur
 * et voit le client venir de la passerelle Docker (172.18.0.1), pas de 127.0.0.1 : un compte créé
 * pour `localhost` et `127.0.0.1` seulement y est refusé (« Access denied for user
 * 'neofrag_test'@'172.18.0.1' »). On crée donc le compte aussi pour cet hôte-là — sans jamais
 * employer le joker `%`, qui ouvrirait un compte au mot de passe connu à tout le réseau.
 */
$hote_client = (string) nf_scalar($admin, "SELECT SUBSTRING_INDEX(USER(), '@', -1)");
$hotes       = array_unique(array_filter(['localhost', '127.0.0.1', $hote_client]));

/*
 * Une étape d'ADMINISTRATION a le droit d'échouer faute de privilèges : sur un hébergement
 * mutualisé — et sur un site d'essai — le compte a les droits sur SA base et rien d'autre. Ce n'est pas
 * un blanc-seing : la vérification finale reste la connexion des TESTS.
 */
$sautees = [];
$tenter  = static function (string $sql, string $etape) use ($admin, &$sautees): void {
    if (!$admin->query($sql))
    {
        $sautees[] = $etape.' — '.$admin->error;
    }
};

printf("Base de test   : %s@%s:%d/%s\n", $o['user'], $o['host'], $o['port'], $o['name']);

$nom = $admin->real_escape_string($o['name']);
$tenter(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $nom), 'création de la base');

$motdepasse = "'".$admin->real_escape_string($o['pass'])."'";

foreach ($hotes as $hote)
{
    $compte = sprintf("'%s'@'%s'", $admin->real_escape_string($o['user']), $admin->real_escape_string($hote));

    $tenter("CREATE USER IF NOT EXISTS $compte IDENTIFIED BY $motdepasse", "création de $compte");
    $tenter("ALTER USER $compte IDENTIFIED BY $motdepasse", "mot de passe de $compte");
    $tenter(sprintf('GRANT ALL PRIVILEGES ON `%s`.* TO %s', $nom, $compte), "droits de $compte");
}

$tenter('FLUSH PRIVILEGES', 'rechargement des droits');

if ($sautees)
{
    printf("Étapes d'administration sautées (droits insuffisants) : %d\n", count($sautees));
    echo "  → sans conséquence si la base et le compte existent déjà ; la vérification finale tranche.\n";
}

// ── Schéma ───────────────────────────────────────────────────────────────────
// Le schéma du projet est en DEUX morceaux : install/schema.sql pose le cœur, et chaque addon livre
// ses propres tables dans install/install.sql. Ne charger que le premier laissait 17 tests
// d'intégration en échec sur des tables absentes.
if (!$admin->query(sprintf('USE `%s`', $nom)))
{
    nf_refus("sélection de la base : {$admin->error}");
}

$schema = nf_racine().'/install/schema.sql';

if (!is_file($schema))
{
    nf_refus('install/schema.sql est introuvable');
}

if (($erreur = nf_sql_jouer_fichier($admin, $schema)) !== NULL)
{
    nf_refus("échec du chargement du schéma du cœur : $erreur");
}

$charges = 0;
$echecs  = [];

foreach (['modules', 'widgets', 'themes', 'addons'] as $famille)
{
    foreach (glob(nf_racine().'/'.$famille.'/*/install/install.sql') ?: [] as $fichier_sql)
    {
        $addon = basename(dirname(dirname($fichier_sql)));

        if (($erreur = nf_sql_jouer_fichier($admin, $fichier_sql)) !== NULL)
        {
            $echecs[] = $famille.'/'.$addon.' : '.$erreur;
            continue;
        }

        $charges++;
    }
}

printf("Schémas d'addons : %d chargés%s\n", $charges, $echecs ? sprintf(', %d en échec', count($echecs)) : '');

foreach ($echecs as $echec)
{
    echo "  ! $echec\n";
}

$tables = (int) nf_scalar($admin, sprintf("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '%s'", $nom));
$admin->close();

printf("Schéma chargé  : %d tables\n", $tables);

// ── Vérification finale : c'est la connexion des TESTS qui doit marcher ──────
$test = nf_connexion_admin(['hostname' => $o['host'], 'port' => $o['port'], 'username' => $o['user'], 'password' => $o['pass']], 'de test');

if (!$test->select_db($o['name']))
{
    nf_refus("l'utilisateur de test se connecte mais n'atteint pas la base « {$o['name']} » : {$test->error}");
}

$test->close();

// `config/` n'est pas versionné (cf. `.gitignore`) : ces identifiants restent sur la machine.
// `tests/Integration/IntegrationTestCase.php` relit ce fichier quand l'environnement est muet.
$destination = nf_racine().'/config/db-test.php';

$contenu = "<?php\n"
    ."/*\n"
    ." * Identifiants de la base des tests d'INTÉGRATION, écrits par tools/prepare-test-db.php\n"
    ." * le ".date('Y-m-d H:i').".\n"
    ." *\n"
    ." * Ce fichier n'est PAS versionné : il décrit cette machine-ci. Les variables\n"
    ." * d'environnement NF_TEST_DB_* le supplantent si elles sont posées.\n"
    ." */\n"
    .'return '
    .var_export([
        'host' => $o['host'],
        'port' => (string) $o['port'],
        'name' => $o['name'],
        'user' => $o['user'],
        'pass' => $o['pass'],
        // Le compte d'ADMINISTRATION, pour `InstallerDbTest` : lui seul déroule une installation
        // complète dans une base éphémère, qu'il crée et supprime.
        'root_user' => $o['root-user'],
        'root_pass' => $o['root-pass'],
    ], TRUE)
    .";\n";

if (@file_put_contents($destination, $contenu) === FALSE)
{
    echo "\nImpossible d'écrire config/db-test.php — exporter les réglages à la main :\n";
    printf("  export NF_TEST_DB_HOST=%s NF_TEST_DB_PORT=%d NF_TEST_DB_NAME=%s NF_TEST_DB_USER=%s NF_TEST_DB_PASS=%s\n",
        $o['host'], $o['port'], $o['name'], $o['user'], $o['pass']);
}
else
{
    @chmod($destination, 0640);
    echo "\nRéglages écrits dans config/db-test.php (non versionné) : la suite les retrouvera seule.\n";
}

nf_ok('base de test prête — lancer la suite complète : vendor/bin/phpunit --fail-on-skipped');
