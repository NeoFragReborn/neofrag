<?php
declare(strict_types=1);

/**
 * check-important — mesure quels `!important` d'une feuille servent réellement à quelque chose.
 *
 * Famille : cible
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le produit en compte près de trois cents, répartis sur 31 feuilles. Le premier plan proposait de les
 * retirer « un par un, avec capture avant/après » : le chantier n'avançait pas, parce que juger un
 * seul cas demandait de regarder sept thèmes à deux modes sur une dizaine de pages.
 *
 * Et il n'existe aucun raccourci par la lecture. Un `!important` peut se battre contre un utilitaire
 * de Bootstrap (qui en porte un lui aussi), contre une autre règle du produit, ou contre **le
 * navigateur lui-même** : le fond jaune du remplissage automatique n'est déclaré dans aucune
 * feuille. Aucune analyse statique ne voit le troisième cas — un premier tri écrit puis jeté le
 * 2026-09-21 rangeait justement ces règles-là parmi les « inutiles ».
 *
 * Ce que fait cet outil
 * ---------------------
 * Il MESURE. Il rend chaque page deux fois — une fois telle quelle, une fois avec les `!important`
 * d'une feuille retirés — et compare le style CALCULÉ de chaque élément. Si rien ne bouge nulle
 * part, ces `!important` ne gagnent contre rien. Si quelque chose bouge, il DICHOTOMISE pour
 * désigner ceux qui comptent, en une poignée de rendus plutôt qu'un par candidat. Il éprouve
 * d'abord la RÉPÉTABILITÉ de chaque page : une page dont l'empreinte bouge toute seule est écartée.
 *
 * « Sans effet sur les pages mesurées » n'est pas « sans effet » : une règle peut ne compter que
 * sur un écran absent de la liste. Le verdict le dit tel quel. L'outil RÉÉCRIT la feuille pendant
 * qu'il mesure, puis la restaure ; une feuille demande une vingtaine de minutes de rendus.
 *
 * Les deux garde-fous, et pourquoi ils ne sont pas facultatifs
 * -----------------------------------------------------------
 * La sonde rend chaque page UNE fois, a 1400x1000, sans souris ni clavier. Tout ce qui depend d'un
 * etat qu'elle ne reproduit pas serait declare « sans effet » alors qu'elle ne l'a jamais eprouve :
 * un `:hover`, un `@media print`, un point de rupture etroit, un remplissage automatique. Ces
 * regles-la sont donc ECARTEES de la mesure et listees a part — l'outil dit qu'il NE SAIT PAS, au
 * lieu de dire non. Le cas qui a rendu ce garde-fou necessaire : `.list-group-item:hover` dans
 * nebula, que la premiere version aurait propose de retirer.
 *
 * Deuxieme garde-fou : un `!important` dont le SELECTEUR n'apparait sur aucune page mesuree ne
 * change evidemment rien — ce n'est pas une preuve d'inutilite, c'est l'aveu que la page ne
 * l'exerce pas. La sonde releve donc, page par page, quels selecteurs trouvent au moins un
 * element, et l'outil ne propose au retrait que ce qui etait SERVI et sans effet. Sans quoi
 * `.no-transitions *` — une classe que le theme pose le temps d'une image, a la bascule clair /
 * sombre — passerait pour mort.
 *
 * Usage
 * -----
 *   php tools/check-important.php --feuille=themes/admin/css/style.css
 *   php tools/check-important.php --feuille=css/nf-bs5-bridge.css --theme=forge --pages=/fr,/fr/forum
 *   php tools/check-important.php --liste          les feuilles concernées et leur compte
 *   php tools/check-important.php --feuille=… --stabilite     n'éprouve que la répétabilité
 *   php tools/check-important.php --feuille=… --garder        ne pas restaurer la feuille (débogage)
 *   php tools/check-important.php --feuille=… --connecte      rendre les pages EN ÉTANT connecté
 *
 * `--connecte` n'est pas un détail : dans un thème public, une bonne part des `!important` vise la
 * barre du membre (`.navbar-user…`) ou son encart (`.widget-user…`), que le visiteur anonyme ne
 * voit JAMAIS. Sans session, ces sélecteurs ne trouvent aucun élément et l'outil refuse de conclure
 * — ce qui est honnête, mais stérile. Le thème d'administration ouvre la session d'office.
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/navigateur.php';

[$o] = nf_options(['feuille' => '', 'theme' => '', 'pages' => '', 'port' => 0, 'liste' => FALSE, 'garder' => FALSE, 'stabilite' => FALSE, 'connecte' => FALSE]);

// ── Les feuilles concernées ─────────────────────────────────────────────────
if ($o['liste'] || $o['feuille'] === '')
{
    $feuilles = [];

    foreach (nf_fichiers(['css', 'themes', 'modules', 'widgets'], ['css']) as $relatif => $chemin)
    {
        if ($n = substr_count((string) file_get_contents($chemin), '!important'))
        {
            $feuilles[$relatif] = $n;
        }
    }

    arsort($feuilles);

    printf("%d feuille(s) portent un `!important`, %d au total.\n\n", count($feuilles), array_sum($feuilles));

    foreach ($feuilles as $relatif => $n)
    {
        printf("  %4d  %s\n", $n, $relatif);
    }

    nf_ok('choisir une feuille : --feuille=<chemin relatif>');
}

$relatif = ltrim(str_replace('\\', '/', $o['feuille']), '/');
$feuille = nf_racine().'/'.$relatif;

if (!is_file($feuille))
{
    nf_refus("feuille introuvable : $relatif");
}

$original  = (string) file_get_contents($feuille);
$positions = [];
$decalage  = 0;

while (($p = strpos($original, '!important', $decalage)) !== FALSE)
{
    $positions[] = $p;
    $decalage    = $p + 10;
}

if (!$positions)
{
    nf_ok("aucun `!important` dans $relatif");
}

printf("%s — %d `!important`.\n", $relatif, count($positions));

// ── Ce que la sonde sait mesurer, et ce qu'elle ne sait pas ─────────────────────────
// Les commentaires sont BLANCHIS (remplacés par des espaces) et non retirés : les positions
// relevées ci-dessus doivent rester valables, et une accolade en commentaire fausserait la pile.
$neutre = (string) preg_replace_callback('#/\*.*?\*/#s',
    static fn (array $m): string => str_repeat(' ', strlen($m[0])), $original);

