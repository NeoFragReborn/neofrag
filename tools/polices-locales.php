<?php
declare(strict_types=1);

/**
 * polices-locales — les polices des thèmes et du réglage « Police du site », servies par le site lui-même.
 *
 * Famille : outil
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Les thèmes chargeaient leurs polices depuis Google Fonts (`@import` de fonts.googleapis.com) : le
 * navigateur de CHAQUE visiteur, sur CHAQUE page, envoyait son adresse IP à Google, sans qu'on le lui
 * demande — mesuré sur la vitrine et la démo, dans les sept thèmes, le 2026-10-08. Une adresse IP est une
 * donnée personnelle (CJUE, Breyer, C-582/14) ; un tribunal allemand a jugé cette transmission illicite
 * sans consentement (LG München I, 20 janvier 2022, 3 O 17493/20), au motif que la même police peut être
 * servie par le site lui-même. C'est ce que fait cet outil : plus aucun tiers, plus rien à consentir.
 *
 * Ce qu'il fait
 * -------------
 * Pour chaque police de POLICES, il demande la feuille `css2` de Google comme un navigateur récent (qui
 * reçoit du woff2), ne garde que les sous-ensembles « latin » et « latin-ext » (les six langues du
 * produit), télécharge chaque fichier dans `fonts/<nom>/` et écrit `css/fonts/<nom>.css`, dont les
 * adresses passent par `path(…, 'fonts')` — exactement comme Open Sans et Titillium Web, locales depuis
 * longtemps. Un thème les charge par `->css('fonts/<nom>')`. Toutes ces polices sont sous SIL Open Font
 * License 1.1 (LICENSES/OFL-1.1.txt) ; la NOTICE les nomme (tools/check-notice.php).
 *
 * L'outil ne se lance qu'à l'ajout d'une police : son résultat est versionné, le site ne contacte jamais
 * Google. Relancé, il réécrit à l'identique ce qui n'a pas changé.
 *
 * Usage
 * -----
 *   php tools/polices-locales.php                 toutes
 *   php tools/polices-locales.php --nom=inter     une seule
 *   php tools/polices-locales.php --liste         la liste, sans rien télécharger
 */

require __DIR__.'/lib/outil.php';

[$o] = nf_options(['nom' => '', 'liste' => FALSE]);

/**
 * Le dossier => [nom de la famille, requête css2 (sans « family= » ni « display »)]. Les graisses sont celles
 * que les thèmes emploient, et 400 à 700 pour les polices du réglage « Police du site » (helpers/fonts.php).
 */
const POLICES = [
    // Les thèmes
    'inter'               => ['Inter', 'Inter:wght@400;500;600;700'],
    'space-grotesk'       => ['Space Grotesk', 'Space+Grotesk:wght@400;500;600;700'],
    'jetbrains-mono'      => ['JetBrains Mono', 'JetBrains+Mono:wght@400;500;600;700'],
    'saira-condensed'     => ['Saira Condensed', 'Saira+Condensed:wght@600;700;800'],
    'albert-sans'         => ['Albert Sans', 'Albert+Sans:wght@400;500;600;700'],
    'playfair-display'    => ['Playfair Display', 'Playfair+Display:ital,wght@0,700;0,900;1,700'],
    'source-serif-4'      => ['Source Serif 4', 'Source+Serif+4:ital,wght@0,400;0,600;0,700;1,400'],
    'jersey-10'           => ['Jersey 10', 'Jersey+10'],
    'rubik'               => ['Rubik', 'Rubik:wght@400;500;600;700'],
    'vt323'               => ['VT323', 'VT323'],
    'fraunces'            => ['Fraunces', 'Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,800;1,9..144,500;1,9..144,800'],
    'work-sans'           => ['Work Sans', 'Work+Sans:wght@400;500;600;700'],
    'ibm-plex-mono'       => ['IBM Plex Mono', 'IBM+Plex+Mono:wght@500;600'],
    'bricolage-grotesque' => ['Bricolage Grotesque', 'Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800'],
    'manrope'             => ['Manrope', 'Manrope:wght@400;500;600;700;800'],
    'barlow'              => ['Barlow', 'Barlow:ital,wght@0,400;0,500;0,600;0,700;1,400'],
    'rajdhani'            => ['Rajdhani', 'Rajdhani:wght@500;600;700'],
    // Le réglage « Police du site » (Open Sans et Titillium Web sont déjà locales)
    'roboto'              => ['Roboto', 'Roboto:wght@400;500;600;700'],
    'lato'                => ['Lato', 'Lato:wght@400;700'],
    'nunito'              => ['Nunito', 'Nunito:wght@400;500;600;700'],
    'source-sans-3'       => ['Source Sans 3', 'Source+Sans+3:wght@400;500;600;700'],
    'montserrat'          => ['Montserrat', 'Montserrat:wght@400;500;600;700'],
    'poppins'             => ['Poppins', 'Poppins:wght@400;500;600;700'],
    'oswald'              => ['Oswald', 'Oswald:wght@400;500;600;700'],
];

