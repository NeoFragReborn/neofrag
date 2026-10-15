# A API REST

A API permite a um **programa** — o bot do Discord do NeoFrag Reborn, um script, uma integração — ler os dados do site e
escrever no seu fórum ou no seu Bugtracker, sem navegador. Responde em **JSON**, em endereços **versionados**
(`/api/v1/…`), e só aceita os programas que tenham uma **chave de acesso** criada por um administrador.

É fornecida pelo módulo **API**, que é opcional: *Sistema → Temas & Addons* para o instalar se não estiver.

## Criar uma chave de acesso

*Administração → API → Nova chave*. Dá-lhe um **nome** que diga quem a usa («Bot do Discord») e marca só os **direitos**
de que o programa precisa:

| Direito | O que abre |
|---|---|
| `members:read` | Os membros (por ID ou por conta do Discord ligada) e a lista de grupos |
| `forum:read` | O fórum: a sua árvore, os seus tópicos, as suas mensagens — categorias restritas incluídas |
| `forum:write` | Escrever no fórum **em nome de** um membro ou de uma conta do Discord: criar um tópico, responder, editar ou eliminar uma mensagem desse autor |
| `events:read` | O fluxo de eventos (novo tópico, nova mensagem, mudança de grupo…) |
| `bugtracker:read` | O Bugtracker: os seus tickets e os seus comentários |
| `bugtracker:write` | Escrever no Bugtracker **em nome de** um membro ou de uma conta do Discord: abrir um ticket, comentar, editar ou eliminar um comentário desse autor |
| `discord:bot` | Ser o bot do Discord do site: a sua configuração — **token do Discord incluído** —, o seu estado, o seu registo. O módulo Discord cria esta chave ele próprio (vê [O bot do Discord](bot-discord.md)) |

A chave (`nfr_` seguido de 40 caracteres) **só é mostrada uma vez**, logo depois de ser criada: o site só guarda a sua
impressão. Uma chave perdida não se recupera — **revogas** e crias outra. A lista das chaves mostra a sua última
utilização e o endereço de onde veio.

## Chamar a API

Cada pedido leva a chave no cabeçalho `Authorization`:

```bash
curl -H "Authorization: Bearer nfr_…" https://o-teu-site.example/api/v1/status
```

Uma resposta bem-sucedida envolve os seus dados em `data`:

```json
{
    "data": {
        "site": "A minha equipa",
        "version": "1.2.9",
        "api": "v1",
        "token": { "name": "Bot do Discord", "scopes": ["members:read", "forum:read"] }
    }
}
```

Os textos (títulos de fóruns e de grupos) são devolvidos na **língua predefinida** do site, e **em texto simples**: «é»,
não `&eacute;` como o site os guarda para as suas páginas. Inversamente, o que um programa escreve é guardado como o que
se escreve no site: uma etiqueta num título é mostrada, não é executada.

## Os endereços da versão 1

