<?php
declare(strict_types=1);
/**
 * check-htaccess — les `.htaccess` du paquet refusent tout ce que le Caddy de production refuse.
 *
 * Famille : statique
 *
 * Pourquoi
 * --------
 * La production tourne sous **Caddy**, et sa configuration est tenue à jour parce qu'on l'exerce
 * tous les jours. Le produit, lui, vise les hébergements **mutualisés**, qui tournent sous Apache
 * et n'ont que les `.htaccess` du paquet. Personne n'exerce ceux-là : ils ne sont vérifiés par
 * aucune requête, sur aucune machine.
 *
 * Les deux ont donc divergé, et dans le mauvais sens. Mesuré le 2026-09-21 : le `.htaccess` racine
 * ne refusait ni `.neon` ni `.md`, et quatre dossiers n'avaient aucune garde — `cache`, `install`,
 * `tools`, `tests`, `docs`. Conséquence la plus concrète : sur **toute installation Apache**,
 * `/les notes du mainteneur` était servi en texte, avec les fiches de travail, les notes
 * d'exploitation et les chemins du serveur.
 *
 * Ce contrôle relit la liste ci-dessous à chaque passage. Elle est la même que celle de la
 * configuration Caddy de production ; quand l'une bouge, l'autre doit suivre, et c'est ici qu'on
 * s'en aperçoit.
 *
 * Ce qu'il ne fait pas
 * --------------------
 * Il ne lance pas Apache. Il n'y en a pas sur le serveur — l'installer à côté de Caddy pour un
 * contrôle lui disputerait le port 80. Il vérifie donc que les RÈGLES sont écrites, pas qu'un
 * Apache réel les applique. C'est une limite, et elle est réelle : un `AllowOverride None` chez un
 * hébergeur rend ces fichiers inopérants, quoi qu'ils contiennent.
 *
 * Usage
 * -----
 *   php tools/check-htaccess.php
 *   php tools/check-htaccess.php --verbeux
 */

require __DIR__.'/lib/outil.php';

[$o]     = nf_options(['verbeux' => FALSE]);
$racine  = nf_racine();
$verbeux = $o['verbeux'];

/*
 * Les dossiers qui ne doivent JAMAIS être joignables en HTTP, et pourquoi.
 *
 * La raison compte autant que la règle : c'est elle qui permet de trancher, le jour où l'on se
 * demande si une garde peut sauter.
 */
const DOSSIERS_INTERDITS = [
    'config'  => 'identifiants de base, clé de chiffrement, sel des mots de passe, SMTP',
    'backups' => 'archives complètes du site, base comprise',
    'logs'    => 'journaux applicatifs : adresses, erreurs, parfois des données de membres',
    'cache'   => 'dérivés internes, parfois issus de contenu réservé',
    'install' => "l'installateur et ses schémas SQL",
    'tools'   => 'migrations, sauvegardes, extraction de schéma — exécutables',
    'tests'   => 'la suite de tests : décrit le fonctionnement interne',
    'docs'    => "fiches de travail, notes d'exploitation, chemins du serveur",
    'upload'  => 'fichiers déposés par les membres : à servir, mais jamais à exécuter',
];

/*
 * Les extensions refusées à la racine, quelle que soit leur place dans l'arborescence.
 *
 * Même liste que la directive `@interdit` du Caddyfile de production.
 */
const EXTENSIONS_INTERDITES = ['sql', 'lock', 'scssc', 'map', 'dist', 'ini', 'sh', 'neon', 'md'];

/*
 * Les fichiers refusés par leur NOM, faute de pouvoir l'être par leur extension : `.json` et
 * `.js` se servent légitimement partout ailleurs. Ce sont les trois fichiers de l'outillage
 * front, ajoutés le 2026-09-22 avec ESLint. Aucun ne porte de secret ; la raison est la même que
 * pour `tools/` et `tests/` : rien de ce qui sert à DÉVELOPPER n'a de raison d'être servi.
 */
const FICHIERS_INTERDITS = ['package.json', 'package-lock.json', 'eslint.config.js', 'playwright.config.js'];

/** Un fichier `.htaccess` refuse-t-il tout accès ? */
function refuse_tout(string $chemin): bool
{
    if (!is_file($chemin))
    {
        return FALSE;
    }

    $contenu = (string) file_get_contents($chemin);

    // Les deux écritures d'Apache, selon la version : `Require all denied` (2.4) et
    // `Deny from all` (2.2). Une seule suffit si l'autre est dans un `<IfModule>` complémentaire.
    return (bool) preg_match('/^\s*Require\s+all\s+denied\s*$/mi', $contenu)
        || (bool) preg_match('/^\s*Deny\s+from\s+all\s*$/mi', $contenu);
}

