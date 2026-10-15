# Crear un módulo

Un **módulo** es una funcionalidad completa: sus páginas públicas, sus rutas, su administración, sus datos y sus
permisos. Es el tipo de addon más rico.

Vamos a crear un módulo `notes` que muestra una lista de notas públicas. Todo lo que sigue está comprobado contra el
código; los módulos incluidos (`modules/contact`, `modules/news`) son los mejores ejemplos para leer después.

## Estructura de archivos

```
modules/notes/
├── notes.php                 # clase: metadatos, declaraciones, rutas, permisos
├── controllers/
│   ├── checker.php           # valida la ruta y carga los datos
│   ├── index.php             # muestra las páginas públicas
│   └── admin.php             # pantalla de administración (opcional)
├── install/
│   ├── install.sql           # las tablas del módulo (ejecutado en la instalación, idempotente)
│   ├── uninstall.sql         # eliminación de las tablas
│   └── migrations/           # cambios de esquema entre dos versiones (opcional)
├── views/
│   └── index.tpl.php
├── css/                      # opcional
├── images/
│   └── thumbnail.jpg         # su miniatura, 960 × 600 — una tarjeta: su icono, su nombre, lo que hace
├── js/                       # opcional — JavaScript puro, nunca jQuery
└── langs/
    └── fr.php                # traducciones (opcional)
```

