# El framework

Una referencia de las piezas que manejas al escribir addons. Para la arquitectura interna completa, consulta
[`docs/architecture.md`](../../architecture.md). Todo lo que sigue está comprobado contra el código.

## El localizador de servicios — `NeoFrag()`

Todo el framework es accesible mediante el singleton global `NeoFrag()` y, dentro de una clase de addon, mediante
`$this` (que delega en él). Los servicios se obtienen con métodos mágicos:

```php
$this->db        // acceso a la base de datos
$this->config    // configuración del sitio (nf_name, nf_default_theme…)
$this->user      // miembro actual
$this->url       // petición / segmentos / base
$this->lang(...) // traducción
$this->events    // eventos internos (fire / on)
$this->module('forum');        // un módulo — NULL si no está instalado
NeoFrag()->model2('addon');    // un modelo
```

> `__call` **se traga las llamadas desconocidas**: `$this->metodo_ausente()` no rompe nada. Para probar una
> comprobación, inyecta un fallo que la magia no capture (una función global ausente).

## El enrutamiento

Un módulo declara sus rutas en `__info().routes`: `patrón => método`.

```php
'routes' => [
    ''                 => 'index',   // la página de inicio del módulo
    '{id}/{url_title}' => '_show',   // /modulo/42/slug
    'admin{pages}'     => 'index',   // administración paginada
],
```

- Marcadores **fijos**: `{id}` (entero), `{key_id}`, `{url_title}` (slug), `{url_title*}`, `{page}` y `{pages}`
  (paginación). Un marcador desconocido → 404 silencioso.
- Ciclo: el **checker** (`controllers/checker.php`) valida la ruta y carga los datos; lo que **devuelve** se convierte
  en los argumentos del método del mismo nombre del **controlador** (`controllers/index.php`).
- El enrutador actual es `ruta → módulo → página → 404`. Las **regiones con nombre** de los temas
  (`region('content')`) son la base del futuro enrutamiento centrado en las páginas (outlines, rutas reservadas), aún
  no escrito.

> En las pruebas o con `curl`, el CMS trata a los agentes que no son navegadores como robots y **no abre la sesión**:
> envía un **User-Agent de navegador**.

### El rechazo de un checker queda en el registro

Cuando un checker falla — un campo POST ausente, un destino que no existe —, el sitio responde **404**; el motivo
(ruta, checker, método, campo ausente y lo que llegó en su lugar) **siempre se registra** (`[checker] …`). Cuando la
depuración es visible — la herramienta de depuración activada, para un administrador conectado —, el motivo se devuelve
al cliente y el estado es **400**: «tu petición está mal formada» en lugar de «esta dirección no existe».
`post_check('a', 'b?')`: el sufijo `?` hace opcional un campo (`NULL` si falta).

## Base de datos

Un constructor de consultas fluido; las tablas llevan el prefijo `nf_`.

```php
$rows = $this->db
    ->select('id', 'title', 'created')
    ->from('nf_news')
    ->where('published', '1')
    ->where('author_id', $user_id)
    ->order_by('created DESC')
    ->limit(10)
    ->get();                 // array de filas; ->row() para una sola

$id = $this->db->insert('nf_news', ['title' => $t, 'body' => $b]);  // devuelve el id
$this->db->where('id', $id)->update('nf_news', ['title' => $t2]);
$this->db->where('id', $id)->delete('nf_news');

$this->db->transaction(); … $this->db->commit();   // o ->rollback()
```

- **`table_exists('nf_teams')`** — verdadero si la tabla existe. El paquete se instala a la carta: un módulo del núcleo
  que lee la tabla de un módulo opcional **debe** protegerse así (o declarar la dependencia en `requires`). El
  resultado se guarda en caché durante la petición.
- **`import($sql)`** — ejecuta un SQL de varias sentencias (DDL de instalación) fuera de la cadena de sentencias
  preparadas.
- Una **unión que solo cuenta o decora debe ser `LEFT`**: siete listas ocultaban todo contenido sin hijos (un foro sin
  mensajes, un álbum sin imágenes) por una unión estricta.
- Un **contador** (visitas, clics) en una tabla cuyo `updated_at` lleva `ON UPDATE current_timestamp()` reescribe la
  fecha de modificación en cada visita: la página del wiki anunciaba «modificada el» a la hora de su último lector.
  Escribe la columna sobre sí misma — `SET views = views + 1, updated_at = updated_at` —; `check-db-compteurs` rechaza
  el olvido.
