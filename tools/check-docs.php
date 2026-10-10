<?php
declare(strict_types=1);

/**
 * check-docs — la documentation respecte ses règles : chiffres justes, renvois vivants, rien d'orphelin ni de recopié.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * La documentation de ce dépôt se présente comme « vérifiée contre le code réel ». Or `docs/README.md`
 * a annoncé « 53 modules · 6 thèmes » pendant des semaines (réel : 54 et 7) ; cinq documents ont
 * annoncé « 17 contrôles » alors qu'il y en avait 26 ; deux fiches renommées ont laissé sept renvois
 * dans le vide ; et le 2026-09-21, la batterie était décrite dans SIX documents, chacun avec sa liste
 * d'outils, dont aucune n'était à jour. Aucun de ces défauts ne se voit en relecture — Markdown ne
 * se compile pas. Les règles sont écrites dans `docs/README.md` ; ce contrôle les fait respecter.
 *
 * Ce qu'il vérifie
 * ----------------
 *   1. les CHIFFRES d'inventaire (modules, widgets, thèmes, addons, contrôles, fichiers stricts)
 *      annoncés dans une forme d'inventaire correspondent au code — jamais la prose —, et la
 *      version citée (« NeoFrag Reborn X.Y.Z ») hors des documents de travail est celle du code ;
 *   2. les RENVOIS entre documents : tout lien vers un `.md` relatif pointe vers un fichier qui
 *      existe, et son ancre vers un titre qui existe ;
 *   3. aucun document ORPHELIN : tout `.md` sous `docs/` est la cible d'au moins un renvoi ;
 *   4. tout OUTIL nommé dans un document vivant existe dans `tools/` (les archives et le
 *      CHANGELOG racontent le passé, ils en sont dispensés) ;
 *   5. aucune PROSE RECOPIÉE : une même phrase de plus de 80 caractères dans deux documents
 *      vivants est une copie, qui divergera — on renvoie, on ne recopie pas ;
 *   6. les documents vivants restent LISIBLES : `docs/internal/journal.md` tourne vers l'archive
 *      au-delà de 400 lignes, et aucun document vivant ne dépasse 900 lignes ;
 *   7. la CLOISON : un document public — tout ce qui n'est pas sous `docs/internal/`, CHANGELOG
 *      compris — ne renvoie jamais aux documents de travail, ne cite ni fiche ni `TODO.md`, et ne
 *      nomme pas le mainteneur par son pseudo, hors de son crédit (« maintenue par … ») : la copie
 *      publique ne les porte pas, le renvoi y serait mort. Le 2026-10-04, la politique de sécurité
 *      publique renvoyait encore à un audit et à une fiche internes, et annonçait comme limite une
 *      purge faite depuis un mois.
 *
 * Usage
 * -----
 *   php tools/check-docs.php
 *   php tools/check-docs.php --verbeux
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o] = nf_options(['verbeux' => FALSE]);

// Les chiffres d'inventaire décrivent le produit entier : sans les addons à la carte, rien à juger.
nf_exiger_assemblage('la documentation');

$root    = nf_racine();
$erreurs = [];
$verifie = 0;

/** Tous les documents Markdown du dépôt qui comptent : la racine, .github/ et docs/. */
$documents = array_merge(
    array_filter([$root.'/README.md', $root.'/CHANGELOG.md', $root.'/CHANGELOG.en.md', $root.'/ROADMAP.md', $root.'/config/README.md', $root.'/tools/README.md'], 'is_file'),
    array_values(nf_fichiers(['.github', 'docs'], ['md']))
);

sort($documents);

/** Les documents VIVANTS : tout sauf les archives et le CHANGELOG, qui racontent le passé. */
$vivants = array_filter($documents, static fn (string $d): bool => !str_contains($d, '/docs/internal/archive/') && !str_ends_with($d, '/CHANGELOG.md') && !str_ends_with($d, '/CHANGELOG.en.md'));

$rel = static fn (string $chemin): string => nf_relatif($chemin);

// ══ 1. Les chiffres d'inventaire ════════════════════════════════════════════════════════════════

