# Crear un widget

Un **widget** es un bloque reutilizable que se coloca en cualquier zona de un tema, con el Editor en vivo. Es el addon
más sencillo de escribir: una clase, un controlador, una vista.

Vamos a crear un widget `hello` que muestra un mensaje de bienvenida configurable. Todo está comprobado contra el
código; `widgets/html` y `widgets/about` son buenos ejemplos reales.

## Estructura de archivos

```
widgets/hello/
├── hello.php                 # la clase del widget (metadatos, declaraciones, tipos)
├── controllers/
│   ├── index.php             # el controlador: prepara y muestra la vista
│   ├── checker.php           # valida y completa los ajustes (recomendado en cuanto hay ajustes)
│   └── admin.php             # el formulario de ajustes (opcional)
├── views/
│   ├── index.tpl.php         # la plantilla HTML
│   └── admin.tpl.php         # plantilla de los ajustes (opcional)
├── css/
│   └── hello.css             # estilos (opcional)
├── images/
│   └── thumbnail.jpg         # su miniatura, 960 × 600 — una tarjeta: su icono, su nombre, lo que hace
└── js/
    └── hello.js              # opcional — JavaScript puro, nunca jQuery
```

## 1. La clase — `hello.php`

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
            'icon'        => 'fas fa-hand-spock',   // OBLIGATORIO, y debe existir en el FontAwesome incluido
            'author'      => 'Tu Nombre',
            'license'     => 'LGPLv3',
            'version'     => '1.0.0',
            'depends'     => ['neofrag' => '1.2.50'],   // la versión del núcleo que necesita como mínimo — sin ella, «Añadir» rechaza el archivo

            // Declaraciones de desacoplamiento — OBLIGATORIAS (la CI rechaza un addon mudo).
            'core'        => FALSE,
            'presets'     => ['gaming', 'communaute'],
            'requires'    => [],                     // p. ej. ['teams'] si el widget lee nf_teams

            // Opcional: varios tipos = varios métodos del controlador, elegidos al colocarlo.
            'types'       => [
                'index' => $this->lang('Message'),
            ],
        ];
    }
}
```

- El `namespace` **debe** seguir la carpeta: `NF\Widgets\<Nombre>`.
- `icon` es obligatorio: el asistente para añadir widgets del Editor en vivo muestra los widgets como **tarjetas con
  icono**, y `tools/check-addon-declarations.php` rechaza un nombre que falte en el FontAwesome incluido (los iconos se
  renombraron entre la versión 5 y la 6).
- `core`, `presets`, `requires`: las mismas reglas que para un módulo — mira [Crear un módulo](create-a-module.md),
  «Las tres declaraciones de desacoplamiento».
- Un widget que **muestra lo que hace un módulo** — sus noticias, su foro, su galería — declara ese módulo en
  `requires`, aunque lleve su nombre: sin ello, o cuando está desactivado, el widget no se muestra y el Editor en vivo
  no lo ofrece; «Añadir» se niega a instalarlo solo, y el instalador lo deja fuera de un perfil que no tiene el módulo.
  Sus `presets` son los del módulo (`tools/check-addon-declarations.php`, regla 9). Un widget que vive sin el módulo
  (lee tablas del núcleo) no lo declara, y solo pone lo que depende de él, un enlace por ejemplo, tras
  `$this->module('x')` e `is_enabled()`.
- Los **ajustes** no se declaran en esta clase sino en `controllers/admin.php` (§5).

## 2. El controlador — `controllers/index.php`

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

El controlador recibe los `$settings` — **ya validados y completados por el checker** (§6) —, prepara los datos, carga
su CSS y muestra su vista. La cadena `->css('hello')->view('index', $data)` es el patrón habitual. Un widget con varios
`types` tiene un método por tipo.

## 3. La vista — `views/index.tpl.php`

```php
<div class="hello-widget">
    <i class="fas fa-hand-spock"></i>
    <span><?php echo nf_texte($message) ?></span>