- `MATCH … AGAINST` (FULLTEXT) **no ve** una fila insertada en una transacción sin confirmar: InnoDB solo indexa al
  confirmar. Una prueba de búsqueda FULLTEXT no puede envolverse en la transacción anulada de la base de pruebas.

Para las entidades gestionadas (addons, archivos…), pasa por los **modelos**: `NeoFrag()->model2('addon')`,
`NeoFrag()->model2('file', $id)->delete()`; para recorrer un conjunto tipado, `NeoFrag()->collection('addon')->get()`.

## Formularios — `form()` y `form2()`

Conviven dos API (una decisión registrada: la v1 está congelada, no se migra en bloque):

- **`form()`** — la API de las **pantallas de administración**: campos declarados en un array con `add_rules([...])`,
  procesados por `is_valid($post)`, mostrados por `->display()`. Un ejemplo completo en
  [Crear un módulo](create-a-module.md) (§7).
- **`form2()`** — fluida, cada campo es un objeto `form_*()`. Para los formularios **públicos** o **ricos**, y la
  **única** que valida un formulario **de solo confirmación** (sin campos) — una confirmación de eliminación solo acepta
  el campo `delete`: una casilla añadida la haría fallar sin decir nada.

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

Las dos llevan su propio token CSRF. Una acción lanzada por un **enlace** o por un **POST escrito a mano** debe
comprobarlo ella misma: `csrf_url()`, `check_csrf()`, `csrf_token()` (trait `Admin_Helpers`); fuera de un
controlador, el mismo token se lee con `nf_jeton_csrf()`.

**El editor enriquecido** — el tipo `editor` en `form()`, `form_textarea()->editor()` en `form2()` — es TinyMCE 8,
alojado por el sitio; el HTML que produce pasa por `sanitize_html()`. Una imagen **pegada o arrastrada** en él se envía
al sitio (`ajax/user/editeur-image`: miembro conectado, token de sesión, frecuencia limitada) y luego se inserta con su
dirección definitiva. El sitio lee su tipo real en sus bytes (JPEG, PNG, GIF, WebP), rechaza un archivo que lleve
código, limita su peso (5 MB) y sus dimensiones, la **vuelve a codificar** — sin metadatos, con la orientación de una
foto aplicada — y la guarda en `upload/editeur/AAAA/MM/` con un nombre aleatorio. Un `tinymce.init()` escrito a mano
(una plantilla, una pantalla aparte) pone `Editeur_Images::tinymce()` al principio de sus ajustes
(`NF\NeoFrag\Libraries\Editeur_Images`): sin él, una imagen pegada queda rota y luego desaparece al guardar.
`EditeurImagesTest` lo comprueba en todo el producto.

**El captcha** — `->captcha()` en `form2()`, `add_captcha()` en `form()` — muestra el proveedor elegido en
*Ajustes → Captcha* y comprueba su respuesta en el servidor; un miembro conectado no ve ninguno. Por defecto es
**ALTCHA**: alojado por el sitio, sin clave ni cookie, hace que el navegador realice un pequeño cálculo (alrededor de un
segundo) que el servidor comprueba, y rechaza que la misma solución sirva dos veces. Turnstile, hCaptcha y reCAPTCHA v2
necesitan sus dos claves; sin ellas, ALTCHA toma el relevo. Fuera de un formulario: `$this->captcha->element()` para
mostrarlo, `$this->captcha->is_valid()` para comprobar (`NF\NeoFrag\Libraries\Captcha`).

## Tablas — `table2()`

Muestra listas paginadas, **ordenables haciendo clic en el encabezado** (Mayús + clic: orden por varias columnas;
Ctrl + clic: quitar) y filtrables, a partir de una consulta. Ejemplo: la lista de miembros,
`modules/user/controllers/admin.php`. El orden lo sirve `js/table2.js` en JavaScript puro.

**Una lista dividida en páginas muestra sus enlaces de página.** Cuando un checker divide una lista —
`->paginate($page)` sobre una colección, `->pagination->get_data($lignes, $page)` sobre un array —, el método del
controlador que la muestra llama a `->pagination->get_pagination()` (o pasa por `table2()`, que la muestra él mismo).
Sin ello, solo se ven los primeros elementos y los demás quedan inaccesibles. Una lista que siempre cabe en una página
lo dice con un comentario `// pagination : <raison>` en el método. `check-pagination` lo comprueba.

## Traducciones

`$this->lang('Clave')` devuelve la traducción. Los archivos `langs/fr.php` asocian el **crc32b de la clave fuente** a
la traducción:

