# Concepts

Comprendre ces cinq notions suffit à maîtriser NeoFrag Reborn.

## Modules

Un **module** est une fonctionnalité complète : le forum, les actualités, la galerie, les membres, la
boutique… Chaque module apporte ses **pages publiques**, son **interface d'administration**, ses
**données** (tables) et ses **permissions**.

Le paquet livre **62 modules**, mais tu ne les installes pas tous : à l'installation, un **profil de
site** — *Complet*, *Gaming / eSport*, *Communauté*, *Association / club* ou *Cœur seul* — pré-coche ce qui correspond, et tu
peux décocher module par module. Ce qu'un module réclame est ajouté automatiquement (le palmarès a
besoin des équipes). Les modules du **cœur** (comptes, permissions, paramètres, pages, outils…) sont
toujours là et ne se désinstallent pas.

Ensuite, depuis **Administration → Thèmes & Addons**, tu installes, actives, désactives ou désinstalles
chaque module. Un module désactivé disparaît du site mais conserve ses données. Les adresses d'un module
absent répondent par un **404 propre**, jamais par une erreur.

## Widgets

Un **widget** est un petit bloc réutilisable : le menu de navigation, l'espace membre, les derniers
commentaires, un compte à rebours, un lecteur Twitch… Un même widget peut être placé plusieurs fois, à
des endroits différents, avec des réglages différents. Le paquet en livre **38**.

Les widgets se posent dans les **régions** du thème via l'**éditeur en direct** : un assistant en
quatre étapes (widget, type, titre, configuration), du glisser-déposer pour les déplacer.

## Thèmes

Un **thème** donne l'identité visuelle du site : couleurs, typographies, mise en page, navigation,
pied de page, et le **mode clair ou sombre**. Il déclare des **zones**, leur donne des **noms de
régions** stables (`header`, `content`, `footer`…), et pose ses **dispositions** par défaut.

Le paquet livre **Nebula**, un thème communautaire généraliste (navy et turquoise, glassmorphism),
pensé pour une équipe, une guilde, un club ou une association. Six autres thèmes — **Granite**, **Forge**,
**Blockcraft**, **Extend**, **Chronique**, **Pulse** — sont dans le paquet et s'installent depuis **Administration → Thèmes &
Addons** ; une installation qui ne les a pas les trouve dans le [marketplace](marketplace.md). Le thème actif se
choisit dans **Administration → Thèmes & Addons** ; si plusieurs thèmes publics sont installés, les
visiteurs peuvent en changer via le sélecteur en pied de page.

Tous les thèmes parlent le **même vocabulaire de couleurs** (`--nf-accent`, `--nf-surface`…) : un widget
prend automatiquement la charte du thème actif.

## Zones, régions et dispositions

Un thème découpe la page en **zones** : généralement *Header*, *Avant-contenu*, *Contenu*,
*Post-contenu*, *Footer*, chacune connue sous un **nom de région** que les gabarits emploient.

Une **disposition** décrit, pour une page donnée (ou un motif de pages), quels widgets occupent quelle
zone et dans quelle grille. Les motifs vont du plus général au plus précis :

| Motif | S'applique à |
|---|---|
| `*` | toutes les pages |
| `/` | la page d'accueil uniquement |
| `forum/*` | toutes les pages du forum |

Le motif le plus précis l'emporte : une disposition `*` met le contenu sur huit colonnes plus une
colonne latérale, une disposition `marketplace*` peut passer la même page en pleine largeur.

Tu modifies les dispositions à la souris avec l'**éditeur en direct**. Une page peut aussi porter des
**blocs** : des instances de modules ordonnées et configurées, insérées par le shortcode `[block:…]`.

## Addons et marketplace

**Addon** est le terme générique pour tout ce qui s'installe : modules, widgets, thèmes, connecteurs
d'authentification (Discord, GitHub, Google) et packs de langue (six langues livrées).

Le **[marketplace](marketplace.md)** est le catalogue du projet : il **détecte les mises à jour** des
addons installés et permet d'**ajouter** ceux qui ne sont pas dans le paquet, avec vérification
d'intégrité SHA-256. Le **cœur du CMS** se met à jour en un clic depuis **Monitoring**, avec sauvegarde
avant écriture et retour arrière automatique en cas d'échec.

Techniquement, un addon n'est qu'un **dossier de fichiers PHP** : tu peux le versionner, le partager, et
le réinstaller sur n'importe quel site NeoFrag Reborn. C'est ce qui rend le CMS extensible — voir les
guides développeur.

## En pratique : monter une communauté

Pour une équipe de jeu : à l'installation, choisis le profil **Gaming / eSport** (forum, équipes,
événements, recrutement, palmarès…) et le thème Nebula est en place ; puis, avec l'éditeur en direct,
place le widget **navigation** dans la région `header` et un widget de contenu (derniers sujets,
prochains matchs…) dans la colonne latérale de l'accueil. Tout se règle dans l'administration, **sans
une ligne de code**.

Pour une association ou un club : choisis le profil **Association / club** — actualités, forum,
galeries, calendrier, dons, newsletter, wiki et FAQ, sans l'attirail esport. Les membres, les pages et
le formulaire de contact sont toujours là ; les équipes, s'il en faut, s'ajoutent d'un clic.

## Surcharge sans forker

Tout fichier livré (vue, classe, asset) peut être **surchargé** sans modifier le code d'origine. Le
framework résout dans cet ordre (le premier trouvé gagne) :

1. `overrides/{type}/{fichier}` — surcharge globale
2. `themes/{thème actif}/overrides/{type}/{fichier}` — surcharge par thème
3. l'original livré

Tu adaptes ainsi un module ou un widget à ton site sans jamais toucher au cœur, et sans casser les mises
à jour.
