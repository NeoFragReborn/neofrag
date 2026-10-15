# Criar um tema

Um **tema** dá ao site todo o seu aspeto: a paginação (navegação, regiões, rodapé), o estilo (cores, tipos de letra) e
as **disposições** predefinidas (que widgets, onde).

Esboçamos um tema `aurora`. A forma mais simples de começar é **clonar `themes/nebula/`** (o tema público incluído) e
adaptá-lo. Tudo o que se segue está verificado no código.

## Estrutura dos ficheiros

```
themes/aurora/
├── aurora.php                # classe: zonas, regiões, recursos, disposições
├── views/
│   ├── body.tpl.php          # o esqueleto da página (mostra as regiões)
│   └── live_editor/          # row.tpl.php, widget.tpl.php: estilos oferecidos no Editor ao vivo
├── css/
│   ├── style.css             # o estilo — define TODO o vocabulário --nf-*
│   └── sass/                 # opcional: SCSS compilado no servidor
├── js/
│   ├── theme.js              # o modo claro/escuro do tema
│   └── aurora.js             # as interações do tema
├── images/
│   └── thumbnail.jpg         # miniatura 960 × 600: cartão do addon e marketplace
└── install/
    └── migrations/           # alterações às disposições incluídas (opcional)
```

## 1. A classe — `aurora.php`

```php
<?php
namespace NF\Themes\Aurora;

use NF\NeoFrag\Addons\Theme;

class Aurora extends Theme
{
    protected function __info()
    {
        return [
            'title'       => 'Aurora',
            'description' => $this->lang('Thème communautaire sombre et néon.'),
            'author'      => 'O Teu Nome',
            'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
            'version'     => '1.0.0',
            'depends'     => ['neofrag' => '1.2.50'],   // a versão do núcleo de que precisa no mínimo — sem ela, «Adicionar» recusa o arquivo

            // Declarações de desacoplamento — OBRIGATÓRIAS.
            'core'        => FALSE,
            'presets'     => [],
            'requires'    => [],

            // As zonas, por ordem, pelo seu TÍTULO (install() usa este título)…
            'zones'       => ['Header', 'Avant-contenu', 'Contenu', 'Post-contenu', 'Footer'],
            // … e as REGIÕES com nome que os modelos mostram. OBRIGATÓRIAS para um tema público.
            'regions'     => [
                'header'         => 'Header',
                'before_content' => 'Avant-contenu',
                'content'        => 'Contenu',
                'after_content'  => 'Post-contenu',
                'footer'         => 'Footer',
            ],
        ];
    }

    public function __init()
    {
        // Os recursos de cada página, por ordem de inclusão. Primeiro os do núcleo: Bootstrap 5, a base do produto
        // (css/nf-bs5-bridge.css: os seus componentes e as cores do Bootstrap tiradas da paleta do tema),
        // FontAwesome; depois o estilo do tema; depois os scripts.
        $this->css('bootstrap.min')->css('nf-bs5-bridge')
             ->css('icons/fontawesome.min')
             ->css('style')
             ->js('bootstrap.bundle.min')
             ->js('modal')->js('notify')->js('confirm')
             ->js('theme')->js('aurora');
    }

    public function styles_row()    { return $this->view('live_editor/row'); }
    public function styles_widget() { return $this->view('live_editor/widget'); }

    public function install($dispositions = [])
    {
        $dispositions = $this->array();
        // … disposições predefinidas (vê §3) …
        return parent::install($dispositions);
    }
}
```

- `zones` declara as zonas por ordem; `regions` dá-lhes um **nome estável** que os modelos usam.
  `tools/check-addon-contracts.php` **exige** a chave `regions` em todo o tema público: é a base do trabalho sobre o
  construtor de páginas, e um modelo que mostrasse `zone(2)` dependeria da ordem.
- **Sem jQuery**: o núcleo já não o carrega, e `tools/check-js-sources.php` recusa todo o script que o chame. O
  Bootstrap 5 carrega-se com `bootstrap.bundle.min` (Popper incluído).
- `styles_row()` / `styles_widget()` mostram as listas de estilos de linha e de painel oferecidos no Editor ao vivo;
  copia `themes/nebula/views/live_editor/` para começar.

## 2. O esqueleto — `views/body.tpl.php`

`body.tpl.php` é o HTML da página. **Mostra as regiões** pelo seu nome:

