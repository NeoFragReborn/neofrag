# El panel de administración

La administración está en **/admin**. Adopta el aspecto de NeoFrag Reborn (azul marino oscuro + turquesa, claro u oscuro
a elegir) y se organiza en torno a una **barra lateral**.

## Referencias

- **Barra lateral**: los módulos se ordenan en nueve secciones, más *Sistema* (Ajustes, Usuarios, Permisos, Temas &
  Addons, Monitorización…). Las secciones se despliegan en acordeón; una sección vacía se oculta.

  | Sección | Módulos |
  |---|---|
  | *Contenido* | Páginas, Blog, Noticias, Slider, Citas, Recetas, Menús |
  | *Comunidad* | Foro, Colectivos, Conversación, Comentarios, Libro de visitas, Emojis |
  | *Animación* | Calendario, Encuestas, Gamificación, Anuncios clasificados |
  | *Gaming* | Eventos, Equipos, Juegos / mapas, Reclutamientos, Palmarés, Socios |
  | *Conocimiento* | Wiki, FAQ, Diccionario, Descargas, Directorio de enlaces, Mapa de lugares |
  | *Medios* | Medios, Galerías, Archivos, Webradio |
  | *Difusión* | Boletín, Plantillas de correo, Fuente RSS, Discord, Webhooks, API |
  | *Soporte* | Contacto, Bugtracker, Moderación |
  | *Monetización* | Tienda, Donaciones, Pagos, Gestión publicitaria |

  Una extensión del marketplace que esta ordenación no conoce va a *Otros módulos*. El menú *Navegación* del Editor en
  vivo sigue las mismas secciones, en submenús, con una búsqueda.
- **Fijar**: pasa el ratón sobre un módulo de la barra lateral y haz clic en la chincheta para añadirlo a *Fijado*
  (accesos directos arriba, recordados por tu navegador).
- **Búsqueda rápida**: `Ctrl/Cmd + K` abre la paleta de comandos para saltar a cualquier módulo o acción.
- **Cabecera**: migas de pan + acciones contextuales (Permisos, Configuración, Ayuda), «Ver el sitio» y el conmutador
  claro/oscuro.

## Tareas habituales