## 1. La clase — `notes.php`

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
            'icon'        => 'fas fa-note-sticky',   // debe existir en el FontAwesome incluido
            'author'      => 'Tu Nombre',
            'license'     => 'LGPLv3',
            'version'     => '1.0.0',
            'depends'     => ['neofrag' => '1.2.50'],   // la versión del núcleo que necesita como mínimo — sin ella, «Añadir» rechaza el archivo
            'admin'       => TRUE,                   // expone una página de administración

            // Declaraciones de desacoplamiento — OBLIGATORIAS (la CI rechaza un addon mudo).
            'core'        => FALSE,                  // TRUE = siempre incluido y no se puede desinstalar
            'presets'     => ['communaute'],         // perfiles de instalación que lo marcan de antemano
            'requires'    => [],                     // addons que NECESITA (sin ellos, se rompe)

            'routes'      => [
                ''                 => 'index',       // /notes
                '{id}/{url_title}' => '_show',       // /notes/42/mi-nota
            ],
        ];
    }
}
```

### Las tres declaraciones de desacoplamiento

Desde el 2026-09-15, el paquete se instala **a la carta**: el instalador ofrece perfiles (*Completo*, *Gaming /
eSport*, *Comunidad*, *Asociación / club*, *Solo el núcleo*) construidos **a partir de estas declaraciones**. No hay
ninguna lista escrita a mano en otro sitio.

- `core` — `TRUE` para un módulo de infraestructura o de CMS siempre incluido y que no se puede desinstalar; `FALSE`
  para todo lo opcional. Un módulo del núcleo **nunca** puede depender de uno opcional (regla 3 de
  `tools/check-addon-declarations.php`).
- `presets` — los perfiles que lo marcan de antemano: `'gaming'`, `'communaute'`, `'association'` (las etiquetas de
  `install/lib/presets.php`). Vacío: solo aparece en el perfil *Completo*.
- `requires` — los addons que necesita **para no romperse**: una tabla que lee, una clase que nombra. El instalador
  los añade automáticamente cuando se marca tu módulo. Una dependencia **blanda** (un servicio que puede faltar) no se
  declara aquí: se **protege** en el código (mira §5).

Estas tres claves se comprueban estáticamente en la CI, igual que la existencia del icono declarado (regla 7:
FontAwesome renombró iconos entre la 5 y la 6). Un addon que no quieres en el catálogo del marketplace añade
`'distributed' => FALSE`.

### Las rutas

Una ruta asocia un **patrón de URL** a un **método del controlador**. El patrón es relativo al nombre del módulo (`''`
= la página de inicio del módulo, aquí `/notes`). Las rutas de administración empiezan por `admin`
(`'admin{pages}' => 'index'` para una lista paginada bajo `/admin/notes`).

> Los marcadores son un conjunto **fijo**: `{id}` (entero), `{key_id}`, `{url_title}` (slug), `{url_title*}` (slug con
> `/`), `{page}` y `{pages}` (paginación). Un marcador desconocido produce un 404 silencioso. Para una página de
> detalle, el patrón idiomático es `{id}/{url_title}`: el `id` identifica el elemento, el slug es decorativo.

## 2. El checker — `controllers/checker.php`

El **checker** se ejecuta antes que el controlador: valida la ruta y carga los datos. Devolver `FALSE` o nada provoca
un 404. Lo que **devuelve se convierte en los argumentos** del método del mismo nombre del controlador.

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

Para un punto de entrada que recibe un **POST** (AJAX, un formulario escrito a mano), el checker lee los campos con
`post_check('titre', 'corps', 'options?')`: cada nombre es **obligatorio**, salvo si lleva el sufijo `?`, en cuyo caso
vale `NULL` cuando falta. Un campo obligatorio ausente hace fallar el checker: una respuesta **404** en producción (el
motivo exacto — qué campo, qué llegó en su lugar — **se registra**) y **400 con el motivo** cuando la depuración es
visible (un administrador conectado, la herramienta de depuración activada). Este comportamiento viene de diez widgets
que ya no podían añadirse en el Editor en vivo porque se exigía un campo opcional.

## 3. El controlador público — `controllers/index.php`

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

`title()`, `icon()`, `breadcrumb()` rellenan la cabecera de la página. El controlador muestra luego su vista, o un panel
mediante `$this->panel()->title()->body($html)` para un HTML construido en PHP.

> **Nunca pases contenido de la base por `$this->title()` y luego por la traducción.** Un título escrito por el
> administrador no tiene traducción: `lang()` lo busca en vano y registra una advertencia en cada visita en otro
> idioma. Envuelve lo que viene de la base en `$this->no_translate(...)`.

## 4. La vista — `views/index.tpl.php`

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

Las variables pasadas a `view()` están disponibles directamente. **Escapa siempre** lo que viene de la base
(`nf_texte()`); para un HTML enriquecido escrito por un miembro, `sanitize_html()` (HTMLPurifier). Una
**dirección** escrita por alguien que acaba en un `href` pasa además por `nf_url_sure()`, que rechaza `javascript:` y los
demás esquemas ejecutables (mira [El framework](framework.md#seguridad-lo-que-ya-existe)).

**El front end es Bootstrap 5, sin jQuery, bajo una CSP estricta.**
- Las clases de rejilla se escriben `col-12 col-lg-8`, nunca `col-8` solo (que se aplica desde 0 px y rompe los
  teléfonos); `tools/check-classes-bs4.php` rechaza las clases de Bootstrap 4 desaparecidas.
- Un `<script>` en línea en una vista recibe automáticamente el **nonce** de la respuesta (el filtro de `index.php`);
  un archivo JS se carga con `->js('nombre')` (la carpeta `js/` del módulo).
- Escribe el JavaScript con las primitivas del núcleo — `NF.ready`, `NF.data(el, 'key')`,
  `NF.ajax({url, method, data, dataType})`, `NF.post(url, data)`, `NF.setHtml`, `NF.insertHtml`, `NF.replaceHtml` —,
  nunca `$(…)`: jQuery ya no se carga, y `tools/check-js-sources.php` rechaza cualquier llamada. Las direcciones que
  necesita el script se ponen como `data-*` en el marcado en lugar de como PHP interpolado en el `.js`.

## 5. Los datos

Un módulo **incluye sus tablas** en `install/install.sql` (y su eliminación en `uninstall.sql`). Este SQL se ejecuta
**cuando se instala el módulo** (instalador, análisis desde la administración, ZIP del marketplace), idempotente gracias
a `CREATE TABLE IF NOT EXISTS`:

```sql
-- modules/notes/install/install.sql
CREATE TABLE IF NOT EXISTS nf_notes (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body  TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

> **El contenido que se traduce** (un título, una descripción) vive en una tabla `<table>_lang`, una fila por idioma,
> leída con `join_lang()` y escrita con `ecrire_lang()`:
> [El framework](framework.md#contenido-traducido-join_lang-y-ecrire_lang).

> **¿Sin tablas propias? Sin `install.sql`.** Un módulo que reutiliza tablas del núcleo (por ejemplo `nf_file` mediante
> `model2('file')`) se instala sin tocar el esquema.

### Hacer evolucionar el esquema — migraciones por addon

`install.sql` crea las tablas que faltan pero no **cambia** una tabla ya presente en los sitios de los usuarios. Para un
cambio de esquema entre dos versiones de tu módulo:

```
modules/notes/install/migrations/2026_10_01_add_pinned.up.sql
```
```sql
ALTER TABLE nf_notes ADD COLUMN pinned TINYINT(1) NOT NULL DEFAULT 0;
```

- Nombre = **prefijo con fecha** (`YYYY_MM_DD_descripcion`), ejecutadas en orden.
- Registradas en `nf_addon_migrations`: cada migración se ejecuta **una sola vez**.
- En una instalación nueva (`install.sql` ya lleva el esquema al día), las migraciones se **marcan como base** —
  anotadas sin ejecutarse. En una actualización (marketplace → «Actualizaciones», o un archivo enviado con «Añadir»),
  solo se **ejecutan** las nuevas (`Addon::update()`). Un módulo incluido con el núcleo recibe las suyas en la primera
  visita con el código nuevo (`nf_migrations_du_code()`, mira [el framework](framework.md#migraciones)).
- Regla de oro: una migración para un cambio real en un esquema **ya distribuido**; una tabla nueva va en `install.sql`,
  nunca en una migración — **salvo** si el módulo ya está instalado en sitios: entonces la tabla nueva va en los dos,
  `CREATE TABLE IF NOT EXISTS` en la migración.
- Una actualización por el marketplace o por un archivo vuelve a ejecutar `install.sql` **antes** de las migraciones: un
  sitio que se salta versiones recibe las tablas de la última y luego ejecuta sobre ellas las migraciones antiguas. Una
  migración que escribe datos apunta, por tanto, al esquema del último `install.sql`, y se reescribe cuando este cambia
  — un sitio que ya la ejecutó la reconoce por su nombre y no la vuelve a ejecutar. Por la misma razón, nunca cambies el
  nombre de una migración.
- En el sitio de demostración, una actualización de addon lanzada desde la administración no ejecuta nada
  (`Addon::migrate()` sale cuando `nf_demo()` es verdadero); las migraciones que llegan con el código nuevo se aplican
  allí como en cualquier otro sitio.

La carpeta `migrations/` **en la raíz del proyecto** está reservada a los cambios transversales del núcleo.

### Depender de otro módulo sin romperse

El paquete se instala a la carta: el módulo que necesitas puede **no estar**. Cuatro formas de depender, y lo que pasa
si falta (`tools/check-addon-coupling.php` las detecta con el tokenizador):

| Medio | Si el addon falta | Qué hacer |
|---|---|---|
| leer **su tabla** (`from('nf_teams')`) | **fatal** — «Table doesn't exist» | declararlo en `requires`, o protegerse con `$this->db->table_exists('nf_teams')` y **anotar** |
| nombrar **su clase** | **fatal** — «Class not found» | lo mismo |
| llamar a **su servicio** (`$this->module('teams')`, `model2('team')`) | tolerante: devuelve `NULL` | probar el retorno: `if ($teams = $this->module('teams'))` |
| escribir **su URL** (`url('teams/…')`) | cosmético: un enlace muerto | aceptable |

Un acoplamiento fatal **ni declarado ni anotado hace fallar la CI**. La anotación va en el lugar exacto, como
comentario: `// couplage(teams): purge à la suppression d'un jeu — gardé par table_exists()`, o al principio del
archivo `couplage(teams): …` cuando las referencias están repartidas.

### Tipos de contenido: reacciones, suscripciones, revisiones, papelera

Si tu módulo publica **contenido** (como las noticias, los artículos, los temas), declaralo: los módulos transversales —
reacciones, notificaciones, revisiones, papelera, gamificación — lo recogen en lugar de llevar cada uno su propia lista
de tablas.

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

Un ejemplo real: `modules/news/news.php`.

### El posicionamiento de un contenido

Un tipo de contenido declarado arriba (con su `table` y su `pk`) puede recibir, como las noticias o el wiki, un título y
una descripción para los buscadores — la página compartida es la de la sección Posicionamiento, no hay nada que escribir
por ese lado. Tres llamadas:

```php
// La ficha de edición de la administración: el botón que lleva a la página compartida.
return $this->admin_card('fas fa-edit', $titre, $formulaire, '', nf_seo_bouton('note', (int) $id));

// La acción de la página pública, antes de mostrar la vista: aplica el título y la descripción introducidos.
nf_seo_contenu('note', (int) $id);

// Cuando cambia la dirección de un contenido (un nombre, un slug), la antigua lleva a la nueva (301).
nf_redirection_ajouter('notes/'.$ancien_slug, 'notes/'.$nouveau_slug);
```

Ejemplos reales: `modules/wiki/controllers/admin.php` y `modules/wiki/controllers/index.php`.

### Los concentradores: estadísticas, actividad, panel, búsqueda, mapa del sitio

Un módulo se conecta a una página que **agrega** aportando un controlador con el nombre del concentrador. El
concentrador llama al método del mismo nombre **sin comprobar nada**: renombrado, vuelto privado o con un parámetro
obligatorio más, el error solo aparece al abrir la página — por eso `tools/check-addon-contracts.php` fija estos
contratos en la CI.

| Controlador | Método esperado | Llamado por |
|---|---|---|
| `controllers/statistics.php` | `statistics()` | la página Estadísticas de la administración |
| `controllers/activity.php` | `activity($user_id, $limit)` | el perfil de un miembro |
| `controllers/dashboard.php` | `dashboard()` | el panel de la administración |
| `controllers/block.php` | `block()` | los bloques `[block:…]` de las páginas |
| `controllers/search.php` | `search()` **y** `suggest()` | la búsqueda global y la sugerencia instantánea — un módulo que solo tiene `search()` se **ignora sin aviso** |
| `controllers/sitemap.php` | `sitemap()` | el mapa del sitio (`/sitemap.xml`, uno por idioma): devuelve `[['adresse' => 'mimodulo/12/titulo', 'date' => …], …]`, rutas como las que recibe `url()`, solo lo que un **visitante** puede leer (`$this->access('mimodulo', 'read', $id, 'visitors')`) y lo que existe **en el idioma del mapa** — un contenido que **no tiene idioma propio** (un tema, un ticket, una página hecha de ese contenido) lleva `'sans_langue' => TRUE`: solo aparece en el mapa del primer idioma del sitio, y su página llama a `nf_seo_sans_langue()`, que pone allí su canónica (seis idiomas del mismo texto, cada uno canónico: Google conserva otro y no indexa ninguno); **una sección vacía devuelve `[]`**: su página solo tendría «nada todavía» que mostrar; sin el concentrador, el módulo no está en los buscadores, e IndexNow no señala sus páginas: la tarea programada compara este mismo mapa de una pasada a la siguiente |

### El área de miembros y el perfil público

Tres métodos de la **clase** del módulo, opcionales, y solo leídos si existen:

- **`espace_membre($user)`** añade páginas al **menú del área de miembros** — la columna izquierda en un ordenador, la
  banda de pestañas en un teléfono, repetida también en la barra superior de los temas y en el widget «Área de
  miembros». Devuelve una lista de entradas: `url`, `titre`, `icone`, si hace falta `badge` (un número que señalar) y
  `compact` (mostrada también en los menús cortos), y `ordre` para ordenarlas.
- **`profil_membre($membre)`** añade **pestañas al perfil público de un miembro**, cada una en su dirección
  `user/<id>/<nombre>/<pestaña>`. Devuelve una lista de pestañas: `onglet` (el segmento de la dirección: minúsculas,
  cifras y guiones), `titre`, `icone`, `ordre`, si hace falta `nombre` (mostrado junto al título y en «En cifras») y
  `contenu`, una función que muestra la página de la pestaña — solo se llama cuando se abre. **Nada que mostrar, nada
  que devolver**: una pestaña vacía no aparece, y su dirección responde 404.
- **`types_de_notification()`** declara las notificaciones que envía el módulo, para las **preferencias** de cada
  miembro (`user/notifications/preferences`): `type` (el que recibe `push()`), `titre` (lo que lee el miembro, «Una
  respuesta en un tema que sigo»), `email` a `TRUE` si el módulo sabe enviarla también por correo, y `ordre`. **Un tipo
  enviado debe declararse**: si no, el miembro no puede apagarlo — `tests/Unit/TypesDeNotificationTest.php` lee los
  envíos del repositorio y lo rechaza.

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

El perfil muestra lo que **quien mira** puede leer: una pestaña que lista contenido filtra como sus propias páginas
(`$this->access(…)`) — el foro solo muestra allí las categorías legibles. Lo que el miembro elige ocultar de sí mismo
(puntos, karma, VIP, edad, presencia en línea) se pregunta a su modelo: `$membre->montre('statut')`. Ejemplos reales:
`modules/forum/forum.php`, `modules/teams/teams.php`.

Una notificación se envía con `push($user_id, $type, $titre, $adresse, $acteur)` del módulo `notifications` (o
`push_unique()`, que no añade una segunda del mismo tipo en la misma dirección mientras la primera no se ha leído): lo
que el miembro apagó **en el sitio** no se escribe. Un módulo que la envía también por correo hace primero la pregunta
él mismo, `$notifications->veut($user_id, $type, 'email')`, y no escribe si la respuesta es no. Desde la clase del
módulo, cárgalo con `\NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['notifications'])` — `$this->module()` se equivoca
allí de tipo de addon. Un ejemplo real: `modules/talks/talks.php`.

## 6. Los permisos (opcional)

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

Los permisos se vuelven editables en **Administración → Permisos** (matriz rol × acción). En el código:
`$this->access('notes', 'add_note')`. Un ejemplo real: `modules/news/news.php`.

Un permiso **por elemento** (una categoría, una galería) declara sus valores de partida en `init`, que
`$this->access->init('notes', 'categorie', $id)` pone cuando se crea el elemento. Como el rol «miembro» no hereda del
rol «visitante», `init` sigue las reglas originales de NeoFrag cuando no se nombra `members`: una lista **vacía** hace
pública la acción (visitantes y miembros); `['visitors', TRUE]` se aplica también a los miembros;
`['visitors', FALSE]` solo rechaza a los visitantes — los miembros conservan la acción —; y `['admins', TRUE]` la
reserva a los administradores. Un ejemplo real: `modules/forum/forum.php` (leer, escribir, moderar una categoría).

## 7. La administración (opcional)

Si `'admin' => TRUE`, añade `controllers/admin.php` (clase `Admin extends Controller_Module`). Su método `index()` se
sirve bajo `/admin/notes`.

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

**`form()`** (`add_rules()` / `is_valid()` / `display()`) es la API de formularios de las pantallas de administración;
`form2()` (mira [El framework](framework.md)) es para los formularios públicos, ricos o de solo confirmación. El trait
`Admin_Helpers`, disponible en todo controlador de módulo, viste el contenido: `admin_card()`, `admin_create()`,
`admin_back()`, `admin_split()`, `admin_action_bar()`, `admin_empty()`, `admin_stats()`, `sort_select()`. Cada
subpágina debe ofrecer una vuelta atrás (`admin_back()` o la miga de pan): `tools/check-admin-back.php` lo comprueba en
cada página de la administración.

### Las acciones que modifican: exigir un token CSRF

Toda acción que **cambia** algo y que se lanza con un **enlace GET** o un **POST escrito a mano** (fuera de `form()` /
`form2()`, que llevan su propio token) debe protegerse: `SameSite=Lax` no basta.

```php
// Construcción del enlace: token en la consulta.
$html .= '<a href="'.$this->csrf_url('admin/notes/delete/'.$id).'">'.icon('far fa-trash-alt').'</a>';

// Controlador: comprueba el token ANTES de cambiar nada, si no redirige.
public function _delete($note)
{
    $this->check_csrf('admin/notes');
    $this->db->where('id', $note['id'])->delete('nf_notes');
    notify($this->lang('Note supprimée.'));
    redirect('admin/notes');
}
```

Para un POST escrito a mano, el token va en un campo oculto:
`<input type="hidden" name="_" value="<?php echo $this->csrf_token() ?>">`.

### Las tablas de la administración

`table2()` muestra listas paginadas, ordenables haciendo clic en el encabezado y filtrables (ejemplo:
`modules/user/controllers/admin.php`). El orden lo gestiona `js/table2.js`, en JavaScript puro. Una lista que el checker
divide a mano (`->paginate()`) muestra sus enlaces de página: el contrato está en
[El framework](framework.md#tablas--table2), y `check-pagination` lo comprueba.

### La guía de estilo de la administración

La administración es **sobria**: el fondo se borra detrás de los datos, el color de acento es escaso, y los colores
vivos se reservan para lo que pide una acción o señala un estado. Una página de módulo se construye así:

- **Una tarjeta por lista**: `admin_card(icono, título, cuerpo, subtítulo, acciones)` — el subtítulo lleva la cuenta
  («3 publicadas · 1 borrador»), las acciones el botón que crea.
- **El botón que crea** («Nueva cita»): `admin_create(url, etiqueta)`, un `btn btn-primary btn-sm` en la cabecera de la
  tarjeta de la lista que alimenta. La barra superior conserva las herramientas de la página — Permisos, Configuración,
  Ayuda.
- **Las acciones de una fila**: botones solo con icono, pequeños. *Modificar* (lápiz), *acceso* (candado), *ordenar*:
  `btn-outline-secondary`, neutros. *Eliminar*: `btn-outline-danger` con el icono `far fa-trash-alt`. Nunca un botón
  relleno en una fila. `button_update()`, `button_access()` y `button_delete()` los muestran así; un botón escrito a mano
  sigue la misma regla, que `tools/check-actions-admin.php` comprueba.
- **Las demás acciones de una fila** (vista previa, duplicar, restaurar): un contorno, nunca un botón relleno; un tinte
  solo si lleva un sentido (`btn-outline-warning` para una vista previa que cambia el modo de navegación,
  `btn-outline-success` para restaurar).
- **Los badges de estado**: `badge text-bg-success` (publicado, activo), `text-bg-secondary` (borrador, inactivo),
  `text-bg-warning` (pendiente), `text-bg-danger` (error) — el tema de la administración los suaviza (fondo pálido,
  texto marcado), en claro como en oscuro.
- **Una tabla** para los datos en columnas (título, cuenta, estado); **tarjetas** (`nf-content-card`) para el contenido
  redactado que se reconoce por su extracto. Una lista agrupada por familia (Plantillas de correo) sigue siendo **una
  sola tabla**, con una fila `<tr class="nf-table-groupe">` al principio de cada familia, en lugar de una tarjeta por
  familia. Una fecha de la base se muestra allí con `nf_date_heure()`, nunca tal cual.
- **El estado vacío**: `admin_empty(icono, título, texto)`, que dice qué hacer para empezar.
- **El botón que envía un formulario**: «Guardar» para un elemento que existe — nunca «Modificar», ya lo estás
  modificando —, «Añadir» o «Crear» para uno nuevo, como el título de la página («Añadir un foro»).
- **Varias vistas del mismo tema** (Monitorización, Usuarios, Discord): pestañas `nf-local-nav` / `nf-local-tab` — como
  enlaces a otras direcciones, o como botones que cambian de panel.

## 8. Probar el módulo

El proyecto tiene una red de seguridad, y un módulo nuevo debe entrar en ella:

- **Pruebas** — `tests/Unit` (sin base), `tests/Headless` (el framework arrancado, modelos reales contra la base de
  pruebas, cada prueba en una transacción anulada — `HeadlessTestCase`), `tests/Integration` (espejo SQL),
  `tests/Browser/*.test.html` (el contrato de un script en un navegador real, mediante `tools/check-js.php`).
  `vendor/bin/phpunit --fail-on-skipped`.
- **Comprobaciones** que pasar antes de distribuir: `check-addon-declarations`, `check-addon-coupling`,
  `check-addon-contracts`, `check-js-sources`, `check-langs --toutes`, `check-textes-en-dur` (ningún texto visible fuera
  de `lang()`), `check-actions-admin` (la guía de estilo de los botones), `check-pagination`, `check-heures` (ninguna
  fecha mostrada sin zona horaria), `check-db-compteurs`, `check-demo-lock` (un módulo que toca la configuración queda
  bloqueado en la demo), `check-vignettes` (su miniatura existe, con el formato correcto), `check-strict-types` (el
  contador nunca debe bajar — declara `declare(strict_types=1)` en tus archivos nuevos), luego
  `check-install-profiles` (instala de verdad cada perfil y llama a las rutas de los módulos ausentes, que deben
  devolver un **404 limpio**, nunca un 500) y `check-js-console` (abre las páginas de la administración en un navegador
  y rechaza cualquier error JS).
- PHPStan: `vendor/bin/phpstan analyse`.

La lista completa está en [`tools/README.md`](../../../tools/README.md); `php tools/check-all.php --navigateur` las
ejecuta todas.

## 9. Instalar y distribuir

1. Pon `modules/notes/` en su sitio.
2. **Administración → Temas & Addons → Analizar el disco** → marca `notes` → instala (la instalación ejecuta
   `install/install.sql`, marca las migraciones como base, pone los permisos).
3. Para distribuirlo: comprime la carpeta (`modules/notes/` en la raíz del archivo), se instala por **Añadir (ZIP)**.
   Para publicarlo en el catálogo del marketplace del proyecto, debe tener `'core' => FALSE` y ningún
   `'distributed' => FALSE`; `tools/package-addons.php` comprime e inscribe cada addon opcional en
   `marketplace/catalog.json` con su huella SHA-256.
4. Su miniatura, antes de empaquetar: una **tarjeta** dibujada a partir de su `__info()` — su icono, su nombre y su
   descripción en francés —, legible a cualquier tamaño, incluso cuando el módulo no tiene nada que mostrar:
   `php tools/vignettes-cartes.php --nom=notes --sortie=/tmp/cartes` para mirarla, luego `--poser` para colocarla.
   Formato y comprobaciones: [Crear un tema, § 5](create-a-theme.md).