```php
<nav class="au-nav">
    <a href="<?php echo url('') ?>"><?php echo nf_texte($this->config->nf_name) ?></a>
    <?php echo $this->output->region('header') ?>
</nav>

<main>
    <?php if ($zone = $this->output->region('before_content')): ?><section class="au-banner"><?php echo $zone ?></section><?php endif ?>
    <?php if ($zone = $this->output->region('content')): ?><div class="container"><?php echo $zone ?></div><?php endif ?>
    <?php if ($zone = $this->output->region('after_content')): ?><div class="container"><?php echo $zone ?></div><?php endif ?>
</main>

<footer>
    <?php echo $this->output->region('footer') ?>
</footer>
```

A proteção `if ($zone = …)` diz a verdade: uma região declarada **sem widgets** devolve uma cadeia vazia (desde
2026-09-17; antes, espaços faziam desenhar uma faixa decorada à volta do nada). Podes, portanto, envolver cada região
num contentor com estilo sem risco.

> **Reconhecer a página inicial**: `((string) $this->url->request === '')`.

És livre: ou mostras as regiões tal como estão (navegação conduzida pelos widgets da região `header`), ou **programas uma
barra de navegação ou um rodapé à medida** no modelo, como o Nebula, para uma identidade única. Se escreveres um rodapé,
**não coloques também** um widget de copyright na região `footer` das tuas disposições: o tema Extend mostrava-o duas
vezes.

Se deixares os visitantes mudar de tema, escreve `<?php echo nf_selecteur_theme() ?>` no teu rodapé: o núcleo mostra o
menu dos temas instalados (exceto `admin`) e memoriza a escolha num cookie do próprio site. Só aparece se houver mais de
um tema público, e se o administrador não tiver fechado a escolha (**Preferências gerais → Escolha do tema**).

Para o idioma, `<?php echo nf_selecteur_langue() ?>` mostra da mesma forma o menu dos idiomas do site, quando
tem vários: um formulário a sério, que leva o visitante de volta à mesma página, no idioma escolhido.

Escreve também `<?php echo nf_liens_legaux() ?>` no teu rodapé, ao lado de «Desenvolvido por»: o núcleo mostra as
ligações para o aviso legal e para a política de privacidade, quando as suas páginas estão publicadas (**Definições →
Privacidade**), e **«Gerir os meus cookies»**, sempre. Esta última ligação é o único meio que um visitante tem para mudar
as suas escolhas: retirar o consentimento tem de ser tão fácil como dá-lo. Se a linha do teu rodapé não mudar de linha
(`white-space: nowrap`), deixa pelo menos estas ligações mudar: num telemóvel, coladas ao resto, fazem a página
transbordar.

### O que fica colado ao topo do ecrã

Três faixas podem ocupar o topo da janela: a da demonstração, a da manutenção e a da pré-visualização das permissões. O
núcleo empilha-as, reserva o seu lugar no topo da página e publica a sua altura em `--nf-haut`. Se o teu cabeçalho ficar
colado ao deslocar, coloca-o por baixo delas e marca-o com `data-nf-entete`: o núcleo mede a sua altura e publica-a em
`--nf-entete`, pela qual se alinham por sua vez as colunas coladas dos módulos (o menu da área de membros, o índice da
wiki).

```php
<header class="au-tete" data-nf-entete>…</header>
<aside class="au-cote" data-nf-colle>…</aside>
```

```css
.au-tete { position: sticky; top: var(--nf-haut, 0px); z-index: 1030; }
.au-cote { position: sticky; top: calc(var(--nf-haut, 0px) + var(--nf-entete, 0px) + 16px); }
```

Uma coluna colada que pode ser mais alta do que o ecrã tem `data-nf-colle`: quando não cabe no espaço que lhe resta, o
núcleo descola-a — colada, o seu fundo ficaria fora do ecrã até ao fim da página. As âncoras (`#section`) param sozinhas
por baixo das faixas e por baixo do teu cabeçalho: não ponhas `scroll-padding-top`. Um elemento fixo no topo
(`position: fixed`) alinha-se também por `--nf-haut`; só um painel que cobre de propósito toda a página (uma janela, uma
gaveta) pode partir do zero. `check-colles` recusa um elemento colado ou fixo no topo que ignore as faixas: na
demonstração, metade do cabeçalho de quatro temas desaparecia por baixo do seu assim que se deslocava.

## 3. As disposições predefinidas — `install()`

