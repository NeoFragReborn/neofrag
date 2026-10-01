# Le bot Discord

Le bot Discord de NeoFrag Reborn relie ton site et ton serveur Discord :

- **les rôles et les pseudos** — un membre qui a lié son compte Discord reçoit sur le serveur les rôles
  reliés à ses groupes, et son pseudo du site si tu le veux ;
- **le forum** — un forum du site et un salon Forum de Discord vivent ensemble : un sujet devient un
  fil, un fil devient un sujet, et les réponses suivent dans les deux sens.

Il se **règle entièrement depuis l'administration** du site : sa clé, son serveur, l'interrupteur
marche / pause, le redémarrage, les correspondances. L'administration montre aussi son état et son
journal, dans ta langue.

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
   - **Server Members Intent** — pour les rôles et les pseudos ;
   - **Message Content Intent** — pour recopier le texte des messages Discord sur le forum.

   Sans eux, le bot fonctionne en mode réduit et le dit dans son journal : il ne demande jamais à
   Discord plus que ce que l'application a le droit de recevoir.

## 2. Régler le module Discord

Le bot demande deux modules optionnels du site, **API** et **Discord** (*Système → Thèmes & Addons*
pour les installer).

*Administration → Discord → Connexion* :

| Champ | Où le trouver |
|---|---|
| Identifiant de l'application | l'*Application ID* de l'étape 1 |
| Identifiant du serveur | dans Discord, mode développeur activé : clic droit sur le serveur → *Copier l'identifiant du serveur* |
| Clé du bot | le token de l'étape 1. Il est **gardé chiffré** et n'est plus jamais affiché ; laisse le champ vide pour garder celui qui est enregistré |
| Pseudos | donner aux membres liés leur pseudo du site sur le serveur |

Puis **Inviter le bot** (même page d'accueil du module) : le lien ajoute le bot au serveur avec ses
seules permissions — jamais « Administrateur » :

| Permission | Pour |
|---|---|
| Voir les salons, Envoyer des messages, Lire l'historique | lire et écrire dans les salons reliés |
| Envoyer des messages dans les fils, Créer des fils publics, Gérer les fils | recopier un sujet du site en fil, renommer ou supprimer le fil |
| Gérer les messages | reporter sur Discord une suppression faite sur le site |
| Gérer les webhooks | poster les messages du site sous le nom et l'avatar de leur auteur |
| Ajouter des réactions, Intégrer des liens, Joindre des fichiers | des messages complets |
| Gérer les rôles, Gérer les pseudos | les rôles et les pseudos |
| Utiliser les commandes de l'application | les commandes à venir |

Dans les réglages du serveur (*Rôles*), place le rôle du bot **au-dessus** des rôles qu'il donne :
Discord interdit à un bot de donner un rôle placé plus haut que le sien.

## 3. Installer le bot sur sa machine

Chaque version de NeoFrag Reborn a son archive du bot, à côté de celle du site
(`neofrag-reborn-bot-X.Y.Z.tar.gz`) ; le code est aussi dans le dossier `bot/` du dépôt.

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

## 4. Le piloter depuis l'administration

*Administration → Discord* montre :

- son **état** — *En ligne*, *En pause* (vivant, interrupteur coupé), *Connexion à Discord…*, ou
  *Hors ligne* (aucune nouvelle depuis une minute et demie) —, sa version, son dernier signe de vie ;
- les boutons **Mettre en marche / Mettre en pause** et **Redémarrer**, qu'il applique dans la
  minute ;
- son **journal** : connexions, synchronisations, et ce qui l'empêche de fonctionner (intent non
  coché, rôle mal placé, permission manquante), avec la marche à suivre.

**Changer la clé du bot** (fuite, régénération) : colle la nouvelle dans *Connexion*. Le bot la relit
et se reconnecte tout seul ; la machine n'est pas touchée.

## Les rôles et les pseudos

