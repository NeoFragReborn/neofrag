# Criar um módulo

Um **módulo** é uma funcionalidade completa: as suas páginas públicas, as suas rotas, a sua administração, os seus dados
e as suas permissões. É o tipo de addon mais rico.

Vamos criar um módulo `notes` que mostra uma lista de notas públicas. Tudo o que se segue está verificado no código; os
módulos incluídos (`modules/contact`, `modules/news`) são os melhores exemplos para ler a seguir.

## Estrutura dos ficheiros

```
modules/notes/
├── notes.php                 # classe: metadados, declarações, rotas, permissões
├── controllers/
│   ├── checker.php           # valida a rota e carrega os dados
│   ├── index.php             # mostra as páginas públicas
│   └── admin.php             # ecrã de administração (opcional)
├── install/
│   ├── install.sql           # as tabelas do módulo (executado na instalação, idempotente)
│   ├── uninstall.sql         # eliminação das tabelas
│   └── migrations/           # alterações de esquema entre duas versões (opcional)
├── views/
│   └── index.tpl.php
├── css/                      # opcional
├── images/
│   └── thumbnail.jpg         # a sua miniatura, 960 × 600 — um cartão: o seu ícone, o seu nome, o que faz
├── js/                       # opcional — JavaScript puro, nunca jQuery
└── langs/
    └── fr.php                # traduções (opcional)
```

## 1. A classe — `notes.php`

```php
<?php
namespace NF\Modules\Notes;

use NF\NeoFrag\Addons\Module;

class Notes extends Module
{
    protected function __info()
    {
        return [
            'title'       => $this->lang('Notes'),
            'description' => $this->lang('Petites notes publiques.'),
            'icon'        => 'fas fa-note-sticky',   // tem de existir no FontAwesome incluído
            'author'      => 'O Teu Nome',
            'license'     => 'LGPLv3',
            'version'     => '1.0.0',
            'depends'     => ['neofrag' => '1.2.50'],   // a versão do núcleo de que precisa no mínimo — sem ela, «Adicionar» recusa o arquivo
            'admin'       => TRUE,                   // expõe uma página de administração

            // Declarações de desacoplamento — OBRIGATÓRIAS (a CI recusa um addon mudo).
            'core'        => FALSE,                  // TRUE = sempre incluído e não desinstalável
            'presets'     => ['communaute'],         // perfis de instalação que o marcam de antemão
            'requires'    => [],                     // addons de que PRECISA (sem eles, estraga-se)

            'routes'      => [
                ''                 => 'index',       // /notes
                '{id}/{url_title}' => '_show',       // /notes/42/a-minha-nota
            ],
        ];
    }
}
```

### As três declarações de desacoplamento

Desde 2026-09-15, o pacote instala-se **à la carte**: o instalador oferece perfis (*Completo*, *Gaming / eSports*,
*Comunidade*, *Associação / clube*, *Só o núcleo*) construídos **a partir destas declarações**. Nenhuma lista é escrita à
mão noutro lado.

- `core` — `TRUE` para um módulo de infraestrutura ou de CMS sempre incluído e não desinstalável; `FALSE` para tudo o
  que é opcional. Um módulo do núcleo **nunca** pode depender de um opcional (regra 3 de
  `tools/check-addon-declarations.php`).
- `presets` — os perfis que o marcam de antemão: `'gaming'`, `'communaute'`, `'association'` (as etiquetas de
  `install/lib/presets.php`). Vazio: só aparece no perfil *Completo*.
- `requires` — os addons de que precisa **para não se estragar**: uma tabela que lê, uma classe que nomeia. O instalador
  adiciona-os automaticamente quando o teu módulo é marcado. Uma dependência **suave** (um serviço que pode faltar) não
  se declara aqui: **protege-se** no código (vê §5).

Estas três chaves são verificadas estaticamente na CI, tal como a existência do ícone declarado (regra 7: o FontAwesome
mudou o nome a ícones entre a 5 e a 6). Um addon que não queres no catálogo do marketplace acrescenta
`'distributed' => FALSE`.

### As rotas

Uma rota associa um **padrão de URL** a um **método do controlador**. O padrão é relativo ao nome do módulo (`''` = a
página inicial do módulo, aqui `/notes`). As rotas de administração começam por `admin` (`'admin{pages}' => 'index'`
para uma lista paginada em `/admin/notes`).

