# Instalación

NeoFrag Reborn se instala en un alojamiento web corriente, **compartido incluido**: no hacen falta ni Docker, ni
Composer, ni acceso shell — el paquete trae sus dependencias.

## Requisitos

- **PHP 8.2 a 8.5** con las extensiones `mysqli`, `mbstring`, `openssl`, `curl`, `gd`, `zip`, `intl`, `fileinfo` e
  `iconv`, y un PHP capaz de cifrar contraseñas con **Argon2** (la mayoría lo es). Se activan en el panel de tu
  proveedor, a menudo en «Versión de PHP» o «Extensiones». El instalador las comprueba todas y se niega a continuar si
  falta una.
- **MySQL 5.7, 8.0 u 8.4**, o **MariaDB 10.5, 10.6, 10.11, 11.4 u 11.8**, con el motor **InnoDB** y el juego de
  caracteres **utf8mb4**. Son las versiones con las que se prueba cada versión del CMS antes de publicarse, con PHP 8.2
  y 8.5: instalación completa, toda la batería de pruebas y cada uno de los perfiles de instalación.
- **Apache** con `mod_rewrite` (se incluye un `.htaccess`); o **nginx** (`nginx.conf`); o **Caddy** (`Caddyfile`).
- Una base de datos **vacía** y sus credenciales.
- Un **nombre de dominio** real que apunte al servidor: el instalador deduce de él la dirección de contacto del sitio
  (`noreply@tu-dominio`) — instalado por su dirección IP, un sitio no tendría una dirección de remitente válida.

## El instalador

El instalador se desarrolla en **cinco pasos**: *Requisitos* → *Perfil del sitio* → *Base de datos* →
*Administrador* → *Terminado*.

