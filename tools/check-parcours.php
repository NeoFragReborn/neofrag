<?php
declare(strict_types=1);

/**
 * check-parcours — suit un visiteur d'un écran au suivant, dans un vrai navigateur.
 *
 * Famille : cible
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le projet mesurait déjà beaucoup de choses, et aucune ne suivait un GESTE. `check-liens` vérifie
 * que les adresses répondent, `check-js-console` qu'aucun script ne plante, `check-smoke` qu'un flux
 * HTTP ne rend pas de 5xx, `check-demo-ecriture` qu'une écriture passe ou est refusée. Tous
 * rejouent des REQUÊTES. Or ce qui casse dans une interface casse ENTRE deux requêtes : un lien
 * qu'aucun clic n'atteint, une modale qui ne s'ouvre pas, un formulaire qui renvoie sur une page
 * technique.
 *
 * Ce contrôle est le seul à cliquer. Dès son premier passage, le 2026-09-22, il a trouvé un défaut
 * qu'aucun autre ne pouvait voir : le sélecteur de langue du pied de page — présent dans CINQ
 * thèmes, dont celui par défaut — s'envoyait nativement vers une route `ajax/`, et le visiteur qui
 * changeait de langue atterrissait sur `{"redirect":"…"}` affiché en texte brut.
 *
 * Ce qu'il n'écrit pas
 * --------------------
 * Rien. Les écritures réelles — livre d'or, administration, et surtout ce qui doit être REFUSÉ en
 * démonstration — restent à `check-demo-ecriture`, qui les rejoue en HTTP et défait tout ce qu'il a
 * écrit. Un parcours de navigateur ne saurait pas défaire aussi proprement ; il s'en tient donc à ce
 * qu'il est seul à pouvoir faire.
 *
 * Famille « cible » : il n'entre pas dans la batterie par défaut. Les parcours durent, et ils
 * demandent un site PEUPLÉ — la démonstration, ou une installation qu'on vient de garnir.
 *
 * Usage
 * -----
 *   php tools/check-parcours.php                                   sert cette installation et la parcourt
 *   php tools/check-parcours.php --base=https://site.tld/demo      parcourt un site déjà servi
 *   php tools/check-parcours.php --parcours=langue                 un seul fichier de parcours
 *   php tools/check-parcours.php --compte=demo --motdepasse=demo   le compte employé pour se connecter
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/navigateur.php';
require __DIR__.'/lib/site.php';

[$o] = nf_options([
    'base'       => '',
    'parcours'   => '',
    'compte'     => 'demo',
    'motdepasse' => 'demo',
    'port'       => 0,
]);

$racine = nf_racine();

// ── Les prérequis, dits clairement plutôt que devinés ───────────────────────────────────────
if (!is_dir($racine.'/node_modules/@playwright/test'))
{
    nf_refus('Playwright n\'est pas installé — lancer `npm install` à la racine du dépôt');
}

$npx = trim((string) @shell_exec(stripos(PHP_OS, 'WIN') === 0 ? 'where npx 2>NUL' : 'command -v npx 2>/dev/null'));
$npx = $npx === '' ? '' : explode("\n", $npx)[0];

if ($npx === '' || !is_file($npx))
{
    nf_refus('npx introuvable — installer Node.js');
}

if (!nf_chrome_utilisable(nf_chrome()))
{
    nf_refus('aucun Chrome utilisable — les parcours emploient celui du système, pas un moteur téléchargé');
}

// ── Le site à parcourir : celui qu'on donne, ou celui qu'on sert ────────────────────────────
$serveur = NULL;

if ($o['base'] !== '')
{
    // Un site distant : on ne peut rien vérifier de ses comptes, et c'est à l'appelant de savoir
    // lequel il donne. La démonstration annonce le sien sur son propre bandeau.
    $base = rtrim((string) $o['base'], '/').'/';
}
else
{
    /*
     * On sert CETTE installation. On peut donc vérifier, avant de partir, que le compte demandé
     * existe — et refuser proprement s'il n'existe pas.
     *
     * La distinction compte : sans elle, parcourir un site d'essai avec le compte de la démonstration
     * rendait « 2 parcours échoués », ce qui se lit comme une connexion cassée alors que le compte
     * n'était simplement pas là. Un contrôle qui accuse le produit d'un défaut de son terrain est
     * pire qu'un contrôle absent.
     */
    $db = nf_connexion();

    if (!nf_table_existe($db, 'nf_user'))
    {
        nf_refus('cette installation n\'a pas de table des membres : elle n\'est pas installée');
    }

    $existe = nf_scalar($db, 'SELECT COUNT(*) FROM nf_user WHERE username = "'.$db->real_escape_string((string) $o['compte']).'"');

    if ((int) $existe === 0)
    {
        $premier = (string) (nf_scalar($db, 'SELECT username FROM nf_user WHERE admin = "1" ORDER BY id LIMIT 1') ?? '');

        nf_refus(sprintf(
            'le compte « %s » n\'existe pas sur cette installation : les parcours de membre ne peuvent pas être mesurés.%s',
            (string) $o['compte'],
            $premier !== '' ? ' Son administrateur est « '.$premier.' » — le passer avec --compte= et --motdepasse=.' : ''
        ));
    }

    $serveur = nf_serveur(nf_port((int) $o['port']));
    $base    = rtrim($serveur->base, '/').'/';
}

