<?php
declare(strict_types=1);

/**
 * interdits — ce qu'un serveur web ne doit jamais servir : les dossiers, les extensions, les fichiers.
 *
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Deux contrôles jugent la même liste : `check-htaccess` lit les RÈGLES des trois configurations
 * livrées (`.htaccess`, `nginx.conf`, `Caddyfile`), `check-serveur-web` les ÉPROUVE sur un vrai serveur
 * en posant des sondes et en les demandant. Une liste écrite deux fois finit par diverger ; elle ne
 * vit qu'ici.
 *
 * La raison de chaque dossier compte autant que la règle : c'est elle qui permet de trancher, le jour
 * où l'on se demande si une garde peut sauter.
 *
 * Usage
 * -----
 *   foreach (NF_DOSSIERS_INTERDITS as $dossier => $pourquoi) { … }
 */

require_once __DIR__.'/outil.php';

/** Les dossiers qui ne doivent JAMAIS être joignables en HTTP, et pourquoi. */
const NF_DOSSIERS_INTERDITS = [
    'config'  => 'identifiants de base, clé de chiffrement, sel des mots de passe, SMTP',
    'backups' => 'archives complètes du site, base comprise',
    'logs'    => 'journaux applicatifs : adresses, erreurs, parfois des données de membres',
    'cache'   => 'dérivés internes, parfois issus de contenu réservé',
    'install' => "l'installateur et ses schémas SQL (l'assistant s'affiche à la racine du site)",
    'tools'   => 'migrations, sauvegardes, extraction de schéma — exécutables',
    'tests'   => 'la suite de tests : décrit le fonctionnement interne',
    'docs'    => 'la documentation de travail',
    'upload'  => 'fichiers déposés par les membres : à servir, mais jamais à exécuter',
];

/** Les extensions refusées, quelle que soit leur place dans l'arborescence. */
const NF_EXTENSIONS_INTERDITES = ['sql', 'lock', 'scssc', 'map', 'dist', 'ini', 'sh', 'neon', 'md'];

/*
 * Les fichiers refusés par leur NOM, faute de pouvoir l'être par leur extension : `.json` et `.js`
 * se servent légitimement partout ailleurs, et les exemples de configuration n'en ont pas. Aucun ne
 * porte de secret ; la raison est la même que pour `tools/` et `tests/` : rien de ce qui sert à
 * développer ou à configurer un serveur n'a de raison d'être servi.
 */
const NF_FICHIERS_INTERDITS = ['package.json', 'package-lock.json', 'eslint.config.js', 'playwright.config.js', 'Caddyfile', 'nginx.conf'];
