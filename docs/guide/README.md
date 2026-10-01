# Documentation NeoFrag Reborn

Bienvenue dans la documentation publique de **NeoFrag Reborn**, le CMS modulaire open source pour créer
ton site eSport, gaming ou communautaire — sans écrire une ligne de code, et entièrement extensible
quand tu en as envie.

> NeoFrag Reborn est la continuité communautaire de **NeoFrag**, créé à l'origine par
> **Michaël BILCOT** & **Jérémy VALENTIN** ([neofr.ag](https://neofr.ag)). Projet open source sous
> licence **LGPLv3**. Ces guides sont **vérifiés contre le code** de la version 1.1.0 (relecture
> complète du 2026-09-17) ; ils sont aussi publiés dans le wiki du site.

## Pour les utilisateurs

| Guide | Tu y apprends |
|---|---|
| [Installation](installation.md) | Mettre NeoFrag Reborn en ligne : prérequis, l'assistant en cinq étapes et ses profils, la ligne de commande, les premiers réglages |
| [Concepts](concepts.md) | Comment le CMS est organisé : modules, widgets, thèmes, zones et régions, dispositions, addons |
| [Le panel d'administration](admin.md) | Piloter ton site : contenu, membres, permissions, réglages, monitoring |
| [Le marketplace](marketplace.md) | Trouver et installer des addons, mettre à jour les addons et le cœur |
| [Le bot Discord](bot-discord.md) | Relier ton site à ton serveur Discord : rôles et pseudos, forum ↔ salon Forum, le tout réglé depuis l'administration — et écrire une fonctionnalité du bot |

## Pour les développeurs

| Guide | Tu y apprends |
|---|---|
| [Le framework](framework.md) | Service locator, routing, base de données, formulaires, tables, traductions, le front sans jQuery sous CSP stricte, tests et contrôles |
| [Créer un module](create-a-module.md) | Une fonctionnalité complète : déclarations, routes, checker, données et migrations, dépendances, carrefours, permissions, administration |
| [Créer un widget](create-a-widget.md) | Un bloc réutilisable : réglages avec valeurs de repli, vocabulaire CSS partagé, JavaScript vanilla |
| [Créer un thème](create-a-theme.md) | Zones et régions, squelette, dispositions par défaut, le vocabulaire `--nf-*` complet, vignette, migrations de thème |
| [L'API REST](api.md) | Faire parler un programme au site — un bot, une intégration : clés d'accès, droits, adresses, erreurs, limite de débit |

## En bref

NeoFrag Reborn s'installe sur un hébergement **PHP 8.2+ / MySQL ou MariaDB** classique (mutualisé
compris), **à la carte** : un profil de site à l'installation, puis des modules à activer ou à ajouter.
Une fois en place, tu composes ton site à la souris : tu places des **widgets** dans les **régions** de
ton **thème** avec l'éditeur en direct, et tu télécharges des compléments depuis le **marketplace**.

Tout est **surchargeable sans forker** (système d'overrides à trois niveaux), et toute extension est un
simple dossier de fichiers PHP que tu peux versionner et partager.
