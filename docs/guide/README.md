# Documentation NeoFrag Reborn

Bienvenue dans la documentation publique de **NeoFrag Reborn**, le CMS modulaire
open source pour créer ton site eSport, gaming ou communautaire — sans écrire une
ligne de code, et entièrement extensible quand tu en as envie.

> NeoFrag Reborn est la continuité communautaire de **NeoFrag**, créé à l'origine par
> **Michaël BILCOT** & **Jérémy VALENTIN** ([neofr.ag](https://neofr.ag)). Projet open
> source sous licence **LGPLv3**.

## Pour les utilisateurs

| Guide | Tu y apprends |
|---|---|
| [Installation](installation.md) | Mettre NeoFrag Reborn en ligne (hébergement mutualisé, prérequis, assistant d'installation) |
| [Concepts](concepts.md) | Comment le CMS est organisé : modules, widgets, thèmes, zones, dispositions, addons |
| [Le panel d'administration](admin.md) | Piloter ton site : contenu, membres, permissions, réglages, monitoring |
| [Le marketplace](marketplace.md) | Trouver, télécharger et installer des modules, widgets et thèmes |

## Pour les développeurs

| Guide | Tu y apprends |
|---|---|
| [Créer un thème](create-a-theme.md) | Donner une identité visuelle complète à un site (navbar, zones, dispositions, CSS) |
| [Créer un widget](create-a-widget.md) | Un bloc réutilisable plaçable dans n'importe quelle zone |
| [Créer un module](create-a-module.md) | Une fonctionnalité complète avec ses pages, son admin et ses données |
| [Le framework](framework.md) | Service locator, routing, ORM (Model2), formulaires (Form2), tables (Table2), helpers |

## En bref

NeoFrag Reborn s'installe sur un hébergement **PHP 8.3+ / MySQL ou MariaDB** classique
(mutualisé compris). Une fois en place, tu composes ton site à la souris : tu actives
des **modules** (forum, actualités, galerie…), tu places des **widgets** dans les
**zones** de ton **thème**, et tu télécharges des compléments depuis le **marketplace**.

Tout est **surchargeable sans forker** (système d'overrides à trois niveaux), et toute
extension est un simple dossier de fichiers PHP que tu peux versionner et partager.