// Les dimensions du rendu, nommées une fois : la portée des points de rupture se juge contre
// elles, et la sonde rend contre elles. Les désolidariser ferait mentir le premier jugement.
const NF_LARGEUR = 1400;
const NF_HAUTEUR = 1000;

$selecteurs = [];
$ecartes    = [];
$mesurables = [];

foreach ($positions as $i => $position)
{
    $pile           = nf_pile_blocs($neutre, $position);
    // Les retours à la ligne d'une liste de sélecteurs cassaient l'alignement du rapport ;
    // dans un sélecteur CSS, une suite d'espaces et un espace se valent.
    $selecteurs[$i] = trim((string) preg_replace('/\s+/', ' ', (string) (end($pile) ?: '')));
    $raison         = nf_hors_de_portee($pile, NF_LARGEUR, NF_HAUTEUR);

    if ($raison === '')
    {
        $mesurables[] = $i;
    }
    else
    {
        $ecartes[$i] = $raison;
    }
}

$ligne = static fn (int $position): int => substr_count(substr($original, 0, $position), "\n") + 1;

if ($ecartes)
{
    printf("\n%d hors de portée de la sonde — CONSERVÉS sans discussion :\n", count($ecartes));

    foreach ($ecartes as $i => $raison)
    {
        printf("  %s:%-5d %-44s %s\n", $relatif, $ligne($positions[$i]),
            mb_strimwidth($selecteurs[$i], 0, 44, '…'), $raison);
    }

    echo "\nUn rendu unique ne reproduit pas ces états : les mesurer rendrait « sans effet » un\n";
    echo "verdict qui n'a jamais été éprouvé.\n";
}

if (!$mesurables)
{
    nf_ok(sprintf('les %d `!important` de %s sont tous hors de portée de la sonde : rien à juger ici',
        count($positions), $relatif));
}

printf("\n%d `!important` mesurables.\n", count($mesurables));

// La feuille est restaurée quoi qu'il arrive — sauf --garder, pour déboguer.
register_shutdown_function(static function () use ($feuille, $original, $o): void {
    if (!$o['garder'] && @file_get_contents($feuille) !== $original)
    {
        file_put_contents($feuille, $original);
        echo "\n(feuille restaurée)\n";
    }
});

/**
 * La PILE des en-têtes de blocs ouverts à cette position : la dernière entrée est le sélecteur de
 * la règle, celles d'avant sont les at-rules qui l'englobent (`@media`, `@supports`).
 *
 * @return list<string>
 */