| Método e endereço | Direito | Devolve |
|---|---|---|
| `GET /api/v1/status` | nenhum | O site, a sua versão, e a chave usada com os seus direitos — para verificar uma configuração |
| `GET /api/v1/members/{id}` | `members:read` | Um membro: `id`, `username`, `url`, `avatar`, `registration_date`, `groups` (chaves de grupo), `discord` (`id`, `username`) ou `null` |
| `GET /api/v1/members/discord/{id}` | `members:read` | O membro que ligou esta conta do Discord (mesma forma) |
| `GET /api/v1/groups` | `members:read` | Os grupos: `key`, `title`, `color`, `icon`, `hidden`, `auto` (um grupo calculado pelo site), `members` (número de membros) |
| `GET /api/v1/forums` | `forum:read` | As categorias, cada uma com os seus fóruns e os seus subfóruns: `id`, `title`, `description`, `icon`, `topics`, `replies`, `url`, e `link` para um fórum que redireciona para outro lado |
| `GET /api/v1/forum/topics/{id}` | `forum:read` | Um tópico: `forum_id`, `title`, `author` (`id`, `username`) ou `null`, `external_author` (`provider`, `external_id`, `name`) para uma conta do Discord não ligada, `created_at`, `announce`, `locked`, `prefix`, `first_message_id`, `last_message_id`, `solution_message_id`, `replies`, `views`, `url` |
| `GET /api/v1/forum/messages/{id}` | `forum:read` | Uma mensagem: `topic_id`, `forum_id`, `author`, `external_author`, `created_at`, `is_first`, `deleted`, `html` (como o fórum a mostra), `text` (texto simples), `url` — uma mensagem eliminada devolve `deleted: true` sem conteúdo |
| `GET /api/v1/events?after={id}&limit={n}` | `events:read` | O fluxo de eventos (vê abaixo) |
| `POST /api/v1/forum/topics` | `forum:write` | Cria um tópico (vê «Escrever no fórum») e devolve-o, com **201** |
| `POST /api/v1/forum/topics/{id}/messages` | `forum:write` | Responde a um tópico e devolve a mensagem, com 201 |
| `POST /api/v1/forum/images?name={nome}` | `forum:write` | Guarda uma imagem para uma mensagem: o corpo do pedido **é** a imagem (JPEG, PNG, GIF ou WebP, 5 MB no máximo, verificada e recodificada como as do editor). Devolve `path` — a escrever no conteúdo, `![nome](path)` — e `url`, com 201 da primeira vez; a mesma imagem enviada outra vez devolve o mesmo endereço |
| `PATCH /api/v1/forum/messages/{id}` | `forum:write` | Edita uma mensagem — só em nome do seu autor |
| `DELETE /api/v1/forum/messages/{id}` | `forum:write` | Põe uma mensagem no lixo do fórum — em nome do seu autor; não a primeira mensagem de um tópico |
| `PATCH /api/v1/forum/topics/{id}` | `forum:write` | Muda o prefixo de um tópico: `prefix_id` (`null` para nenhum); devolve o tópico |
| `GET /api/v1/bugtracker/tickets?after={id}&limit={n}&open=1` | `bugtracker:read` | Os tickets pela ordem do seu número, por lotes (50 por predefinição, 100 no máximo); só os abertos com `open=1`. Devolve `tickets`, `next` (o cursor seguinte) e `more` |
| `GET /api/v1/bugtracker/tickets/{id}` | `bugtracker:read` | Um ticket: `title`, `description`, `type` (`bug`, `feature`, `question`, `other`), `priority`, `status`, `duplicate_of`, `author`, `created_at`, `updated_at`, `url` |
| `GET /api/v1/bugtracker/comments/{id}` | `bugtracker:read` | Um comentário: `ticket_id`, `content`, `author`, `external_author` (uma conta do Discord não ligada), `status_change`, `created_at` |
| `POST /api/v1/bugtracker/tickets` | `bugtracker:write` | Abre um ticket (vê «Escrever no Bugtracker») e devolve-o, com 201 |
| `POST /api/v1/bugtracker/tickets/{id}/comments` | `bugtracker:write` | Comenta um ticket e devolve o comentário, com 201 |
| `PATCH /api/v1/bugtracker/comments/{id}` | `bugtracker:write` | Edita um comentário — só em nome do seu autor |
| `DELETE /api/v1/bugtracker/comments/{id}` | `bugtracker:write` | Elimina um comentário — só em nome do seu autor |

Os endereços `bugtracker/*` respondem 404 `module_unavailable` se o Bugtracker não estiver instalado.

### Os endereços do bot do Discord

Exigem o direito `discord:bot` e respondem 404 `module_unavailable` se o módulo Discord não estiver instalado. Servem o
bot do NeoFrag Reborn; a outro programa não servem de nada.

