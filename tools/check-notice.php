<?php
declare(strict_types=1);

/**
 * check-notice — la NOTICE dit qui a écrit chaque addon, sous quelle licence, et ce que le produit embarque d'autrui.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Avant l'ouverture des dépôts publics (2026-10-04), aucun fichier ne disait ce que le paquet embarque
 * d'autrui — TinyMCE sous GPL-2.0, une bibliothèque TeamSpeak sous GPL-3.0, des polices sous OFL, un
 * thème sous CC BY-NC-SA —, et le champ `author` des addons s'écrivait de six façons, le pseudo du
 * mainteneur compris, quand il n'effaçait pas l'auteur d'origine.
 *
 * La NOTICE est donc ENGENDRÉE, jamais écrite à la main : les addons depuis leur déclaration
 * (`__info()` : author, license), les bibliothèques PHP depuis `composer.lock`, le reste depuis la liste
 * `TIERS` ci-dessous, que ce contrôle confronte au disque.
 *
 * Ce qu'il vérifie
 * ----------------
 *   1. l'auteur de chaque addon distribué a l'une des trois formes : les auteurs d'origine (BILCOT,
 *      VALENTIN), « <auteur> — portage NeoFrag Reborn », ou « NeoFrag Reborn » ; et sa licence est
 *      déclarée ;
 *   2. chaque élément de `TIERS` existe, et porte la version annoncée (un témoin dans son fichier) ;
 *   3. tout dossier de `js/` et de `fonts/`, et tout `js/*.min.js`, est déclaré dans `TIERS` : une
 *      bibliothèque ajoutée sans sa licence est refusée ;
 *   4. chaque licence citée a son texte dans `LICENSES/` ;
 *   5. la NOTICE à la racine est celle qu'on engendre (`--ecrire` la réécrit).
 *
 * Le dépôt `extensions`, qui porte les addons à la carte, a sa propre NOTICE : les auteurs et licences
 * de SES addons, et les remerciements qui les concernent (`--a-la-carte --montrer`).
 *
 * Usage
 * -----
 *   php tools/check-notice.php                         code 1 si la NOTICE n'est plus à jour
 *   php tools/check-notice.php --ecrire                la réécrit
 *   php tools/check-notice.php --montrer               l'affiche, sans rien écrire
 *   php tools/check-notice.php --a-la-carte --montrer  celle du dépôt des addons à la carte
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o]    = nf_options(['ecrire' => FALSE, 'montrer' => FALSE, 'a-la-carte' => FALSE]);

// La NOTICE dit qui a écrit CHAQUE addon du produit : sans les addons à la carte, elle serait fausse.
nf_exiger_assemblage('la NOTICE');

if ($o['a-la-carte'] && !$o['montrer'])
{
    nf_refus('la NOTICE des addons à la carte ne vit pas dans ce dépôt : --a-la-carte va avec --montrer');
}

/** Les addons à la carte, `module ads`… : le contenu du dépôt `extensions`. */
$a_la_carte = [];

foreach ((require __DIR__.'/lib/addons-manifest.php')['optional'] as $type => $noms)
{
    foreach ($noms as $nom)
    {
        $a_la_carte[] = $type.' '.$nom;
    }
}
$racine = nf_racine();
$fautes = [];

/**
 * Ce que le produit embarque d'autrui, hors Composer. `chemins` : ce qu'il occupe ; `temoin` : un
 * fichier et le texte qui y prouve la version annoncée ; `licences` : identifiants SPDX, dont le
 * texte est dans `LICENSES/`.
 */
