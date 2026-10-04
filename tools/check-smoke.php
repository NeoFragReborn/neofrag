<?php
declare(strict_types=1);

/**
 * check-smoke — frappe les flux critiques d'un site qui tourne, et échoue au moindre 5xx.
 *
 * Famille : cible
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * C'est le filet RUNTIME, qui complète PHPStan et PHPUnit : il attrape ce qui n'est visible qu'à
 * l'exécution — fatal de rendu, 500 sur un POST, 503 de maintenance. Il frappe les pages publiques,
 * les fichiers racine, les modales AJAX, la recherche, et le flux « mot de passe perdu » posté DEUX
 * fois : la seconde requête relit l'état de session écrit par la première, le scénario exact qui
 * révélait un 500 (serialized/DateTime via anti_flood). Donner `--email` d'un membre EXISTANT exerce
 * anti_flood ; sinon le chemin « e-mail inconnu » est couvert.
 *
 * Deux règles de jugement : un endpoint est OK tant qu'il ne renvoie pas 5xx ; les adresses qu'un
 * thème expose en lien (`user/login`…) et la recherche doivent en plus ne PAS répondre 404 — c'est
 * ainsi que `user/login` a pu 404 en production avec une CI verte. Et les fichiers racine doivent
 * rendre leur vrai type de contenu : le 2026-09-20, `/sitemap.xml` rendait du JSON en 200.
 *
 * Usage
 * -----
 *   php tools/check-smoke.php http://localhost:8080 --email=<membre existant>
 *   php tools/check-smoke.php https://neofrag-reborn.xyz
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/serveur.php';

[$o, $reste] = nf_options(['email' => '']);

$base   = rtrim($reste[0] ?? 'http://localhost:8080', '/');
$cookie = tempnam(sys_get_temp_dir(), 'nfsmoke');
$agent  = 'Mozilla/5.0 (NeoFrag-SmokeTest) Firefox/151.0'; // UA navigateur : is_crawler() sauterait la session/CSRF

register_shutdown_function(static function () use ($cookie): void { @unlink($cookie); });

$failures = [];
$checks   = 0;

/** Requête HTTP avec bocal à cookies (le flux lost-password dépend de la session). Retourne [code, body, type]. */
function http(string $method, string $url, ?array $post = NULL, bool $follow = FALSE): array
{
    global $cookie, $agent;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_USERAGENT      => $agent,
        CURLOPT_COOKIEJAR      => $cookie,
        CURLOPT_COOKIEFILE     => $cookie,
        CURLOPT_HTTPHEADER     => ['X-Requested-With: XMLHttpRequest'],
    ]);

    if ($method === 'POST')
    {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post ?? []));
    }

    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    return [$code, $body, $type];
}

/** Un endpoint est OK tant qu'il ne renvoie pas 5xx (302/404/200 sont acceptables pour un smoke). */
function check(string $label, string $method, string $url, ?array $post = NULL): array
{
    global $failures, $checks;

    $checks++;
    [$code, $body] = http($method, $url, $post);
    $ok = $code < 500 && $code !== 0;
    printf("  [%s] %-44s → %d\n", $ok ? 'OK ' : 'KO ', $label, $code);

    if (!$ok)
    {
        $failures[] = "$label ($method $url) → HTTP $code";
    }

    return [$code, $body];
}

/** Comme check(), mais un 4xx est AUSSI un échec : réservé aux adresses qu'un thème expose en lien. */
function check_found(string $label, string $method, string $url): array
{
    global $failures, $checks;

    $checks++;
    [$code, $body] = http($method, $url);
    $ok = $code > 0 && $code < 400;
    printf("  [%s] %-44s → %d\n", $ok ? 'OK ' : 'KO ', $label, $code);

    if (!$ok)
    {
        $failures[] = "$label ($method $url) → HTTP $code (attendu 2xx/3xx)";
    }

    return [$code, $body];
}

echo "Smoke test NeoFrag Reborn — base = $base\n\n";

/*
 * 1) Les fichiers RACINE que demandent robots et navigateurs, SANS préfixe de langue. Chacun doit
 * rendre son vrai type de contenu : le 2026-09-20, /sitemap.xml et /robots.txt rendaient
 * `{"redirect":"\/fr\/…"}` en application/json avec un code 200, et Google recevait du JSON à la
 * place du plan du site. Un corps qui commence par `{"redirect"` est le symptôme exact du défaut.
 */
echo "1) Pages publiques (pas de 5xx)\n";

foreach ([
    '/robots.txt'  => ['text/plain'],
    '/humans.txt'  => ['text/plain'],
    '/sitemap.xml' => ['text/xml', 'application/xml'],
    '/favicon.ico' => ['image/'],
] as $chemin => $types)
{
    $checks++;
    [$code, $corps, $type] = http('GET', $base.$chemin, NULL, TRUE);

    $ok = $code === 200 && array_filter($types, static fn (string $t): bool => str_starts_with($type, $t));

    if ($ok && str_starts_with(ltrim($corps), '{"redirect"'))
    {
        $ok    = FALSE;
        $type .= ' — corps de redirection JSON';
    }

    printf("  [%s] %-44s → %d %s\n", $ok ? 'OK ' : 'KO ', 'racine '.$chemin, $code, $type);

    if (!$ok)
    {
        $failures[] = "racine $chemin (GET $base$chemin, redirections suivies) → HTTP $code $type (attendu 200 ".implode(' ou ', $types).')';
    }
}