`install()` descreve, por padrão de página e por **título de zona**, a grelha de widgets colocada quando o tema é
instalado:

```php
// Todas as páginas: módulo principal (8 colunas) + coluna lateral (4 colunas).
$dispositions->set('*', 'Contenu', $this->array([
    $this->row(
        $this->col(
            $this->widget($this->db->insert('nf_widgets', ['widget' => 'module', 'type' => 'index']))
        )->size('col-12 col-lg-8'),
        $this->col(
            $this->widget($this->db->insert('nf_widgets', ['widget' => 'user', 'type' => 'index']))->style('panel-color')
        )->size('col-12 col-lg-4')
    )->style('row-default')
]));

// Só a página inicial: um carrossel em largura total antes do conteúdo.
$dispositions->set('/', 'Avant-contenu', $this->array([
    $this->row($this->col(
        $this->widget($this->db->insert('nf_widgets', ['widget' => 'slider', 'type' => 'index']))
    ))->style('row-default')
]));
```

- `set($page, $zone, [...linhas])`: `$page` é um padrão (`*`, `/`, `forum/*`…), ganha o mais preciso.
- `$this->widget($id)` recebe o **id** de uma linha `nf_widgets` acabada de inserir (widget, tipo, definições em JSON).
  Um widget colocado **sem definições** tem de se desenrascar: é o trabalho do seu checker.
- `->size('col-12 col-lg-8')` — grelha do Bootstrap 5, **sempre com um ponto de quebra**: `col-8` sozinho aplica-se a
  partir de 0 px e esmaga a página nos telemóveis. `->style('row-default' | 'row-dark')` e
  `->style('panel-default' | 'panel-color' | 'panel-header')` dão estilo às linhas e aos painéis.
- Coloca só widgets **do núcleo** nas disposições incluídas: o pacote instala-se à la carte, um widget opcional pode não
  estar lá.
- As **etiquetas de menu** que pões (o widget `navigation`, `$this->lang('Actualités')`) são traduzidas pelo widget ao
  mostrá-las, com a sua própria chave: cada uma tem de existir em `widgets/navigation/langs/*.php`, senão fica em
  francês num site noutra língua. `check-langs` verifica-o.

Uma instalação existente nunca volta a executar `install()`: para corrigir uma disposição incluída para quem já tem o
tema, escreve uma **migração do tema** (`install/migrations/YYYY_MM_DD_name.up.sql`, registada em
`nf_addon_migrations`, executada na atualização). Um exemplo real: `themes/extend/install/migrations/`.

## 4. O estilo — `css/style.css`

**Cada tema define todo o vocabulário `--nf-*`**, em `:root` (e na sua variante clara ou escura). As folhas de estilo
dos módulos e dos widgets só usam este vocabulário; uma variável que um tema esquece cai num valor de recurso — foi assim
que apareceram **blocos brancos** nos temas escuros. `tools/check-css-variables.php` recusa qualquer variável usada por
um módulo ou um widget que **todos** os temas não definam. O vocabulário completo — vinte e sete tokens — com valores
**de exemplo** (o Nebula aponta cada um para a sua paleta privada `--fg-*`, o que lhe permite ter um modo claro e um
escuro mudando só essa paleta):

```css
:root {
    /* fundos e superfícies */
    --nf-bg: #070a10;            --nf-bg-elevated: #0d1220;
    --nf-surface: rgba(255,255,255,.035);  --nf-surface-2: rgba(255,255,255,.06);  --nf-surface-3: rgba(255,255,255,.09);
    --nf-hover: rgba(255,255,255,.06);
    /* linhas */
    --nf-border: rgba(255,255,255,.09);    --nf-border-strong: rgba(255,255,255,.18);
    /* textos */
    --nf-text: #e7eef6;  --nf-text-strong: #ffffff;  --nf-text-soft: #b7c2d0;  --nf-text-muted: #8593a6;
    --nf-muted: #8593a6; --nf-muted-2: #66748a;
    /* destaque — e a cor posta POR CIMA dele */
    --nf-accent: #2dd4bf;  --nf-accent-soft: rgba(45,212,191,.15);  --nf-accent-strong: #14b8a6;  --nf-accent-text: #99f6e4;
    --nf-on-accent: #041014;
    /* estados */
    --nf-success: #22c55e;  --nf-info: #38bdf8;  --nf-warning: #f59e0b;  --nf-danger: #ef4444;
    --nf-danger-soft: rgba(239,68,68,.18);
    /* formas */
    --nf-radius: 14px;  --nf-radius-sm: 8px;
    --nf-font-display: "Space Grotesk", system-ui, sans-serif;
}
```

