<?php
declare(strict_types=1);

/**
 * mise-en-page — rendre des pages par le pilote Playwright, traduire ce que les sondes y voient en constats, et les montrer regroupés.
 *
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * `check-mise-en-page` juge le site d'essai, servi par le serveur intégré ; `check-demo-connecte` juge la VRAIE
 * démonstration, connecté par son vrai formulaire (2026-10-10). Les deux font rendre des pages au même pilote
 * (`tests/MiseEnPage/pilote.js`), lisent les mêmes sondes (`sonde.js`, `sous-bandeau.js`) et présentent leurs
 * défauts de la même façon : un défaut, ses lieux. Tout cela vit ici, une fois.
 *
 * Usage
 * -----
 *   $node     = nf_mep_node();
 *   $sortie   = nf_mep_piloter($node, $base, ['/fr', '/fr/forum'], [390, 1280], 3, ['bandeau' => 40]);
 *   $constats = nf_mep_constats($sortie[0]['mesures'][0]);      // un verdict de sonde → constats
 *   nf_mep_montrer(nf_mep_regrouper($constats_avec_lieux), FALSE);
 *
 * Options du pilote : `bandeau` (px d'un faux bandeau de démo posé avant la passe « sous le bandeau » ; -1 : la passe
 * sans rien poser, le site a le sien ; 0 : pas de passe), `cookies` (liste de {name, value}), `stockage` (clés du
 * localStorage posées avant chaque page), `connexion` ({chemin, login, motdepasse} : le vrai formulaire, une fois,
 * puis la déconnexion à la fin).
 */

/** Le chemin de node, Playwright présent ; refuse sinon. */
function nf_mep_node(): string
{
    if (!is_dir(nf_racine().'/node_modules/@playwright/test'))
    {
        nf_refus('Playwright absent (node_modules/@playwright/test) — `npm install` à la racine du dépôt');
    }

    $node = trim((string) @shell_exec(stripos(PHP_OS, 'WIN') === 0 ? 'where node 2>NUL' : 'command -v node 2>/dev/null'));
    $node = $node === '' ? '' : explode("\n", $node)[0];

    if ($node === '' || !is_file($node))
    {
        nf_refus('node introuvable — installer Node.js');
    }

    return $node;
}

/** Le modèle d'une adresse : ses identifiants et le segment qui suit un identifiant sont effacés. */
function nf_mep_modele(string $chemin): string
{
    $segments = explode('/', trim($chemin, '/'));
    $apres_id = FALSE;

    foreach ($segments as $i => $s)
    {
        if (ctype_digit($s))
        {
            $segments[$i] = '{n}';
            $apres_id     = TRUE;
        }
        else if ($apres_id)
        {
            $segments[$i] = '{s}';
            $apres_id     = FALSE;
        }
    }

    return '/'.implode('/', $segments);
}

/**
 * Les pages à rendre : `--par-modele` représentantes par modèle d'adresse, séparées en deux familles.
 *
 * @param  list<string> $chemins
 * @return array{admin: list<string>, public: list<string>, modeles: int}
 */
function nf_mep_representantes(array $chemins, int $par_modele): array
{
    $par = [];

    foreach ($chemins as $c)
    {
        $m = nf_mep_modele($c);

        if (count($par[$m] ?? []) < $par_modele)
        {
            $par[$m][] = $c;
        }
    }

    $admin = $public = [];

    foreach ($par as $m => $liste)
    {
        foreach ($liste as $c)
        {
            preg_match('#^/[a-z]{2}/admin(/|$)#', $c) ? $admin[] = $c : $public[] = $c;
        }
    }

    return ['admin' => $admin, 'public' => $public, 'modeles' => count($par)];
}

/**
 * Les largeurs de la passe du bandeau : le plus petit écran demandé (un téléphone) et le plus grand jusqu'à 1 440 px
 * (un ordinateur) — défiler chaque page à chaque largeur coûterait le double du reste.
 *
 * @param  list<int> $largeurs
 * @return list<int>
 */
function nf_mep_largeurs_bandeau(array $largeurs): array
{
    if (!$largeurs)
    {
        return [];
    }

    $ordinateur = array_filter($largeurs, static fn (int $l): bool => $l <= 1440);

    return array_values(array_unique([min($largeurs), $ordinateur ? max($ordinateur) : max($largeurs)]));
}

