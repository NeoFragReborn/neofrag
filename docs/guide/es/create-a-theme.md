# Crear un tema

Un **tema** da al sitio todo su aspecto: la maquetación (navegación, regiones, pie de página), el estilo (colores,
tipografías) y las **disposiciones** por defecto (qué widgets, dónde).

Esbozamos un tema `aurora`. La forma más sencilla de empezar es **clonar `themes/nebula/`** (el tema público incluido) y
adaptarlo. Todo lo que sigue está comprobado contra el código.

## Estructura de archivos

```
themes/aurora/
├── aurora.php                # clase: zonas, regiones, recursos, disposiciones
├── views/
│   ├── body.tpl.php          # el esqueleto de la página (muestra las regiones)
│   └── live_editor/          # row.tpl.php, widget.tpl.php: estilos ofrecidos en el Editor en vivo
├── css/
│   ├── style.css             # el estilo — define TODO el vocabulario --nf-*
│   └── sass/                 # opcional: SCSS compilado en el servidor
├── js/
│   ├── theme.js              # el modo claro/oscuro del tema
│   └── aurora.js             # las interacciones del tema
├── images/
│   └── thumbnail.jpg         # miniatura 960 × 600: tarjeta del addon y marketplace
└── install/
    └── migrations/           # cambios en las disposiciones incluidas (opcional)
```

## 1. La clase — `aurora.php`

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
            'author'      => 'Tu Nombre',
            'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
            'version'     => '1.0.0',
            'depends'     => ['neofrag' => '1.2.50'],   // la versión del núcleo que necesita como mínimo — sin ella, «Añadir» rechaza el archivo

            // Declaraciones de desacoplamiento — OBLIGATORIAS.
            'core'        => FALSE,
            'presets'     => [],
            'requires'    => [],

            // Las zonas, en orden, por su TÍTULO (install() usa este título)…
            'zones'       => ['Header', 'Avant-contenu', 'Contenu', 'Post-contenu', 'Footer'],
            // … y las REGIONES con nombre que muestran las plantillas. OBLIGATORIAS para un tema público.
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
        // Los recursos de cada página, en orden de inclusión. Primero los del núcleo: Bootstrap 5, la base del
        // producto (css/nf-bs5-bridge.css: sus componentes y los colores de Bootstrap tomados de la paleta del tema),
        // FontAwesome; luego el estilo del tema; luego los scripts.
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
        // … disposiciones por defecto (mira §3) …
        return parent::install($dispositions);
    }
}
```

- `zones` declara las zonas en orden; `regions` les da un **nombre estable** que usan las plantillas.
  `tools/check-addon-contracts.php` **exige** la clave `regions` en todo tema público: es la base del trabajo sobre el
  constructor de páginas, y una plantilla que mostrara `zone(2)` dependería del orden.
- **Sin jQuery**: el núcleo ya no lo carga, y `tools/check-js-sources.php` rechaza todo script que lo llame. Bootstrap 5
  se carga con `bootstrap.bundle.min` (Popper incluido).
- `styles_row()` / `styles_widget()` muestran las listas de estilos de fila y de panel ofrecidas en el Editor en vivo;
  copia `themes/nebula/views/live_editor/` para empezar.

## 2. El esqueleto — `views/body.tpl.php`

`body.tpl.php` es el HTML de la página. **Muestra las regiones** por su nombre:

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

La protección `if ($zone = …)` dice la verdad: una región declarada **sin widgets** devuelve una cadena vacía (desde el
2026-09-17; antes, unos espacios hacían que se dibujara un banner decorado alrededor de nada). Puedes, pues, envolver
cada región en un contenedor con estilo sin riesgo.

> **Detectar la página de inicio**: `((string) $this->url->request === '')`.

Eres libre: o bien muestras las regiones tal cual (navegación dirigida por los widgets de la región `header`), o bien
**programas una barra de navegación o un pie de página propios** en la plantilla, como Nebula, para una identidad única.
Si escribes un pie de página, **no coloques además** un widget de copyright en la región `footer` de tus disposiciones:
el tema Extend lo mostraba dos veces.

Si dejas que los visitantes cambien de tema, escribe `<?php echo nf_selecteur_theme() ?>` en tu pie de página: el núcleo
muestra el menú de los temas instalados (salvo `admin`) y recuerda la elección en una cookie del propio sitio. Solo se
muestra si hay más de un tema público, y si el administrador no ha cerrado la elección (**Preferencias generales →
Elección del tema**).

Para el idioma, `<?php echo nf_selecteur_langue() ?>` muestra igualmente el menú de idiomas del sitio, cuando
tiene varios: un formulario de verdad, que devuelve al visitante a la misma página, en el idioma elegido.

Escribe también `<?php echo nf_liens_legaux() ?>` en tu pie de página, junto a «Desarrollado por»: el núcleo muestra los
enlaces al aviso legal y a la política de privacidad, cuando sus páginas están publicadas (**Ajustes → Privacidad**), y
**«Gestionar mis cookies»**, siempre. Este último enlace es el único medio que tiene un visitante para cambiar sus
elecciones: retirar el consentimiento debe ser tan fácil como darlo. Si la línea de tu pie de página no salta de línea
(`white-space: nowrap`), deja al menos que estos enlaces lo hagan: en un teléfono, pegados al resto, hacen que la página
desborde.

### Lo que se queda pegado arriba de la pantalla

Tres banners pueden ocupar la parte superior de la ventana: el de la demo, el del mantenimiento y el de la vista previa
de los permisos. El núcleo los apila, reserva su sitio arriba de la página y publica su altura en `--nf-haut`. Si tu
cabecera se queda pegada al desplazarse, colócala debajo de ellos y márcala con `data-nf-entete`: el núcleo mide su
altura y la publica en `--nf-entete`, sobre la que se alinean a su vez las columnas pegadas de los módulos (el menú del
área de miembros, el índice del wiki).

```php
<header class="au-tete" data-nf-entete>…</header>
<aside class="au-cote" data-nf-colle>…</aside>
```

```css
.au-tete { position: sticky; top: var(--nf-haut, 0px); z-index: 1030; }
.au-cote { position: sticky; top: calc(var(--nf-haut, 0px) + var(--nf-entete, 0px) + 16px); }
```

Una columna pegada que puede ser más alta que la pantalla lleva `data-nf-colle`: cuando no cabe en el espacio que se le
deja, el núcleo la despega — pegada, su parte inferior quedaría fuera de la pantalla hasta el final de la página. Los
anclajes (`#section`) se detienen solos debajo de los banners y debajo de tu cabecera: no pongas `scroll-padding-top`.
Un elemento fijo arriba (`position: fixed`) también se alinea con `--nf-haut`; solo un panel que cubre a propósito toda
la página (una ventana, un cajón) puede partir de cero. `check-colles` rechaza un elemento pegado o fijo arriba que
ignore los banners: en la demo, la mitad de la cabecera de cuatro temas desaparecía bajo la suya en cuanto se
desplazaba.

