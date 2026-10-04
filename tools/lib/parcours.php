<?php
declare(strict_types=1);

/**
 * parcours — suivre les liens internes d'un site servi, sans jamais ouvrir une adresse qui agit.
 *
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * `check-liens` parcourait le site pour y trouver les liens morts. Le 2026-09-22, `check-mise-en-page`
 * a eu besoin du même parcours pour savoir QUELLES pages rendre dans chaque thème et à chaque
 * largeur. Une seule façon de parcourir, et surtout une seule liste des adresses qu'on n'ouvre
 * jamais : un verbe ajouté ici protège les deux outils à la fois.
 *
 * Usage
 * -----
 *   $p = nf_parcourir_site($base, ['/fr', '/fr/admin'], 1000);
 *   $p['html']      liste des chemins qui ont rendu une page HTML du site (200)
 *   $p['casses']    liens internes morts : chemin, code, page qui le portait, raison
 *   $p['restantes'] adresses connues mais non ouvertes, faute de budget
 */

require_once __DIR__.'/outil.php';
require_once __DIR__.'/serveur.php';

/**
 * Les adresses qui AGISSENT : jamais ouvertes. Une seule visite suffirait à casser l'installation
 * qu'on contrôle — surtout avec une session d'administrateur.
 */
const NF_ADRESSES_QUI_AGISSENT = '#/(delete|supprimer|disable|enable|toggle|purge|reset|logout|uninstall|install|clone|move|send|cancel|restore|preview|export|totp-reset|deconnexion'
    // Ajoutés après coup : ces verbes-là MODIFIENT aussi, et `check-liens` les ouvrait. Il changeait
    // donc l'état des messages du livre d'or qu'il était censé se contenter d'inspecter.
    .'|approve|approuver|reject|rejeter|validate|valider|refuse|refuser|ban|unban|publish|publier'
    .'|unpublish|depublier|lock|unlock|pin|unpin|close|open|mark|read|unread|resend|retry|flush'
    .')(/|\?|$)#i';

/**
 * @param  list<string> $departs les chemins d'où partir
 * @param  list<string> $ignorer préfixes servis par une AUTRE installation (la démonstration sous `/demo`)
 * @return array{html: list<string>, casses: list<array{chemin: string, code: int, depuis: string, raison: string}>, ouverts: int, connues: int, restantes: int}
 */
function nf_parcourir_site(string $base, array $departs, int $max, array $ignorer = []): array
{
    $file    = [];
    $vus     = [];
    $origine = [];
    $html    = [];
    $casses  = [];
    $ouverts = 0;

    foreach ($departs as $d)
    {
        $file[]      = $d;
        $vus[$d]     = TRUE;
        $origine[$d] = '(départ)';
    }

    while ($file && $ouverts < $max)
    {
        $chemin  = array_shift($file);
        $reponse = nf_http($base.$chemin);
        $ouverts++;

        // Une redirection a pu nous emmener HORS du site (un lien sortant du module `links`, par
        // exemple). On ne suit pas les liens d'une page étrangère : ils sont relatifs à SON domaine.
        $hors_site = !str_starts_with($reponse['arrivee'], $base) && preg_match('#^https?://#i', $reponse['arrivee']);

        if ($reponse['code'] !== 200)
        {
            // Sortie du site : la redirection interne a FAIT SON TRAVAIL, et le code rendu appartient
            // au site d'en face (`downloads/go/1` renvoyait vers example.com).
            if (!$hors_site)
            {
                $casses[] = ['chemin' => $chemin, 'code' => $reponse['code'], 'depuis' => $origine[$chemin], 'raison' => $reponse['raison']];
            }

            continue;
        }

        $corps = $reponse['corps'];

        // On ne suit les liens que des pages HTML, et seulement si elles sont bien du site.
        if ($hors_site || (!str_contains($corps, '<html') && !str_contains($corps, '<body')))
        {
            continue;
        }

        $html[] = $chemin;

        // Délimiteur `~` et non `#` : la classe de caractères contient un `#` (on coupe l'ancre).
        preg_match_all('~<a\b[^>]+href="([^"#]+)~i', $corps, $trouves);

        foreach (array_unique($trouves[1]) as $brut)
        {
            $cible = html_entity_decode($brut, ENT_QUOTES, 'UTF-8');

            if (preg_match('#^(https?:)?//#i', $cible) || preg_match('#^(mailto|tel|javascript|data):#i', $cible))
            {
                continue;   // hors du site
            }

            if ($cible === '' || $cible[0] !== '/')
            {
                continue;   // relatif : ambigu, on ne devine pas
            }

            $cible = strtok($cible, '?');

            if ($cible === FALSE || isset($vus[$cible]) || preg_match(NF_ADRESSES_QUI_AGISSENT, $cible))
            {
                continue;
            }

            foreach ($ignorer as $prefixe)
            {
                if (str_starts_with($cible, $prefixe))
                {
                    continue 2;
                }
            }

            $vus[$cible]     = TRUE;
            $origine[$cible] = $chemin;
            $file[]          = $cible;
        }
    }

    return ['html' => $html, 'casses' => $casses, 'ouverts' => $ouverts, 'connues' => count($vus), 'restantes' => count($file)];
}