/**
 * Rend des pages à toutes les largeurs par le pilote Playwright, et rend ses résultats.
 *
 * @param  list<string>         $pages
 * @param  list<int>            $largeurs
 * @param  array<string, mixed> $options  bandeau, cookies, stockage, connexion (voir l'en-tête)
 * @return list<array{chemin: string, code: int, erreur: string, connecte?: bool, console?: list<string>, ressources?: list<string>, mesures: list<array<string, mixed>>, bandeau?: list<array<string, mixed>>}>
 */
function nf_mep_piloter(string $node, string $base, array $pages, array $largeurs, int $parallele, array $options = []): array
{
    if (!$pages)
    {
        return [];
    }

    $travail  = nf_temp('mise-en-page-travail.json');
    $resultat = nf_temp('mise-en-page-resultat.json');
    @unlink($resultat);

    file_put_contents($travail, (string) json_encode([
        'base' => rtrim($base, '/'), 'pages' => $pages, 'largeurs' => $largeurs,
        'hauteur' => 900, 'parallele' => $parallele, 'delai' => 120,
        'bandeau' => (int) ($options['bandeau'] ?? 0), 'largeursBandeau' => nf_mep_largeurs_bandeau($largeurs),
        'cookies' => $options['cookies'] ?? [], 'stockage' => (object) ($options['stockage'] ?? []),
        'connexion' => $options['connexion'] ?? NULL,
    ]));

    $sortie = nf_temp('mise-en-page-pilote.log');
    $code   = 0;
    $cmd    = sprintf('cd %s && %s tests/MiseEnPage/pilote.js %s %s > %s 2>&1',
        escapeshellarg(nf_racine()), escapeshellarg($node), escapeshellarg($travail), escapeshellarg($resultat), escapeshellarg($sortie));

    exec($cmd, $rien, $code);

    $json = is_file($resultat) ? json_decode((string) file_get_contents($resultat), TRUE) : NULL;

    if (!is_array($json))
    {
        nf_refus('le pilote n\'a rien rendu (code '.$code.') : '.trim((string) @file_get_contents($sortie)));
    }

    return $json;
}

/**
 * Le verdict d'une sonde, traduit en constats : un type, une CLÉ (ce qui identifie le défaut d'une
 * page à l'autre) et un détail lisible.
 *
 * @param  array<string, mixed> $m
 * @return list<array{type: string, cle: string, detail: string, texte?: string}>
 */
function nf_mep_constats(array $m): array
{
    $c = [];

    if (!empty($m['deborde']))
    {
        $coupables = implode(', ', array_map(fn (array $x): string => $x['el'].' (+'.$x['depasse'].' px)', $m['coupables'] ?? []));
        $c[] = ['type' => 'débordement', 'cle' => $coupables !== '' ? $coupables : 'la page', 'detail' => $m['deborde'].' px trop large'];
    }

    foreach ($m['tronques'] ?? [] as $t)
    {
        $c[] = ['type' => 'texte tronqué', 'cle' => $t['el'], 'detail' => '« '.$t['texte'].' » dépasse de '.$t['depasse'].' px'];
    }

    foreach ($m['escaliers'] ?? [] as $e)
    {
        $c[] = ['type' => 'boutons en escalier', 'cle' => $e['el'], 'detail' => $e['boutons'].' boutons sur '.$e['lignes'].' lignes'];
    }

    foreach ($m['chevauchements'] ?? [] as $x)
    {
        $c[] = ['type' => 'chevauchement', 'cle' => $x['el'].' sur '.$x['sur'], 'detail' => '« '.$x['texte'].' »'];
    }

    foreach ($m['images'] ?? [] as $x)
    {
        $c[] = ['type' => 'image '.explode(' ', $x['defaut'])[0], 'cle' => $x['el'].' '.$x['src'], 'detail' => $x['src'].' — '.$x['defaut']];
    }

    foreach ($m['icones'] ?? [] as $x)
    {
        $c[] = ['type' => 'icône inconnue', 'cle' => $x['classes'], 'detail' => $x['el'].' — classes « '.$x['classes'].' »'];
    }

    foreach ($m['techniques'] ?? [] as $x)
    {
        $c[] = ['type' => 'texte technique', 'cle' => $x['defaut'].' : '.$x['el'], 'detail' => $x['defaut'].' — « '.$x['texte'].' »'];
    }

    foreach ($m['contrastes'] ?? [] as $x)
    {
        $c[] = ['type' => 'contraste', 'cle' => $x['el'].' '.$x['couleur'].' / '.$x['fond'], 'detail' => sprintf('« %s » %s:1 (exigé %s:1) — %s sur %s', $x['texte'], $x['ratio'], $x['exige'], $x['couleur'], $x['fond'])];
    }

    foreach ($m['horsEcran'] ?? [] as $x)
    {
        $c[] = ['type' => 'texte hors écran', 'cle' => $x['el'], 'detail' => '« '.$x['texte'].' » coupé de '.$x['depasse'].' px par le bord gauche'];
    }

    foreach ($m['colles'] ?? [] as $x)
    {
        $c[] = ['type' => 'boutons collés', 'cle' => $x['el'], 'detail' => $x['texte'].' — '.($x['ecart'] < 0 ? 'chevauchés de '.(-$x['ecart']) : 'écartés de '.$x['ecart']).' px'];
    }

    foreach ($m['bords'] ?? [] as $x)
    {
        $c[] = ['type' => 'collé au bord', 'cle' => $x['carte'].' ⟶ '.$x['el'], 'detail' => '« '.$x['texte'].' » à '.max(0, $x['ecart']).' px du bord '.$x['cote'].' de sa carte'];
    }

    foreach ($m['cibles'] ?? [] as $x)
    {
        $c[] = ['type' => 'cible trop petite', 'cle' => $x['el'], 'detail' => '« '.($x['texte'] ?? '').' » '.$x['taille'].' — 24×24 px au moins sur un téléphone'];
    }

    // La clé est le TEXTE : le même libellé oublié se retrouve sur cent pages, c'est un seul oubli.
    foreach ($m['francais'] ?? [] as $x)
    {
        $c[] = ['type' => 'texte en français', 'cle' => '« '.$x['texte'].' »'.($x['ou'] !== 'texte' ? ' ('.$x['ou'].')' : ''), 'detail' => $x['el'], 'texte' => $x['texte']];
    }

    return $c;
}