1. **Sube** los archivos de NeoFrag Reborn a la raíz web: el contenido de la carpeta `neofrag-reborn/` del paquete
   `neofrag-reborn-public-<version>.zip` (por FTP, o descomprimiéndolo en el servidor). El paquete se descarga en la
   [página de versiones](https://github.com/NeoFragReborn/neofrag/releases/latest) del proyecto.
2. Abre tu dominio: el instalador arranca y muestra los **requisitos** con el valor encontrado (versión de PHP,
   extensiones presentes).
3. Elige el **perfil del sitio**:

   | Perfil | Lo que instala |
   |---|---|
   | **Completo** | el núcleo y todos los módulos del paquete |
   | **Gaming / eSport** | el núcleo y la identidad gaming: foro, equipos, juegos, eventos, reclutamiento, palmarés… |
   | **Comunidad** | el núcleo, las noticias, el foro, la galería, los eventos, los colectivos y la API (para el bot de Discord y las integraciones) |
   | **Asociación / club** | el núcleo, las noticias, el foro, la galería, los eventos, el calendario, los colectivos, las donaciones, las cuotas (pagos), el boletín, la wiki y las FAQ |
   | **Solo el núcleo** | cuentas, permisos, páginas, ajustes — nada más |

   Los módulos siguen pudiendo desmarcarse uno a uno; los que un módulo necesita se añaden automáticamente, y la
   pantalla final lo dice. El formulario funciona también sin JavaScript.
4. Indica la **conexión a la base de datos**: el instalador importa el esquema, instala los módulos, widgets y temas
   elegidos, y coloca las disposiciones por defecto.
5. Crea la **cuenta de administrador**. Listo.

Los módulos no instalados se añaden más tarde desde **Administración → Temas & Addons**, y los que no están en el
paquete desde el [marketplace](marketplace.md).

## Instalación por línea de comandos

Para un despliegue **automatizable** (servidor, aprovisionamiento), `install/cli.php` hace lo mismo que el instalador,
sin navegador — requisitos incluidos: se niega a continuar si falta uno. Instala el perfil **Completo**.

```bash
# Contraseña de administrador por variable de entorno (invisible en la lista de procesos):
export NF_ADMIN_PASS='mi-contraseña-fuerte'
php install/cli.php \
  --db-host=localhost --db-name=neofrag --db-user=neofrag --db-pass=secret \
  --admin-user=admin --admin-email=admin@site.tld --admin-pass-env=NF_ADMIN_PASS \
  --site-name="Mi comunidad" --site-url=https://site.tld --yes
```

Sin argumentos, pasa al **modo interactivo** (contraseña oculta). Opciones: `--db-port`, `--create-db` (crea la base),
`--dry-run` (valida la configuración y prueba la conexión sin escribir nada), `--force` (reinstala), `--no-lock` (no
coloca el bloqueo), `--lang=fr|en|de|es|it|pt` (el idioma de sus mensajes), `--help`.

## Proteger el acceso tras la instalación

Al final, el instalador coloca un **bloqueo** (`install/db.txt`): volver a visitar `/install/` ya no muestra nada
aprovechable. Por precaución, **elimina o renombra la carpeta `install/`** — ya no es necesaria. Activa **HTTPS** si tu
proveedor no lo ha hecho. Con nginx o Caddy, parte de los ejemplos incluidos en la raíz (`nginx.conf`, `Caddyfile`):
ya deniegan el acceso a las carpetas y archivos sensibles, como hace el `.htaccess` con Apache.

## Primeros ajustes

1. **Administración → Ajustes**: nombre del sitio, descripción, favicon, **dirección de contacto** (comprueba que es
   válida y que un servidor de correo puede enviar — si no, el formulario de contacto y los correos de registro nunca
   llegan; un servidor SMTP se configura en *Ajustes → Correo*, y *Correos → Envío de prueba* lo demuestra), página de
   inicio.
2. **Administración → Temas & Addons**: tu tema, tus módulos.
3. **Administración → Editor en vivo**: compón tus páginas (coloca tus widgets en las regiones).
4. **Administración → Usuarios / Permisos**: crea tus roles y ajusta los accesos.
5. **Tareas programadas**: las publicaciones programadas, los boletines y los recordatorios de eventos salen por el
   **cron** del sitio (la Monitorización da su dirección y su clave). Añade una tarea cada cinco minutos en tu
   proveedor; sin ella, no sale nada programado.

Los siguientes pasos, en orden: [Primeros pasos](premiers-pas.md).

## Solución de problemas

- **«No se puede conectar con la base de datos»**: comprueba el host (a menudo `localhost`, a veces una dirección
  propia en los alojamientos compartidos), el puerto (`3306`), el nombre de la base — **debe existir y estar vacía** — y
  las credenciales.
- **«Falta una extensión de PHP»**: activa la extensión indicada desde el panel de tu proveedor; en línea de comandos,
  `php -m`.
- **Carpeta `config/` sin permiso de escritura**: el instalador debe escribir allí `db.php` y los secretos. Da permisos
  de escritura a `config/`, `cache/`, `logs/`, `upload/`, `backups/`.
- **Instalación interrumpida**: MySQL no sabe deshacer un `CREATE TABLE` a medio ejecutar — **crea una base vacía
  nueva** antes de volver a intentarlo.
- **Direcciones en 404 o AJAX roto con nginx o Plesk**: el `.htaccess` solo lo lee Apache; mira la nota «nginx / Plesk»
  de la [guía de despliegue](../../deploy-ftp.md).
- **No sale ningún correo**: mira *Sistema → Monitorización → Registro de errores* (una línea `[email] échec envoi …`);
  la dirección de contacto debe ser válida y debe existir un transporte (un servidor SMTP configurado, o un agente de
  correo local).
- **«Se ha producido un error», con una referencia**: la página falló. El detalle está en el registro de errores; la
  referencia encuentra la línea correcta.

## Desarrollo

El código está en GitHub: el CMS en [NeoFragReborn/neofrag](https://github.com/NeoFragReborn/neofrag), los addons
opcionales en [NeoFragReborn/extensions](https://github.com/NeoFragReborn/extensions) — `tools/assembler.php` los coloca
en el árbol del CMS. Para desarrollar o probar sirve cualquier máquina con PHP 8.2+ y MariaDB o MySQL; se incluye una
pila Docker (`docker-compose.yml`). Detalles (base, migraciones, pruebas, controles, despliegue):
[`docs/development.md`](../../development.md).