## 3. Las disposiciones por defecto — `install()`

`install()` describe, por patrón de página y por **título de zona**, la rejilla de widgets colocada cuando se instala el
tema:

```php
// Todas las páginas: módulo principal (8 columnas) + columna lateral (4 columnas).
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

// Solo la página de inicio: un carrusel a ancho completo antes del contenido.
$dispositions->set('/', 'Avant-contenu', $this->array([
    $this->row($this->col(
        $this->widget($this->db->insert('nf_widgets', ['widget' => 'slider', 'type' => 'index']))
    ))->style('row-default')
]));
```

- `set($page, $zone, [...filas])`: `$page` es un patrón (`*`, `/`, `forum/*`…), gana el más preciso.
- `$this->widget($id)` recibe el **id** de una fila `nf_widgets` recién insertada (widget, tipo, ajustes en JSON). Un
  widget colocado **sin ajustes** debe arreglárselas: es el trabajo de su checker.
- `->size('col-12 col-lg-8')` — rejilla de Bootstrap 5, **siempre con un punto de ruptura**: `col-8` solo se aplica desde
  0 px y aplasta la página en los teléfonos. `->style('row-default' | 'row-dark')` y
  `->style('panel-default' | 'panel-color' | 'panel-header')` dan estilo a las filas y a los paneles.
- Coloca solo widgets **del núcleo** en las disposiciones incluidas: el paquete se instala a la carta, un widget opcional
  puede no estar.
- Las **etiquetas de menú** que pones (el widget `navigation`, `$this->lang('Actualités')`) las traduce el widget al
  mostrarlas, con su propia clave: cada una debe existir en `widgets/navigation/langs/*.php`, si no se queda en francés
  en un sitio en otro idioma. `check-langs` lo comprueba.

Una instalación existente nunca vuelve a ejecutar `install()`: para corregir una disposición incluida para quienes ya
tienen el tema, escribe una **migración del tema** (`install/migrations/YYYY_MM_DD_name.up.sql`, registrada en
`nf_addon_migrations`, ejecutada en la actualización). Un ejemplo real: `themes/extend/install/migrations/`.