/**
 * Les constats regroupés par défaut — un même élément fautif sur vingt pages et trois thèmes est UN défaut, dont on
 * donne les lieux. `$preparer` peut réécrire un constat avant qu'il ne compte, ou l'écarter en rendant NULL ; il n'est
 * appelé qu'au premier constat de chaque défaut.
 *
 * @param  list<array<string, mixed>> $constats  chacun avec type, cle, detail, site, theme, largeur, modele, chemin
 * @return array<string, array<string, mixed>>
 */
function nf_mep_regrouper(array $constats, ?callable $preparer = NULL): array
{
    $defauts = [];

    foreach ($constats as $c)
    {
        $cle = $c['type'].'|'.$c['cle'];

        if ($preparer !== NULL && !isset($defauts[$cle]) && ($c = $preparer($c)) === NULL)
        {
            continue;
        }

        $d   = &$defauts[$cle];
        $d['type']  = $c['type'];
        $d['cle']   = $c['cle'];
        $d['detail'] ??= $c['detail'];
        $d['sites'][$c['site']] = TRUE;
        $d['themes'][$c['theme']] = TRUE;

        if ($c['largeur'])
        {
            $d['largeurs'][$c['largeur']] = TRUE;
        }

        $d['modeles'][$c['modele']] = $c['chemin'];
        unset($d);
    }

    uasort($defauts, fn (array $a, array $b): int => [$a['type'], -count($b['modeles'])] <=> [$b['type'], -count($a['modeles'])]);

    return $defauts;
}

/**
 * Les défauts regroupés, en ligne de commande.
 *
 * @param array<string, array<string, mixed>> $defauts
 */
function nf_mep_montrer(array $defauts, bool $detail): void
{
    foreach ($defauts as $d)
    {
        $d['largeurs'] ??= [];
        ksort($d['largeurs']);
        printf("  ✗ %-20s %s\n", $d['type'], mb_substr($d['cle'], 0, 120));
        printf("      %s\n", mb_substr($d['detail'], 0, 160));
        printf("      %s · %s%s\n", implode(' + ', array_keys($d['sites'])), implode(', ', array_keys($d['themes'])), $d['largeurs'] ? ' · '.implode(', ', array_keys($d['largeurs'])).' px' : '');

        $modeles = array_values($d['modeles']);
        printf("      %d page(s) : %s%s\n", count($modeles), implode(', ', array_slice($modeles, 0, $detail ? 50 : 4)), count($modeles) > 4 && !$detail ? ' …' : '');
    }
}
