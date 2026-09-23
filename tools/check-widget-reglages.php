<?php
declare(strict_types=1);
/**
 * check-widget-reglages — aucun checker de widget ne lit un réglage sans valeur de repli.
 *
 * Famille : statique
 *
 * Pourquoi ce contrôle existe
 * ---------------------------
 * Un widget peut parfaitement arriver SANS réglages : posé par l'`install()` d'un thème, ajouté en
 * Live Editor avant qu'on ouvre son formulaire, ou restauré depuis une disposition ancienne. Son
 * checker doit donc rendre des réglages utilisables même quand on ne lui en donne aucun.
 *
 * Douze checkers sur trente-huit ne le faisaient pas. Onze n'y perdaient qu'un `Undefined array
 * key` journalisé à chaque affichage — bruyant, sans plus. Le douzième, `partners`, employait une
 * de ces clés comme DIVISEUR : `ceil($total / NULL)`, division par zéro, widget mort. Le défaut
 * n'était visible ni aux tests, ni à l'écran tant qu'on ouvrait la page avec des réglages complets.
 * Il a été trouvé dans le journal du site de démonstration, le 2026-09-16, par hasard.
 *
 * Ce que le contrôle vérifie
 * --------------------------
 * Une lecture `$settings['clé']` est acceptée si l'une de ces conditions est vraie :
 *   - CHACUNE de ses lectures sur la ligne est suivie de `??` ;
 *   - la ligne teste cette clé par `isset`, `empty` ou `array_key_exists` ;
 *   - la méthode complète ses réglages en tête, avec `$settings = (array) $settings + [...]`,
 *     et la clé lue figure dans ce repli.
 *
 * Le jugement porte sur la LECTURE, plus sur la ligne. La première version acceptait toute ligne
 * contenant un `??` : `in_array($settings['mode'] ?? 'native', […]) ? $settings['mode'] : 'native'`
 * passait donc, alors que la branche qui rend la valeur lit la clé sans repli. Vingt lectures de
 * cette forme dans treize checkers, trouvées le 2026-09-22 quand les checkers ont commencé à servir
 * aussi à l'AFFICHAGE (`Widget::_completer()`). L'épreuve à l'envers ci-dessous épingle la forme.
 *
 * Et ce que le contrôle ne dit pas : qu'un widget se rend sans réglages. C'est la seconde clause
 * de `check-widget-contract`, qui le RENDRE et lit le journal.
 *
 * C'est un contrôle STATIQUE : il lit le code, il n'exécute rien. Il ne prouve donc pas qu'un
 * widget se rend correctement — `check-widget-contract.php` s'en charge — mais il attrape la classe
 * entière, y compris pour les widgets pas encore écrits, et il tourne en une fraction de seconde.
 *
 * Usage
 * -----
 *   php tools/check-widget-reglages.php            liste les lectures nues, code 1 s'il y en a
 *   php tools/check-widget-reglages.php --detail   montre aussi les checkers conformes
 */

require __DIR__.'/lib/outil.php';

[$o]    = nf_options(['detail' => FALSE]);
$racine = nf_racine();
$detail = $o['detail'];

/** Découpe un fichier en méthodes prenant `$settings`, par comptage d'accolades. */
function methodes_a_reglages(string $src): array
{
    $methodes = [];

    if (!preg_match_all('/(?:public|protected|private)\s+function\s+(\w+)\s*\(\s*\$settings[^)]*\)\s*\R?\s*\{/', $src, $m, PREG_OFFSET_CAPTURE))
    {
        return $methodes;
    }

    foreach ($m[0] as $i => [$texte, $debut])
    {
        $ouverture = $debut + strlen($texte) - 1;
        $profondeur = 0;
        $k = $ouverture;
        $fin = strlen($src);

        while ($k < strlen($src))
        {
            if ($src[$k] === '{')
            {
                $profondeur++;
            }
            else if ($src[$k] === '}')
            {
                $profondeur--;

                if ($profondeur === 0)
                {
                    $fin = $k;
                    break;
                }
            }
            $k++;
        }

        $methodes[] = [
            'nom'   => $m[1][$i][0],
            'corps' => substr($src, $ouverture + 1, $fin - $ouverture - 1),
            'ligne' => substr_count($src, "\n", 0, $debut) + 1,
        ];
    }

    return $methodes;
}

/**
 * Les clés lues SANS repli sur une ligne : celles dont une lecture au moins n'est pas suivie de `??`,
 * et que la ligne ne teste pas par `isset`, `empty` ou `array_key_exists`.
 *
 * @param  list<string> $repli les clés complétées en tête de méthode
 * @return list<string>
 */
