<?php
declare(strict_types=1);

/**
 * navigateur — ouvrir une page dans un Chrome sans interface, et relire ce qu'une sonde y a écrit.
 *
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Six outils lançaient Chrome avec la même ligne de commande recopiée, et chacun avait ses propres
 * chemins de recherche du binaire. L'un d'eux a longtemps tenu pour un navigateur le SIMULACRE
 * `/usr/bin/chromium-browser` d'Ubuntu, qui existe, est exécutable, et ne lance rien — d'où un
 * diagnostic « la page n'a pas rendu de verdict » parfaitement faux. Le binaire est donc éprouvé
 * par `--version` avant d'être retenu.
 *
 * Chaque lancement laisse un dossier `.com.google.Chrome.*` dans le dossier temporaire, quel que soit
 * le profil demandé : 10 460 dossiers pour 1,3 Go le 2026-09-21, de quoi remplir le tmpfs et rendre
 * muets tous les outils qui écrivent. `nf_chrome_menage()` balaie.
 *
 * Usage
 * -----
 *   $dom     = nf_chrome_dom($url, ['largeur' => 390]);
 *   $verdict = nf_sonde_verdict($dom, 'nf-responsive-verdict');   // NULL si la sonde est restée muette
 */

require_once __DIR__.'/outil.php';

/**
 * Le premier Chromium utilisable : `NF_CHROME`, puis `NF_BROWSER`, puis les emplacements usuels,
 * puis le PATH. Refuse de juger s'il n'y en a aucun.
 */
function nf_chrome(): string
{
    static $chrome = NULL;

    if ($chrome !== NULL)
    {
        return $chrome;
    }

    foreach (['NF_CHROME', 'NF_BROWSER'] as $variable)
    {
        if (($env = getenv($variable)) && is_file($env) && nf_chrome_utilisable($env))
        {
            return $chrome = $env;
        }
    }

    $candidats = [
        '/usr/bin/google-chrome',
        '/usr/bin/google-chrome-stable',
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
        '/snap/bin/chromium',
        'C:\Program Files\Google\Chrome\Application\chrome.exe',
        'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
        'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
    ];

    foreach ($candidats as $c)
    {
        if (is_file($c) && nf_chrome_utilisable($c))
        {
            return $chrome = $c;
        }
    }

    foreach (['google-chrome', 'chromium', 'chromium-browser'] as $nom)
    {
        $chemin = trim((string) @shell_exec('command -v '.escapeshellarg($nom).' 2>/dev/null'));

        if ($chemin !== '' && is_file($chemin) && nf_chrome_utilisable($chemin))
        {
            return $chrome = $chemin;
        }
    }

    nf_refus("aucun navigateur sans interface trouvé. Renseigner NF_CHROME avec le chemin d'un Chromium\n"
        ."  (sous Ubuntu : snap install chromium, ou le .deb de Google Chrome — le paquet apt\n"
        ."  chromium-browser est un simulacre qui renvoie vers snap)");
}

/** Le binaire répond-il vraiment à `--version` ? */
function nf_chrome_utilisable(string $binaire): bool
{
    return (bool) preg_match('/\d+\.\d+\.\d+/', (string) @shell_exec(escapeshellarg($binaire).' --version 2>&1'));
}

/**
 * Charge une page et rend son DOM une fois les scripts exécutés.
 *
 * `--virtual-time-budget` laisse le temps aux feuilles et aux scripts de s'appliquer ; sans lui on
 * mesurerait une page pas encore mise en page.
 *
 * @param array{largeur?: int, hauteur?: int, budget?: int, profil?: string, echelle?: int} $options
 */
function nf_chrome_dom(string $url, array $options = []): string
{
    return (string) shell_exec(nf_chrome_commande($url, $options).' --dump-dom '.escapeshellarg($url).' 2>/dev/null');
}