const TIERS = [
    ['nom' => 'Bootstrap 5.3.8', 'auteur' => 'The Bootstrap Authors', 'licences' => ['MIT'],
     'chemins' => ['js/bootstrap.bundle.min.js'], 'temoin' => ['js/bootstrap.bundle.min.js', 'Bootstrap v5.3.8']],
    ['nom' => 'Chart.js 4.4.7', 'auteur' => 'Chart.js Contributors', 'licences' => ['MIT'],
     'chemins' => ['js/chart.umd.min.js'], 'temoin' => ['js/chart.umd.min.js', 'Chart.js v4.4.7']],
    ['nom' => 'CodeMirror 5.65.16', 'auteur' => 'Marijn Haverbeke et contributeurs', 'licences' => ['MIT'],
     'chemins' => ['js/codemirror'], 'temoin' => ['js/codemirror/5.65.16/codemirror.min.js', 'CodeMirror']],
    ['nom' => 'flatpickr 4.6.13', 'auteur' => 'Gregory Petrosyan et contributeurs', 'licences' => ['MIT'],
     'chemins' => ['js/flatpickr.min.js', 'js/flatpickr'], 'temoin' => ['js/flatpickr.min.js', 'flatpickr v4.6.13']],
    ['nom' => 'SortableJS 1.15.7', 'auteur' => 'les contributeurs de SortableJS', 'licences' => ['MIT'],
     'chemins' => ['js/sortable.lib.min.js'], 'temoin' => ['js/sortable.lib.min.js', 'Sortable 1.15.7']],
    ['nom' => 'Tom Select 2.6.1', 'auteur' => 'les contributeurs de Tom Select', 'licences' => ['Apache-2.0'],
     'chemins' => ['js/tom-select.complete.min.js'], 'temoin' => ['js/tom-select.complete.min.js', 'Tom Select v2.6.1']],
    ['nom' => 'ALTCHA', 'auteur' => 'Daniel Regeci, BAU Software s.r.o.', 'licences' => ['MIT'],
     'chemins' => ['js/altcha'], 'temoin' => ['js/altcha/LICENSE.txt', 'Daniel Regeci']],
    ['nom' => 'TinyMCE 7.6.1', 'auteur' => 'Ephox Corporation DBA Tiny Technologies, Inc.', 'licences' => ['GPL-2.0-or-later'],
     'chemins' => ['js/tinymce'], 'temoin' => ['js/tinymce/tinymce.min.js', 'TinyMCE version 7.6.1']],
    // L'interface de l'éditeur dans les langues du site (m08, 2026-10-10) : les fichiers `langs7` du paquet
    // tinymce-i18n, de simples paires « texte » : « traduction ». Corrigés ici : en français l'aide de la barre d'état,
    // trop longue pour elle ; en espagnol « Undo » / « Redo » (inversés, l'un resté en anglais) ; en italien « Redo » et
    // cinq libellés « … {0} » restés en anglais.
    ['nom' => 'TinyMCE, traductions de l\'interface (paquet tinymce-i18n 26.9.21, langs7)', 'auteur' => 'les traducteurs de la communauté TinyMCE, Tiny Technologies, Inc.', 'licences' => ['GPL-2.0-or-later'],
     'precision' => 'GPL-2.0-or-later, comme TinyMCE ; quelques libellés corrigés en français, espagnol et italien',
     'chemins' => ['js/tinymce/langs'], 'temoin' => ['js/tinymce/langs/fr_FR.js', 'tinymce.addI18n("fr_FR"']],
    ['nom' => 'Font Awesome Free 6.7.2', 'auteur' => 'Fonticons, Inc.', 'licences' => ['OFL-1.1', 'CC-BY-4.0', 'MIT'],
     'precision' => 'polices sous OFL-1.1, icônes sous CC-BY-4.0, code sous MIT',
     'chemins' => ['fonts/fontawesome', 'css/icons/fontawesome.min.css'], 'temoin' => ['css/icons/fontawesome.min.css', 'Font Awesome Free 6.7.2']],
    ['nom' => 'Open Sans', 'auteur' => 'Steve Matteson', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/open-sans'], 'temoin' => NULL],
    ['nom' => 'Titillium Web', 'auteur' => 'Accademia di Belle Arti di Urbino', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/titillium-web'], 'temoin' => NULL],
    // Les polices des thèmes et du réglage « Police du site », servies par le site (tools/polices-locales.php,
    // 2026-10-08) : auteurs et licence relevés dans les métadonnées de Google Fonts.
    ['nom' => 'Inter', 'auteur' => 'Rasmus Andersson', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/inter', 'css/fonts/inter.css'], 'temoin' => NULL],
    ['nom' => 'Space Grotesk', 'auteur' => 'Florian Karsten', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/space-grotesk', 'css/fonts/space-grotesk.css'], 'temoin' => NULL],
    ['nom' => 'JetBrains Mono', 'auteur' => 'JetBrains, Philipp Nurullin, Konstantin Bulenkov', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/jetbrains-mono', 'css/fonts/jetbrains-mono.css'], 'temoin' => NULL],
    ['nom' => 'Saira Condensed', 'auteur' => 'Omnibus-Type', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/saira-condensed', 'css/fonts/saira-condensed.css'], 'temoin' => NULL],
    ['nom' => 'Albert Sans', 'auteur' => 'Andreas Rasmussen', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/albert-sans', 'css/fonts/albert-sans.css'], 'temoin' => NULL],
    ['nom' => 'Playfair Display', 'auteur' => 'Claus Eggers Sørensen', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/playfair-display', 'css/fonts/playfair-display.css'], 'temoin' => NULL],
    ['nom' => 'Source Serif 4', 'auteur' => 'Frank Grießhammer', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/source-serif-4', 'css/fonts/source-serif-4.css'], 'temoin' => NULL],
    ['nom' => 'Jersey 10', 'auteur' => 'Sarah Cadigan-Fried', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/jersey-10', 'css/fonts/jersey-10.css'], 'temoin' => NULL],
    ['nom' => 'Rubik', 'auteur' => 'Hubert and Fischer, Meir Sadan, Cyreal, Daniel Grumer, Omaima Dajani', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/rubik', 'css/fonts/rubik.css'], 'temoin' => NULL],
    ['nom' => 'VT323', 'auteur' => 'Peter Hull', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/vt323', 'css/fonts/vt323.css'], 'temoin' => NULL],
    ['nom' => 'Fraunces', 'auteur' => 'Undercase Type, Phaedra Charles, Flavia Zimbardi', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/fraunces', 'css/fonts/fraunces.css'], 'temoin' => NULL],
    ['nom' => 'Work Sans', 'auteur' => 'Wei Huang', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/work-sans', 'css/fonts/work-sans.css'], 'temoin' => NULL],
    ['nom' => 'IBM Plex Mono', 'auteur' => 'Mike Abbink, Bold Monday', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/ibm-plex-mono', 'css/fonts/ibm-plex-mono.css'], 'temoin' => NULL],
    ['nom' => 'Bricolage Grotesque', 'auteur' => 'Mathieu Triay', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/bricolage-grotesque', 'css/fonts/bricolage-grotesque.css'], 'temoin' => NULL],
    ['nom' => 'Manrope', 'auteur' => 'Mikhail Sharanda', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/manrope', 'css/fonts/manrope.css'], 'temoin' => NULL],
    ['nom' => 'Barlow', 'auteur' => 'Jeremy Tribby', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/barlow', 'css/fonts/barlow.css'], 'temoin' => NULL],
    ['nom' => 'Rajdhani', 'auteur' => 'Indian Type Foundry', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/rajdhani', 'css/fonts/rajdhani.css'], 'temoin' => NULL],
    ['nom' => 'Roboto', 'auteur' => 'Christian Robertson, ParaType, Font Bureau', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/roboto', 'css/fonts/roboto.css'], 'temoin' => NULL],
    ['nom' => 'Lato', 'auteur' => 'Łukasz Dziedzic', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/lato', 'css/fonts/lato.css'], 'temoin' => NULL],
    ['nom' => 'Nunito', 'auteur' => 'Vernon Adams, Cyreal, Jacques Le Bailly', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/nunito', 'css/fonts/nunito.css'], 'temoin' => NULL],
    ['nom' => 'Source Sans 3', 'auteur' => 'Paul D. Hunt', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/source-sans-3', 'css/fonts/source-sans-3.css'], 'temoin' => NULL],
    ['nom' => 'Montserrat', 'auteur' => 'Julieta Ulanovsky, Sol Matas, Juan Pablo del Peral, Jacques Le Bailly', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/montserrat', 'css/fonts/montserrat.css'], 'temoin' => NULL],
    ['nom' => 'Poppins', 'auteur' => 'Indian Type Foundry, Jonny Pinhorn, Ninad Kale', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/poppins', 'css/fonts/poppins.css'], 'temoin' => NULL],
    ['nom' => 'Oswald', 'auteur' => 'Vernon Adams, Kalapi Gajjar, Cyreal', 'licences' => ['OFL-1.1'],
     'chemins' => ['fonts/oswald', 'css/fonts/oswald.css'], 'temoin' => NULL],
    ['nom' => 'Pe-icon-7-stroke', 'auteur' => 'Pixeden', 'licences' => [], 'precision' => 'licence de son auteur, pixeden.com ; héritée du NeoFrag d’origine',
     'chemins' => ['fonts/Pe-icon-7-stroke', 'css/icons/Pe-icon-7-stroke.css'], 'temoin' => NULL],
    ['nom' => 'famfamfam flag icons', 'auteur' => 'Mark James', 'licences' => [], 'precision' => 'libres de tout usage, sans condition ; hérités du NeoFrag d’origine',
     'chemins' => ['images/flags'], 'temoin' => ['images/flags/fam.png', 'PNG']],
];

/** Les idées reprises sans leur code : un merci, pas un crédit d'auteur — avec les addons qu'il concerne. */
const REMERCIEMENTS = [
    'le widget Twitch, réécrit pour l’API actuelle de Twitch, reprend l’idée du lecteur en fenêtre du « Twitch Mini » de Zaekof, Blober et Chewbaka'
        => ['widget twitch'],
    'le module Menu et l’installation en ligne de commande reprennent des idées de HiddenCMS, de HiddenBlob'
        => ['module menu'],
    'les thèmes Granite et Blockcraft s’inspirent des thèmes « yosemite » et « LD_minecraft » de Nuked-Klan, sans en reprendre ni code ni image'
        => ['theme granite', 'theme blockcraft'],
];

/** Ce qu'une licence d'autrui implique pour un addon, dit une fois — avec l'addon qu'elle concerne. */
const REMARQUES = [
    'le widget TeamSpeak s’appuie sur planetteamspeak/ts3-php-framework, sous GPL-3.0 : employé avec cette bibliothèque, il relève aussi de ses conditions'
        => ['widget teamspeak'],
];

const AUTEURS_ORIGINE = 'Michaël BILCOT & Jérémy VALENTIN';

// ── 1. Les addons, d'après leur déclaration ──────────────────────────────────
$addons = ['origine' => [], 'portage' => [], 'maison' => []];
$licences_addons = [];

foreach (['module' => 'modules', 'widget' => 'widgets', 'theme' => 'themes', 'addon' => 'addons'] as $type => $dossier)
{
    foreach (glob($racine.'/'.$dossier.'/*', GLOB_ONLYDIR) ?: [] as $chemin)
    {
        $nom     = basename($chemin);
        $fichier = $chemin.'/'.$nom.'.php';

        if (!is_file($fichier))
        {
            continue;
        }

        $source = (string) file_get_contents($fichier);

        if (preg_match("/'distributed'\s*=>\s*FALSE/i", $source))
        {
            continue;
        }

        $debut = strpos($source, 'function __info');
        $bloc  = $debut === FALSE ? '' : substr($source, $debut, 4000);
        $champ = static fn (string $cle): string => preg_match("/'{$cle}'\s*=>\s*'((?:[^'\\\\]|\\\\.)*)'/", $bloc, $m) ? stripslashes($m[1]) : '';

        $auteur  = $champ('author');
        $licence = $champ('license');
        $etiquette = $type.' '.$nom;

        if ($o['a-la-carte'] && !in_array($etiquette, $a_la_carte, TRUE))
        {
            continue;
        }

        if ($licence === '')
        {
            $fautes[] = "{$dossier}/{$nom}/{$nom}.php — aucune licence déclarée (__info()['license'])";
        }

        if (str_contains($auteur, 'BILCOT') || str_contains($auteur, 'VALENTIN'))
        {
            $addons['origine'][$etiquette] = $auteur;
        }
        else if (str_ends_with($auteur, '— portage NeoFrag Reborn'))
        {
            $addons['portage'][$etiquette] = $auteur;
        }
        else if ($auteur === 'NeoFrag Reborn')
        {
            $addons['maison'][$etiquette] = $auteur;
        }
        else
        {
            $fautes[] = "{$dossier}/{$nom}/{$nom}.php — auteur « {$auteur} » : attendu les auteurs d'origine, « <auteur> — portage NeoFrag Reborn » ou « NeoFrag Reborn »";
        }

        $licences_addons[$licence][] = $etiquette;
    }
}

// ── 2 et 3. Ce que le produit embarque d'autrui ──────────────────────────────
$declares = [];

foreach (TIERS as $tiers)
{
    foreach ($tiers['chemins'] as $chemin)
    {
        $declares[$chemin] = TRUE;

        if (!file_exists($racine.'/'.$chemin))
        {
            $fautes[] = "{$chemin} — déclaré dans TIERS ({$tiers['nom']}), absent du disque";
        }
    }

    if ($tiers['temoin'] !== NULL)
    {
        [$fichier, $texte] = $tiers['temoin'];

        if (!str_contains((string) @file_get_contents($racine.'/'.$fichier, FALSE, NULL, 0, 4096), $texte))
        {
            $fautes[] = "{$fichier} — ne porte pas « {$texte} » : la version a changé, mettre TIERS à jour ({$tiers['nom']})";
        }
    }
}

$a_declarer = array_merge(
    glob($racine.'/js/*', GLOB_ONLYDIR) ?: [],
    glob($racine.'/js/*.min.js') ?: [],
    glob($racine.'/fonts/*', GLOB_ONLYDIR) ?: []
);

foreach ($a_declarer as $chemin)
{
    $relatif = substr(str_replace('\\', '/', $chemin), strlen(str_replace('\\', '/', $racine)) + 1);

    if (!isset($declares[$relatif]))
    {
        $fautes[] = "{$relatif} — bibliothèque ou police d'autrui non déclarée : l'ajouter à TIERS, avec son auteur et sa licence";
    }
}

// ── Les bibliothèques PHP, d'après composer.lock ─────────────────────────────
$composer = [];
$verrou   = json_decode((string) @file_get_contents($racine.'/composer.lock'), TRUE);

foreach (is_array($verrou) ? ($verrou['packages'] ?? []) : [] as $paquet)
{
    $composer[] = [$paquet['name'], ltrim((string) $paquet['version'], 'v'), implode(' ou ', $paquet['license'] ?? ['(non déclarée)'])];
}

// ── 4. Le texte de chaque licence citée ──────────────────────────────────────
$citees = ['LGPL-3.0-or-later' => TRUE, 'CC-BY-NC-SA-4.0' => TRUE];

foreach (TIERS as $tiers)
{
    foreach ($tiers['licences'] as $licence)
    {
        $citees[$licence] = TRUE;
    }
}

foreach (array_keys($citees) as $licence)
{
    $texte = $licence === 'LGPL-3.0-or-later' ? 'COPYING.LESSER' : 'LICENSES/'.$licence.'.txt';

    if (!is_file($racine.'/'.$texte))
    {
        $fautes[] = "{$texte} — le texte de la licence {$licence} manque";
    }
}

// ── La NOTICE ────────────────────────────────────────────────────────────────
$liste = static fn (array $etiquettes): string => wordwrap(implode(', ', $etiquettes), 98, "\n  ");

/** Les remerciements et remarques qui concernent le contenu de cette NOTICE. */
$concernes = static fn (array $textes): array => $o['a-la-carte']
    ? array_keys(array_filter($textes, static fn (array $etiquettes): bool => (bool) array_intersect($etiquettes, $a_la_carte)))
    : array_keys($textes);

if ($o['a-la-carte'])
{
    $notice  = "NeoFrag Reborn, les addons à la carte — qui a écrit quoi, et sous quelle licence\n";
    $notice .= "================================================================================\n\n";
    $notice .= "Ces addons de NeoFrag Reborn, continuité communautaire de NeoFrag, créé par Michaël BILCOT (FoxLey)\n";
    $notice .= "et Jérémy VALENTIN (eResnova), sont distribués sous la GNU Lesser General Public License, version 3\n";
    $notice .= "ou ultérieure (COPYING, COPYING.LESSER), sauf ceux que ce fichier signale.\n\n";
    $notice .= "Ce fichier est engendré par tools/check-notice.php (dépôt neofrag) depuis les déclarations des\n";
    $notice .= "addons : ne pas l'éditer à la main.\n\n";
}
else
{
    $notice  = "NeoFrag Reborn — qui a écrit quoi, et sous quelle licence\n";
    $notice .= "=========================================================\n\n";
    $notice .= "NeoFrag Reborn est la continuité communautaire de NeoFrag, créé par Michaël BILCOT (FoxLey) et\n";
    $notice .= "Jérémy VALENTIN (eResnova). Il est distribué sous la GNU Lesser General Public License, version 3\n";
    $notice .= "ou ultérieure (COPYING, COPYING.LESSER), sauf les éléments que ce fichier signale.\n\n";
    $notice .= "Ce fichier est engendré par tools/check-notice.php depuis les déclarations des addons, composer.lock\n";
    $notice .= "et la liste de ce que le produit embarque d'autrui : ne pas l'éditer à la main.\n\n";
}

if ($addons['origine'])
{
    $notice .= "Addons du NeoFrag d'origine — ".AUTEURS_ORIGINE." (LGPL-3.0)\n";
    $notice .= "--------------------------------------------------------------------------\n";
    $notice .= '  '.$liste(array_keys($addons['origine']))."\n\n";
}

if ($addons['portage'])
{
    $notice .= "Portages d'addons de la communauté NeoFrag\n";
    $notice .= "------------------------------------------\n";

    foreach ($addons['portage'] as $etiquette => $auteur)
    {
        $licence = (string) array_key_first(array_filter($licences_addons, static fn (array $e): bool => in_array($etiquette, $e, TRUE)));
        $court   = str_contains($licence, 'BY-NC-SA') ? 'CC BY-NC-SA 4.0 (LICENSES/CC-BY-NC-SA-4.0.txt)' : trim((string) preg_replace('/\s*<[^>]*>/', '', $licence));
        $notice .= "  {$etiquette} — {$auteur} — {$court}\n";
    }

    $notice .= "\n";
}

if ($addons['maison'])
{
    $notice .= "Addons écrits par NeoFrag Reborn (LGPL-3.0)\n";
    $notice .= "-------------------------------------------\n";
    $notice .= '  '.$liste(array_keys($addons['maison']))."\n\n";
}

if ($mercis = $concernes(REMERCIEMENTS))
{
    $notice .= "Remerciements — idées reprises, sans leur code\n";
    $notice .= "----------------------------------------------\n";

    foreach ($mercis as $merci)
    {
        $notice .= '  - '.wordwrap($merci, 96, "\n    ")."\n";
    }
}

if (!$o['a-la-carte'])
{
    $notice .= "\nCe que le produit embarque d'autrui\n";
    $notice .= "-----------------------------------\n";

    foreach (TIERS as $tiers)
    {
        $licences = $tiers['precision'] ?? implode(', ', $tiers['licences']);
        $notice  .= "  {$tiers['nom']} — {$tiers['auteur']} — {$licences} — ".implode(', ', $tiers['chemins'])."\n";
    }
}

if ($composer && !$o['a-la-carte'])
{
    $notice .= "\nBibliothèques PHP livrées dans vendor/ (composer.lock) — chacune y garde son texte de licence\n";
    $notice .= "---------------------------------------------------------------------------------------------\n";

    foreach ($composer as [$nom, $version, $licence])
    {
        $notice .= "  {$nom} {$version} — {$licence}\n";
    }
}

if ($remarques = $concernes(REMARQUES))
{
    $notice .= "\nRemarques\n---------\n";

    foreach ($remarques as $remarque)
    {
        $notice .= '  - '.wordwrap($remarque, 96, "\n    ")."\n";
    }
}

$notice .= "\nLes textes des licences citées sont dans COPYING.LESSER (LGPL-3.0) et dans LICENSES/.\n";

// ── La NOTICE du bot Discord, d'après bot/package-lock.json ──────────────────
// Le bot a son propre dépôt : sa NOTICE dit ce qu'il emploie d'autrui (les paquets npm de production,
// que `npm ci` installe avec leur texte de licence). Là où le bot n'est pas, rien à écrire.
$notices = ['NOTICE' => $notice];
$verrou_bot = $o['a-la-carte'] ? NULL : json_decode((string) @file_get_contents($racine.'/bot/package-lock.json'), TRUE);

if (is_array($verrou_bot))
{
    $paquets = [];

    foreach ($verrou_bot['packages'] ?? [] as $cle => $paquet)
    {
        if ($cle === '' || !empty($paquet['dev']))
        {
            continue;
        }

        $nom = substr((string) $cle, (int) strrpos((string) $cle, 'node_modules/') + strlen('node_modules/'));
        $paquets[$nom.' '.($paquet['version'] ?? '?')] = $paquet['license'] ?? '(non déclarée)';
    }

    ksort($paquets);

    $bot  = "Le bot Discord de NeoFrag Reborn — ce qu'il emploie d'autrui\n";
    $bot .= "============================================================\n\n";
    $bot .= "Le bot est distribué sous la GNU Lesser General Public License, version 3 ou ultérieure (COPYING,\n";
    $bot .= "COPYING.LESSER), comme NeoFrag Reborn.\n\n";
    $bot .= "Ce fichier est engendré par tools/check-notice.php (dépôt de NeoFrag Reborn) depuis package-lock.json :\n";
    $bot .= "ne pas l'éditer à la main.\n\n";
    $bot .= "Paquets npm de production — `npm ci` les installe, chacun avec son texte de licence\n";
    $bot .= "-----------------------------------------------------------------------------------\n";

    foreach ($paquets as $paquet => $licence)
    {
        $bot .= "  {$paquet} — {$licence}\n";
    }

    $notices['bot/NOTICE'] = $bot;
}

// ── Le verdict ───────────────────────────────────────────────────────────────
if ($o['montrer'])
{
    echo implode("\n", $notices);
    exit(NF_OK);
}

foreach ($notices as $fichier => $attendue)
{
    if ((string) @file_get_contents($racine.'/'.$fichier) === $attendue)
    {
        continue;
    }

    if ($o['ecrire'] && !$fautes)
    {
        file_put_contents($racine.'/'.$fichier, $attendue);
        echo "{$fichier} réécrite.\n";
    }
    else if (!$o['ecrire'])
    {
        $fautes[] = "{$fichier} — elle ne correspond plus aux déclarations : php tools/check-notice.php --ecrire";
    }
}

printf("%d addon(s) d'origine, %d portage(s), %d fait(s) maison ; %d élément(s) d'autrui ; %d bibliothèque(s) PHP%s.\n",
    count($addons['origine']), count($addons['portage']), count($addons['maison']), count(TIERS), count($composer),
    isset($notices['bot/NOTICE']) ? ' ; la NOTICE du bot' : '');

if (!$fautes)
{
    nf_ok('la NOTICE dit qui a écrit chaque addon et ce que le produit embarque d’autrui');
}

echo "\n";

foreach ($fautes as $faute)
{
    echo "  ✗ {$faute}\n";
}

nf_echec(count($fautes).' écart(s) entre la NOTICE et ce que le produit porte');
