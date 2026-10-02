<?php
declare(strict_types=1);

/**
 * journal — lire le journal PHP d'une installation, classer ses lignes, et les montrer regroupées.
 *
 * Pourquoi
 * --------
 * Le 2026-09-22 au soir, la lecture du journal de la DÉMONSTRATION a trouvé en dix minutes six
 * défauts que rien d'autre n'avait vus : la page des événements qui rendait 404, les votes des
 * sondages perdus, le widget du forum toujours vide, une traduction absente dans cinq langues… Tous
 * étaient écrits là depuis des heures. Personne ne lisait ce journal, et le seul outil qui en lisait
 * un, `check-journal`, ne regardait que ce qui s'écrivait pendant qu'il servait seize pages.
 *
 * Ce qu'est une ligne FAUTIVE — erreur PHP, ou ligne écrite par le produit lui-même — et comment la
 * regrouper, le produit le sait (`neofrag/helpers/journal.php`, que lit aussi la page Journal du
 * Monitoring) ; ce fichier y ajoute ce dont les outils ont besoin : préparer un journal, le lire
 * depuis un octet ou une date, et montrer le rapport en ligne de commande.
 *
 * Usage
 * -----
 *   $taille   = nf_journal_taille($journal);                 // avant d'agir
 *   $entrees  = nf_journal_depuis_octet($journal, $taille);  // après
 *   $entrees  = nf_journal_depuis_date($journal, time() - 86400);
 *   $classe   = nf_journal_classer($entrees);                // ['php' => …, 'produit' => …, 'autres' => …]
 *   nf_journal_montrer($classe, dirname($journal, 2));       // rapport regroupé
 */

require_once __DIR__.'/outil.php';

// Ce qu'est une entrée, sa classe, son message et son regroupement : le produit le sait, pour la page
// Journal du Monitoring comme pour les outils — une seule définition (cf. neofrag/helpers/journal.php).
require_once dirname(__DIR__, 2).'/neofrag/helpers/journal.php';

/**
 * Le journal d'une installation que l'outil sert LUI-MÊME, prêt à être mesuré.
 *
 * Sur une installation neuve, `logs/php.log` n'existe pas : PHP ne le crée qu'à sa première ligne.
 * Le 2026-09-22, la CI — qui installe un site neuf — a vu `check-widget-contract` refuser de conclure
 * sur un « journal introuvable » qui n'était que VIDE. Quand l'outil sert le site avec son propre
 * serveur, il crée le fichier d'avance : c'est aussi la preuve qu'on peut y écrire. FALSE si le
 * dossier ne le permet pas — là, le contrôle est vraiment aveugle, et doit le dire.
 *
 * Ne s'emploie PAS pour lire une installation servie par quelqu'un d'autre (`check-journal
 * --depuis`) : un journal absent y est ambigu — rien d'écrit, ou journal mal configuré.
 */
function nf_journal_preparer(string $journal): bool
{
    if (is_file($journal))
    {
        return is_writable($journal);
    }

    if (!is_dir($dossier = dirname($journal)) || !is_writable($dossier) || @file_put_contents($journal, '') === FALSE)
    {
        return FALSE;
    }

    @chmod($journal, 0666);

    return TRUE;
}

function nf_journal_taille(string $journal): int
{
    clearstatcache(TRUE, $journal);

    return is_file($journal) ? (int) filesize($journal) : 0;
}

/**
 * Les ENTRÉES écrites après l'octet donné. Une entrée commence par un horodatage `[22-Sep-2026 …]` ;
 * les lignes qui suivent sans horodatage (la pile d'une exception) lui sont rattachées.
 *
 * @return list<array{date: ?int, texte: string}>
 */
function nf_journal_depuis_octet(string $journal, int $octet): array
{
    // PHP-FPM écrit le journal en différé : lui laisser le temps de vider son tampon.
    usleep(500000);

    if (nf_journal_taille($journal) <= $octet)
    {
        return [];
    }

    return nf_journal_entrees((string) file_get_contents($journal, FALSE, NULL, $octet));
}

/**
 * Les entrées datées d'au moins `$depuis` (horodatage Unix). Un journal dont AUCUNE ligne ne porte
 * d'horodatage lisible n'est pas un journal vide : c'est un format inconnu, et l'appelant doit
 * refuser de conclure — d'où `NULL`.
 *
 * @return list<array{date: ?int, texte: string}>|null
 */
function nf_journal_depuis_date(string $journal, int $depuis): ?array
{
    $entrees = nf_journal_entrees((string) file_get_contents($journal));

    if ($entrees && !array_filter($entrees, fn(array $e): bool => $e['date'] !== NULL))
    {
        return NULL;
    }

    return array_values(array_filter($entrees, fn(array $e): bool => $e['date'] !== NULL && $e['date'] >= $depuis));
}

/**
 * Le rapport, section par section. Rend le nombre de MESSAGES DISTINCTS fautifs (PHP + produit).
 *
 * @param array{php: list<array{date: ?int, texte: string}>, produit: list<array{date: ?int, texte: string}>, autres: list<array{date: ?int, texte: string}>} $classe
 */
function nf_journal_montrer(array $classe, string $installation = '', int $max = 25): int
{
    $sections = [
        'php'     => 'ERREURS DE PHP',
        'produit' => 'LIGNES ÉCRITES PAR LE PRODUIT (étiquette entre crochets)',
        'autres'  => 'AUTRES LIGNES — montrées, non jugées',
    ];

    $fautifs = 0;

    foreach ($sections as $cle => $titre)
    {
        if (!$classe[$cle])
        {
            continue;
        }

        $groupes = nf_journal_regrouper($classe[$cle], $installation);

        if ($cle !== 'autres')
        {
            $fautifs += count($groupes);
        }

        nf_avertir(sprintf("\n%s — %d ligne(s), %d message(s) distinct(s) :\n", $titre, count($classe[$cle]), count($groupes)));

        foreach (array_slice($groupes, 0, $max) as $g)
        {
            nf_avertir(sprintf('  %4d×  %s  %s',
                $g['nombre'],
                $g['dernier'] !== NULL ? gmdate('d/m H:i', $g['dernier']).' UTC' : '         ?      ',
                mb_substr($g['message'], 0, 170)));
        }

        if (count($groupes) > $max)
        {
            nf_avertir('  … et '.(count($groupes) - $max).' autre(s) message(s).');
        }
    }

    if ($classe['produit'])
    {
        nf_avertir("\nUne ligne du produit se lit de deux façons, et il faut trancher avant de la faire taire :\n"
            ."  - c'est une VRAIE anomalie → corriger ce qui refuse, pas le journal ;\n"
            ."  - c'est un refus ordinaire, du genre « cette adresse n'existe pas » → le checker\n"
            ."    concerné rend TRUE depuis `refus_ordinaire()` ; son diagnostic reste rendu à l'écran\n"
            ."    quand NEOFRAG_DEBUG_BAR est actif.");
    }

    return $fautifs;
}