| Método e endereço | Devolve |
|---|---|
| `GET /api/v1/discord/config` | A configuração do bot: `token` (o seu token do Discord, decifrado), `client_id`, `guild_id`, `running`, `channels`, `roles` (cada um `group_key`, `role_id`, e o `name` e a `color` do seu grupo), `tags` (prefixo ↔ etiqueta), `features` (cada funcionalidade ligada ou desligada, e as suas definições), `lang`, `i18n` (as traduções dos textos que mostra), `version` (o número que muda a cada definição), `events_cursor`, `api_token_id`, `texts` |
| `POST /api/v1/discord/heartbeat` | O sinal de vida do bot (`version`, `connected`, `intents`, `guild`: o servidor, os seus canais, as suas etiquetas e os seus cargos; `features`: as suas funcionalidades e as suas definições; `texts`: os seus textos a traduzir; `events_cursor`); devolve `running`, `version` e os `commands` pendentes, cada um `type` (`restart`, `resync`, `setup`, `setup-undo`) e `data` |
| `POST /api/v1/discord/logs` | Linhas do seu registo: `entries`, cada uma `level`, `message`, `template` e `args` (o modelo que a administração traduz) |
| `GET /api/v1/discord/members` | Os membros que ligaram o seu Discord: `discord_id`, `member_id`, `username`, `groups` |
| `GET /api/v1/discord/links?type=…&site_id=…` (ou `discord_id=…`) | A ligação entre um elemento do site e o seu correspondente no Discord — `type`: `topic`, `message`, `ticket` ou `comment`; 404 `link_not_found` se não houver nenhuma |
| `POST /api/v1/discord/links` | Guarda uma ligação: `type`, `site_id`, `discord_id`; com `replace: true`, a que o elemento do site já tinha cede o lugar (um ticket que muda de canal) |
| `POST /api/v1/discord/links/lookup` | Quais destes elementos do Discord (`type`, `discord_ids`, 500 no máximo) já têm o seu correspondente no site: `found` |
| `POST /api/v1/discord/link-request` | `/forum account link`: uma ligação de uso único para o site (`url`, `expires_in`), ou `linked` e `member` se este Discord já estiver ligado |
| `POST /api/v1/discord/unlink` | `/forum account unlink`: desliga este Discord (409 `not_linked`, `only_login_method`) |
| `GET /api/v1/discord/identity/{discord_id}` | Como esta conta aparece no fórum: `linked` e `member`, ou `mode` (`public`, `guest`, `custom`), `name`, `custom_name`, `custom_change_at` |
| `PATCH /api/v1/discord/identity/{discord_id}` | `/forum visibility`: `mode`, `username`, `custom_name` (409 `linked`, `invalid_name`, `name_taken`, `too_soon`) |
| `POST /api/v1/discord/setup`, `POST /api/v1/discord/setup/undo` | O relatório de uma configuração do servidor, ou da sua anulação: o site põe ou retira as correspondências |
| `GET /api/v1/discord/timed-roles?discord_id=…&due=1` | Os cargos temporários em curso — os de um membro, ou os que expiraram com `due=1`: `timed_roles`, cada um `timed_id`, `discord_id`, `username`, `role_id`, `expires_at` (marca temporal Unix), `given_by`, `given_by_name`, `reason` |
| `POST /api/v1/discord/timed-roles` | Dá ou prolonga um cargo temporário: `discord_id`, `username`, `role_id`, `duration` (segundos, de 60 a um ano), `given_by`, `given_by_name`, `reason`; 409 `role_mapped` para um cargo ligado a um grupo do site |
| `DELETE /api/v1/discord/timed-roles/{id}` | Retira um cargo temporário da lista |
| `GET /api/v1/discord/sanctions` | As sanções que se aplicam no Discord neste momento — silêncios e banimentos de todo o site, em vigor, de um membro ligado: `sanctions`, cada uma `sanction_id`, `user_id`, `discord_id`, `type` (`mute`, `ban_temp`, `ban_perm`), `expires_at` (marca temporal Unix, ou `null`), `origine` (`site` ou `discord`) |
| `POST /api/v1/discord/sanctions` | Faz subir uma sanção aplicada no Discord: `type`, `executor_discord_id`, `target_discord_id`, `duration_seconds`, `reason`; devolve 201 `sanction_id`, `requires_approval`. Recusas: 422 `invalid_type`, 403 `executor_unlinked`, 404 `target_unlinked`, 403 `forbidden` (o moderador não tem este direito no site), 403 `refused` (hierarquia, a própria pessoa) |
| `POST /api/v1/discord/sanctions/{id}/revoke` | Levanta uma sanção vinda do Discord: `executor_discord_id`; 404 `not_found`, 409 `site_sanction` (levanta-se no site), 403 `forbidden` |
| `GET /api/v1/discord/collectives` | Os coletivos que querem o seu espaço no Discord: `collectives`, cada um `collective_id`, `name`, `group_key` (a chave do seu grupo), `role_id` e `channel_id` (ou `null` enquanto não existirem) |
| `POST /api/v1/discord/collectives/{id}` | O cargo ou o canal que o bot acabou de criar: `role_id`, `channel_id` — o cargo torna-se a correspondência do grupo do coletivo; 404 `not_found`, 422 `invalid_id` |

## Escrever no fórum

A API escreve sempre **em nome de um autor**, indicado no corpo JSON do pedido:

- `"author": { "member_id": 7 }` — um membro do site;
- `"author": { "discord": { "id": "123456789012345678", "username": "Alcunha", "avatar": "https://cdn.discordapp.com/…" } }`
  — uma conta do Discord. Se estiver **ligada** a um membro (início de sessão pelo Discord), a mensagem é publicada em
  nome desse membro: a sua conta real, o seu nome e o seu avatar. Senão, é publicada com a **identidade do Discord** da
  pessoa: o seu nome do Discord, marcado com o logótipo do Discord, sem ligação para um perfil do site.