function nf_pile_blocs(string $neutre, int $position): array
{
    $pile  = [];
    $debut = 0;

    for ($i = 0; $i < $position; $i++)
    {
        if ($neutre[$i] === '{')
        {
            $pile[] = trim(substr($neutre, $debut, $i - $debut));
            $debut  = $i + 1;
        }
        elseif ($neutre[$i] === '}')
        {
            array_pop($pile);
            $debut = $i + 1;
        }
        elseif ($neutre[$i] === ';')
        {
            $debut = $i + 1;
        }
    }

    return array_values($pile);
}

/**
 * Pourquoi la sonde ne peut RIEN dire de cette règle, ou '' si elle le peut.
 *
 * @param list<string> $pile
 */
function nf_hors_de_portee(array $pile, int $largeur, int $hauteur): string
{
    $selecteur = (string) (end($pile) ?: '');

    // Les états qu'un rendu unique, sans souris ni clavier, ne produit jamais.
    $etats = [
        ':hover'             => 'survol de la souris',
        ':focus'             => "élément au focus",
        ':active'            => 'clic maintenu',
        ':visited'           => "lien déjà visité",
        ':target'            => "ancre visée par l'adresse",
        ':autofill'          => 'remplissage automatique du navigateur',
        ':placeholder-shown' => "champ resté vide",
        ':user-invalid'      => 'champ saisi puis invalidé',
        '::selection'        => "texte sélectionné",
        '::backdrop'         => "arrière-plan d'une fenêtre native",
        ':fullscreen'        => "plein écran",
        '::-webkit-'         => "pseudo-élément propre au moteur",
        '::-moz-'            => "pseudo-élément propre au moteur",
        ':-moz-'             => "état propre au moteur",
    ];

    foreach ($etats as $marque => $raison)
    {
        if (str_contains($selecteur, $marque))
        {
            return $raison;
        }
    }

    // Les conditions de média que CE rendu ne remplit pas.
    $conditions = [
        'print'                  => 'impression',
        'prefers-reduced-motion' => "animations réduites",
        'prefers-contrast'       => "contraste renforcé",
        'prefers-color-scheme'   => "préférence claire / sombre du système",
        'forced-colors'          => "couleurs imposées par le système",
        'hover: none'            => 'appareil sans survol',
        'pointer: coarse'        => 'pointeur grossier',
    ];

    foreach ($pile as $entete)
    {
        if (!str_starts_with($entete, '@media'))
        {
            continue;
        }

        foreach ($conditions as $marque => $raison)
        {
            if (str_contains($entete, $marque))
            {
                return $raison;
            }
        }

        if (preg_match('/max-width:\s*(\d+)/', $entete, $m) && (int) $m[1] < $largeur)
        {
            return 'largeur ≤ '.$m[1].' px (rendu à '.$largeur.')';
        }

        if (preg_match('/min-width:\s*(\d+)/', $entete, $m) && (int) $m[1] > $largeur)
        {
            return 'largeur ≥ '.$m[1].' px (rendu à '.$largeur.')';
        }

        if (preg_match('/max-height:\s*(\d+)/', $entete, $m) && (int) $m[1] < $hauteur)
        {
            return 'hauteur ≤ '.$m[1].' px (rendu à '.$hauteur.')';
        }
    }

    return '';
}

/**
 * Le contenu de la feuille avec, retirés, les `!important` dont l'index est dans $retirer.
 * Par POSITION et non par expression régulière : deux déclarations identiques dans deux règles
 * différentes doivent rester distinguables, sans quoi la dichotomie désigne la mauvaise.
 */
function sans_important(string $source, array $positions, array $retirer): string
{
    $sortie = '';
    $avant  = 0;

    foreach ($positions as $i => $p)
    {
        if (!in_array($i, $retirer, TRUE))
        {
            continue;
        }

        $sortie .= substr($source, $avant, $p - $avant);
        $avant   = $p + 10;
    }

    return $sortie.substr($source, $avant);
}

// ── Le thème et les pages ───────────────────────────────────────────────────
// Une feuille de thème ne s'applique QUE sous son thème : la mesurer sous un autre ne montrerait
// rien, et conclurait à tort que tous ses `!important` sont inertes.
$theme = $o['theme'];

if ($theme === '' && preg_match('#^themes/([^/]+)/#', $relatif, $trouve))
{
    $theme = $trouve[1];
}