> Os marcadores são um conjunto **fixo**: `{id}` (inteiro), `{key_id}`, `{url_title}` (slug), `{url_title*}` (slug com
> `/`), `{page}` e `{pages}` (paginação). Um marcador desconhecido produz um 404 silencioso. Para uma página de detalhe,
> o padrão idiomático é `{id}/{url_title}`: o `id` identifica o elemento, o slug é decorativo.

## 2. O checker — `controllers/checker.php`

O **checker** corre antes do controlador: valida a rota e carrega os dados. Devolver `FALSE` ou nada provoca um 404. O
que **devolve torna-se os argumentos** do método com o mesmo nome do controlador.

```php
<?php
namespace NF\Modules\Notes\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
    public function index()
    {
        $notes = $this->db->select('id', 'title', 'body')
                          ->from('nf_notes')
                          ->order_by('id DESC')
                          ->get();

        return [$notes];   // → index($notes)
    }

    public function _show($id, $url_title)
    {
        if ($note = $this->db->from('nf_notes')->where('id', $id)->row())
        {
            return [$note];   // → _show($note)
        }
        // nada: 404
    }
}
```

Para um ponto de entrada que recebe um **POST** (AJAX, um formulário escrito à mão), o checker lê os campos com
`post_check('titre', 'corps', 'options?')`: cada nome é **obrigatório**, exceto se tiver o sufixo `?`, caso em que vale
`NULL` quando falta. Um campo obrigatório ausente faz falhar o checker: uma resposta **404** em produção (o motivo exato
— que campo, o que chegou no seu lugar — é **registado**) e **400 com o motivo** quando a depuração está visível (um
administrador com sessão iniciada, a ferramenta de depuração ligada). Este comportamento vem de dez widgets que já não
podiam ser adicionados no Editor ao vivo porque um campo opcional era exigido.

## 3. O controlador público — `controllers/index.php`

```php
<?php
namespace NF\Modules\Notes\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
    public function index($notes)
    {
        $this->title($this->lang('Notes'))
             ->icon('fas fa-note-sticky')
             ->breadcrumb();

        return $this->css('notes')->view('index', ['notes' => $notes]);
    }
}
```

`title()`, `icon()`, `breadcrumb()` preenchem o cabeçalho da página. O controlador mostra depois a sua vista, ou um
painel através de `$this->panel()->title()->body($html)` para um HTML construído em PHP.

> **Nunca faças passar conteúdo da base de dados por `$this->title()` e depois pela tradução.** Um título escrito pelo
> administrador não tem tradução: `lang()` procura-o em vão e regista um aviso a cada visita noutra língua. Envolve o
> que vem da base em `$this->no_translate(...)`.

## 4. A vista — `views/index.tpl.php`

```php
<div class="notes">
    <?php if (empty($notes)): ?>
        <div class="alert alert-info"><?php echo $this->lang('Aucune note.') ?></div>
    <?php else: foreach ($notes as $n): ?>
        <article class="note">
            <h3><?php echo nf_texte($n['title']) ?></h3>
            <p><?php echo nf_texte($n['body']) ?></p>
        </article>
    <?php endforeach; endif ?>
</div>
```