/** Règle du chargeur : un addon valide est un dossier `x/` contenant `x.php`. */
$compter = static fn (string $type): int => count(nf_addons($type));

/*
 * Addons distribuables : ceux que tools/lib/addons-manifest.php tire des déclarations — identité
 * et à la carte — et que tools/package-addons.php zippe. Compter les zips de marketplace/ donnait
 * le même chiffre sur l'atelier, mais ils ne sont pas versionnés : la CI en comptait zéro.
 */
require __DIR__.'/lib/addons-manifest.php';

$distribuables = 0;

foreach (['identity', 'optional'] as $tier)
{
    foreach ($nf_manifeste[$tier] as $liste)
    {
        $distribuables += count($liste);
    }
}

/*
 * Thèmes distribués : tous, sauf ceux qui se déclarent `'distributed' => FALSE` — la vitrine, propre
 * au site officiel, qu'aucun paquet ne porte. Le README disait « le paquet livre 7 thèmes » : le
 * dépôt en a 7, le paquet 6.
 */
$themes_distribues = count(array_filter(nf_addons('theme'), static fn (string $dossier, string $nom): bool
    => !preg_match("/'distributed'\s*=>\s*FALSE/i", (string) @file_get_contents($dossier.'/'.$nom.'.php')), ARRAY_FILTER_USE_BOTH));

$attendus = [
    'modules'              => $compter('module'),
    'widgets'              => $compter('widget'),
    'themes'               => $compter('theme'),
    'themes distribues'    => $themes_distribues,
    'addons'               => $compter('addon'),
    'addons distribuables' => $distribuables,
];

// L'ordre compte : « addons distribuables » doit être tenté AVANT « addons », sinon la seconde
// alternative capturerait le préfixe et comparerait 52 zips à 10 dossiers. De même pour les thèmes.
$libelles = [
    'addons distribuables' => 'addons?\s+distribuables?',
    'themes distribues'    => 'th[èe]mes?\s+distribu[ée]s?',
    'modules'              => 'modules?',
    'widgets'              => 'widgets?',
    'themes'               => 'th[èe]mes?',
    'addons'               => 'addons?',
];
$alternation = implode('|', $libelles);
$libelle_de  = static function (string $trouve) use ($libelles): string {
    foreach ($libelles as $cle => $motif)
    {
        if (preg_match('/^'.$motif.'$/ui', $trouve))
        {
            return $cle;
        }
    }

    return '';
};

/**
 * Le nombre entier derrière une écriture française : « 1 365 » avec son espace insécable étroite
 * était lu « 1 » suivi de « 365 », et le contrôle signalait une phrase juste comme fausse.
 */
function nombre_francais(string $brut): int
{
    return (int) str_replace(["\u{202F}", "\u{00A0}", "\u{2009}", ' ', "'"], '', $brut);
}

// Le nombre de contrôles : les `tools/check-*.php`, moins le lanceur.
$controles = count(array_filter(glob($root.'/tools/check-*.php') ?: [], static fn (string $f): bool => basename($f) !== 'check-all.php'));

// L'avancement de strict_types : les deux nombres, les fichiers stricts ET le total du périmètre.
$php_total = $php_stricts = 0;

foreach (nf_fichiers(['neofrag', 'modules', 'widgets', 'addons'], ['php'], [], FALSE) as $chemin)
{
    $php_total++;
    $php_stricts += str_contains((string) file_get_contents($chemin), 'declare(strict_types=1)') ? 1 : 0;
}

// La version du code, pour les documents qui la citent.
$version = preg_match("/NEOFRAG_VERSION',\s*'([^']+)'/", (string) @file_get_contents($root.'/index.php'), $mv) ? $mv[1] : '';