$manques = [];

// ── Les dossiers ────────────────────────────────────────────────────────────
foreach (DOSSIERS_INTERDITS as $dossier => $pourquoi)
{
    $chemin = $racine.'/'.$dossier;

    if (!is_dir($chemin))
    {
        // Un dossier absent du dépôt n'est pas un manque : `cache` et `logs` naissent à l'usage.
        continue;
    }

    // `upload` est le seul à devoir rester SERVI : on y interdit l'exécution, pas la lecture.
    if ($dossier === 'upload')
    {
        $contenu = is_file($chemin.'/.htaccess') ? (string) file_get_contents($chemin.'/.htaccess') : '';

        if (!preg_match('/php_flag\s+engine\s+off|RemoveHandler|SetHandler|php_admin_flag\s+engine\s+off|Require\s+all\s+denied/i', $contenu))
        {
            $manques[] = ['upload/.htaccess', "n'empêche pas l'exécution de ce qui y est déposé", $pourquoi];
        }
        else if ($verbeux)
        {
            printf("  ok    %-22s exécution neutralisée\n", 'upload/.htaccess');
        }

        continue;
    }

    if (!refuse_tout($chemin.'/.htaccess'))
    {
        $manques[] = [$dossier.'/.htaccess', is_file($chemin.'/.htaccess') ? 'ne refuse pas tout accès' : 'absent', $pourquoi];
    }
    else if ($verbeux)
    {
        printf("  ok    %-22s refuse tout accès\n", $dossier.'/.htaccess');
    }
}

// ── Les extensions ──────────────────────────────────────────────────────────
$racine_htaccess = $racine.'/.htaccess';

if (!is_file($racine_htaccess))
{
    $manques[] = ['.htaccess', 'absent à la racine', 'aucune extension sensible refusée'];
}
else
{
    $contenu = (string) file_get_contents($racine_htaccess);

    foreach (EXTENSIONS_INTERDITES as $extension)
    {
        // On cherche l'extension dans un `<FilesMatch>` qui refuse. Le motif reste large : il
        // s'agit de repérer un OUBLI, pas de valider une expression régulière d'Apache.
        if (!preg_match('/<FilesMatch[^>]*\b'.preg_quote($extension, '/').'\b/i', $contenu))
        {
            $manques[] = ['.htaccess', 'ne refuse pas les fichiers `.'.$extension.'`', 'refusé par Caddy en production'];
        }
        else if ($verbeux)
        {
            printf("  ok    %-22s .%s refusé\n", '.htaccess', $extension);
        }
    }

    foreach (FICHIERS_INTERDITS as $fichier)
    {
        // Un `<Files>` ou un `<FilesMatch>` qui nomme le fichier : là encore, on repère un OUBLI.
        if (!preg_match('/<Files(?:Match)?[^>]*'.preg_quote(str_replace('.', '\\.', $fichier), '/').'/i', $contenu))
        {
            $manques[] = ['.htaccess', 'ne refuse pas `'.$fichier.'`', 'refusé par Caddy en production'];
        }
        else if ($verbeux)
        {
            printf("  ok    %-22s %s refusé\n", '.htaccess', $fichier);
        }
    }
}

// ── Le verdict ──────────────────────────────────────────────────────────────
printf("%d dossier(s), %d extension(s) et %d fichier(s) surveillés.\n\n",
    count(DOSSIERS_INTERDITS), count(EXTENSIONS_INTERDITES), count(FICHIERS_INTERDITS));

if (!$manques)
{
    nf_ok('Apache refuse tout ce que Caddy refuse');
}

echo "CE QUE CADDY REFUSE ET QU'APACHE LAISSE PASSER :\n\n";

foreach ($manques as [$fichier, $quoi, $pourquoi])
{
    printf("  %-22s %s\n      → %s\n", $fichier, $quoi, $pourquoi);
}

echo "\nLa production tourne sous Caddy, dont la configuration est exercée tous les jours. Ces\n";
echo "fichiers-ci ne le sont par personne : ils ne valent que pour les hébergements mutualisés,\n";
echo "que ce produit vise, et c'est précisément là que l'écart ne se voit pas.\n";

nf_echec(count($manques).' garde(s) Apache manquante(s) ou incomplète(s)');
