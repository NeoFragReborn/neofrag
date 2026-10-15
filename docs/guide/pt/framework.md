# O framework

Uma referência das peças que manipulas ao escrever addons. Para a arquitetura interna completa, vê
[`docs/architecture.md`](../../architecture.md). Tudo o que se segue está verificado no código.

## O localizador de serviços — `NeoFrag()`

Todo o framework é acessível através do singleton global `NeoFrag()` e, dentro de uma classe de addon, através de
`$this` (que delega nele). Os serviços obtêm-se com métodos mágicos:

```php
$this->db        // acesso à base de dados
$this->config    // configuração do site (nf_name, nf_default_theme…)
$this->user      // membro atual
$this->url       // pedido / segmentos / base
$this->lang(...) // tradução
$this->events    // eventos internos (fire / on)
$this->module('forum');        // um módulo — NULL se não estiver instalado
NeoFrag()->model2('addon');    // um modelo
```

> `__call` **engole as chamadas desconhecidas**: `$this->metodo_ausente()` não estraga nada. Para testar uma
> verificação, injeta uma falha que a magia não apanhe (uma função global ausente).

## O encaminhamento

Um módulo declara as suas rotas em `__info().routes`: `padrão => método`.

```php
'routes' => [
    ''                 => 'index',   // a página inicial do módulo
    '{id}/{url_title}' => '_show',   // /modulo/42/slug
    'admin{pages}'     => 'index',   // administração paginada
],
```

- Marcadores **fixos**: `{id}` (inteiro), `{key_id}`, `{url_title}` (slug), `{url_title*}`, `{page}` e `{pages}`
  (paginação). Um marcador desconhecido → 404 silencioso.
- Ciclo: o **checker** (`controllers/checker.php`) valida a rota e carrega os dados; o que **devolve** torna-se os
  argumentos do método com o mesmo nome do **controlador** (`controllers/index.php`).
- O encaminhador atual é `rota → módulo → página → 404`. As **regiões com nome** dos temas (`region('content')`) são a
  base do futuro encaminhamento centrado nas páginas (outlines, rotas reservadas), ainda não escrito.

> Nos testes ou com `curl`, o CMS trata os agentes que não são navegadores como robôs e **não abre a sessão**: envia um
> **User-Agent de navegador**.

### A recusa de um checker fica no registo

Quando um checker falha — um campo POST ausente, um alvo que não existe —, o site responde **404**; o motivo (rota,
checker, método, campo ausente e o que chegou no seu lugar) é **sempre registado** (`[checker] …`). Quando a depuração
está visível — a ferramenta de depuração ligada, para um administrador com sessão iniciada —, o motivo é devolvido ao
cliente e o estado é **400**: «o teu pedido está mal formado» em vez de «este endereço não existe».
`post_check('a', 'b?')`: o sufixo `?` torna um campo opcional (`NULL` se faltar).

## Base de dados

Um construtor de consultas fluente; as tabelas têm o prefixo `nf_`.

```php
$rows = $this->db
    ->select('id', 'title', 'created')
    ->from('nf_news')
    ->where('published', '1')
    ->where('author_id', $user_id)
    ->order_by('created DESC')
    ->limit(10)
    ->get();                 // array de linhas; ->row() para uma só

$id = $this->db->insert('nf_news', ['title' => $t, 'body' => $b]);  // devolve o id
$this->db->where('id', $id)->update('nf_news', ['title' => $t2]);
$this->db->where('id', $id)->delete('nf_news');

$this->db->transaction(); … $this->db->commit();   // ou ->rollback()
```

- **`table_exists('nf_teams')`** — verdadeiro se a tabela existir. O pacote instala-se à la carte: um módulo do núcleo
  que lê a tabela de um módulo opcional **tem de** se proteger assim (ou declarar a dependência em `requires`). O
  resultado fica em cache durante o pedido.
- **`import($sql)`** — executa um SQL com várias instruções (DDL de instalação) fora da cadeia das instruções
  preparadas.
- Uma **junção que só conta ou decora tem de ser `LEFT`**: sete listas escondiam todo o conteúdo sem filhos (um fórum
  sem mensagens, um álbum sem imagens) por causa de uma junção estrita.
- Um **contador** (visitas, cliques) numa tabela cujo `updated_at` tem `ON UPDATE current_timestamp()` reescreve a data
  de modificação a cada visita: a página da wiki anunciava «modificada em» à hora do seu último leitor. Escreve a coluna
  sobre si própria — `SET views = views + 1, updated_at = updated_at` —; `check-db-compteurs` recusa o esquecimento.