## 4. El estilo — `css/style.css`

**Cada tema define todo el vocabulario `--nf-*`**, en `:root` (y en su variante clara u oscura). Las hojas de estilo de
los módulos y los widgets solo usan este vocabulario; una variable que un tema olvida cae en un valor de respaldo — así
aparecieron **bloques blancos** en los temas oscuros. `tools/check-css-variables.php` rechaza toda variable usada por un
módulo o un widget que **todos** los temas no definan. El vocabulario completo — veintisiete tokens — con valores **de
ejemplo** (Nebula apunta cada uno a su paleta privada `--fg-*`, lo que le permite tener un modo claro y uno oscuro
cambiando solo esa paleta):

```css
:root {
    /* fondos y superficies */
    --nf-bg: #070a10;            --nf-bg-elevated: #0d1220;
    --nf-surface: rgba(255,255,255,.035);  --nf-surface-2: rgba(255,255,255,.06);  --nf-surface-3: rgba(255,255,255,.09);
    --nf-hover: rgba(255,255,255,.06);
    /* líneas */
    --nf-border: rgba(255,255,255,.09);    --nf-border-strong: rgba(255,255,255,.18);
    /* textos */
    --nf-text: #e7eef6;  --nf-text-strong: #ffffff;  --nf-text-soft: #b7c2d0;  --nf-text-muted: #8593a6;
    --nf-muted: #8593a6; --nf-muted-2: #66748a;
    /* acento — y el color puesto SOBRE él */
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

Dos trampas medidas en los temas incluidos:

- **`--nf-on-accent` debe recalcularse cuando cambia el acento.** Si tu segunda paleta (modo claro, una variante)
  sustituye `--nf-accent`, redefine también el color puesto sobre él, si no el texto de los botones conserva el del otro
  acento — blanco sobre turquesa, 1,86:1. `tools/check-contraste.php` mide el contraste WCAG de cada tema en los dos
  modos.
- **Nada de `.row { margin: -15px }`**: es el margen de Bootstrap 4; Bootstrap 5 usa −12 px mediante `--bs-gutter-x`, y
  los 3 px de diferencia hacen desbordar la página. `tools/check-responsive.php` mide el desbordamiento horizontal a 390,
  768 y 1400 px; `tools/check-classes-bs4.php` rechaza las clases desaparecidas de Bootstrap **3 y 4** (`card-columns`,
  `float-right`, `panel-body`, `badge-danger`…), tanto en las plantillas como en las hojas de estilo — redefinir
  `.badge-danger` en tu tema no la hace volver, la comprobación lo rechaza —, los atributos `data-*` sin el prefijo `bs`
  — un `data-target` ya no hace nada, sin decirlo — y las clases que el código **construye** por concatenación. Un tema
  debe usar los nombres de Bootstrap 5: `float-end`, `text-start`, `g-0`, `ms-2`,
  `bg-danger-subtle text-danger-emphasis`. Para teñir estos colores a tu estilo, redefine las variables de Bootstrap
  (`--bs-danger-text-emphasis`…) en lugar de las clases.

### Colores ajustables

Un tema deja elegir tres colores — el acento, el fondo, el texto — en su página de configuración, con una **vista previa
en directo** sobre una página real del sitio y el **rechazo de un color ilegible**. El núcleo lo hace todo; el tema
declara sus colores originales en `controllers/admin.php`:

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

Su hoja de estilo es entonces una **plantilla PHP** que lee los ajustes `<tema>_theme_color`, `<tema>_background_color`
y `<tema>_text_color`, con los mismos valores originales como respaldo:

```php
<?php $accent = $this->config->aurora_theme_color ?: '#2dd4bf'; ?>
:root { --nf-accent: <?php echo $accent ?>; }
```

Un tema que ya tiene su página de configuración solo le añade la vista previa, bajo sus selectores:
`nf_apercu_couleurs($this->__caller, [...colores originales])`. Un ejemplo real: `themes/nebula/`.

### La base común de los temas — `css/nf-socle-themes.css` (opcional)

Blockcraft, Chronique, Extend, Forge, Granite y Pulse se apoyan en una **base común**: lo que sus hojas de estilo tenían
en común (el reset, los enlaces, las tarjetas, los formularios, las listas, la barra de miembro, los puntos de
presencia…), escrito una sola vez en el núcleo, solo con el vocabulario `--nf-*`. Un tema la adopta cargándola **antes**
de su propia hoja de estilo, que conserva su identidad y puede redefinirlo todo:

```php
$this->css('bootstrap.min')->css('nf-bs5-bridge')
     ->css('icons/fontawesome.min')
     ->css('nf-socle-themes')   // la base común de los temas
     ->css('style')
     ->css('nf-apres-theme');
