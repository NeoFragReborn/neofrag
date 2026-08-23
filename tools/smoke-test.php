<?php
declare(strict_types=1);
// Outil d'administration : jamais servi en HTTP (sinon maintenance/migrations/dumps
// seraient executables par n'importe qui si tools/ etait expose par erreur).
if (PHP_SAPI !== 'cli')
{
	http_response_code(404);
	exit;
}


/**
 * Smoke test des flux critiques — filet RUNTIME (complète PHPStan/PHPUnit).
 *
 * Frappe les endpoints publics + le flux lost-password (POST ×2) contre un site qui tourne, et
 * échoue (exit 1) au moindre 5xx. C'est ce qui aurait attrapé les bugs de cette session qui ne sont
 * visibles qu'à l'exécution : fatal de rendu, 500 sur un POST, 503 de maintenance, etc. La 2e requête
 * lost-password rejoue le cas qui révélait le fatal serialized/DateTime (état de session).
 *
 * Usage : php tools/smoke-test.php [base_url] [--email=user@exists.tld]
 *   base_url : défaut http://localhost:8080
 *   --email  : email d'un membre EXISTANT → exerce anti_flood (le chemin qui fatalait). Sinon, le POST
 *              est quand même testé (chemin « email inconnu »), sans déclencher anti_flood.
 *
 * Pensé pour la CI (stack docker) comme pour un check local.
 */

$base  = 'http://localhost:8080';
$email = '';
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--email=')) {
        $email = substr($arg, 8);
    } elseif (!str_starts_with($arg, '--')) {
        $base = rtrim($arg, '/');
    }
}

$cookie = tempnam(sys_get_temp_dir(), 'nfsmoke');
$UA     = 'Mozilla/5.0 (NeoFrag-SmokeTest) Firefox/151.0'; // UA navigateur : is_crawler() sauterait la session/CSRF.

$failures = [];
$checks   = 0;

/** Requête HTTP. Retourne [code, body]. */
function http(string $method, string $url, array $post = null): array
{
    global $cookie, $UA;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_USERAGENT      => $UA,
        CURLOPT_COOKIEJAR      => $cookie,
        CURLOPT_COOKIEFILE     => $cookie,
        CURLOPT_HTTPHEADER     => ['X-Requested-With: XMLHttpRequest'],
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post ?? []));
    }
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

/** Comme check(), mais un 4xx est AUSSI un échec. Réservé aux URLs qu'un thème expose en `href` :
 *  un lien d'en-tête cassé renvoie 404, donc passait pour OK avec la règle « pas de 5xx » seule
 *  (c'est ainsi que `user/login` et `user/registration` ont pu 404 en prod avec une CI verte). */
function check_found(string $label, string $method, string $url): array
{
    global $failures, $checks;
    $checks++;
    [$code, $body] = http($method, $url);
    $ok = $code > 0 && $code < 400;
    printf("  [%s] %-44s → %d\n", $ok ? 'OK ' : 'KO ', $label, $code);
    if (!$ok) {
        $failures[] = "$label ($method $url) → HTTP $code (attendu 2xx/3xx)";
    }
    return [$code, $body];
}

/** Un endpoint est OK tant qu'il ne renvoie pas 5xx (302/404/200 sont acceptables pour un smoke). */
function check(string $label, string $method, string $url, array $post = null): array
{
    global $failures, $checks;
    $checks++;
    [$code, $body] = http($method, $url, $post);
    $ok = $code < 500 && $code !== 0;
    printf("  [%s] %-44s → %d\n", $ok ? 'OK ' : 'KO ', $label, $code);
    if (!$ok) {
        $failures[] = "$label ($method $url) → HTTP $code";
    }
    return [$code, $body];
}

echo "Smoke test NeoFrag Reborn — base = $base\n\n";

echo "1) Pages publiques (pas de 5xx)\n";
foreach ([
    'home'         => '/fr',
    'news'         => '/fr/news',
    'forum'        => '/fr/forum',
    'gallery'      => '/fr/gallery',
    'members'      => '/fr/members',
    'sitemap'      => '/sitemap.xml',
    // Les flux RSS sont servis par le module `feeds` (/feeds/news, /feeds/articles), PAS par
    // /news/rss (route inexistante — l'ancienne URL renvoyait 404, masqué par la règle « pas de 5xx »).
    'rss-news'     => '/fr/feeds/news',
    'rss-articles' => '/fr/feeds/articles',
] as $label => $path) {
    check($label, 'GET', $base . $path);
}

echo "\n1bis) Points d'entrée liés par les thèmes (ne doivent PAS 404)\n";
foreach ([
    'login-page'        => '/fr/user/login',
    'registration-page' => '/fr/user/registration',
    'logout-page'       => '/fr/user/logout',
] as $label => $path) {
    check_found($label, 'GET', $base . $path);
}

echo "\n2) Modales AJAX (rendu form)\n";
foreach ([
    'login'         => '/fr/ajax/user/login',
    'lost-password' => '/fr/ajax/user/lost-password',
] as $label => $path) {
    check($label, 'GET', $base . $path);
}

echo "\n2bis) Recherche instantanée (ajax/search/suggest → JSON array)\n";
[, $suggest_body] = check('search-suggest', 'GET', $base . '/fr/ajax/search/suggest?q=ne');
$checks++;
if (is_array(json_decode($suggest_body, true))) {
    printf("  [%s] %-44s → JSON array\n", 'OK ', 'search-suggest JSON');
} else {
    printf("  [%s] %-44s → réponse non-JSON\n", 'KO ', 'search-suggest JSON');
    $failures[] = "search-suggest: réponse non-JSON";
}

echo "\n3) Flux lost-password POST ×2 (régression serialized/DateTime via anti_flood)\n";

/** Récupère un token CSRF '_' FRAIS (le token form2 est à usage unique → re-GET avant chaque POST). */
function lost_password_token(string $base): ?string
{
    [, $form] = http('GET', $base . '/fr/ajax/user/lost-password');
    // La modale AJAX revient en JSON → le HTML a ses guillemets échappés (name=\"_\"). On dé-échappe.
    $form = str_replace('\\"', '"', $form);
    if (preg_match('/name="_"\s+value="([^"]+)"/', $form, $m) || preg_match('/value="([^"]+)"\s+name="_"/', $form, $m)) {
        return $m[1];
    }
    return null;
}

$mail = $email !== '' ? $email : 'smoke-unknown@example.invalid';
foreach ([1, 2] as $i) {
    $token = lost_password_token($base);
    if ($token === null) {
        echo "  [!! ] token CSRF '_' introuvable (vérifier le rendu form2)\n";
        $failures[] = "lost-password: token CSRF introuvable";
        break;
    }
    // Le POST #2 relit l'état de session écrit par le #1 (anti_flood) — le cas exact qui fatalait.
    check("lost-password POST #$i", 'POST', $base . '/fr/ajax/user/lost-password', ['_' => $token, 'email' => $mail]);
}
if ($email === '') {
    echo "  (note: sans --email=<membre existant>, anti_flood n'est pas atteint ; le POST est tout de même couvert)\n";
}

@unlink($cookie);

echo "\n";
if ($failures) {
    echo "ÉCHEC — " . count($failures) . " / $checks check(s) en erreur :\n";
    foreach ($failures as $f) {
        echo "  ✗ $f\n";
    }
    exit(1);
}
echo "OK — $checks check(s), aucun 5xx.\n";
exit(0);
