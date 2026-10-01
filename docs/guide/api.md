# L'API REST

L'API permet à un **programme** — le bot Discord de NeoFrag Reborn, un script, une intégration —
de lire les données du site et d'écrire sur son forum, sans navigateur. Elle répond en **JSON**, à des adresses **versionnées**
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

Les textes (titres de forums, de groupes) sont rendus dans la **langue par défaut** du site.

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
| `PATCH /api/v1/forum/messages/{id}` | `forum:write` | Modifie un message — au nom de son auteur seulement |
| `DELETE /api/v1/forum/messages/{id}` | `forum:write` | Met un message à la corbeille du forum — au nom de son auteur ; pas le premier message d'un sujet |
| `GET /api/v1/discord/config` | `discord:bot` | La configuration du bot : `token` (sa clé Discord, déchiffrée), `client_id`, `guild_id`, `running`, `nicknames`, `channels`, `roles`, `version` (le numéro qui change à chaque réglage), `events_cursor`, `api_token_id`, `texts` (les textes qu'il poste, dans la langue du site) |
| `POST /api/v1/discord/heartbeat` | `discord:bot` | Le signe de vie du bot (`version`, `connected`, `intents`, `guild` : le serveur, ses salons et ses rôles) ; rend `running`, `version` et les `commands` en attente (`restart`) |
| `POST /api/v1/discord/logs` | `discord:bot` | Des lignes de son journal : `entries`, chacune `level`, `message`, `template` et `args` (le modèle que l'administration traduit) |
| `GET /api/v1/discord/members` | `discord:bot` | Les membres qui ont lié leur Discord : `discord_id`, `member_id`, `username`, `groups` |
| `GET /api/v1/discord/links?type=topic\|message&site_id=…` (ou `discord_id=…`) | `discord:bot` | Le lien d'un sujet et de son fil, d'un message et du sien ; 404 `link_not_found` s'il n'y en a pas |
| `POST /api/v1/discord/links` | `discord:bot` | Garde un lien : `type`, `site_id`, `discord_id` |

Les adresses `discord/*` répondent 404 `module_unavailable` si le module Discord n'est pas installé.

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
même nettoyage que le reste du site. Un sujet **verrouillé** refuse les réponses (409
`topic_locked`). Modifier ou supprimer exige le **même auteur** que le message (403 `not_author`
sinon) : la personne qui l'a écrit le corrige. Une requête mal formée rend 400 `invalid_json` ; un
champ invalide, 422 `validation_failed` avec le détail dans `error.fields`.

Les écritures de l'API apparaissent dans le fil d'événements avec `source.token_id` : un programme
qui recopie le forum ailleurs reconnaît ainsi ce qu'il a lui-même écrit.

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
| `user.groups.changed` | `user_id` — relire le membre pour ses groupes à jour |

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
| 403 | `forbidden`, `not_author` | La clé n'a pas le droit demandé par l'adresse ; l'auteur donné n'est pas celui du message |
| 400 | `invalid_json` | Le corps de la requête n'est pas un JSON valide |
| 404 | `not_found`, `member_not_found`, `topic_not_found`, `message_not_found`, `module_unavailable` | Adresse inconnue ; membre, sujet ou message absent ; module (le forum) non installé |
| 409 | `topic_locked`, `is_first_message` | Sujet verrouillé ; suppression du premier message d'un sujet |
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
  révocation est inscrite au journal d'audit (*Monitoring*).
- L'API ne pose ni cookie ni session, et ne sert jamais une page : seulement du JSON.
- Garde la clé côté serveur (variable d'environnement, fichier non versionné), jamais dans du code
  publié ni dans une page web.