$admin = $theme === 'admin';
$pages = $o['pages'] !== ''
    ? array_values(array_filter(array_map('trim', explode(',', $o['pages']))))
    : ($admin
        ? ['/fr/admin', '/fr/admin/settings', '/fr/admin/user', '/fr/admin/addons', '/fr/admin/news']
        : ['/fr', '/fr/forum', '/fr/news', '/fr/user', '/fr/contact']);

printf("Thème : %s · %d page(s)\n\n", $theme ?: '(celui du site)', count($pages));

// ── La sonde : l'empreinte du style calculé de chaque élément ───────────────
// On ne relève QUE les propriétés que cette feuille impose, sinon l'empreinte bouge au moindre
// détail sans rapport et tout paraît significatif. Les commentaires d'abord : un mot de prose
// placé juste avant un `!important` passait pour un nom de propriété.
$sans_commentaires = (string) preg_replace('#/\*.*?\*/#s', '', $original);
$proprietes        = [];

foreach (preg_split('/\s*!important/', $sans_commentaires) ?: [] as $morceau)
{
    if (preg_match('/(?:^|[;{])\s*([-a-z]+)\s*:[^;{}]*$/i', $morceau, $trouve))
    {
        $proprietes[] = strtolower($trouve[1]);
    }
}

$proprietes = array_values(array_unique($proprietes));

printf("Propriétés surveillées : %s\n\n", implode(', ', $proprietes));

// Un CONDENSÉ et non l'empreinte entière : sur la page des addons — des milliers d'éléments,
// seize propriétés chacun — l'attribut dépassait le mégaoctet et la sonde paraissait muette.
// Deux condensés indépendants : une collision sur les deux à la fois est hors de portée.
$sonde = <<<'JS'
(function(){
    var props = NF_PROPRIETES;
    var sels = NF_SELECTEURS;
    var tous = document.querySelectorAll('body *');
    var h1 = 5381, h2 = 52711;

    function avaler(s) {
        for (var k = 0; k < s.length; k++) {
            var c = s.charCodeAt(k);
            h1 = (h1 * 33 ^ c) >>> 0;
            h2 = (h2 * 31 ^ c) >>> 0;
        }
    }

    for (var i = 0; i < tous.length; i++) {
        var cs = getComputedStyle(tous[i]);

        for (var j = 0; j < props.length; j++) {
            avaler(cs.getPropertyValue(props[j]));
            avaler('|');
        }
    }

    // Quels selecteurs trouvent au moins un element ICI : « 1 » servi, « 0 » absent, « ? » refuse
    // par le moteur. Hors empreinte : cette chaine ne doit pas faire bouger la comparaison.
    var servis = '';

    for (var s = 0; s < sels.length; s++) {
        try { servis += document.querySelector(sels[s]) ? '1' : '0'; }
        catch (e) { servis += '?'; }
    }

    var bal = document.createElement('div');
    bal.id = 'nf-important-verdict';
    bal.setAttribute('data-verdict', JSON.stringify({ empreinte: h1.toString(16) + '-' + h2.toString(16) + '-' + tous.length, servis: servis }));
    document.body.appendChild(bal);
})();
JS;

// Les sélecteurs partent DANS L'ORDRE des `!important` mesurables : la chaîne rendue par la sonde
// se lit donc caractère par caractère contre cette liste.
$a_tester = array_values(array_map(static fn (int $i): string => $selecteurs[$i], $mesurables));

$fichier_sonde = nf_temp('sonde.js');
file_put_contents($fichier_sonde, str_replace(
    ['NF_PROPRIETES', 'NF_SELECTEURS'],
    [json_encode($proprietes) ?: '[]', json_encode($a_tester) ?: '[]'],
    $sonde));

// ── Serveur : session d'administration au besoin, bandeau cookies écarté, sonde en pied ─────
$env = ['NF_OUTIL_CONSENT' => 'all', 'NF_OUTIL_SONDE' => $fichier_sonde, 'NF_OUTIL_SONDE_OU' => 'body'];

if ($admin || $o['connecte'])
{
    $env['NF_OUTIL_SESSION'] = nf_session_admin(nf_connexion());
    echo "Pages rendues EN ÉTANT CONNECTÉ.\n";
}

$serveur = nf_serveur(nf_port($o['port']), $env);

/**
 * Rend les pages et rend l'empreinte de chacune (NULL si la sonde est restée muette). $servis, s'il
 * est fourni, reçoit par page la chaîne disant quels sélecteurs ont trouvé un élément.
 *
 * Trois tentatives, avec de plus en plus de temps : une page lourde peut dépasser le budget de temps
 * virtuel, et une page non mesurée rend fausse la dichotomie qui suit. Et un profil NEUF à chaque
 * rendu : un profil partagé garde un cache, alors que la feuille change justement entre deux mesures.
 */
