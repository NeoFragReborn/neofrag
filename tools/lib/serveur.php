<?php
declare(strict_types=1);

/**
 * serveur — servir le site avec le serveur intégré de PHP, et lui parler en HTTP.
 *
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Quatorze outils lançaient leur `php -S`, chacun avec ses trente lignes : refuser un port occupé
 * (sinon on interroge le serveur d'un autre et le rapport est faux — 38 faux échecs un jour), lancer
 * par `exec` (sinon `proc_terminate` tue le shell et le serveur survit — onze fantômes trouvés le
 * 2026-09-20), attendre qu'il réponde, l'arrêter à la fin. Treize écrivaient un routeur temporaire
 * à coups de chaînes concaténées pour poser la session et injecter une sonde. Tout cela vit ici,
 * et le routeur est un vrai fichier : `routeur-outil.php`, piloté par des variables d'environnement.
 *
 * Usage
 * -----
 *   $serveur = nf_serveur(nf_port($o['port']), ['NF_OUTIL_SESSION' => $session]);
 *   $reponse = nf_http($serveur->base.'/fr/admin');           // ['code' => 200, 'corps' => …, …]
 *   … le serveur s'arrête tout seul à la fin de l'outil.
 *
 *   $bocal = new NfBocal();                                   // les cookies, d'une requête à l'autre
 *   nf_http($serveur->base.'/', ['bocal' => $bocal]);
 *
 * Variables lues par le routeur (toutes facultatives) :
 *   NF_OUTIL_SESSION   identifiant de session à poser en cookie (session, session_https et __Host-session)
 *   NF_OUTIL_CONSENT   valeur du cookie `nf_consent` (`essentials` écarte le bandeau cookies)
 *   NF_OUTIL_THEME     `light` ou `dark` : force le mode dans TOUTES les clés de thème du
 *                      localStorage et fige animations, transitions et carrousel
 *   NF_OUTIL_FIGER     `1` : fige animations, transitions et carrousel SANS forcer le mode
 *   NF_OUTIL_SONDE     chemin d'un fichier JS injecté avec le nonce de la réponse
 *   NF_OUTIL_SONDE_OU  `head` (en tête de document, avant le premier script) ou `body` (défaut,
 *                      juste avant `</body>`, une fois la page rendue)
 */

require_once __DIR__.'/outil.php';

/** Un agent de navigateur : le CMS traite « curl » et « PHP » comme des robots et n'ouvre pas leur session. */
const NF_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36';

final class NfServeur
{
    /** @var resource|null */
    private $processus;

    public function __construct(public readonly string $base, public readonly int $port, public readonly string $journal, $processus)
    {
        $this->processus = $processus;
    }

    public function arreter(): void
    {
        if (!is_resource($this->processus))
        {
            return;
        }

        $statut = @proc_get_status($this->processus);

        if (!empty($statut['pid']) && stripos(PHP_OS, 'WIN') === 0)
        {
            @exec('taskkill /F /T /PID '.(int) $statut['pid'].' 2>NUL');
        }
        else if (!empty($statut['pid']))
        {
            /*
             * Les OUVRIERS d'abord. Lancé avec `PHP_CLI_SERVER_WORKERS`, le serveur intégré forke des
             * processus enfants qui écoutent le même port ; tuer le parent seul les laissait vivre, et
             * le serveur suivant trouvait son port occupé (check-mise-en-page, 2026-09-22).
             */
            @exec('pkill -TERM -P '.(int) $statut['pid'].' 2>/dev/null');
        }

        @proc_terminate($this->processus);
        @proc_close($this->processus);
        $this->processus = NULL;

        // Rendre la main quand le port est VRAIMENT libre, pas quand on a demandé l'arrêt.
        for ($i = 0; $i < 30; $i++)
        {
            if (!($x = @fsockopen('127.0.0.1', $this->port, $errno, $errstr, 0.1)))
            {
                return;
            }

            fclose($x);
            usleep(100000);
        }
    }
}

