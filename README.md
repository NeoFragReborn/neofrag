<div align="center">

<a href="https://neofrag-reborn.xyz"><img src="https://neofrag-reborn.xyz/themes/vitrine/images/partage/fr.png" alt="NeoFrag Reborn — le CMS libre des communautés" width="720"></a>

# NeoFrag Reborn

**Le CMS PHP libre des communautés, du gaming aux associations.**

[![Version](https://img.shields.io/github/v/release/NeoFragReborn/neofrag?label=version&color=2dd4bf)](https://github.com/NeoFragReborn/neofrag/releases/latest)
[![CI](https://img.shields.io/github/actions/workflow/status/NeoFragReborn/neofrag/ci.yml?branch=main&label=CI&logo=githubactions&logoColor=white)](https://github.com/NeoFragReborn/neofrag/actions/workflows/ci.yml)
[![PHP 8.2 à 8.5](https://img.shields.io/badge/PHP-8.2%20%C3%A0%208.5-777bb4.svg?logo=php&logoColor=white)](docs/guide/installation.md#prérequis)
[![Licence LGPL-3.0-or-later](https://img.shields.io/badge/licence-LGPL--3.0--or--later-blue.svg)](COPYING.LESSER)
[![Discord](https://img.shields.io/badge/Discord-rejoindre-5865F2.svg?logo=discord&logoColor=white)](https://discord.gg/UmBRbwxtch)

[Site du projet](https://neofrag-reborn.xyz) · [Démonstration](https://demo.neofrag-reborn.xyz) ·
[Wiki](https://neofrag-reborn.xyz/wiki) · [Versions](https://github.com/NeoFragReborn/neofrag/releases) ·
[Discord](https://discord.gg/UmBRbwxtch) · [English](#-in-english)

</div>

Le site d'une équipe, d'une guilde, d'un club ou d'une association, sans écrire une ligne de code :
NeoFrag Reborn s'installe sur un simple hébergement mutualisé, se compose à la souris et s'étend par des
modules, des widgets et des thèmes. C'est la continuité communautaire de [NeoFrag](https://neofr.ag),
créé par Michaël BILCOT et Jérémy VALENTIN.

> 🎮 **Envie de voir à quoi ça ressemble ?** La [démonstration en ligne](https://demo.neofrag-reborn.xyz)
> se parcourt sans rien installer.

## ✨ Fonctionnalités

- 💬 **La vie d'une communauté** — forum, actualités, événements et calendrier, membres, messagerie,
  dons, newsletter, wiki, galerie et modération.
- 🎮 **Pour les équipes de jeu** — équipes, matchs, recrutement et palmarès ; gamification (karma,
  points), boutique et VIP.
- 🧭 **Une installation à la carte** — un assistant en cinq étapes et cinq profils de site (*Complet*,
  *Gaming / eSport*, *Communauté*, *Association / club*, *Cœur seul*) ; chaque module se décoche, et
  ses dépendances suivent d'elles-mêmes.
- 🎨 **Un site composé à la souris** — des widgets posés dans les régions du thème avec l'éditeur en
  direct ; modules, widgets et thèmes se surchargent sans forker.
- 🧩 **Un marketplace intégré** — ajouter, mettre à jour ou reprendre un addon depuis l'administration ;
  chaque archive est vérifiée par son empreinte SHA-256.
- 🔄 **La mise à jour du cœur en un clic** — sauvegarde avant d'écrire, retour arrière automatique si une
  étape échoue.
- 🔐 **La sécurité au quotidien** — double authentification (TOTP), mots de passe hachés en Argon2id,
  jeton CSRF sur chaque formulaire, politique de sécurité du contenu stricte, limitation de débit et
  journal d'audit : [docs/securite.md](docs/securite.md).
- 🌍 **Six langues d'interface** — français, anglais, allemand, espagnol, italien et portugais.
- 🤖 **Relié à Discord** — connexion par Discord, GitHub ou Google, et un
  [bot](https://github.com/NeoFragReborn/bot-discord) qui relie le site à son serveur Discord.
- 🏠 **Un hébergement mutualisé suffit** — ni Docker, ni Composer, ni shell pour installer.

## 📦 Les dépôts du projet

| Dépôt | Ce qu'il contient |
|---|---|
| **neofrag** (celui-ci) | le cœur du CMS et les addons qu'installent ses profils d'installation, la documentation, les tests et les outils |
| [extensions](https://github.com/NeoFragReborn/extensions) | les addons à la carte, que le marketplace propose après l'installation |
| [bot-discord](https://github.com/NeoFragReborn/bot-discord) | le bot qui relie un site et son serveur Discord, avec ses propres versions |

Le **paquet d'installation** de chaque version réunit le cœur et tous les addons, dépendances
comprises : c'est lui qu'il faut télécharger pour installer un site.

## 🚀 Installer

Sur un hébergement **PHP 8.2 à 8.5 et MySQL ou MariaDB** — Apache (`.htaccess` livré), nginx ou Caddy
(exemples `nginx.conf` et `Caddyfile` livrés) —, **sans Docker, Composer ni shell** :

1. télécharger le paquet `neofrag-reborn-public-<version>.zip` sur la
   [page des versions](https://github.com/NeoFragReborn/neofrag/releases) ;
2. le téléverser chez l'hébergeur ;
3. ouvrir le domaine, et suivre l'assistant.

Prérequis exacts et pas-à-pas : [docs/guide/installation.md](docs/guide/installation.md) ; déploiement
par FTP et mise à jour : [docs/deploy-ftp.md](docs/deploy-ftp.md).

## 🔧 Développer

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
CI dans [docs/development.md](docs/development.md).

## 📚 Documentation

| | Où lire |
|---|---|
| 📖 **Utiliser le CMS** | les [guides](docs/guide/README.md) : installation, concepts, administration, marketplace, bot Discord — publiés aussi dans le [wiki du site](https://neofrag-reborn.xyz/wiki) |
| 🧱 **L'étendre** | [le framework](docs/guide/framework.md), [créer un module](docs/guide/create-a-module.md), [un widget](docs/guide/create-a-widget.md), [un thème](docs/guide/create-a-theme.md), [l'API REST](docs/guide/api.md) |
| 📐 **Comprendre comment il est fait** | [l'architecture](docs/architecture.md), [l'inventaire des composants](docs/components.md), [la sécurité](docs/securite.md) |
| 🔎 **Tout le reste** | [l'index de la documentation](docs/README.md) |
| 📝 **Ce qui a changé** | [CHANGELOG.md](CHANGELOG.md) |
| 🧭 **Où va le projet** | [ROADMAP.md](ROADMAP.md) |

## 🤝 Contribuer

Toute aide est bienvenue, du signalement d'un bug à l'écriture d'un addon :

- 🐛 **un bug** — une [issue](https://github.com/NeoFragReborn/neofrag/issues), avec la version, ce qui
  se passe et ce qui était attendu ;
- 💡 **une question, une idée** — les [Discussions](https://github.com/NeoFragReborn/neofrag/discussions)
  ou le [serveur Discord](https://discord.gg/UmBRbwxtch) ;
- 🔧 **du code** — le [guide du contributeur](.github/CONTRIBUTING.md) : le filet de contrôles, les
  conventions et le chemin d'une contribution ;
- 🔒 **une faille de sécurité** — jamais d'issue publique : la
  [politique de sécurité](https://github.com/NeoFragReborn/.github/blob/main/SECURITY.md).

Les échanges suivent le [code de conduite](https://github.com/NeoFragReborn/.github/blob/main/CODE_OF_CONDUCT.md)
de l'organisation.

## 🙏 Crédits

NeoFrag a été créé par **Michaël BILCOT** (FoxLey) et **Jérémy VALENTIN** (eResnova) ; leurs addons
gardent leur signature. NeoFrag Reborn en est la continuité, **maintenue par Luandre**.

Merci aux auteurs de la communauté NeoFrag dont des addons ont été portés : **ArkaNiX** (l'Horloge),
**Chewbaka** (le thème Extend), **HiddenBlob** (le gestionnaire de fichiers, issu de HiddenCMS, et les
Dons, d'après le module de **majiid**) — et à ceux dont les idées ont inspiré d'autres addons. La liste
complète, addon par addon, avec chaque licence : [NOTICE](NOTICE).

## 📜 Licence

LGPL-3.0 ou ultérieure ([COPYING](COPYING), [COPYING.LESSER](COPYING.LESSER)). Quelques éléments ont
leur propre licence — le thème Extend (CC BY-NC-SA 4.0), TinyMCE (GPL-2.0 ou ultérieure), les polices
(OFL) : [NOTICE](NOTICE) et [LICENSES/](LICENSES/). Signaler une faille de sécurité :
[politique de sécurité](https://github.com/NeoFragReborn/.github/blob/main/SECURITY.md).

## 🌍 In English

**NeoFrag Reborn** is a free PHP CMS (LGPL-3.0-or-later) for communities, from gaming teams to
associations: forum, news, events and calendar, members, donations, newsletter, wiki, gallery,
moderation and messaging — plus teams, matches, recruitment and awards for gaming teams. It runs on
ordinary shared hosting (PHP 8.2 to 8.5, MySQL or MariaDB) with no Docker, Composer or shell: download
the package from the [releases page](https://github.com/NeoFragReborn/neofrag/releases), upload it, open
your domain and follow the installer. The site interface is available in six languages; the code, the
documentation and the discussions are in French. Try the [online demo](https://demo.neofrag-reborn.xyz),
or say hello on [Discord](https://discord.gg/UmBRbwxtch). NeoFrag Reborn carries on
[NeoFrag](https://neofr.ag), created by Michaël BILCOT and Jérémy VALENTIN.
