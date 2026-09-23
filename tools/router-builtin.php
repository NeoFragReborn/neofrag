<?php
// Ce fichier est le routeur du serveur INTEGRE de PHP : il tourne donc en SAPI `cli-server`,
// et non `cli` comme les autres outils — sa garde ne peut pas etre la meme. Servi par un vrai
// serveur web (Apache sur un mutualise, ou nginx), il inclurait `index.php` depuis `tools/`,
// c'est-a-dire avec une racine fausse. On refuse tout ce qui n'est pas le serveur integre.
if (PHP_SAPI !== 'cli-server' && PHP_SAPI !== 'cli')
{
    http_response_code(404);
    exit;
}

/**
 * Routeur pour le serveur intégré de PHP (php -S), utilisé par tools/check-install-profiles.php.
 * Sert les fichiers existants tels quels, et envoie tout le reste à index.php — comme le font
 * .htaccess et la configuration Caddy de production.
 */
/*
 * La racine servie est celle que `php -S -t <dossier>` a reçue (DOCUMENT_ROOT), pas le dépôt où vit
 * ce fichier. Les outils servent parfois une AUTRE copie du site — un site neuf et jetable
 * (`tools/lib/vierge.php`) — avec ce même routeur : il incluait alors `index.php` à côté de lui, et
 * le site jetable exécutait le code de l'atelier. Trouvé le 2026-09-23 par check-mise-a-jour : la
 * copie déclarée en 1.1.0 répondait avec le 1.2.0 de l'atelier, et se croyait déjà à jour.
 */
$racine  = is_file(($_SERVER['DOCUMENT_ROOT'] ?? '') . '/index.php') ? rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/') : dirname(__DIR__);
$chemin  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$fichier = $racine . '/' . ltrim($chemin, '/');

if ($chemin !== '/' && is_file($fichier) && !preg_match('/\.(php|css|js)$/', $chemin))
{
    return FALSE; // le serveur intégré sert le fichier
}

/*
 * Se présenter comme un vrai serveur web. Pour une adresse à extension qui ne correspond à aucun
 * fichier (`/fr/css/bootstrap.min.css`), le serveur intégré de PHP 8.3 pose SCRIPT_NAME et PHP_SELF
 * au chemin DEMANDÉ, là où Apache, nginx, Caddy — et PHP 8.5 — donnent `/index.php`. Or Url::__construct()
 * déduit la base du site de SCRIPT_NAME : il obtenait `/fr/css/bootstrap` et redirigeait l'asset vers
 * la langue. Mesuré en CI le 2026-09-21 : toutes les feuilles et scripts « introuvables », sur PHP 8.3
 * seulement.
 */
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['PHP_SELF']        = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = realpath($racine . '/index.php');

require $racine . '/index.php';