| Quiero… | Voy a |
|---|---|
| Publicar una noticia / una entrada de blog / una página | *Contenido* → el módulo correspondiente |
| Ordenar las entradas del Blog en categorías y series (una entrada en varias partes) | *Contenido → Blog*, botones «Categorías» y «Series»; la serie y el orden de una entrada se eligen en su formulario |
| Gestionar el foro y los comentarios; la moderación | *Comunidad*; *Soporte → Moderación* |
| Crear un equipo privado — una redacción, un equipo de moderación — con su sección del foro y su canal de Discord | *Comunidad → Colectivos* — ver [Colectivos](#colectivos) |
| Cobrar las cuotas, seguir a los afiliados | *Monetización → Pagos* — ver [Cuotas](#cuotas) |
| Vincular Ko-fi, HelloAsso, TipeeeStream o PayPal a las campañas de donaciones | *Monetización → Donaciones*, tarjeta «Servicios vinculados» — ver [Donaciones vinculadas](#donaciones-vinculadas) |
| Crear los prefijos de tema del foro («Pregunta», «Tutorial»…), elegir el icono de un foro | *Comunidad → Foro*, botón «Prefijos»; el icono en el formulario del foro |
| Gestionar miembros, grupos, sesiones | *Sistema → Usuarios* |
| Decidir quién puede hacer qué | *Sistema → Permisos (matriz)* — rejilla rol × acción (verde = permitido, gris = por defecto, rojo = nunca) |
| Cambiar el tema / instalar un addon | *Sistema → Temas & Addons* |
| Ajustes del sitio (nombre, inicio, registros, seguridad, copyright) | *Sistema → Ajustes* |
| Componer las páginas con el ratón | *Sistema → Editor en vivo* |
| Vincular el sitio a un servidor de Discord: roles, apodos, foro ↔ canal Foro, Bugtracker, roles temporales, sanciones | *Difusión → Discord* — ver [El bot de Discord](bot-discord.md) |
| Copias de seguridad, actualizaciones, estado del sitio | *Sistema → Monitorización* |
| Leer los errores del sitio, encontrar el que señaló un visitante | *Sistema → Monitorización → Registro de errores* |
| Revisar las acciones sensibles (ajustes, addons, cuentas, claves de API) | *Sistema → Usuarios → Registro de auditoría* |

> La **Monitorización** reúne el **estado del sitio** (comprobaciones de integridad de los archivos, espacio en disco,
> información del servidor), las **actualizaciones**, las **copias de seguridad** (crear / descargar / restaurar) y la
> dirección de la **tarea programada** (cron) que publica los contenidos programados.

### Monitorización

*Sistema → Monitorización* resume lo esencial arriba — versión y actualización, PHP, copias de seguridad, herramientas
de diagnóstico encendidas — y se lee en cinco pestañas: *Resumen* (alertas, estado del sitio, almacenamiento, dirección
del sitio), *Copias de seguridad*, *Diagnóstico*, *Servidor y seguridad* (configuración, contraseña de webmaster, tarea
programada) y *Archivos* (la instalación comparada con la versión publicada). La página vuelve a la pestaña en la que
estabas; una dirección como `admin/monitoring#diagnostic` abre la correcta.

Cada actualización mediante el botón toma primero una **copia de seguridad** completa del sitio, base incluida. El sitio
conserva siempre las **cinco más recientes**; las demás se retiran solas pasados **treinta días**, y nunca quedan más de
**diez**. Descarga la que quieras conservar más tiempo.

### El registro de errores

*Monitorización → Registro de errores* muestra lo que falló en el sitio, sin pasar por FTP: los errores agrupados (un
mismo error visto cien veces sigue siendo una línea), del más reciente al más antiguo, clasificados por gravedad —
**fatal**, **error**, **aviso**, **información** —, con su última aparición completa para desplegar. Se filtra por
gravedad y por periodo, y se busca una palabra.

Cuando una página falla, el visitante lee «Se ha producido un error» y una **referencia** de ocho caracteres (`3F9A1C07`);
si te la pasa, búscala en el registro: lleva a la línea exacta. Las rutas del servidor, las contraseñas, las claves, las
direcciones de correo y las direcciones IP se ocultan en pantalla; el archivo entero se **descarga** (para pasarlo a
quien te ayuda) y se **vacía** (el antiguo se guarda al lado, `logs/php.log.1`). La Monitorización señala también los
errores de las últimas 24 horas, y una carpeta `logs/` en la que el sitio ya no puede escribir.

### Las herramientas de diagnóstico

La pestaña *Monitorización → Diagnóstico* enciende tres herramientas durante una hora; cada una se apaga sola, o con un
clic. Se acabó editar `config/neofrag.php` por FTP.

- **Modo de depuración**: aparece una barra al pie de las páginas (consultas a la base, tiempo de cálculo, memoria, datos
  de la visita), y una página que falla muestra el detalle del error — **solo para los administradores conectados**:
  los visitantes ven el sitio normal. El registro sirve para saber que existe un problema; el modo de depuración, para
  entenderlo reproduciéndolo.
- **Traza de páginas**: cada página servida, con sus consultas a la base y su duración. *Leer la traza* muestra las
  últimas páginas, la más reciente primero, buscables por dirección; los valores pasados a las consultas se ocultan en
  pantalla, el archivo completo se descarga. Voluminosa: para el tiempo de un diagnóstico.
- **Revisión de traducciones**: los textos sin traducción en el idioma de la visita se anotan y se listan en
  *Traducciones que faltan*, con la extensión que los usa. Mientras tanto, los administradores ven la bandera del idioma
  delante de cada texto traducido: un texto sin bandera no pasó por la traducción.

Una herramienta encendida en `config/neofrag.php` solo se apaga allí; la tarjeta lo indica.

### La dirección del sitio

Los enlaces de los correos (contraseña olvidada, validación del registro), los retornos de las conexiones externas y las
comparticiones se construyen sobre la dirección registrada en la instalación. Tras un cambio de dominio, la tarjeta
*Dirección del sitio* (Monitorización, pestaña *Resumen*) lo señala: abre la administración por la nueva dirección y el
botón *Usar*, seguido de esa dirección, la registra (se pide la contraseña de webmaster si está definida).

## Permisos

NeoFrag Reborn funciona por **roles**. La **matriz de permisos** (por módulo) ajusta, para cada rol, el acceso y las
acciones. Asignas los roles a los miembros y a los grupos desde *Sistema*.

**Ejemplo — crear un rol «Moderador»**:

1. En *Sistema → Permisos*, **crea un rol** «Moderador».
2. En su columna de la **matriz**, marca las acciones deseadas (moderar el foro, gestionar los comentarios…) →
   **verde = permitido**.
3. En *Sistema → Usuarios*, edita un miembro y **asígnale este rol**: recibe esos accesos al instante.

### Colectivos

Un **colectivo** reúne miembros en torno a un proyecto, a salvo de los demás: una redacción, un equipo de moderación, un
grupo de creadores. No es un equipo de juego (*Gaming → Equipos*, que se muestra al público): todo lo que recibe un
colectivo le está reservado.

*Comunidad → Colectivos → Nuevo colectivo*: su nombre, su descripción y su **jefe** (un nombre de usuario). El sitio crea
de una vez:

- una **sección privada del foro** — una categoría y su foro «Discussions» —, que solo sus miembros leen y donde solo
  ellos escriben;
- dos **roles** del sitio, *Colectivo «nombre»* y *… — moderación*, que el módulo da y retira por sí mismo: no los
  asignes a mano;
- si la casilla *Discord* está marcada y el bot encendido (funcionalidad *Colectivos*), un **rol y un canal privado** en
  el servidor — ver [El bot de Discord](bot-discord.md#colectivos).

Después, el jefe gestiona a sus miembros desde la página del colectivo (*Mis colectivos*, en el área de miembros), sin
ser administrador del sitio. Los rangos:

| Rango | Puede |
|---|---|
| Jefe | añadir miembros, nombrar administradores y moderadores, retirar a cualquiera por debajo de él |
| Administrador | añadir miembros, nombrar moderadores, retirar moderadores y miembros |
| Moderador | moderar la sección del foro (editar, eliminar, fijar, cerrar) |
| Miembro | leer y escribir en la sección |

El jefe solo se nombra en la administración: un nuevo jefe sustituye allí al anterior, que se queda como administrador
del colectivo. Eliminar un colectivo quita a sus miembros su espacio; su sección del foro y su canal de Discord
**permanecen**, visibles solo para los administradores — lo que se escribió allí no se pierde; elimínalos a mano si
quieres.

### Cuotas

*Monetización → Pagos*. Una cuota convierte a un miembro en **afiliado** durante un tiempo: recibe el rol **«Afiliado»**
— ábrele lo que quieras en *Sistema → Permisos* (una sección del foro, páginas) — y forma parte del grupo
**«Afiliados»** (su insignia, y un rol en Discord si lo vinculas). Dos formas de cobrar:

- **en línea**, con Stripe: un lote de tipo *Afiliación (días)* («Cuota anual», 365 días) que el miembro paga en la
  página *Pagos*;
- **de otro modo** (efectivo, cheque, transferencia): la tarjeta *Afiliados* → *Registrar una cuota* (nombre de
  usuario, duración).

Una nueva cuota prolonga la afiliación desde su vencimiento. Siete días antes, la tarea programada avisa al miembro (una
notificación); al vencimiento, le retira el rol. *Terminar la afiliación* la detiene al instante (un error de
escritura, un reembolso). Las cuotas no necesitan la Gamificación — los puntos y el VIP, sí.

### Donaciones vinculadas

*Monetización → Donaciones*, tarjeta **«Servicios vinculados»**: una donación hecha en uno de estos servicios se
registra sola en la campaña elegida, **una sola vez**, aunque el servicio avise dos veces. Las claves se guardan
cifradas y no se vuelven a mostrar.

| Servicio | Lo que hace falta | Cómo comprueba el sitio |
|---|---|---|
| **Ko-fi** | en Ko-fi, *More → API → Webhooks*: la dirección que muestra la tarjeta, y el token de verificación | el token, que solo conoce el sitio |
| **HelloAsso** | en el espacio de la asociación, *Mi cuenta → Integraciones y API*: el identificador y el secreto del cliente, y la dirección de notificación mostrada | vuelve a leer el pedido por la API de HelloAsso: la notificación sola no vale |
| **TipeeeStream** | la clave de API de la cuenta | nada que comprobar: el sitio lee las donaciones por sí mismo, en cada tarea programada, desde el día en que se guarda la clave |
| **PayPal** | la casilla *Registrar las donaciones automáticamente* | PayPal confirma cada notificación, y el dinero debe haber ido a la dirección PayPal de la campaña |

Una **afiliación** pagada en HelloAsso convierte en afiliado, durante la duración ajustada, al miembro del sitio que
tiene la misma dirección (módulo Pagos). Los donantes de HelloAsso y de PayPal no aparecen en la página de la campaña —
no lo consintieron —; los de Ko-fi y TipeeeStream que lo quisieron, sí. El total cuenta todas las donaciones. Para
probar sin dinero real: las casillas *entorno de pruebas* de HelloAsso y de PayPal.

## Ajustes esenciales

En **Ajustes**: título y descripción del sitio, favicon, correo de contacto, página de inicio, zona horaria,
posicionamiento, gestión de los registros, seguridad antibots (captcha), mantenimiento, copyright, redes sociales. Estos
ajustes alimentan los temas y los widgets.

### Registros

*Ajustes → Registro* abre o cierra los registros, puede hacer **validar la dirección de correo** de los nuevos miembros,
y ajusta dos textos, **idioma por idioma** (una pestaña por idioma del sitio).

La **validación por correo** (apagada por defecto): al registrarse, el miembro recibe un enlace, válido dos días, y no
puede conectarse antes de abrirlo; un intento de conexión le envía otro (tres por hora como máximo). Un registro por
Discord, GitHub o Google no lo necesita. Los dos textos:

- el **reglamento**, que el visitante acepta al registrarse — por el formulario como por Discord, GitHub o Google, que lo
  muestran antes de crear la cuenta;
- el **mensaje de bienvenida**, enviado por la mensajería al nuevo miembro — también cuando se registra con Discord,
  GitHub o Google —, en el idioma de la página donde se registró. `[pseudo]` se convierte en su nombre de usuario. Llega
  en texto: títulos, listas y enlaces se conservan, el formato no; una dirección escrita entera se convierte en enlace.

Un idioma que aún no tiene su texto muestra el texto común: el primer texto guardado sirve a todos los idiomas mientras
no estén traducidos.

### Posicionamiento

Lo que leen los buscadores se hace **solo**:

- un **mapa del sitio** por idioma, reunido en la raíz (`/sitemap.xml`) y anunciado por `robots.txt` — cada módulo
  aporta sus páginas públicas (el blog, el foro y sus temas, la wiki, la galería…), solo las que un visitante puede leer;
- en cada página, su **dirección de referencia** (canónica), los **enlaces a sus otros idiomas** y lo que muestran las
  vistas previas al compartir (título, descripción, imagen);
- en la página de inicio, **datos estructurados** que describen el sitio, su logo y sus redes sociales (*Ajustes → Redes
  sociales*);
- la búsqueda, los perfiles y el directorio de miembros **no** se indexan.

Lo que rellenas, en **Ajustes → Posicionamiento**:

- para cada idioma, un **eslogan** (el título de la página de inicio pasa a ser «Nombre del sitio — eslogan», 60
  caracteres como máximo) y una **descripción** (lo que los buscadores muestran bajo el título, 160 caracteres como
  máximo; vacía, sirve la de las Preferencias generales);
- una **imagen para compartir** de 1200 × 630 píxeles, mostrada cuando se pega un enlace del sitio en Discord, X o
  Facebook — si no, el logo;
- el código de verificación de **Google Search Console** y de **Bing Webmaster Tools**: crea la propiedad del sitio en la
  herramienta, elige la verificación por **etiqueta HTML**, pega la etiqueta (o solo el código) y envía allí el mapa del
  sitio, `https://tu-sitio/sitemap.xml`;
- **Avisar a los buscadores (IndexNow)**: marcado, el sitio señala cada página que aparece, cambia o desaparece a Bing,
  Yandex, Seznam, Naver, Yep y Amazon, en los minutos siguientes, en lugar de esperar su próxima visita. Google no
  participa: para él, es el mapa del sitio. La **tarea programada** del sitio debe estar en marcha (la *Monitorización*
  da la línea que hay que instalar). La primera pasada solo registra las páginas existentes; después, solo salen los
  cambios.

Tres pantallas más, desde la misma sección:

- **Informe de posicionamiento** — lo que ve un buscador, medido en el sitio: el número de páginas del mapa y su módulo,
  los textos de la página de inicio en cada idioma, la imagen para compartir, Google y Bing, IndexNow, `robots.txt`, el
  mantenimiento. Cada punto enlaza con lo que lo corrige.
- **Redirecciones** — una dirección antigua que lleva a la nueva, en lugar de «Página no encontrada»: una posición
  ganada no se pierde. Una página o una página de la wiki **renombrada** deja la suya sola; añade a mano las de un sitio
  antiguo (`page.php`, `/fr/pagina-antigua`…). La columna *Visitas* dice si la dirección antigua aún se usa.
- **El posicionamiento de un contenido** — en la tarjeta de edición de una noticia, una entrada del Blog, una página o
  una página de la wiki, el botón **Posicionamiento**: un título para los buscadores (60 caracteres como máximo, sigue
  el nombre del sitio) y una descripción (160 como máximo), en cada idioma. Vacíos, son el título y el principio del
  texto.

### El captcha

El formulario de contacto y el registro piden a los visitantes una comprobación antirrobots. En **Ajustes → Captcha**,
elige el proveedor:

- **ALTCHA** (por defecto, recomendado): nada que configurar. Lo aloja tu sitio, sin cuenta ni cookie; el visitante ve
  una casilla que se marca sola mientras rellena el formulario.
- **Cloudflare Turnstile**, **hCaptcha** o **Google reCAPTCHA v2**: crea un sitio en su consola (enlaces en la página) y
  pega la **clave del sitio** y la **clave secreta**. La clave secreta se cifra y no se vuelve a mostrar. Sin sus dos
  claves, el proveedor se sustituye por ALTCHA.
- **Ninguno**: desaconsejado, los robots escriben entonces libremente.

ALTCHA encarece el envío masivo para un robot; contra un atacante obstinado, Turnstile o hCaptcha juzgan más.

### La zona horaria

Cada uno ve las horas del sitio en su propia zona horaria:

- un **miembro** puede elegir la suya en su perfil (**Perfil → Zona horaria**);
- si no, el sitio toma la de su **navegador**, desde su segunda página;
- si no — primera visita, robots, tareas automáticas —, la del **sitio**, ajustada en **Ajustes → Preferencias generales
  → Zona horaria**.

Una hora introducida en un formulario (el inicio de un evento, la reapertura tras un mantenimiento) se entiende en la
zona horaria de quien la introduce. Una fecha sola — un cumpleaños, un día entero en el calendario — nunca cambia de
día. La parrilla de una webradio está escrita a la hora del sitio.

### Las notificaciones push

Un miembro puede recibir sus notificaciones en su navegador, **incluso con el sitio cerrado** — un mensaje del foro,
una reacción, un recordatorio de evento. Para ofrecerlo, marca **Ajustes → Preferencias generales → Notificaciones
push**. El sitio debe servirse por **https**, y el servidor saber cifrar (OpenSSL con la curva P-256, y cURL: casi todos
los alojamientos los tienen; si no, la casilla queda desmarcada y el sitio lo dice).

Nadie recibe nada mientras no lo haya pedido: cada miembro lo activa **dispositivo a dispositivo**, en **Mi cuenta →
Preferencias de notificaciones → Este dispositivo**, y su navegador le pide su consentimiento. Allí elige también, tipo
a tipo, lo que recibe así, y puede enviarse una prueba.

Lo que debes saber, y decir en tu **política de privacidad**: el navegador pasa por su servicio de push — Google para
Chrome y Edge, Mozilla para Firefox, Apple para Safari. El sitio guarda allí la dirección de suscripción de cada
dispositivo mientras el miembro no corte el push (o no elimine su cuenta). El mensaje está cifrado solo para ese
navegador: el servicio lo transporta sin poder leerlo.