- `MATCH … AGAINST` (FULLTEXT) **não vê** uma linha inserida numa transação não confirmada: o InnoDB só indexa na
  confirmação. Um teste de pesquisa FULLTEXT não pode ficar dentro da transação anulada da base de testes.

Para as entidades geridas (addons, ficheiros…), passa pelos **modelos**: `NeoFrag()->model2('addon')`,
`NeoFrag()->model2('file', $id)->delete()`; para percorrer um conjunto tipado, `NeoFrag()->collection('addon')->get()`.

## Formulários — `form()` e `form2()`

Coexistem duas API (uma decisão registada: a v1 está congelada, não é migrada em bloco):

- **`form()`** — a API dos **ecrãs de administração**: campos declarados num array com `add_rules([...])`, tratados por
  `is_valid($post)`, mostrados por `->display()`. Um exemplo completo em [Criar um módulo](create-a-module.md) (§7).
- **`form2()`** — fluente, cada campo é um objeto `form_*()`. Para os formulários **públicos** ou **ricos**, e a **única**
  que valida um formulário **só de confirmação** (sem campos) — uma confirmação de eliminação só aceita o campo
  `delete`: uma caixa acrescentada fá-la-ia falhar em silêncio.

```php
return $this->form2()
    ->rule($this->form_text('title')->title($this->lang('Titre'))->required())
    ->rule($this->form_textarea('body')->title($this->lang('Contenu')))
    ->captcha()
    ->success(function ($data) {
        $this->db->insert('nf_news', $data);
        notify($this->lang('Enregistré'));
    })
    ->submit($this->lang('Publier'));
```

As duas trazem o seu próprio token CSRF. Uma ação lançada por uma **ligação** ou por um **POST escrito à mão** tem de o
verificar ela própria: `csrf_url()`, `check_csrf()`, `csrf_token()` (trait `Admin_Helpers`); fora de um controlador, o
mesmo token lê-se com `nf_jeton_csrf()`.

**O editor rico** — o tipo `editor` em `form()`, `form_textarea()->editor()` em `form2()` — é o TinyMCE 8, alojado pelo
site; o HTML que produz passa por `sanitize_html()`. Uma imagem **colada ou largada** nele é enviada ao site
(`ajax/user/editeur-image`: membro com sessão iniciada, token de sessão, frequência limitada), e depois inserida com o
seu endereço definitivo. O site lê o seu tipo real nos seus bytes (JPEG, PNG, GIF, WebP), recusa um ficheiro que traga
código, limita o seu peso (5 MB) e as suas dimensões, **volta a codificá-la** — sem metadados, com a orientação de uma
foto aplicada — e guarda-a em `upload/editeur/AAAA/MM/` com um nome aleatório. Um `tinymce.init()` escrito à mão (um
modelo, um ecrã à parte) põe `Editeur_Images::tinymce()` no início das suas definições
(`NF\NeoFrag\Libraries\Editeur_Images`): sem isso, uma imagem colada fica partida, e depois desaparece ao guardar.
`EditeurImagesTest` verifica-o em todo o produto.

**O captcha** — `->captcha()` em `form2()`, `add_captcha()` em `form()` — mostra o fornecedor escolhido em
*Definições → Captcha* e verifica a sua resposta no servidor; um membro com sessão iniciada não vê nenhum. O predefinido
é o **ALTCHA**: alojado pelo site, sem chave nem cookie, põe o navegador a fazer um pequeno cálculo (cerca de um segundo)
que o servidor verifica, e recusa que a mesma solução sirva duas vezes. O Turnstile, o hCaptcha e o reCAPTCHA v2
precisam das suas duas chaves; sem elas, o ALTCHA assume. Fora de um formulário: `$this->captcha->element()` para o
mostrar, `$this->captcha->is_valid()` para verificar (`NF\NeoFrag\Libraries\Captcha`).

## Tabelas — `table2()`

Mostra listas paginadas, **ordenáveis clicando no cabeçalho** (Shift + clique: ordenação por várias colunas; Ctrl +
clique: retirar) e filtráveis, a partir de uma consulta. Exemplo: a lista de membros,
`modules/user/controllers/admin.php`. A ordenação é servida por `js/table2.js` em JavaScript puro.

**Uma lista dividida em páginas mostra as suas ligações de página.** Quando um checker divide uma lista —
`->paginate($page)` sobre uma coleção, `->pagination->get_data($lignes, $page)` sobre um array —, o método do
controlador que a mostra chama `->pagination->get_pagination()` (ou passa por `table2()`, que a mostra ele próprio).
Sem isso, só se veem os primeiros elementos e os outros ficam inacessíveis. Uma lista que cabe sempre numa página
di-lo com um comentário `// pagination : <raison>` no método. `check-pagination` verifica-o.

