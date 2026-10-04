<?php
declare(strict_types=1);

/**
 * vierge — une installation NEUVE, sans contenu, montée le temps d'un outil, puis détruite.
 *
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Un site vide ne se comporte pas comme un site peuplé : une liste sans élément, une galerie sans
 * image, un widget sans données prennent des chemins qu'un site d'essai peuplé n'emprunte jamais. Le 2026-09-22,
 * c'est la CI — qui installe un site neuf — qui a vu le widget « image aléatoire » planter sur une
 * galerie vide. Les vérifications doivent porter sur le site « avec et sans
 * contenu » : ce fichier fabrique le second, à la demande, sur la machine elle-même.
 *
 * Le code du dépôt est recopié HORS de lui (sur le disque, pas dans `/tmp`, une mémoire vive de 2 Go
 * qu'un outil a déjà remplie), dans une base jetable, puis installé par `tools/ci-install.php` — la
 * même installation complète que la CI. Base et copie sont détruites à la fin de l'outil, et sur
 * interruption si l'outil intercepte les signaux (cf. check-mise-en-page).
 *
 * Il faut un compte capable de créer une base : celui de `config/db-test.php` (`root_user`), que
 * `prepare-test-db` écrit. Le nom de la base finit par `_install_test`, le motif sur lequel ce
 * compte a tous les droits.
 *
 * Usage
 * -----
 *   $vierge = nf_site_vierge();          // ['racine' => '/var/tmp/nf-vierge-…', 'db' => mysqli]
 *   $serveur = nf_serveur($port, [...], $vierge['racine']);
 */

require_once __DIR__.'/outil.php';
require_once __DIR__.'/site.php';

/** @return array{racine: string, db: mysqli} */
function nf_site_vierge(string $base = 'neofrag_vierge_install_test'): array
{
    $cfg = @include nf_racine().'/config/db-test.php';

    if (!is_array($cfg) || empty($cfg['root_user']))
    {
        nf_refus('une installation vierge demande config/db-test.php avec un compte capable de créer une base (root_user) — php tools/prepare-test-db.php');
    }

    $acces = [
        'hostname' => (string) ($cfg['host'] ?? '127.0.0.1'),
        'port'     => (int) ($cfg['port'] ?? 3306),
        'username' => (string) $cfg['root_user'],
        'password' => (string) ($cfg['root_pass'] ?? ''),
    ];

    $copie   = '/var/tmp/nf-vierge-'.bin2hex(random_bytes(3));
    $serveur = nf_connexion_admin($acces, 'à la base jetable');

    if (!$serveur->query("DROP DATABASE IF EXISTS `$base`") || !$serveur->query("CREATE DATABASE `$base` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"))
    {
        nf_refus("base jetable `$base` impossible : ".$serveur->error);
    }

    register_shutdown_function(static function () use ($serveur, $base, $copie): void {
        @$serveur->query("DROP DATABASE IF EXISTS `$base`");

        if (str_starts_with($copie, '/var/tmp/nf-vierge-') && is_dir($copie))
        {
            exec('rm -rf '.escapeshellarg($copie));
        }
    });

    // Le code, sans ce qui appartient à CETTE installation : configuration, fichiers envoyés,
    // caches, journaux, sauvegardes, dépendances de développement Node, historique git.
    exec(sprintf('rsync -a --exclude=/config/*.php --exclude=/upload/* --exclude=/cache/* --exclude=/logs/*.log '
        .'--exclude=/backups/* --exclude=/node_modules --exclude=/.git %s/ %s/ 2>&1', escapeshellarg(nf_racine()), escapeshellarg($copie)), $sortie, $code);

    if ($code !== 0 || !is_file($copie.'/index.php'))
    {
        nf_refus('copie du code impossible : '.implode(' ', $sortie));
    }

    $env = sprintf('NF_DB_HOST=%s NF_DB_PORT=%d NF_DB_USER=%s NF_DB_PASS=%s NF_DB_NAME=%s',
        escapeshellarg($acces['hostname']), $acces['port'], escapeshellarg($acces['username']), escapeshellarg($acces['password']), escapeshellarg($base));

    exec('cd '.escapeshellarg($copie).' && '.$env.' '.escapeshellarg(PHP_BINARY).' tools/ci-install.php 2>&1', $sortie, $code);

    if ($code !== 0)
    {
        nf_refus('installation neuve impossible : '.implode(' | ', array_slice($sortie, -4)));
    }

    $db = nf_connexion_admin($acces, 'au site vierge');

    if (!$db->select_db($base))
    {
        nf_refus("la base jetable `$base` n'est pas joignable : ".$db->error);
    }

    return ['racine' => $copie, 'db' => $db];
}
