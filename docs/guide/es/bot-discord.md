# El bot de Discord

El bot de Discord de NeoFrag Reborn vincula tu sitio y tu servidor de Discord. Sus funcionalidades se encienden una a
una:

- **roles y apodos** — un miembro que vinculó su cuenta de Discord recibe en el servidor los roles vinculados a sus
  grupos, y su nombre del sitio si quieres;
- **foro y canales Foro** — un foro del sitio y un canal Foro de Discord viven juntos: un tema se convierte en un hilo,
  un hilo en un tema, las respuestas siguen en ambos sentidos, y los prefijos del foro se convierten en las etiquetas
  del canal;
- **cuenta y apariencia** — el comando `/forum`: vincular la cuenta desde Discord, y elegir cómo se aparece en el foro
  sin cuenta vinculada;
- **Bugtracker** — cada ticket se convierte en un hilo, su tipo y su estado en etiquetas; `/bug` e `/idee` abren un
  ticket desde Discord;
- **roles temporales** — el comando `/role`: dar un rol por un tiempo limitado, que el bot retira al final;
- **sanciones vinculadas** — los silencios y baneos del sitio se aplican en Discord, y los de un moderador vinculado
  pueden pasar al sitio;
- **colectivos** — cada colectivo del sitio recibe un rol y un canal privado, que solo ven sus miembros.

Se **ajusta por completo desde la administración** del sitio: su token, su servidor, sus funcionalidades y sus ajustes,
el interruptor marcha / pausa, el reinicio, las correspondencias, e incluso la creación de canales y roles en el
servidor. La administración muestra también su estado y su registro, en tu idioma.

El bot es un **programa aparte** que funciona sin parar. Necesita una máquina encendida — un VPS, una Raspberry Pi, un
PC — con **Node.js 22.9 o posterior**. Un alojamiento compartido no puede ejecutarlo; el sitio, en cambio, no lo
necesita y se queda donde está.

## 1. Crear la aplicación de Discord

