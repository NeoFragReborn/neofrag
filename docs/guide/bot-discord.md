# Le bot Discord

Le bot Discord de NeoFrag Reborn relie ton site et ton serveur Discord. Ses fonctionnalités
s'allument une à une :

- **rôles et pseudos** — un membre qui a lié son compte Discord reçoit sur le serveur les rôles
  reliés à ses groupes, et son pseudo du site si tu le veux ;
- **forum et salons Forum** — un forum du site et un salon Forum de Discord vivent ensemble : un sujet
  devient un fil, un fil devient un sujet, les réponses suivent dans les deux sens, et les préfixes du
  forum deviennent les étiquettes du salon ;
- **compte et apparence** — la commande `/forum` : relier son compte depuis Discord, et choisir
  comment on paraît sur le forum sans compte relié ;
- **Bugtracker** — chaque ticket devient un fil, son type et son statut en étiquettes ; `/bug` et
  `/idee` ouvrent un ticket depuis Discord ;
- **rôles temporaires** — la commande `/role` : donner un rôle pour un temps limité, que le bot retire
  à la fin.

Il se **règle entièrement depuis l'administration** du site : sa clé, son serveur, ses
fonctionnalités et leurs réglages, l'interrupteur marche / pause, le redémarrage, les
correspondances, et même la création des salons et des rôles sur le serveur. L'administration montre
aussi son état et son journal, dans ta langue.

Le bot est un **programme à part** qui tourne en permanence. Il lui faut une machine allumée — un
VPS, un Raspberry Pi, un PC — avec **Node.js 22.9 ou plus**. Un hébergement mutualisé ne peut pas le
faire tourner ; le site, lui, n'en a pas besoin et reste où il est.

## 1. Créer l'application Discord