foreach ($vivants as $doc)
{
    $lignes = file($doc, FILE_IGNORE_NEW_LINES) ?: [];

    // La feuille de route parle au visiteur avec les chiffres du CATALOGUE, contrôlés plus bas.
    $catalogue_seul = str_ends_with($doc, '/ROADMAP.md');

    foreach ($lignes as $i => $ligne)
    {
        // Ce qui énonce un inventaire : un titre (`## 54 modules`), une chaîne séparée par « · »
        // (`**54 modules · 38 widgets · 7 thèmes**`), un compte en gras (`**54 modules**`), la ligne
        // d'un tableau qui décrit le dossier lui-même (`| modules/ | 54 modules |`). Seules les deux
        // premières étaient lues jusqu'au 2026-10-02 : le README annonçait en gras 54 modules quand le
        // code en comptait 62. Le reste est de la prose : « statistics (19 modules) » ou « le code de
        // 23 modules, 10 widgets et 4 thèmes à la carte » parlent d'un sous-ensemble, et les contrôler
        // crierait à tort — une règle « toute énumération » l'a fait le soir même. Une citation
        // (« … ») rapporte ce qui était écrit — souvent le chiffre faux dont le journal raconte la
        // correction — : elle n'affirme rien, et sort de la mesure, ici comme pour les contrôles, les
        // fichiers stricts et la version.
        $affirme = (string) preg_replace('/«[^»]*»/u', '', $ligne);

        $inventaire = !$catalogue_seul && (preg_match('/^#{1,6}\s/u', $affirme) || str_contains($affirme, '·')
            || preg_match('/\*\*\d+\s+(?:'.$alternation.')\b/ui', $affirme)
            || preg_match('#^\|\s*`(?:modules|widgets|themes|addons)/`\s*\|#u', $affirme));

        if ($inventaire)
        {
            if (preg_match_all('/(\d+)\s+('.$alternation.')/ui', $affirme, $m, PREG_SET_ORDER))
            {
                foreach ($m as $match)
                {
                    $cle = $libelle_de($match[2]);

                    if ($cle === '')
                    {
                        continue;
                    }

                    $verifie++;

                    if ((int) $match[1] !== $attendus[$cle])
                    {
                        $erreurs[] = sprintf("%s:%d — annonce %d %s, le code en compte %d\n      > %s", $rel($doc), $i + 1, (int) $match[1], $cle, $attendus[$cle], trim($ligne));
                    }
                }
            }
        }

        // « N contrôles », hors qualificatif qui restreint le sens (« 21 contrôles HTTP »).
        if (preg_match_all('/(?:\*\*)?(\d[\d\x{202F}\x{00A0}\x{2009} \']*)(?:\*\*)?\s+contrôles?\b(?!\s*\*{0,2}\s*(?:HTTP|lourds?|légers?|ponctuels?|de\s+la\s+CI|statiques?|en\s+navigateur|à\s+cible))/ui', $affirme, $m, PREG_SET_ORDER))
        {
            foreach ($m as $match)
            {
                if (!($trouve = nombre_francais((string) $match[1])))
                {
                    continue;
                }

                $verifie++;

                if ($trouve !== $controles)
                {
                    $erreurs[] = sprintf("%s:%d — annonce %d contrôles, tools/ en compte %d\n      > %s", $rel($doc), $i + 1, $trouve, $controles, trim($ligne));
                }
            }
        }

        // « N fichiers sur M » sur une ligne qui parle de strict_types.
        if (str_contains($affirme, 'strict_types')
            && preg_match('/(?:\*\*)?(\d[\d\x{202F}\x{00A0}\x{2009} \']*?)(?:\*\*)?\s+fichiers?\s+sur\s+(?:\*\*)?(\d[\d\x{202F}\x{00A0}\x{2009} \']*)/u', $affirme, $m))
        {
            $verifie += 2;

            if (nombre_francais($m[1]) !== $php_stricts || nombre_francais($m[2]) !== $php_total)
            {
                $erreurs[] = sprintf("%s:%d — annonce %d fichiers stricts sur %d, le code en compte %d sur %d\n      > %s",
                    $rel($doc), $i + 1, nombre_francais($m[1]), nombre_francais($m[2]), $php_stricts, $php_total, trim($ligne));
            }
        }

        // « NeoFrag Reborn X.Y.Z » dans un document qui décrit le présent : la version du code. Six
        // documents se disaient « vérifiés contre la 1.1.0 » à la 1.2.17 (2026-10-02). Les documents
        // de travail (`docs/internal/`) racontent les versions passées et en sont dispensés.
        if (!str_contains($doc, '/docs/internal/') && $version !== ''
            && preg_match_all('/NeoFrag Reborn (\d+\.\d+\.\d+)/u', $affirme, $m))
        {
            foreach ($m[1] as $citee)
            {
                $verifie++;

                if ($citee !== $version)
                {
                    $erreurs[] = sprintf("%s:%d — cite NeoFrag Reborn %s, le code est en %s\n      > %s", $rel($doc), $i + 1, $citee, $version, trim($ligne));
                }
            }
        }
    }
}