*Discord → Groupes et rôles* relie un groupe du site à un rôle du serveur. Le **site fait foi** :

- un membre lié qui est dans le groupe reçoit le rôle ; qui n'y est plus le perd ;
- un rôle que rien ne relie n'est **jamais touché** ; un membre qui n'a pas lié son compte non plus ;
- avec l'option *Pseudos*, le membre lié porte sur le serveur son pseudo du site (sauf le
  propriétaire du serveur, que Discord protège).

Un membre lie son compte en se connectant au site par Discord, ou depuis *Mon compte → Mes comptes
liés*. Le bot applique un changement de groupe dans la demi-minute, l'arrivée d'un membre lié sur le
serveur aussitôt, et repasse sur tout le monde au démarrage, à chaque changement de réglage et toutes
les dix minutes.

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

- Un message venu de Discord est publié sous le **compte du membre** qui a lié son Discord ; sinon
  sous son **identité Discord** : son pseudo, marqué du logo Discord.
- Un message trop long pour Discord (2 000 caractères) y est coupé, avec un lien vers la suite.
- Les pièces jointes de Discord sont signalées sur le site par un lien vers le message Discord.
- Ne sont pas reportés : la suppression d'un fil entier sur Discord (le sujet reste, le journal le
  signale), celle de son message d'ouverture, et sur Discord la modification d'un message écrit sur
  Discord (il appartient à son auteur).

## Sécurité

- Sur la machine du bot : l'adresse du site et sa clé d'accès, rien d'autre.
- La clé du bot est chiffrée dans la base du site avec la clé propre au site. Seule une clé d'accès qui
  a le droit `discord:bot` peut la lire — celle que l'administration crée pour le bot, révocable à
  tout moment.
- Le bot ne mentionne jamais personne : un message recopié ne peut pas notifier `@everyone`.

## Écrire une fonctionnalité

Le bot est écrit en **TypeScript** avec [discord.js](https://discord.js.org/). Une fonctionnalité est
un dossier de `bot/src/fonctionnalites/`, inscrit dans `bot/src/fonctionnalites/index.ts`, qui
respecte le contrat de `bot/src/fonctionnalites/types.ts` :

```ts
import { GatewayIntentBits } from 'discord.js';
import type { Evenement } from '../../site.js';
import type { Contexte, Fonctionnalite } from '../types.js';

export class Bienvenue implements Fonctionnalite {
    readonly nom = 'bienvenue';
    readonly intents = [GatewayIntentBits.GuildMembers] as const;  // en plus de ceux de base
    readonly evenements = ['forum.topic.created'] as const;         // le fil d'événements du site

    demarrer(ctx: Contexte): void {
        // ctx.client (discord.js, connecté), ctx.guilde, ctx.site (l'API), ctx.config, ctx.journal
        ctx.journal.info('Bienvenue : prête sur « %s ».', ctx.guilde.name);
    }

    reconfigurer(ctx: Contexte): void { /* l'administration a changé un réglage */ }
    tour(ctx: Contexte): void { /* toutes les 30 secondes environ */ }
    surEvenement(ctx: Contexte, evenement: Evenement): void { /* un événement suivi */ }
    arreter(): void { /* retirer ses minuteries et ses écouteurs */ }
}
```

- Une fonctionnalité qui lève une erreur l'écrit au journal sans emporter le bot ni les autres.
- **Le journal est traduit par le site** : écris un *modèle* et ses valeurs
  (`ctx.journal.warn('Le rôle « %s » est mal placé.', role.name)`), jamais une phrase assemblée.
  Ajoute chaque nouveau modèle à `Discord::textes_du_bot()` (`modules/discord/discord.php`), puis
  `php tools/check-langs.php --fix` et `php tools/fill-langs.php` : un test du bot échoue si un modèle
  manque.
- `npm test` compile et lance les tests (`*.test.ts`, lanceur intégré de Node) ; la CI les joue à
  chaque envoi.