/**
 * Un bocal à cookies : ce qu'un navigateur garde d'une réponse à l'autre.
 *
 * Sans lui, chaque requête de `nf_http()` arrive sans session : l'assistant d'installation, qui range
 * en session son jeton anti-falsification et le profil choisi, recommencerait à chaque page, et une
 * connexion par le vrai formulaire serait oubliée à la requête suivante. Un seul site par bocal : le
 * domaine et le chemin des cookies ne sont pas lus.
 */
final class NfBocal
{
    /** @var array<string, string> */
    public array $cookies = [];

    /** L'en-tête `Cookie:` à envoyer, ou NULL si le bocal est vide. */
    public function entete(): ?string
    {
        if (!$this->cookies)
        {
            return NULL;
        }

        $paires = [];

        foreach ($this->cookies as $nom => $valeur)
        {
            $paires[] = $nom.'='.$valeur;
        }

        return 'Cookie: '.implode('; ', $paires);
    }

    /**
     * Range les `Set-Cookie` d'une réponse ; un cookie effacé (valeur vide, `deleted`, expiré) sort.
     *
     * @param list<string> $entetes  les lignes brutes de la réponse
     */
    public function lire(array $entetes): void
    {
        foreach ($entetes as $ligne)
        {
            if (!preg_match('/^Set-Cookie:\s*([^=;\s]+)=([^;]*)/i', $ligne, $c))
            {
                continue;
            }

            $efface = $c[2] === '' || $c[2] === 'deleted' || preg_match('/;\s*max-age=0(?![0-9])/i', $ligne)
                || (preg_match('/;\s*expires=([^;]+)/i', $ligne, $e) && ($t = strtotime($e[1])) !== FALSE && $t < time());

            if ($efface)
            {
                unset($this->cookies[$c[1]]);
            }
            else
            {
                $this->cookies[$c[1]] = $c[2];
            }
        }
    }
}

/**
 * Lance le serveur intégré sur la racine du dépôt, avec le routeur des outils.
 *
 * Refuse de juger si le port est déjà occupé, attend que le serveur réponde, et l'arrête à la fin
 * de l'outil quoi qu'il arrive. `$racine` permet de servir un autre dossier (les épreuves JS).
 *
 * `$php` passe des options à PHP lui-même, avant `-S` : `['-n', '-d', 'extension=…']` lance un PHP
 * sans php.ini, avec les seules extensions données — c'est ainsi qu'on éprouve un hébergement auquel il
 * en manque une (`check-prerequis-absents`).
 *
 * @param array<string, string> $env  variables passées au routeur (voir l'en-tête)
 * @param list<string>          $php  options de PHP
 */
function nf_serveur(int $port, array $env = [], ?string $racine = NULL, ?string $routeur = NULL, array $php = []): NfServeur
{
    $options_php = implode('', array_map(static fn (string $a): string => ' '.escapeshellarg($a), $php));

    $racine  ??= nf_racine();
    $routeur ??= __DIR__.'/routeur-outil.php';

    // Une chaîne vide demande le serveur de fichiers nu de `php -S`, sans routeur : c'est ce que
    // veulent les épreuves JS, qui ne servent que des pages statiques.
    $routeur = $routeur === '' ? '' : ' '.escapeshellarg($routeur);

    if ($occupe = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.5))
    {
        fclose($occupe);
        nf_refus(sprintf("le port %d est déjà occupé — interroger le serveur d'un autre donnerait un rapport faux.\n"
            ."  Voir qui l'occupe : ss -lptn | grep :%d\n  Ou changer de port : php tools/%s.php --port=%d",
            $port, $port, nf_outil(), $port + 1));
    }

    $journal = nf_temp('serveur.log');
    @unlink($journal);

    // `exec` : sans lui, proc_open lance un shell qui lance php, et proc_terminate ne tue que le
    // shell. Windows n'a pas de shell intermédiaire ici, et arreter() y passe par taskkill.
    $prefixe = stripos(PHP_OS, 'WIN') === 0 ? '' : 'exec ';
    $variables = '';

    foreach ($env as $nom => $valeur)
    {
        $variables .= $nom.'='.escapeshellarg((string) $valeur).' ';
    }

    $commande = sprintf('%s%s%s%s -S 127.0.0.1:%d -t %s%s',
        $prefixe, $variables === '' ? '' : 'env '.$variables, escapeshellarg(PHP_BINARY), $options_php,
        $port, escapeshellarg($racine), $routeur);

    // Sous Windows, `env` n'existe pas : les variables passent par putenv() avant proc_open.
    if (stripos(PHP_OS, 'WIN') === 0)
    {
        foreach ($env as $nom => $valeur)
        {
            putenv($nom.'='.$valeur);
        }

        $commande = sprintf('%s%s -S 127.0.0.1:%d -t %s%s', escapeshellarg(PHP_BINARY), $options_php, $port,
            escapeshellarg($racine), $routeur);
    }

    $processus = proc_open($commande, [1 => ['file', $journal, 'a'], 2 => ['file', $journal, 'a']], $tuyaux, $racine);

    if (!is_resource($processus))
    {
        nf_refus('impossible de lancer le serveur intégré : '.$commande);
    }

    $serveur = new NfServeur(sprintf('http://127.0.0.1:%d', $port), $port, $journal, $processus);

    register_shutdown_function(static function () use ($serveur): void {
        $serveur->arreter();
    });

    for ($i = 0; $i < 50; $i++)
    {
        if ($x = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2))
        {
            fclose($x);

            return $serveur;
        }

        usleep(100000);
    }

    nf_refus(sprintf("le serveur intégré n'a pas répondu sur le port %d. Journal : %s", $port, $journal));
}