```bash
curl -X POST -H "Authorization: Bearer nfr_…" -H "Content-Type: application/json" \
     -d '{"forum_id": 3, "title": "A minha pergunta", "content": "**Olá** a todos", "author": {"discord": {"id": "123456789012345678", "username": "Alcunha"}}}' \
     https://o-teu-site.example/api/v1/forum/topics
```

| Campo | Para | Conteúdo |
|---|---|---|
| `forum_id` | tópico | O fórum onde publicar (não um fórum que redireciona para outro lado) |
| `title` | tópico | 1 a 100 caracteres |
| `prefix_id` | tópico | Opcional: um dos prefixos de tópico do fórum |
| `content` | todos | O texto, 1 a 20 000 caracteres |
| `format` | todos | `markdown` (predefinido — o formato do Discord) ou `html` |
| `reply_to` | resposta | Opcional: a mensagem a que se responde |
| `author` | todos | O autor (acima) |

O Markdown é convertido em HTML; o HTML que lá se infiltre é **escapado**, e o HTML passa pela mesma limpeza que o resto
do site. O autor tem de ter o **direito de escrever** no fórum, como no site (senão 403 `forum_forbidden`): um membro, ou
uma conta do Discord ligada, pelos seus direitos na categoria; uma conta do Discord sem conta no site, pelos dos membros.
Um tópico **fechado** recusa as respostas (409 `topic_locked`). Editar ou eliminar exige o **mesmo autor** que a
mensagem (senão 403 `not_author`): quem a escreveu é quem a corrige. Um pedido mal formado devolve 400 `invalid_json`;
um campo inválido, 422 `validation_failed` com o detalhe em `error.fields`.

As **sanções de moderação** também se aplicam pela API: um membro silenciado ou banido do fórum (ou do site) não escreve
lá (403 `sanctioned`), e um membro privado de ligações externas não publica um texto que as tenha (403
`links_forbidden`). `error.message` indica a sanção, para mostrar ao autor. Uma conta do Discord sem conta no site não
tem sanções.

As escritas da API aparecem no fluxo de eventos com `source.token_id`: um programa que copia o fórum para outro lado
reconhece assim o que ele próprio escreveu.

## Escrever no Bugtracker

Como no fórum, a API escreve **em nome de um autor** (`author`, mesma forma). Um **ticket** pertence a um membro: uma
conta do Discord não ligada a nenhum membro é recusada (409 `not_linked`). Um **comentário**, esse, pode vir de uma conta
do Discord não ligada: aparece com o seu nome do Discord. As sanções de moderação aplicam-se como no fórum (403
`sanctioned`, `links_forbidden`).

| Campo | Para | Conteúdo |
|---|---|---|
| `title` | ticket | 1 a 200 caracteres |
| `description` | ticket | O texto, 1 a 20 000 caracteres |
| `type` | ticket | `bug` (predefinido), `feature`, `question` ou `other` |
| `content` | comentário | O texto, 1 a 20 000 caracteres |
| `author` | todos | O autor |

Editar ou eliminar um comentário exige o **mesmo autor** (senão 403 `not_author`).

## O fluxo de eventos

Em vez de consultar todo o site a intervalos regulares, um programa lê o **fluxo de eventos**: «o que aconteceu desde o
evento n.º X?». O site não tem de o contactar — o programa não abre nenhuma porta.