</div>
```

**Escapa siempre** lo que viene de un ajuste o de la base de datos (`nf_texte()`). Un título escrito por un miembro
y puesto como `innerHTML` del lado JS se convertiría en una inyección almacenada, servida a todos.

`nf_texte()` decodifica y luego escapa: un texto que el sitio guardó codificado («`&eacute;`») se muestra bien,
donde `htmlspecialchars()` lo codificaría por segunda vez — el visitante leería «`&eacute;`». `check-double-codage`
rechaza `htmlspecialchars()` en el producto.

## 4. El CSS — `css/hello.css` (opcional)

```css
.hello-widget { display: flex; align-items: center; gap: 10px; padding: 14px 16px; color: var(--nf-text); }
.hello-widget i { color: var(--nf-accent); }
```

**Usa solo el vocabulario `--nf-*` compartido**, el que **todos** los temas definen: `--nf-bg`, `--nf-bg-elevated`,
`--nf-surface`, `--nf-surface-2`, `--nf-surface-3`, `--nf-hover`, `--nf-border`, `--nf-border-strong`, `--nf-text`,
`--nf-text-strong`, `--nf-text-soft`, `--nf-text-muted`, `--nf-muted`, `--nf-muted-2`, `--nf-accent`,
`--nf-accent-soft`, `--nf-accent-strong`, `--nf-accent-text`, `--nf-on-accent`, `--nf-success`, `--nf-info`,
`--nf-warning`, `--nf-danger`, `--nf-danger-soft`, `--nf-radius`, `--nf-radius-sm`, `--nf-font-display`.

Una variable que un tema no hubiera definido caería en su valor de respaldo — así aparecieron **bloques blancos** en los
temas oscuros. `tools/check-css-variables.php` rechaza toda variable usada por un widget o un módulo que no definan todos
los temas. No pongas valor de respaldo: ocultaría el defecto en lugar de revelarlo.

Rejilla: `col-12 col-lg-6`, nunca `col-6` solo (Bootstrap 5 lo aplica desde 0 px).

## 5. Los ajustes — `controllers/admin.php` (opcional)

Para que un widget sea **configurable**, añade `controllers/admin.php`: muestra el formulario de ajustes, y sus campos
alimentan los `$settings` que recibe el controlador `index`. La clase extiende `Controller` (no `Widget`):

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

La vista `views/admin.tpl.php` muestra los campos; cada campo se llama `settings[<clave>]`. Cada fila lleva `nf-field`,
la clase de campo del producto: da el espaciado que cada tema fija para todos los formularios.

```php
<div class="nf-field row">
    <label for="settings-message" class="col-12 col-lg-4 col-form-label"><?php echo $this->lang('Message') ?></label>
    <div class="col-12 col-lg-8">
        <input class="form-control" type="text" name="settings[message]" id="settings-message"
               value="<?php echo nf_texte($message) ?>">
    </div>
</div>
```

En el Editor en vivo, el asistente para añadir muestra el paso «Configuración» **si y solo si** este controlador
muestra algo: un widget sin ajustes simplemente no tiene `controllers/admin.php`.

## 6. Validar y completar los ajustes — `controllers/checker.php`

**Un widget puede llegar sin ajustes**: colocado por el `install()` de un tema, añadido en el Editor en vivo antes de
abrir su formulario, o restaurado desde una disposición antigua. Su checker debe devolver ajustes utilizables incluso
cuando no se le da ninguno. Doce widgets no lo hacían; uno usaba una clave ausente como divisor — división por cero,
widget muerto.

El método `index($settings)` recibe los ajustes **en bruto** y **devuelve** un array **validado y completado con sus
valores por defecto**, que se convierte en los `$settings` del controlador:

```php
<?php
namespace NF\Widgets\Hello\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
    public function index($settings = [])
    {
        // El respaldo primero: `+` solo rellena las claves ausentes, los valores dados quedan intactos.
        $settings = (array) $settings + ['message' => '', 'align' => ''];

        return [
            'message' => $settings['message'] !== '' ? $settings['message'] : $this->lang('Bienvenue !'),
            'align'   => in_array($settings['align'], ['text-start', 'text-center', 'text-end'], TRUE)
                       ? $settings['align'] : 'text-start',
        ];
    }
}
```

`tools/check-widget-reglages.php` rechaza toda lectura `$settings['clave']` que no esté protegida en su línea (`isset`,
`??`, `empty`, `array_key_exists`) ni cubierta por este respaldo al principio del método. Un ejemplo real completo:
`widgets/about/controllers/checker.php`.

## 7. JavaScript en un widget

- Cárgalo con `->js('hello')` en el controlador; el archivo es `js/hello.js`.
- **JavaScript puro, sin jQuery**: `NF.ready(fn)`, `NF.data(el, 'key')`, `NF.ajax({url, method, data, dataType})`,
  `NF.post(url, data)`, `NF.setHtml(el, html)` (los `<script>` insertados se vuelven a ejecutar con el nonce correcto).
  `tools/check-js-sources.php` rechaza `$(…)`.
- Las direcciones AJAX se leen en el marcado (`data-url="…"`) en lugar de interpolar PHP en el `.js`, lo que hace el
  archivo comprobable tal cual en el banco de pruebas (`tests/Browser/*.test.html`).
- Un script que falla antes de colgar sus oyentes no rompe nada visible: `tools/check-js-console.php` abre las páginas
  en un navegador real y lo ve.

## 8. Instalar, colocar, distribuir

1. Pon `widgets/hello/` en tu sitio.
2. **Administración → Temas & Addons → Analizar el disco**, marca `hello`, instala.
3. En el **Editor en vivo**, añádelo a una zona: el asistente ofrece el widget, su tipo, un título y luego su
   configuración si la tiene. Un widget puede colocarse varias veces, con ajustes distintos.
4. Para distribuirlo: comprime la carpeta (`widgets/hello/` en la raíz del archivo) — se instala por **Añadir (ZIP)** —
   o deja que `tools/package-addons.php` lo inscriba en el catálogo del marketplace.
5. Su miniatura: una **tarjeta** dibujada a partir de su `__info()`, como la de un módulo:
   `php tools/vignettes-cartes.php --nom=hello --sortie=/tmp/cartes` para mirarla, luego `--poser`. Formato y
   comprobaciones: [Crear un tema, § 5](create-a-theme.md).

`tools/check-widget-contract.php` llama de verdad a cada par widget/tipo para comprobar que ninguno se rompe al
colocarlo.
