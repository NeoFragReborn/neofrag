# Criar um widget

Um **widget** é um bloco reutilizável que se coloca em qualquer zona de um tema, com o Editor ao vivo. É o addon mais
simples de escrever: uma classe, um controlador, uma vista.

Vamos criar um widget `hello` que mostra uma mensagem de boas-vindas configurável. Tudo está verificado no código;
`widgets/html` e `widgets/about` são bons exemplos reais.

## Estrutura dos ficheiros

```
widgets/hello/
├── hello.php                 # a classe do widget (metadados, declarações, tipos)
├── controllers/
│   ├── index.php             # o controlador: prepara e mostra a vista
│   ├── checker.php           # valida e completa as definições (recomendado assim que haja definições)
│   └── admin.php             # o formulário das definições (opcional)
├── views/
│   ├── index.tpl.php         # o modelo HTML
│   └── admin.tpl.php         # modelo das definições (opcional)
├── css/
│   └── hello.css             # estilos (opcional)
├── images/
│   └── thumbnail.jpg         # a sua miniatura, 960 × 600 — um cartão: o seu ícone, o seu nome, o que faz
└── js/
    └── hello.js              # opcional — JavaScript puro, nunca jQuery
```

## 1. A classe — `hello.php`

```php
<?php
namespace NF\Widgets\Hello;

use NF\NeoFrag\Addons\Widget;

class Hello extends Widget
{
    protected function __info()
    {
        return [
            'title'       => $this->lang('Bienvenue'),
            'description' => $this->lang('Affiche un message de bienvenue personnalisable.'),
            'icon'        => 'fas fa-hand-spock',   // OBRIGATÓRIO, e tem de existir no FontAwesome incluído
            'author'      => 'O Teu Nome',
            'license'     => 'LGPLv3',
            'version'     => '1.0.0',
            'depends'     => ['neofrag' => '1.2.50'],   // a versão do núcleo de que precisa no mínimo — sem ela, «Adicionar» recusa o arquivo

            // Declarações de desacoplamento — OBRIGATÓRIAS (a CI recusa um addon mudo).
            'core'        => FALSE,
            'presets'     => ['gaming', 'communaute'],
            'requires'    => [],                     // p. ex. ['teams'] se o widget ler nf_teams

            // Opcional: vários tipos = vários métodos do controlador, escolhidos ao colocá-lo.
            'types'       => [
                'index' => $this->lang('Message'),
            ],
        ];
    }
}
```

- O `namespace` **tem de** seguir a pasta: `NF\Widgets\<Nome>`.
- `icon` é obrigatório: o assistente para adicionar widgets do Editor ao vivo mostra os widgets como **cartões com
  ícone**, e `tools/check-addon-declarations.php` recusa um nome ausente do FontAwesome incluído (os ícones mudaram de
  nome entre a versão 5 e a 6).
- `core`, `presets`, `requires`: as mesmas regras que para um módulo — vê [Criar um módulo](create-a-module.md), «As três
  declarações de desacoplamento».
- Um widget que **mostra o que um módulo faz** — as suas notícias, o seu fórum, a sua galeria — declara esse módulo em
  `requires`, mesmo que tenha o seu nome: sem isso, ou quando está desativado, o widget não aparece e o Editor ao vivo
  não o oferece; «Adicionar» recusa instalá-lo sozinho, e o instalador deixa-o fora de um perfil que não tenha o módulo.
  Os seus `presets` são os do módulo (`tools/check-addon-declarations.php`, regra 9). Um widget que vive sem o módulo
  (lê tabelas do núcleo) não o declara, e só põe o que dele depende, uma ligação por exemplo, atrás de
  `$this->module('x')` e `is_enabled()`.
- As **definições** não se declaram nesta classe mas em `controllers/admin.php` (§5).

## 2. O controlador — `controllers/index.php`

```php
<?php
namespace NF\Widgets\Hello\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
    public function index($settings = [])
    {
        return $this->css('hello')->view('index', [
            'message' => $settings['message'],
        ]);
    }
}
```

O controlador recebe os `$settings` — **já validados e completados pelo checker** (§6) —, prepara os dados, carrega o seu
CSS e mostra a sua vista. A cadeia `->css('hello')->view('index', $data)` é o padrão habitual. Um widget com vários
`types` tem um método por tipo.

## 3. A vista — `views/index.tpl.php`

```php
<div class="hello-widget">
    <i class="fas fa-hand-spock"></i>
    <span><?php echo nf_texte($message) ?></span>
</div>
```

**Faz sempre o escape** do que vem de uma definição ou da base de dados (`nf_texte()`). Um título escrito por um
membro e posto como `innerHTML` do lado do JS tornar-se-ia uma injeção armazenada, servida a todos.

`nf_texte()` descodifica e depois faz o escape: um texto que o site guardou codificado («`&eacute;`») aparece
corretamente, onde `htmlspecialchars()` o codificaria uma segunda vez — o visitante leria «`&eacute;`».
`check-double-codage` recusa `htmlspecialchars()` no produto.

## 4. O CSS — `css/hello.css` (opcional)

```css
.hello-widget { display: flex; align-items: center; gap: 10px; padding: 14px 16px; color: var(--nf-text); }
.hello-widget i { color: var(--nf-accent); }
```