/**
 * Encode une adresse comme le fait un navigateur avant de l'envoyer.
 *
 * Un lien qui porte un espace ou un accent — `/fr/teams/1/Équipe principale` — fait échouer
 * `file_get_contents` ; le contrôle rapportait « HTTP 0 », un code nu qui masquait le vrai
 * verdict. Idempotent sur une adresse déjà encodée.
 */
function nf_encoder_adresse(string $url): string
{
    $parts = parse_url($url);

    if ($parts === FALSE || !isset($parts['path']))
    {
        return $url;
    }

    $chemin = implode('/', array_map(
        static fn (string $segment): string => rawurlencode(rawurldecode($segment)),
        explode('/', $parts['path'])
    ));

    return (isset($parts['scheme']) ? $parts['scheme'].'://' : '')
        .($parts['host'] ?? '')
        .(isset($parts['port']) ? ':'.$parts['port'] : '')
        .$chemin
        .(isset($parts['query']) ? '?'.$parts['query'] : '');
}

/**
 * Une requête HTTP, en se présentant comme un navigateur.
 *
 * Les redirections sont suivies À LA MAIN (jusqu'à `suivre` sauts) : avec `follow_location`, PHP
 * ne laisse pas voir l'adresse d'arrivée de façon fiable, et un contrôle a suivi les liens d'une
 * page ÉTRANGÈRE en croyant être resté sur le site. `suivre => 0` rend la réponse brute.
 *
 * Avec un `bocal`, les cookies reçus sont gardés et renvoyés, à chaque saut de redirection aussi —
 * c'est ainsi qu'un navigateur suit une connexion.
 *
 * Avec des `fichiers` (`['champ' => '/chemin/archive.zip']`), le POST part en `multipart/form-data`,
 * comme un formulaire d'envoi : c'est ainsi qu'on éprouve « Ajouter » un addon par son archive.
 *
 * @param array{post?: array<string, string|list<string>>|null, fichiers?: array<string, string>, ajax?: bool, suivre?: int, timeout?: int, agent?: string, entetes?: list<string>, bocal?: NfBocal|null} $options
 * @return array{code: int, corps: string, arrivee: string, raison: string, entetes: array<string, string>}
 *         `code` vaut 0 quand la requête n'a pas abouti, et `raison` dit alors pourquoi.
 */
