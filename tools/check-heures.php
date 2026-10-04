<?php
declare(strict_types=1);
/**
 * check-heures — une heure montrée à quelqu'un passe par timetostr(), qui la met dans SON fuseau horaire.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi ce contrôle existe
 * ---------------------------
 * Le produit enregistre ses dates dans le fuseau du serveur — l'heure universelle en production — et
 * les affiche dans celui de celui qui regarde : son choix de profil, sinon son navigateur, sinon le
 * fuseau du site (neofrag/helpers/time.php, 2026-10-02). La conversion se fait dans `timetostr()`
 * et la bibliothèque Date.
 *
 * `date()` ne convertit rien : il écrit l'heure du serveur. Une quinzaine d'affichages l'appelaient
 * directement (le wiki, le Bugtracker, les conversations archivées, le calendrier, le gestionnaire
 * de fichiers…) et montraient l'heure avec deux heures de retard à un visiteur français.
 *
 * Ce que le contrôle vérifie
 * --------------------------
 * Chaque `date('…')` ou `gmdate('…')` du produit doit porter un format de MACHINE — une valeur pour
 * la base, un nom de fichier, un en-tête HTTP, une date ISO —, pris dans la liste FORMATS_MACHINE.
 * Tout autre format (« d/m/Y H:i », « j F Y », « Y-m-d H:i »…) est un affichage : il doit passer par
 * `timetostr()`. Un format dont le premier argument n'est pas une chaîne écrite en clair est refusé
 * aussi : on ne peut pas savoir ce qu'il écrit.
 *
 * Un nouveau format de machine s'ajoute à FORMATS_MACHINE, avec son usage.
 *
 * Il vérifie aussi que `timetostr()` ne reçoit pas un format chiffré écrit en dur : « d/m/Y H:i »
 * ignore la langue (l'allemand écrit « 02.10.2026 ») et « Y-m-d H:i » montre une date de machine
 * (2026-10-02, le livre d'or, le wiki, le Bugtracker). Une date et heure passe par `nf_date_heure()`,
 * une date seule par `nf_date()`, qui lisent le format de la langue. Les formats qui nomment le mois
 * (« j M Y ») se traduisent d'eux-mêmes et passent, comme « H:i » et « Y-m-d » (une comparaison).
 *
 * Usage
 * -----
 *   php tools/check-heures.php             liste les affichages fautifs, code 1 s'il y en a
 *   php tools/check-heures.php --epreuve   ne joue QUE l'épreuve à l'envers
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o] = nf_options(['epreuve' => FALSE]);

/** Les formats qui ne s'affichent pas : ils s'écrivent dans le fuseau du serveur, et c'est voulu. */
const FORMATS_MACHINE = [
    'Y-m-d H:i:s',      // une date et heure pour la base
    'Y-m-d',            // une date pour la base, un plan de site (lastmod)
    'Y-m-d 00:00:00',   // le début d'une journée, pour une requête
    'm-d',              // le jour de l'année (anniversaires)
    'Y',                // l'année (mention de copyright)
    'N',                // le jour de la semaine, pour un calcul
    'c',                // ISO 8601 avec décalage (API, données structurées)
    'r',                // RFC 2822 (en-têtes HTTP, flux RSS, dumps)
    'U',                // un horodatage
    'Ymd', 'YmdHis', 'Ymd-His', 'Ymd-Hi',   // des noms de fichiers
    'Ymd\THis\Z', 'Y-m-d\TH:i:s\Z',         // l'heure universelle d'un agenda (avec gmdate)
];

/**
 * Les appels fautifs d'une source PHP.
 *
 * @return list<array{ligne: int, format: string}>
 */
function analyser_source(string $src): array
{
    $jetons = token_get_all($src);
    $fautes = [];

    foreach ($jetons as $i => $jeton)
    {
        if (!is_array($jeton) || $jeton[0] !== T_STRING || !in_array(strtolower($jeton[1]), ['date', 'gmdate', 'timetostr'], TRUE))
        {
            continue;
        }

        $affichage = strtolower($jeton[1]) === 'timetostr';

        // Une méthode (`->date(`, `::date(`) ou une définition (`function date(`) n'est pas l'appel visé.
        $avant = $i - 1;

        while ($avant >= 0 && is_array($jetons[$avant]) && $jetons[$avant][0] === T_WHITESPACE)
        {
            $avant--;
        }

        if ($avant >= 0 && (in_array($jetons[$avant], ['->', '::'], TRUE) || (is_array($jetons[$avant]) && in_array($jetons[$avant][0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NULLSAFE_OBJECT_OPERATOR, T_NEW], TRUE))))
        {
            continue;
        }

        $apres = $i + 1;

        while (isset($jetons[$apres]) && is_array($jetons[$apres]) && $jetons[$apres][0] === T_WHITESPACE)
        {
            $apres++;
        }

        if (($jetons[$apres] ?? NULL) !== '(')
        {
            continue;
        }

        $argument = $apres + 1;

        while (isset($jetons[$argument]) && is_array($jetons[$argument]) && $jetons[$argument][0] === T_WHITESPACE)
        {
            $argument++;
        }

        $premier = $jetons[$argument] ?? NULL;
        $suivant = $argument + 1;

        while (isset($jetons[$suivant]) && is_array($jetons[$suivant]) && $jetons[$suivant][0] === T_WHITESPACE)
        {
            $suivant++;
        }

        $en_clair = is_array($premier) && $premier[0] === T_CONSTANT_ENCAPSED_STRING && in_array($jetons[$suivant] ?? NULL, [',', ')'], TRUE);

        // timetostr() avec un format calculé ($this->lang('d/m/Y')) : c'est la bonne façon.
        if ($affichage && !$en_clair)
        {
            continue;
        }

        // Le format doit être une chaîne écrite en clair, et elle seule (pas une concaténation).
        if (!$en_clair)
        {
            $fautes[] = ['ligne' => $jeton[2], 'format' => '(format calculé)'];
            continue;
        }

        $format = stripcslashes(substr($premier[1], 1, -1));

        if ($premier[1][0] === "'")
        {
            $format = str_replace(["\\'", '\\\\'], ["'", '\\'], substr($premier[1], 1, -1));
        }

        if ($affichage)
        {
            if (preg_match('#d[/.-]m|m[/.]d#', $format) || in_array($format, ['Y-m-d H:i', 'Y-m-d H:i:s'], TRUE))
            {
                $fautes[] = ['ligne' => $jeton[2], 'format' => $format.' (timetostr : nf_date_heure() ou nf_date())'];
            }
        }
        else if (!in_array($format, FORMATS_MACHINE, TRUE))
        {
            $fautes[] = ['ligne' => $jeton[2], 'format' => $format];
        }
    }

    return $fautes;
}