```php
return [
    hash('crc32b', 'Bienvenue') => 'Bienvenue',   // o directamente '467f39a3' => '…'
];
```

- `$this->lang('%d élément(s)', $n)` acepta argumentos (al estilo de `sprintf`).
- **El francés es el idioma fuente**; se incluyen seis idiomas y `tools/check-langs.php --toutes` rechaza una clave que
  falte en uno de ellos. `--fix` añade las claves que faltan **solo al francés**.
- **Nunca traduzcas contenido de la base de datos** (un título escrito por el administrador): `lang()` lo busca en vano
  y registra una advertencia en cada visita. Envuélvelo en `$this->no_translate(...)`.

### Contenido traducido: `join_lang()` y `ecrire_lang()`

El **contenido** de un módulo (un título, una descripción) se traduce de otra forma: vive en una tabla
`<table>_lang`, una fila por idioma, y lo que no tiene idioma (una fecha, una imagen, un precio) se queda en la tabla
principal.

```sql
CREATE TABLE IF NOT EXISTS nf_notes_lang (
    note_id INT UNSIGNED NOT NULL,
    lang    VARCHAR(5)   NOT NULL,
    title   VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (note_id, lang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- **Leer**: `join_lang()` une la **mejor** traducción — la del idioma pedido, si no la francesa, si no la del primer
  idioma escrito. Un contenido escrito en un solo idioma sigue así visible en los demás, en lugar de desaparecer como
  con una unión filtrada por el idioma.

  ```php
  $this->db->select('n.id', 'nl.title')->from('nf_notes n')->join_lang('nf_notes_lang nl', 'note_id', 'n.id')->get();
  ```

- **Escribir**: `ecrire_lang()` crea la fila cuando falta — un `update()` filtrado por el idioma no escribía nada la
  primera vez que se traducía algo, y el texto introducido se perdía. Da todas las columnas obligatorias de la tabla.

  ```php
  $this->db->ecrire_lang('nf_notes_lang', ['note_id' => $id, 'lang' => $langue], ['title' => $titre]);
  ```

- **La pantalla de edición** escribe en el idioma de la administración, y lo dice bajo el campo («Escrito para
  %s…»): traduces cambiando de idioma. Lo que ven el administrador y el visitante:
  [Un sitio en varios idiomas](multilingue.md).

## Fechas y zonas horarias

El producto **guarda** sus fechas en la zona horaria que tiene PHP al arrancar — la hora universal en producción — y
nunca la cambia: la base de datos alinea su sesión con ella (`drivers/mysqli.php`). Las **muestra** en la zona horaria
de quien mira, dada por `nf_fuseau()`: la elección del miembro (`nf_user_profile.timezone`, aplicada al abrir la
sesión), si no la de su navegador (cookie `nf_fuseau`, puesta por `views/theme/main.tpl.php`), si no el ajuste del
sitio (`nf_timezone`), si no la zona horaria de almacenamiento. Todo está en `neofrag/helpers/time.php`.

- **Escribir en la base**: `date('Y-m-d H:i:s')`, `NOW()` o `now()` — la zona horaria de almacenamiento.
- **Mostrar**: `timetostr($format, $when)` o la librería Date (`$this->date($when)`); nunca `date()`, que
  `check-heures` rechaza fuera de los formatos de máquina. Para una columna de fecha y hora leída tal cual (una lista,
  una ficha): `nf_date_heure($valeur)` — `d/m/Y H:i` en el idioma y la zona horaria del visitante, una cadena vacía si
  el valor está vacío; para una fecha sola, `nf_date($valeur)`. Nunca un formato numérico escrito a mano en
  `timetostr()` (`'d/m/Y'`, `'Y-m-d H:i'`): el alemán escribe `02.10.2026`, y `check-heures` lo rechaza.
- **Leer una entrada**: los campos `datetime` de los dos sistemas de formularios convierten solos; a mano,
  `nf_heure_saisie()` (y `nf_heure_affichee()` para el sentido inverso).
- **Una fecha sola o una hora sola** (`Y-m-d`, `H:i`) no es un instante: `timetostr()` y Date no la convierten. Un día
  entero guardado en una columna de fecha y hora se pasa como `Y-m-d` (mira `Calendar::format_dt()`).
- **Una hora en la hora del sitio**, sea cual sea el visitante (un horario de programación):
  `new \DateTime('now', nf_fuseau_site())`.

## El front end: Bootstrap 5, JavaScript puro, CSP estricta

- **Bootstrap 5.3**, **sin jQuery**. Las clases de rejilla llevan siempre un punto de ruptura (`col-12 col-lg-8`);
  `tools/check-classes-bs4.php` rechaza las clases de Bootstrap 4.
- **CSP estricta basada en nonce**: cada respuesta HTML lleva un nonce aleatorio, puesto en cada `<script>` en línea por
  el filtro de `index.php`, y `script-src` solo permite `'self'`, ese nonce, los orígenes del proveedor de captcha
  **activo** — ninguno con ALTCHA, el predeterminado — y Google Analytics **si** hay un ID configurado. **Ningún script
  desde un CDN.** El JS insertado dinámicamente pasa por `NF.setHtml` / `NF.insertHtml` / `NF.replaceHtml`, que vuelven
  a ejecutar los `<script>` añadidos con el nonce correcto, también en el iframe del Editor en vivo.
- **`window.NF`**, definido en la plantilla principal, sustituye las pocas primitivas que hacían falta — y nada más (no
  es un mini-jQuery):

| Primitiva | Función |
|---|---|
| `NF.ready(fn)` | ejecuta `fn` cuando el DOM está listo (o al instante si ya lo está) |
| `NF.data(el, 'my-key')` | lee `data-my-key` con la conversión de jQuery (número, booleano, JSON) |
| `NF.ajax({url, method, data, dataType, headers, signal})` | `fetch` con `X-Requested-With` en el mismo origen, cuerpo `form-urlencoded`, arrays como `key[]`; **rechaza** con un estado de error; `dataType: 'text'` o si no JSON |
| `NF.post(url, data)` | atajo POST |
| `NF.setHtml(el, html)` | `innerHTML` + ejecución de los scripts |
| `NF.insertHtml(target, position, html)` | `insertAdjacentHTML` + ejecución solo de los scripts añadidos |
| `NF.replaceHtml(el, html)` | sustituye el elemento, scripts ejecutados |
| `NF.runScripts(root)`, `NF.loadScript(src)` | volver a ejecutar, cargar con el nonce |
| `NF.bandeaux()` | ordena los banners de la parte superior de la página y vuelve a publicar `--nf-haut` y `--nf-entete` (mira *Crear un tema*); vuelve a llamarla tras añadir un banner |

- Las ventanas modales (`js/modal.js`), las notificaciones (`js/notify.js`) y las confirmaciones (`js/confirm.js`) las
  cargan los temas.
- Dos comprobaciones protegen este front end: `check-js-sources` (sintaxis, PHP interpolado tolerado, y ningún `$(`) y
  `check-js-console` (las páginas reales, en un navegador real: ningún error, ninguna violación de la CSP). El banco
  `tests/Browser/*.test.html` + `tools/check-js.php` prueba el **contrato** de un script sobre el archivo real, con
  dobles de `fetch`.

## Seguridad: lo que ya existe

Antes de escribir lo tuyo: `Rate_Limit` (por clave, p. ej. `contact:ip:<ip>`), `Audit_Log` (acciones sensibles),
`File_Jail` (rutas de subida), `Moderation`, TOTP para la autenticación en dos pasos, `sanitize_html()` (HTMLPurifier)
para el HTML enriquecido, `is_dangerous_upload()`, y el modo demo (`nf_demo()`), que bloquea la escritura de los
módulos sensibles.

**Una dirección escrita por alguien y colocada en un `href` o un `src`** pasa por `nf_url_sure($url)`: devuelve `FALSE`
para un esquema distinto de `http`, `https`, `mailto`, `tel` o `geo` — un `javascript:` se ejecutaría al hacer clic con
los derechos de quien hace clic. Una dirección relativa pasa. `nf_texte()` no basta: escapa las comillas, no el
esquema. `url()` devuelve `#` para una dirección absoluta rechazada.

### El sitio de demostración

La demo es compartida: su cuenta `demo` es administradora. La red de seguridad está en `neofrag/helpers/system.php`, y
actúa delante de cada controlador (`core/output.php`):

- **`NF_DEMO_MODULES_VERROUILLES`** — los módulos donde ninguna acción pasa en la demo: todo POST, todo enlace que lleve
  un token (`?_=`), todo método cuyo nombre contenga una palabra de acción (`NF_DEMO_MOTS_D_ACTION`: export, test,
  toggle…) se rechaza — un 403 JSON para una llamada AJAX, si no una notificación y una vuelta a la página anterior
  (`nf_demo_requete_refusee()`). **Un nuevo módulo que toca la configuración del sitio se añade a esta lista**: es una
  lista de bloqueo, un módulo ausente queda abierto. `check-demo-lock` lo recuerda.
- **`NF_DEMO_LECTURES`** — las pocas lecturas permitidas en un módulo bloqueado (`controleur::methode`), que entonces
  devuelven ejemplos ficticios.
- **`nf_demo_ecriture_permise($module)`** — la pregunta que hay que hacer en un código que escribe fuera de la red;
  verdadera fuera de la demo.
- **`nf_compte_masque()`** — la cuenta de emergencia de la demo, que ninguna lista de miembros muestra:
  `->where('u.id !=', nf_compte_masque())`. Fuera de la demo, vale 0.
- En la demo, `File::delete()` no elimina nada y las subidas de archivos se rechazan. Una página que mostraría un
  archivo, un registro o un ajuste sensible muestra un ejemplo en su lugar.
- La cuenta compartida no puede cambiarse desde el área de miembros: ni nombre de usuario, ni dirección, ni contraseña,
  ni autenticación en dos pasos, ni eliminación — un visitante dejaba fuera a los demás hasta el reinicio. Como la red
  solo cubre la administración, estas páginas hacen la prueba ellas mismas (`nf_demo()`,
  `modules/user/controllers/index.php`).

## Eventos

`$this->events->fire('forum.post.created', $message_id, $topic_id, $user_id)` del lado emisor;
`$this->events->on('forum.post.created', function (...) { … })` del lado suscriptor. Los oyentes siguen activos durante
toda la petición.

## Helpers útiles

- `url('forum/42')` — construye una URL enrutada (prefijo de idioma incluido).
- `notify('Mensaje')` — una notificación en pantalla tras una redirección.
- `nf_texte(...)` / `sanitize_html(...)` — escape / limpieza anti-XSS.
- `nf_url_sure($url)` — si una dirección escrita puede ir en un enlace (mira Seguridad).
- `nf_date_heure($valeur)` / `nf_date($valeur)` — una fecha de la base, para mostrar (mira Fechas).
- `nf_demo()` — verdadero en el sitio de demostración.

## Migraciones

El esquema del **núcleo** evoluciona mediante migraciones versionadas, registradas en `nf_migrations`:

```bash
php tools/migrate.php status                 # aplicadas / pendientes
php tools/migrate.php up [--pretend]          # aplica (simulación con --pretend)
php tools/migrate.php down [--step=N]         # deshace
php tools/migrate.php baseline --until=NAME   # adoptar una base existente
```

Archivos: `migrations/YYYY_MM_DD_name.up.sql` (+ `.down.sql`). El DDL de MySQL se confirma solo → **haz una copia de
seguridad antes de `up` en producción**. Los **addons** tienen sus propias migraciones (`<addon>/install/migrations/`,
registradas en `nf_addon_migrations`) — mira [Crear un módulo](create-a-module.md).

**El código nuevo aplica sus migraciones él solo**, una vez, en la primera página vista tras la actualización — por el
botón como por FTP: las del núcleo, luego las de los addons instalados (`nf_migrations_du_code()`, llamada por
`index.php` cuando el ajuste `nf_migrations_version` difiere de `NEOFRAG_VERSION`). Vale para todos los sitios, la demo
incluida. Un fallo no impide que la página se muestre: se registra, y el siguiente intento espera diez minutos.

## Sobrescritura en tres niveles

Resolución de las vistas, clases y archivos (gana el primero encontrado):

1. `overrides/{type}/{file}` — global
2. `themes/{tema activo}/overrides/{type}/{file}` — por tema
3. el original incluido

Personalizas sin hacer un fork y sin romper las actualizaciones. Cuidado: dos archivos con el mismo nombre en dos
niveles, y `path()` sirve uno u otro según quien llama — un asistente se mostró una vez sin estilo por culpa de un
duplicado (`tools/check-assets.php` lo rechaza ahora).

## Entorno de desarrollo

El proyecto funciona en **cualquier PHP 8.2+ con MySQL o MariaDB** (Apache + `mod_rewrite`, o nginx, o Caddy). El camino
de referencia del proyecto es una **instalación de pruebas en un servidor**, donde funciona la batería completa:
`php tools/check-all.php --navigateur`, `vendor/bin/phpunit --fail-on-skipped` y `composer stan`. El entorno, la base de
pruebas y el ciclo de trabajo se describen en [`docs/development.md`](../../development.md); cada herramienta se
describe en [`tools/README.md`](../../../tools/README.md).
