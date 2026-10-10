# L'API REST

L'API permet à un **programme** — le bot Discord de NeoFrag Reborn, un script, une intégration —
de lire les données du site et d'écrire sur son forum ou son Bugtracker, sans navigateur. Elle répond en **JSON**, à des adresses **versionnées**
(`/api/v1/…`), et n'accepte que les programmes munis d'une **clé d'accès** créée par un administrateur.

Elle est livrée par le module **API**, optionnel : *Système → Thèmes & Addons* pour l'installer s'il
ne l'est pas.

## Créer une clé d'accès

*Administration → API → Nouvelle clé*. Donne-lui un **nom** qui dise qui s'en sert (« Bot Discord »)
et ne coche que les **droits** dont le programme a besoin :

| Droit | Ce qu'il ouvre |
|---|---|
| `members:read` | Les membres (par identifiant ou par compte Discord lié) et la liste des groupes |
| `forum:read` | Le forum : son arborescence, ses sujets, ses messages — catégories réservées comprises |
| `forum:write` | Écrire sur le forum **au nom** d'un membre ou d'un compte Discord : créer un sujet, répondre, modifier ou supprimer un message de cet auteur |
| `events:read` | Le fil d'événements (nouveau sujet, nouveau message, changement de groupe…) |
| `bugtracker:read` | Le Bugtracker : ses tickets et leurs commentaires |
| `bugtracker:write` | Écrire dans le Bugtracker **au nom** d'un membre ou d'un compte Discord : ouvrir un ticket, commenter, modifier ou supprimer un commentaire de cet auteur |
| `discord:bot` | Être le bot Discord du site : sa configuration — **clé Discord comprise** —, son état, son journal. Le module Discord crée lui-même cette clé (voir [Le bot Discord](bot-discord.md)) |

La clé (`nfr_` suivi de 40 caractères) n'est **affichée qu'une fois**, juste après sa création : le
site n'en garde que l'empreinte. Une clé perdue ne se retrouve pas — on la **révoque** et on en crée
une autre. La liste des clés montre leur dernier usage et l'adresse d'où il venait.

## Appeler l'API

Chaque requête porte la clé dans l'en-tête `Authorization` :

```bash
curl -H "Authorization: Bearer nfr_…" https://ton-site.example/api/v1/status
```

Une réponse réussie enveloppe ses données dans `data` :

```json
{
    "data": {
        "site": "Mon équipe",
        "version": "1.2.9",
        "api": "v1",
        "token": { "name": "Bot Discord", "scopes": ["members:read", "forum:read"] }
    }
}
```

Les textes (titres de forums, de groupes) sont rendus dans la **langue par défaut** du site, et **en
clair** : « é », pas `&eacute;` comme le site les range pour ses pages. À l'inverse, ce qu'un programme
écrit est rangé comme ce qu'on écrit sur le site : une balise dans un titre s'affiche, elle ne
s'exécute pas.

## Les adresses de la version 1