/** L'épreuve à l'envers : les affichages plantés sont refusés, les usages de machine passent. */
function epreuve(): array
{
    $echecs = [];

    $defauts = [
        'heure affichée'           => "<?php echo date('d/m/Y H:i', \$t);",
        'date en toutes lettres'   => "<?php \$s = '<small>'.date('j F Y', \$ts).'</small>';",
        'heure sans secondes'      => "<?php echo date('Y-m-d H:i', \$r['ts']);",
        'gmdate affiché'           => "<?php echo gmdate('H:i');",
        'format calculé'           => "<?php echo date(\$this->lang('d/m/Y'), \$t);",
        'format concaténé'         => "<?php echo date('d/m/Y'.' H:i', \$t);",
        'timetostr chiffré figé'   => "<?php echo timetostr('d/m/Y H:i', \$t);",
        'timetostr de machine'     => "<?php echo '<td>'.timetostr('Y-m-d H:i', \$r['ts']).'</td>';",
        'timetostr jour et mois'   => "<?php echo timetostr('d/m', \$t);",
    ];

    $propres = [
        'valeur pour la base'      => "<?php \$db->update('t', ['at' => date('Y-m-d H:i:s')]);",
        'nom de fichier'           => "<?php \$f = 'export-'.date('Ymd-His').'.json';",
        'ISO pour une API'         => "<?php return ['created_at' => date('c', \$t)];",
        'agenda en UTC'            => "<?php return gmdate('Ymd\\\\THis\\\\Z', \$ts);",
        'timetostr, format traduit' => "<?php echo timetostr(\$this->lang('d/m/Y H:i'), \$t);",
        'timetostr, mois nommé'    => "<?php echo timetostr('j M Y', \$t);",
        'timetostr, heure seule'   => "<?php echo timetostr('H:i', \$t);",
        'timetostr, comparaison'   => "<?php if (timetostr('Y-m-d', \$a) === timetostr('Y-m-d', \$b)) {}",
        'nf_date_heure'            => "<?php echo nf_date_heure(\$r['created_at']);",
        'méthode date()'           => "<?php echo \$this->date(\$t)->short_date();",
        'méthode statique'         => "<?php \$d = Feed::date(\$e);",
        'définition'               => "<?php function date(\$x) {}",
    ];

    foreach ($defauts as $nom => $src)
    {
        if (!analyser_source($src))
        {
            $echecs[] = "défaut NON vu : $nom";
        }
    }

    foreach ($propres as $nom => $src)
    {
        if ($vu = analyser_source($src))
        {
            $echecs[] = "faux positif : $nom ({$vu[0]['format']})";
        }
    }

    return $echecs;
}

$echecs = epreuve();

if ($o['epreuve'])
{
    foreach ($echecs as $echec)
    {
        echo "  $echec\n";
    }

    $echecs ? nf_echec('épreuve à l\'envers : le contrôle est aveugle ou trop bavard') : nf_ok('épreuve à l\'envers : les affichages plantés sont refusés, les usages de machine passent');
}

if ($echecs)
{
    // Un contrôle qui ne voit plus ce qu'il vise ne doit pas se dire vert.
    nf_refus('épreuve à l\'envers ratée — '.implode(' ; ', $echecs));
}

$fautes   = [];
$fichiers = 0;

foreach (nf_fichiers(NF_DOSSIERS_PRODUIT, ['php']) as $rel => $chemin)
{
    $fichiers++;

    foreach (analyser_source((string) file_get_contents($chemin)) as $faute)
    {
        $fautes[] = [$rel] + $faute;
    }
}

if (!$fautes)
{
    nf_ok(sprintf('toutes les heures affichées passent par timetostr() (%d fichier(s), épreuve à l\'envers passée)', $fichiers));
}

echo "HEURES ÉCRITES DANS LE FUSEAU DU SERVEUR — date() ne les met pas dans celui de celui qui regarde :\n\n";

foreach ($fautes as $faute)
{
    printf("  %s:%d\n      date('%s') — un affichage passe par timetostr() ; un format de machine s'ajoute à FORMATS_MACHINE\n\n",
        $faute[0], $faute['ligne'], $faute['format']);
}

nf_echec(count($fautes).' heure(s) affichée(s) sans conversion de fuseau');