1. En el [portal de desarrolladores de Discord](https://discord.com/developers/applications): *New Application*, dale un
   nombre.
2. Pestaña *General Information*: anota el **Application ID**.
3. Pestaña *Bot*: *Reset Token*, y copia el **token del bot** — solo se muestra una vez.
4. Todavía en *Bot*, marca los dos **intents privilegiados**:
   - **Server Members Intent** — para los roles, los apodos y los roles temporales;
   - **Message Content Intent** — para copiar el texto de los mensajes de Discord en el sitio (foro, Bugtracker).

   Sin ellos, el bot funciona en modo reducido y lo dice en su registro: nunca pide a Discord más de lo que la aplicación
   tiene derecho a recibir.

## 2. Configurar el módulo Discord

El bot se apoya en dos módulos del sitio, **API** y **Discord**. El perfil de instalación *Completo* instala los dos; si
no, *Sistema → Temas & Addons* los añade (Discord, un addon opcional, viene del marketplace si no está en el servidor).
La sincronización del foro necesita también el módulo **Foro**, la de los tickets el módulo **Bugtracker**.

*Administración → Discord → Conexión*:

| Campo | Dónde encontrarlo |
|---|---|
| Application ID | el *Application ID* del paso 1 |
| ID del servidor | en Discord, con el modo desarrollador activado: clic derecho en el servidor → *Copiar ID del servidor* |
| Token del bot | el token del paso 1. Se **guarda cifrado** y no se vuelve a mostrar; deja el campo vacío para conservar el guardado |

Luego **Invitar al bot** (página de inicio del módulo): el enlace añade el bot al servidor con solo sus propios permisos —
nunca «Administrador»:

| Permiso | Para |
|---|---|
| Ver canales, Enviar mensajes, Leer el historial de mensajes | leer y escribir en los canales vinculados |
| Enviar mensajes en hilos, Crear hilos públicos, Gestionar hilos | copiar un tema o un ticket como hilo, renombrarlo, archivarlo, poner sus etiquetas |
| Gestionar canales | la configuración del servidor: crear una categoría, canales Foro y sus etiquetas |
| Gestionar mensajes | llevar a Discord una eliminación hecha en el sitio |
| Gestionar webhooks | publicar los mensajes del sitio con el nombre y el avatar de su autor |
| Añadir reacciones, Insertar enlaces, Adjuntar archivos | mensajes completos |
| Gestionar roles, Gestionar apodos | los roles, los apodos y los roles temporales |
| Usar comandos de la aplicación | `/forum`, `/bug`, `/idee`, `/role` |

En los ajustes del servidor (*Roles*), coloca el rol del bot **por encima** de los roles que da: Discord prohíbe a un bot
dar un rol situado más alto que el suyo.

## 3. Instalar el bot en su máquina

El bot se descarga como archivo ya compilado, `neofrag-reborn-bot-X.Y.Z.tar.gz`, en la
[página de versiones](https://github.com/NeoFragReborn/bot-discord/releases/latest) de su repositorio. Tiene sus propios
números de versión, independientes de los del sitio.

```bash
tar xzf neofrag-reborn-bot-X.Y.Z.tar.gz && cd bot
npm ci --omit=dev
cp .env.example .env
```

*Administración → Discord → Crear la clave de acceso*: la página da **dos líneas** — la dirección del sitio y la clave de
acceso del bot — que hay que pegar en `.env`. Solo se muestran una vez; crear una nueva clave revoca la antigua. Luego:

```bash
npm start
```

Para que funcione como servicio, arranque con la máquina y se reinicie si cae: `neofrag-bot.service` (systemd,
reforzado) explica su instalación al principio del archivo. Su registro: `journalctl -u neofrag-bot -f` — y, sobre todo,
la administración del sitio.

En la máquina solo quedan esas dos líneas. Todo lo demás llega por el sitio.

**Actualizar el bot**: sustituye la carpeta por la del nuevo archivo conservando tu configuración (`.env`, o
`/etc/neofrag-bot/bot.env` con systemd), luego `npm ci --omit=dev` y reinicia el servicio. Una nueva versión del bot que
necesita más derechos en el sitio lo dice en la administración: crea entonces una nueva clave de acceso.

## 4. Manejarlo desde la administración

*Administración → Discord* muestra:

- su **estado** — *En línea*, *En pausa* (vivo, interruptor apagado), *Conectando con Discord…* o *Desconectado* (sin
  noticias desde hace un minuto y medio) —, su versión, su última señal de vida;
- los botones **Poner en marcha / Poner en pausa**, **Reiniciar** y **Resincronizar** (volver a ponerlo todo de acuerdo:
  roles y apodos, y lo que se escribió en Discord durante una ausencia del bot), que aplica en el minuto siguiente;
- su **registro**: conexiones, sincronizaciones y lo que le impide funcionar (intent sin marcar, rol mal colocado,
  permiso que falta, clave de acceso demasiado antigua), con lo que hay que hacer.

**Cambiar el token del bot** (fuga, regeneración): pega el nuevo en *Conexión*. El bot lo vuelve a leer y se reconecta
solo; la máquina no se toca.

### Funcionalidades

*Discord → Funcionalidades* lista lo que sabe hacer el bot — las declara él mismo en cada señal de vida: una
funcionalidad añadida al bot aparece allí sin tocar el sitio. Cada una se **enciende** o se **apaga**, y sus **ajustes**
se cambian allí; el bot aplica el cambio en el minuto siguiente, sin reiniciar. El Bugtracker, los roles temporales, las
sanciones vinculadas y los colectivos están apagados al principio.

### Configuración del servidor

*Discord → Configuración del servidor* crea en Discord lo necesario para vincular el sitio, sin hacer nada a mano:

- una **categoría** (reutilizada si ya existe);
- un **canal Foro por foro** elegido, con los prefijos del foro como etiquetas;
- un **rol por grupo** elegido, colocado bajo el del bot para que pueda darlo.

Un canal o un rol con el mismo nombre se reutiliza en lugar de duplicarse. Una **vista previa** muestra lo que se creará
y reutilizará; **Aplicar** lo hace, y las correspondencias (canal ↔ foro, grupo ↔ rol) se colocan solas. **Deshacer la
última configuración** elimina lo que creó — nunca lo que reutilizó.

## Roles y apodos

*Discord → Grupos y roles* vincula un grupo del sitio a un rol del servidor. **Manda el sitio**:

- un miembro vinculado que está en el grupo recibe el rol; quien ya no está lo pierde;
- un rol que nada vincula **nunca se toca**; tampoco un miembro que no vinculó su cuenta;
- con el ajuste *Apodos*, el miembro vinculado lleva en el servidor su nombre del sitio (salvo el propietario del
  servidor, al que Discord protege);
- un rol vinculado lleva el **nombre y el color de su grupo** (bot 0.2.5): un grupo renombrado o recoloreado en el sitio
  también lo está en el servidor. Un rol vinculado a varios grupos conserva los suyos, y un grupo sin color deja el del
  rol. El ajuste «Dar a los roles vinculados el nombre y el color de su grupo» lo apaga.

El bot aplica un cambio de grupo en medio minuto, la llegada de un miembro vinculado al servidor y la vinculación de una
cuenta al instante, y vuelve a pasar por todos al arrancar, en cada cambio de ajuste y en el intervalo elegido (diez
minutos por defecto).

## La cuenta y la apariencia: `/forum`

Un miembro vincula su cuenta conectándose al sitio con Discord, desde *Mi cuenta → Mis cuentas vinculadas*, o **desde
Discord**:

- `/forum account link` da un enlace de un solo uso, válido quince minutos. En el sitio, conectado, el miembro confirma
  la vinculación — y puede recuperar a su nombre lo que había publicado desde Discord;
- `/forum account unlink` desvincula, salvo si Discord es su única forma de conectarse al sitio.

Quien no vinculó su cuenta elige cómo aparece en el foro del sitio:

- `/forum visibility public` — con su nombre de Discord;
- `/forum visibility guest` — con un nombre anónimo;
- `/forum visibility custom` — con un nombre de su elección (2 a 32 caracteres, modificable cada siete días, nunca el de
  un miembro del sitio);
- `/forum visibility status` — lo que está establecido.

Sus mensajes ya publicados siguen su elección. Todas las respuestas son privadas, en el idioma de Discord de cada uno.

## El foro y los canales Foro

*Discord → Canales y foros* vincula un foro del sitio a un **canal Foro** de Discord (no un canal de texto). Dos modos,
canal por canal:

- **Todo**: cada hilo del canal se convierte en un tema del sitio, y cada tema del foro en un hilo;
- **A petición**: un hilo solo pasa al sitio cuando alguien que puede *gestionar los hilos* del canal pone la
  **reacción** elegida (📌 por defecto) — el hilo entero, y luego lo que sigue. Los temas del sitio, ya públicos, van
  siempre a Discord.

Lo que sigue, y cómo:

| | Del sitio a Discord | De Discord al sitio |
|---|---|---|
| Nuevo tema / hilo | un hilo, con el nombre y el avatar del autor | un tema |
| Respuesta | un mensaje en el hilo | una respuesta (la cita de un mensaje se conserva) |
| Edición | el mensaje se reescribe, el título del hilo sigue | el mensaje se reescribe |
| Eliminación | el mensaje desaparece; un tema eliminado se lleva su hilo | el mensaje pasa a la papelera del foro |
| Prefijo / etiqueta | se pone la etiqueta correspondiente | se pone el prefijo correspondiente |

- **Prefijos y etiquetas** (botón de cada canal vinculado): un prefijo del foro ↔ una etiqueta del canal. La configuración
  del servidor los vincula sola.
- Un mensaje venido de Discord se publica con la **cuenta del miembro** que vinculó su Discord; si no, con su **identidad
  de Discord**, como la eligió con `/forum visibility`, marcada con el logo de Discord.
- Las **sanciones de moderación** del sitio siguen al miembro vinculado: silenciado o baneado del foro, sus mensajes de
  Discord no se copian; privado de enlaces externos, un mensaje que los lleve tampoco (bot 0.2.5). El registro del bot lo
  anota, sin decir nada en el canal; `/bug` e `/idee` responden solo al autor.
- Un mensaje demasiado largo para Discord (2000 caracteres) se corta allí, con un enlace al resto.
- Las **imágenes** de un mensaje del sitio van a Discord como vistas previas, bajo el texto; los enlaces del mensaje no
  muestran allí tarjeta de vista previa.
- Una **imagen adjunta en Discord** (JPEG, PNG, GIF o WebP, 5 MB como máximo) se conserva en el sitio y se muestra en el
  mensaje del foro — un sitio demasiado antiguo para recibirla conserva el enlace. Los demás adjuntos, y todos los del
  Bugtracker, se señalan con un enlace al mensaje de Discord.
- Ajustes: un enlace al sitio bajo cada tema copiado, y la **recuperación** al arrancar de lo que se escribió en Discord
  mientras el bot estaba apagado.
- Los **permisos** de cada canal vinculado siguen los derechos de su foro en el sitio (bot 0.2.5, ajuste encendido por
  defecto). Todo el servidor cuenta como los miembros del sitio: quien no puede leer el foro no ve el canal, quien no
  puede escribir en él no publica allí — la escritura solo en modo **Todo**; en modo **A petición**, Discord sigue siendo
  un lugar de conversación. Un rol vinculado da a su grupo lo que los miembros no tienen: el canal de un foro del equipo
  solo se muestra a los roles del equipo. El bot solo toca la vista y la escritura, para @everyone, los roles vinculados
  y él mismo; cualquier otro ajuste hecho a mano se queda.
- El sitio, por su parte, se niega a escribir en nombre de quien no tiene derecho: un mensaje venido de Discord en un
  foro donde su autor no puede escribir se queda en Discord, y el registro del bot lo dice.
- No se trasladan: la eliminación de un hilo entero en Discord (el tema se queda, el registro lo señala), la de su mensaje
  inicial, y en Discord la edición de un mensaje escrito en Discord (pertenece a su autor). La copia en Discord de un
  mensaje del sitio, eliminada por un moderador, no elimina el original.

## El Bugtracker

En los ajustes de la funcionalidad, elige el **canal Foro de los tickets** (no un canal ya vinculado a un foro). El bot
crea allí las etiquetas de los tipos (Error, Idea, Pregunta, Otro) y de los estados (Abierto, En curso, Resuelto,
Cerrado, No se hará, Duplicado), en el idioma del sitio.

Para separar las ideas de los errores, elige también un **canal Foro de las sugerencias** (bot 0.2.5): los tickets de
tipo Idea tienen allí su hilo, con la etiqueta Idea y las de los estados; los demás se quedan en el canal de los tickets.

- Cada ticket se convierte en un **hilo**, con el nombre y el avatar de su autor, con su número, su tipo, su prioridad y
  un enlace al sitio. Los tickets aún abiertos reciben su hilo cuando eliges el canal — y, cuando eliges el canal de las
  sugerencias, las ideas aún abiertas pasan allí.
- Un ticket que **cambia de tipo** (un error convertido en idea) cambia de canal: Discord no mueve un hilo, así que el
  bot abre uno nuevo en el canal correcto. El hilo antiguo, cerrado, remite a él, y el nuevo remite al antiguo. En el
  sitio, el ticket conserva todos sus comentarios.
- Cuando el ticket cambia en el sitio, el hilo sigue: etiquetas, título, descripción. Se **archiva** cuando el ticket se
  cierra; un duplicado remite a su ticket original; un ticket eliminado se lleva su hilo.
- Los **comentarios** pasan en ambos sentidos. En Discord, quien no vinculó su cuenta comenta con su nombre de Discord.
- `/bug` e `/idee` abren un ticket mediante una pequeña ventana (título, descripción), y un hilo abierto a mano en uno de
  los canales se convierte en un ticket — una idea en el canal de las sugerencias. Un ticket pertenece a un miembro: hay
  que haber vinculado la cuenta (si no, el bot lo recuerda).
- **Manda el sitio**: una etiqueta cambiada a mano en Discord se vuelve a poner como dice el ticket.

## Los roles temporales: `/role`

Reservado a quien puede **gestionar los roles** en el servidor:

- `/role give` — dar un rol a un miembro por un tiempo (minutos, horas, días, semanas), con un motivo si se quiere. Dado
  de nuevo, se prolonga;
- `/role remove` — retirarlo al instante;
- `/role list` — los que están en curso, para todo el servidor o para un miembro.

El bot retira el rol al vencimiento (comprobado cada minuto), **incluso tras un reinicio**: la lista vive en el sitio, en
*Discord → Roles temporales*, donde **Quitar ahora** adelanta el vencimiento. Un miembro que deja el servidor y vuelve
antes del final recupera su rol (ajuste *Devolver el rol*).

Se rechazan: `@everyone` y los roles de una integración, los roles **vinculados a un grupo** del sitio (la
sincronización de los grupos los gestiona), y — como hace Discord para este derecho — un rol situado por encima del bot,
o al nivel de quien lo da o por encima. La duración está limitada por el ajuste *Duración máxima* (90 días por defecto,
un año como máximo).

## Las sanciones vinculadas

Apagada por defecto: *Discord → Funcionalidades → Sanciones vinculadas*. Dos sentidos, cada uno con su ajuste.

**Del sitio a Discord** (*Aplicar en Discord los silencios y baneos de todo el sitio*, encendido con la
funcionalidad): una sanción de **todo el sitio** sigue a la cuenta de Discord vinculada del miembro.

| En el sitio | En Discord |
|---|---|
| Silencio | una expulsión temporal hasta el vencimiento — renovada por el bot más allá de los 28 días que permite Discord |
| Baneo temporal | un baneo, levantado por el bot al vencimiento |
| Baneo definitivo | un baneo |

Una advertencia, una restricción, un shadow ban (debe seguir invisible) y la sanción de un solo módulo (el foro, los
salones…) nunca pasan. Una sanción que espera su validación pasa cuando se valida. Levantada en el sitio, se levanta en
Discord — el bot solo retira lo que puso (su motivo lleva «NeoFrag #número») o lo que venía de Discord. Vuelve a leer las
sanciones en curso al arrancar, y luego cada diez minutos: un vencimiento pasado mientras estaba parado se recupera.

**De Discord al sitio** (*Comunicar al sitio las sanciones impuestas en Discord*, apagado por defecto): un baneo o una
expulsión temporal puestos en Discord pasan al sitio si el moderador es una persona cuya cuenta de Discord está vinculada
a una cuenta del sitio que tiene ese derecho — *moderar* para una expulsión temporal (un silencio en el sitio), *banear
definitivamente* para un baneo —, y si el miembro sancionado vinculó la suya. El sitio la vuelve a juzgar como si la
hubiera impuesto él (jerarquía, validación); si no, se queda en Discord, y el registro del bot dice por qué. Una sanción
venida de Discord se levanta en Discord (desbanear, retirar la expulsión temporal) o en el sitio; una sanción del sitio
solo se levanta en el sitio.

El enlace de invitación no da estos derechos al bot: añádelos a su rol — *Banear miembros* y *Aislar temporalmente a
miembros* para el primer sentido, *Ver el registro de auditoría* para el segundo. El bot dice en su registro lo que le
falta. Discord se niega a sancionar al propietario del servidor y a un miembro situado por encima del rol del bot.

## Colectivos

Apagada por defecto: *Discord → Funcionalidades → Colectivos*. Cada colectivo del sitio cuya casilla *Discord* esté
marcada ([Colectivos](admin.md#colectivos)) recibe en el servidor:

- un **rol**, con su nombre — es el grupo del colectivo, vinculado a este rol: la funcionalidad *Roles y apodos* se lo da
  a sus miembros que vincularon su cuenta, y se lo retira a quien sale;
- un **canal de texto** en la categoría *Collectifs* (el nombre se ajusta; el bot la crea), cerrado a `@everyone`,
  abierto a este rol.

El bot crea lo que falta al arrancar, cuando cambia la configuración (un colectivo creado o renombrado) y cada cinco
minutos: un rol o un canal eliminado a mano vuelve, un colectivo renombrado se renombra. Un colectivo eliminado, o cuya
casilla se desmarca: el bot elimina su rol, y su canal **se queda**, visible solo para los administradores del servidor.
El rol del bot necesita *Gestionar roles* y *Gestionar canales* (en el enlace de invitación), y debe estar por encima de
los roles que crea.

## Seguridad

- En la máquina del bot: la dirección del sitio y su clave de acceso, nada más.
- El token del bot está cifrado en la base del sitio con la clave propia del sitio. Solo una clave de acceso con el
  derecho `discord:bot` puede leerlo — la que la administración crea para el bot, revocable en cualquier momento.
- El bot nunca menciona a nadie: un mensaje copiado no puede notificar a `@everyone`.
- No necesita «Administrador», y el enlace de invitación no lo pide. Si lo tiene de todas formas (un rol dado a mano), lo
  dice en su registro en cada arranque: quítaselo — un bot administrador cuyo token se filtra abre todo el servidor.
  También nombra los permisos del enlace de invitación que le faltan a su rol.
- Lo que llega de Discord — un nombre de hilo, un nombre de usuario, un comentario — se guarda en el sitio como lo que se
  escribe en él: se muestra, no se ejecuta.

## Escribir una funcionalidad

El bot se amplía con **funcionalidades**, cada una en su carpeta, en TypeScript. Cómo hacerlo — el contrato de una
funcionalidad, sus ajustes, sus comandos, sus textos traducidos, sus pruebas — está en la guía del colaborador del bot,
`CONTRIBUTING.md`, en la raíz de su código: [NeoFragReborn/bot-discord](https://github.com/NeoFragReborn/bot-discord).
