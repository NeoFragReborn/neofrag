<?php
declare(strict_types=1);

/**
 * check-js-console — balaye les pages dans un vrai navigateur et refuse toute erreur JavaScript, violation CSP ou script introuvable.
 *
 * Famille : navigateur
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le 2026-09-17, quatre scripts du projet appelaient encore jQuery — retiré trois mois plus tôt.
 * Chacun levait « ReferenceError: $ is not defined » à sa première ligne, et personne ne l'a vu :
 * un script qui plante AVANT d'attacher ses écouteurs ne casse rien de visible. La page s'affiche,
 * les boutons sont dessinés, ils ne font rien. Aucun 500, rien dans le journal PHP, aucun test en
 * échec. La seule trace était dans la console du navigateur, que personne ne lit sur une page qui
 * « marche ». Le tri des tables d'administration, le glisser-déposer du forum et les cartes
 * Ouvert/Fermé de la maintenance étaient morts depuis juin.
 *
 * `check-js-sources` valide la syntaxe et le vocabulaire, `check-js` des contrats isolés : aucun ne
 * voit ce qui se passe quand la VRAIE page charge ses VRAIS scripts dans l'ordre où le CMS les
 * émet. Cet outil-là le voit : il pose une sonde en tête de chaque page servie — avant le premier
 * script, avec le nonce de la réponse — et relève tout ce qui remonte à la fenêtre.
 *
 * Une page qui ne rend pas de verdict n'est PAS une page sans erreur : elle est déclarée muette, et
 * trop de pages muettes font échouer le contrôle — un contrôle aveugle qui se dit vert est pire
 * qu'un contrôle absent.
 *
 * Usage
 * -----
 *   php tools/check-js-console.php                  toutes les pages d'administration + un échantillon public
 *   php tools/check-js-console.php --max=40         les 40 premières (CI pressée)
 *   php tools/check-js-console.php -- /fr /fr/admin/forum
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/navigateur.php';

[$o, $chemins] = nf_options(['max' => 0, 'port' => 0]);

// ── 1. Les pages à balayer ───────────────────────────────────────────────────
// Les routes d'administration déclarées par chaque module (même lecture que check-admin-back :
// c'est la source qu'emploie le routeur), l'accueil d'administration de chaque module, et un
// échantillon des pages publiques — celles qui portent du JavaScript à elles.
if (!$chemins)
{
    $routes = [];

    foreach (nf_addons('module') as $module => $dossier)
    {
        $source = (string) file_get_contents($dossier.'/'.$module.'.php');

        if (!preg_match("/'routes'\s*=>\s*\[(.*?)\n\t*\]/s", $source, $bloc))
        {
            continue;
        }

        preg_match_all("/'(admin\/[^']*)'\s*=>\s*'([^']+)'/", $bloc[1], $trouves, PREG_SET_ORDER);

        foreach ($trouves as $t)
        {
            if (str_contains($t[1], '/ajax/'))
            {
                continue; // un point d'entrée AJAX n'est pas une page : pas de scripts à y exécuter
            }

            $chemin = 'admin/'.$module.'/'.substr($t[1], strlen('admin/'));

            if (str_contains($chemin, '{') || str_contains($chemin, '('))
            {
                // Tentée avec des valeurs plausibles ; si la cible n'existe pas, la page ne répond pas
                // 200 et n'est pas jugée — jamais comptée comme bonne.
                $chemin = (string) preg_replace(['/\{id\}/', '/\{url_title\}/', '/\([^)]*\)/', '/\{[^}]*\}/'], ['1', 'x', '', ''], $chemin);
                $chemin = rtrim(str_replace('//', '/', $chemin), '/');
            }

            $routes['/fr/'.$chemin] = TRUE;
        }

        if (is_file($dossier.'/controllers/admin.php'))
        {
            $routes['/fr/admin/'.$module] = TRUE;
        }
    }

    $publiques = ['/fr', '/fr/forum', '/fr/news', '/fr/members', '/fr/events', '/fr/gallery', '/fr/contact',
        '/fr/search?q=test', '/fr/user', '/fr/user/account', '/fr/user/profile', '/fr/user/login', '/fr/admin'];

    $chemins = array_values(array_unique(array_merge($publiques, array_keys($routes))));
}

if ($o['max'] > 0)
{
    $chemins = array_slice($chemins, 0, $o['max']);
}

// ── 2. La sonde ─────────────────────────────────────────────────────────────
// Posée EN TÊTE du document, avant le premier script : c'est la seule façon d'entendre une erreur
// levée au chargement. Elle relève quatre familles, puis écrit son verdict dans le DOM après `load`.
$sonde = <<<'JS'
(function(){
    var releves = [];

    function noter(type, message, source, ligne){
        releves.push({
            type:    type,
            message: String(message || '').slice(0, 300),
            source:  String(source || '').replace(/^https?:\/\/[^\/]+/, ''),
            ligne:   ligne || 0
        });
    }

    window.addEventListener('error', function(e){
        // Une ressource introuvable arrive ici aussi (phase de capture), sans message : on nomme sa balise.
        if (e.target && e.target !== window && !e.message){
            var t = e.target;
            noter('ressource:' + String(t.tagName || '').toLowerCase(), 'introuvable', t.src || t.href || '', 0);
            return;
        }
        noter('erreur', e.message, e.filename, e.lineno);
    }, true);

    window.addEventListener('unhandledrejection', function(e){
        var r = e.reason;
        noter('promesse', r && r.message ? r.message : r, '', 0);
    });

    document.addEventListener('securitypolicyviolation', function(e){
        noter('csp', e.violatedDirective + ' bloque ' + (e.blockedURI || 'un script inline'), e.sourceFile || '', e.lineNumber || 0);
    });

    function verdict(){
        var n = document.createElement('div');
        n.id = 'nf-console-verdict';
        n.setAttribute('data-verdict', JSON.stringify({ releves: releves, scripts: document.scripts.length }));
        (document.body || document.documentElement).appendChild(n);
    }

    if (document.readyState === 'complete'){ setTimeout(verdict, 500); }
    else { window.addEventListener('load', function(){ setTimeout(verdict, 500); }); }
})();
JS;

$fichier_sonde = nf_temp('sonde.js');
file_put_contents($fichier_sonde, $sonde);

// ── 3. Session d'administrateur temporaire, serveur, sonde en tête ──────────
$db      = nf_connexion();
$serveur = nf_serveur(nf_port($o['port']), [
    'NF_OUTIL_SESSION'  => nf_session_admin($db),
    'NF_OUTIL_SONDE'    => $fichier_sonde,
    'NF_OUTIL_SONDE_OU' => 'head',
]);

printf("Base : %s — %d page(s) candidates\n", $serveur->base, count($chemins));

// Un asset témoin avant de juger : feuilles et scripts passent par index.php sous le routeur des
// outils. S'il ne répond pas 200, chaque page comptera une dizaine de ressources « introuvables »
// et le verdict parlera du serveur, pas des scripts — autant le dire en une ligne.
$temoin = nf_http($serveur->base.'/fr/css/bootstrap.min.css?v=1', ['suivre' => 0]);
printf("Asset témoin : /fr/css/bootstrap.min.css?v=1 → %s\n\n", $temoin['code']
    ? $temoin['code'].' '.($temoin['entetes']['content-type'] ?? 'sans Content-Type').', '.strlen($temoin['corps']).' octets'
        .(isset($temoin['entetes']['location']) ? ' → '.$temoin['entetes']['location'] : '')
    : 'pas de réponse ('.$temoin['raison'].')');

$fautives   = 0;   // pages avec au moins une erreur bloquante
$jugees     = 0;
$non_jugees = [];
$muettes    = [];
$avertis    = 0;   // ressources non-script introuvables : signalées, pas bloquantes

foreach ($chemins as $chemin)
{
    // Seules les pages en 200 sont jugées : une redirection (page réservée, cible absente) mènerait
    // à juger une AUTRE page que celle annoncée.
    $code = nf_statut($serveur->base.$chemin);

    if ($code !== 200)
    {
        $non_jugees[] = sprintf('%s (%s)', $chemin, $code ?: 'sans réponse');
        continue;
    }

    $debut   = microtime(TRUE);
    $verdict = nf_sonde_verdict(nf_chrome_dom($serveur->base.$chemin, ['budget' => 8000]), 'nf-console-verdict');

    // Une page lente se dit tout de suite : l'épreuve n'écrit rien d'autre avant la fin, et une page
    // qui ne finissait pas de charger la faisait tomber sur la limite de la CI sans laisser de trace.
    if (($duree = microtime(TRUE) - $debut) > 25)
    {
        printf("  … %s : %d s%s\n", $chemin, $duree, $verdict === NULL ? ', interrompue' : '');
        flush();
    }

    if ($verdict === NULL)
    {
        $muettes[] = $chemin;
        continue;
    }

    $jugees++;

    $bloquantes  = [];
    $secondaires = [];

    foreach ($verdict['releves'] ?? [] as $r)
    {
        // Un script ou une feuille introuvable casse la page ; une image absente ne casse rien.
        $grave = !str_starts_with($r['type'], 'ressource:') || in_array($r['type'], ['ressource:script', 'ressource:link'], TRUE);

        if ($grave)
        {
            $bloquantes[] = $r;
        }
        else
        {
            $secondaires[] = $r;
        }
    }

    $avertis += count($secondaires);

    if (!$bloquantes)
    {
        continue;
    }

    $fautives++;

    printf("  ✗ %s — %d erreur(s), %d script(s) sur la page\n", $chemin, count($bloquantes), $verdict['scripts'] ?? 0);

    foreach (array_slice($bloquantes, 0, 8) as $r)
    {
        printf("       [%s] %s%s\n", $r['type'], $r['message'], $r['source'] !== '' ? sprintf('  (%s:%d)', $r['source'], $r['ligne']) : '');
    }
}

@unlink($fichier_sonde);

printf("\n%d page(s) jugée(s), %d non jugée(s) (autre statut que 200), %d muette(s), %d ressource(s) secondaire(s) introuvable(s).\n",
    $jugees, count($non_jugees), count($muettes), $avertis);

if ($muettes)
{
    printf("\nMuettes — la sonde n'a rendu aucun verdict, ces pages n'ont PAS été jugées :\n");

    foreach (array_slice($muettes, 0, 10) as $m)
    {
        printf("   %s\n", $m);
    }
}

if ($jugees === 0)
{
    nf_refus("aucune page jugée : un contrôle qui n'a rien mesuré ne peut pas être vert");
}

if ($muettes && count($muettes) > $jugees / 4)
{
    nf_refus("trop de pages muettes pour conclure : vérifier l'injection de la sonde (nonce, <head>)");
}

if ($fautives)
{
    nf_echec(sprintf("%d page(s) avec des erreurs JavaScript au chargement — un script qui plante avant d'attacher ses écouteurs ne casse rien de visible, c'est ici qu'on le voit", $fautives));
}

nf_ok(sprintf('aucune erreur JavaScript, aucune violation CSP, aucun script introuvable sur %d page(s)', $jugees));