Duas armadilhas medidas nos temas incluídos:

- **`--nf-on-accent` tem de ser recalculado quando o destaque muda.** Se a tua segunda paleta (modo claro, uma variante)
  substituir `--nf-accent`, redefine também a cor posta por cima dele, senão o texto dos botões mantém a do outro
  destaque — branco sobre turquesa, 1,86:1. `tools/check-contraste.php` mede o contraste WCAG de cada tema nos dois
  modos.
- **Nada de `.row { margin: -15px }`**: é a margem do Bootstrap 4; o Bootstrap 5 usa −12 px através de `--bs-gutter-x`,
  e os 3 px de diferença fazem a página transbordar. `tools/check-responsive.php` mede o transbordo horizontal a 390,
  768 e 1400 px; `tools/check-classes-bs4.php` recusa as classes desaparecidas do Bootstrap **3 e 4** (`card-columns`,
  `float-right`, `panel-body`, `badge-danger`…), nos modelos como nas folhas de estilo — redefinir `.badge-danger` no
  teu tema não a faz voltar, a verificação recusa-o —, os atributos `data-*` deixados sem o prefixo `bs` — um
  `data-target` já não faz nada, em silêncio — e as classes que o código **constrói** por concatenação. Um tema tem de
  usar os nomes do Bootstrap 5: `float-end`, `text-start`, `g-0`, `ms-2`, `bg-danger-subtle text-danger-emphasis`. Para
  tingir estas cores ao teu estilo, redefine as variáveis do Bootstrap (`--bs-danger-text-emphasis`…) em vez das
  classes.

### Cores ajustáveis

Um tema deixa escolher três cores — o destaque, o fundo, o texto — na sua página de configuração, com uma
**pré-visualização ao vivo** numa página real do site e a **recusa de uma cor ilegível**. O núcleo faz tudo; o tema
declara as suas cores originais em `controllers/admin.php`:

```php
class Admin extends Controller
{
    const COULEURS = ['theme_color' => '#2dd4bf', 'background_color' => '#070a10', 'text_color' => '#e7eef6'];

    public function index()
    {
        return nf_reglage_couleurs($this, $this->__caller, self::COULEURS);
    }
}
```

A sua folha de estilo é então um **modelo PHP** que lê as definições `<tema>_theme_color`, `<tema>_background_color` e
`<tema>_text_color`, com os mesmos valores originais como recurso:

```php
<?php $accent = $this->config->aurora_theme_color ?: '#2dd4bf'; ?>
:root { --nf-accent: <?php echo $accent ?>; }
```

Um tema que já tem a sua página de configuração só lhe acrescenta a pré-visualização, por baixo dos seus seletores:
`nf_apercu_couleurs($this->__caller, [...cores originais])`. Um exemplo real: `themes/nebula/`.

### A base comum dos temas — `css/nf-socle-themes.css` (opcional)

O Blockcraft, o Chronique, o Extend, o Forge, o Granite e o Pulse apoiam-se numa **base comum**: o que as suas folhas de
estilo tinham em comum (o reset, as ligações, os cartões, os formulários, as listas, a barra de membro, os pontos de
presença…), escrito uma só vez no núcleo, só com o vocabulário `--nf-*`. Um tema adota-a carregando-a **antes** da sua
própria folha de estilo, que mantém a sua identidade e pode redefinir tudo:

```php
$this->css('bootstrap.min')->css('nf-bs5-bridge')
     ->css('icons/fontawesome.min')
     ->css('nf-socle-themes')   // a base comum dos temas
     ->css('style')
     ->css('nf-apres-theme');
```

Além dos tokens acima, usa estes, que o tema que a carrega define (os seis temas que a carregam apontam-nos para a sua
paleta privada) — `tools/check-css-variables.php` recusa um tema que carregue a base sem definir cada token que ela usa:

