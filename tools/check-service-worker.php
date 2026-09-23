<?php
declare(strict_types=1);

/**
 * check-service-worker — le worker se retire quand on l'éteint, et ne met jamais le HTML en cache.
 *
 * Famille : navigateur
 *
 * Pourquoi
 * --------
 * Un service worker installé dans un navigateur y reste. Il continue de répondre à la place du
 * réseau **même si le fichier disparaît du serveur** : supprimer le fichier ne désinstalle rien, il
 * fige la dernière version connue, pour toujours, chez des visiteurs qu'on ne peut pas joindre.
 * C'est le seul composant de ce produit capable de casser un site de façon durable, et le seul dont
 * un déploiement ne répare pas la casse.
 *
 * Deux propriétés le rendent sûr, et aucune des deux ne se voit à la relecture :
 *
 *   1. **Éteint, l'adresse répond quand même**, et rend un worker qui efface ses caches, se
 *      désinscrit et n'écoute AUCUN `fetch`. C'est le chemin de sortie : un worker déjà installé
 *      revérifie son script à chaque navigation, reçoit celui-là, et disparaît. Rendre 404 le
 *      laisserait en place.
 *   2. **Allumé, il ne met jamais le HTML en cache.** Une page en cache survit à un déploiement :
 *      on sert alors un site d'avant-hier, sans qu'aucune mesure ne le dise.
 *
 * Ce contrôle bascule le réglage, demande l'adresse dans les deux états, et **remet le réglage
 * comme il l'a trouvé** — y compris si une interruption survient.
 *
 * Usage
 * -----
 *   php tools/check-service-worker.php
 *   php tools/check-service-worker.php --port=8107
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/site.php';

[$o] = nf_options(['port' => 0]);

$db = nf_connexion();

if (!nf_table_existe($db, 'nf_settings'))
{
    nf_refus('la table des réglages est absente : ce site n\'est pas installé');
}

$avant = nf_scalar($db, 'SELECT value FROM nf_settings WHERE name = "nf_pwa"');

if ($avant === NULL)
{
    nf_refus('le réglage `nf_pwa` n\'existe pas — jouer `php tools/migrate.php up`');
}

$avant = (string) $avant;

/** Pose la valeur du réglage, sans passer par le framework. */
$poser = static function(string $valeur) use ($db): void {
    $db->query('UPDATE nf_settings SET value = "'.$db->real_escape_string($valeur).'" WHERE name = "nf_pwa"');
};

/*
 * Le réglage est remis en place quoi qu'il arrive : fin normale, refus, interruption. Un contrôle
 * qui laisse un site avec son service worker allumé serait exactement le défaut qu'il surveille.
 */
$rendre = static function() use ($poser, $avant): void { $poser($avant); };
register_shutdown_function($rendre);

foreach ([SIGINT, SIGTERM] as $signal)
{
    if (function_exists('pcntl_signal'))
    {
        pcntl_signal($signal, static function() use ($rendre): void { $rendre(); exit(NF_REFUS); });
    }
}

/** Le script servi, dans l'état de réglage demandé. */
$servi = static function(string $valeur) use ($poser, $o): array {
    $poser($valeur);

    $serveur = nf_serveur(nf_port((int) $o['port']));
    $worker  = nf_http($serveur->base.'/service-worker.js', ['suivre' => 0]);
    $page    = nf_http($serveur->base.'/fr', ['suivre' => 1]);
    $serveur->arreter();

    return [$worker, (string) $page['corps']];
};

$ecarts = [];

// ── 1. Éteint : l'adresse répond, et ce qu'elle rend se retire ──────────────────────────────
[$worker, $page] = $servi('0');
$corps = (string) $worker['corps'];

if ($worker['code'] !== 200)
{
    $ecarts[] = sprintf('éteint : /service-worker.js répond %s — un 404 laisserait les workers déjà installés en place, pour toujours', $worker['code'] ?: 'rien');
}

if (!str_contains($corps, 'registration.unregister'))
{
    $ecarts[] = 'éteint : le script ne se désinscrit pas — éteindre l\'interrupteur ne désinstallerait rien';
}

if (!str_contains($corps, 'caches.delete'))
{
    $ecarts[] = 'éteint : le script n\'efface pas ses caches — des fichiers d\'une version passée survivraient';
}

if (str_contains($corps, "addEventListener('fetch'"))
{
    $ecarts[] = 'éteint : le script écoute encore `fetch` — il répondrait à la place du réseau alors qu\'il est censé partir';
}

if (str_contains($page, 'serviceWorker.register'))
{
    $ecarts[] = 'éteint : la page inscrit quand même un worker';
}

// ── 2. Allumé : il sert, mais jamais le HTML ────────────────────────────────────────────────
[$worker, $page] = $servi('1');
$corps = (string) $worker['corps'];

if ($worker['code'] !== 200)
{
    $ecarts[] = sprintf('allumé : /service-worker.js répond %s', $worker['code'] ?: 'rien');
}

$type = strtolower((string) ($worker['entetes']['content-type'] ?? ''));

if (!str_contains($type, 'javascript'))
{
    $ecarts[] = sprintf('allumé : servi en « %s » — un navigateur refuse un worker qui n\'est pas du JavaScript', $type ?: 'sans type');
}

if (!str_contains($corps, "addEventListener('fetch'"))
{
    $ecarts[] = 'allumé : aucun écouteur `fetch` — le worker ne servirait à rien';
}

/*
 * Les deux gardes qui protègent du pire. `mode === 'navigate'` écarte les navigations, et le
 * `accept` écarte celles que certains navigateurs n'annoncent pas comme telles. Les chercher dans
 * le script est une lecture, pas une mesure — mais c'est la seule façon de le dire sans installer
 * réellement un worker, ce qu'aucun contrôle ne devrait faire sur une machine de travail.
 */
foreach ([
    "requete.mode === 'navigate'" => 'la garde sur `mode === navigate` a disparu',
    "indexOf('text/html')"        => 'la garde sur l\'en-tête `accept: text/html` a disparu',
] as $motif => $quoi)
{
    if (!str_contains(str_replace(' ', '', $corps), str_replace(' ', '', $motif)))
    {
        $ecarts[] = 'allumé : '.$quoi.' — le HTML pourrait être mis en cache, et une page périmée survivrait à un déploiement';
    }
}

if (!str_contains($page, 'serviceWorker.register'))
{
    $ecarts[] = 'allumé : la page n\'inscrit aucun worker';
}

// ── Le réglage retrouve sa valeur ───────────────────────────────────────────────────────────
$rendre();

printf("réglage `nf_pwa` éprouvé dans ses deux états, puis rendu à « %s ».\n\n", $avant);

if ($ecarts)
{
    foreach ($ecarts as $e)
    {
        echo '  ✗ ', $e, "\n";
    }

    echo "\n";

    nf_echec(sprintf('%d écart(s) sur le service worker', count($ecarts)));
}

nf_ok('éteint il se retire et n\'écoute rien ; allumé il sert les fichiers statiques et jamais le HTML');