// Le hero de la vitrine et la feuille de route publique s'adressent à un visiteur : leurs chiffres
// sont ceux du CATALOGUE — ce qu'on peut ajouter ou retirer — et non l'inventaire brut du dépôt.
$catalogue = $root.'/marketplace/catalog.json';
$offert    = ['module' => 0, 'widget' => 0, 'theme' => 0];

if (is_file($catalogue))
{
    foreach ((json_decode((string) file_get_contents($catalogue), TRUE) ?: [])['addons'] ?? [] as $addon)
    {
        if (isset($offert[$addon['type'] ?? '']))
        {
            $offert[$addon['type']]++;
        }
    }
}

// L'accueil de la vitrine les COMPTE dans le catalogue publié (m25, 2026-10-10) : il ne doit plus porter de chiffre
// écrit à la main, ni dans le haut de page, ni dans la feuille de route.
if (is_file($landing = $root.'/themes/vitrine/views/landing.tpl.php'))
{
    $html = (string) file_get_contents($landing);
    $verifie++;

    if (!str_contains($html, "NEOFRAG_CMS.'/marketplace/catalog.json'"))
    {
        $erreurs[] = 'themes/vitrine/views/landing.tpl.php — ne lit plus le catalogue publié (marketplace/catalog.json) : d’où viennent ses chiffres ?';
    }

    if (preg_match('#<div class="n">\d+</div>#', $html) || preg_match("#lang\('%d modules & %d widgets', \d#", $html))
    {
        $erreurs[] = 'themes/vitrine/views/landing.tpl.php — un nombre de modules, de widgets ou de thèmes écrit à la main : le compter dans le catalogue ($vt_offert).';
    }
}

if (array_sum($offert) > 0 && is_file($roadmap = $root.'/ROADMAP.md'))
{
    $verifie++;

    if (!preg_match('/\*\*(\d+)\s+modules?,\s*(\d+)\s+widgets?\s+et\s+(\d+)\s+thèmes?\*\*/u', (string) file_get_contents($roadmap), $m))
    {
        $erreurs[] = 'ROADMAP.md — la phrase d\'inventaire « **N modules, N widgets et N thèmes** » est introuvable : le contrôle ne vérifie plus rien.';
    }
    elseif ([(int) $m[1], (int) $m[2], (int) $m[3]] !== [$offert['module'], $offert['widget'], $offert['theme']])
    {
        $erreurs[] = sprintf('ROADMAP.md — annonce %d modules, %d widgets et %d thèmes ; le catalogue marketplace en offre %d, %d et %d',
            (int) $m[1], (int) $m[2], (int) $m[3], $offert['module'], $offert['widget'], $offert['theme']);
    }
}

// ══ 2. Les renvois entre documents, ancres comprises ════════════════════════════════════════════

/**
 * Les ancres d'un document : une par titre, telles que les fabriquent GitHub et GitLab. Minuscules,
 * ponctuation retirée SAUF le tiret et le souligné, espaces en tirets. Un titre qui porte un tiret
 * cadratin ou un émoji laisse donc DEUX tirets consécutifs — c'est exact, et c'est ce qui rend une
 * ancre écrite à la main si facile à rater.
 */