function empreintes(array $pages, string $base, ?array &$servis = NULL): array
{
    $sortie = [];

    foreach ($pages as $page)
    {
        foreach ([8000, 20000, 30000] as $budget)
        {
            $profil  = 'jetable-'.bin2hex(random_bytes(4));
            $verdict = nf_sonde_verdict(nf_chrome_dom($base.$page, ['largeur' => NF_LARGEUR, 'hauteur' => NF_HAUTEUR, 'budget' => $budget, 'profil' => $profil]), 'nf-important-verdict');
            exec('rm -rf '.escapeshellarg(nf_temp('profil-'.$profil)));

            if ($verdict !== NULL && isset($verdict['empreinte']))
            {
                $sortie[$page] = (string) $verdict['empreinte'];

                if ($servis !== NULL && isset($verdict['servis']))
                {
                    $servis[$page] = (string) $verdict['servis'];
                }

                continue 2;
            }
        }

        $sortie[$page] = NULL;
    }

    return $sortie;
}

/*
 * ÉPREUVE DE RÉPÉTABILITÉ, toujours jouée avant de conclure quoi que ce soit. Tout ce qui suit
 * repose sur une hypothèse : deux rendus de la MÊME page, sans rien changer, donnent la même
 * empreinte. Si c'est faux, chaque différence observée plus loin peut venir du bruit. Ce n'est pas
 * théorique : un premier verdict annonçait « 29 des 33 comptent » alors que la moitié des règles
 * visées portent sur un calendrier ABSENT des pages mesurées — c'était du bruit.
 */
echo "Répétabilité : on rend chaque page deux fois sans rien changer…\n";

$premier   = empreintes($pages, $serveur->base);
$second    = empreintes($pages, $serveur->base);
$instables = [];

foreach ($pages as $page)
{
    if (($premier[$page] ?? NULL) === NULL || $premier[$page] !== ($second[$page] ?? NULL))
    {
        $instables[] = $page;
    }
}

if ($instables)
{
    printf("  %d page(s) INSTABLE(S), écartée(s) : %s\n", count($instables), implode(', ', $instables));
    $pages = array_values(array_diff($pages, $instables));
}
else
{
    echo '  les '.count($pages)." page(s) sont stables.\n";
}

if (!$pages)
{
    nf_refus("aucune page stable : une empreinte qui bouge toute seule rendrait un verdict aléatoire — mieux vaut ne rien dire");
}

if ($o['stabilite'])
{
    $instables ? nf_echec(count($instables).' page(s) instable(s)') : nf_ok('les '.count($pages).' page(s) sont mesurables');
}

echo "\n";

// ── Référence ───────────────────────────────────────────────────────────────
$presence  = [];
$reference = empreintes($pages, $serveur->base, $presence);
$muettes   = array_keys(array_filter($reference, static fn ($e): bool => $e === NULL));

if ($muettes)
{
    nf_refus('pages muettes (la sonde n\'a rien rendu) : '.implode(', ', $muettes)." — une page muette n'est pas une page sans changement : elle n'a pas été mesurée");
}

printf("Référence relevée sur %d page(s).\n", count($reference));

/** Vrai si retirer CES `!important`-là ne change rien nulle part. */
$inerte = static function (array $retirer) use ($feuille, $original, $positions, $pages, $serveur, $reference): bool {
    file_put_contents($feuille, sans_important($original, $positions, $retirer));

    // Le serveur intégré relit la feuille sur le disque à chaque requête, et chaque rendu a son
    // propre profil : aucun cache ne survit d'une mesure à l'autre.
    $vu = empreintes($pages, $serveur->base);

    file_put_contents($feuille, $original);

    foreach ($reference as $page => $empreinte)
    {
        if (($vu[$page] ?? NULL) !== $empreinte)
        {
            return FALSE;
        }
    }

    return TRUE;
};

// ── Tout d'un coup : le cas le plus fréquent est « tout compte » ou « rien ne compte » ──
$tous = $mesurables;

echo "\nÉpreuve : on retire les ".count($tous)." mesurables d'un coup…\n";

$rien_ne_bouge = $inerte($tous);

// Quels sélecteurs ont été SERVIS au moins une fois : « 1 » quelque part l'emporte, « ? » (moteur
// qui refuse le sélecteur) l'emporte sur « 0 ». Un sélecteur jamais servi ne PEUT pas changer le
// rendu : son inertie ne prouve rien, elle constate que la page ne l'exerce pas.
$servi = [];