function nf_http(string $url, array $options = []): array
{
    $post    = $options['post'] ?? NULL;
    $suivre  = $options['suivre'] ?? 5;
    $bocal   = $options['bocal'] ?? NULL;
    $arrivee = $url;
    $corps_post = $post !== NULL ? http_build_query($post) : '';
    $type_post  = 'application/x-www-form-urlencoded';

    if (!empty($options['fichiers']))
    {
        // Chaque champ du formulaire (tableaux compris, `a[b][]`), puis chaque fichier, en parties séparées.
        $limite = '----nf'.bin2hex(random_bytes(12));
        $parties = '';

        foreach (explode('&', $corps_post) as $paire)
        {
            if ($paire === '')
            {
                continue;
            }

            [$nom, $valeur] = array_map('urldecode', array_pad(explode('=', $paire, 2), 2, ''));
            $parties .= "--{$limite}\r\nContent-Disposition: form-data; name=\"{$nom}\"\r\n\r\n{$valeur}\r\n";
        }

        foreach ($options['fichiers'] as $champ => $chemin)
        {
            $parties .= "--{$limite}\r\nContent-Disposition: form-data; name=\"{$champ}\"; filename=\"".basename($chemin)."\"\r\n"
                ."Content-Type: application/zip\r\n\r\n".file_get_contents($chemin)."\r\n";
        }

        $corps_post = $parties."--{$limite}--\r\n";
        $type_post  = 'multipart/form-data; boundary='.$limite;
        $post     ??= [];
    }

    for ($saut = 0; $saut <= $suivre; $saut++)
    {
        $entetes = ['User-Agent: '.($options['agent'] ?? NF_AGENT)];

        if (!empty($options['ajax']))
        {
            $entetes[] = 'X-Requested-With: XMLHttpRequest';
        }

        if ($post !== NULL)
        {
            $entetes[] = 'Content-Type: '.$type_post;
        }

        foreach ($options['entetes'] ?? [] as $entete)
        {
            $entetes[] = $entete;
        }

        if ($bocal !== NULL && ($cookies = $bocal->entete()) !== NULL)
        {
            $entetes[] = $cookies;
        }

        $contexte = stream_context_create(['http' => [
            'method'          => $post !== NULL ? 'POST' : 'GET',
            'content'         => $post !== NULL ? $corps_post : '',
            'timeout'         => $options['timeout'] ?? 30,
            'ignore_errors'   => TRUE,
            'follow_location' => 0,
            'header'          => implode("\r\n", $entetes)."\r\n",
        ]]);

        $avant  = error_get_last();
        $corps  = @file_get_contents(nf_encoder_adresse($arrivee), FALSE, $contexte);
        $code   = 0;
        $vers   = '';
        $recus  = [];

        foreach ($http_response_header ?? [] as $entete)
        {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $entete, $t))
            {
                $code = (int) $t[1];
            }
            elseif (preg_match('#^([A-Za-z-]+):\s*(.*)$#', $entete, $t))
            {
                $recus[strtolower($t[1])] = trim($t[2]);
            }
        }

        $bocal?->lire($http_response_header ?? []);

        $vers = $recus['location'] ?? '';

        if ($code < 300 || $code >= 400 || $vers === '')
        {
            $apres  = error_get_last();
            $raison = ($code === 0 && $apres && $apres !== $avant)
                ? trim((string) preg_replace('#^file_get_contents\([^)]*\):\s*#', '', $apres['message']))
                : '';

            return ['code' => $code, 'corps' => (string) $corps, 'arrivee' => $arrivee, 'raison' => $raison, 'entetes' => $recus];
        }

        // Une redirection ne rejoue jamais le POST : c'est ainsi que font les navigateurs.
        $post    = NULL;
        $arrivee = str_starts_with($vers, 'http')
            ? $vers
            : preg_replace('#^(https?://[^/]+).*$#', '$1', $arrivee).'/'.ltrim($vers, '/');
    }

    /*
     * Le budget de redirections est épuisé : on rend la DERNIÈRE réponse, un 3xx avec son en-tête
     * Location dans `entetes` et sa cible dans `arrivee` — pas un code 0. Avec `suivre => 0`, un
     * 302 est une réponse parfaitement valable ; la première version rendait « pas de réponse » et
     * check-install-profiles comptait une erreur serveur sur `/` et `/admin`, qui redirigent.
     */
    return ['code' => $code, 'corps' => (string) $corps, 'arrivee' => $arrivee,
        'raison' => $suivre > 0 ? 'plus de '.$suivre.' redirections enchaînées' : 'redirection non suivie', 'entetes' => $recus];
}