```css
:root {
    --nf-radius-xs: 2px;
    --nf-shadow-sm: 0 1px 3px rgba(0,0,0,.4);  --nf-shadow: 0 6px 20px -8px rgba(0,0,0,.6);  --nf-shadow-md: 0 12px 32px -12px rgba(0,0,0,.7);
    --nf-success-soft: rgba(34,197,94,.12);  --nf-warning-soft: rgba(245,158,11,.12);  --nf-info-soft: rgba(56,189,248,.12);
    --nf-link: var(--nf-accent-text);  --nf-link-hover: var(--nf-accent);
    --nf-font-mono: "JetBrains Mono", ui-monospace, monospace;
    --nf-font: "Inter", system-ui, sans-serif;
}
```

Um tema que não a carrega — o Nebula, ou um tema ainda numa versão anterior — não tem nada a mudar. A base chegou com a
versão 1.2.32 do núcleo: um tema que a carrega só se instala num núcleo que a tenha (o marketplace serve a cada site o
catálogo da sua própria versão).

O CSS pode ser um **modelo PHP**; o `?v=` baseia-se na data do ficheiro, qualquer edição invalida a cache. Uma pasta
`css/sass/` é compilada no servidor (scssphp) na instalação e a partir de Administração → Ferramentas.

**Modo claro / escuro.** Cada tema gere o seu em `js/theme.js` (um atributo `data-theme` em `<html>`, a preferência
memorizada pelo navegador com **a sua própria chave**, não uma partilhada). Um tema «só escuro» força
`document.documentElement.setAttribute('data-theme', 'dark')`.

## 5. A miniatura — `images/thumbnail.jpg`

O cartão do tema em **Temas & Addons** e a sua página no marketplace mostram `images/thumbnail.jpg`: um **JPEG de
960 × 600** (16:10), 200 KB no máximo. É uma **fotografia da página inicial tal como um visitante a vê**, nunca uma
maqueta. `tools/capturer-apercus.php` tira-a num site onde o tema está instalado; com `--basculer-theme`, torna-o o tema
predefinido durante a fotografia, e depois repõe o anterior. Fá-lo num site de testes preenchido com o conteúdo da
demonstração (`install/cli.php --demo`), nunca num site em funcionamento:

```bash
php tools/capturer-apercus.php --type=theme --nom=aurora --basculer-theme
```

`check-vignettes` recusa depois um addon sem miniatura, uma miniatura no formato errado e duas miniaturas idênticas;
`check-marketplace` recusa uma entrada do catálogo sem pré-visualização. O formato e as poucas exceções justificadas
vivem em `tools/lib/vignettes.php`. Os módulos e os widgets seguem o mesmo formato, mas a sua miniatura é um cartão
desenhado (`tools/vignettes-cartes.php`), não uma fotografia: um módulo sem dados só mostraria uma moldura vazia,
enquanto um tema se mostra a si próprio.

## 6. Instalar, ativar, testar

1. Põe `themes/aurora/` no sítio.
2. **Administração → Temas & Addons → Analisar o disco** → marca `aurora` → instala.
3. **Ativa-o** no seu cartão (define o tema predefinido). Se o tema foi registado sem disposições, a ativação executa
   `install()` sozinha. O botão **Reinstalar predefinição** volta a executar `install()`.
4. Antes de o distribuir, passa: `check-addon-declarations`, `check-addon-contracts` (a chave `regions`),
   `check-css-variables`, `check-colles`, `check-classes-bs4`, `check-js-sources`, depois `check-responsive`,
   `check-contraste`, `check-bleu-bootstrap` (o azul predefinido do Bootstrap que ficou num componente que a tua paleta
   não retomou) e `check-js-console` numa instalação em que o tema está ativo. Olha para a página mostrada: uma faixa
   vazia, um copyright duplicado ou um comentário ilegível não se veem no código.

## Boas práticas

- **Tipos de letra**: serve-os a partir do tema ou do núcleo, nunca do Google Fonts — a política de segurança já não
  permite nenhuma origem de tipos de letra (`font-src 'self' data:`), e esse carregamento enviava o endereço IP de cada
  visitante à Google. `php tools/polices-locales.php --nom=…` transfere um tipo de letra do Google Fonts para `fonts/` e
  escreve a sua folha de estilo `css/fonts/<nome>.css`, que o tema carrega com `->css('fonts/<nome>')`.
- **Nenhum script a partir de um CDN**: a CSP estrita (`script-src 'self' 'nonce-…'`) recusá-lo-ia, e o projeto não
  depende de nenhum terceiro. Todo o JS vive no tema ou no núcleo.
- **Cita** o projeto original (NeoFrag, Michaël BILCOT & Jérémy VALENTIN, LGPLv3) se partires de um tema existente.