## Traduções

`$this->lang('Chave')` devolve a tradução. Os ficheiros `langs/fr.php` associam o **crc32b da chave de origem** à
tradução:

```php
return [
    hash('crc32b', 'Bienvenue') => 'Bienvenue',   // ou diretamente '467f39a3' => '…'
];
```

- `$this->lang('%d élément(s)', $n)` aceita argumentos (à maneira de `sprintf`).
- **O francês é a língua de origem**; são incluídas seis línguas e `tools/check-langs.php --toutes` recusa uma chave em
  falta numa delas. `--fix` acrescenta as chaves em falta **só ao francês**.
- **Nunca traduzas conteúdo da base de dados** (um título escrito pelo administrador): `lang()` procura-o em vão e
  regista um aviso a cada visita. Envolve-o em `$this->no_translate(...)`.

### Conteúdo traduzido: `join_lang()` e `ecrire_lang()`

O **conteúdo** de um módulo (um título, uma descrição) traduz-se de outra forma: vive numa tabela `<table>_lang`, uma
linha por língua, e o que não tem língua (uma data, uma imagem, um preço) fica na tabela principal.

```sql
CREATE TABLE IF NOT EXISTS nf_notes_lang (
    note_id INT UNSIGNED NOT NULL,
    lang    VARCHAR(5)   NOT NULL,
    title   VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (note_id, lang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- **Ler**: `join_lang()` junta a **melhor** tradução — a da língua pedida, senão a francesa, senão a da primeira língua
  escrita. Um conteúdo escrito numa só língua continua assim visível nas outras, em vez de desaparecer como com uma
  junção filtrada pela língua.

  ```php
  $this->db->select('n.id', 'nl.title')->from('nf_notes n')->join_lang('nf_notes_lang nl', 'note_id', 'n.id')->get();
  ```

- **Escrever**: `ecrire_lang()` cria a linha quando falta — um `update()` filtrado pela língua não escrevia nada da
  primeira vez que se traduzia algo, e o texto introduzido perdia-se. Dá todas as colunas obrigatórias da tabela.

  ```php
  $this->db->ecrire_lang('nf_notes_lang', ['note_id' => $id, 'lang' => $langue], ['title' => $titre]);
  ```

- **O ecrã de edição** escreve na língua da administração, e di-lo por baixo do campo («Escrito para %s…»): traduzes
  mudando de língua. O que o administrador e o visitante veem: [Um site em várias línguas](multilingue.md).

## Datas e fusos horários

O produto **guarda** as suas datas no fuso horário que o PHP tem ao arrancar — a hora universal em produção — e nunca o
muda: a base de dados alinha a sua sessão por ele (`drivers/mysqli.php`). **Mostra-as** no fuso horário de quem olha,
dado por `nf_fuseau()`: a escolha do membro (`nf_user_profile.timezone`, aplicada ao abrir a sessão), senão a do seu
navegador (cookie `nf_fuseau`, posto por `views/theme/main.tpl.php`), senão a definição do site (`nf_timezone`), senão
o fuso horário de armazenamento. Tudo está em `neofrag/helpers/time.php`.

- **Escrever na base**: `date('Y-m-d H:i:s')`, `NOW()` ou `now()` — o fuso horário de armazenamento.
- **Mostrar**: `timetostr($format, $when)` ou a biblioteca Date (`$this->date($when)`); nunca `date()`, que
  `check-heures` recusa fora dos formatos de máquina. Para uma coluna de data e hora lida tal como está (uma lista, uma
  ficha): `nf_date_heure($valeur)` — `d/m/Y H:i` na língua e no fuso horário do visitante, uma cadeia vazia se o valor
  estiver vazio; para uma data sozinha, `nf_date($valeur)`. Nunca um formato numérico escrito à mão em `timetostr()`
  (`'d/m/Y'`, `'Y-m-d H:i'`): o alemão escreve `02.10.2026`, e `check-heures` recusa-o.
- **Ler uma introdução**: os campos `datetime` dos dois sistemas de formulários convertem sozinhos; à mão,
  `nf_heure_saisie()` (e `nf_heure_affichee()` para o sentido inverso).
- **Uma data sozinha ou uma hora sozinha** (`Y-m-d`, `H:i`) não é um instante: `timetostr()` e Date não a convertem. Um
  dia inteiro guardado numa coluna de data e hora passa-se como `Y-m-d` (vê `Calendar::format_dt()`).
- **Uma hora na hora do site**, seja qual for o visitante (uma grelha de programação):
  `new \DateTime('now', nf_fuseau_site())`.

## O front end: Bootstrap 5, JavaScript puro, CSP estrita

- **Bootstrap 5.3**, **sem jQuery**. As classes de grelha têm sempre um ponto de quebra (`col-12 col-lg-8`);
  `tools/check-classes-bs4.php` recusa as classes do Bootstrap 4.
- **CSP estrita baseada em nonce**: cada resposta HTML tem um nonce aleatório, posto em cada `<script>` em linha pelo
  filtro de `index.php`, e `script-src` só permite `'self'`, esse nonce, as origens do fornecedor de captcha **ativo** —
  nenhuma com o ALTCHA, o predefinido — e o Google Analytics **se** houver um ID configurado. **Nenhum script a partir de
  um CDN.** O JS inserido dinamicamente passa por `NF.setHtml` / `NF.insertHtml` / `NF.replaceHtml`, que voltam a executar
  os `<script>` acrescentados com o nonce certo, também no iframe do Editor ao vivo.
- **`window.NF`**, definido no modelo principal, substitui as poucas primitivas que eram precisas — e nada mais (não é
  um mini-jQuery):

| Primitiva | Papel |
|---|---|
| `NF.ready(fn)` | executa `fn` quando o DOM está pronto (ou de imediato se já estiver) |
| `NF.data(el, 'my-key')` | lê `data-my-key` com a conversão do jQuery (número, booleano, JSON) |
| `NF.ajax({url, method, data, dataType, headers, signal})` | `fetch` com `X-Requested-With` na mesma origem, corpo `form-urlencoded`, arrays como `key[]`; **rejeita** perante um estado de erro; `dataType: 'text'` senão JSON |
| `NF.post(url, data)` | atalho POST |
| `NF.setHtml(el, html)` | `innerHTML` + execução dos scripts |
| `NF.insertHtml(target, position, html)` | `insertAdjacentHTML` + execução só dos scripts acrescentados |
| `NF.replaceHtml(el, html)` | substitui o elemento, scripts executados |
| `NF.runScripts(root)`, `NF.loadScript(src)` | voltar a executar, carregar com o nonce |
| `NF.bandeaux()` | arruma as faixas no topo da página e volta a publicar `--nf-haut` e `--nf-entete` (vê *Criar um tema*); chama-a de novo depois de acrescentar uma faixa |

- As janelas modais (`js/modal.js`), as notificações (`js/notify.js`) e as confirmações (`js/confirm.js`) são carregadas
  pelos temas.
- Duas verificações protegem este front end: `check-js-sources` (sintaxe, PHP interpolado tolerado, e nenhum `$(`) e
  `check-js-console` (as páginas reais, num navegador real: nenhum erro, nenhuma violação da CSP). O banco
  `tests/Browser/*.test.html` + `tools/check-js.php` testa o **contrato** de um script no ficheiro real, com duplos de
  `fetch`.

## Segurança: o que já existe

Antes de escreveres o teu: `Rate_Limit` (por chave, p. ex. `contact:ip:<ip>`), `Audit_Log` (ações sensíveis),
`File_Jail` (caminhos de envio), `Moderation`, TOTP para a autenticação de dois fatores, `sanitize_html()`
(HTMLPurifier) para o HTML rico, `is_dangerous_upload()`, e o modo de demonstração (`nf_demo()`), que bloqueia a escrita
dos módulos sensíveis.

**Um endereço escrito por alguém e colocado num `href` ou num `src`** passa por `nf_url_sure($url)`: devolve `FALSE` para
um esquema diferente de `http`, `https`, `mailto`, `tel` ou `geo` — um `javascript:` seria executado ao clicar com os
direitos de quem clica. Um endereço relativo passa. `nf_texte()` não chega: escapa as aspas, não o esquema.
`url()` devolve `#` para um endereço absoluto recusado.

### O site de demonstração

A demonstração é partilhada: a sua conta `demo` é administradora. A rede de segurança está em
`neofrag/helpers/system.php`, e atua à frente de cada controlador (`core/output.php`):

- **`NF_DEMO_MODULES_VERROUILLES`** — os módulos onde nenhuma ação passa na demonstração: todo o POST, toda a ligação que
  traga um token (`?_=`), todo o método cujo nome contenha uma palavra de ação (`NF_DEMO_MOTS_D_ACTION`: export, test,
  toggle…) é recusado — um 403 JSON para uma chamada AJAX, senão uma notificação e um regresso à página anterior
  (`nf_demo_requete_refusee()`). **Um novo módulo que mexa na configuração do site acrescenta-se a esta lista**: é uma
  lista de bloqueio, um módulo ausente fica aberto. `check-demo-lock` lembra-o.
- **`NF_DEMO_LECTURES`** — as poucas leituras permitidas num módulo bloqueado (`controleur::methode`), que devolvem então
  exemplos fictícios.
- **`nf_demo_ecriture_permise($module)`** — a pergunta a fazer num código que escreve fora da rede; verdadeira fora da
  demonstração.
- **`nf_compte_masque()`** — a conta de emergência da demonstração, que nenhuma lista de membros mostra:
  `->where('u.id !=', nf_compte_masque())`. Fora da demonstração, vale 0.
- Na demonstração, `File::delete()` não elimina nada e os envios de ficheiros são recusados. Uma página que mostraria um
  ficheiro, um registo ou uma definição sensível mostra um exemplo no seu lugar.
- A conta partilhada não pode ser alterada a partir da área de membros: nem nome de utilizador, nem endereço, nem
  palavra-passe, nem autenticação de dois fatores, nem eliminação — um visitante deixava os outros de fora até à
  reposição. Como a rede só cobre a administração, estas páginas fazem elas próprias o teste (`nf_demo()`,
  `modules/user/controllers/index.php`).

## Eventos

`$this->events->fire('forum.post.created', $message_id, $topic_id, $user_id)` do lado emissor;
`$this->events->on('forum.post.created', function (...) { … })` do lado subscritor. Os ouvintes ficam ativos durante todo
o pedido.

## Helpers úteis

- `url('forum/42')` — constrói um URL encaminhado (prefixo de língua incluído).
- `notify('Mensagem')` — uma notificação no ecrã depois de um redirecionamento.
- `nf_texte(...)` / `sanitize_html(...)` — escape / limpeza anti-XSS.
- `nf_url_sure($url)` — se um endereço escrito pode ir para uma ligação (vê Segurança).
- `nf_date_heure($valeur)` / `nf_date($valeur)` — uma data da base, para mostrar (vê Datas).
- `nf_demo()` — verdadeiro no site de demonstração.

## Migrações

O esquema do **núcleo** evolui por migrações versionadas, registadas em `nf_migrations`:

```bash
php tools/migrate.php status                 # aplicadas / pendentes
php tools/migrate.php up [--pretend]          # aplica (simulação com --pretend)
php tools/migrate.php down [--step=N]         # anula
php tools/migrate.php baseline --until=NAME   # adotar uma base existente
```

Ficheiros: `migrations/YYYY_MM_DD_name.up.sql` (+ `.down.sql`). O DDL do MySQL confirma-se sozinho → **faz uma cópia de
segurança antes de `up` em produção**. Os **addons** têm as suas próprias migrações (`<addon>/install/migrations/`,
registadas em `nf_addon_migrations`) — vê [Criar um módulo](create-a-module.md).

**O código novo aplica as suas migrações sozinho**, uma vez, na primeira página vista depois da atualização — pelo botão
como por FTP: as do núcleo, depois as dos addons instalados (`nf_migrations_du_code()`, chamada por `index.php` quando a
definição `nf_migrations_version` difere de `NEOFRAG_VERSION`). Vale para todos os sites, a demonstração incluída. Uma
falha não impede a página de aparecer: é registada, e a tentativa seguinte espera dez minutos.

## Sobreposição em três níveis

Resolução das vistas, classes e ficheiros (ganha o primeiro encontrado):

1. `overrides/{type}/{file}` — global
2. `themes/{tema ativo}/overrides/{type}/{file}` — por tema
3. o original incluído

Personalizas sem fazer um fork e sem estragar as atualizações. Atenção: dois ficheiros com o mesmo nome em dois níveis,
e `path()` serve um ou outro conforme quem chama — um assistente apareceu uma vez sem estilo por causa de um duplicado
(`tools/check-assets.php` recusa-o agora).

## Ambiente de desenvolvimento

O projeto funciona em **qualquer PHP 8.2+ com MySQL ou MariaDB** (Apache + `mod_rewrite`, ou nginx, ou Caddy). O caminho
de referência do projeto é uma **instalação de testes num servidor**, onde corre a bateria completa:
`php tools/check-all.php --navigateur`, `vendor/bin/phpunit --fail-on-skipped` e `composer stan`. O ambiente, a base de
testes e o ciclo de trabalho estão descritos em [`docs/development.md`](../../development.md); cada ferramenta está
descrita em [`tools/README.md`](../../../tools/README.md).