/** Les sous-ensembles gardés : ceux des six langues du produit. */
const SOUS_ENSEMBLES = ['latin', 'latin-ext'];

$racine = nf_racine();
$liste  = $o['nom'] !== '' ? array_intersect_key(POLICES, [$o['nom'] => TRUE]) : POLICES;

if (!$liste)
{
    nf_refus("--nom={$o['nom']} : police inconnue (php tools/polices-locales.php --liste)");
}

if ($o['liste'])
{
    foreach ($liste as $dossier => [$famille, $requete])
    {
        printf("  %-20s %-20s %s\n", $dossier, $famille, $requete);
    }

    nf_ok(count($liste).' police(s)');
}

$contexte = stream_context_create(['http' => [
    'header'  => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36\r\n",
    'timeout' => 30,
]]);

$fichiers = 0;
$octets   = 0;

foreach ($liste as $dossier => [$famille, $requete])
{
    $css = @file_get_contents('https://fonts.googleapis.com/css2?family='.$requete.'&display=swap', FALSE, $contexte);

    if ($css === FALSE || stripos($css, '@font-face') === FALSE)
    {
        nf_echec("$famille : Google n'a pas rendu de feuille pour « $requete »");
    }

    // Chaque bloc est précédé du nom de son sous-ensemble : /* latin */ @font-face { … }
    preg_match_all('#/\*\s*([a-z0-9-]+)\s*\*/\s*(@font-face\s*\{[^}]*\})#i', $css, $blocs, PREG_SET_ORDER);
    $sortie = [];
    $vus    = [];

    @mkdir($racine.'/fonts/'.$dossier, 0775, TRUE);

    foreach ($blocs as [, $sous_ensemble, $bloc])
    {
        if (!in_array($sous_ensemble, SOUS_ENSEMBLES, TRUE) || !preg_match('#url\((https://fonts\.gstatic\.com/[^)]+\.woff2)\)#', $bloc, $u))
        {
            continue;
        }

        $nom_fichier = basename(parse_url($u[1], PHP_URL_PATH));
        $local       = $racine.'/fonts/'.$dossier.'/'.$nom_fichier;

        // Une police variable sert plusieurs graisses avec le même fichier : téléchargé une fois.
        if (!isset($vus[$nom_fichier]))
        {
            $vus[$nom_fichier] = TRUE;

            if (!is_file($local))
            {
                $donnees = @file_get_contents($u[1], FALSE, $contexte);

                if ($donnees === FALSE || strlen($donnees) < 100)
                {
                    nf_echec("$famille : fichier introuvable, $u[1]");
                }

                file_put_contents($local, $donnees);
            }

            $fichiers++;
            $octets += filesize($local);
        }

        $sortie[] = '/* '.$sous_ensemble." */\n".str_replace($u[0], 'url("<?php echo path(\''.$dossier.'/'.$nom_fichier.'\', \'fonts\') ?>")', trim($bloc));
    }

    if (!$sortie)
    {
        nf_echec("$famille : aucun sous-ensemble latin dans la feuille de Google");
    }

    // Les fichiers d'une version précédente que la feuille ne cite plus s'en vont.
    foreach (glob($racine.'/fonts/'.$dossier.'/*.woff2') ?: [] as $ancien)
    {
        if (!isset($vus[basename($ancien)]))
        {
            unlink($ancien);
        }
    }

    file_put_contents($racine.'/css/fonts/'.$dossier.'.css',
        "/* $famille — SIL Open Font License 1.1 (LICENSES/OFL-1.1.txt). Fichiers de fonts/$dossier/, tirés de\n".
        "   Google Fonts par tools/polices-locales.php : le site les sert lui-même, aucun visiteur n'est envoyé chez Google. */\n".
        implode("\n", $sortie)."\n");

    printf("  %-20s %d bloc(s), %d fichier(s)\n", $famille, count($sortie), count($vus));
}

nf_ok(sprintf('%d police(s), %d fichier(s) woff2, %.1f Mo', count($liste), $fichiers, $octets / 1048576));