1. Sur le [portail des développeurs Discord](https://discord.com/developers/applications) :
   *New Application*, donne-lui un nom.
2. Onglet *General Information* : note l'**Application ID**.
3. Onglet *Bot* : *Reset Token*, et copie la **clé du bot** (le « token ») — elle n'est montrée
   qu'une fois.
4. Toujours dans *Bot*, coche les deux **intents privilégiés** :
   - **Server Members Intent** — pour les rôles, les pseudos et les rôles temporaires ;
   - **Message Content Intent** — pour recopier le texte des messages Discord sur le site (forum,
     Bugtracker).

   Sans eux, le bot fonctionne en mode réduit et le dit dans son journal : il ne demande jamais à
   Discord plus que ce que l'application a le droit de recevoir.

## 2. Régler le module Discord

Le bot s'appuie sur deux modules du site, **API** et **Discord**. Le profil d'installation *Complet*
installe les deux ; sinon, *Système → Thèmes & Addons* les ajoute (Discord, un addon à la carte, vient du
marketplace s'il n'est pas sur le serveur). La synchronisation du forum demande aussi le module
**Forum**, celle des tickets le module **Bugtracker**.

*Administration → Discord → Connexion* :

| Champ | Où le trouver |
|---|---|
| Identifiant de l'application | l'*Application ID* de l'étape 1 |
| Identifiant du serveur | dans Discord, mode développeur activé : clic droit sur le serveur → *Copier l'identifiant du serveur* |
| Clé du bot | le token de l'étape 1. Il est **gardé chiffré** et n'est plus jamais affiché ; laisse le champ vide pour garder celui qui est enregistré |

Puis **Inviter le bot** (page d'accueil du module) : le lien ajoute le bot au serveur avec ses seules
permissions — jamais « Administrateur » :

| Permission | Pour |
|---|---|
| Voir les salons, Envoyer des messages, Lire l'historique | lire et écrire dans les salons reliés |
| Envoyer des messages dans les fils, Créer des fils publics, Gérer les fils | recopier un sujet ou un ticket en fil, le renommer, l'archiver, poser ses étiquettes |
| Gérer les salons | la mise en place du serveur : créer une catégorie, des salons Forum et leurs étiquettes |
| Gérer les messages | reporter sur Discord une suppression faite sur le site |
| Gérer les webhooks | poster les messages du site sous le nom et l'avatar de leur auteur |
| Ajouter des réactions, Intégrer des liens, Joindre des fichiers | des messages complets |
| Gérer les rôles, Gérer les pseudos | les rôles, les pseudos et les rôles temporaires |
| Utiliser les commandes de l'application | `/forum`, `/bug`, `/idee`, `/role` |

Dans les réglages du serveur (*Rôles*), place le rôle du bot **au-dessus** des rôles qu'il donne :
Discord interdit à un bot de donner un rôle placé plus haut que le sien.

## 3. Installer le bot sur sa machine

Le bot se télécharge en archive déjà compilée, `neofrag-reborn-bot-X.Y.Z.tar.gz`, sur la
[page des versions](https://github.com/NeoFragReborn/bot-discord/releases/latest) de son dépôt. Il a ses propres numéros de version,
indépendants de ceux du site.

```bash
tar xzf neofrag-reborn-bot-X.Y.Z.tar.gz && cd bot
npm ci --omit=dev
cp .env.example .env
```

*Administration → Discord → Créer la clé d'accès* : la page donne **deux lignes** — l'adresse du site
et la clé d'accès du bot — à coller dans `.env`. Elles ne sont montrées qu'une fois ; créer une
nouvelle clé révoque l'ancienne. Puis :

```bash
npm start
```

Pour qu'il tourne en service, démarre avec la machine et redémarre s'il tombe :
`neofrag-bot.service` (systemd, durci) explique son installation en tête de fichier. Son journal :
`journalctl -u neofrag-bot -f` — et, surtout, l'administration du site.

Sur la machine, il ne reste que ces deux lignes. Tout le reste arrive par le site.

**Mettre à jour le bot** : remplace le dossier par celui de la nouvelle archive en gardant ta
configuration (`.env`, ou `/etc/neofrag-bot/bot.env` sous systemd), puis `npm ci --omit=dev` et
redémarre le service. Une nouvelle version du bot qui a besoin de droits
de plus sur le site le dit dans l'administration : crée alors une nouvelle clé d'accès.

## 4. Le piloter depuis l'administration

*Administration → Discord* montre :

- son **état** — *En ligne*, *En pause* (vivant, interrupteur coupé), *Connexion à Discord…*, ou
  *Hors ligne* (aucune nouvelle depuis une minute et demie) —, sa version, son dernier signe de vie ;
- les boutons **Mettre en marche / Mettre en pause**, **Redémarrer** et **Resynchroniser** (remettre
  tout d'accord : rôles et pseudos, et ce qui s'est écrit sur Discord pendant une absence du bot),
  qu'il applique dans la minute ;
- son **journal** : connexions, synchronisations, et ce qui l'empêche de fonctionner (intent non
  coché, rôle mal placé, permission manquante, clé d'accès trop ancienne), avec la marche à suivre.

**Changer la clé du bot** (fuite, régénération) : colle la nouvelle dans *Connexion*. Le bot la relit
et se reconnecte tout seul ; la machine n'est pas touchée.

### Les fonctionnalités

*Discord → Fonctionnalités* liste ce que le bot sait faire — il les déclare lui-même à chaque signe de
vie : une fonctionnalité ajoutée au bot y apparaît sans rien toucher au site. Chacune s'**allume** ou
s'**éteint**, et ses **réglages** s'y changent ; le bot applique le changement dans la minute, sans
redémarrer. Le Bugtracker et les rôles temporaires sont éteints au départ.

### La mise en place du serveur

*Discord → Mise en place du serveur* crée sur Discord ce qu'il faut pour relier le site, sans rien
faire à la main :

- une **catégorie** (reprise si elle existe déjà) ;
- un **salon Forum par forum** choisi, avec les préfixes du forum en étiquettes ;
- un **rôle par groupe** choisi, placé sous celui du bot pour qu'il puisse le donner.

Un salon ou un rôle du même nom est repris au lieu d'être dédoublé. Un **aperçu** montre ce qui sera
créé et repris ; **Appliquer** le fait, et les correspondances (salon ↔ forum, groupe ↔ rôle) se posent
d'elles-mêmes. **Annuler la dernière mise en place** supprime ce qu'elle a créé — jamais ce qu'elle a
repris.

## Les rôles et les pseudos

*Discord → Groupes et rôles* relie un groupe du site à un rôle du serveur. Le **site fait foi** :

- un membre lié qui est dans le groupe reçoit le rôle ; qui n'y est plus le perd ;
- un rôle que rien ne relie n'est **jamais touché** ; un membre qui n'a pas lié son compte non plus ;
- avec le réglage *Pseudos*, le membre lié porte sur le serveur son pseudo du site (sauf le
  propriétaire du serveur, que Discord protège) ;
- un rôle relié porte le **nom et la couleur de son groupe** (bot 0.2.5) : un
  groupe renommé ou recoloré sur le site l'est aussi sur le serveur. Un rôle relié à plusieurs groupes
  garde les siens, et un groupe sans couleur laisse celle du rôle. Le réglage « Donner aux rôles
  reliés le nom et la couleur de leur groupe » l'éteint.

Le bot applique un changement de groupe dans la demi-minute, l'arrivée d'un membre lié sur le serveur
et la liaison d'un compte aussitôt, et repasse sur tout le monde au démarrage, à chaque changement de
réglage et à l'intervalle choisi (dix minutes par défaut).

## Le compte et l'apparence : `/forum`

Un membre lie son compte en se connectant au site par Discord, depuis *Mon compte → Mes comptes liés*,
ou **depuis Discord** :

- `/forum account link` donne un lien à usage unique, valable quinze minutes. Sur le site, connecté,
  le membre confirme la liaison — et peut reprendre à son nom ce qu'il avait publié depuis Discord ;
- `/forum account unlink` délie, sauf si Discord est son seul moyen de se connecter au site.

Quelqu'un qui n'a pas relié son compte choisit comment il paraît sur le forum du site :

- `/forum visibility public` — sous son pseudo Discord ;
- `/forum visibility guest` — sous un nom anonyme ;
- `/forum visibility custom` — sous un pseudo de son choix (2 à 32 caractères, changeable tous les
  sept jours, jamais celui d'un membre du site) ;
- `/forum visibility status` — ce qui est en place.

Ses messages déjà publiés suivent son choix. Toutes les réponses sont privées, dans la langue Discord
de chacun.

## Le forum et les salons Forum

*Discord → Salons et forums* relie un forum du site à un **salon Forum** de Discord (pas un salon
texte). Deux modes, salon par salon :

- **Tout** : chaque fil du salon devient un sujet du site, et chaque sujet du forum un fil ;
- **À la demande** : un fil ne passe sur le site que quand quelqu'un qui peut *gérer les fils* du salon
  y pose la **réaction** choisie (📌 par défaut) — le fil entier, puis la suite. Les sujets du site,
  déjà publics, vont toujours sur Discord.

Ce qui suit, et comment :

| | Du site vers Discord | De Discord vers le site |
|---|---|---|
| Nouveau sujet / fil | un fil, sous le nom et l'avatar de l'auteur | un sujet |
| Réponse | un message dans le fil | une réponse (la citation d'un message suit) |
| Modification | le message est réécrit, le titre du fil suit | le message est réécrit |
| Suppression | le message disparaît ; un sujet supprimé emporte son fil | le message passe à la corbeille du forum |
| Préfixe / étiquette | l'étiquette correspondante est posée | le préfixe correspondant est posé |

- **Préfixes et étiquettes** (bouton de chaque salon relié) : un préfixe du forum ↔ une étiquette du
  salon. La mise en place les relie d'elle-même.
- Un message venu de Discord est publié sous le **compte du membre** qui a lié son Discord ; sinon
  sous son **identité Discord**, comme il l'a choisi avec `/forum visibility`, marquée du logo Discord.
- Les **sanctions de modération** du site suivent le membre lié : muet ou banni du forum, ses messages
  Discord ne sont pas recopiés ; privé de liens externes, un message qui en porte non plus (bot 0.2.5).
  Le journal du bot le note, sans rien dire dans le salon ; `/bug` et `/idee` répondent à l'auteur seul.
- Un message trop long pour Discord (2 000 caractères) y est coupé, avec un lien vers la suite.
- Les **images** d'un message du site partent sur Discord en aperçus, sous le texte ; les liens du
  message n'y affichent pas de carte d'aperçu.
- Une **image jointe sur Discord** (JPEG, PNG, GIF ou WebP, 5 Mo au plus) est gardée sur le site et
  s'affiche dans le message du forum — un site trop ancien pour la recevoir garde le lien. Les autres
  pièces jointes, et toutes celles du Bugtracker, sont signalées par un lien vers le message Discord.
- Réglages : un lien vers le site sous chaque sujet recopié, et le **rattrapage** au démarrage de ce
  qui s'est écrit sur Discord pendant que le bot était éteint.
- Les **permissions** de chaque salon relié suivent les droits de son forum sur le site (bot 0.2.5,
  réglage allumé par défaut). Tout le serveur compte comme les membres du site : qui ne peut pas lire
  le forum ne voit pas le salon, qui ne peut pas y écrire n'y poste pas — l'écriture en mode **Tout**
  seulement ; en mode **À la demande**, Discord reste un lieu de discussion. Un rôle relié rend à son
  groupe ce que les membres n'ont pas : le salon d'un forum de l'équipe n'est montré qu'aux rôles de
  l'équipe. Le bot ne touche qu'à la vue et à l'écriture, pour @everyone, les rôles reliés et
  lui-même ; un autre réglage fait à la main reste.
- Le site, lui, refuse d'écrire au nom de qui n'en a pas le droit : un message venu de Discord dans un
  forum où son auteur ne peut pas écrire reste sur Discord, et le journal du bot le dit.
- Ne sont pas reportés : la suppression d'un fil entier sur Discord (le sujet reste, le journal le
  signale), celle de son message d'ouverture, et sur Discord la modification d'un message écrit sur
  Discord (il appartient à son auteur). La copie Discord d'un message du site, effacée par un
  modérateur, n'efface pas l'original.

## Le Bugtracker

Dans les réglages de la fonctionnalité, choisis le **salon Forum des tickets** (pas un salon déjà relié
à un forum). Le bot y crée les étiquettes des types (Bogue, Idée, Question, Autre) et des statuts
(Ouvert, En cours, Résolu, Fermé, Ne sera pas fait, Doublon), dans la langue du site.

Pour tenir les idées à part des bogues, choisis aussi un **salon Forum des suggestions** (bot 0.2.5) :
les tickets de type Idée y ont leur fil, avec l'étiquette Idée et celles des statuts ; les autres
restent dans le salon des tickets.

- Chaque ticket devient un **fil**, sous le nom et l'avatar de son auteur, avec son numéro, son type,
  sa priorité et un lien vers le site. Les tickets encore ouverts reçoivent leur fil quand tu choisis
  le salon — et, quand tu choisis le salon des suggestions, les idées encore ouvertes y passent.
- Un ticket qui **change de type** (un bogue devenu idée) change de salon : Discord ne déplace pas un
  fil, le bot en ouvre donc un nouveau dans le bon salon. L'ancien fil, verrouillé, y renvoie, et le
  nouveau renvoie à l'ancien. Sur le site, le ticket garde tous ses commentaires.
- Quand le ticket change sur le site, le fil suit : étiquettes, titre, description. Il **s'archive**
  quand le ticket est clos ; un doublon renvoie à son ticket d'origine ; un ticket supprimé emporte
  son fil.
- Les **commentaires** passent dans les deux sens. Sur Discord, celui qui n'a pas relié son compte
  commente sous son pseudo Discord.
- `/bug` et `/idee` ouvrent un ticket par une petite fenêtre (titre, description), et un fil ouvert à
  la main dans l'un des salons devient un ticket — une idée dans le salon des suggestions. Un ticket
  appartient à un membre : il faut avoir relié son compte (le bot le rappelle sinon).
- Le **site fait foi** : une étiquette changée à la main sur Discord est remise comme le dit le ticket.

## Les rôles temporaires : `/role`

Réservée à qui peut **gérer les rôles** sur le serveur :

- `/role give` — donner un rôle à un membre pour une durée (minutes, heures, jours, semaines), avec
  une raison si l'on veut. Donné de nouveau, il est prolongé ;
- `/role remove` — le retirer tout de suite ;
- `/role list` — ceux en cours, pour tout le serveur ou pour un membre.

Le bot retire le rôle à l'échéance (vérifiée chaque minute), **même après un redémarrage** : la liste
vit sur le site, dans *Discord → Rôles temporaires*, où **Retirer maintenant** avance l'échéance. Un
membre qui quitte puis rejoint le serveur avant la fin retrouve son rôle (réglage *Redonner le rôle*).

Sont refusés : `@everyone` et les rôles tenus par une intégration, les rôles **reliés à un groupe** du
site (la synchronisation des groupes les gère), et — comme Discord le fait pour ce droit — un rôle
placé au-dessus du bot, ou au niveau de celui qui le donne ou au-dessus. La durée est plafonnée par le
réglage *Durée maximale* (90 jours par défaut, un an au plus).

## Sécurité

- Sur la machine du bot : l'adresse du site et sa clé d'accès, rien d'autre.
- La clé du bot est chiffrée dans la base du site avec la clé propre au site. Seule une clé d'accès qui
  a le droit `discord:bot` peut la lire — celle que l'administration crée pour le bot, révocable à
  tout moment.
- Le bot ne mentionne jamais personne : un message recopié ne peut pas notifier `@everyone`.
- Ce qui arrive de Discord — un nom de fil, un pseudo, un commentaire — est rangé sur le site comme ce
  qu'on y écrit soi-même : il s'affiche, il ne s'exécute pas.

## Écrire une fonctionnalité

Le bot s'étend par **fonctionnalités**, chacune dans un dossier, en TypeScript. La marche à suivre — le
contrat d'une fonctionnalité, ses réglages, ses commandes, ses textes traduits, ses tests — est dans le
guide du contributeur du bot, `CONTRIBUTING.md`, à la racine de son code :
[NeoFragReborn/bot-discord](https://github.com/NeoFragReborn/bot-discord).
