# NeoFrag Reborn

[![CI](https://github.com/NeoFragReborn/neofrag/actions/workflows/ci.yml/badge.svg)](https://github.com/NeoFragReborn/neofrag/actions/workflows/ci.yml)
[![PHP 8.2 à 8.5](https://img.shields.io/badge/PHP-8.2%20%C3%A0%208.5-777bb4.svg)](docs/guide/installation.md#prérequis)
[![License: LGPL v3](https://img.shields.io/badge/license-LGPL%20v3-blue.svg)](COPYING.LESSER)

Le CMS PHP libre des communautés, du gaming aux associations : forum, actualités, événements et
calendrier, membres, dons, newsletter, wiki, galerie, modération, messagerie — et, pour les équipes de
jeu, équipes, matchs, recrutement et palmarès. Continuité communautaire de [NeoFrag](https://neofr.ag),
créé par Michaël BILCOT et Jérémy VALENTIN.

**Site du projet :** [neofrag-reborn.xyz](https://neofrag-reborn.xyz) · **Démonstration :**
[demo.neofrag-reborn.xyz](https://demo.neofrag-reborn.xyz) · **Versions :**
[page des versions](https://github.com/NeoFragReborn/neofrag/releases)

## Les dépôts du projet

| Dépôt | Ce qu'il contient |
|---|---|
| **neofrag** (celui-ci) | le cœur du CMS et les addons qu'installent ses profils d'installation, la documentation, les tests et les outils |
| [extensions](https://github.com/NeoFragReborn/extensions) | les addons à la carte, que le marketplace propose après l'installation |
| [bot-discord](https://github.com/NeoFragReborn/bot-discord) | le bot qui relie un site et son serveur Discord, avec ses propres versions |

Le **paquet d'installation** de chaque version réunit le cœur et tous les addons, dépendances
comprises : c'est lui qu'il faut télécharger pour installer un site.

## Installer

Sur un hébergement **PHP 8.2 à 8.5 et MySQL ou MariaDB** — Apache (`.htaccess` livré), nginx ou Caddy
(exemples `nginx.conf` et `Caddyfile` livrés) —, **sans Docker, Composer ni shell** : télécharger le
paquet `neofrag-reborn-public-<version>.zip` sur la [page des versions](https://github.com/NeoFragReborn/neofrag/releases),
le téléverser, ouvrir le domaine, suivre l'assistant. Prérequis exacts et pas-à-pas :
[docs/guide/installation.md](docs/guide/installation.md) ; déploiement par FTP et mise à jour :
[docs/deploy-ftp.md](docs/deploy-ftp.md).

## Développer

<!-- nouveau-venu : ce bloc est joué tel quel, sur une machine vierge, par tools/check-nouveau-venu.php -->
```bash
git clone https://github.com/NeoFragReborn/neofrag.git
git clone https://github.com/NeoFragReborn/extensions.git
cd neofrag && composer install
npm ci                                 # ESLint, pour le contrôle du JavaScript (Node.js 22 ou plus)
php tools/assembler.php --extensions=../extensions   # les addons à la carte : le produit entier
php tools/check-all.php                # composer audit + les contrôles statiques
```

Un site qui tourne sur ta machine, la base des tests et la suite entière : le chemin pas à pas est dans
[.github/CONTRIBUTING.md](.github/CONTRIBUTING.md#démarrer).

Chaque contrôle est décrit dans [tools/README.md](tools/README.md) ; l'environnement, la batterie et la
CI dans [docs/development.md](docs/development.md) ; les règles et le chemin d'une contribution dans
[.github/CONTRIBUTING.md](.github/CONTRIBUTING.md). Créer un [module](docs/guide/create-a-module.md),
un [widget](docs/guide/create-a-widget.md) ou un [thème](docs/guide/create-a-theme.md) : les guides
développeur. Ce qui a changé : [CHANGELOG.md](CHANGELOG.md) ; où va le projet : [ROADMAP.md](ROADMAP.md).

## Crédits

NeoFrag a été créé par **Michaël BILCOT** (FoxLey) et **Jérémy VALENTIN** (eResnova) ; leurs addons
gardent leur signature. NeoFrag Reborn en est la continuité, **maintenue par Luandre**.

Merci aux auteurs de la communauté NeoFrag dont des addons ont été portés : **ArkaNiX** (l'Horloge),
**Chewbaka** (le thème Extend), **HiddenBlob** (le gestionnaire de fichiers, issu de HiddenCMS, et les
Dons, d'après le module de **majiid**) — et à ceux dont les idées ont inspiré d'autres addons. La liste
complète, addon par addon, avec chaque licence : [NOTICE](NOTICE).

## Licence

LGPL-3.0 ou ultérieure ([COPYING](COPYING), [COPYING.LESSER](COPYING.LESSER)). Quelques éléments ont
leur propre licence — le thème Extend (CC BY-NC-SA 4.0), TinyMCE (GPL-2.0 ou ultérieure), les polices
(OFL) : [NOTICE](NOTICE) et [LICENSES/](LICENSES/). Signaler une faille de sécurité :
[politique de sécurité](https://github.com/NeoFragReborn/.github/blob/main/SECURITY.md).
