<?php
declare(strict_types=1);
require_once __DIR__.'/outil.php';

/**
 * NeoFrag Reborn — manifeste de découplage / packaging (3 tiers).
 *
 * Ce fichier était, jusqu'au 2026-09-15, une **table écrite à la main** figée le 2026-06-06. C'était
 * la seconde source de vérité sur les tiers, à côté des déclarations des addons eux-mêmes — et les
 * deux avaient déjà divergé : `emojis` y figurait comme cœur alors qu'il se déclare à la carte, ce
 * qui le rendait **décochable à l'installation mais absent du catalogue**, donc impossible à
 * réinstaller ensuite. Exactement le genre d'écart qu'une liste tenue à la main finit toujours par
 * produire.
 *
 * Il DÉRIVE désormais des déclarations, qui sont la seule source de vérité :
 *
 *   Tier 0 — Cœur     : `'core' => TRUE`. Toujours installé, non désinstallable, jamais packagé.
 *   Tier 1 — Identité : `'core' => FALSE` + `'presets'` non vide. Pré-coché par les profils.
 *   Tier 2 — À la carte : `'core' => FALSE` + `'presets'` vide. Marketplace ou coche manuelle.
 *
 * Un addon `'distributed' => FALSE` (le thème `vitrine`, qui est notre propre site) n'apparaît dans
 * AUCUNE des deux listes : il ne doit être ni packagé ni publié au catalogue.
 *
 * La forme de retour est inchangée — `['identity' => ['module' => [...], 'widget' => [...], 'theme' =>
 * [...]], 'optional' => [...]]` — pour les trois consommateurs : tools/package-addons.php,
 * tools/un outil interne.php et tools/dump-schema.php.
 *
 * ⚠ Ce qui reste vrai et n'est PAS déductible d'ici : les thèmes granite/blockcraft/forge référencent
 * les widgets `talks` et `slider` dans leurs dispositions. Ces deux widgets doivent donc rester au
 * cœur, sous peine d'orphelins. C'est tools/check-addon-declarations.php qui garde cette propriété.
 */

require_once dirname(__DIR__, 2).'/install/lib/installer.php';

$nf_declarations = \NF\Install\Lib\Installer::addon_declarations(dirname(__DIR__, 2));

$nf_manifeste = [
    'identity' => ['module' => [], 'widget' => [], 'theme' => []],
    'optional' => ['module' => [], 'widget' => [], 'theme' => []],
];

foreach ($nf_declarations as $nf_cle => $nf_d)
{
    [$nf_type, $nf_nom] = explode(':', $nf_cle, 2);

    // Cœur, ou hors distribution : ni packagé, ni publié.
    if (!empty($nf_d['core']) || (isset($nf_d['distributed']) && !$nf_d['distributed']))
    {
        continue;
    }

    if (!isset($nf_manifeste['identity'][$nf_type]))
    {
        continue; // type inconnu : on ne l'invente pas
    }

    $nf_manifeste[empty($nf_d['presets']) ? 'optional' : 'identity'][$nf_type][] = $nf_nom;
}

foreach ($nf_manifeste as &$nf_tier)
{
    foreach ($nf_tier as &$nf_liste)
    {
        sort($nf_liste);
    }
}

unset($nf_tier, $nf_liste, $nf_declarations, $nf_d, $nf_cle, $nf_type, $nf_nom);

return $nf_manifeste;