function ancres(string $fichier): array
{
    static $cache = [];

    if (isset($cache[$fichier]))
    {
        return $cache[$fichier];
    }

    $sortie = [];

    foreach (file($fichier, FILE_IGNORE_NEW_LINES) ?: [] as $ligne)
    {
        if (preg_match('/^#{1,6}\s+(.+)$/u', $ligne, $trouve))
        {
            $titre    = mb_strtolower(trim($trouve[1]));
            $titre    = (string) preg_replace('/[`*\[\]()]/u', '', $titre);
            $titre    = (string) preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $titre);
            $sortie[] = str_replace(' ', '-', $titre);
        }
    }

    return $cache[$fichier] = $sortie;
}

$cibles_de_renvoi = [];   // fichier absolu => TRUE : tout document visé par au moins un renvoi

foreach ($documents as $fichier)
{
    $texte = (string) file_get_contents($fichier);

    // `[libellé](cible.md#ancre)` — la cible peut être vide pour un renvoi interne au même document.
    preg_match_all('~\]\(([^)\s#]*\.md)?(#[^)\s]+)?\)~', $texte, $trouves, PREG_SET_ORDER);

    foreach ($trouves as $trouve)
    {
        $lien  = $trouve[1] ?? '';
        $ancre = isset($trouve[2]) ? substr($trouve[2], 1) : '';

        if (($lien === '' && $ancre === '') || ($lien !== '' && preg_match('~^(?:https?:)?//~i', $lien)))
        {
            continue;
        }

        $cible = $lien === '' ? $fichier : realpath(dirname($fichier).'/'.$lien);

        if ($lien !== '')
        {
            $verifie++;

            if ($cible === FALSE || !is_file($cible))
            {
                $erreurs[] = sprintf('%s — renvoi cassé vers %s', $rel($fichier), $lien);
                continue;
            }

            $cibles_de_renvoi[$cible] = TRUE;
        }

        if ($ancre !== '' && $cible !== FALSE && is_file($cible))
        {
            $verifie++;

            if (!in_array($ancre, ancres($cible), TRUE))
            {
                $erreurs[] = sprintf('%s — ancre introuvable : %s#%s', $rel($fichier), $lien ?: '(ce document)', $ancre);
            }
        }
    }
}

// ══ 3. Aucun document orphelin sous docs/ ═══════════════════════════════════════════════════════
foreach ($documents as $fichier)
{
    // Les deux index sont des points d'entrée : le public (`docs/README.md`) et celui des documents de
    // travail (`docs/internal/README.md`), qu'aucun document public n'a le droit de citer (règle 7).
    if (!str_contains($fichier, '/docs/') || str_ends_with($fichier, '/docs/README.md') || str_ends_with($fichier, '/docs/internal/README.md'))
    {
        continue;
    }

    $verifie++;

    if (!isset($cibles_de_renvoi[realpath($fichier)]))
    {
        $erreurs[] = sprintf("%s — aucun document n'y renvoie : l'ajouter à son index (docs/README.md, docs/guide/README.md ou docs/internal/README.md), ou l'archiver", $rel($fichier));
    }
}

// ══ 4. Les outils nommés existent ═══════════════════════════════════════════════════════════════
$outils_existants = array_map(static fn (string $f): string => basename($f), glob($root.'/tools/*.php') ?: []);

foreach ($vivants as $fichier)
{
    if (preg_match_all('#tools/([a-z0-9-]+\.php)#', (string) file_get_contents($fichier), $m))
    {
        foreach (array_unique($m[1]) as $outil)
        {
            $verifie++;

            if (!in_array($outil, $outils_existants, TRUE) && $outil !== 'router-builtin.php')
            {
                $erreurs[] = sprintf("%s — nomme tools/%s, qui n'existe pas (renommé, fusionné ou supprimé ?)", $rel($fichier), $outil);
            }
        }
    }
}

// ══ 5. Aucune prose recopiée entre deux documents vivants ═══════════════════════════════════════
$phrases = [];   // phrase normalisée => liste de documents