/**
 * Capture une page en PNG ; vrai si le fichier a été écrit.
 *
 * Le navigateur annonce « moins d'animations » : une capture montre la page dans son état STABLE.
 * Sans cela, elle montre l'instant où tombe le budget de temps virtuel — le titre défilant de la
 * vitrine change toutes les trois secondes, et chaque capture à six secondes tombait en plein fondu,
 * une ligne vide au milieu du titre (2026-10-03).
 */
function nf_chrome_capture(string $url, string $fichier, array $options = []): bool
{
    @unlink($fichier);
    shell_exec(nf_chrome_commande($url, $options).' --force-prefers-reduced-motion --screenshot='.escapeshellarg($fichier).' '.escapeshellarg($url).' 2>/dev/null');

    return is_file($fichier) && filesize($fichier) > 0;
}

/**
 * La ligne de commande commune, sans l'action (`--dump-dom` ou `--screenshot`) ni l'adresse.
 *
 * Chrome est borné dans le temps par `timeout` (coreutils) : le temps virtuel ne s'écoule pas tant
 * qu'une requête reste en suspens, et une seule page qui ne finit pas de charger tenait l'épreuve
 * entière jusqu'à la limite de la CI — 10 minutes, sans dire quelle page (2026-10-02). Interrompu,
 * Chrome ne rend rien : la page compte comme muette, et l'appelant la nomme.
 */
function nf_chrome_commande(string $url, array $options): string
{
    static $timeout = NULL;

    $timeout ??= trim((string) @shell_exec('command -v timeout 2>/dev/null'));
    $profil    = nf_temp('profil-'.($options['profil'] ?? 'defaut'));
    $budget    = $options['budget'] ?? 6000;

    // `echelle` : les points par pixel de l'écran simulé. À 2, une capture de 1 280 px de large fait
    // 2 560 px réels — la page est mise en page à l'identique, mais une zone découpée puis agrandie
    // reste nette (capturer-apercus).
    return sprintf('%s%s --headless=new --disable-gpu --no-sandbox --hide-scrollbars --user-data-dir=%s'
        .' --window-size=%d,%d --virtual-time-budget=%d%s',
        $timeout !== '' ? escapeshellarg($timeout).' -k 5 '.(intdiv($budget, 1000) + 45).' ' : '',
        escapeshellarg(nf_chrome()), escapeshellarg($profil),
        $options['largeur'] ?? 1400, $options['hauteur'] ?? 900, $budget,
        isset($options['echelle']) ? ' --force-device-scale-factor='.(int) $options['echelle'] : '');
}

/**
 * Le verdict qu'une sonde a déposé dans le DOM : `<div id="…" data-verdict='{json}'>`.
 *
 * NULL quand la sonde est restée muette — et une page muette n'est PAS une page sans défaut : elle
 * n'a pas été mesurée. C'est à l'appelant de le compter et de refuser de conclure s'il y en a trop.
 */
function nf_sonde_verdict(string $dom, string $id): ?array
{
    if (!preg_match('/id="'.preg_quote($id, '/').'" data-verdict="([^"]*)"/', $dom, $t))
    {
        return NULL;
    }

    $verdict = json_decode(html_entity_decode($t[1], ENT_QUOTES, 'UTF-8'), TRUE);

    return is_array($verdict) ? $verdict : NULL;
}

/**
 * Balaie les dossiers de travail que Chrome sème dans le dossier temporaire, si plus aucun Chrome
 * ne tourne. Rend le nombre de dossiers supprimés.
 */
function nf_chrome_menage(): int
{
    $restes = array_merge(
        glob(sys_get_temp_dir().'/.com.google.Chrome.*') ?: [],
        glob(sys_get_temp_dir().'/com.google.Chrome.*') ?: []
    );

    if (!$restes)
    {
        return 0;
    }

    exec('pgrep -c chrome 2>/dev/null', $sortie);

    if ((int) ($sortie[0] ?? 0) > 0)
    {
        return 0;
    }

    foreach ($restes as $reste)
    {
        exec('rm -rf '.escapeshellarg($reste));
    }

    return count($restes);
}
