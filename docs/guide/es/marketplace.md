# El marketplace

El **marketplace** sirve para **actualizar** tus addons y para **instalar los que no están en tu servidor**. Solo ofrece
los addons oficiales de NeoFrag Reborn, que el paquete de instalación ya contiene todos: sirve, pues, sobre todo para
las actualizaciones, y para recuperar un addon que habías eliminado. Interviene *después* de la instalación, desde la
administración.

El catálogo y los archivos se sirven desde **neofrag-reborn.xyz**, por HTTPS; cada archivo se verifica por su **huella
SHA-256** al descargarlo. Cada versión los publica también, con el catálogo, en la
[página de versiones](https://github.com/NeoFragReborn/extensions/releases) del repositorio de los addons opcionales.

Tu sitio recibe **el catálogo de su versión**: a un sitio que aún no está al día se le ofrecen los addons hechos para la
versión que ejecuta, no los de la última, que podrían llamar a funciones que su núcleo aún no tiene. Y si un addon pide
una versión más reciente de NeoFrag, la ventana del marketplace no lo ofrece: te dice que actualices primero tu sitio.

## Instalar un addon

Dos caminos:

### 1. Con un clic desde la administración

**Admin → Temas & Addons → Marketplace**. La ventana lista los addons disponibles no instalados; márcalos y haz clic en
**Instalar** — NeoFrag descarga, verifica la huella SHA-256, extrae y registra el addon (y su widget asociado, si lo
hay).

### 2. A mano (archivo ZIP)

La página (pública) **Marketplace** presenta cada addon en una ficha con un botón **Descargar**. También puedes
descargar el `.zip` y luego **Admin → Temas & Addons → Añadir** y enviar el archivo (la subida se valida: los archivos
con contenido no seguro se rechazan). Es también el camino de un addon que hayas escrito tú: comprime su carpeta y
envíala.

## Actualizar o retirar

- **Actualizar**: **Admin → Temas & Addons → Actualizaciones** compara la versión instalada de cada addon con la del
  catálogo y lista las que hay que actualizar; aplícalas con un clic (descarga + SHA-256, archivos sustituidos,
  **migraciones de esquema pendientes aplicadas**). Si no, enviar un nuevo `.zip` por *Añadir* también actualiza (una
  versión superior sustituye a la antigua).
- **Retirar**: desde el menú de su ficha en **Temas & Addons**. Un módulo o un widget: **Desinstalar** — se retiran
  sus tablas, sus datos y sus archivos, tras una copia de seguridad hecha justo antes (carpeta `backups/addons` del
  servidor); un módulo que otro addon necesita se rechaza, y la ventana dice cuáles. Un tema: **Eliminar** (no el
  tema activo). Los addons del núcleo no se retiran.
- **Restablecer** (un módulo): sus datos se borran y vuelve a empezar como el día de su instalación; sus ajustes,
  sus permisos y los enlaces que llevan a él se mantienen. Antes se hace una copia de seguridad de sus tablas.

> Los **conectores de autenticación** (Discord, GitHub, Google) forman parte del **núcleo**: ya están, y se configuran en
> los ajustes — no pasan por el marketplace.

> **¿Marketplace inaccesible?** Si la pantalla «Actualizaciones» muestra «Marketplace inaccesible», comprueba que tu
> servidor puede salir por **HTTPS** hacia `neofrag-reborn.xyz`. El catálogo está fijado a ese origen: la lista de
> hosts autorizados está escrita en el código (`MARKETPLACE_HOSTS`, `neofrag/installer.php`), y el ajuste
> `nf_marketplace_url`, sin pantalla de administración, solo puede designar una dirección HTTPS en esos hosts.

## Seguridad

El marketplace solo descarga desde un **origen fijo** por **HTTPS** (nunca una URL escrita por el usuario), verifica el
**SHA-256** de cada archivo contra el catálogo y **rechaza todo archivo trucado** (anti zip-slip: rutas absolutas, `..`,
enlaces simbólicos). No se ejecuta ningún código remoto al añadir o actualizar un addon (solo extracción + `install.sql`
y migraciones SQL idempotentes).

## Para los autores de addons

El código de los addons opcionales vive en [NeoFragReborn/extensions](https://github.com/NeoFragReborn/extensions):
proponer un addon o una corrección es abrir allí una solicitud de fusión. El catálogo lo genera
`tools/package-addons.php`, que comprime cada addon (una carpeta `<name>/` en la raíz del archivo) y produce
`marketplace/catalog.json`. Una entrada real, abreviada:

```json
{
  "schema": 1, "base_version": "1.2.22",
  "addons": [{
    "type": "module", "name": "quotes", "tier": 2, "category": "contenu",
    "title": "Citations", "description": "Recueil de citations classées, avec leur auteur et leur source.",
    "i18n": { "en": { "title": "Quotes", "description": "…" } },
    "version": "1.0", "author": "NeoFrag Reborn", "license": "LGPLv3 <https://neofr.ag/license>",
    "file": "modules/quotes.zip", "preview": "modules/quotes.jpg", "size": 60556, "sha256": "…",
    "requires": { "base": ">=1.2.22", "addons": [] }, "provides_widgets": [], "install": "zip"
  }]
}
```

`catalog.json` y los `.zip` se publican **juntos**: las huellas SHA-256 solo valen para los archivos de la misma pasada.
El catálogo oficial se publica con cada versión, y un sitio no puede seguir otro sin modificar `MARKETPLACE_HOSTS`. Un
addon que escribas — guías: [crear un módulo](create-a-module.md), [un widget](create-a-widget.md),
[un tema](create-a-theme.md) — se instala en cualquier sitio por *Temas & Addons → Añadir*.

## Actualizar el núcleo (NeoFrag en sí)

**Admin → Temas & Addons → Actualizaciones** **señala** una nueva versión del núcleo si la hay: la que anuncia el canal
de actualización (la Monitorización lo consulta) y, si no, el `base_version` del catálogo, la versión del CMS para la que
se construyó.

Dos caminos para aplicarla.

### Con un clic desde la Monitorización

**Admin → Monitorización → Actualizar**. El sitio toma primero una **copia de seguridad**, luego descarga el paquete de
actualización, verifica su huella, superpone los archivos, aplica las migraciones pendientes y recompila las hojas de
estilo.

El botón solo acepta la actualización correcta, por cuatro garantías:

1. el **origen** viene de la misma lista blanca que el marketplace, y un valor inyectado en la base no puede salir de
   ella;
2. `version.json` solo aporta un **nombre de archivo**, nunca una URL: ni host, ni ruta, así que ni redirección ni
   escalada de directorios;
3. la huella **SHA-256** se verifica **antes** de tocar un solo archivo del sitio;
4. el archivo se controla **entrada por entrada** (anti zip-slip, enlaces simbólicos rechazados).

Los archivos ya presentes en `config/` y el bloqueo `install/db.txt` nunca se reescriben: la configuración de un sitio en
servicio se conserva. El resto de `install/` es código del producto, y sigue las versiones.

### A mano

Descarga `neofrag-reborn-public-<version>.zip` en la
[página de versiones](https://github.com/NeoFragReborn/neofrag/releases) y sustituye los archivos (salvo `config/`,
`upload/`, `backups/`), luego visita el sitio — las migraciones se aplican solas
([guía de despliegue](../../deploy-ftp.md#mettre-à-jour-un-site-déjà-en-ligne)).

### Lo que publica cada versión

Cada versión publica en el origen de actualización (`https://neofrag-reborn.xyz/update/`) tres archivos que forman un
conjunto coherente: el paquete de actualización, `version.json` (la versión, el nombre del paquete, su SHA-256 y su
tamaño) y `checksum.json` (una huella por archivo incluido, para el control de integridad de la Monitorización). El botón
verifica cada uno con los demás: publicar uno sin los otros hace fallar la actualización, sin tocar nada del sitio.