/*
 * Une adresse qui n'existe pas doit rendre 404 — y compris en `.json`, `.xml` ou `.txt` : `Url::ajax()`
 * tenait ces extensions pour des requêtes AJAX, et toute adresse inconnue rendait 200.
 * C'est le code FINAL qui compte, redirections suivies ; un corps de redirection JSON est refusé aussi.
 */
$marque = 'nf-inconnu-'.bin2hex(random_bytes(4));

foreach (["/$marque.json", "/$marque.xml", "/$marque.txt", "/update/$marque.json", "/fr/$marque.json", "/$marque"] as $chemin)
{
    $checks++;
    [$code, $corps] = http('GET', $base.$chemin, NULL, TRUE);

    $ok     = $code === 404;
    $detail = (string) $code;

    if ($ok && str_starts_with(ltrim($corps), '{"redirect"'))
    {
        $ok      = FALSE;
        $detail .= ' — corps de redirection JSON';
    }

    printf("  [%s] %-44s → %s\n", $ok ? 'OK ' : 'KO ', 'inconnue '.$chemin, $detail);

    if (!$ok)
    {
        $failures[] = "adresse inconnue $chemin (GET $base$chemin, redirections suivies) → HTTP $detail (attendu 404).";
    }
}

foreach ([
    'home'         => '/fr',
    'news'         => '/fr/news',
    'forum'        => '/fr/forum',
    'gallery'      => '/fr/gallery',
    'members'      => '/fr/members',
    'sitemap'      => '/sitemap.xml',
    // Les flux RSS sont servis par le module `feeds`, PAS par /news/rss (route inexistante).
    'rss-news'     => '/fr/feeds/news',
    'rss-articles' => '/fr/feeds/articles',
] as $label => $path)
{
    check($label, 'GET', $base.$path);
}

echo "\n1bis) Points d'entrée liés par les thèmes (ne doivent PAS 404)\n";

foreach (['login-page' => '/fr/user/login', 'registration-page' => '/fr/user/registration', 'logout-page' => '/fr/user/logout'] as $label => $path)
{
    check_found($label, 'GET', $base.$path);
}

echo "\n2) Modales AJAX (rendu form)\n";

foreach (['login' => '/fr/ajax/user/login', 'lost-password' => '/fr/ajax/user/lost-password'] as $label => $path)
{
    check($label, 'GET', $base.$path);
}

echo "\n2bis) Recherche instantanée (ajax/search/suggest → JSON array)\n";
[, $suggest_body] = check('search-suggest', 'GET', $base.'/fr/ajax/search/suggest?q=ne');
$checks++;

if (is_array(json_decode($suggest_body, TRUE)))
{
    printf("  [%s] %-44s → JSON array\n", 'OK ', 'search-suggest JSON');
}
else
{
    printf("  [%s] %-44s → réponse non-JSON\n", 'KO ', 'search-suggest JSON');
    $failures[] = 'search-suggest: réponse non-JSON';
}

/*
 * 2ter) La recherche sur des mots COURANTS. Le défaut figé : `highlight()` faisait
 * `substr($t, strpos($t, '<mark>'))`, et `strpos` rend FALSE dès que le mot ne figure pas dans le
 * champ AFFICHÉ — atteint sans effort quand la requête l'a trouvé dans une autre colonne, ou sans
 * les accents. La TypeError qui suivait donnait une page 404 qui s'affichait PARFAITEMENT. Des mots
 * de deux lettres, exprès : ce sont ceux qui se retrouvent dans un titre sans être dans le corps.
 */
echo "\n2ter) Recherche en page entière sur des mots COURANTS (ne doivent PAS 404)\n";

foreach (['le', 'ci', 'de', 'la'] as $mot)
{
    check_found('search-q='.$mot, 'GET', $base.'/fr/search?q='.rawurlencode($mot));
}

echo "\n3) Flux lost-password POST ×2 (régression serialized/DateTime via anti_flood)\n";

/** Récupère un token CSRF '_' FRAIS (le token form2 est à usage unique → re-GET avant chaque POST). */
function lost_password_token(string $base): ?string
{
    [, $form] = http('GET', $base.'/fr/ajax/user/lost-password');
    // La modale AJAX revient en JSON → le HTML a ses guillemets échappés (name=\"_\"). On dé-échappe.
    $form = str_replace('\\"', '"', $form);

    if (preg_match('/name="_"\s+value="([^"]+)"/', $form, $m) || preg_match('/value="([^"]+)"\s+name="_"/', $form, $m))
    {
        return $m[1];
    }

    return NULL;
}

$mail = $o['email'] !== '' ? $o['email'] : 'smoke-unknown@example.invalid';

foreach ([1, 2] as $i)
{
    $token = lost_password_token($base);

    if ($token === NULL)
    {
        echo "  [!! ] token CSRF '_' introuvable (vérifier le rendu form2)\n";
        $failures[] = 'lost-password: token CSRF introuvable';
        break;
    }

    // Le POST #2 relit l'état de session écrit par le #1 (anti_flood) — le cas exact qui fatalait.
    check("lost-password POST #$i", 'POST', $base.'/fr/ajax/user/lost-password', ['_' => $token, 'email' => $mail]);
}

if ($o['email'] === '')
{
    echo "  (note: sans --email=<membre existant>, anti_flood n'est pas atteint ; le POST est tout de même couvert)\n";
}

echo "\n";

if ($failures)
{
    foreach ($failures as $f)
    {
        echo "  ✗ $f\n";
    }

    nf_echec(count($failures)." / $checks contrôle(s) HTTP en erreur");
}

nf_ok("$checks contrôle(s) HTTP, aucun 5xx");