```bash
curl -H "Authorization: Bearer nfr_…" "https://o-teu-site.example/api/v1/events?after=0&limit=100"
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

Guarda o valor de `next` e devolve-o como `after` da vez seguinte. Enquanto `more` for `true`, há eventos por ler de
imediato. Os eventos ficam **30 dias** no fluxo.

| `type` | `data` |
|---|---|
| `forum.topic.created` | `topic_id`, `message_id`, `forum_id`, `user_id`, `identity_id` |
| `forum.post.created` | `message_id`, `topic_id`, `forum_id`, `user_id`, `identity_id`, `is_starter` (a primeira mensagem de um tópico) |
| `forum.post.edited` | `message_id`, `topic_id`, `forum_id`, `user_id`, `is_topic` |
| `forum.post.deleted` | `message_id`, `topic_id`, `forum_id`, `is_topic`, `hard_delete`, `deleted_by` |
| `forum.topic.split` | `source_topic_id`, `new_topic_id`, `message_ids`, `forum_id` |
| `forum.topics.merged` | `source_topic_id`, `target_topic_id`, `forum_id` |
| `forum.topic.prefixed` | `topic_id`, `forum_id`, `prefix_id` — o prefixo de um tópico mudou |
| `bugtracker.ticket.created` | `ticket_id`, `user_id`, `type` |
| `bugtracker.ticket.updated` | `ticket_id`, `status`, `type`, `fields` (os campos que mudaram) |
| `bugtracker.ticket.deleted` | `ticket_id` |
| `bugtracker.comment.created`, `.edited`, `.deleted` | `comment_id`, `ticket_id` (e `user_id` na criação) |
| `user.groups.changed` | `user_id` — volta a ler o membro para ter os seus grupos atualizados |
| `user.discord.linked`, `user.discord.unlinked` | `user_id`, `discord_id` — uma conta do Discord acabou de ser ligada ou desligada |
| `discord.collective.removed` | `collective_id`, `role_id`, `channel_id` — um coletivo sai do Discord (eliminado, ou com a caixa desmarcada): o seu cargo tem de ser eliminado |
| `discord.sanction.created`, `.approved`, `.revoked` | `sanction_id`, `user_id`, `discord_id`, `type`, `expires_at`, `origine` — um silêncio ou um banimento de todo o site de um membro ligado acabou de entrar em vigor (aplicado, ou validado) ou de ser levantado |

Um evento só traz **identificadores**: o conteúdo lê-se pelos endereços acima, quando for preciso (uma mensagem eliminada
entretanto devolve `deleted: true`). `source` diz de onde veio a mudança: `null` para o site, `{ "token_id": … }` para
uma escrita feita com uma chave da API — um programa reconhece assim as suas próprias escritas, e não as copia uma
segunda vez.

## Os erros

Um erro devolve o código HTTP adequado e um objeto `error`, cujo `code` é **estável** (um programa pode confiar nele) e
cujo `message` é legível:

```json
{ "error": { "code": "forbidden", "message": "Cette clé n’a pas le droit « forum:read »." } }
```

| HTTP | `code` | Quando |
|---|---|---|
| 401 | `unauthorized` | Chave ausente, desconhecida ou revogada (com o cabeçalho `WWW-Authenticate: Bearer`) |
| 403 | `forbidden`, `not_author`, `forum_forbidden`, `sanctioned`, `links_forbidden` | A chave não tem o direito que o endereço exige; o autor indicado não é o da mensagem; o autor não pode escrever neste fórum; uma sanção de moderação impede-o, ou proíbe a ligação que o seu texto traz |
| 400 | `invalid_json` | O corpo do pedido não é um JSON válido |
| 404 | `not_found`, `member_not_found`, `topic_not_found`, `message_not_found`, `ticket_not_found`, `comment_not_found`, `link_not_found`, `module_unavailable` | Endereço desconhecido; elemento ausente; módulo (o fórum, o Bugtracker, o Discord) não instalado |
| 409 | `topic_locked`, `is_first_message`, `not_linked`, `role_mapped`, … | Tópico fechado; eliminação da primeira mensagem de um tópico; conta do Discord não ligada; cargo ligado a um grupo — e as recusas próprias dos endereços do bot, indicadas com eles |
| 422 | `validation_failed` | Um campo é inválido — o detalhe está em `error.fields` |
| 405 | `method_not_allowed` | Método errado (o cabeçalho `Allow` diz qual) |
| 429 | `too_many_requests` | Limite de frequência atingido (o cabeçalho `Retry-After` diz dentro de quantos segundos tentar outra vez) |

## O limite de frequência

Uma chave pode fazer **120 pedidos por minuto**; cada resposta diz quantos restam (`X-RateLimit-Remaining`). Para lá
disso, a API responde 429 durante um minuto. Um endereço IP que envie **20 chaves recusadas** num minuto fica bloqueado
cinco minutos: nada permite martelar a API para adivinhar uma chave.

## Segurança

- O site só guarda as **impressões** das chaves: uma fuga da base de dados não dá nenhuma chave utilizável.
- Uma chave só abre os seus **direitos**, e **revoga-se** com um clique; cada criação e cada revogação é inscrita no
  registo de auditoria (*Utilizadores → Registo de auditoria*).
- A API não põe nem cookie nem sessão, e nunca serve uma página: só JSON.
- Guarda a chave do lado do servidor (variável de ambiente, ficheiro não versionado), nunca num código publicado nem
  numa página web.
