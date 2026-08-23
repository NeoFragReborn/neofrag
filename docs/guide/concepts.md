# Concepts

Comprendre ces cinq notions suffit à maîtriser NeoFrag Reborn.

## Modules

Un **module** est une fonctionnalité complète : le forum, les actualités, la galerie,
les membres, la boutique… Chaque module apporte ses **pages publiques** (ce que voient
les visiteurs), son **interface d'administration**, ses **données** (tables) et ses
**permissions**.

Tu actives ou désactives les modules depuis **Admin → Thèmes & Addons**. Un module
désactivé disparaît du site mais conserve ses données.

L'installation fournit **tous les modules** du paquet (modèle « tout bundlé », comme
WordPress) : ils sont installés et activés d'emblée, **sans choix de profil**. Tu règles
ensuite leur visibilité via **Admin → Thèmes & Addons** (activer/désactiver) et
**Admin → Permissions** (RBAC). D'autres addons, non livrés dans le paquet, s'ajoutent
depuis le **marketplace**.

## Widgets

Un **widget** est un petit bloc réutilisable : le menu de navigation, l'espace membre,
les derniers commentaires, un compte à rebours, un lecteur Twitch… Un même widget peut
être placé plusieurs fois, à des endroits différents, avec des réglages différents.

Les widgets se posent dans les **zones** du thème via le **Live Editor** (éditeur
visuel) ou via les **dispositions** définies par le thème.

## Thèmes

Un **thème** donne l'identité visuelle du site : couleurs, typographies, mise en page,
navbar, footer. Le thème déclare des **zones** et organise le contenu via des
**dispositions**.

NeoFrag Reborn fournit deux thèmes cœur :

- **Vitrine** — le thème officiel (landing moderne, dédié au site de présentation).
- **Nebula** — un thème communautaire généraliste (dark navy + teal), pensé pour ta
  team, ta guilde ou ta communauté.

D'autres thèmes (Granite, Forge, Blockcraft…) sont disponibles au téléchargement sur le
marketplace. Le thème actif se choisit dans **Admin → Thèmes & Addons**, et les
visiteurs peuvent en changer via le sélecteur en bas de page (si plusieurs sont
installés).

## Zones & dispositions

Un thème découpe la page en **zones** : généralement *Header*, *Avant-contenu*,
*Contenu*, *Post-contenu*, *Footer*.

Une **disposition** décrit, pour une page donnée (ou un motif de pages), quels widgets
occupent quelle zone et dans quelle grille. Les motifs vont du plus général au plus
précis :

| Motif | S'applique à |
|---|---|
| `*` | toutes les pages |
| `/` | la page d'accueil uniquement |
| `forum/*` | toutes les pages du forum |

Le motif le plus précis l'emporte. Exemple : une disposition `*` met le contenu sur
8 colonnes + une colonne latérale de widgets, tandis qu'une disposition `marketplace*`
peut passer la même page en pleine largeur.

Tu modifies les dispositions à la souris avec le **Live Editor**.

## Addons & marketplace

**Addon** est le terme générique pour tout ce qui s'installe : modules, widgets,
thèmes et connecteurs d'authentification (Discord, GitHub, Google…).

Le **[marketplace](marketplace.md)** est le catalogue public où tu trouves et
télécharges des addons (`.zip`). L'installation se fait ensuite en deux clics depuis
**Admin → Thèmes & Addons → Ajouter**.

Techniquement, un addon n'est qu'un **dossier de fichiers PHP** : tu peux le versionner,
le partager, et le réinstaller sur n'importe quel site NeoFrag Reborn. C'est ce qui rend
le CMS infiniment extensible — voir les guides développeur.

## En pratique : monter une communauté

Pour une team gaming, concrètement : dans *Thèmes & Addons* tu actives les modules **forum**, **équipes**
et **événements** (déjà installés) et tu passes sur le thème **Nebula** ; puis, avec le **Live Editor**, tu
places le widget **navigation** dans le *Header* et un widget de contenu (derniers sujets, prochains
matchs…) dans la colonne latérale de l'accueil. Tout se règle dans l'admin, **sans une ligne de code**.

## Surcharge sans forker

Tout fichier livré (vue, classe, asset) peut être **surchargé** sans modifier le code
d'origine. Le framework résout dans cet ordre (le premier trouvé gagne) :

1. `overrides/{type}/{fichier}` — surcharge globale
2. `themes/{thème actif}/overrides/{type}/{fichier}` — surcharge par thème
3. l'original livré

Tu adaptes ainsi un module ou un widget à ton site (ou à un thème précis) sans jamais
toucher au cœur — et sans casser les mises à jour.