```

Además de los tokens de arriba, usa estos, que define el tema que la carga (los seis temas que la cargan los apuntan a
su paleta privada) — `tools/check-css-variables.php` rechaza un tema que carga la base sin definir cada token que usa:

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

Un tema que no la carga — Nebula, o un tema que sigue en una versión anterior — no tiene nada que cambiar. La base llegó
con la versión 1.2.32 del núcleo: un tema que la carga solo se instala en un núcleo que la lleva (el marketplace sirve a
cada sitio el catálogo de su propia versión).

El CSS puede ser una **plantilla PHP**; el `?v=` se basa en la fecha del archivo, cualquier edición invalida la caché.
Una carpeta `css/sass/` se compila en el servidor (scssphp) en la instalación y desde Administración → Herramientas.

**Modo claro / oscuro.** Cada tema gestiona el suyo en `js/theme.js` (un atributo `data-theme` en `<html>`, la
preferencia recordada por el navegador con **su propia clave**, no una compartida). Un tema «solo oscuro» fuerza
`document.documentElement.setAttribute('data-theme', 'dark')`.

## 5. La miniatura — `images/thumbnail.jpg`

La tarjeta del tema en **Temas & Addons** y su página en el marketplace muestran `images/thumbnail.jpg`: un **JPEG de
960 × 600** (16:10), 200 KB como máximo. Es una **foto de la página de inicio tal como la ve un visitante**, nunca una
maqueta. `tools/capturer-apercus.php` la toma en un sitio donde el tema está instalado; con `--basculer-theme`, lo
convierte en el tema por defecto durante la foto y luego restaura el anterior. Hazlo en un sitio de pruebas lleno con
el contenido de la demo (`install/cli.php --demo`), nunca en un sitio en servicio:

```bash
php tools/capturer-apercus.php --type=theme --nom=aurora --basculer-theme
```

`check-vignettes` rechaza entonces un addon sin miniatura, una miniatura con el formato incorrecto y dos miniaturas
idénticas; `check-marketplace` rechaza una entrada del catálogo sin vista previa. El formato y las pocas excepciones
justificadas viven en `tools/lib/vignettes.php`. Los módulos y los widgets siguen el mismo formato, pero su miniatura es
una tarjeta dibujada (`tools/vignettes-cartes.php`), no una foto: un módulo sin datos solo mostraría un marco vacío,
mientras que un tema se muestra a sí mismo.

## 6. Instalar, activar, probar

1. Pon `themes/aurora/` en su sitio.
2. **Administración → Temas & Addons → Analizar el disco** → marca `aurora` → instala.
3. **Actívalo** en su tarjeta (define el tema por defecto). Si el tema se registró sin disposiciones, la activación
   ejecuta `install()` sola. El botón **Reinstalar predeterminado** vuelve a ejecutar `install()`.
4. Antes de distribuirlo, pasa: `check-addon-declarations`, `check-addon-contracts` (la clave `regions`),
   `check-css-variables`, `check-colles`, `check-classes-bs4`, `check-js-sources`, luego `check-responsive`,
   `check-contraste`, `check-bleu-bootstrap` (el azul por defecto de Bootstrap que queda en un componente que tu paleta
   no ha recogido) y `check-js-console` en una instalación donde el tema está activo. Mira la página mostrada: un banner
   vacío, un copyright duplicado o un comentario ilegible no se ven en el código.

## Buenas prácticas

- **Tipografías**: sírvelas desde el tema o el núcleo, nunca desde Google Fonts — la política de seguridad ya no permite
  ningún origen de fuentes (`font-src 'self' data:`), y esa carga enviaba la dirección IP de cada visitante a Google.
  `php tools/polices-locales.php --nom=…` descarga una fuente de Google Fonts en `fonts/` y escribe su hoja de estilo
  `css/fonts/<nombre>.css`, que el tema carga con `->css('fonts/<nombre>')`.
- **Ningún script desde un CDN**: la CSP estricta (`script-src 'self' 'nonce-…'`) lo rechazaría, y el proyecto no
  depende de ningún tercero. Todo el JS vive en el tema o en el núcleo.
- **Cita** el proyecto original (NeoFrag, Michaël BILCOT & Jérémy VALENTIN, LGPLv3) si partes de un tema existente.