foreach ($vivants as $fichier)
{
    $dans_code = FALSE;

    foreach (file($fichier, FILE_IGNORE_NEW_LINES) ?: [] as $ligne)
    {
        $nue = trim($ligne);

        // Un bloc de code n'est pas de la prose : la même commande peut légitimement figurer à deux endroits.
        if (str_starts_with($nue, '```'))
        {
            $dans_code = !$dans_code;
            continue;
        }

        // Une phrase de prose : au moins 80 caractères, ni tableau, ni titre, ni citation, ni liste.
        if ($dans_code || mb_strlen($nue) < 80 || preg_match('/^(\||#|>|-|\*|\d+\.|\s*`)/', $nue))
        {
            continue;
        }

        $phrases[(string) preg_replace('/\s+/', ' ', $nue)][$rel($fichier)] = TRUE;
    }
}

foreach ($phrases as $phrase => $ou)
{
    if (count($ou) > 1)
    {
        $erreurs[] = sprintf("prose recopiée dans %s :\n      > %s", implode(' et ', array_keys($ou)), mb_strimwidth($phrase, 0, 110, '…'));
    }
}

// ══ 6. Les documents vivants restent lisibles ═══════════════════════════════════════════════════
const LIMITES = ['docs/internal/journal.md' => 400];

foreach ($vivants as $fichier)
{
    $lignes = count(file($fichier) ?: []);
    $limite = LIMITES[$rel($fichier)] ?? 900;

    if ($lignes > $limite)
    {
        $erreurs[] = sprintf('%s — %d lignes, plus de %d : le raccourcir, ou faire tourner ses entrées anciennes vers docs/internal/archive/', $rel($fichier), $lignes, $limite);
    }
}

// ══ 7. La cloison : un document public ne renvoie jamais au travail interne ════════════════════════
const CLOISON = [
    '#(?<![\w-])(?:\.\./)*(?:docs/)?internal/#'                                   => 'renvoie aux documents de travail (`docs/internal/`)',
    '/\bTODO\.md\b/'                                                               => 'renvoie à l\'ancien tableau des chantiers (`TODO.md`)',
    '/\b[Ff]iches? [ABC]\d{1,2}\b|\((?:[ABC]\d{1,2})(?:, ?[ABC]\d{1,2})*\)/'      => 'cite une fiche de travail',
    '/\bLuandre\b/'                                                                => 'nomme le mainteneur par son pseudo',
];

/**
 * La seule place du pseudo dans un document public : le crédit, sous cette forme. Le mainteneur a
 * choisi (2026-10-04) d'être neutre partout — « le mainteneur » — et crédité une fois, dans le README
 * public et le profil de l'organisation.
 */
const CREDIT = '/\b[Mm]aintenue? par \*{0,2}Luandre\*{0,2}/';

foreach ($documents as $fichier)
{
    if (str_contains($fichier, '/docs/internal/'))
    {
        continue;
    }

    foreach (file($fichier, FILE_IGNORE_NEW_LINES) ?: [] as $i => $ligne)
    {
        foreach (CLOISON as $motif => $quoi)
        {
            $verifie++;

            if (preg_match($motif, $ligne) && !($quoi === CLOISON['/\bLuandre\b/'] && preg_match(CREDIT, $ligne)))
            {
                $erreurs[] = sprintf("%s:%d — %s : la copie publique ne l'a pas", $rel($fichier), $i + 1, $quoi);
            }
        }
    }
}

// ══ Le verdict ══════════════════════════════════════════════════════════════════════════════════
printf("Inventaire réel : %d contrôles · %d/%d strict_types · ", $controles, $php_stricts, $php_total);

foreach ($attendus as $k => $v)
{
    echo "{$v} {$k} · ";
}

printf("\n%d document(s), dont %d vivant(s).\n", count($documents), count($vivants));

if ($o['verbeux'])
{
    foreach ($documents as $d)
    {
        printf("  %s\n", $rel($d));
    }
}

if (!$erreurs)
{
    nf_ok("{$verifie} vérification(s), aucun écart entre la documentation et le code");
}

echo "\n";

foreach ($erreurs as $e)
{
    echo "  ✗ {$e}\n\n";
}

nf_echec(count($erreurs).' écart(s) — corriger le document (ou le code, si c\'est lui qui a changé par erreur)');
