<?php
declare(strict_types=1);
/**
 * check-theme-zones — toute zone qu'un thème déclare est rendue par ses gabarits.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi ce contrôle existe
 * ---------------------------
 * Un thème annonce ses zones dans `__info()` : `'zones' => ['Header', …, 'Footer']`. À l'installation,
 * `neofrag/addons/theme.php` crée une ligne de disposition par zone déclarée, et l'éditeur de mise en
 * page les propose. Si le gabarit n'appelle jamais `region('footer')` ni `zone(4)`, l'administrateur
 * place son widget, la disposition est bien enregistrée en base — et **rien ne s'affiche**, sans le
 * moindre message. Le défaut est invisible côté code : chaque fichier, pris isolément, est correct.
 *
 * Trouvé le 2026-09-20 en posant le widget `seasonal` dans le pied de page : `nebula` et `vitrine`
 * déclaraient toutes deux « Header » et « Footer » sans jamais les rendre, quand `blockcraft`,
 * `extend`, `forge` et `granite` les rendaient bien. La démonstration en portait la trace depuis
 * longtemps : la disposition `nebula / * / zone 0` contenait deux widgets que personne n'a jamais vus.
 *
 * Ce que le contrôle vérifie
 * --------------------------
 * Pour chaque thème public, chaque zone déclarée doit être atteinte par au moins une de ces deux
 * voies, dans n'importe quel gabarit du thème :
 *   - `region('nom')`, le nom étant résolu par la table `regions` du thème ;
 *   - `zone(n)`, `n` étant l'index de la zone dans `zones`.
 *
 * Le thème `admin` est hors périmètre : le panneau d'administration n'expose pas d'éditeur de
 * disposition pour lui-même, ses zones ne sont donc jamais proposées à personne.
 *
 * C'est un contrôle STATIQUE : il lit les déclarations et les gabarits, il n'exécute aucun rendu.
 *
 * Usage
 * -----
 *   php tools/check-theme-zones.php            liste les zones orphelines, code 1 s'il y en a
 *   php tools/check-theme-zones.php --detail   montre aussi les thèmes conformes
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o]    = nf_options(['detail' => FALSE]);
$racine = nf_racine();
$detail = $o['detail'];

/** Thèmes dont les zones ne sont jamais proposées dans un éditeur de disposition. */
const HORS_PERIMETRE = ['admin'];

/**
 * Le contenu d'un littéral de tableau PHP associé à une clé de `__info()`.
 *
 * On lit le texte plutôt que d'exécuter le thème : `__info()` est protégée et appelle `$this->lang()`,
 * donc l'instancier exigerait tout le service locator — précisément ce qu'un contrôle statique évite.
 */
function litteral(string $src, string $cle): ?string
{
    if (!preg_match('/[\'"]'.preg_quote($cle, '/').'[\'"]\s*=>\s*\[/', $src, $m, PREG_OFFSET_CAPTURE))
    {
        return NULL;
    }

    $i          = $m[0][1] + strlen($m[0][0]) - 1;
    $profondeur = 0;

    for ($k = $i, $n = strlen($src); $k < $n; $k++)
    {
        if ($src[$k] === '[')
        {
            $profondeur++;
        }
        else if ($src[$k] === ']' && --$profondeur === 0)
        {
            return substr($src, $i + 1, $k - $i - 1);
        }
    }

    return NULL;
}

/**
 * Les titres de zones déclarés, dans l'ordre : l'index est ce que `zone(n)` reçoit.
 *
 * Un titre peut être écrit en dur (`'Header'`) ou traduit (`$this->lang('Header')`) ; dans les deux
 * cas c'est le texte source qui sert de clé, puisque `regions` le référence tel quel.
 *
 * @return list<string>
 */
function zones_declarees(string $src): array
{
    if (($liste = litteral($src, 'zones')) === NULL)
    {
        return [];
    }

    preg_match_all('/(?:\$this->lang\(\s*)?\'((?:[^\'\\\\]|\\\\.)*)\'/', $liste, $m);

    return array_map(static fn (string $t): string => stripslashes($t), $m[1]);
}

/**
 * La table `regions` : nom sémantique → titre de zone.
 *
 * @return array<string, string>
 */
