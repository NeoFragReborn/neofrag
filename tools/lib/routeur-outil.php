<?php
/*
 * routeur-outil — le routeur du serveur intégré quand c'est un OUTIL qui sert le site.
 *
 * Diffusion : publique
 *
 * Il tourne en SAPI `cli-server` (c'est `php -S` qui l'exécute), jamais servi par un vrai serveur
 * web : on refuse tout autre SAPI, comme `tools/router-builtin.php`.
 *
 * Il fait ce que treize outils faisaient chacun avec leur routeur écrit à coups de chaînes :
 * poser la session d'administrateur, écarter le bandeau cookies, forcer le mode clair ou sombre,
 * et INJECTER une sonde JavaScript avec le nonce de la réponse — la politique de sécurité du site
 * interdit tout script inline sans nonce, et on réutilise celui que la page vient d'émettre plutôt
 * que d'affaiblir la politique pour pouvoir mesurer. Puis il délègue à `router-builtin.php`.
 *
 * Tout est piloté par les variables d'environnement NF_OUTIL_* décrites dans `serveur.php`.
 */

if (PHP_SAPI !== 'cli-server')
{
    http_response_code(404);
    exit;
}

if ($session = getenv('NF_OUTIL_SESSION'))
{
    $_COOKIE['session'] = $_COOKIE['session_https'] = $session;
}

if ($consent = getenv('NF_OUTIL_CONSENT'))
{
    $_COOKIE['nf_consent'] = $consent;
}

$nf_theme    = (string) getenv('NF_OUTIL_THEME');
$nf_sonde    = (string) getenv('NF_OUTIL_SONDE');
$nf_sonde_ou = getenv('NF_OUTIL_SONDE_OU') ?: 'body';

if ($nf_theme !== '' || ($nf_sonde !== '' && is_file($nf_sonde)))
{
    ob_start(static function (string $html) use ($nf_theme, $nf_sonde, $nf_sonde_ou): string {
        if (stripos($html, '</body>') === FALSE)
        {
            return $html;
        }

        $nonce  = preg_match('/<script[^>]+nonce="([^"]+)"/i', $html, $t) ? ' nonce="'.$t[1].'"' : '';

        if ($nf_theme !== '')
        {
            /*
             * Chaque thème garde le choix clair/sombre du visiteur dans SA PROPRE clé de
             * localStorage (`nf-forge-theme`, `nf-admin-theme`…) et REMET `data-theme` à sa valeur au
             * chargement. N'écrire que la clé générique laissait le script du thème écraser
             * l'attribut : toutes les comparaisons clair/sombre faites ainsi comparaient le thème à
             * lui-même (2026-09-16). On écrit donc la clé de chaque thème présent sur le disque.
             */
            $cles = ['nf-theme', 'nf-admin-theme'];

            foreach (glob(dirname(__DIR__, 2).'/themes/*', GLOB_ONLYDIR) ?: [] as $dossier)
            {
                $cles[] = 'nf-'.basename($dossier).'-theme';
            }

            $js = 'try{';

            foreach ($cles as $cle)
            {
                $js .= 'localStorage.setItem('.json_encode($cle).','.json_encode($nf_theme).');';
            }

            $js .= '}catch(e){}'
                .'document.documentElement.setAttribute("data-theme",'.json_encode($nf_theme).');'
                .'document.documentElement.setAttribute("data-bs-theme",'.json_encode($nf_theme).');';

            /*
             * Figer ce qui bouge : le carrousel avance tout seul, et une capture prise pendant la
             * transition attrape une diapositive à zéro pixel de large — la page a l'air CASSÉE alors
             * qu'elle est en mouvement (faux défaut signalé sur `forge` le 2026-09-16). Une mesure doit
             * montrer un état, pas un instant.
             */
            $tete = '<script'.$nonce.'>'.$js.'</script>'
                .'<style'.$nonce.'>*, *::before, *::after { animation-play-state: paused !important;'
                .' animation-duration: 0s !important; transition-duration: 0s !important; }</style>';

            $html = str_ireplace('data-bs-ride="carousel"', 'data-bs-ride="false"', $html);

            if (($i = stripos($html, '</head>')) !== FALSE)
            {
                $html = substr($html, 0, $i).$tete.substr($html, $i);
            }
        }

        if ($nf_sonde !== '' && is_file($nf_sonde))
        {
            $balise = '<script'.$nonce.'>'.file_get_contents($nf_sonde).'</script>';

            if ($nf_sonde_ou === 'head' && preg_match('/<head[^>]*>/i', $html, $tete, PREG_OFFSET_CAPTURE))
            {
                // En tête, AVANT le premier script : la seule façon d'entendre une erreur levée au chargement.
                $i    = $tete[0][1] + strlen($tete[0][0]);
                $html = substr($html, 0, $i).$balise.substr($html, $i);
            }
            else
            {
                $i    = strripos($html, '</body>');
                $html = substr($html, 0, $i).$balise.substr($html, $i);
            }
        }

        return $html;
    });
}

$nf_resultat = require dirname(__DIR__).'/router-builtin.php';

if (ob_get_level() > 0)
{
    ob_end_flush();
}

return $nf_resultat;