| Méthode et adresse | Droit | Rend |
|---|---|---|
| `GET /api/v1/status` | aucun | Le site, sa version, et la clé utilisée avec ses droits — pour vérifier une configuration |
| `GET /api/v1/members/{id}` | `members:read` | Un membre : `id`, `username`, `url`, `avatar`, `registration_date`, `groups` (clés de groupes), `discord` (`id`, `username`) ou `null` |
| `GET /api/v1/members/discord/{id}` | `members:read` | Le membre qui a lié ce compte Discord (même forme) |
| `GET /api/v1/groups` | `members:read` | Les groupes : `key`, `title`, `color`, `icon`, `hidden`, `auto` (groupe calculé par le site), `members` (effectif) |
| `GET /api/v1/forums` | `forum:read` | Les catégories, chacune avec ses forums et leurs sous-forums : `id`, `title`, `description`, `icon`, `topics`, `replies`, `url`, et `link` pour un forum qui renvoie ailleurs |
| `GET /api/v1/forum/topics/{id}` | `forum:read` | Un sujet : `forum_id`, `title`, `author` (`id`, `username`) ou `null`, `external_author` (`provider`, `external_id`, `name`) pour un compte Discord non lié, `created_at`, `announce`, `locked`, `prefix`, `first_message_id`, `last_message_id`, `solution_message_id`, `replies`, `views`, `url` |
| `GET /api/v1/forum/messages/{id}` | `forum:read` | Un message : `topic_id`, `forum_id`, `author`, `external_author`, `created_at`, `is_first`, `deleted`, `html` (tel que le forum l'affiche), `text` (texte brut), `url` — un message supprimé rend `deleted: true` sans contenu |
| `GET /api/v1/events?after={id}&limit={n}` | `events:read` | Le fil d'événements (voir ci-dessous) |
| `POST /api/v1/forum/topics` | `forum:write` | Crée un sujet (voir « Écrire sur le forum ») et rend le sujet, en **201** |
| `POST /api/v1/forum/topics/{id}/messages` | `forum:write` | Répond à un sujet et rend le message, en 201 |
| `POST /api/v1/forum/images?name={nom}` | `forum:write` | Garde une image pour un message : le corps de la requête **est** l'image (JPEG, PNG, GIF ou WebP, 5 Mo au plus, contrôlée et ré-encodée comme celles de l'éditeur). Rend `path` — à écrire dans le contenu, `![nom](path)` — et `url`, en 201 la première fois ; la même image renvoyée rend la même adresse |
| `PATCH /api/v1/forum/messages/{id}` | `forum:write` | Modifie un message — au nom de son auteur seulement |
| `DELETE /api/v1/forum/messages/{id}` | `forum:write` | Met un message à la corbeille du forum — au nom de son auteur ; pas le premier message d'un sujet |
| `PATCH /api/v1/forum/topics/{id}` | `forum:write` | Change le préfixe d'un sujet : `prefix_id` (`null` pour aucun) ; rend le sujet |
| `GET /api/v1/bugtracker/tickets?after={id}&limit={n}&open=1` | `bugtracker:read` | Les tickets dans l'ordre de leur numéro, par lots (50 par défaut, 100 au plus) ; les seuls ouverts avec `open=1`. Rend `tickets`, `next` (le curseur suivant) et `more` |
| `GET /api/v1/bugtracker/tickets/{id}` | `bugtracker:read` | Un ticket : `title`, `description`, `type` (`bug`, `feature`, `question`, `other`), `priority`, `status`, `duplicate_of`, `author`, `created_at`, `updated_at`, `url` |
| `GET /api/v1/bugtracker/comments/{id}` | `bugtracker:read` | Un commentaire : `ticket_id`, `content`, `author`, `external_author` (un compte Discord non lié), `status_change`, `created_at` |
| `POST /api/v1/bugtracker/tickets` | `bugtracker:write` | Ouvre un ticket (voir « Écrire dans le Bugtracker ») et le rend, en 201 |
| `POST /api/v1/bugtracker/tickets/{id}/comments` | `bugtracker:write` | Commente un ticket et rend le commentaire, en 201 |
| `PATCH /api/v1/bugtracker/comments/{id}` | `bugtracker:write` | Modifie un commentaire — au nom de son auteur seulement |
| `DELETE /api/v1/bugtracker/comments/{id}` | `bugtracker:write` | Supprime un commentaire — au nom de son auteur seulement |

Les adresses `bugtracker/*` répondent 404 `module_unavailable` si le Bugtracker n'est pas installé.

### Les adresses du bot Discord

Elles demandent le droit `discord:bot` et répondent 404 `module_unavailable` si le module Discord
n'est pas installé. Elles servent au bot de NeoFrag Reborn ; un autre programme n'en a pas l'usage.