function regions_declarees(string $src): array
{
    if (($liste = litteral($src, 'regions')) === NULL)
    {
        return [];
    }

    preg_match_all('/\'((?:[^\'\\\\]|\\\\.)*)\'\s*=>\s*\'((?:[^\'\\\\]|\\\\.)*)\'/', $liste, $m, PREG_SET_ORDER);

    $regions = [];

    foreach ($m as $paire)
    {
        $regions[stripslashes($paire[1])] = stripslashes($paire[2]);
    }

    return $regions;
}

/** Tous les gabarits et contrôleurs d'un thème, concaténés : c'est là que le rendu a lieu. */
function sources_du_theme(string $dossier): string
{
    $src = '';

    foreach (nf_fichiers([nf_relatif($dossier)], ['php', 'tpl'], [], FALSE) as $chemin)
    {
        $src .= (string) file_get_contents($chemin)."\n";
    }

    return $src;
}

$orphelines = [];
$conformes  = [];

foreach (glob($racine.'/themes/*', GLOB_ONLYDIR) ?: [] as $dossier)
{
    $nom = basename($dossier);

    if (in_array($nom, HORS_PERIMETRE, TRUE) || !is_file($manifeste = $dossier.'/'.$nom.'.php'))
    {
        continue;
    }

    $declaration = (string) file_get_contents($manifeste);
    $zones       = zones_declarees($declaration);

    if (!$zones)
    {
        continue;
    }

    $regions = regions_declarees($declaration);
    $rendu   = sources_du_theme($dossier);

    // Les noms de régions effectivement rendus, et les index effectivement rendus.
    preg_match_all('/->region\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'/', $rendu, $m);
    $noms_rendus = array_map(static fn (string $t): string => stripslashes($t), $m[1]);

    preg_match_all('/->zone\(\s*(\d+)\s*\)/', $rendu, $m);
    $index_rendus = array_map('intval', $m[1]);

    // Un nom rendu vaut pour le titre auquel `regions` le fait pointer.
    $titres_rendus = [];

    foreach ($noms_rendus as $nom_region)
    {
        if (isset($regions[$nom_region]))
        {
            $titres_rendus[] = $regions[$nom_region];
        }
    }

    $manquantes = [];

    foreach ($zones as $index => $titre)
    {
        if (!in_array($titre, $titres_rendus, TRUE) && !in_array($index, $index_rendus, TRUE))
        {
            // Une zone déclarée sans entrée dans `regions` n'est atteignable que par son index.
            $voie = array_search($titre, $regions, TRUE);
            $manquantes[] = [$index, $titre, $voie === FALSE ? NULL : (string) $voie];
        }
    }

    if ($manquantes)
    {
        $orphelines[$nom] = $manquantes;
    }
    else
    {
        $conformes[$nom] = count($zones);
    }
}

if ($detail)
{
    foreach ($conformes as $nom => $combien)
    {
        printf("  ✓ %-14s %d zone(s) déclarée(s), toutes rendues\n", $nom, $combien);
    }

    echo "\n";
}

if (!$orphelines)
{
    nf_ok(sprintf('%d thème(s), toutes les zones déclarées sont rendues', count($conformes)));
}

echo "ZONES DÉCLARÉES MAIS JAMAIS RENDUES — l'éditeur de mise en page les propose, le widget qu'on y\n";
echo "place est enregistré, et rien ne s'affiche :\n\n";

$total = 0;

foreach ($orphelines as $theme => $manquantes)
{
    printf("  %s\n", $theme);

    foreach ($manquantes as [$index, $titre, $region])
    {
        printf("      zone %d « %s »%s\n", $index, $titre, $region !== NULL ? " (region '".$region."')" : ' — aucune entrée dans regions');
        $total++;
    }

    echo "\n";
}

echo "Deux correctifs possibles, au choix :\n";
echo "  - rendre la zone dans le gabarit, comme le font blockcraft, forge et granite —\n";
echo "      <?php if (\$zone = \$this->output->region('footer')): ?> … <?php endif ?>\n";
echo "  - ou retirer la zone de `zones` et de `regions` si le thème ne veut pas l'offrir.\n";
echo "Ce qu'il ne faut pas laisser, c'est la promettre sans la tenir.\n";

nf_echec(sprintf('%d zone(s) déclarée(s) mais jamais rendue(s) dans %d thème(s)', $total, count($orphelines)));