printf("Parcours de %s\n\n", $base);

// ── Playwright, en JSON pour que le verdict ne dépende pas d'un format d'affichage ───────────
$rapport = $racine.'/cache/e2e/rapport.json';
@mkdir(dirname($rapport), 0775, TRUE);
@unlink($rapport);

/*
 * Pas de `--reporter=` ici : le drapeau REMPLACE les rapporteurs de playwright.config.js et écrit
 * sur la sortie standard, si bien que le fichier de rapport n'était jamais produit. La configuration
 * en décide, et elle écrit `cache/e2e/rapport.json`.
 *
 * La sortie de Playwright est gardée : quand il refuse de démarrer — un moteur absent, une
 * configuration illisible — c'est la seule chose qui dise pourquoi.
 */
$trace = nf_temp('playwright.out');

$commande = sprintf(
    'cd %s && NF_BASE=%s NF_PARCOURS_COMPTE=%s NF_PARCOURS_MOTDEPASSE=%s %s playwright test %s > %s 2>&1',
    escapeshellarg($racine),
    escapeshellarg($base),
    escapeshellarg((string) $o['compte']),
    escapeshellarg((string) $o['motdepasse']),
    escapeshellarg($npx),
    $o['parcours'] !== '' ? escapeshellarg('tests/E2E/'.$o['parcours'].'.spec.js') : '',
    escapeshellarg($trace)
);

@shell_exec($commande);

if ($serveur !== NULL)
{
    $serveur->arreter();
}

if (!is_file($rapport))
{
    $dit = is_file($trace) ? trim((string) file_get_contents($trace)) : '';

    nf_refus('Playwright n\'a rendu aucun rapport'.($dit !== '' ? " — il dit :\n".substr($dit, -600) : ' et n\'a rien dit'));
}

$json = json_decode((string) file_get_contents($rapport), TRUE);

if (!is_array($json) || empty($json['suites']))
{
    nf_refus('le rapport de Playwright est vide : aucun parcours n\'a été joué');
}

// ── Le dépouillement ────────────────────────────────────────────────────────────────────────
$reussis = [];
$echoues = [];
$sautes  = [];

$parcourir = static function(array $suites, string $chemin = '') use (&$parcourir, &$reussis, &$echoues, &$sautes): void {
    foreach ($suites as $suite)
    {
        $ici = trim($chemin.' › '.(string) ($suite['title'] ?? ''), ' ›');

        foreach ($suite['specs'] ?? [] as $spec)
        {
            $nom    = $ici.' › '.(string) ($spec['title'] ?? '');
            $statut = 'inconnu';
            $raison = '';

            foreach ($spec['tests'] ?? [] as $test)
            {
                $statut = (string) ($test['status'] ?? 'inconnu');

                foreach ($test['results'] ?? [] as $resultat)
                {
                    if (!empty($resultat['error']['message']))
                    {
                        $raison = trim(strtok((string) $resultat['error']['message'], "\n") ?: '');
                    }
                }
            }

            if ($statut === 'expected')
            {
                $reussis[] = $nom;
            }
            elseif ($statut === 'skipped')
            {
                $sautes[] = $nom;
            }
            else
            {
                $echoues[] = [$nom, $raison];
            }
        }

        if (!empty($suite['suites']))
        {
            $parcourir($suite['suites'], $ici);
        }
    }
};

$parcourir($json['suites']);

foreach ($reussis as $nom)
{
    echo '  ✓ ', $nom, "\n";
}

foreach ($sautes as $nom)
{
    echo '  – ', $nom, "  (sauté)\n";
}

foreach ($echoues as [$nom, $raison])
{
    printf("  ✗ %s\n      %s\n", $nom, $raison !== '' ? $raison : 'sans message');
}

printf("\n%d parcours : %d réussi(s), %d échoué(s), %d sauté(s).\n",
    count($reussis) + count($echoues) + count($sautes), count($reussis), count($echoues), count($sautes));

/*
 * Un parcours sauté n'est PAS un parcours réussi. Les fichiers de `tests/E2E/` n'emploient jamais
 * `test.skip` : un saut voudrait dire que Playwright a renoncé, et conclure vert là-dessus est
 * exactement la façon dont un contrôle cesse de mesurer sans que personne ne s'en aperçoive.
 */
if ($sautes)
{
    nf_refus(sprintf('%d parcours sauté(s) : ce site n\'avait pas de quoi être mesuré, ou Playwright a renoncé', count($sautes)));
}

if ($echoues)
{
    nf_echec(sprintf('%d parcours sur %d', count($echoues), count($reussis) + count($echoues)));
}

if (!$reussis)
{
    nf_refus('aucun parcours n\'a été joué');
}

nf_ok(sprintf('%d parcours suivis de bout en bout dans un vrai navigateur', count($reussis)));