function lectures_nues(string $ligne, array $repli = []): array
{
    if (str_contains($ligne, '(array) $settings +'))
    {
        return [];   // la ligne du repli lui-même n'est pas une lecture
    }

    preg_match_all("/\\\$settings\[\s*'([a-z0-9_]+)'\s*\]/", $ligne, $lues);

    $nues = [];

    foreach (array_unique($lues[1] ?? []) as $cle)
    {
        $q = preg_quote($cle, '/');

        if (in_array($cle, $repli, TRUE)
            || preg_match('/\b(isset|empty)\s*\(\s*\$settings\[\s*\''.$q.'\'\s*\]/', $ligne)
            || preg_match('/array_key_exists\s*\(\s*\''.$q.'\'/', $ligne))
        {
            continue;
        }

        // Chaque lecture de la clé doit porter son repli.
        $toutes   = preg_match_all('/\$settings\[\s*\''.$q.'\'\s*\]/', $ligne);
        $gardees  = preg_match_all('/\$settings\[\s*\''.$q.'\'\s*\]\s*\?\?/', $ligne);

        if ($gardees < $toutes)
        {
            $nues[] = $cle;
        }
    }

    return $nues;
}

/**
 * L'ÉPREUVE À L'ENVERS, jouée à chaque lancement : la forme qui a échappé au contrôle doit être
 * refusée, et les formes correctes acceptées — sans quoi le contrôle refuse de conclure.
 */
function epreuve_reglages(): array
{
    $echecs = [];

    $nues = [
        "'mode' => in_array(\$settings['mode'] ?? 'native', ['native', 'iframe'], TRUE) ? \$settings['mode'] : 'native',",
        "if (\$settings['display_panel'] == 'oui')",
    ];

    $gardees = [
        "'mode' => in_array(\$settings['mode'] ?? 'native', ['native'], TRUE) ? (\$settings['mode'] ?? 'native') : 'native',",
        "'x' => isset(\$settings['x']) ? \$settings['x'] : 'd',",
        "'y' => !empty(\$settings['y']) ? \$settings['y'] : 'd',",
        "'z' => array_key_exists('z', \$settings) ? \$settings['z'] : 'd',",
        "\$settings = (array) \$settings + ['w' => ''];",
    ];

    foreach ($nues as $ligne)
    {
        if (!lectures_nues($ligne))
        {
            $echecs[] = 'lecture nue NON vue : '.$ligne;
        }
    }

    foreach ($gardees as $ligne)
    {
        if (lectures_nues($ligne))
        {
            $echecs[] = 'faux positif : '.$ligne;
        }
    }

    if (lectures_nues("echo \$settings['r'];", ['r']))
    {
        $echecs[] = 'faux positif : une clé du repli de tête';
    }

    return $echecs;
}

/** Clés couvertes par un repli `$settings = (array) $settings + [...]` en tête de méthode. */
function cles_du_repli(string $corps): array
{
    if (!preg_match('/\$settings\s*=\s*\(array\)\s*\$settings\s*\+\s*\[(.*?)\];/s', $corps, $m))
    {
        return [];
    }

    preg_match_all("/'([a-z0-9_]+)'\s*=>/i", $m[1], $cles);

    return $cles[1] ?? [];
}

if ($echecs_epreuve = epreuve_reglages())
{
    nf_refus('épreuve à l\'envers ratée — '.implode(' ; ', $echecs_epreuve));
}

$fichiers = glob($racine.'/widgets/*/controllers/checker.php') ?: [];
sort($fichiers);

$nues      = [];
$conformes = [];
$examines  = 0;

foreach ($fichiers as $fichier)
{
    $src = (string) file_get_contents($fichier);

    if (!str_contains($src, '$settings['))
    {
        continue;
    }

    $examines++;
    $widget = basename(dirname(dirname($fichier)));
    $souci  = [];

    foreach (methodes_a_reglages($src) as $methode)
    {
        $repli = cles_du_repli($methode['corps']);
        $n     = $methode['ligne'];

        foreach (explode("\n", $methode['corps']) as $i => $ligne)
        {
            if (!str_contains($ligne, '$settings['))
            {
                continue;
            }

            foreach (lectures_nues($ligne, $repli) as $cle)
            {
                $souci[] = [$methode['nom'], $n + $i + 1, $cle, trim($ligne)];
            }
        }
    }

    if ($souci)
    {
        $nues[$widget] = $souci;
    }
    else
    {
        $conformes[] = $widget;
    }
}

printf("%d checker(s) de widget lisent des réglages.\n\n", $examines);

if ($detail && $conformes)
{
    echo "Conformes (repli présent ou lectures protégées) :\n  ".implode(', ', $conformes)."\n\n";
}
else if ($conformes)
{
    printf("%d conforme(s) (--detail pour la liste).\n\n", count($conformes));
}

if (!$nues)
{
    nf_ok('aucune lecture de réglage sans valeur de repli');
}

echo "LECTURES SANS REPLI — un `Undefined array key` à chaque affichage, et un plantage si la clé\n";
echo "sert de diviseur ou d'index :\n\n";

$total = 0;

foreach ($nues as $widget => $souci)
{
    printf("  %s\n", $widget);

    foreach ($souci as [$methode, $ligne, $cle, $texte])
    {
        printf("      %s() L%-4d \$settings['%s']\n", $methode, $ligne, $cle);
        $total++;
    }
    echo "\n";
}

echo "Correctif : compléter les réglages en tête de méthode —\n";
echo "  \$settings = (array) \$settings + ['clé1' => '', 'clé2' => ''];\n";
echo "`+` ne remplit que les clés absentes : les valeurs fournies restent intactes.\n";

nf_echec(sprintf('%d lecture(s) sans repli dans %d widget(s)', $total, count($nues)));