As variáveis passadas a `view()` estão disponíveis diretamente. **Faz sempre o escape** do que vem da base de dados
(`nf_texte()`); para um HTML rico escrito por um membro, `sanitize_html()` (HTMLPurifier). Um **endereço** escrito
por alguém que acabe num `href` passa além disso por `nf_url_sure()`, que recusa `javascript:` e os outros esquemas
executáveis (vê [O framework](framework.md#segurança-o-que-já-existe)).

**O front end é Bootstrap 5, sem jQuery, sob uma CSP estrita.**
- As classes de grelha escrevem-se `col-12 col-lg-8`, nunca `col-8` sozinho (que se aplica a partir de 0 px e estraga
  os telemóveis); `tools/check-classes-bs4.php` recusa as classes do Bootstrap 4 desaparecidas.
- Um `<script>` em linha numa vista recebe automaticamente o **nonce** da resposta (o filtro de `index.php`); um ficheiro
  JS carrega-se com `->js('nome')` (a pasta `js/` do módulo).
- Escreve o JavaScript com as primitivas do núcleo — `NF.ready`, `NF.data(el, 'key')`,
  `NF.ajax({url, method, data, dataType})`, `NF.post(url, data)`, `NF.setHtml`, `NF.insertHtml`, `NF.replaceHtml` —,
  nunca `$(…)`: o jQuery já não é carregado, e `tools/check-js-sources.php` recusa qualquer chamada. Os endereços de que
  o script precisa põem-se como `data-*` na marcação em vez de PHP interpolado no `.js`.

## 5. Os dados

Um módulo **traz as suas tabelas** em `install/install.sql` (e a sua remoção em `uninstall.sql`). Este SQL é executado
**quando o módulo é instalado** (instalador, análise a partir da administração, ZIP do marketplace), idempotente graças a
`CREATE TABLE IF NOT EXISTS`:

```sql
-- modules/notes/install/install.sql
CREATE TABLE IF NOT EXISTS nf_notes (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body  TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

> **O conteúdo que se traduz** (um título, uma descrição) vive numa tabela `<table>_lang`, uma linha por língua, lida com
> `join_lang()` e escrita com `ecrire_lang()`:
> [O framework](framework.md#conteúdo-traduzido-join_lang-e-ecrire_lang).

> **Sem tabelas próprias? Sem `install.sql`.** Um módulo que reutiliza tabelas do núcleo (por exemplo `nf_file` através
> de `model2('file')`) instala-se sem tocar no esquema.

### Fazer evoluir o esquema — migrações por addon

`install.sql` cria as tabelas em falta mas não **altera** uma tabela já presente nos sites dos utilizadores. Para uma
alteração de esquema entre duas versões do teu módulo:

```
modules/notes/install/migrations/2026_10_01_add_pinned.up.sql
```
```sql
ALTER TABLE nf_notes ADD COLUMN pinned TINYINT(1) NOT NULL DEFAULT 0;
```

- Nome = **prefixo com data** (`YYYY_MM_DD_descricao`), executadas por ordem.
- Registadas em `nf_addon_migrations`: cada migração é executada **uma única vez**.
- Numa instalação nova (`install.sql` já traz o esquema atualizado), as migrações são **marcadas como base** — anotadas
  sem serem executadas. Numa atualização (marketplace → «Atualizações», ou um arquivo enviado com «Adicionar»), só as
  novas são **executadas** (`Addon::update()`). Um módulo incluído com o núcleo recebe as suas na primeira visita com o
  código novo (`nf_migrations_du_code()`, vê [o framework](framework.md#migrações)).
- Regra de ouro: uma migração para uma alteração real a um esquema **já distribuído**; uma tabela nova vai para
  `install.sql`, nunca para uma migração — **exceto** se o módulo já estiver instalado em sites: então a tabela nova vai
  para os dois, `CREATE TABLE IF NOT EXISTS` na migração.
- Uma atualização pelo marketplace ou por um arquivo volta a executar `install.sql` **antes** das migrações: um site que
  salta versões recebe as tabelas da última e depois executa sobre elas as migrações antigas. Uma migração que escreve
  dados visa, portanto, o esquema do último `install.sql`, e reescreve-se quando ele muda — um site que já a executou
  reconhece-a pelo nome e não a volta a executar. Pela mesma razão, nunca mudes o nome de uma migração.
- No site de demonstração, uma atualização de addon lançada a partir da administração não executa nada
  (`Addon::migrate()` sai quando `nf_demo()` é verdadeiro); as migrações que chegam com o código novo aplicam-se lá como
  noutro lado.

A pasta `migrations/` **na raiz do projeto** está reservada às alterações transversais do núcleo.

### Depender de outro módulo sem se estragar

O pacote instala-se à la carte: o módulo de que precisas pode **não estar lá**. Quatro formas de depender, e o que
acontece se faltar (`tools/check-addon-coupling.php` deteta-as com o tokenizador):

| Meio | Se o addon faltar | O que fazer |
|---|---|---|
| ler **a sua tabela** (`from('nf_teams')`) | **fatal** — «Table doesn't exist» | declará-lo em `requires`, ou proteger-se com `$this->db->table_exists('nf_teams')` e **anotar** |
| nomear **a sua classe** | **fatal** — «Class not found» | idem |
| chamar **o seu serviço** (`$this->module('teams')`, `model2('team')`) | tolerante: devolve `NULL` | testar o retorno: `if ($teams = $this->module('teams'))` |
| escrever **o seu URL** (`url('teams/…')`) | cosmético: uma ligação morta | aceitável |

Um acoplamento fatal **nem declarado nem anotado faz falhar a CI**. A anotação vai no sítio exato, como comentário:
`// couplage(teams): purge à la suppression d'un jeu — gardé par table_exists()`, ou no início do ficheiro
`couplage(teams): …` quando as referências estão espalhadas.

### Tipos de conteúdo: reações, subscrições, revisões, lixo

Se o teu módulo publicar **conteúdo** (como as notícias, os artigos, os tópicos), declara-o: os módulos transversais —
reações, notificações, revisões, lixo, gamificação — recolhem-no em vez de cada um ter a sua própria lista de tabelas.

```php
public function declare_content_types()
{
    return [
        'note' => [
            'table' => 'nf_notes', 'pk' => 'id', 'author' => 'user_id',
            'reactable' => TRUE, 'subscribable' => TRUE, 'revisable' => FALSE,
        ],
    ];
}
```

Um exemplo real: `modules/news/news.php`.

### A referenciação de um conteúdo

Um tipo de conteúdo declarado acima (com a sua `table` e a sua `pk`) pode receber, como as notícias ou a wiki, um título
e uma descrição para os motores de pesquisa — a página partilhada é a da secção Referenciação, nada a escrever desse
lado. Três chamadas:

```php
// O cartão de edição da administração: o botão que leva à página partilhada.
return $this->admin_card('fas fa-edit', $titre, $formulaire, '', nf_seo_bouton('note', (int) $id));

// A ação da página pública, antes de mostrar a vista: aplica o título e a descrição introduzidos.
nf_seo_contenu('note', (int) $id);

// Quando o endereço de um conteúdo muda (um nome, um slug), o antigo leva ao novo (301).
nf_redirection_ajouter('notes/'.$ancien_slug, 'notes/'.$nouveau_slug);
```

Exemplos reais: `modules/wiki/controllers/admin.php` e `modules/wiki/controllers/index.php`.

### Os concentradores: estatísticas, atividade, painel, pesquisa, mapa do site

Um módulo liga-se a uma página que **agrega** fornecendo um controlador com o nome do concentrador. O concentrador chama
o método com o mesmo nome **sem verificar nada**: com o nome alterado, tornado privado ou com mais um parâmetro
obrigatório, o erro só aparece ao abrir a página — por isso `tools/check-addon-contracts.php` fixa estes contratos na
CI.

| Controlador | Método esperado | Chamado por |
|---|---|---|
| `controllers/statistics.php` | `statistics()` | a página Estatísticas da administração |
| `controllers/activity.php` | `activity($user_id, $limit)` | o perfil de um membro |
| `controllers/dashboard.php` | `dashboard()` | o painel da administração |
| `controllers/block.php` | `block()` | os blocos `[block:…]` das páginas |
| `controllers/search.php` | `search()` **e** `suggest()` | a pesquisa global e a sugestão instantânea — um módulo que só tenha `search()` é **ignorado em silêncio** |
| `controllers/sitemap.php` | `sitemap()` | o mapa do site (`/sitemap.xml`, um por língua): devolve `[['adresse' => 'meumodulo/12/titulo', 'date' => …], …]`, caminhos como os que `url()` recebe, só o que um **visitante** pode ler (`$this->access('meumodulo', 'read', $id, 'visitors')`) e o que existe **na língua do mapa** — um conteúdo que **não tem língua própria** (um tópico, um ticket, uma página feita desse conteúdo) tem `'sans_langue' => TRUE`: só aparece no mapa da primeira língua do site, e a sua página chama `nf_seo_sans_langue()`, que põe lá a sua canónica (seis línguas do mesmo texto, cada uma canónica: a Google guarda outra e não indexa nenhuma); **uma secção vazia devolve `[]`**: a sua página só teria «ainda nada» para mostrar; sem o concentrador, o módulo está ausente dos motores de pesquisa, e o IndexNow não assinala as suas páginas: a tarefa agendada compara este mesmo mapa de uma passagem para a seguinte |

### A área de membros e o perfil público

Três métodos da **classe** do módulo, opcionais, e só lidos se existirem:

- **`espace_membre($user)`** acrescenta páginas ao **menu da área de membros** — a coluna da esquerda num computador, a
  faixa de separadores num telemóvel, retomada também na barra superior dos temas e no widget «Área de membros».
  Devolve uma lista de entradas: `url`, `titre`, `icone`, se for preciso `badge` (um número a assinalar) e `compact`
  (mostrada também nos menus curtos), e `ordre` para as ordenar.
- **`profil_membre($membre)`** acrescenta **separadores ao perfil público de um membro**, cada um no seu endereço
  `user/<id>/<nome>/<separador>`. Devolve uma lista de separadores: `onglet` (o segmento do endereço: minúsculas,
  algarismos e hífenes), `titre`, `icone`, `ordre`, se for preciso `nombre` (mostrado ao lado do título e em «Em
  números»), e `contenu`, uma função que mostra a página do separador — só é chamada quando é aberta. **Nada a mostrar,
  nada a devolver**: um separador vazio não aparece, e o seu endereço responde 404.
- **`types_de_notification()`** declara as notificações que o módulo envia, para as **preferências** de cada membro
  (`user/notifications/preferences`): `type` (o que `push()` recebe), `titre` (o que o membro lê, «Uma resposta num
  tópico que sigo»), `email` a `TRUE` se o módulo também a souber enviar por e-mail, e `ordre`. **Um tipo enviado tem de
  ser declarado**: senão o membro não o pode desligar — `tests/Unit/TypesDeNotificationTest.php` lê os envios do
  repositório e recusa-o.

```php
public function profil_membre($membre): array
{
    $notes = $this->db->from('nf_notes')->where('user_id', (int) $membre->id)->count();

    return $notes ? [[
        'onglet'  => 'notes',
        'titre'   => (string) $this->lang('Notes'),
        'icone'   => 'fas fa-note-sticky',
        'ordre'   => 50,
        'nombre'  => $notes,
        'contenu' => fn () => $this->view('profil-membre', ['notes' => $this->model()->du_membre((int) $membre->id)]),
    ]] : [];
}
```

O perfil mostra o que **quem olha** pode ler: um separador que lista conteúdo filtra como as suas próprias páginas
(`$this->access(…)`) — o fórum só mostra lá as categorias legíveis. O que o membro escolhe esconder sobre si próprio
(pontos, karma, VIP, idade, presença online) pergunta-se ao seu modelo: `$membre->montre('statut')`. Exemplos reais:
`modules/forum/forum.php`, `modules/teams/teams.php`.

Uma notificação envia-se com `push($user_id, $type, $titre, $adresse, $acteur)` do módulo `notifications` (ou
`push_unique()`, que não acrescenta uma segunda do mesmo tipo no mesmo endereço enquanto a primeira não for lida): o que o
membro desligou **no site** não é escrito. Um módulo que também a envia por e-mail faz primeiro ele próprio a pergunta,
`$notifications->veut($user_id, $type, 'email')`, e não escreve se a resposta for não. A partir da classe do módulo,
carrega-o com `\NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['notifications'])` — `$this->module()` engana-se lá no tipo
de addon. Um exemplo real: `modules/talks/talks.php`.

## 6. As permissões (opcional)

```php
public function permissions()
{
    return [
        'default' => [
            'access' => [
                [
                    'title'  => $this->lang('Notes'),
                    'icon'   => 'fas fa-note-sticky',
                    'access' => [
                        'add_note'    => ['title' => $this->lang('Ajouter'),   'icon' => 'fas fa-plus', 'admin' => TRUE],
                        'delete_note' => ['title' => $this->lang('Supprimer'), 'icon' => 'far fa-trash-alt', 'admin' => TRUE],
                    ],
                ],
            ],
        ],
    ];
}
```

As permissões tornam-se editáveis em **Administração → Permissões** (matriz cargo × ação). No código:
`$this->access('notes', 'add_note')`. Um exemplo real: `modules/news/news.php`.

Uma permissão **por elemento** (uma categoria, uma galeria) declara os seus valores de partida em `init`, que
`$this->access->init('notes', 'categorie', $id)` define quando o elemento é criado. Como o cargo «membro» não herda do
cargo «visitante», `init` segue as regras originais do NeoFrag quando `members` não é nomeado: uma lista **vazia** torna
a ação pública (visitantes e membros); `['visitors', TRUE]` vale também para os membros; `['visitors', FALSE]` só recusa
os visitantes — os membros mantêm a ação —; e `['admins', TRUE]` reserva-a aos administradores. Um exemplo real:
`modules/forum/forum.php` (ler, escrever, moderar uma categoria).

## 7. A administração (opcional)

Se `'admin' => TRUE`, acrescenta `controllers/admin.php` (classe `Admin extends Controller_Module`). O seu método
`index()` é servido em `/admin/notes`.

```php
<?php
namespace NF\Modules\Notes\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
    public function index()
    {
        $this->title($this->lang('Notes'))->icon('fas fa-note-sticky');

        $this->form()
             ->add_rules([
                 'title' => ['label' => $this->lang('Titre'),   'type' => 'text',   'rules' => 'required'],
                 'body'  => ['label' => $this->lang('Contenu'), 'type' => 'editor'],
             ])
             ->add_submit($this->lang('Ajouter'));

        if ($this->form()->is_valid($post))
        {
            $this->db->insert('nf_notes', ['title' => $post['title'], 'body' => $post['body']]);
            notify($this->lang('Note ajoutée.'));
            redirect('admin/notes');
        }

        return $this->admin_card('fas fa-note-sticky', $this->lang('Notes'), $this->form()->display());
    }
}
```

**`form()`** (`add_rules()` / `is_valid()` / `display()`) é a API de formulários dos ecrãs de administração; `form2()`
(vê [O framework](framework.md)) é para os formulários públicos, ricos ou só de confirmação. O trait `Admin_Helpers`,
disponível em todo o controlador de módulo, veste o conteúdo: `admin_card()`, `admin_create()`, `admin_back()`,
`admin_split()`, `admin_action_bar()`, `admin_empty()`, `admin_stats()`, `sort_select()`. Cada subpágina tem de
oferecer um regresso (`admin_back()` ou o trilho de navegação): `tools/check-admin-back.php` verifica-o em cada página
da administração.

### As ações que alteram: exigir um token CSRF

Toda a ação que **muda** alguma coisa e que é lançada por uma **ligação GET** ou por um **POST escrito à mão** (fora de
`form()` / `form2()`, que trazem o seu próprio token) tem de ser protegida: `SameSite=Lax` não chega.

```php
// Construção da ligação: token na consulta.
$html .= '<a href="'.$this->csrf_url('admin/notes/delete/'.$id).'">'.icon('far fa-trash-alt').'</a>';

// Controlador: verifica o token ANTES de mudar o que quer que seja, senão redireciona.
public function _delete($note)
{
    $this->check_csrf('admin/notes');
    $this->db->where('id', $note['id'])->delete('nf_notes');
    notify($this->lang('Note supprimée.'));
    redirect('admin/notes');
}
```

Para um POST escrito à mão, o token vai num campo oculto:
`<input type="hidden" name="_" value="<?php echo $this->csrf_token() ?>">`.

### As tabelas da administração

`table2()` mostra listas paginadas, ordenáveis clicando no cabeçalho e filtráveis (exemplo:
`modules/user/controllers/admin.php`). A ordenação é tratada por `js/table2.js`, em JavaScript puro. Uma lista que o
checker divide à mão (`->paginate()`) mostra as suas ligações de página: o contrato está em
[O framework](framework.md#tabelas--table2), e `check-pagination` verifica-o.

### O guia de estilo da administração

A administração é **sóbria**: o fundo apaga-se por trás dos dados, a cor de destaque é rara, e as cores vivas ficam para
o que pede uma ação ou assinala um estado. Uma página de módulo constrói-se assim:

- **Um cartão por lista**: `admin_card(ícone, título, corpo, subtítulo, ações)` — o subtítulo tem a contagem («3
  publicadas · 1 rascunho»), as ações o botão que cria.
- **O botão que cria** («Nova citação»): `admin_create(url, etiqueta)`, um `btn btn-primary btn-sm` no cabeçalho do
  cartão da lista que alimenta. A barra superior mantém as ferramentas da página — Permissões, Configuração, Ajuda.
- **As ações de uma linha**: botões só com ícone, pequenos. *Editar* (lápis), *acesso* (cadeado), *ordenar*:
  `btn-outline-secondary`, neutros. *Eliminar*: `btn-outline-danger` com o ícone `far fa-trash-alt`. Nunca um botão
  cheio numa linha. `button_update()`, `button_access()` e `button_delete()` mostram-nos assim; um botão escrito à mão
  segue a mesma regra, que `tools/check-actions-admin.php` verifica.
- **As outras ações de uma linha** (pré-visualizar, duplicar, restaurar): um contorno, nunca um botão cheio; uma cor só
  se tiver um significado (`btn-outline-warning` para uma pré-visualização que muda o modo de navegação,
  `btn-outline-success` para restaurar).
- **Os distintivos de estado**: `badge text-bg-success` (publicado, ativo), `text-bg-secondary` (rascunho, inativo),
  `text-bg-warning` (pendente), `text-bg-danger` (erro) — o tema da administração suaviza-os (fundo pálido, texto
  marcado), em claro como em escuro.
- **Uma tabela** para os dados em colunas (título, contagem, estado); **cartões** (`nf-content-card`) para o conteúdo
  redigido que se reconhece pelo seu excerto. Uma lista agrupada por família (Modelos de email) continua a ser **uma só
  tabela**, com uma linha `<tr class="nf-table-groupe">` no início de cada família, em vez de um cartão por família.
  Uma data da base mostra-se lá com `nf_date_heure()`, nunca tal como está.
- **O estado vazio**: `admin_empty(ícone, título, texto)`, que diz o que fazer para começar.
- **O botão que envia um formulário**: «Guardar» para um elemento que existe — nunca «Editar», já o estás a editar —,
  «Adicionar» ou «Criar» para um novo, como o título da página («Adicionar um fórum»).
- **Várias vistas do mesmo assunto** (Monitorização, Utilizadores, Discord): separadores `nf-local-nav` /
  `nf-local-tab` — como ligações para outros endereços, ou como botões que mudam de painel.

## 8. Testar o módulo

O projeto tem uma rede de segurança, e um módulo novo tem de entrar nela:

- **Testes** — `tests/Unit` (sem base de dados), `tests/Headless` (o framework arrancado, modelos reais contra a base de
  testes, cada teste numa transação anulada — `HeadlessTestCase`), `tests/Integration` (espelho SQL),
  `tests/Browser/*.test.html` (o contrato de um script num navegador real, através de `tools/check-js.php`).
  `vendor/bin/phpunit --fail-on-skipped`.
- **Verificações** a passar antes de distribuir: `check-addon-declarations`, `check-addon-coupling`,
  `check-addon-contracts`, `check-js-sources`, `check-langs --toutes`, `check-textes-en-dur` (nenhum texto visível fora
  de `lang()`), `check-actions-admin` (o guia de estilo dos botões), `check-pagination`, `check-heures` (nenhuma data
  mostrada sem fuso horário), `check-db-compteurs`, `check-demo-lock` (um módulo que mexe na configuração fica bloqueado
  na demonstração), `check-vignettes` (a sua miniatura existe, no formato certo), `check-strict-types` (o contador nunca
  pode descer — declara `declare(strict_types=1)` nos teus ficheiros novos), depois `check-install-profiles` (instala
  mesmo cada perfil e chama as rotas dos módulos ausentes, que têm de devolver um **404 limpo**, nunca um 500) e
  `check-js-console` (abre as páginas da administração num navegador e recusa qualquer erro JS).
- PHPStan: `vendor/bin/phpstan analyse`.

A lista completa está em [`tools/README.md`](../../../tools/README.md); `php tools/check-all.php --navigateur` corre-as
todas.

## 9. Instalar e distribuir

1. Põe `modules/notes/` no sítio.
2. **Administração → Temas & Addons → Analisar o disco** → marca `notes` → instala (a instalação executa
   `install/install.sql`, marca as migrações como base, define as permissões).
3. Para o distribuir: comprime a pasta (`modules/notes/` na raiz do arquivo), instala-se por **Adicionar (ZIP)**. Para o
   publicar no catálogo do marketplace do projeto, tem de ter `'core' => FALSE` e nenhum `'distributed' => FALSE`;
   `tools/package-addons.php` comprime e inscreve cada addon opcional em `marketplace/catalog.json` com a sua impressão
   SHA-256.
4. A sua miniatura, antes de empacotar: um **cartão** desenhado a partir do seu `__info()` — o seu ícone, o seu nome e a
   sua descrição em francês —, legível em qualquer tamanho, mesmo quando o módulo não tem nada para mostrar:
   `php tools/vignettes-cartes.php --nom=notes --sortie=/tmp/cartes` para o ver, depois `--poser` para o colocar.
   Formato e verificações: [Criar um tema, § 5](create-a-theme.md).