foreach ($tous as $rang => $i)
{
    $etat = '0';

    foreach ($presence as $chaine)
    {
        $c = $chaine[$rang] ?? '0';

        if ($c === '1')
        {
            $etat = '1';
            break;
        }

        if ($c === '?')
        {
            $etat = '?';
        }
    }

    $servi[$i] = $etat;
}

$jamais_servis = array_values(array_filter($tous, static fn (int $i): bool => $servi[$i] !== '1'));

if ($jamais_servis)
{
    printf("%d sélecteur(s) ne trouvent AUCUN élément sur les pages mesurées.\n", count($jamais_servis));
}

if ($rien_ne_bouge)
{
    $retirables = array_values(array_diff($tous, $jamais_servis));

    @unlink($fichier_sonde);

    if (!$retirables)
    {
        nf_ok(sprintf('aucun des %d `!important` mesurables de %s n\'est servi sur ces %d page(s) : rien de prouvé, élargir --pages=',
            count($tous), $relatif, count($pages)));
    }

    echo "\nSans effet, et le sélecteur ÉTAIT servi — retirables :\n";

    foreach ($retirables as $i)
    {
        printf("  %s:%-5d %s\n", $relatif, $ligne($positions[$i]), mb_strimwidth($selecteurs[$i], 0, 60, '…'));
    }

    nf_echec(sprintf('%d `!important` de %s sont servis et ne changent rien sur les %d page(s) mesurée(s) : retirables',
        count($retirables), $relatif, count($pages)));
}

echo "Au moins un compte. Dichotomie…\n";

// Le plus petit ensemble dont le retrait change quelque chose : on descend l'arbre en deux moitiés,
// et retirer un sous-ensemble inerte ne coûte rien.
$comptent = [];
$explorer = static function (array $lot) use (&$explorer, &$comptent, $inerte): void {
    if (!$lot || $inerte($lot))
    {
        return;
    }

    if (count($lot) === 1)
    {
        $comptent[] = $lot[0];

        return;
    }

    $moitie = (int) (count($lot) / 2);
    $explorer(array_slice($lot, 0, $moitie));
    $explorer(array_slice($lot, $moitie));
};

$explorer($tous);

$inutiles   = array_values(array_diff($tous, $comptent));
$retirables = array_values(array_diff($inutiles, $jamais_servis));
$non_exerces = array_values(array_intersect($inutiles, $jamais_servis));

echo "\n";
printf("%d `!important` CHANGENT le rendu, %d ne changent rien.\n\n", count($comptent), count($inutiles));

if ($retirables)
{
    echo "Sans effet ALORS QUE le sélecteur était servi — retirables :\n";

    foreach ($retirables as $i)
    {
        $extrait = explode("\n", substr($original, max(0, $positions[$i] - 90), min(90, $positions[$i])));
        printf("  %s:%-5d %s !important\n", $relatif, $ligne($positions[$i]), trim(end($extrait)));
    }

    echo "\n« Sans effet sur les pages mesurées » n'est pas « sans effet » : une règle peut ne compter\n";
    echo "que sur un écran absent de la liste. Élargir avec --pages= avant de retirer.\n\n";
}

if ($non_exerces)
{
    echo "Sans effet, mais le sélecteur n'apparaît sur AUCUNE page mesurée — non conclu :\n";

    foreach ($non_exerces as $i)
    {
        printf("  %s:%-5d %s\n", $relatif, $ligne($positions[$i]), mb_strimwidth($selecteurs[$i], 0, 60, '…'));
    }

    echo "\nLeur inertie ne prouve rien : la page ne les exerce pas. Élargir --pages=.\n\n";
}

if ($comptent)
{
    echo "Indispensables — les retirer change l'affichage :\n";

    foreach ($comptent as $i)
    {
        printf("  %s:%-5d\n", $relatif, $ligne($positions[$i]));
    }
}

@unlink($fichier_sonde);

$appoint = ($non_exerces ? ', '.count($non_exerces).' non exercé(s)' : '')
    .($ecartes ? ', '.count($ecartes).' hors de portée' : '');

if ($retirables)
{
    nf_echec(sprintf('%d `!important` servis et sans effet sur les pages mesurées, %d indispensables%s',
        count($retirables), count($comptent), $appoint));
}

nf_ok(sprintf('les %d `!important` mesurés de %s changent tous le rendu%s', count($comptent), $relatif, $appoint));