**Usa só o vocabulário `--nf-*` partilhado**, o que **todos** os temas definem: `--nf-bg`, `--nf-bg-elevated`,
`--nf-surface`, `--nf-surface-2`, `--nf-surface-3`, `--nf-hover`, `--nf-border`, `--nf-border-strong`, `--nf-text`,
`--nf-text-strong`, `--nf-text-soft`, `--nf-text-muted`, `--nf-muted`, `--nf-muted-2`, `--nf-accent`,
`--nf-accent-soft`, `--nf-accent-strong`, `--nf-accent-text`, `--nf-on-accent`, `--nf-success`, `--nf-info`,
`--nf-warning`, `--nf-danger`, `--nf-danger-soft`, `--nf-radius`, `--nf-radius-sm`, `--nf-font-display`.

Uma variável que um tema não tivesse definido cairia no seu valor de recurso — foi assim que apareceram **blocos
brancos** nos temas escuros. `tools/check-css-variables.php` recusa qualquer variável usada por um widget ou por um
módulo que nem todos os temas definam. Não ponhas um valor de recurso: esconderia o defeito em vez de o revelar.

Grelha: `col-12 col-lg-6`, nunca `col-6` sozinho (o Bootstrap 5 aplica-o a partir de 0 px).

## 5. As definições — `controllers/admin.php` (opcional)

Para que um widget seja **configurável**, acrescenta `controllers/admin.php`: mostra o formulário das definições, e os
seus campos alimentam os `$settings` que o controlador `index` recebe. A classe estende `Controller` (não `Widget`):

```php
<?php
namespace NF\Widgets\Hello\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
    public function index($settings = [])
    {
        $settings = (array) $settings + ['message' => ''];

        return $this->view('admin', [
            'message' => $settings['message'] ?: $this->lang('Bienvenue sur le site !'),
        ]);
    }
}
```

A vista `views/admin.tpl.php` mostra os campos; cada campo chama-se `settings[<chave>]`. Cada linha tem `nf-field`, a
classe de campo do produto: dá o espaçamento que cada tema fixa para todos os formulários.

```php
<div class="nf-field row">
    <label for="settings-message" class="col-12 col-lg-4 col-form-label"><?php echo $this->lang('Message') ?></label>
    <div class="col-12 col-lg-8">
        <input class="form-control" type="text" name="settings[message]" id="settings-message"
               value="<?php echo nf_texte($message) ?>">
    </div>
</div>
```

No Editor ao vivo, o assistente para adicionar mostra o passo «Configuração» **se e só se** este controlador mostrar
alguma coisa: um widget sem definições simplesmente não tem `controllers/admin.php`.

## 6. Validar e completar as definições — `controllers/checker.php`

**Um widget pode chegar sem definições**: colocado pelo `install()` de um tema, adicionado no Editor ao vivo antes de
abrir o seu formulário, ou restaurado a partir de uma disposição antiga. O seu checker tem de devolver definições
utilizáveis mesmo quando não lhe é dada nenhuma. Doze widgets não o faziam; um usava uma chave ausente como divisor —
divisão por zero, widget morto.

O método `index($settings)` recebe as definições **em bruto** e **devolve** um array **validado e completado com os seus
valores predefinidos**, que se torna os `$settings` do controlador:

```php
<?php
namespace NF\Widgets\Hello\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
    public function index($settings = [])
    {
        // Primeiro o recurso: `+` só preenche as chaves ausentes, os valores dados ficam intactos.
        $settings = (array) $settings + ['message' => '', 'align' => ''];

        return [
            'message' => $settings['message'] !== '' ? $settings['message'] : $this->lang('Bienvenue !'),
            'align'   => in_array($settings['align'], ['text-start', 'text-center', 'text-end'], TRUE)
                       ? $settings['align'] : 'text-start',
        ];
    }
}
```

`tools/check-widget-reglages.php` recusa qualquer leitura `$settings['chave']` que não esteja nem protegida na sua linha
(`isset`, `??`, `empty`, `array_key_exists`) nem coberta por este recurso no início do método. Um exemplo real completo:
`widgets/about/controllers/checker.php`.

## 7. JavaScript num widget

- Carrega-o com `->js('hello')` no controlador; o ficheiro é `js/hello.js`.
- **JavaScript puro, sem jQuery**: `NF.ready(fn)`, `NF.data(el, 'key')`, `NF.ajax({url, method, data, dataType})`,
  `NF.post(url, data)`, `NF.setHtml(el, html)` (os `<script>` inseridos voltam a ser executados com o nonce certo).
  `tools/check-js-sources.php` recusa `$(…)`.
- Os endereços AJAX leem-se na marcação (`data-url="…"`) em vez de PHP interpolado no `.js`, o que torna o ficheiro
  testável tal como está no banco de testes (`tests/Browser/*.test.html`).
- Um script que falha antes de ligar os seus ouvintes não estraga nada visível: `tools/check-js-console.php` abre as
  páginas num navegador real e vê-o.

## 8. Instalar, colocar, distribuir

1. Põe `widgets/hello/` no teu site.
2. **Administração → Temas & Addons → Analisar o disco**, marca `hello`, instala.
3. No **Editor ao vivo**, adiciona-o a uma zona: o assistente oferece o widget, o seu tipo, um título e depois a sua
   configuração se tiver uma. Um widget pode ser colocado várias vezes, com definições diferentes.
4. Para o distribuir: comprime a pasta (`widgets/hello/` na raiz do arquivo) — instala-se por **Adicionar (ZIP)** — ou
   deixa `tools/package-addons.php` inscrevê-lo no catálogo do marketplace.
5. A sua miniatura: um **cartão** desenhado a partir do seu `__info()`, como o de um módulo:
   `php tools/vignettes-cartes.php --nom=hello --sortie=/tmp/cartes` para o ver, depois `--poser`. Formato e
   verificações: [Criar um tema, § 5](create-a-theme.md).

`tools/check-widget-contract.php` chama mesmo cada par widget/tipo para verificar que nenhum se estraga ao ser colocado.