| Méthode et adresse | Rend |
|---|---|
| `GET /api/v1/discord/config` | La configuration du bot : `token` (sa clé Discord, déchiffrée), `client_id`, `guild_id`, `running`, `channels`, `roles` (chacun `group_key`, `role_id`, et le `name` et la `color` de son groupe), `tags` (préfixe ↔ étiquette), `features` (chaque fonctionnalité allumée ou non, et ses réglages), `lang`, `i18n` (les traductions des textes qu'il affiche), `version` (le numéro qui change à chaque réglage), `events_cursor`, `api_token_id`, `texts` |
| `POST /api/v1/discord/heartbeat` | Le signe de vie du bot (`version`, `connected`, `intents`, `guild` : le serveur, ses salons, leurs étiquettes et ses rôles ; `features` : ses fonctionnalités et leurs réglages ; `texts` : ses textes à traduire ; `events_cursor`) ; rend `running`, `version` et les `commands` en attente, chacune `type` (`restart`, `resync`, `setup`, `setup-undo`) et `data` |
| `POST /api/v1/discord/logs` | Des lignes de son journal : `entries`, chacune `level`, `message`, `template` et `args` (le modèle que l'administration traduit) |
| `GET /api/v1/discord/members` | Les membres qui ont lié leur Discord : `discord_id`, `member_id`, `username`, `groups` |
| `GET /api/v1/discord/links?type=…&site_id=…` (ou `discord_id=…`) | Le lien d'un élément du site et de son pendant Discord — `type` : `topic`, `message`, `ticket` ou `comment` ; 404 `link_not_found` s'il n'y en a pas |
| `POST /api/v1/discord/links` | Garde un lien : `type`, `site_id`, `discord_id` ; avec `replace: true`, celui que l'élément du site avait déjà cède la place (un ticket qui change de salon) |
| `POST /api/v1/discord/links/lookup` | Lesquels de ces éléments Discord (`type`, `discord_ids`, 500 au plus) ont déjà leur pendant sur le site : `found` |
| `POST /api/v1/discord/link-request` | `/forum account link` : un lien à usage unique vers le site (`url`, `expires_in`), ou `linked` et `member` si ce Discord est déjà lié |
| `POST /api/v1/discord/unlink` | `/forum account unlink` : délie ce Discord (409 `not_linked`, `only_login_method`) |
| `GET /api/v1/discord/identity/{discord_id}` | Comment ce compte paraît sur le forum : `linked` et `member`, ou `mode` (`public`, `guest`, `custom`), `name`, `custom_name`, `custom_change_at` |
| `PATCH /api/v1/discord/identity/{discord_id}` | `/forum visibility` : `mode`, `username`, `custom_name` (409 `linked`, `invalid_name`, `name_taken`, `too_soon`) |
| `POST /api/v1/discord/setup`, `POST /api/v1/discord/setup/undo` | Le compte rendu d'une mise en place du serveur, ou de son annulation : le site pose ou retire les correspondances |
| `GET /api/v1/discord/timed-roles?discord_id=…&due=1` | Les rôles temporaires en cours — ceux d'un membre, ou arrivés à échéance avec `due=1` : `timed_roles`, chacun `timed_id`, `discord_id`, `username`, `role_id`, `expires_at` (horodatage Unix), `given_by`, `given_by_name`, `reason` |
| `POST /api/v1/discord/timed-roles` | Donne ou prolonge un rôle temporaire : `discord_id`, `username`, `role_id`, `duration` (secondes, de 60 à un an), `given_by`, `given_by_name`, `reason` ; 409 `role_mapped` pour un rôle relié à un groupe du site |
| `DELETE /api/v1/discord/timed-roles/{id}` | Retire un rôle temporaire de la liste |

## Écrire sur le forum

L'API écrit toujours **au nom d'un auteur**, donné dans le corps JSON de la requête :

- `"author": { "member_id": 7 }` — un membre du site ;
- `"author": { "discord": { "id": "123456789012345678", "username": "Pseudo", "avatar": "https://cdn.discordapp.com/…" } }`
  — un compte Discord. S'il est **lié** à un membre (connexion par Discord), le message est publié sous
  ce membre : son vrai compte, son pseudo, son avatar. Sinon, il est publié sous l'**identité Discord**
  de la personne : son pseudo Discord, marqué du logo Discord, sans lien vers un profil du site.

```bash
curl -X POST -H "Authorization: Bearer nfr_…" -H "Content-Type: application/json"      -d '{"forum_id": 3, "title": "Ma question", "content": "**Bonjour** à tous", "author": {"discord": {"id": "123456789012345678", "username": "Pseudo"}}}'      https://ton-site.example/api/v1/forum/topics
```

| Champ | Pour | Contenu |
|---|---|---|
| `forum_id` | sujet | Le forum où publier (pas un forum qui renvoie ailleurs) |
| `title` | sujet | 1 à 100 caractères |
| `prefix_id` | sujet | Facultatif : un préfixe de sujet du forum |
| `content` | tout | Le texte, 1 à 20 000 caractères |
| `format` | tout | `markdown` (par défaut — le format de Discord) ou `html` |
| `reply_to` | réponse | Facultatif : le message auquel on répond |
| `author` | tout | L'auteur (ci-dessus) |

Le Markdown est converti en HTML ; tout HTML qu'on y glisse est **échappé**, et le HTML passe par le
même nettoyage que le reste du site. L'auteur doit avoir le **droit d'écrire** dans le forum, comme sur
le site (403 `forum_forbidden` sinon) : un membre, ou un compte Discord lié, par ses droits sur la
catégorie ; un compte Discord sans compte du site, par ceux des membres. Un sujet **verrouillé** refuse
les réponses (409 `topic_locked`). Modifier ou supprimer exige le **même auteur** que le message (403 `not_author`
sinon) : la personne qui l'a écrit le corrige. Une requête mal formée rend 400 `invalid_json` ; un
champ invalide, 422 `validation_failed` avec le détail dans `error.fields`.

Les **sanctions de modération** valent aussi par l'API : un membre muet ou banni du forum (ou du site)
n'y écrit pas (403 `sanctioned`), et un membre privé de liens externes ne publie pas un texte qui en
porte (403 `links_forbidden`). `error.message` dit la sanction, à montrer à l'auteur. Un compte Discord
sans compte du site n'a pas de sanction.

Les écritures de l'API apparaissent dans le fil d'événements avec `source.token_id` : un programme
qui recopie le forum ailleurs reconnaît ainsi ce qu'il a lui-même écrit.

## Écrire dans le Bugtracker

Comme sur le forum, l'API écrit **au nom d'un auteur** (`author`, même forme). Un **ticket** appartient
à un membre : un compte Discord qui n'est lié à aucun membre est refusé (409 `not_linked`). Un
**commentaire**, lui, peut venir d'un compte Discord non lié : il paraît sous son pseudo Discord.
Les sanctions de modération s'appliquent comme sur le forum (403 `sanctioned`, `links_forbidden`).

| Champ | Pour | Contenu |
|---|---|---|
| `title` | ticket | 1 à 200 caractères |
| `description` | ticket | Le texte, 1 à 20 000 caractères |
| `type` | ticket | `bug` (par défaut), `feature`, `question` ou `other` |
| `content` | commentaire | Le texte, 1 à 20 000 caractères |
| `author` | tout | L'auteur |

Modifier ou supprimer un commentaire exige le **même auteur** (403 `not_author` sinon).

## Le fil d'événements

Plutôt que d'interroger tout le site à intervalles réguliers, un programme lit le **fil d'événements** :
« qu'est-ce qui s'est passé depuis l'événement n° X ? ». Le site n'a pas à le contacter — le programme
n'ouvre aucun port.

```bash
curl -H "Authorization: Bearer nfr_…" "https://ton-site.example/api/v1/events?after=0&limit=100"
```

```json
{
    "data": {
        "events": [
            {
                "id": 41,
                "type": "forum.post.created",
                "data": { "message_id": 512, "topic_id": 88, "forum_id": 3, "user_id": 7, "is_starter": false },
                "source": null,
                "created_at": "2026-10-01T17:42:03+02:00"
            }
        ],
        "next": 41,
        "more": false
    }
}
```

Garde la valeur de `next` et redonne-la en `after` la fois suivante. Tant que `more` vaut `true`, il
reste des événements à lire tout de suite. Les événements restent **30 jours** dans le fil.

| `type` | `data` |
|---|---|
| `forum.topic.created` | `topic_id`, `message_id`, `forum_id`, `user_id`, `identity_id` |
| `forum.post.created` | `message_id`, `topic_id`, `forum_id`, `user_id`, `identity_id`, `is_starter` (le premier message d'un sujet) |
| `forum.post.edited` | `message_id`, `topic_id`, `forum_id`, `user_id`, `is_topic` |
| `forum.post.deleted` | `message_id`, `topic_id`, `forum_id`, `is_topic`, `hard_delete`, `deleted_by` |
| `forum.topic.split` | `source_topic_id`, `new_topic_id`, `message_ids`, `forum_id` |
| `forum.topics.merged` | `source_topic_id`, `target_topic_id`, `forum_id` |
| `forum.topic.prefixed` | `topic_id`, `forum_id`, `prefix_id` — le préfixe d'un sujet a changé |
| `bugtracker.ticket.created` | `ticket_id`, `user_id`, `type` |
| `bugtracker.ticket.updated` | `ticket_id`, `status`, `type`, `fields` (les champs qui ont changé) |
| `bugtracker.ticket.deleted` | `ticket_id` |
| `bugtracker.comment.created`, `.edited`, `.deleted` | `comment_id`, `ticket_id` (et `user_id` à la création) |
| `user.groups.changed` | `user_id` — relire le membre pour ses groupes à jour |
| `user.discord.linked`, `user.discord.unlinked` | `user_id`, `discord_id` — un compte Discord vient d'être lié ou délié |

Un événement ne porte que des **identifiants** : le contenu se lit par les adresses ci-dessus, au
moment voulu (un message supprimé entre-temps rend `deleted: true`). `source` dit d'où vient le
changement : `null` pour le site, `{ "token_id": … }` pour une écriture faite par une clé de l'API — un
programme reconnaît ainsi ses propres écritures, et ne les recopie pas une seconde fois.

## Les erreurs

Une erreur rend le code HTTP qui convient et un objet `error`, dont le `code` est **stable** (un
programme peut s'y fier) et le `message` lisible :

```json
{ "error": { "code": "forbidden", "message": "Cette clé n’a pas le droit « forum:read »." } }
```

| HTTP | `code` | Quand |
|---|---|---|
| 401 | `unauthorized` | Clé absente, inconnue ou révoquée (avec l'en-tête `WWW-Authenticate: Bearer`) |
| 403 | `forbidden`, `not_author`, `forum_forbidden`, `sanctioned`, `links_forbidden` | La clé n'a pas le droit demandé par l'adresse ; l'auteur donné n'est pas celui du message ; l'auteur n'a pas le droit d'écrire dans ce forum ; une sanction de modération l'en empêche, ou lui interdit le lien que porte son texte |
| 400 | `invalid_json` | Le corps de la requête n'est pas un JSON valide |
| 404 | `not_found`, `member_not_found`, `topic_not_found`, `message_not_found`, `ticket_not_found`, `comment_not_found`, `link_not_found`, `module_unavailable` | Adresse inconnue ; élément absent ; module (le forum, le Bugtracker, Discord) non installé |
| 409 | `topic_locked`, `is_first_message`, `not_linked`, `role_mapped`, … | Sujet verrouillé ; suppression du premier message d'un sujet ; compte Discord non lié ; rôle relié à un groupe — et les refus propres aux adresses du bot, dits avec elles |
| 422 | `validation_failed` | Un champ est invalide — le détail est dans `error.fields` |
| 405 | `method_not_allowed` | Mauvaise méthode (l'en-tête `Allow` dit laquelle) |
| 429 | `too_many_requests` | Limite de débit atteinte (l'en-tête `Retry-After` dit dans combien de secondes réessayer) |

## La limite de débit

Une clé peut faire **120 requêtes par minute** ; chaque réponse dit combien il en reste
(`X-RateLimit-Remaining`). Au-delà, l'API répond 429 pendant une minute. Une adresse IP qui envoie
**20 clés refusées** en une minute est bloquée cinq minutes : rien ne permet de marteler l'API pour
deviner une clé.

## Sécurité

- Le site ne garde que l'**empreinte** des clés : une fuite de la base ne donne aucune clé utilisable.
- Une clé n'ouvre que ses **droits**, et se **révoque** d'un clic ; chaque création et chaque
  révocation est inscrite au journal d'audit (*Utilisateurs → Journal d'audit*).
- L'API ne pose ni cookie ni session, et ne sert jamais une page : seulement du JSON.
- Garde la clé côté serveur (variable d'environnement, fichier non versionné), jamais dans du code
  publié ni dans une page web.