/** Le statut HTTP d'une adresse, sans suivre les redirections : 0 si elle ne répond pas. */
function nf_statut(string $url): int
{
    return nf_http($url, ['suivre' => 0, 'timeout' => 20])['code'];
}

/**
 * Le premier formulaire d'une page qui contient le champ demandé, avec TOUS ses champs et leur
 * valeur courante, prêt à être reposté — comme un navigateur l'enverrait.
 *
 * Renvoyer tous les champs compte : un formulaire de réglages posté amputé écrirait des valeurs
 * vides, et une épreuve « réussirait » en cassant la page. Une case non cochée n'est pas envoyée ;
 * un `<textarea>` porte sa valeur dans son contenu ; un `<select>` envoie l'option sélectionnée,
 * à défaut la première qui porte une valeur (les listes du produit ouvrent sur une option vide).
 *
 * @return array<string, string>|null
 */
function nf_formulaire(string $html, string $champ_attendu): ?array
{
    if (!preg_match_all('#<form[^>]*>(.*?)</form>#is', $html, $formes, PREG_SET_ORDER))
    {
        return NULL;
    }

    foreach ($formes as $forme)
    {
        $corps = $forme[1];

        if (!str_contains($corps, $champ_attendu))
        {
            continue;
        }

        $champs = [];

        preg_match_all('#<input\b([^>]*)>#i', $corps, $entrees, PREG_SET_ORDER);

        foreach ($entrees as $balise)
        {
            if (!preg_match('/name="([^"]+)"/i', $balise[1], $n))
            {
                continue;
            }

            $type = preg_match('/type="([^"]+)"/i', $balise[1], $t) ? strtolower($t[1]) : 'text';

            if (in_array($type, ['submit', 'button', 'file', 'reset'], TRUE))
            {
                continue;
            }

            if (in_array($type, ['checkbox', 'radio'], TRUE) && !preg_match('/\bchecked\b/i', $balise[1]))
            {
                continue;
            }

            $champs[$n[1]] = preg_match('/value="([^"]*)"/i', $balise[1], $v) ? html_entity_decode($v[1], ENT_QUOTES, 'UTF-8') : '';
        }

        preg_match_all('#<textarea\b([^>]*)>(.*?)</textarea>#is', $corps, $zones, PREG_SET_ORDER);

        foreach ($zones as $zone)
        {
            if (preg_match('/name="([^"]+)"/i', $zone[1], $n))
            {
                $champs[$n[1]] = html_entity_decode($zone[2], ENT_QUOTES, 'UTF-8');
            }
        }

        preg_match_all('#<select\b([^>]*)>(.*?)</select>#is', $corps, $listes, PREG_SET_ORDER);

        foreach ($listes as $liste)
        {
            if (!preg_match('/name="([^"]+)"/i', $liste[1], $n))
            {
                continue;
            }

            $valeur = '';

            if (preg_match('#<option\b([^>]*\bselected\b[^>]*)>#i', $liste[2], $o)
                && preg_match('/value="([^"]*)"/i', $o[1], $v) && $v[1] !== '')
            {
                $valeur = html_entity_decode($v[1], ENT_QUOTES, 'UTF-8');
            }
            elseif (preg_match('#<option\b[^>]*\bvalue="([^"]+)"[^>]*>#i', $liste[2], $o))
            {
                $valeur = html_entity_decode($o[1], ENT_QUOTES, 'UTF-8');
            }

            $champs[$n[1]] = $valeur;
        }

        if ($champs)
        {
            return $champs;
        }
    }

    return NULL;
}

/**
 * Le HTML d'une réponse, qu'elle soit une page ou une modale AJAX : les modales du CMS reviennent
 * en JSON, leur balisage dans un champ `content`.
 */
function nf_balisage(string $reponse): string
{
    $json = json_decode($reponse, TRUE);

    return is_array($json) && isset($json['content']) && is_string($json['content']) ? $json['content'] : $reponse;
}
