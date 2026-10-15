# Changelog

Esta es la traducción al español de [CHANGELOG.md](CHANGELOG.md); la versión francesa es la de referencia.

Todos los cambios notables de **NeoFrag Reborn** se recogen aquí.

Formato inspirado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/);
los números de versión se inspiran en el [versionado semántico](https://semver.org/lang/es/); una versión que solo
cambia el último número (1.2.x) puede, aun así, traer novedades y retiradas.

NeoFrag Reborn es la continuación comunitaria de **NeoFrag** (base Alpha 0.2.4), creado originalmente por
Michaël BILCOT y Jérémy VALENTIN — un proyecto de código abierto con licencia LGPLv3.

---

## [1.2.52] — 2026-10-15

### Corregido

- **Las páginas del foro declaraban su canónica en otro idioma.** Un foro conserva un nombre por defecto para los
  idiomas a los que no está traducido; la 1.2.51 solo miraba sus traducciones, y una página del foro en el idioma del
  nombre por defecto enviaba a los buscadores a otro idioma, a una dirección que redirigía. Cada foro vuelve a
  anunciarse en todos los idiomas, cada uno en la dirección de su propio nombre.

## [1.2.51] — 2026-10-15

### Añadido

- **Desinstalar un módulo o un widget, y restablecer un módulo, desde su ficha.** Hasta ahora, un módulo o un
  widget solo se podía desactivar. **Desinstalar** retira sus tablas, sus datos y sus archivos; un módulo que otro
  addon necesita se rechaza, y la ventana dice cuáles. **Restablecer** borra los datos de un módulo y lo hace volver a
  empezar como el día de su instalación; sus ajustes, sus permisos y los enlaces que llevan a él se mantienen. Ambos
  hacen primero una copia de seguridad (carpeta `backups/addons` del servidor), y la confirmación dice cuántas filas
  se van. Los addons del núcleo no se retiran.

### Corregido

- **Las vistas previas del marketplace seguían siendo las antiguas en el navegador.** Una miniatura conserva su
  dirección de una versión a otra: quien ya la había visto conservaba la antigua en caché — las miniaturas dibujadas
  de la 1.2.50 seguían pareciendo capturas. La dirección lleva ahora la huella del archivo.
- **Un álbum de las Galerías no se podía traducir.** En la administración de otro idioma, un álbum aún sin
  traducir faltaba en la lista, y su pantalla de edición respondía «página no encontrada». Ahora aparece, y su
  pantalla se abre con los campos vacíos, listos para la traducción; el texto de los demás idiomas no cambia.
- **Nebula: «Cerrar sesión» no se encontraba en las noticias, el foro y los perfiles.** Esas páginas no tienen
  columna lateral, así que tampoco el bloque del área de miembros, y el nombre de usuario de arriba solo llevaba a «Mi
  espacio». Ahora abre el menú de la cuenta, el mismo que en los demás temas: perfil, cuenta, mensajes,
  administración, cerrar sesión.
- **El menú de un tema se quedaba en el idioma del administrador que lo había activado.** Activado o reinstalado
  desde una administración en inglés, un tema escribía «Home», «News»… y todos los visitantes, franceses incluidos,
  lo leían así. Sus enlaces se escriben ahora en francés, el idioma de origen, y la navegación los muestra en el
  idioma de cada página. Un tema ya instalado conserva su menú: «Reinstalar a los valores predeterminados» lo
  reescribe.
- **Los buscadores conocen las traducciones del contenido.** Los eventos, el palmarés, las ofertas de reclutamiento,
  las encuestas, las campañas de donaciones, las páginas del wiki, las descargas, las FAQ, los enlaces y la tienda se
  declaraban todavía sin idioma: una sola dirección anunciada, en francés. Ahora anuncian los idiomas en los que
  están escritos, y un idioma en el que no lo están remite su canónica a uno en el que sí. Y los enlaces entre
  idiomas de las noticias, las entradas del Blog, los foros y las categorías de anuncios llevaban, en los demás
  idiomas, a una redirección hacia el título traducido — los buscadores descartan un enlace así: cada uno lleva
  ahora a la dirección de su propio título.

## [1.2.50] — 2026-10-15

### Añadido

- **Un tema muestra el selector de idioma en una línea**: `nf_selecteur_langue()`, junto a
  `nf_selecteur_theme()` y `nf_liens_legaux()`. Los temas incluidos copiaban cada uno su formulario.
- **La documentación y las notas de versión, en los seis idiomas.** Las guías de la wiki — instalación, primeros pasos,
  administración, marketplace, sitio multilingüe, bot de Discord, framework, crear un tema, un módulo o un widget —
  solo existían en francés y en inglés: ahora se escriben también en alemán, español, italiano y portugués, con las
  etiquetas exactas que muestra la pantalla en cada idioma, y la wiki las sirve en el idioma del visitante. Las notas
  de versión siguen el mismo camino: la página «Notas de versión» de la wiki y las entradas del Blog se leen en los seis
  idiomas. El francés sigue siendo la referencia; una prueba rechaza una traducción que se saltara una versión o la
  fechara de otra forma.
- **Las miniaturas de los módulos y widgets, rehechas.** En *Temas y addons* y en el marketplace, la vista previa de una
  extensión era una foto de ella en funcionamiento — ilegible cuando no tenía datos que mostrar: un marco, un título,
  unas líneas. Cada módulo y cada widget recibe ahora una tarjeta dibujada con el lenguaje visual del proyecto: su
  icono, su nombre y lo que hace, legibles a cualquier tamaño. Los temas conservan la foto de su página de inicio: un
  tema se muestra a sí mismo.
- **Las bibliotecas incluidas al día, y una salvaguarda para que lo sigan estando.** El editor de texto pasa a
  TinyMCE 8 (la línea 7 perdía su soporte el 14 de noviembre, y la 8.9.3 corregía un fallo de su plugin «media») y los
  iconos a Font Awesome 7 — los 363 iconos que usa el sitio se cotejaron con la nueva colección antes del cambio, y no
  falta ninguno. Los archivos de fuente duplicados (.ttf) desaparecen: la versión 7 solo sirve el formato moderno,
  715 KB menos en el paquete. Sobre todo, un nuevo control vigila: no da la alarma con cada publicación de los proyectos
  de origen, pero rechaza cuando las versiones entregadas llevan más de tres meses sin cotejarse con el registro. Una
  dependencia no se vuelve peligrosa de golpe — se va desviando, y ahora esa deriva se ve.
- **Las notificaciones push.** Un miembro puede recibir sus notificaciones en el navegador, incluso con el sitio
  cerrado: un mensaje del foro, una reacción, un recordatorio de evento. El administrador lo ofrece (Ajustes →
  Preferencias generales → Notificaciones push); cada miembro lo activa después dispositivo por dispositivo, en sus
  preferencias de notificaciones, elige tipo por tipo lo que recibe así, y puede enviarse una prueba. Sin añadir
  bibliotecas: el mensaje se cifra para ese navegador en concreto (RFC 8291) y se firma en nombre del sitio (VAPID,
  RFC 8292) — el servicio push del navegador lo transporta sin poder leerlo. El servidor solo escribe a servicios push
  conocidos (Google, Mozilla, Apple, Microsoft), nunca a una dirección que un miembro hubiera podido inventar.
- **Nebula y el tema escaparate tienen su página de colores, y los ocho temas una vista previa en directo.** Acento,
  fondo, texto: las paletas de Nebula y del escaparate estaban escritas en sus hojas de estilo; ahora se ajustan como
  las de los otros seis temas, con el mismo rechazo de un color ilegible. Y bajo los selectores de cada tema, una página
  real del sitio — la de inicio, las noticias o el foro — toma los colores a medida que se eligen, sin guardar nada,
  con los contrastes medidos en directo: se ve el resultado antes de pulsar «Guardar».
- **Los enlaces de los menús, las salas públicas, el título propio de un widget y las páginas de la wiki se
  traducen.** Con ellos, ya no queda ningún texto escrito por el responsable del sitio sin idioma. Una página de la wiki
  guarda un historial POR IDIOMA: traducir al italiano no archiva nada de la versión francesa, y el historial italiano
  solo muestra sus propias revisiones. Una conversación entre miembros conserva un único nombre para todos sus
  participantes — solo se traducen las salas públicas. La página «Notas de versión» de la wiki existe ahora también en
  inglés.
- **La tienda, las donaciones, la publicidad, los paquetes de pago y el reclutamiento se traducen.** El nombre y la
  descripción de un artículo de la tienda, el título y la descripción de una campaña de donaciones, el título de un
  anuncio, la etiqueta de un paquete y una oferta de reclutamiento entera — su título, su entradilla, su descripción,
  sus requisitos y las preguntas de su formulario de candidatura — se escriben ahora idioma por idioma. Las preguntas no
  tenían pantalla para editarlas (solo se podían añadir o eliminar): ahora las abre un lápiz, y es ahí donde se
  traducen. Lo que no es texto sigue siendo común: el precio, el stock, la dirección de una campaña, el código de una
  red publicitaria, el rol asignado en el equipo — y las respuestas de los candidatos, que conservan la pregunta tal
  como la leyeron.
- **Los modos de juego, los mapas, los programas de la radio web y los campos de perfil se traducen.** «Compétitif 5v5»
  se convierte en «Competitive 5v5» para un visitante de habla inglesa, «Le réveil du serveur» en «The server wake-up»,
  y la etiqueta de un campo de perfil se escribe en cada idioma. Un nombre de mapa sigue siendo un nombre propio, pero
  también se escribe por idioma, para quien quiera traducir «Faille de l'invocateur». Se ha tomado una decisión, y la
  pantalla lo dice: la LISTA DE OPCIONES de un campo de perfil sigue siendo común — una opción se guarda tal cual en la
  respuesta del miembro, y traducirla haría imposibles de encontrar las respuestas ya dadas.
- **Los tipos de evento, los roles de equipo y las secciones de los anuncios clasificados se traducen.** «Torneo»,
  «Capitán», «Material»: tres nombres cortos que escribe el responsable del sitio, y que un sitio en seis idiomas
  mostraba en uno solo. Ahora se escriben idioma por idioma, y el visitante los ve en el suyo en todos los lugares
  donde aparecen — la lista de eventos y sus fichas, el calendario, la plantilla de un equipo, el directorio de
  anuncios clasificados y las pantallas de administración.
- **Una encuesta se edita sin perder sus votos — y se traduce.** Hasta ahora, guardar una encuesta borraba todas sus
  respuestas para volver a crearlas, y todos los VOTOS con ellas: corregir una coma en la pregunta costaba la encuesta
  entera (la pantalla lo avisaba, lo que no la hacía menos costosa). Ahora una respuesta conserva sus votos mientras
  siga en la lista — corregida, movida o traducida; solo una respuesta RETIRADA pierde los suyos, y la pantalla lo dice
  contándolos. Es lo que por fin permite traducir una encuesta: la pregunta, su descripción y la etiqueta de cada
  respuesta se escriben idioma por idioma, y escribir la versión italiana ya no toca la francesa.
- **Las descargas, los enlaces útiles y la FAQ se traducen.** El título y la descripción de un archivo o de un enlace,
  la pregunta y su respuesta, y los títulos de sus categorías, no tenían idioma: un sitio en seis idiomas los mostraba
  en uno solo — se veía en la demostración en inglés, que anunciaba «Pack de fonds d'écran» y «Chaîne Twitch». Ahora se
  escriben idioma por idioma, como las noticias o los eventos: la pantalla de edición abre el idioma en el que se
  trabaja y dice cuál, y el visitante los ve en su idioma — en la biblioteca, en el directorio de enlaces, en los
  widgets y en los bloques. Lo que no tiene idioma (la dirección, el tamaño, el tipo, la versión, los contadores) sigue
  siendo común, y lo que solo está escrito en un idioma sigue visible en los otros cinco.
- **Se rechaza un color de tema que haría ilegible el sitio.** Los seis temas que dejan elegir su color de acento, de
  texto y de fondo aceptaban cualquier cosa: un acento casi idéntico al fondo, y los enlaces y botones desaparecían; un
  texto demasiado pálido, y la página ya no se podía leer. Nada lo advertía, y había que recuperar uno mismo el color
  anterior. Cada color se mide ahora frente a los otros dos — los guardados, ya que los dos formularios se guardan por
  separado — y se rechaza, con su cifra, cuando no destaca lo suficiente: 4,5 a 1 para el texto, 3 a 1 para un acento,
  como exige la norma de accesibilidad. Los colores originales de los nueve temas pasan todos.
- **Las bibliotecas incluidas, actualizadas.** El editor de texto (TinyMCE 7.6.1 → 7.9.3, que incluye un limpiador de
  HTML más reciente), los gráficos de las estadísticas (Chart.js 4.4.7 → 4.5.1) y las listas desplegables (Tom Select
  2.6.1 → 2.6.2). Las otras tres — Bootstrap, flatpickr, Sortable — ya estaban en la última versión publicada. Se
  conservan las traducciones del editor, corregidas a mano.
- **Treinta y un títulos de pantalla hablaban francés en los seis idiomas.** «Actualizaciones», «Nuevo mapa», «Mi
  opinión sobre la candidatura», «Gestionar mi cuenta», «Socios», «Ofertas de reclutamiento»…: los títulos de paneles,
  ventanas y botones no pasaban por las traducciones, y se mostraban en francés fuera cual fuera el idioma del sitio.
  Su traducción ya existía, en los seis idiomas: solo faltaba la llamada. El control que vigila los textos fijos en el
  código los dejaba pasar porque suponía que esos títulos los traducía el producto — lo que en realidad solo hacen las
  etiquetas de FORMULARIO; ahora distingue ambos casos.
- **Tres pantallas recuperan el diseño de su tema.** La hoja de estilos de un widget se sirve después de la del tema:
  cuando ambas decían lo mismo con el mismo peso, ganaba el widget, y el tema dibujaba en el vacío — una pantalla
  presentable, un diseño pensado que nunca se servía, y nada que lo dijera. El panel derecho de *Extend* (quién está en
  línea, la sala de chat) y sus grandes cifras recuperan su tipografía y sus colores; las pestañas del perfil de
  *Forge* vuelven a ser cuadradas, subrayadas y en mayúsculas, como las de sus temas hermanos; y el indicador de salud
  de la *Monitorización* cabe por fin en su círculo, en lugar de desbordarlo. Un control rechaza ahora cualquier
  declaración de tema que nunca podría aplicarse.
- **Los eventos se traducen.** El título de un evento, su descripción, su descripción privada y su lugar no tenían
  idioma: un sitio en seis idiomas los mostraba en uno solo. Ahora se escriben idioma por idioma — la pantalla de
  edición abre el idioma en el que se trabaja, y dice cuál. El visitante ve un evento en su idioma en todos los lugares
  donde aparece: la lista, la ficha, el calendario, los recordatorios por correo, el mapa del sitio y el widget. Lo que
  no tiene idioma (las fechas, el tipo, la imagen, el autor, los participantes) sigue siendo común; un evento escrito en
  un solo idioma sigue visible en los otros cinco.
- **El palmarés se traduce.** El nombre de un título ganado, su lugar y su relato no tenían idioma: un sitio en seis
  idiomas los mostraba en uno solo. Ahora se escriben idioma por idioma, como el resto — la pantalla de edición abre el
  idioma en el que se trabaja, y dice cuál. Lo que no tiene idioma (la fecha, la posición, el número de participantes,
  la plataforma, el equipo, el juego) sigue siendo común.
- **El carrusel se traduce.** El título y la leyenda de una diapositiva no tenían idioma: un sitio en seis idiomas los
  mostraba en uno solo. Ahora se escriben idioma por idioma, como una página o una categoría — la administración
  escribe en el idioma en el que trabaja, y lo dice encima del campo. El visitante ve la diapositiva en su idioma; una
  diapositiva traducida a un solo idioma sigue visible en los otros cinco (el texto del idioma pedido, si no el
  francés, si no el primero que se escribió), y una diapositiva sin ningún texto conserva su imagen. Nada se pierde en
  la actualización: el texto de hoy se copia en todos los idiomas instalados, ya que es lo que hoy ve cualquier
  visitante.
- **Las sanciones del sitio siguen al miembro en Discord.** Con el bot 0.3.0 y su función *Sanciones vinculadas*
  (desactivada al principio), los silencios y baneos de todo el sitio se aplican a la cuenta de Discord vinculada del
  miembro — un aislamiento temporal para un silencio, un baneo para un baneo — y se levantan en Discord cuando se
  levantan en el sitio. Una advertencia, una restricción o un shadow ban nunca se trasladan. En sentido contrario, y
  solo si se activa: un moderador cuya cuenta de Discord está vinculada a una cuenta del sitio que tiene permiso para
  ello puede banear o aislar en Discord, y el sitio recibe la sanción y la vuelve a juzgar como si se hubiera dictado
  en él. La lista de sanciones dice ahora de dónde viene cada una.
- **Los colectivos.** Un nuevo módulo reúne a miembros en torno a un proyecto, al margen de los demás: una redacción,
  un equipo de moderación, un grupo de creadores. Creado en la administración (*Comunidad → Colectivos*), un colectivo
  recibe una sección privada del foro, que solo leen sus miembros, y — con el bot 0.3.0 y su función *Colectivos* — un
  rol y un canal privado en Discord. Su responsable añade miembros y les da un rango (administrador, moderador,
  miembro) desde la página del colectivo, sin ser administrador del sitio; los moderadores moderan su sección. Si se
  elimina un colectivo, su sección y su canal se conservan, reservados a los administradores: nada de lo que se
  escribió allí se pierde.
- **Las cuotas.** Una asociación cobra sus cuotas en el sitio: un miembro paga en línea (Stripe) — o la administración
  registra una cuota pagada en efectivo o con cheque — y pasa a ser **afiliado** durante el periodo elegido. Recibe el
  rol «Afiliado», que se puede abrir a lo que se quiera (una sección reservada del foro, páginas), y la insignia del
  grupo «Afiliados». Siete días antes del vencimiento se le avisa; al vencer, se le retira el rol. Las cuotas no
  necesitan el módulo Gamificación, que los pagos exigían hasta ahora.
- **Las donaciones hechas en otros sitios se registran solas.** Ko-fi, HelloAsso, TipeeeStream y el botón de PayPal de
  las campañas: cada donación llega a la campaña elegida, una sola vez, y la barra de progreso avanza sin introducir
  nada a mano. Nada se registra sin prueba — el token de Ko-fi, el pedido releído en HelloAsso, la confirmación de
  PayPal. Una afiliación pagada en HelloAsso convierte en afiliado al miembro del sitio que tiene la misma dirección de
  correo. Los nombres de los donantes de HelloAsso y PayPal no se muestran públicamente.

### Seguridad

- **El bot de Discord avisa de que es administrador del servidor.** No lo necesita — el enlace de invitación no lo
  pide —, pero un rol asignado a mano podía darle ese permiso, y un bot administrador cuyo token se filtra abre todo el
  servidor. Cada vez que entra en el servidor, el registro del bot, que muestra la administración, lo dice, y nombra los
  permisos que le faltan a su rol.
- **El enlace de «/forum account link» no siempre vinculaba la cuenta correcta.** Quien recibía ese enlace de otra
  persona vinculaba la cuenta de Discord *de esa persona* a SU PROPIA cuenta del sitio — y como una cuenta vinculada
  permite iniciar sesión, quien había enviado el enlace podía después entrar en la cuenta de su víctima. La vinculación
  se confirma ahora con Discord: el sitio compara el identificador que Discord le devuelve con el que nombra el enlace,
  y no vincula nada si difieren. Una advertencia sustituía a esta comprobación; no bastaba.

### Corregido

- **El ajuste «Fuente del sitio» decía que las fuentes las servía Google Fonts.** Las sirve el propio sitio desde que
  ningún visitante se envía ya a Google: la descripción ahora lo dice.
- **Una actualización de addon volvía a ejecutar todas sus migraciones.** La lectura de las migraciones ya hechas
  siempre volvía vacía: en cada actualización de un módulo, un widget o un tema — por el marketplace o por un archivo
  —, todas sus migraciones se ejecutaban de nuevo, y las que escriben datos los escribían una segunda vez. Ahora solo
  se ejecutan las nuevas. `check-db-query` vigila la forma que causaba el defecto: una consulta preparada sin llegar a
  enviarse.
- **La cuenta atrás de los eventos próximos se quedaba vacía.** Su script vivía en un tema retirado: la lista de
  eventos y la página de un evento mostraban un reloj de arena junto a nada, en todos los temas. Ahora vive con el
  módulo, y escribe el tiempo que queda en el idioma de la página («11d 7h 22min», «11d 7h 22m»).
- **Justo después de una actualización, el sitio todavía podía servir el código antiguo.** La caché de PHP (OPcache)
  conserva el código compilado de un archivo hasta que lo vuelve a comprobar: dos segundos en general, y hasta el
  reinicio de PHP en los alojamientos que desactivan esa comprobación. La actualización del núcleo, la de un addon por
  el marketplace y el envío de un archivo por «Añadir» no la avisaban: una página de un módulo actualizado respondía
  «página no encontrada» hasta que se volvían a leer sus nuevas direcciones, y un sitio podía funcionar con una mezcla
  de código antiguo y nuevo. Las tres avisan ahora a la caché de cada archivo que escriben.
- **Actualizar un addon enviando su archivo se saltaba sus migraciones — y borraba la disposición de un tema.**
  «Añadir», en *Temas y addons*, reinstalaba el addon en lugar de actualizarlo: los cambios de su base de datos se
  marcaban como hechos sin hacerse, y un tema perdía todas sus disposiciones y sus widgets, sustituidos por su
  disposición original. Un archivo más reciente se actualiza ahora como por el marketplace: su base de datos sigue, y
  la disposición sigue siendo la del administrador.
- **En el editor en vivo de Nebula, las vistas previas de los estilos eran las de Forge**: naranja sobre marrón.
  Ahora toman los colores de Nebula, los que eligió el administrador. La misma corrección para el tema escaparate.
- **Un widget de navegación que mostraba un menú guardaba una copia congelada de él.** Copiaba los enlaces del menú en
  el momento de guardarlo, y hasta olvidaba cuál se había elegido: un enlace añadido o renombrado después nunca llegaba
  a la navegación. El menú se vuelve a leer ahora en cada visualización, en el idioma de la página.
- **Un enlace a una sección de una página de la wiki llevaba al principio de la página.** Solo los títulos de primer
  nivel tenían un ancla: un enlace a un subtítulo no encontraba nada, y la documentación, que a menudo remite a una
  sección concreta («ver Posicionamiento»), dejaba a su lector al principio de la página, en todos los idiomas. Los
  subtítulos tienen ahora su ancla, y cada remisión de la documentación lleva al título que nombra.
- **Una página de la wiki colocada bajo una de sus propias subpáginas desaparecía del índice**, con toda su sección: la
  pantalla ofrecía las subpáginas como página madre. Ya no lo hace, y un bucle así enviado de todos modos se rechaza.
- **El formulario de candidatura respondía «Error 500» en cuanto una oferta hacía una pregunta opcional.** Una regla
  «ninguna» escrita como texto vacío no la entendía la biblioteca de formularios, y toda la página caía. Encontrado al
  probar la traducción de las preguntas.
- **El widget *Reclutamiento* ocultaba las ofertas sin candidaturas** — es decir, toda oferta recién abierta. El módulo
  se había corregido; el widget, no.
- **Una confirmación de eliminación abierta como página completa no mostraba nada.** En la administración, seguir un
  enlace «Eliminar» en una nueva pestaña — o sin JavaScript — llevaba a una página en blanco: ni mensaje, ni botón, ni
  vuelta atrás. Ahora muestra lo que va a eliminar, el botón que lo hace y un enlace de vuelta al módulo.
- **Las páginas Moderación y Monitorización no se parecían al resto de la administración.** Sus tarjetas de cifras,
  sus cabeceras y el latido del indicador de salud venían de una hoja de estilos de respaldo — prevista para los
  sitios que muestran esas pantallas fuera de la administración — que, servida en último lugar, se imponía al tema. El
  respaldo solo se aplica ahora donde el tema no dice nada.
- **En portugués, no se podía eliminar una cuenta.** La pantalla pedía escribir «EXCLUIR» y el sitio esperaba
  «ELIMINAR»: la confirmación fallaba siempre. Los otros cinco idiomas eran correctos. Un control rechaza ahora que una
  instrucción cite una palabra distinta de la que espera el sitio.
- **El portugués mezclaba Brasil y Portugal.** Ochenta expresiones revisadas: *usuário* pasa a *utilizador*, *senha*
  a *palavra-passe*, *excluir* a *eliminar*, *registro* a *registo*, y *arquivo* pasa a *ficheiro* cuando designa
  un fichero — y sigue siendo *arquivo* cuando designa un archivo comprimido.
- **Algunas frases torpes en inglés**: «All posts will be also deleted» pasa a «will also be deleted», en los cuatro
  mensajes de eliminación en los que quedaba.
- **Traducir un contenido por primera vez: el texto se perdía, sin una palabra.** Escribir una noticia en francés,
  pasar la administración al inglés, traducirla y guardar: no se conservaba nada. La fila de ese idioma aún no existía,
  y el guardado no tocaba ninguna fila — en silencio. Ocurría con las noticias, las entradas del blog, las páginas, las
  categorías, las galerías, los juegos, los equipos, los grupos y los socios. Una traducción crea ahora su fila.
- **Un contenido aún sin traducir era inencontrable en la administración de ese idioma.** Las listas de noticias, de
  entradas del blog y de páginas solo mostraban lo que existía en el idioma en el que se trabaja: el resto era
  invisible, y por tanto imposible de abrir para traducirlo. Las listas lo muestran ahora todo, con el mejor título
  disponible, y la pantalla de edición se abre con los campos vacíos, listos para la traducción.
- **Renombrar un grupo borraba todas sus traducciones.** El guardado no apuntaba a ningún idioma en particular:
  escribía el mismo título en todas las filas del grupo — y fallaba en cuanto el sitio tenía más de una.
- **Un socio solo podía tener un idioma.** La tabla de traducciones del módulo *Socios* tenía como clave solo el
  número del socio: un segundo idioma se rechazaba o sobrescribía el primero. Ahora tiene una fila por idioma, como las
  otras dieciséis tablas de traducción, y describir un socio en un nuevo idioma crea su fila en lugar de no escribir
  nada.
- **La próxima cita: el día de la semana pegado al número del día.** En la casilla de fecha del widget *La próxima
  cita*, las tres líneas — día de la semana, número del día, mes — se tocaban, y se solapaban en cuanto un tema
  aplicaba su tipografía de títulos: las cifras desbordaban una altura de línea más corta que su tinta. Las tres líneas
  conservan ahora su separación en los siete temas, en modo claro y oscuro. La misma corrección para los grandes
  números del widget *El sitio en cifras*.
- **Pulse dibuja por fin sus widgets.** El tema preveía su propio aspecto para la casilla de fecha del calendario, los
  números de *El sitio en cifras*, la semana y las miniaturas de la galería: ninguno se mostraba, porque la hoja de
  estilos del widget se sirve después de la del tema y ganaba a igual especificidad. La losa de Pulse (esquinas
  redondeadas, cifras grandes, gris pizarra) aparece ahora como la mostraba la maqueta.

## [1.2.49] — 2026-10-10

Un lote grande: una dirección con un título erróneo lleva a la correcta, la lista de páginas no encontradas, el editor
de texto en el idioma de la página, el módulo Eventos utilizable sin juegos — para una asociación o un club —,
imágenes en los tickets que llegan de Discord, y una larga lista de correcciones encontradas al releer todo el registro
de cambios, entre ellas la página de un partido que ya no mostraba su partido. El bot de Discord pasa a la
**versión 0.2.7** (consulta su propio registro de cambios).

### Añadido

- **Imágenes en un ticket del gestor de errores**: una captura adjunta en Discord a un ticket o a una idea, al abrirlo o
  en una respuesta, aparece ahora en el ticket del sitio — con el bot 0.2.7, que se la entrega al sitio como ya hace
  con el foro. El texto de un ticket sigue siendo texto plano: solo se muestran en él las imágenes que conserva el
  sitio; la dirección de una imagen alojada en otro lugar sigue siendo texto.
- **La lista de páginas no encontradas**, en *Monitorización → Diagnóstico*: cada dirección que el sitio no pudo
  encontrar — sin el idioma ni los parámetros —, cuántos visitantes y robots la pidieron, cuándo por última vez y la
  última página de la que venían los visitantes. Un botón «Redirigir» abre el formulario de redirección con la
  dirección ya rellenada; los sondeos de robots que buscan una vulnerabilidad se listan aparte. Ninguna dirección IP,
  ningún navegador; una dirección que nadie pide desde hace 90 días se olvida.
- **Imágenes abandonadas del editor**, en *Monitorización → Diagnóstico*: una imagen subida a un texto y luego
  retirada, o la imagen de un borrador nunca guardado, se quedaba en el disco. La página las encuentra — ningún texto
  del sitio las muestra ya, ningún contenido, mensaje, ajuste, revisión ni elemento de la papelera, y tienen más de
  30 días —, las muestra y elimina las que marques.
- **El editor de texto habla el idioma de la página**: sus menús, botones y diálogos estaban en inglés en todas
  partes; ahora están en francés, alemán, español, italiano o portugués según el idioma de la página.
- **Un GIF animado sigue animado** en el editor: se convertía en una imagen fija. Se reescribe conservando solo sus
  fotogramas, su ritmo y su bucle. Un GIF de más de 2000 px de lado o de más de 1000 fotogramas sigue convirtiéndose en
  una imagen fija, como antes.
- **Eventos sin juegos**: el módulo Eventos ya no necesita los módulos Juegos y Equipos. Una asociación o un club lo
  instala solo — invitaciones, respuestas, repetición, recordatorios; los partidos, sus rivales y sus resultados
  llegan con los módulos Juegos y Equipos. Entra en los perfiles de instalación «Comunidad» y «Asociación / club».

### Corregido

- **Una dirección con un título erróneo redirige a la correcta** (redirección permanente, 301): el título del
  contenido ha cambiado, o el enlace viene de otro idioma o tiene una errata. Los artículos del blog, las categorías,
  los autores y las series, el palmarés, los tickets del gestor de errores, los eventos del calendario, los anuncios
  clasificados, las encuestas y las recetas respondían a cualquier título — duplicados para los buscadores; las
  noticias, los eventos, las ofertas de reclutamiento, los equipos, los álbumes y las imágenes de la galería, los foros
  y los temas, los perfiles, los grupos y los socios respondían «no encontrado» en la dirección antigua de un título
  cambiado. La página de una categoría del blog muestra su título, ya no el texto de la dirección; la página de un tipo
  de evento o de un equipo que no existe responde ahora «no encontrado».
- **Una imagen subida en el editor ya no desborda** la página en las páginas que no la limitaban (la pestaña «Acerca
  de» de un perfil, eventos, socios, álbumes, ofertas de reclutamiento, equipos) ni en un teléfono: ocupa como máximo
  el ancho de la columna.
- Un tamaño inferior a 1000 bytes se lee «685 B», ya no «685.00 B».
- **Forge**, en modo de día: el texto atenuado (fechas, autores, lugares, «Registrado el…») es un poco más oscuro — se
  quedaba justo por debajo del umbral de legibilidad.
- **Forge y Extend**: las líneas finas que eligen la imagen del carrusel son fáciles de pulsar en un teléfono (solo
  medían 3 px de alto); visualmente no cambia nada.
- **Chronique**: el número de notificaciones sin leer, en el icono de la cuenta, se lee mucho mejor, de día y de
  noche.
- **Discord**: cuando se elimina un tema o un mensaje del foro, un ticket o un comentario de ticket, el registro que lo
  vincula a su hilo de Discord se borra ahora automáticamente una semana después; se quedaba para siempre en la base de
  datos.
- **Nada pegado al borde**: la lista de IP bloqueadas y su formulario para añadir (la frase de ayuda y el botón tocaban
  los bordes de su tarjeta), el campo de búsqueda de la lista de eventos, la paginación de una tabla en Granite, la
  paginación y el botón «Seguir» en Nebula, un nombre de usuario largo en la lista de miembros. En un teléfono, las
  tablas del carrusel y de los permisos se desplazan dentro de su tarjeta en lugar de desbordarla.
- **Se acabaron los botones pegados**: «Invitar», «Salir»… en una conversación, en un teléfono; «Volver» y «Publicar
  el tema» en el foro; los dos botones de la búsqueda del foro en la administración.
- **Forge**: en un teléfono, el menú del área de miembros vuelve — se deslizaba bajo la tarjeta de la página; en un
  ordenador, se queda a la vista al desplazarse. El botón verde «Ver mi candidatura» vuelve a ser legible.
- **La página de un partido** vuelve a mostrar su partido — el equipo, el rival, el resultado, el juego y las rondas,
  que ya no mostraba. El equipo, el resultado y el rival caben en una línea, como cada ronda, en un teléfono como en un
  ordenador.
- **Reclutamiento**: la página «Mi candidatura» vuelve a mostrar el puesto y el equipo correctos — mostraba el nombre
  del icono en lugar del puesto. **Foro**: editar un foro, en la administración, vuelve a mostrar su descripción y su
  categoría — mostraba el título en su lugar, y guardar podía mover el foro a otra parte.
- **Moderación**: una sanción se elimina automáticamente tres años después de terminar (vencida o levantada; una
  advertencia, tres años después de darse); una sanción sin fin (un baneo definitivo, por ejemplo) se queda mientras
  dure. Se conservaban sin límite.
- El botón de denuncia (la banderita «Reportar este contenido») es fácil de pulsar en un teléfono; en la lista
  *Plantillas de correo* de la administración, las variables del asunto de un correo (`{{date}}`) destacan del texto.
- **Boletín**: el destino «Grupo» y la lista de campañas nombran cada grupo en el idioma de la administración; lo
  tomaban en un idioma al azar («Criador de addons», «Mitwirkender»).
- **Etiquetas correctas**: en el perfil de un miembro, el botón «Contacter» mostraba «Contact» en francés; el tema
  Extend escribe «Destacado» y «Descubrir» en el idioma de la página (se quedaban en francés); el botón «Cuadrícula» de
  la página del blog y de los addons ya no se llama «Parrilla» (ni «Schedule» en inglés, ni su equivalente en alemán e
  italiano); el tipo de ticket francés «Demande de feature» pasa a ser «Demande de fonctionnalité». En inglés, el botón
  de inicio de sesión dice «Log in», el menú «Log out», y «View my profile» se usa en todas partes. En la
  Monitorización, la tarjeta «Almacenamiento» escribía «Sauvegarde», «Libre» y «Total» en francés en todos los
  idiomas.
- **Pulse, de noche**: el número de notificaciones sin leer, en el icono de la cuenta, es legible — una cifra oscura
  sobre el ladrillo claro; en blanco, solo alcanzaba 3,9:1.
- **El widget Donaciones** muestra la inicial de un donante cuyo nombre empieza por una letra acentuada; mostraba «&».
- **Restablecer la contraseña** rechaza, como el registro y el cambio, una contraseña idéntica al nombre de usuario: el
  restablecimiento no la comparaba.
- El bot de Discord pasa a la **versión 0.2.7** (0.2.6, luego 0.2.7): entrega al sitio las imágenes adjuntas a los
  tickets (ver arriba), y su registro ya no anota las reconexiones normales a Discord, solo un corte de más de un
  minuto (consulta su propio registro de cambios).

## [1.2.48] — 2026-10-09

Un lote grande: una auditoría de seguridad (accesos, inyecciones, cuentas, archivos), una moderación que por fin
funciona — sus sanciones se aplican ahora a todo lo que escriben los miembros —, una nueva dirección de correo
confirmada antes de contar, la eliminación de las cuentas inactivas, y el inicio de sesión con Discord, GitHub o Google
reparado. El bot de Discord pasa a la **versión 0.2.5** (consulta su propio registro de cambios). **En un sitio con
HTTPS, todo el mundo vuelve a iniciar sesión una vez tras la actualización**: la cookie de sesión cambia de nombre.

### Seguridad

- **La búsqueda del foro, abierta a los visitantes, permitía una inyección SQL** a través de una palabra escrita entre
  comillas; la búsqueda de la mensajería privada tenía el mismo fallo. Esa palabra se limpia ahora como las demás, y la
  escapa la propia base de datos.
- **Un envío que llega de otro sitio se rechaza** (POST, PUT, DELETE…), según lo que dice el navegador
  (`Sec-Fetch-Site`, si no `Origin`): una página trampa ya no puede hacer actuar a un miembro conectado sin que lo
  sepa. Un cliente sin navegador — el bot, un servicio de pago — no se ve afectado.
- **Veinte acciones que cambiaban algo con un simple enlace** exigen ahora el token de sesión, o solo aceptan POST:
  seguir un tema, convertirlo en anuncio, marcarlo todo como leído, salir de una conversación, archivarla, eliminarla o
  restaurarla, reaccionar, marcar las notificaciones como leídas, dar su disponibilidad para un evento, abrir o cerrar
  el sitio y los registros, lanzar una copia de seguridad o una actualización, eliminar un campo de reclutamiento… Cerrar
  la sesión con un enlace sin token pide una confirmación con un botón.
- **En HTTPS, para un sitio instalado en la raíz de su dominio, la cookie de sesión lleva el prefijo `__Host-`**: un
  subdominio (una demo, un webmail) ya no la recibe y ya no puede imponer una. Un sitio instalado en una subcarpeta
  conserva una cookie sin este prefijo, que solo cambia de nombre.
- **Cuentas**:
  - el enlace «contraseña olvidada» también servía como enlace de confirmación del registro, que inicia sesión sin
    pedir nada durante dos días: cada enlace solo sirve ahora para lo suyo;
  - cambiar la contraseña anula una nueva dirección pendiente y los enlaces «contraseña olvidada»;
  - los fallos de inicio de sesión se cuentan por cuenta — se escriba como se escriba — y por red: una dirección IPv6
    cuenta por su red /64, en todos los límites de intentos del sitio;
  - activar la autenticación en dos pasos o vincular una cuenta de Discord, GitHub o Google vuelve a pedir la
    contraseña; cinco errores bloquean esta confirmación durante un cuarto de hora;
  - «contraseña olvidada» responde lo mismo esté o no registrada una dirección, y el registro solo dice que una
    dirección ya está ocupada cinco veces por hora y por red;
  - el historial de inicios de sesión anota la dirección de la conexión, ya no una cabecera que elige el navegador.
- **Doce fugas de acceso cerradas**: el historial y las revisiones de una página del wiki en borrador; las categorías
  VIP del foro, a través de la búsqueda y los perfiles; los grupos ocultos; la imagen de un álbum cerrado; los
  partidos de un tipo restringido; la presencia que los participantes habían ocultado; los comentarios y reacciones
  sobre contenido oculto; denunciar una conversación de la que no se forma parte.
- **Dos direcciones antiguas de la mensajería privada devolvían los mensajes de cualquier conversación**, incluso
  privada, a cualquiera con derecho de lectura — visitantes incluidos: retiradas. Publicar una imagen en la galería
  solo exigía el derecho de ver el álbum.
- **Los adjuntos del foro y de las conversaciones los sirve el sitio**, a quien puede leer el tema o la conversación,
  ya no el servidor web a cualquiera que tuviera su dirección. Una imagen o un PDF se muestra, todo lo demás se
  descarga; un adjunto que un navegador ejecutaría se rechaza al subirlo. En Apache, el `upload/.htaccess` incluido se
  encarga; en nginx o Caddy, copia en la configuración de tu servidor la regla que bloquea `upload/forum/` y
  `upload/talks/` (mira los `nginx.conf` y `Caddyfile` incluidos).
- **Lo que el servidor va a buscar él mismo** (relevo de imágenes, canales RSS, webhooks) se limita a direcciones
  estrictamente públicas, en los puertos 80 y 443.
- **Las claves de Stripe ya no se ven en claro** en la administración: guardadas cifradas, nunca vuelven a mostrarse.
- **Demo**: las direcciones IP de los visitantes, los nombres de host y los sitios de procedencia ya no se muestran; un
  anuncio, un enlace, un socio que lleva fuera del sitio se muestra en una página que dice adónde lleva, en lugar de
  redirigir allí.
- **Un enlace `javascript:` ya no puede mostrarse** en lugar de una dirección: la página de la que venía una sesión
  (administración), los enlaces de un partido (sitio del rival, stream, artículo). El enlace que una denuncia da hacia
  el contenido denunciado solo puede llevar a una página del sitio.

### Añadido

- **Una nueva dirección de correo espera su confirmación**: el enlace va a la nueva dirección (válido dos días), se
  avisa a la antigua, y «Mi cuenta» ofrece reenviar el enlace o cancelar. Hasta ahora, una errata dejaba al miembro sin
  nada de lo que envía el sitio.
- **Las cuentas inactivas se eliminan**: sin visitas desde hace tres años — ajustable en *Ajustes*, `0` para nunca —,
  un correo avisa al miembro un mes antes, y una visita lo anula todo. Nunca un administrador.
- **Sancionar a un miembro sin esperar una denuncia**, desde su historial de moderación, al que enlaza su página de
  administración.
- **El botón de denuncia** (la banderita «Reportar este contenido») en el libro de visitas, las imágenes de la galería,
  los anuncios clasificados, los tickets y sus comentarios.
- **«Mis sanciones»** en el área de miembros, para quien haya tenido una en el año: su tipo, su motivo, su periodo.
- **Mediación**: a partir de una denuncia, el moderador propone una conversación privada entre él, el miembro
  denunciado y quien denunció. El miembro denunciado verá allí quién lo denunció, así que solo se abre con el acuerdo
  de quien denunció, dado en una página que explica lo que acepta; si lo rechaza, su denuncia sigue siendo anónima y
  continúa como de costumbre.

### Corregido

- **Las sanciones de moderación se aplican a todo lo que escriben los miembros**: foro, mensajería privada,
  comentarios, libro de visitas, gestor de errores, reclutamiento, anuncios clasificados, galería, perfil, imágenes
  subidas en el editor de texto. Solo se comprobaba el silencio del foro y de la mensajería, y después del envío. El
  formulario deja paso a un aviso que dice qué prohíbe la sanción, hasta cuándo y por qué; la sanción «Restricción:
  enlaces» rechaza un texto que lleva un enlace, sin perderlo.
- **No se podía imponer ninguna sanción desde la pantalla de moderación**: un error interno lo impedía, así que la
  escalada automática nunca se disparaba. Revisados al mismo tiempo: los ajustes (casillas que no se podían desmarcar,
  umbral de denunciante sospechoso), los ámbitos, las duraciones (un baneo temporal sin duración se volvía
  definitivo), la validación por un moderador de rango superior, el panel de los moderadores, las denuncias
  duplicadas, las páginas a partir de la 2, la lista de IP. Un moderador ya no puede levantar una sanción que le
  afecta, ni la de un miembro de rango igual o superior, ni una impuesta por un moderador de rango superior.
- **El shadow ban funciona**: lo que escribe un miembro con shadow ban solo se muestra a él y a los moderadores, y no se
  difunde nada — ninguna notificación, nada de Discord, ningún suscriptor.
- **Un rol adicional — Moderador — quitaba derechos a un miembro**: un moderador perdía el foro y la mensajería privada.
  Un rol añade derechos, ya no quita ninguno.
- **Los correos se envían en el idioma de quien los recibe** — sanciones, respuestas en el foro, mensajería privada —,
  ya no en el del miembro que los provocó.
- **El inicio de sesión con Discord, GitHub o Google fallaba** en cuanto un servicio estaba configurado (la biblioteca
  OAuth había cambiado), y enviaba al servicio una dirección de retorno que este rechazaba. Un retorno rechazado dice
  ahora por qué en lugar de una página de error, y el avatar de Discord vuelve a importarse.
- **Libro de visitas, administración**: aprobar, rechazar o eliminar un mensaje llevaba a un error «página no
  encontrada».
- **El error de un campo de formulario se escribe bajo el campo**, en un teléfono como en un ordenador: solo se podía
  leer en una burbuja al pasar el ratón.
- **La barra superior de la administración se queda arriba** al desplazarse; en un teléfono, solo el menú y la miga de
  pan quedan fijos.
- **Treinta y cuatro errores de traducción** en doce archivos de idioma («There are 1 user», confirmaciones de
  eliminación que mostraban `< br / >`, plurales ingleses…).
- La dirección del sitio web que declaran 48 addons del núcleo es la del proyecto, ya no la del NeoFrag original. La
  página *Plantillas de correo* de la administración ya no lista las plantillas de un módulo no instalado. Un error en
  algunos formularios (límite del libro de visitas alcanzado, eliminación de la cuenta, autenticación en dos pasos,
  contraseña, creación de un miembro en la administración) ya no lleva a un error «página no encontrada».
- **Demo**: un miembro corriente no podía abrir un tema, responder en ninguna parte ni presentar ninguna candidatura.
- **La exportación «Mis datos» incluye las denuncias que hizo el miembro**: faltaban en el archivo.

### Cambiado

- **Un widget ligado a un módulo depende ahora de él** (21 widgets): sin ese módulo, o si está desactivado, el widget ya
  no se muestra y el editor en vivo ya no lo ofrece; «Añadir» rechaza un archivo de widget sin su módulo.
- **La documentación del producto ya no se incluye** en el wiki de un sitio nuevo, que llega vacío; vive en el sitio del
  proyecto. Una instalación nueva ya no recibe los ajustes de temas que no tiene.

### Eliminado

- Las antiguas direcciones `ajax/talks` de la mensajería privada, y su antigua denuncia de mensajes, que solo escribía
  en el registro de auditoría, con su página de administración «Reportes de mensajes»: un mensaje se denuncia ahora a
  través de la moderación.

## [1.2.47] — 2026-10-09

La barra de objetos de Blockcraft, en los teléfonos.

### Corregido

- **La barra de objetos de Blockcraft ocupa todo el ancho del teléfono**: sus casillas se reparten la pantalla por
  igual y cada nombre cabe en la suya — en dos líneas para «Mi espacio», con guion para una palabra alemana o portuguesa
  demasiado larga («Neuig-keiten»). Mantenía el mismo ancho fuera cual fuera la pantalla (siete casillas de 47 px): un
  hueco vacío a la derecha, casillas estrechas y «Noticias» saliéndose de la suya. El marco blanco de la casilla
  elegida ya no toca su nombre.

## [1.2.46] — 2026-10-08

Las barras de la parte superior de la página ya no tapan nada, la campana llega a Nebula, y la página *Temas & Addons*
vuelve a poder reinstalar un tema.

### Corregido

- **Lo que se pega a la parte superior de la pantalla se coloca ahora debajo de las barras de arriba de la página** — la
  barra de la demo, la de mantenimiento y la de la vista previa de los permisos. En cuanto se desplazaba la página, la
  mitad de las cabeceras de Extend, Chronique, Pulse y Nebula se deslizaba bajo la barra de la demo; el final de la
  barra lateral de Forge y de la barra lateral de la administración se salía de la pantalla; en los teléfonos, el
  escudo y la cuenta de Forge quedaban ocultos. La plantilla principal apila ahora las barras y publica su altura; los
  temas, la administración y el editor en vivo se alinean debajo, y las notificaciones en pantalla aparecen debajo.
- **Las columnas fijas de los módulos se alinean debajo de la cabecera del tema**, sea cual sea: el menú del área de
  miembros (a 16 px del borde superior, se deslizaba bajo una cabecera fija de 60 a 70 px), el índice del wiki, el
  índice y el recuadro lateral de un artículo. Una columna más alta que el espacio que le queda ya no se pega: su parte
  inferior se quedaba fuera de la pantalla hasta el final de la página — el menú del área de miembros de Extend, en la
  pantalla de un portátil.
- **Un enlace a un ancla se detiene debajo de la cabecera fija** — un mensaje del foro, una sección del wiki o de un
  artículo — en lugar de dejar su título debajo: cada tema fijaba este desplazamiento a mano, sin contar las barras.
- **«Reinstalar predeterminado» y «Eliminar» un tema funcionan desde la página «Temas & Addons»**, y el orden de los
  idiomas y de los autenticadores se guarda: la confirmación se rechazaba, «token de seguridad no válido».
- **La barra de la demo es legible**: su texto, blanco sobre verde (contraste de 2,4:1), pasa a oscuro.
- **En los teléfonos, los botones de una cabecera de la administración pasan debajo del título**: en la página de los
  artículos, «Categorías», «Series» y «Nuevo artículo» tapaban «4 publicados», y el último se salía de la pantalla.
- **Un sitio abierto con otro nombre que el de su configuración** (con o sin `www.`, por ejemplo) muestra las imágenes
  escritas con la dirección configurada: la política de seguridad del navegador las bloqueaba, porque a sus ojos venían
  de otro sitio; ahora pasan por el relevo de imágenes del sitio.
- **Para los colaboradores**: la herramienta `check-mise-en-page` ya no señala falsos solapamientos dentro de un bloque
  plegado (`<details>`), que Chrome mantiene en la página sin mostrarlo — 48 falsas alertas en la página *Registro de
  errores* de la Monitorización —, ni en los mensajes de error que esa página muestra a propósito.

### Añadido

- **La campana de notificaciones en Nebula**, como en los demás temas: el área de miembros la anuncia «en la parte
  superior de cada página», y Nebula no tenía.
- **Para los autores de temas**: la cabecera fija de un tema lleva `data-nf-entete`, y lo que se pega a la parte
  superior de la pantalla se alinea con `--nf-haut` (y una columna con `--nf-entete`); una comprobación nueva,
  `check-colles`, lo verifica — pasada sobre la 1.2.45, encontró veinte reglas CSS defectuosas (*Crear un tema*).

### Eliminado

- **Reglas de estilo sin uso en la hoja de estilos de Nebula** (una barra, botones y un pie de página heredados de otro
  tema): ninguna plantilla de Nebula las usaba.

## [1.2.45] — 2026-10-08

La campana de notificaciones, revisada en todos los temas que la muestran, y el inicio de sesión en dos pasos de una
sola vez.

### Corregido

- **Con la autenticación en dos pasos, el código se pide justo después de la contraseña**: la ventana de inicio de
  sesión deja paso a la del código, sin recargar la página. Se cerraba, y había que volver a pulsar «Iniciar sesión»
  para ver el código; desde el formulario del área de miembros, la ventana del código se abre al recargar.
- **La pastilla de la campana se queda en su esquina** en todos los temas que muestran la campana: la hoja de estilos
  del módulo, cargada después de la del tema, la devolvía al flujo, y en Extend y Forge caía en medio del botón, sobre
  la campana.
- **La campana se queda en el centro de su botón** cuando lleva una pastilla: un icono que ya no estaba solo en su
  enlace recibía un margen derecho y se desplazaba tres píxeles.
- **«Notificaciones» y «Marcar todo como leído» ya no se tocan** en la parte superior de la lista: conserva su ancho
  cuando el tema estrecha sus menús, y los dos textos guardan una separación, pasando de línea si hace falta.
- **Las fechas se muestran en el formato del idioma y en la zona horaria del visitante** en la lista de la campana, el
  historial de revisiones y la burbuja de una denuncia, que mostraban la marca de tiempo bruta de la base de datos.

## [1.2.44] — 2026-10-08

Una corrección de la 1.2.43.

### Corregido

- **El filtro de terceros funciona con PHP-FPM y Apache.** Se ejecuta al final de la petición, cuando la carpeta actual
  ya no es la del sitio: ya no encontraba la clave del relevo de imágenes, y la página salía sin filtrar. Las imágenes
  de otros sitios, que la política de seguridad rechaza ahora, no se mostraban (los avatares del widget de Discord), y
  un vídeo incrustado se habría cargado sin su aviso. El defecto no aparecía con el servidor integrado de PHP, que no
  cambia de carpeta al final de la petición.
- **El registro de errores de PHP también guarda los errores del final de la petición**, errores fatales incluidos: su
  ruta era relativa, y se resolvía entonces desde la raíz del sistema de archivos (`/`).

## [1.2.43] — 2026-10-08

Privacidad: ya no se envía a ningún visitante a un tercero sin su consentimiento.

### Añadido

- **Una verdadera elección sobre los servicios de otras empresas.** El banner de cookies solo controlaba Google
  Analytics, mientras que los vídeos, el widget de Discord y los captchas se cargaban respondiera lo que respondiera el
  visitante. La ventana **«Gestionar mis cookies»**, abierta desde el pie de cada página, muestra las cookies propias
  del sitio y luego cada servicio, para aceptarlo o rechazarlo uno a uno. En lugar de un vídeo de YouTube, Twitch,
  Vimeo o Dailymotion, de una pista de Spotify o SoundCloud, o del widget de Discord, un aviso dice quién recibiría la
  dirección IP del visitante, con «Mostrar» y «Permitir siempre…». El banner solo se abre si el sitio tiene de verdad
  algo que preguntar de entrada (medición de audiencia, un captcha gestionado por un tercero); «Aceptar todo» y
  «Rechazar todo» tienen el mismo peso. La elección se conserva seis meses, y su constancia, sin la dirección IP,
  trece meses.
- **Ajustes → Privacidad**: las páginas de aviso legal y de política de privacidad, elegidas entre las páginas
  publicadas. El pie de página de cada tema enlaza a ellas, junto a «Gestionar mis cookies».

### Cambiado

- **Las fuentes las sirve el sitio**: los temas y el ajuste «Fuente del sitio» las tomaban de Google Fonts, y el
  navegador de cada visitante enviaba su dirección IP a Google, en cada página.
- **Las imágenes de otros sitios las sirve el sitio** — un avatar de Discord, Steam o Twitch, un GIF en una
  conversación, una imagen pegada en un artículo, el foro o un anuncio clasificado, un banner publicitario: el sitio las
  va a buscar él mismo y las guarda siete días, y el navegador ya no contacta con su servidor. Las teselas del mapa de
  lugares también.
- **La política de seguridad solo acepta ya el propio sitio** para las imágenes y las conexiones de scripts (salvo
  Google Analytics, si está configurado). Deja ahora mostrarse los reproductores de servicios conocidos (YouTube,
  Twitch, Vimeo, Dailymotion, Spotify, SoundCloud, Discord), que antes bloqueaba: un vídeo en un artículo no se veía. Un
  script de otro sitio pegado en un widget «Código HTML» o en un anuncio ya no se ejecuta: solo pasan Google Analytics y
  el captcha elegido.
- **Mientras el visitante no acepte un captcha de un tercero, ALTCHA lo sustituye**, servido por el sitio: nadie tiene
  que entregar sus datos a Google, hCaptcha o Cloudflare para escribir en el sitio o registrarse.
- **Se acabó el píxel de seguimiento en el boletín**, y la tasa de apertura: un rastreador que la suscripción no había
  pedido (recomendación de la CNIL del 12 de marzo de 2026 sobre los píxeles de seguimiento en los correos).
- **Plazos de conservación**: el registro de auditoría un año, los contadores antiabuso un día, las suscripciones al
  boletín nunca confirmadas treinta días, la cola de envío del boletín noventa días; para una denuncia tratada hace más
  de un año, se borran la dirección IP de su autor y la copia del contenido. La cookie de sesión termina al cerrar el
  navegador, salvo con «Recordarme» (un año), que ya no viene marcado por defecto.
- **Se acabaron los mapas de Google en los textos**: ya no se acepta un mapa de Google Maps incrustado en un texto. El
  mapa del producto es el módulo «Mapa de lugares», basado en OpenStreetMap.
- El enlace «Desarrollado por NeoFrag Reborn» de los temas, la palabra clave de copyright `{neofrag}` y los enlaces del
  pie de la administración llevan al sitio del proyecto; llevaban al sitio del NeoFrag original.

### Corregido

- **Ya no se envía ninguna dirección IP a neofr.ag**: se retira la bandera del país junto a las direcciones de las
  sesiones; para obtenerla, el navegador del administrador enviaba todas esas direcciones al sitio del NeoFrag
  original.
- **Eliminar una cuenta como administrador borra de verdad** el perfil, las cuentas vinculadas, el historial de inicios
  de sesión y las notificaciones, como la eliminación que pide un miembro él mismo; antes solo cerraba la cuenta.
- **El archivo «Mis datos» se descarga ahora**: tres de sus listas (temas del foro, comentarios, mensajes de las
  conversaciones) leían columnas que no existen, y el archivo fallaba.
- Las burbujas de la lista de sesiones y de la galería se muestran ahora: llevaban un atributo de Bootstrap 4, que
  Bootstrap 5 ignora, y se quedaban vacías.
- La dirección del avatar del widget de Steam se escribía tal cual en la página; ahora se escapa, como el resto del
  widget.
- La burbuja «Abrir el canal» y el botón «Cerrar» del reproductor del widget de Twitch, que se habían quedado en
  francés, están traducidos.

## [1.2.42] — 2026-10-07

Un tema rediseñado: **Extend**, el sitio como lanzador de un juego en línea.

### Añadido

- **Extend 2.0.0 «Launcher»** — una disposición totalmente nueva, elegida entre tres maquetas. Una barra de pestañas
  arriba, en mayúsculas estrechas, la pestaña abierta iluminada por una línea azul; en la página de inicio, un **gran
  escaparate** (el carrusel, su título en letras grandes y su botón «Descubrir»; sin carrusel, el nombre del sitio sobre
  la imagen del tema), luego los próximos eventos en tarjetas, las noticias en miniaturas, los últimos resultados; en
  otras páginas, el título de la página sobre esa imagen. A la derecha, en cada página, un **panel siempre abierto**:
  quién está en línea, con avatares, y luego el canal de chat (y, en las páginas del foro, sus estadísticas). Abajo,
  una **barra de estado**: el sitio de los widgets «Servidor de juego», «Servidor TeamSpeak 3» y «Servidor Discord», el
  idioma, luego «Desarrollado por NeoFrag Reborn», el copyright y los enlaces legales. El foro como biblioteca, el área
  de miembros como ficha de jugador (banner, avatar con aro, nivel de gamificación en la barra). En los teléfonos, las
  pestañas pasan a la parte inferior de la pantalla, con «En línea», que abre el panel como un cajón, y «Más» a partir
  de cuatro secciones. Azul acero sobre azul marino, noche por defecto, día a petición; títulos en Saira Condensed,
  texto en Albert Sans. El logo, la imagen del escaparate, el fondo y los colores son ajustables. Basado en el tema de
  Chewbaka, cuyo nombre y licencia conserva.
- **«¿Quién está conectado? (lista)»**, una nueva visualización del widget Miembros: las tres cifras (administradores,
  miembros, visitantes) y luego las propias personas con su avatar y su última actividad.
- **«Un canal público: sus últimos mensajes»**, una nueva visualización del widget Conversaciones: los cuatro últimos
  mensajes de un canal abierto a todos los miembros, y el enlace para escribir en él. Un visitante no lee los mensajes:
  se le invita a iniciar sesión, como en la propia mensajería; el canal del equipo nunca se muestra.

### Corregido

- **Las entradas relacionadas de un artículo del blog ya no desbordan en los teléfonos**: el recuadro de una entrada sin
  imagen tomaba su ancho de la altura de su fila y se salía unos píxeles de la pantalla (a 414 px, en todos los temas).
- **Blockcraft, revisado en la demo publicada**: en un teléfono, el banner de la página muestra todo el paisaje
  (recortado, solo mostraba troncos de árboles), sin el sol ni la luna, sobre los que pasaba un título largo; la demo
  pone una dirección de servidor de ejemplo (`play.example.org`), para que se vea el botón «Copiar la dirección».

## [1.2.41] — 2026-10-07

Un tema rediseñado: **Blockcraft**, el sitio de un servidor de un juego de bloques.

### Añadido

- **Blockcraft 2.0.0 «Spawn + Inventario»** — una disposición nueva, elegida entre cuatro maquetas. Arriba, un cielo
  sobre un paisaje de bloques (colinas, árboles, piedra), el sol y nubes que pasan, la luna y las estrellas de noche; en
  la página de inicio, el nombre del servidor en letras grandes y **su dirección, copiada con un clic** (ajuste
  «Dirección del servidor»), y el Discord cuando está configurado. La navegación es una **barra de objetos**: cada
  sección en su casilla, con el objeto que la representa (el libro para las noticias, el mapa para el foro…); en un
  teléfono, se pega a la parte inferior de la pantalla, al alcance del pulgar. Bloques de esquinas rectas con sombras
  duras, el **foro como cofres** (una tapa de tablones por categoría, cada foro en su casilla, una casilla encantada que
  brilla cuando hay algo nuevo), el **área de miembros como pantalla del personaje**, un pie de página de piedra.
  Títulos en Jersey 10, texto en Rubik, cifras en VT323; «pradera» de día, «cielo estrellado» de noche. Cada píxel está
  dibujado para el tema. De día, los textos del cielo son oscuros: en blanco, solo habrían sido legibles por su sombra.

### Corregido

- **La columna lateral de Pulse, Chronique y Blockcraft ya no deforma los widgets que se colocan en ella**: su
  disposición llegaba también al interior de los widgets, y «¿Quién está en línea?» alejaba allí sus cifras de sus
  etiquetas.
- **La pestaña elegida del perfil de un miembro es legible en Pulse, Chronique y Granite**: estos temas cambiaban su
  texto sin cambiar su fondo, que el módulo pinta con el color de acento — texto oscuro sobre el acento.
- **El icono de un grupo ya no toca su nombre** (por ejemplo «Equipo principal», en el perfil y el área de miembros):
  la base común de los temas no dejaba espacio entre el icono y el nombre — en los seis temas que la cargan.
- **Los comentarios de un evento conservan su icono** en Pulse, Chronique y Granite: estos temas quitaban el icono de
  todos los enlaces del pie de una tarjeta, y el enlace solo decía «0 →». Ahora solo los pies de los widgets («Ver el
  calendario →») pierden su icono.
- **La pastilla elegida del módulo de mensajería es legible** («Todos», «Archivos»…) en Pulse y Chronique: la base
  común daba a su texto el color de los enlaces y a su icono el color de acento, sobre un fondo del color de acento
  (1,1:1).
- **Granite**: los botones del pie de una tarjeta («Publicar una imagen», «Ver mi candidatura», la paginación de las
  sesiones) tomaban el rojo de los enlaces sobre la tinta (2:1); «Levantar esta sanción» solo tenía 3,7:1; en un
  teléfono, la capitular de la portada se deslizaba sobre la línea «Por … el …» de debajo; los botones pequeños miden
  24 px de alto.
- **Pulse, de noche**: el contador del menú del área de miembros es legible: blanco sobre el ladrillo nocturno más
  claro, solo tenía 3,9:1; ahora conserva el ladrillo del día.
- **El menú del área de miembros, en un teléfono, se ajusta al inicio de una pestaña**: centrado al píxel, dejaba la
  pestaña anterior cortada al principio de su nombre («…ficaciones»).
- **Activar el «Modo de depuración» desde la Monitorización ya no hace fallar su página**: la respuesta fallaba en
  cuanto había una notificación esperando («Cannot use object of type stdClass as array»).
- **Las guías cuentan los temas del paquete**: «Conceptos» anunciaba cuatro (faltaban Chronique y Pulse), y «Crear un
  tema» nombraba cuatro temas sobre la base común en lugar de seis.

### Cambiado

- **`check-mise-en-page` ya no da falsas alarmas**: el texto del banner de cookies o de una barra pegada a la parte
  inferior de la pantalla pasa a propósito sobre un gráfico (solo una misma capa crea un conflicto); la parte oculta de
  un texto — el código de un bloque con desplazamiento, una descripción recortada — ya no «se solapa» con la columna
  siguiente; una pestaña de una banda con desplazamiento ya no está «perdida más allá del borde izquierdo». Menos
  defectos falsos, y los verdaderos siguen saliendo (comprobado en una página de prueba trampeada, con la sonda antigua
  y la nueva).

## [1.2.40] — 2026-10-07

La indexación por los buscadores, revisada tras las alertas de la Search Console en el sitio oficial: Google no indexaba
los temas del foro ni las páginas del wiki, y se le ofrecían páginas vacías. Y el mensaje de bienvenida, que ya no se
enviaba.

### Corregido

- **El contenido sin idioma ya no se presenta como duplicado**: un tema del foro, una página del wiki, un ticket, un
  evento, un anuncio clasificado, una receta, una encuesta, una oferta de empleo, un premio, una campaña de donaciones —
  escritos una sola vez — respondían bajo cada idioma del sitio, con solo los menús traducidos, y cada dirección se
  declaraba canónica. Google elegía otra y no indexaba ninguna («Duplicada: Google ha elegido una versión canónica
  diferente a la del usuario»: en el sitio oficial, en seis idiomas, 59 contenidos listados seis veces, 354 de las 580
  direcciones de su mapa del sitio). Su canónica está ahora en el primer idioma del sitio, el único anunciado en
  `hreflang` y en el mapa del sitio. Lo mismo para las páginas hechas de ese contenido: la FAQ, el glosario, las citas,
  los enlaces, las descargas, la tienda y la lista de eventos.
- **Ya no se ofrecen páginas vacías a los buscadores**: la radio web sin emisión ni programa, el libro de visitas sin
  mensajes y la página de donaciones sin campaña estaban en el mapa del sitio, en todos los idiomas — «soft 404» para
  Google. Se quedan fuera mientras estén vacías, y declaran `noindex`.
- **`/fr/forum/` redirige ahora de forma permanente a `/fr/forum`**: la redirección de una dirección terminada en barra,
  o con una barra doble, era temporal (302) — y la primera perdía todo lo que iba tras el `?`; ahora es permanente (301)
  y conserva el final de la dirección.
- **El mensaje de bienvenida vuelve a enviarse**: el módulo Miembros no encontraba el módulo de mensajería, y un miembro
  nuevo no recibía nada. Un módulo que cargaba otro (`$this->module(…)`) obtenía siempre «nada» — el mismo defecto
  privaba a los webhooks de los comentarios del título y de la dirección del contenido comentado.
- **Las copias de seguridad de las actualizaciones ya no se acumulan**: el sitio conserva siempre las cinco más
  recientes y retira las demás a los treinta días, pero unas actualizaciones muy seguidas las dejaban pasar todas — el
  sitio oficial llevaba cuarenta, 663 MB. Ahora nunca quedan más de diez, sea cual sea su antigüedad.

### Cambiado

- **`check-seo` compara los idiomas entre sí**: toma una misma ruta en cada mapa del sitio y señala el mismo texto
  servido bajo varios idiomas, cada uno canónico. Declaraba sano el sitio oficial; ahora encuentra allí esos duplicados.
  Un módulo puede declarar su contenido sin idioma: una entrada del mapa del sitio marcada `'sans_langue' => TRUE` solo
  aparece en el mapa del primer idioma, y `nf_seo_sans_langue()` pone la canónica de la página (guía «Crear un
  módulo»).

## [1.2.39] — 2026-10-06

Pulse, revisado en la demo publicada: el foro en un teléfono ya no pega sus botones, en todos los temas; y una entrada
del blog que aún se salía del borde de la pantalla en los teléfonos.

### Corregido

- **El foro en un teléfono ya no pega sus botones**: la barra de un tema (Volver, Responder y las herramientas de
  moderación) y la barra de un foro dejan un espacio entre sus botones y entre sus filas; los botones de un mensaje
  pasan encima de su fecha, que ya no se parte en tres líneas; el título de un tema ya no toca el botón «Seguir». En
  todos los temas.
- **Una entrada del blog ya no desborda en un teléfono**: un fragmento largo de código (una ruta de archivo, por
  ejemplo) se parte, y una tabla que sigue siendo demasiado ancha se desplaza dentro de su columna (la entrada de la
  1.2.0 se salía del borde de la pantalla).

## [1.2.38] — 2026-10-06

Un tema nuevo: **Pulse**, la casa común de las asociaciones, los clubes y las comunidades, con tres novedades que sirven
a todos los temas — el sitio en cifras, la próxima cita y las últimas fotos.

### Añadido

- **El tema Pulse.** Una barra clara que se queda arriba, con las secciones como pastillas, la cuenta y la llamada
  «Afiliarse» siempre a la vista (un botón «Menú» en un teléfono); la página de inicio es un mosaico de baldosas — el
  nombre y el lema del sitio sobre la imagen de inicio, la próxima cita, el sitio en cifras, las noticias, la agenda, la
  encuesta, las conversaciones, los documentos, las fotos, los socios — que el editor en vivo reordena baldosa a
  baldosa (clara, sol o pizarra). El foro se lee como tarjetas, con sus estadísticas y su actividad al lado; el pie de
  página oscuro lleva el mapa del sitio y las redes sociales. Claro de día, «pizarra» de noche a elección del
  visitante; títulos en Bricolage Grotesque, texto en Manrope. Ajustes: colores, imagen de inicio, logo, y la dirección
  del botón «Afiliarse» (una página de afiliación, un formulario en otra plataforma o el registro del sitio).
- **El widget «El sitio en cifras»**: hasta cuatro números, elegidos entre los miembros, las conversaciones, los
  mensajes del foro, las noticias, los próximos eventos y las fotos — contando solo lo que el visitante tiene derecho a
  ver (los foros restringidos y los álbumes de grupo quedan fuera de la cuenta). En Pulse los números cuentan hacia
  arriba cuando aparece la baldosa, salvo si el visitante pidió menos movimiento.
- **El calendario muestra también «La próxima cita»**: su fecha en letra grande, su título, dentro de cuántos días, a
  qué hora (o «Todo el día») y dónde, el comienzo de su descripción, y un botón para abrirla.
- **La galería muestra también «Las últimas fotos»**, en cuadrícula — solo de los álbumes que el visitante puede ver.

### Corregido

- **El estilo «Título de color» de Chronique y Granite**: el título del panel tenía el color de su fondo (verde azulado
  sobre verde azulado, rojo sobre rojo), así que era invisible. Ahora tiene color, sobre el papel, como estos temas
  pretendían — y la vista previa del estilo en el editor en vivo lo muestra así.
- **El estilo «Widget de color» de Granite**: el texto, las etiquetas de formulario y el botón de un widget con este
  estilo podían volverse blancos sobre el papel (o tinta sobre tinta, de noche). Conservan los colores del periódico.
  La comprobación de contraste puede medir ahora un widget en cada tema y cada estilo de panel.
- **La miga de pan encima de una noticia**: todos los temas la preveían, nunca aparecía — la regla que la colocaba
  apuntaba a una dirección que no existe. Aparece ahora en las noticias y en la lista de noticias, en todos los temas.
- **Dos recuadros uno al lado del otro siguen alineados**: bajo una noticia, «Otras noticias de este autor» quedaba 14 a
  22 px más abajo que «Acerca del autor» (igual en la página de un evento y en la bandeja de entrada).
- **Chronique: un artículo conserva sus márgenes** dentro de su tarjeta; el título y el texto tocaban el borde.
- **El texto sobre un degradado es legible**: el botón «Unirse al servidor» del widget de Discord, las iniciales del
  escudo de Forge, el título y el lema del banner de Blockcraft de día. La comprobación de contraste mide ahora el texto
  sobre un degradado, y como miembro conectado (área de miembros incluida).
- **Una entrada del Blog con un solo título ya no aparece encogida**: sin índice, la página conservaba su columna, el
  texto se apretaba en 200 px y el recuadro lateral (autor, compartir) ocupaba su lugar — las notas de versión de la
  1.2.32, la 1.2.33 y la 1.2.37 en el sitio oficial. Gracias a Blober por señalarlo.
- **`admin.php` y `ajax.php` responden «página no encontrada»** en lugar de con un error del servidor (y dos líneas de
  registro cada vez que un robot sondea esas direcciones).
- **La cronología de la temporada**: «En 5 días» ya no se parte en dos líneas en un teléfono.

## [1.2.37] — 2026-10-06

Chronique, revisado en la demo tal como se ve — como visitante y conectado, de día, de noche, en un teléfono —, y una
demo que vive en el presente.

### Corregido

- **El área de miembros de Chronique.** En su panel, «¿Olvidó su contraseña?» quedaba encima de «Iniciar sesión» en
  cuanto la columna los ponía uno debajo del otro; una vez conectado, los iconos del menú seguían en gris oscuro sobre
  la pizarra y «Cerrar sesión» caía en un rectángulo blanco (negro de noche). Ahora son enlaces blancos, un único botón
  relleno y un espacio en ambos sentidos — también con «Crear una cuenta», cuando el registro está abierto.
- **Chronique en un teléfono conserva el inicio de sesión en la cabecera**, como icono: la palabra «Iniciar sesión»
  desaparecía, y había que desplazarse hasta el final de la página para conectarse.
- **Los botones del panel de inicio de sesión conservan su espacio cuando pasan de línea**, en los cinco temas que
  comparten la base común (Blockcraft, Chronique, Extend, Forge, Granite).
- **Extend: los iconos de los campos del panel de color se ven** (nombre de usuario y contraseña): eran blancos sobre
  un recuadro claro.
- **La lista de miembros, de noche**: una red social que un miembro no había rellenado se convertía en un punto gris
  pegado al siguiente. Es un icono atenuado sin fondo, y los botones de las redes rellenadas tienen un espacio real — en
  una línea en una tarjeta estrecha.
- **La demo vive en el presente.** Restablecida cada cuarto de hora, seguía mostrando el contenido del día en que se
  tomó su instantánea: eventos y partidos «próximos» que habían pasado hacía semanas, un calendario vacío, una
  cronología de la temporada que se habría vaciado. Cada restablecimiento adelanta ahora las fechas del contenido el
  tiempo transcurrido (los intervalos entre ellas no cambian); los miembros conservan las suyas.

## [1.2.36] — 2026-10-06

Un tema nuevo: **Chronique**, el cuaderno de la temporada de las asociaciones y los clubes, con dos funciones nuevas que
sirven a todos los temas — la cronología de la temporada y la semana del calendario.

### Añadido

- **El tema Chronique.** El sitio como cuaderno de la temporada: una cabecera discreta que se queda arriba, con una
  línea fina que sigue tu lectura, un botón «Índice» que abre todo el sitio a pantalla completa (cada sección numerada)
  y la llamada «Afiliarse» para los visitantes; la página de inicio se abre con un gran titular — la temporada, el
  nombre del sitio, su eslogan — y la semana en curso; luego la cronología cuenta la temporada mes a mes, junto a una
  columna que se queda en su sitio (el área de miembros, los documentos, los socios). El foro se lee como capítulos
  numerados, el área de miembros como un cuaderno, y el pie de página cierra el libro. «Papel» de día, «luz de lámpara»
  de noche; títulos en Fraunces, texto en Work Sans, fechas en IBM Plex Mono. Ajustes: colores, imagen de apertura,
  logo, y la dirección del botón «Afiliarse» (una página de afiliación, un formulario en otra plataforma o el registro
  del sitio).
- **El widget «Cronología de la temporada»**: lo que vive el sitio, mes a mes, en una línea de tiempo — los próximos
  eventos del calendario (su punto late), las noticias, las conversaciones del foro y los álbumes de fotos, cada uno si
  su módulo está instalado, y solo lo que el visitante puede leer (los foros restringidos y los álbumes de grupo quedan
  ocultos). El número de entradas y los meses pasados se pueden ajustar.
- **El calendario muestra también «Esta semana»**: los siete días de la semana en curso, los que tienen un evento
  marcados, hoy rodeado con un círculo, y la próxima cita — en la zona horaria de quien mira.

### Corregido

- **En modo de día, los componentes de Bootstrap toman los colores del tema**: un acordeón de la FAQ, una casilla, una
  tabla o un menú desplegable se quedaban blancos, con texto gris oscuro, sobre el papel de Granite. Comprobado elemento
  a elemento en Forge, Granite, Blockcraft y Extend: solo cambian sus colores, y el modo de noche se queda como estaba.
- **«Modo día» y «Modo noche» están traducidos**: la burbuja y el nombre, leído por los lectores de pantalla, del botón
  que alterna entre ellos se quedaban en francés en un sitio en otro idioma (Forge, Granite, Blockcraft, Extend). La
  comprobación de textos escritos en el código conoce ahora las palabras «jour» y «nuit».
- **La página de personalización de un tema ya no escribe una advertencia en el registro** cuando sus ajustes aún no
  existen (un tema registrado sin que su instalación haya creado sus ajustes): la posición de una imagen vuelve a su
  valor por defecto.

## [1.2.35] — 2026-10-06

El segundo tema rehecho desde cero: **Granite 2.0.0 «Gazette»**, el periódico de las asociaciones y los clubes.

### Cambiado

- **Granite 2.0.0 «Gazette»: una disposición nueva, no un cambio de pintura.** El sitio se convierte en el periódico del
  club: la fecha del día y la cuenta en una línea arriba del todo, el nombre del sitio impreso muy grande (el logo
  encima, si lo hay), una doble raya, luego las secciones en versalitas entre dos rayas. En la página de inicio, una
  línea «Breves» hace desfilar los últimos temas del foro; se detiene al pasar el ratón, con el teclado y con su botón
  de pausa, y se queda quieta cuando el visitante pide menos movimiento. La portada se abre con la noticia principal en
  letra grande, con su capitular adornada, y luego las siguientes en columnas separadas por rayas; la columna derecha
  lleva el calendario, la encuesta y quién está en línea. Los bloques se convierten en paneles sin recuadro, titulados
  en versalitas sobre una raya; un artículo se lee como el cuerpo de texto de un periódico; el foro toma el aspecto de
  una página de cartas de los lectores; el área de miembros, el de un carné de socio sellado con el primer grupo del
  miembro; el pie de página se convierte en una mancheta (los socios, y luego quién publica). Una barra de lectura fina
  sigue la página. El día es papel, la noche una página «entintada», a elección del visitante; títulos en Playfair
  Display, texto en Source Serif. En los teléfonos, las secciones se desplazan de lado y todo pasa a una sola columna.
- **Las zonas de Granite llevan el nombre de su lugar**: «Secciones», «Breves», «Contenido», «Post-contenido», «Pie de
  página».
- **Los ajustes de Granite**: la imagen del banner pasa a ser la imagen del título, y el color del título, una vez
  cambiado, pone una banda detrás del nombre, como la cabecera de un diario; el logo se usa por fin (encima del nombre);
  un ajuste «Capitulares» sustituye al de la barra superior fija, que ya no tiene sentido.

### Corregido

- **Se acabó el azul de Bootstrap entre los colores de un tema.** La barra de una encuesta, la casilla marcada y el
  botón de opción elegido (también en la administración), la página activa, la entrada pulsada de un menú, la flecha de
  un acordeón, el halo del foco del teclado y los estados de un botón principal conservaban el azul por defecto de
  Bootstrap. Toman el color de acento del tema, en todos los temas; una comprobación nueva, `check-bleu-bootstrap`,
  busca ese azul en las páginas servidas, en reposo y con el foco, de día y de noche.
- **Las secciones que ponen Forge y Granite están traducidas**: «Matchs», «Nous rejoindre», «À la une», «Agenda» y
  «Documents» se quedaban en francés en un sitio en otro idioma. La comprobación de traducciones verifica ahora cada
  título de menú que pone un tema.
- **El editor en vivo muestra el sitio con sus propias fuentes**: imponía las suyas al contenido de las zonas que
  enmarca.
- **La comprobación de contraste mide también el texto sobre un grano** (papel, una trama): lo tomaba por una imagen y
  renunciaba a medirlo.

## [1.2.34] — 2026-10-06

El primer tema rehecho desde cero: **Forge 2.0.0 «Coulée»**, para los clanes competitivos.

### Cambiado

- **Forge 2.0.0 «Coulée»: una disposición nueva, no un cambio de pintura.** La navegación deja la parte superior de la
  página por una barra de acero en el lateral, con el logo del sitio arriba y la cuenta abajo (avatar, notificaciones,
  modo día, menú del área de miembros); en los teléfonos, el logo y la cuenta pasan a una barra superior y la
  navegación a una barra de pestañas en la parte inferior de la pantalla, y a partir de cinco entradas las últimas van a
  «Más». La página de inicio se abre con un hogar de lava del que suben brasas: el carrusel, su título fundido en metal,
  y la placa de los últimos resultados. Debajo, un tablero: las noticias (la primera destacada) y, al lado, los próximos
  partidos, el palmarés y quién está en línea. Los bloques son placas de esquinas cortadas cuyo borde brilla al pasar el
  ratón; el foro pone cada foro en un cajón de acero con un indicador de calor para su actividad; el área de miembros y
  el perfil público reciben la placa de identidad y pestañas biseladas; el pie de página está remachado y lleva los
  socios. El modo día aclara el contenido, mientras la barra y el hogar siguen siendo acero y fuego. Todo movimiento se
  detiene cuando el visitante pide menos movimiento. Los bloques de los partidos, del palmarés y de los socios solo se
  colocan cuando sus módulos están instalados.
- **Las zonas de Forge llevan el nombre de su lugar**: «Barra de navegación lateral», «Parte superior de la página»,
  «Contenido», «Post-contenido», «Pie de página».
- **Los ajustes de Forge**: el logo se usa por fin (el emblema de la barra; sin logo, las iniciales del sitio); la imagen
  del banner pasa a ser la imagen del hogar; un ajuste «Brasas del hogar» (ninguna, suaves, vivas) sustituye al de la
  barra superior fija, que ya no tiene sentido.
- **El widget del palmarés** muestra el puesto del podio («1.º», «2.º», «3.º») y el nombre de la competición en todo el
  ancho disponible, con el lugar y la plataforma debajo; la línea empezaba por la plataforma y cortaba el nombre a los
  veinte caracteres.
- **Las imágenes del carrusel de la demo ya no llevan su título**: el carrusel ya lo escribe encima, y aparecía dos
  veces, atravesando la leyenda en los teléfonos.

### Corregido

- **Un ajuste de un tema se ve al instante** (color de acento, imágenes de fondo y de banner…): la dirección de la hoja
  de estilos solo seguía la fecha del archivo, y el navegador conservaba la hoja antigua tras un cambio.
- **La página de personalización de los temas Forge, Granite, Blockcraft y Extend** lleva como título el nombre del
  tema, en lugar de un «Dashboard» sin traducir mostrado en cada pestaña.
- **En inglés, el palmarés ya no dice «2th» ni «3th»**: los puestos del podio tienen sus propias palabras, los demás
  puestos se leen «#4».
- **Reinstalar un tema ya no escribe una advertencia en el registro** en modo de depuración (una advertencia
  «Deprecated» de PHP 8.2).

## [1.2.33] — 2026-10-06

Una versión de corrección: la CI pública del repositorio `neofrag`, rota en la 1.2.32 por un problema de herramientas,
está reparada. Nada cambia para los sitios.

### Corregido

- **Las comprobaciones automáticas (CI) del repositorio `neofrag` vuelven a pasar.** Desde la 1.2.32, el paso que busca
  la versión correspondiente del repositorio `extensions` podía fallar: dejaba de leer la lista de versiones en cuanto
  encontraba la buena, y `git`, cortado a mitad de escritura, fallaba ahora que la lista es larga. Ahora lee la lista
  entera; misma corrección en la CI de `extensions`.
- **La comprobación de enlaces de los documentos ya no lee los de las bibliotecas incluidas** (la licencia de TinyMCE):
  no se pueden corregir por nuestra parte, y gnu.org cerraba la conexión con su antigua dirección.

## [1.2.32] — 2026-10-06

Empieza la renovación de los temas: Forge, Granite, Blockcraft y Extend comparten ahora una base común. Nada cambia en
pantalla; es el cimiento sobre el que cada uno recibirá su propia identidad.

### Cambiado

- **Los temas Forge, Granite, Blockcraft y Extend comparten una base común** (`css/nf-socle-themes.css`): 153 de las
  reglas que tenían en común se escriben ahora una sola vez, en el núcleo; una cincuentena más se quedan en cada tema,
  porque su posición en la hoja de estilos importa. Nada cambia en pantalla — comprobado elemento a elemento y píxel a
  píxel, en los cuatro temas, de día y de noche, en ordenador y en teléfono. Es el primer paso de su renovación: cada
  uno tendrá pronto su propio aspecto. Los cuatro temas pasan a la 1.1.0 y requieren el núcleo 1.2.32.

## [1.2.31] — 2026-10-06

El área de miembros, últimos pasos: la seguridad de la cuenta — los dispositivos conectados se pueden desconectar, las
sanciones de avatar y de firma por fin se aplican — y las notificaciones, trasladadas al área de miembros, donde cada
miembro elige lo que recibe. La renovación del área de miembros está terminada.

### Añadido

- **«Seguridad» muestra los dispositivos en los que la cuenta tiene la sesión iniciada, y permite desconectarlos**:
  cada sesión con su navegador, su sistema, su dirección IP y su última actividad, «Este dispositivo» para aquel desde
  el que se mira; «Desconectar» para otro, o «Desconectar todos los demás dispositivos». Hasta ahora, «Seguridad» solo
  mostraba el historial de inicios de sesión, y una sesión abierta en otro lugar no se podía cerrar. En un sitio de
  demostración, cuya cuenta es compartida, no se puede cerrar ninguna sesión.
- **Cada miembro elige las notificaciones que recibe**, en «Preferencias de notificaciones»: una casilla por tipo de
  notificación (un mensaje privado, una respuesta en un tema seguido, una mención, un comentario, una reacción, un
  recordatorio de evento…) para recibirla en el sitio, y una casilla «Por correo electrónico» para las que el sitio
  también envía por correo (mensajes privados, respuestas en el foro y menciones). Sin ningún ajuste, recibe todo, como
  antes. Para los autores de módulos: un módulo declara las suyas con `types_de_notification()` (ver la guía de creación
  de un módulo).
- **«Mis notificaciones» entra en el área de miembros**, bajo la entrada «Notificaciones» de su menú, veinte por página:
  la página vivía aparte, en `/notifications`, y solo mostraba las cincuenta últimas (ahora hasta las quinientas
  últimas). La dirección antigua lleva a la nueva.

### Corregido

- **Las sanciones de avatar y de firma se aplican ahora.** Un moderador podía imponerlas, pero el perfil nunca las
  comprobaba: un miembro sancionado cambiaba su avatar y su firma como antes. Ahora ve lo que la sanción prohíbe, su
  motivo y hasta cuándo.
- **Ocho modelos de datos del núcleo leen siempre su propia tabla** (sesiones, historial de inicios de sesión, addons,
  traducciones, registros…): cargados desde un módulo, buscaban una tabla que no existe. Nada lo mostraba aún en
  pantalla; la nueva lista de dispositivos lo habría hecho. Es el mismo defecto que el del favicon y los archivos
  eliminados, corregido en la 1.2.29.
- **Una mención silenciada en el foro ya no se traga la respuesta.** Un miembro mencionado en un tema que sigue no
  recibía nada cuando la mención estaba desactivada para él — el correo de mención desactivado por el sitio («Notificar
  las menciones @user por email»), o las menciones desactivadas en sus preferencias. Ahora recibe la respuesta. Cuando la
  mención sí le llega, sustituye a la respuesta, para que no se le notifique dos veces.

## [1.2.30] — 2026-10-05

El área de miembros, segundo paso: el perfil público, y lo que cada uno elige mostrar en él. Y una corrección que
importa: un miembro conectado vuelve a poder leer y escribir en el foro.

### Añadido

- **El perfil público de un miembro se convierte en una verdadera página.** Su portada como banner (se podía subir pero
  no aparecía en ninguna parte), su avatar superpuesto, su nombre de usuario, su rango, sus grupos y su presencia; para
  un visitante conectado, «Contactar» (si la mensajería privada está instalada) y la banderita «Reportar este
  contenido», o «Editar mi perfil» en el propio; luego pestañas, cada una en su propia dirección: Acerca de (cita,
  identidad, enlaces, los campos públicos del sitio y sus estadísticas), Actividad, y las que traen los módulos
  instalados — Foro, Blog, Equipos, Anuncios clasificados. Un módulo sin nada que mostrar sobre este miembro no añade
  ninguna pestaña vacía.
- **Cada miembro elige lo que muestra su perfil**, en «Privacidad y datos»: sus puntos, su karma y sus días VIP se
  quedan para él hasta que los muestre (eran visibles para todos, visitantes incluidos); su edad y su presencia en línea
  siguen mostrándose hasta que las oculte. Su rango sigue siendo público. La elección se aplica en todas partes: la
  tarjeta que se abre al pasar sobre un nombre de usuario, el punto del avatar, las plantillas de los equipos, los
  widgets «¿Quién está en línea?» y «Actividad del foro», la búsqueda de miembros, los cumpleaños del reloj. En su
  propio perfil, un miembro lo ve todo, con un candado en lo que solo él puede ver.

### Corregido

- **Un miembro conectado vuelve a poder leer y escribir en el foro**, y ve las galerías, páginas, tipos de eventos y
  carpetas abiertos a los visitantes. Desde la renovación de los permisos, el rol «Miembro» solo tenía lo que se le
  había dado expresamente: un miembro veía «Sin foro» donde un visitante podía leerlo todo, y no podía escribir en
  ninguna parte — solo los administradores, que se saltan los permisos, no veían nada raro. Vuelven las reglas
  originales: lo que puede hacer un visitante, lo puede hacer un miembro; lo que solo se niega a los visitantes sigue
  permitido a los miembros. Una migración las restaura en cada sitio, sin tocar una regla ya establecida para los
  miembros.
- **Los rangos de reputación (Novato, Bronce, Plata…) están traducidos**: aparecían en francés en todos los idiomas. Y
  la burbuja de un rango ya no revela la puntuación de karma.
- **En un sitio de demostración, el widget «Actividad del foro» ya no muestra la cuenta de administrador creada en la
  instalación** entre los miembros en línea; «¿Quién está en línea?» ya la dejaba fuera.
- **Un sitio web introducido sin «https://» en un perfil lleva ahora a ese sitio web**: se convertía en un enlace a una
  página del propio sitio.
- **Una actualización ya no sobrescribe el catálogo del sitio que sirve el marketplace.** El paquete de actualización
  llevaba un catálogo antiguo, generado en la 1.2.22: colocado en ese sitio, ya no coincidía con los archivos, y toda
  instalación de addon se rechazaba hasta que se regeneraba el catálogo. Un sitio corriente lee el catálogo en línea y
  no se veía afectado.
- **En «Mi espacio», el grupo del miembro se alinea a la izquierda bajo su nombre de usuario**: estaba centrado en
  medio de la cabecera.

## [1.2.29] — 2026-10-05

El área de miembros, primer paso de su renovación: un solo marco, un solo menú, los ajustes donde se esperan. Y el
marketplace ya no ofrece a un sitio un addon hecho para una versión más reciente de NeoFrag.

### Añadido

- **Una contraseña nueva debe tener al menos 10 caracteres.** Se rechaza si repite el nombre de usuario, usa solo uno
  o dos caracteres distintos («aaaaaaaaaa», «ababababab») o es una de las contraseñas más comunes («qwertyuiop»,
  «1234567890»…). La regla se aplica al registrarse, al cambiarla y al restablecerla (al restablecerla, el nombre de
  usuario no se compara). Las contraseñas existentes siguen siendo válidas.

### Cambiado

- **El área de miembros tiene un solo marco y un solo menú.** Cambiaba de forma de una página a otra — un menú a la
  izquierda bajo la tarjeta del perfil, una barra «Menú» plegada arriba, nada en absoluto — y una página tenía tres
  nombres. Las páginas de la cuenta tienen ahora todas el mismo menú, en el mismo sitio: una columna a la izquierda en
  un ordenador, una banda de pestañas con desplazamiento en un teléfono, la página actual resaltada. El menú lleva
  también a las páginas de los módulos: mensajes y notificaciones con su número de no leídos, las suscripciones del foro
  (a las que no llegaba ningún enlace), la moderación. La barra superior de los temas Blockcraft, Extend, Forge y
  Granite y el widget «Área de miembros» usan este menú, con las mismas palabras.
- **Los ajustes están donde se esperan.** «Mi cuenta» reúne el nombre de usuario, la dirección de correo, la
  contraseña, el idioma (que solo se podía elegir con el selector de idioma del sitio) y la zona horaria (que estaba en
  medio del perfil público); «Seguridad» conserva la autenticación en dos pasos y el historial de inicios de sesión; una
  página «Privacidad y datos» contiene la exportación de tus datos y la eliminación de tu cuenta, clasificadas hasta
  ahora en «Seguridad (2FA)».
- **«Mi espacio» se abre con una cabecera compacta** — avatar, nombre de usuario, grupos, «Ver mi perfil» y «Editar mi
  perfil» — en lugar de la gran tarjeta del perfil, que empujaba el menú una pantalla entera hacia abajo en un teléfono.

### Corregido

- **El marketplace ya no ofrece a un sitio un addon hecho para una versión más reciente de NeoFrag.** Solo la
  actualización de un addon comprobaba la versión de NeoFrag que exige, no su instalación: un sitio que se había
  quedado atrás podía instalar un addon que llamaba a funciones que su versión no tenía, y romperse. El marketplace
  oficial envía ahora a cada sitio el catálogo hecho para su versión (un sitio más antiguo que todos los catálogos
  conservados recibe el más antiguo). La ventana del marketplace deja fuera también un addon demasiado reciente y dice
  que primero hay que actualizar el sitio. El botón «Actualizaciones» anuncia una nueva versión de NeoFrag a partir de
  la comprobación de actualizaciones de la Monitorización, ya no solo a partir del catálogo.
- **El favicon elegido por el administrador es también el icono del sitio en un teléfono, y un archivo eliminado sale
  de verdad del disco.** Un error interno impedía al sitio encontrar estos archivos: el favicon faltaba al añadir el
  sitio a la pantalla de inicio de un teléfono (con una advertencia en el registro cada vez), y el archivo de un adjunto
  eliminado del foro o de los mensajes privados se quedaba en el disco y en la base de datos, igual que el avatar y la
  portada de un miembro que borra su cuenta.

## [1.2.28] — 2026-10-05

Una versión de corrección: el área de miembros de una cuenta creada con Discord, GitHub o Google, la exportación y la
eliminación de los datos personales, y el sitio de demostración.

### Corregido

- **Un miembro registrado con Discord, GitHub o Google ya no queda encerrado en su cuenta.** Sin contraseña, no podía
  cambiar su nombre de usuario ni su dirección de correo, ni crear una contraseña, ni desactivar la autenticación en dos
  pasos, ni eliminar su cuenta: todos los formularios pedían la contraseña actual. Ahora confirma su identidad volviendo
  a pasar por el servicio vinculado; la confirmación dura diez minutos, e iniciar sesión con ese servicio cuenta como
  una.
- **La exportación «Mis datos» funciona, y contiene más.** Fallaba con un error: el miembro descargaba una página de
  error en lugar de sus datos. El archivo contiene ahora también el perfil (nombre, fecha de nacimiento, ubicación,
  firma, enlaces), las cuentas vinculadas, el historial de inicios de sesión, las notificaciones y lo que los módulos
  del sitio guardan a su nombre, en texto plano y sin ningún secreto, ni siquiera sus identificadores de sesión. Los
  temas del foro, los comentarios y los mensajes de las conversaciones solo entraron con la 1.2.43.
- **Eliminar tu cuenta borra lo que prometía la página.** El nombre de usuario se anonimiza en las publicaciones y el
  perfil se vacía; las cuentas vinculadas, el historial de inicios de sesión y las notificaciones se borran, y una cuenta
  de Discord, GitHub o Google vinculada vuelve a quedar libre para un nuevo registro. El mensaje «Tu cuenta ha sido
  eliminada» por fin se muestra.
- **En un sitio de demostración, la cuenta compartida ya no se puede modificar**: un visitante podía cambiar su
  contraseña, activar su autenticación en dos pasos o eliminarla, dejando fuera a todos los demás hasta el
  restablecimiento.
- **«Connexion» se traduce en el sentido de «iniciar sesión»** (Log in, Anmelden, Iniciar sesión, Accedi, Entrar): los
  otros cinco idiomas decían «conexión de red». El botón de la administración de Discord que vincula el bot pasa a ser
  «Conectar el bot».

## [1.2.27] — 2026-10-05

El bot de Discord pasa a la **versión 0.2.4**: las imágenes viajan entre el foro y Discord en ambos sentidos (consulta
su propio registro de cambios). La versión 0.2.3 sigue funcionando.

### Añadido

- **Una imagen enviada en Discord aparece en el foro** (con el bot de Discord 0.2.4): el bot la guarda en el sitio,
  comprobada como una imagen pegada en el editor, en lugar de un simple enlace a Discord. La API gana para ello el punto
  de acceso `POST /api/v1/forum/images`.
- **La validación de los registros por correo** (*Ajustes → Gestión de registros*, casilla «Validación por correo»,
  desactivada por defecto): el nuevo miembro recibe un enlace, válido dos días. No puede iniciar sesión antes de
  abrirlo; si lo intenta, se le envía un enlace nuevo. Esta función, heredada de NeoFrag, se había quedado sin terminar
  y no tenía ningún ajuste en la administración.

### Corregido

- **El texto con acentos ya no aparece codificado.** Un título, un nombre de usuario, una etiqueta o el nombre del sitio
  que contuvieran «é» o «—» podían aparecer como `&eacute;` o `&mdash;`: en las conversaciones, la Tienda, las
  Donaciones, el índice de una entrada, los canales RSS, la administración… Estos textos se muestran ahora todos de la
  misma manera, y dos comprobaciones automáticas impiden que el error vuelva (una lee el código, la otra las páginas del
  sitio). Las sugerencias de menciones y de búsqueda, los asuntos de los correos, los datos que leen los buscadores
  (nombre del sitio, título y autor de una entrada) y los webhooks reciben también texto plano.
- **La inicial de un avatar** mostraba «&» para un nombre que empieza por una letra acentuada (administración, página
  de una campaña de donaciones).
- **Registrarse con Discord, GitHub o Google exige aceptar el reglamento**, cuando el sitio lo tiene: una pantalla lo
  muestra, y la cuenta solo se crea una vez marcada la casilla — como con el formulario de registro, que estas cuentas
  se saltaban.
- **El menú ya no lleva a un módulo ausente**: el enlace de un módulo no instalado o desactivado (Foro, Galería,
  Noticias…) se quedaba en el menú — un clic, una página no encontrada. Desaparece, y vuelve cuando el módulo se
  reactiva. Un enlace a una página personalizada publicada se queda; uno a una página sin publicar desaparece también.
- **En un sitio sin el módulo Eventos**, la página de los Equipos ya no escribe una advertencia en el registro de
  errores.
- **El botón ☰ de la administración funciona en las pantallas grandes**: se mostraba allí sin hacer nada; ahora pliega
  el menú lateral, y lo vuelve a abrir, recordando la elección. En un teléfono, abre el menú como antes.
- **El avatar del autor de un mensaje del foro es más grande**: 80 px (36 px en un teléfono), en lugar de los 40 px que
  le daban todos los temas.
- **Una imagen en una firma, o en la descripción de un álbum de la galería, se muestra**: la página mostraba el código
  HTML (`<p><img …></p>`) en lugar de la imagen. Las firmas y descripciones ya guardadas se muestran sin tener que
  volver a escribirlas.
- **La versión de texto de los correos es legible**: los párrafos se pegaban, los acentos estaban codificados, y un
  enlace terminaba seguido de la palabra siguiente — inutilizable en un cliente de correo que solo muestra texto.
- **Un formulario manipulado ya no hace fallar la página**: una casilla enviada en una forma que ningún navegador envía
  se ignora, como una casilla sin marcar.
- **En un sitio de demostración, la cuenta de administrador creada en la instalación sigue oculta al pasar el ratón y
  en el historial de moderación**, como en su página: la tarjeta de miembro mostrada al pasar el ratón daba su nombre,
  sus grupos y sus fechas de visita, y el historial de moderación su nombre.

## [1.2.26] — 2026-10-04

El bot de Discord pasa a la **versión 0.2.3**: ya no escribe una advertencia de discord.js en cada respuesta privada
(consulta su propio registro de cambios). La versión 0.2.2 sigue funcionando.

### Añadido

- **El reglamento y el mensaje de bienvenida se traducen idioma por idioma**: *Ajustes → Gestión de registros* tiene una
  pestaña por idioma del sitio, y cada visitante lee el reglamento — y recibe el mensaje — en el idioma de la página. Un
  idioma que aún no tiene su propio texto muestra el texto común: un sitio que solo tenía uno lo conserva para todos sus
  idiomas, nada cambia hasta que se traduce.
- **El registro de cambios existe en inglés** (`CHANGELOG.en.md`; la versión francesa sigue siendo la referencia).
- **Cada addon tiene ahora su miniatura, en el mismo formato (960 × 600)**: los módulos API y Discord no tenían, los
  cuatro temas del catálogo conservaban una imagen más antigua y más pequeña, y tres widgets del núcleo no tenían
  ninguna en *Temas y addons*. Algunas siguen mostrando un addon vacío o sin configurar. Un addon nuevo ya no se puede
  publicar sin la suya.

### Cambiado

- **Las descripciones del catálogo dicen lo que hace cada addon**: treinta de ellas cabían en pocas palabras o solo se
  presentaban como «gaming». Ahora describen lo que el addon hace de verdad, y para quién es, en los seis idiomas; cinco
  eran incluso falsas (el widget Equipos no muestra miembros, el widget Descargas muestra los archivos más
  descargados…).
- **El módulo Eventos se llama «Eventos»**, ya no «Eventos gaming»: ningún título del catálogo lleva ya la palabra
  «gaming».
- **El módulo Pagos requiere el módulo Gamificación**, que acredita los puntos y días VIP comprados: sin él, un pago se
  cobraba y no se acreditaba nada. Al instalar el sitio, elegir Pagos añade Gamificación; desde el marketplace, hay que
  instalarlo antes. Si falta o se desactiva más tarde, las ventas se cierran y la administración lo dice; un pago
  recibido no se marca como tratado, y Stripe lo volverá a enviar.

### Corregido

- **Una imagen pegada (Ctrl+V) o arrastrada en el editor de texto se guarda** — respuesta en el foro, comentario,
  página de la administración, sala del equipo de los mensajes privados: aparecía rota y luego desaparecía al publicar.
  Ahora se envía al sitio y se queda en el mensaje. Está reservado a los miembros conectados: una imagen JPEG, PNG, GIF o
  WebP de 5 MB como máximo. El sitio guarda una copia nueva, que elimina la información oculta de una foto (dónde se
  tomó, por ejemplo). Por encima de 2000 píxeles de lado, la imagen se reduce; por encima de 8192 píxeles de lado o de
  unos 12,6 millones de píxeles, se rechaza. Un GIF animado se convierte en una imagen fija. En el sitio de
  demostración, la subida de imágenes sigue cerrada.
- **El mensaje de bienvenida se muestra limpio**: escrito en el editor enriquecido, llegaba a los mensajes con sus
  etiquetas a la vista (`<h3>`, `<p>`…). Ahora se convierte en texto (títulos, listas con viñetas o numeradas, enlaces
  convertidos en clicables), y su título ya no muestra `&eacute;` en lugar de una letra acentuada.
- **El mensaje de bienvenida se envía también cuando alguien se registra con Discord, GitHub o Google**: solo lo enviaba
  el formulario de registro.
- **El código QR de la autenticación en dos pasos se muestra**: la pantalla de activación (*Seguridad de la cuenta →
  Activar 2FA*) mostraba el código de la imagen en un cuadro de texto en lugar de la imagen que escanear — los
  formularios no conocían este tipo de campo y lo trataban como texto.
- **Los logos del widget Socios llevan al sitio del socio** y cuentan la visita: llevaban a una página inexistente desde
  la 1.0.0, y el contador «Visitas» ya no se movía. La página de los Socios también pasa por la visita contada.
- **Los botones «Pagar» (Pagos) y «Comprar» (Tienda) funcionan**: llamaban a una dirección inexistente.
- **Suscribirse al boletín desde el widget funciona**: la dirección escrita se perdía por el camino. El enlace de
  confirmación del correo era relativo (inútil en un cliente de correo), como el enlace de baja de las campañas: los dos
  son ahora direcciones completas. Y si el correo no puede enviarse, la suscripción se anula y se avisa al visitante, en
  lugar de leer «enviado».
- **Los enlaces de los correos del foro y de la mensajería** (mención, suscripción, conversación) son direcciones
  completas: relativos, no llevaban a ninguna parte.
- **Las encuestas siguen el ajuste «Mostrar resultados»** (después de votar, al cerrarse, nunca) — en su página como en
  su widget, que siempre los mostraba. Los gestores los ven siempre, con una nota.
- **Los widgets siguen las reglas de su módulo**: el widget Galerías ya no muestra álbumes en borrador, programados, en
  la papelera o restringidos a un grupo; el widget Foro, ningún extracto de las categorías VIP ni líneas vacías para los
  mensajes eliminados; el widget Eventos y el calendario, ningún evento programado antes de su hora; el widget
  Reclutamiento sigue «Ocultar ofertas no disponibles».
- **Un enlace a una página del sitio, insertado en el editor, conserva su dirección completa**: el editor lo acortaba en
  una ruta (`../../…`) que solo funcionaba desde la página de edición — mostrado en cualquier otro lugar, no llevaba a
  ninguna parte.
- **La hoja de ruta** (`ROADMAP.md`) dice que el código está publicado desde el 4 de octubre de 2026.

### Seguridad

- **Dos direcciones técnicas antiguas de la mensajería mostraban los mensajes sin protegerlos**: un mensaje que
  contuviera código podría haberse ejecutado allí en el navegador. Ninguna página las usaba ya, y el acceso a la
  conversación ya era necesario; ahora muestran los mensajes como la propia conversación.
- **Los botones Facebook y Twitter de la página de los Socios ya no siguen un enlace `javascript:`** introducido en la
  administración: estas direcciones se filtran y se escapan, como ya lo era el sitio web del socio.
- **El botón «Comprar» de la Tienda y el botón «Pagar» comprueban ahora que la petición viene de verdad del sitio**: una
  página maliciosa ya no puede hacer que un miembro conectado compre un artículo sin saberlo.
- **Una compra ya no puede contarse dos veces**: el cargo de los puntos, el stock y la posesión se comprueban y se
  escriben en una sola transacción — dos compras simultáneas ya no venden dos veces el último artículo, ni cargan dos
  veces un saldo. Los puntos ganados y los días VIP también se suman de una vez: dos ganancias en el mismo momento ya no
  se sobrescriben. Un artículo de 0 puntos puede obtenerse ahora sin cargo (se rechazaba, «no hay suficientes puntos»).
- **Las suscripciones al boletín tienen un límite de frecuencia**: 5 peticiones por hora y dirección IP, 3 al día por
  dirección de correo — lo bastante para impedir el envío masivo de correos de confirmación a direcciones elegidas.
- **Una encuesta cuyos resultados están ocultos ya no muestra su número total de votos** en la lista.

## [1.2.25] — 2026-10-04

El bot de Discord pasa a la **versión 0.2.2**: los mensajes del foro que retransmite pierden todas sus etiquetas HTML,
incluso las anidadas (consulta su propio registro de cambios). La versión 0.2.1 sigue funcionando.

### Corregido

- **La ventana del marketplace ofrece también los widgets que no aporta ningún módulo** (Acerca de, Servidor de juego,
  Lector de canales, Efecto de temporada, Grupo de Steam, Servidor TeamSpeak 3, Estado en directo): solo mostraba
  módulos y temas, y estos siete widgets solo se podían instalar con «Añadir», con el archivo en la mano. Un widget que
  ya aporta un módulo (el del Foro, por ejemplo) no aparece allí dos veces.
- **La guía de administración nombra el botón que guarda una nueva dirección del sitio** por su etiqueta, *Usar*,
  seguida de la dirección — y ya no por una falsa dirección `https://…` que un lector tomó por un enlace.

### Seguridad

- **Los flujos de verificación del repositorio solo reciben un token de solo lectura** (`permissions: contents:
  read`): no tienen nada que escribir, así que un paso comprometido no podría cambiar nada. Señalado por el análisis de
  código de GitHub cuando los repositorios se abrieron al público.
- **El widget Vídeo solo acepta, para un elemento de su lista de reproducción, una dirección `http(s)` o una ruta del
  sitio**: una dirección `javascript:` o `data:` se ignora al hacer clic. El reproductor no ejecutaba nada, pero una
  dirección así no tenía nada que hacer allí. Señalado por el mismo análisis.

### Añadido

- **Tres pruebas más en el flujo `installation.yml`**: `check-extensions` instala cada uno de los addons del marketplace
  publicado en un sitio que solo tiene el núcleo, a través de la ventana del marketplace, y con «Añadir» lo que esta no
  ofrece; `check-prerequis-absents` retira por turnos cada extensión de PHP necesaria, y comprueba que el asistente y el
  instalador de línea de comandos dicen cuál falta; el paquete publicado se instala con el asistente en un alojamiento
  compartido simulado (Apache sin las funciones que lanzan un programa, `open_basedir`, 128 MB). Y se comprueban los
  enlaces de todos los documentos (lychee).

## [1.2.24] — 2026-10-04

**NeoFrag Reborn se abre en GitHub.** El código del CMS ([NeoFragReborn/neofrag](https://github.com/NeoFragReborn/neofrag)),
los addons a la carta ([NeoFragReborn/extensions](https://github.com/NeoFragReborn/extensions)) y el bot de Discord
([NeoFragReborn/bot-discord](https://github.com/NeoFragReborn/bot-discord)) se publican allí con la historia de sus
versiones — desde la 1.0.0 para el CMS y los addons, desde la 0.1.0 para el bot: el código de cada versión, sus notas, y
los paquetes a partir de la 1.2.23.

### Cambiado

- **Las bases de datos indicadas son las que se prueban**: cada versión se instala y pasa toda su batería de pruebas,
  con PHP 8.2 y 8.5, en MySQL 5.7, 8.0 y 8.4 y en MariaDB 10.5, 10.6, 10.11, 11.4 y 11.8 — la guía de instalación cita
  esas versiones, y ya no «5.7+» y «10.5+».
- **Las guías dicen dónde encontrar el proyecto**: el paquete de instalación y el archivo del bot en su página de
  versiones, los addons a la carta y su catálogo en la de `extensions`, el código para quien quiera contribuir. En el
  wiki, las referencias a un documento del repositorio (despliegue, arquitectura, herramientas) llevan a ese documento en
  GitHub, en lugar de conservar solo su título.

### Corregido

- **Los ajustes de los módulos vuelven a abrirse**: la ventana «Configuración» de los módulos Foro, Reclutamiento,
  Noticias, Galería, Eventos, Calendario y Artículos respondía «error 500» cada vez que se abría, en todos los sitios,
  desde la 1.2.0: el campo «número» rechazaba un entero (su paso, y el valor leído de la base de datos) desde que el
  código pasó al tipado estricto.
- **Equipos: la opción «Mostrar partidos jugados» se puede volver a desmarcar.** Guardar los ajustes almacenaba la
  palabra «Array» en lugar de un sí o un no, que siempre se leía como «sí».
- **Los ajustes de un autenticador nunca configurado** (Google, Discord, GitHub) se abren sin escribir ocho
  advertencias en el registro.
- **El panel y las estadísticas de un sitio sin Artículos, gestor de errores o Foro** ya no escriben advertencias en el
  registro cada vez que se abren: las tarjetas de estos módulos solo aparecen si están instalados, y ya no se consulta
  ninguna tabla ausente.
- **El asistente de instalación vuelve a leer la configuración que acaba de escribir**: en un alojamiento cuya caché de
  PHP no vuelve a comprobar los archivos, un segundo intento del paso «Base de datos» seguía leyendo el antiguo
  `config/db.php`.
- **Un sitio desplegado desde git e instalado con el asistente web funciona**: el asistente no escribía
  `config/neofrag.php` (el paquete lo lleva, un clon solo tiene su plantilla), y cada página respondía «error 500» sin
  una sola línea en el registro.
- **El ejemplo `nginx.conf` arranca**: la regla de TinyMCE, sin comillas, hacía que nginx rechazara toda la
  configuración («missing closing parenthesis»). Sus redirecciones ya no llevan el parámetro interno `request_url`.
- **Los archivos subidos se sirven con Apache y PHP como módulo**: la protección de `upload/` usaba una directiva
  prohibida en un `.htaccess`, y toda esa carpeta — avatares, galería, mediateca — respondía «error 500».
- **Con Caddy, los archivos subidos que no son imágenes** (un PDF de la mediateca) se sirven: el ejemplo solo servía
  imágenes.
- **`/index.php` lleva a la página de inicio** (redirección permanente) en lugar de a una página no encontrada; y una
  dirección con una extensión que ninguna página sirve (`/newsletter/.env`, que prueban los robots) responde 404 sin
  escribir una anomalía en el registro.
- **`tools/check-all.php` con PHP 8.2**: cada comprobación contaba allí como un fallo, incluso cuando pasaba — el código
  de salida se leía una vez de más, algo que PHP solo permite desde la 8.3. Para quien contribuye con PHP 8.2.

### Seguridad

- **El asistente de instalación ya no se vuelve a abrir en un sitio instalado cuya base de datos no responde.** Si el
  bloqueo `install/db.txt` había desaparecido, un corte temporal de la base de datos volvía a abrir el asistente, cuyo
  paso «Base de datos» reescribe `config/db.php`: un visitante podía entonces redirigir el sitio a su propia base de
  datos. El sitio muestra ahora «temporalmente no disponible». Y el paso «Administrador» se niega a crear una cuenta en
  cuanto el sitio tiene una.
- **Apache solo ejecuta ya el `index.php` de la raíz**: la regla de reescritura dejaba ejecutarse directamente cualquier
  archivo `index.php` de una subcarpeta (más de ochenta controladores internos).

### Añadido

- **Cuatro comprobaciones más**: `check-assistant` recorre el asistente de instalación de principio a fin, como un
  visitante, para cada perfil (pantallas, rechazos, cuenta creada, inicio de sesión con la contraseña introducida,
  registro silencioso); `check-reglages` abre la pantalla de ajustes de cada addon, la guarda tal cual y la vuelve a
  abrir; `check-serveur-web` prueba Apache, nginx y Caddy, con las configuraciones incluidas, colocando sondas (carpetas
  y archivos prohibidos, scripts que no deben ejecutarse, reescritura, cabeceras de seguridad); `check-nouveau-venu`
  sigue al pie de la letra el README y la guía del colaborador en una máquina limpia. Los flujos `installation.yml` y
  `nouveau-venu.yml` los vuelven a ejecutar cada semana.
- **Los comandos del README funcionan en una máquina nueva**: su bloque «Desarrollar» terminaba con una batería de
  pruebas que fallaba por falta de una base de pruebas; ahora remite a la guía del colaborador, que crea esa base
  (`php tools/prepare-test-db.php`).

## [1.2.23] — 2026-10-04

El bot de Discord pasa a la **versión 0.2.1**: nada cambia en su funcionamiento — se convierte en un proyecto propio,
con su licencia, su NOTICE y su propio registro de cambios. Instalarlo solo es útil para quien quiera esos archivos; la
0.2.0 sigue funcionando.

### Cambiado

- **El mapa del sitio ya no lista una sección vacía**: noticias, reclutamiento, una FAQ, equipos… sin nada que mostrar
  ya no se ofrecen a los buscadores, que solo encontrarían allí una página «nada todavía». La sección vuelve sola con su
  primer contenido. El contacto, el libro de visitas, el boletín, la radio web y las donaciones siguen listados: siempre
  tienen algo que ofrecer.
- **Las copias de seguridad ya no se acumulan**: cada actualización hace una completa (16 MB y más), y nada las
  retiraba. El sitio conserva siempre las cinco más recientes, y retira las demás a los treinta días; un archivo
  colocado a mano en `backups/` nunca se toca.
- **La instalación comprueba todo lo que necesita el CMS.** El asistente no comprobaba ni `openssl` (sin él, guardar un
  secreto — servidor de correo, captcha, autenticación en dos pasos — falla con un error), ni `fileinfo` (sin él, se
  rechaza toda subida de archivos), ni `iconv` (el código QR de la autenticación en dos pasos), ni el cifrado de
  contraseñas Argon2, sin el cual la creación de la cuenta de administrador fallaba. Ahora los comprueba y se niega a
  continuar si falta uno; `install/cli.php` exige exactamente la misma lista, versión de PHP incluida, en lugar de dos
  extensiones. La *Monitorización* y el panel de la administración muestran la misma lista. Se requiere PHP 8.2 o
  posterior; el producto está probado hasta PHP 8.5.
- **nginx y Caddy**: el paquete incluye por fin los ejemplos de configuración que prometían las guías (`nginx.conf`,
  `Caddyfile`), genéricos, y que bloquean las mismas carpetas y archivos sensibles que el `.htaccess` de Apache — el
  antiguo ejemplo de nginx dejaba accesibles `install/`, `tools/`, `tests/` y `docs/`, y el extracto de la guía de
  despliegue solo protegía tres carpetas de ocho.
- **Cada addon dice quién lo escribió.** Los addons del NeoFrag original conservan a sus autores, Michaël BILCOT y Jérémy
  VALENTIN — los conectores de inicio de sesión y los idiomas, que no firmaban nada, los nombran ahora, y las
  traducciones inglesas originales vuelven a citar a sus traductores, FoxLey y eResnova. Las adaptaciones citan a su
  autor: el Reloj (ArkaNiX), el tema Extend (Chewbaka), el gestor de archivos (HiddenBlob, HiddenCMS) y Donaciones
  (HiddenBlob, a partir de majiid). El resto lleva la firma «NeoFrag Reborn». Los temas Nebula, Blockcraft, Forge y
  Granite pasan a la LGPL, como el producto; Extend conserva la licencia de su autor (CC BY-NC-SA). La licencia de cada
  addon LGPL remite al texto oficial de la LGPL.
- **HSTS**: la cabecera que pone el `.htaccess` ya no se extiende a los subdominios y ya no declara el sitio apto para la
  lista de precarga de los navegadores (`includeSubDomains` y `preload` retirados): puestos por defecto, comprometían
  durante un año todos los subdominios de quien instalaba el CMS.

### Corregido

- **Un sitio instalado desde el paquete no registraba ningún error.** El paquete no contenía la carpeta `logs/`, y nada
  la creaba: PHP escribía sus errores en el registro del servidor, *Monitorización → Registro de errores* se quedaba
  vacío y decía que la carpeta estaba «sin permiso de escritura». El paquete incluye ahora `logs/`, `cache/` y
  `backups/` con su protección, y un sitio que no tiene `logs/` la vuelve a crear solo: basta con actualizar para
  reparar un sitio ya instalado.
- **La documentación del wiki ya no tiene enlaces muertos.** Tres referencias de sus páginas a documentos que el sitio no
  tiene (el manual de las herramientas, una parte de la guía de despliegue) llevaban a una página de error; ahora solo
  conservan su texto. La guía del marketplace ya no menciona addons «de terceros» que no existen, ni procedimientos
  reservados al servidor del proyecto.
- **Ajustes → Posicionamiento** se negaba a guardar en cuanto una descripción, un eslogan o un título para los
  buscadores contenía muchas letras acentuadas: cada una contaba como varios caracteres, y una descripción portuguesa
  de 139 caracteres superaba el límite de 160. Un código de verificación de Google o Bing pegado como etiqueta completa
  se rechazaba del mismo modo. Las redirecciones y el posicionamiento por contenido siguen la misma regla.
- **El informe de posicionamiento** reconoce una propiedad de Google Search Console verificada por el DNS del dominio
  (en lugar de decir que «no está declarada»), y aconseja enviar el mapa del sitio que reúne todos los idiomas,
  `/sitemap.xml`, más que el del idioma mostrado. Cuando Google está verificado, ya no dice que Bing «no está
  declarado»: Bing Webmaster Tools puede importar un sitio desde Google Search Console sin colocar nada en el sitio, así
  que el informe no puede verlo.
- **`humans.txt`, `robots.txt` y la clave de IndexNow**: una dirección ausente — un `humans.txt` vacío, una clave
  desconocida — responde un simple «no encontrado», sin escribir un error en el registro del sitio por cada robot que
  la pide.

---

## [1.2.22] — 2026-10-03

### Añadido

- **El posicionamiento por contenido**: un botón «Posicionamiento» en la ficha de edición de una noticia, de una entrada
  del Blog, de una página o de una página del wiki da, para cada idioma, el título y la descripción que muestran los
  buscadores y las vistas previas al compartir. Si se dejan vacíos, siguen siendo automáticos.
- **Ajustes → Posicionamiento → Informe de posicionamiento**: lo que ve un buscador, medido en el sitio — las páginas
  del mapa del sitio por módulo, los textos de cada idioma, la imagen para compartir, Google y Bing, `robots.txt`, el
  mantenimiento —, con, para cada punto, el enlace a lo que lo corrige.
- **Redirecciones**: una dirección antigua lleva a la nueva (301) en lugar de responder «Página no encontrada», con el
  número de visitas que aún recibe. Una página o una página del wiki renombrada deja la suya sola; se pueden añadir más
  a mano, para la dirección de un sitio anterior, por ejemplo.
- **Avisar a los buscadores (IndexNow)**: activado en Ajustes → Posicionamiento, el sitio señala, en pocos minutos, cada
  página que aparece, cambia o desaparece, a Bing, Yandex, Seznam, Naver, Yep y Amazon. Google no participa: para
  Google, el mapa del sitio sigue siendo el camino. Se señalan las páginas de cada módulo listado en el mapa del sitio,
  sin nada que añadir; se necesita la tarea programada del sitio.

### Corregido

- **Moderación**: en la administración, un moderador sin el derecho «conversaciones privadas» podía abrir la denuncia de
  un mensaje privado, y ese acceso no se anotaba en el registro de auditoría. Quien denuncia un mensaje privado ve por fin
  el aviso que le está destinado.
- **Mensajes**: el mensaje enviado a la sala del equipo desde la administración respondía siempre «No hay ninguna sala
  del equipo configurada».
- Se acabaron las advertencias de PHP en el registro cuando la denuncia, el mensaje o el miembro afectado habían sido
  eliminados.

---

## [1.2.21] — 2026-10-03

### Añadido

- **Ajustes → Posicionamiento**: para cada idioma, el eslogan del título de la página de inicio y la descripción que
  muestran los buscadores; una imagen para compartir (1200 × 630) para las vistas previas en Discord, X o Facebook; los
  códigos de verificación de Google Search Console y Bing Webmaster Tools.
- **Un mapa del sitio por idioma**, reunidos en la raíz (`/sitemap.xml`): cada módulo lista allí sus propias páginas
  públicas — el blog, el foro y sus temas, el wiki, la galería, el calendario, los eventos… —, solo las que un visitante
  puede leer y que existen en el idioma del mapa.
- En la página de inicio, **datos estructurados** que describen el sitio, su logo y sus redes sociales.

### Cambiado

- **Los perfiles de los miembros y el directorio de miembros** ya no se ofrecen a los buscadores, ni los resultados de
  búsqueda: poco contenido, y un miembro no debería acabar en Google sin haberlo elegido.

### Corregido

- **El mapa del sitio** solo contenía direcciones relativas — ignoradas por los buscadores —, en un solo idioma, sin el
  wiki ni el foro; `robots.txt` lo anunciaba con una dirección relativa.
- **Cada página del Blog** declaraba `/articles/…`, una redirección, como su dirección canónica; la página de inicio,
  `/index`. Los enlaces entre idiomas están completos, con la versión por defecto (`x-default`).
- **El título de la página de inicio** ya no repite el nombre del sitio («NeoFrag Reborn | NeoFrag Reborn»).
- **Google Analytics**: el ajuste solo aceptaba el antiguo formato `UA-…`, que Google abandonó en 2023; acepta los
  identificadores actuales, `G-…`.
- **Idioma del navegador**: un visitante cuyo navegador solo anuncia `de-DE` llega en alemán, y no en el idioma por
  defecto del sitio.
- **El selector de idioma** y el aviso «Este contenido no existe en …», desde el Blog, llevaban a su dirección antigua.

### Seguridad

- **Las direcciones completas** de la cabecera de la página (canónica, idiomas, compartir) se construyen a partir de la
  dirección del sitio, y ya no a partir de la cabecera `Host` de la petición, que cualquiera puede falsificar.

## [1.2.20] — 2026-10-03

### Añadido

- **Un perfil de instalación «Asociación / club»**: noticias, foro, galerías, calendario, donaciones, boletín, wiki y
  FAQ, sin el equipamiento esport. Se une a *Completo*, *Gaming / eSports*, *Comunidad* y *Solo el núcleo*, en los seis
  idiomas del asistente.

### Corregido

- **La exportación de los miembros en formato JSON** (RGPD, artículo 15) escribe ahora su etiqueta «Exportación de los
  datos de los miembros»: salía vacía.
- **Blog**: en la página de un autor, «Ver su perfil» llevaba a una página no encontrada.

### Seguridad

- **La instantánea de la demo ya no lleva ningún secreto.** El archivo que restablece la demo, incluido en el paquete de
  la demo, habría copiado el usuario del envío de correo, la clave del servicio de traducción automática del NeoFrag
  original y las claves de los widgets Twitch y TeamSpeak de un sitio donde se hubieran introducido; aún llevaba la
  línea, vacía, de la clave secreta del captcha. No se había filtrado ninguna clave. Una comprobación verifica ahora la
  lista contra el código, y el propio archivo incluido.

### Documentación

- NeoFrag Reborn se presenta como **el CMS de código abierto para las comunidades, del gaming a las asociaciones**; la
  guía de conceptos dice qué perfil elegir para el sitio de una asociación o de un club.

## [1.2.19] — 2026-10-02

### Añadido

- **Un captcha moderno, activo desde la instalación: ALTCHA.** Lo aloja el propio sitio — sin cuenta, sin clave, sin
  cookie, sin servicio de terceros. El visitante ve una casilla que se marca sola mientras rellena el formulario: su
  navegador hace un pequeño cálculo, que el servidor comprueba. Protege el formulario de contacto, el registro y el
  reclutamiento. Al actualizar, un sitio sin claves de reCAPTCHA pasa a ALTCHA; un sitio que las tenía conserva
  reCAPTCHA. ALTCHA encarece el envío masivo para un robot; frente a un atacante decidido, los proveedores siguientes,
  que añaden sus propias comprobaciones, son más sólidos.
- **Como alternativa, Cloudflare Turnstile, hCaptcha o Google reCAPTCHA v2**, en *Ajustes → Seguridad antibots*, con el
  enlace a la consola de cada uno. La clave secreta se cifra y nunca vuelve a mostrarse. Sin sus dos claves, un
  proveedor se sustituye por ALTCHA en lugar de dejar el formulario abierto.
- El captcha y sus mensajes existen en los seis idiomas.

### Corregido

- **reCAPTCHA**: la clave secreta se enviaba en la URL de verificación; ahora se envía en el cuerpo de la petición, como
  exige Google. El enlace obsoleto a su consola se sustituye, y la dirección del visitante que se transmite sigue la
  regla que usa el resto del sitio, que tiene en cuenta un proxy declarado de confianza.
- Cuando no se ha completado la comprobación antibots, el mensaje se escribe completo bajo el captcha, en lugar de un
  icono solitario cuya burbuja no aparece en una pantalla táctil.

### Seguridad

- **Superar el captcha una vez ya no dispensa de volver a superarlo.** Desde el NeoFrag original, un captcha superado
  dispensaba de volver a superarlo en todos los envíos siguientes del mismo formulario, hasta el final de la sesión: un
  robot que resolvía un solo desafío podía luego enviar sin límite. La dispensa solo vale ya para un único reenvío, tras
  otro error en el formulario.
- Una solución de ALTCHA solo se puede usar una vez: presentarla de nuevo se rechaza.
- La política de seguridad de las páginas ya no permite Google por defecto: solo las direcciones del proveedor de
  captcha elegido, y ninguna con ALTCHA.

## [1.2.18] — 2026-10-02

### Corregido

- **Reordenar arrastrando vuelve a funcionar**: las categorías y los foros del foro, los equipos y sus roles, los
  socios, los grupos de miembros, los idiomas y los conectores de inicio de sesión, y las filas, columnas y widgets del
  editor en vivo. La posición llegaba del navegador como texto, y el desplazamiento se detenía con un error — detectado
  en el registro de la demo.
- **En la demo, el editor en vivo** vuelve a abrir los ajustes de un widget, y cualquier cambio allí recibe el mensaje
  «Acción desactivada en el sitio de demostración.» en lugar de un error silencioso. Las disposiciones no se pueden
  cambiar allí: un widget HTML escrito por un visitante se mostraría a todos los demás.

### Documentación

- **La guía de instalación por FTP** nombra el paquete correcto (`neofrag-reborn-public-<version>.zip`), describe la
  actualización con el botón, y ya no pide ejecutar una herramienta que falta en el paquete: las migraciones se aplican
  solas en la primera visita, demo incluida.
- **Las guías para desarrolladores** describen cómo poner una dirección introducida en un enlace solo una vez
  comprobada (`nf_url_sure()`), el bloqueo del sitio de demostración, las listas divididas en páginas, los contadores y
  las fechas; las cifras del README y de las guías siguen el código (62 módulos, 40 widgets).

## [1.2.17] — 2026-10-02

### Seguridad

- **En la demo, la pestaña Archivos de la Monitorización** seguía mostrando el árbol real de la instalación (nombres de
  carpetas y archivos, sin su contenido): ahora muestra un árbol de ejemplo, como el gestor de archivos.

## [1.2.16] — 2026-10-02

### Seguridad

- **Un enlace malicioso ya no se ejecuta desde la dirección de una denuncia.** El miembro que denuncia proporciona esta
  dirección, que luego se muestra al moderador: una dirección «javascript:» se ejecutaba en su navegador al hacer clic.
  La misma protección se aplica a los enlaces de un canal RSS (widget), de un carrusel, del directorio de enlaces, de los
  socios y a cualquier dirección que construya el sitio: solo pasan las direcciones web, de correo, de teléfono o
  internas del sitio.
- **Eliminar un rol, crear la clave del bot de Discord, cerrar un anuncio clasificado o bloquear un tema del foro** se
  hacían con un simple enlace: bastaba un enlace malicioso para desencadenar la acción para un administrador, un autor o
  un moderador conectado. Estos enlaces llevan ahora un token de seguridad.
- **La demo, blindada.** Su cuenta compartida (demo / demo) es administradora: podía activar el modo de depuración para
  todos los visitantes, recorrer y leer sus archivos, el registro de errores y phpinfo, ver la clave de la tarea
  programada, cambiar los ajustes del sitio, crear claves de API, banear para siempre direcciones IP o miembros,
  desviar la dirección de PayPal de las donaciones y borrar imágenes que el restablecimiento no podía devolver. En una
  demo, cualquier acción hacia una zona bloqueada se rechaza antes de llegar a su pantalla, las pantallas sensibles
  muestran un aviso, el gestor de archivos solo muestra un árbol de ejemplo, no se elimina ningún archivo, y el
  restablecimiento restaura también los mapas y modos de juego, los roles de los equipos, las notificaciones, los puntos
  y los permisos.

### Corregido

- **Un archivo rechazado al subirlo** (extensión prohibida, o demo) hacía fallar la página (error 500), por ejemplo al
  cambiar el avatar: ahora el campo dice por qué.
- **Las estadísticas sobre un periodo desmesurado** (siglos, hora a hora) agotaban la memoria del servidor: el periodo y
  el número de puntos están limitados.
- **La exportación de los miembros** escribía una advertencia de PHP 8.5 por fila en el registro.
- **En la demo**, el banner verde tapaba las notificaciones, y la cuenta de emergencia aparecía en la lista de jugadores
  de un equipo y en la actividad del panel.
- **Una fina línea blanca recorría el borde derecho del editor de texto en modo oscuro** (comentarios, mensajería,
  administración): el editor pintaba su zona de escritura de blanco, y ese blanco se salía una fracción de píxel.

## [1.2.15] — 2026-10-02

### Cambiado

- **La búsqueda rápida de la administración (Ctrl+K), rehecha**: secciones en versalitas discretas, alineadas con los
  iconos, en lugar de títulos pegados al borde; el nombre de la sección ya no se repite al final de cada fila; una lupa
  en el campo; la parte encontrada resaltada. La búsqueda ignora los acentos (en un sitio francés, «evenements»
  encuentra «Événements gaming») y busca también en las secciones («gaming» lista los seis módulos de esa sección).
- **La foto del miembro en la barra lateral de la administración**, en lugar de la primera letra de su nombre de
  usuario, que se mantiene para una cuenta sin foto.

### Corregido

- **Las teclas de flecha, en la búsqueda rápida**, movían una selección invisible: solo el ratón resaltaba una fila.
- **«Lo que trae esta versión»**, en la ventana de actualización, abría el registro de cambios arriba de la página, en
  la versión anterior: ahora lleva a la versión anunciada.
- **El recuadro de comentarios de una entrada del Blog** perdía su margen interior y la línea bajo su título:
  «Comentarios» y el contador tocaban el borde. Cinco temas aplicaban a todo recuadro anidado una regla pensada para la
  cabecera de los recuadros transparentes.

## [1.2.14] — 2026-10-02

### Cambiado

- **La página Monitorización, en pestañas**: un resumen siempre visible (versión y actualización, PHP, copias de
  seguridad, herramientas de diagnóstico), y luego *Resumen*, *Copias de seguridad*, *Diagnóstico*, *Servidor y
  seguridad* y *Archivos*. Apilaba una decena de tarjetas en una columna estrecha, sobre casi 2700 píxeles; cada
  pestaña cabe ahora en una pantalla. La salud del sitio se dice con sobriedad («Todo va bien», «Puntos por revisar»,
  «Errores por corregir»).
- **Los botones de acción de la administración, iguales en todas partes**: editar, acceso y ordenar en gris neutro;
  eliminar con contorno rojo, con una papelera. Cambiaban de una página a otra — azul cielo, verde azulado, botones
  rellenos, cruces o papeleras. Las reglas de diseño de la administración se describen en la guía «Crear un módulo», y
  una comprobación las hace cumplir.
- **Las pastillas de estado, suaves en todas partes**: un fondo pálido y un texto más marcado del mismo color, como
  «Publicado». «Activo» se escribía en blanco sobre verde relleno, «Built-in» en negro. Se acabaron los botones de color
  rellenos en las filas de las listas (vista previa, duplicar, restaurar…).
- **El botón de crear, en el mismo sitio en todas partes**: arriba de la tarjeta de la lista que alimenta. Ocho páginas
  lo ponían en la barra superior, al final de la lista o encima de la tarjeta (publicidad, tienda, donaciones, pagos,
  carrusel, equipos, juegos, copias de seguridad de la Monitorización).
- **Temas y addons, compacto**: una búsqueda, el número de extensiones de cada tipo, filtros de tipo y de estado que se
  combinan («módulos inactivos»), y una vista en lista más densa, mostrada por defecto, junto a la cuadrícula. La página
  se abre en los módulos; alineaba las unas 120 extensiones como grandes tarjetas, sobre más de 10 000 píxeles.
- **Las plantillas de correo, en una sola tabla**: las plantillas agrupadas por familia (cuentas, foro, mensajería,
  moderación, boletín), con su asunto, sus idiomas y su estado en una fila. La página apilaba una tarjeta por plantilla,
  sobre 2000 píxeles; ahora es la mitad.
- **Discord, en pestañas**: *Resumen*, *Funcionalidades*, *Configuración del servidor*, *Canales y foros*, *Grupos y
  roles*, *Roles temporales* — se pasa de una a otra sin volver a la página principal. El registro del bot se desplaza
  dentro de su tarjeta en lugar de alargar la página.
- **Las estadísticas, más legibles**: las fechas y el intervalo caben en una línea. El gráfico ya no repite en su
  leyenda las casillas, que ya llevan el color de cada serie. Sus curvas ya no bajan de cero y su eje se detiene en los
  límites del periodo elegido. Sus fechas siguen el idioma de la página en lugar del del navegador (en una página
  francesa, «2 oct. 2025», no «Oct 2, 2025»). A partir de tres series, solo líneas en lugar de áreas que se solapan.
- **Permisos → Vista de matriz**: los módulos agrupados por sección, como en la barra lateral, en columnas. Estaban
  alineados como unas cuarenta baldosas grandes, sin orden.
- **El registro de auditoría, en páginas de 50**: alineaba 200 filas de un solo bloque, sobre más de 5000 píxeles.
- **Usuarios: una búsqueda** por nombre de usuario o correo, encima de la lista de miembros.
- **El gestor de archivos dice lo que hacen sus botones**: «Añadir archivos», «Crear una carpeta», «Mover», «Eliminar»,
  en lugar de cuatro pastillas de color con solo un icono. Sus diálogos tienen un «Cancelar» neutro y un botón de
  confirmación con nombre.
- **«Guardar» en el botón que confirma un cambio**, en todas partes. Veinte formularios decían «Editar» (foro, noticias,
  páginas, eventos, equipos, juegos, galerías…).

### Corregido

- **Algunas listas de la administración se detenían en su primera página**, sin enlace al resto: los miembros y los
  comentarios más allá de los 20 primeros, los premios y los reclutamientos más allá de los 10 primeros, así como «Mis
  suscripciones» del foro en el lado del miembro. El contador de la tarjeta solo daba la página mostrada («20 miembros»
  para 25).
- **Fechas introducidas en inglés y en alemán.** En inglés, el selector escribía el 2 de octubre como «10/02/2026 g:05
  A», rellenaba un evento fechado el 30/09/2026 como «06/09/2028», y el 2 de octubre se guardaba como el 10 de febrero.
  En alemán, una fecha mostrada como «02.10.2026» no se volvía a leer al guardar. Las fechas cortas en inglés se
  escriben ahora día/mes/año con un reloj de 24 horas, como en los demás idiomas («02/10/2026 14:05»); una prueba
  comprueba, en los seis idiomas, que una fecha mostrada se vuelve a leer.
- **Se acabaron las falsas alertas de «archivo dañado» antes de una actualización.** La Monitorización comparaba los
  archivos del sitio con la lista de huellas de la última versión publicada, incluso cuando el sitio aún no estaba a ese
  nivel: todo lo que cambia la nueva versión aparecía dañado (102 alertas en un sitio justo antes de pasar a la
  1.2.13). La comprobación espera ahora a que el sitio esté al día, y lo dice.
- **Estadísticas**: en alemán, el gráfico nunca cargaba; los botones «7 días», «30 días», «90 días» y «1 año» no hacían
  nada, en ningún idioma.
- **La fecha de modificación de una página del wiki** mostraba la hora de la última visita: el contador de visitas
  reescribía la fecha de modificación. Mismo defecto en los anuncios clasificados.
- **En el diálogo de eliminación del gestor de archivos**, «Cancelar» y «Eliminar» eran dos papeleras rojas idénticas.
  Los textos franceses del módulo habían perdido sus acentos («Dossier cree avec succes», «Element deplace») y contaban
  en «élément(s)».
- **El panel y el registro de auditoría** muestran lo que pasó («Modo de depuración activado», «Ajustes guardados») y ya
  no identificadores técnicos (`monitoring.debogage.allume`).
- **La página Comentarios de la administración** mostraba código en lugar de la fecha de cada comentario.
- **Un título acentuado** ya no muestra sus acentos como código HTML en la cabecera de las tarjetas de la
  administración.
- **En modo oscuro**, los textos de color de la administración (avisos, pastillas, mensajes de éxito en verde) se
  quedaban oscuros sobre fondo oscuro, casi ilegibles.
- **Las fechas de la moderación** (sanciones, denuncias, historial de un miembro), **de la papelera, de las copias de
  seguridad, de las notificaciones y del registro del bot de Discord** se muestran en el idioma y la zona horaria del
  visitante («21/09/2026 22:54»), y ya no tal como están en la base de datos («2026-09-21 20:54:11»).
- **En francés, la etiqueta de estadísticas «Connexions de membres»** (inicios de sesión de miembros) estaba mal escrita,
  «Connections de membres».
- **Las fechas siguen el idioma del visitante**: el wiki, el gestor de errores, los anuncios clasificados, el libro de
  visitas, el boletín, las conversaciones archivadas, el gestor de archivos y el widget de eventos las escribían fijas —
  tal como están en la base de datos («2026-09-20 22:54») o a la francesa incluso en alemán (se esperaba
  «02.10.2026»).

## [1.2.13] — 2026-10-02

### Añadido

- **El registro de errores, en la administración** (*Sistema → Monitorización → Registro de errores*). Ya no hace falta
  FTP ni acceso al servidor para saber qué ha fallado: los errores del sitio, agrupados, del más reciente al más
  antiguo, ordenados por gravedad, filtrables por periodo o por palabra. La ruta de instalación, las contraseñas, las
  claves y los tokens se ocultan en pantalla, las direcciones de correo e IP en parte; el archivo se puede descargar y
  vaciar (el antiguo se guarda al lado). La Monitorización señala los errores de las últimas 24 horas, y una carpeta
  `logs/` en la que el sitio ya no puede escribir.
- **Las herramientas de diagnóstico se activan desde la administración** (*Monitorización → Diagnóstico*), durante una
  hora, sin editar `config/neofrag.php` por FTP:
  - **el modo de depuración** — lo que muestra, la barra al pie de la página y el detalle de los errores, solo se
    enseña ya a los administradores conectados; activado, se mostraba a todos los visitantes, consultas y datos de
    visita incluidos;
  - **el rastreo de páginas**, que por fin se puede leer en la administración: las últimas páginas servidas, sus
    consultas y su duración, con los valores sensibles ocultos;
  - **la revisión de traducciones**, con la lista de textos que faltan por idioma y por extensión; la bandera que pone
    delante de los textos traducidos solo se muestra ya a los administradores.
- **La administración ordenada en nueve secciones**: *Contenido*, *Comunidad*, *Animación*, *Gaming*, *Conocimiento*,
  *Medios*, *Difusión*, *Soporte*, *Monetización* (y *Sistema*). Las seis anteriores mezclaban el calendario con los
  medios o el gestor de errores con la comunidad, y trece módulos acababan en «Otros módulos».
- **El menú «Navegación» del editor en vivo** sigue las mismas secciones, en submenús plegables, con una búsqueda;
  alineaba más de cuarenta páginas una tras otra.
- **La dirección del sitio se corrige desde la administración** (*Monitorización → Dirección del sitio*). Tras un
  cambio de dominio, los enlaces de los correos seguían llevando a la antigua hasta editar `config/url.php` por FTP.
- **Una referencia para cada error.** Un visitante que se topa con un error lee una referencia de ocho caracteres; el
  administrador la busca en el registro y llega a la línea correcta.
- **Las horas en la zona horaria de cada uno.** Un miembro elige su zona horaria en su perfil; si no, el sitio usa la de
  su navegador, y si no la del sitio, un ajuste nuevo en *Ajustes → Preferencias generales*. Las horas introducidas en
  los formularios se entienden en la zona horaria de quien las introduce. La lista de zonas horarias está agrupada por
  región, con las ciudades nombradas en el idioma del sitio.

### Corregido

- **Las horas ya no están desfasadas.** El sitio las mostraba en hora del servidor — hora universal — para todos: dos
  horas de retraso para un visitante en Francia en verano. La zona horaria del perfil del miembro, por su parte, solo
  se aplicaba en las páginas del perfil, y desplazaba las fechas que ese miembro guardaba. El calendario, el wiki, el
  gestor de errores, las conversaciones archivadas y el gestor de archivos escribían sus horas sin conversión; la
  emisión «en directo» de la radio web se leía en hora del servidor.
- **El calendario**: el inicio y el final de un evento se eligen en un selector de fecha, y ya no en un campo de texto
  con el formato «AAAA-MM-DD HH:MM:SS»; un título acentuado ya no muestra sus acentos como código HTML; la descripción
  con formato ya no muestra sus etiquetas; la exportación del calendario recibe un texto limpio.
- **Las listas con búsqueda** dicen «Sin resultados» en el idioma del sitio, y no «No results found».
- **El editor en vivo** ofrece ahora componer las páginas Foro, Galerías, Equipos, Contacto y Palmarés: su menú las
  dejaba fuera, porque no reconocía su página de inicio.
- **Una página que falla dice «Se ha producido un error» (500)**, con su referencia, en lugar de «Página no
  encontrada», que hacía pensar en una dirección equivocada.
- **Se acabaron las páginas en blanco.** Un error fatal, o un error lanzado fuera de una página, muestra una página de
  error en el idioma del visitante. Igual que una base de datos inaccesible (503), en lugar de un mensaje en bruto en
  inglés, y el corte queda por fin anotado en el registro.
- **Una acción que falla lo dice**: un diálogo que no se abre, un envío rechazado, un servidor que ya no responde
  muestran un mensaje, con la referencia del error — en lugar de un botón que se queda en gris sin decir nada. La
  Monitorización, cuando no logra actualizarse, detiene su reloj de arena.
- **La copia de seguridad y la actualización dicen la verdad.** Anunciaban «Copia de seguridad guardada» o «Actualización
  realizada con éxito» incluso tras un fallo; ahora solo anuncian el éxito si el servidor lo confirma, y si no dicen qué
  ha fallado. La copia de seguridad comprueba también que de verdad ha escrito su archivo: en una carpeta sin permiso de
  escritura o en un disco lleno, anunciaba un éxito — y la actualización, que se apoya en ella para volver atrás, no
  habría tenido nada que restaurar.
- **El error de un campo de formulario se muestra bajo el campo**, y ya no solo al pasar sobre un pequeño icono.
- Dos errores internos ya no dejan rastro en el registro: un formulario enviado sin uno de sus campos, y una consulta a
  la base de datos que no devuelve nada que leer — esta última hacía fallar la página.
- **La barra de depuración vuelve a mostrarse**: un valor numérico la hacía fallar desde que el código comprueba sus
  tipos de forma estricta, y su línea de tiempo era falsa.
- **El anuncio de actualización vuelve a verse.** Se había reducido a un «1.2.x» sin color en la barra superior: una
  limpieza de los estilos de la administración se había llevado los suyos. Recupera un recuadro bajo el logo —
  «Actualización disponible · NeoFrag X.Y.Z» — y una pastilla legible en la barra superior.
- **La ventana de actualización** mostraba un bloque vacío y dejaba una advertencia en el registro: ahora dice qué
  versión llega, que el sitio se respalda antes de empezar y vuelve a su estado anterior en caso de fallo, con un enlace
  a lo que trae la versión.

### Eliminado

- **`NEOFRAG_LOGS_DB`**, que nada leía: las instalaciones nuevas ya no lo escriben en `config/neofrag.php`. Una
  instalación existente puede conservarlo; no tiene ningún efecto.

## [1.2.12] — 2026-10-02

El bot de Discord pasa a la **versión 0.2.0**. Para actualizarlo: sustituye su carpeta por la del nuevo archivo,
conservando el archivo `.env`, ejecuta `npm ci --omit=dev` y luego reinícialo. Después crea una nueva clave de acceso
en *Administración → Discord* — la página lo pide — y sustituye la antigua en `.env`: sin ella, al bot le faltan los
permisos del gestor de errores. La configuración del servidor necesita además el permiso de Discord «Gestionar
canales»: vuelve a invitar un bot invitado con la 1.2.11 con el botón «Invitar al bot», o da ese permiso a su rol en los
ajustes del servidor.

### Añadido

- **Bot de Discord: funciones que se activan una a una.** *Discord → Funcionalidades* lista lo que sabe hacer el bot
  (lo declara él mismo). Cada una se activa, se desactiva y se configura desde la administración; el cambio se aplica en
  menos de un minuto, sin reiniciar el bot. El botón **Resincronizar** vuelve a ponerlo todo de acuerdo, y al arrancar
  el foro recupera lo que se escribió en Discord durante la ausencia del bot.
- **Bot de Discord: se pone al día con el sitio al volver.** Lo que se escribe en el sitio mientras el bot está apagado
  va a Discord cuando vuelve a arrancar, dentro de los 30 días de eventos que conserva el sitio. Hasta ahora volvía a
  empezar desde el último evento, y lo que se había escrito entretanto nunca llegaba a Discord.
- **Bot de Discord: la configuración del servidor.** Desde la administración, el bot crea en Discord una categoría, un
  canal Foro por cada foro elegido (con sus prefijos como etiquetas) y un rol por cada grupo elegido, pone él mismo las
  correspondencias, y reutiliza lo que ya existe en lugar de duplicarlo. Primero hay una vista previa, y la última
  configuración se puede deshacer.
- **Bot de Discord: `/forum`.** `/forum account link` da un enlace de un solo uso que vincula tu cuenta de Discord a tu
  cuenta del sitio; también puedes elegir pasar a tu nombre lo que habías publicado desde Discord.
  `/forum account unlink` la desvincula. Sin cuenta vinculada, `/forum visibility` deja elegir cómo se aparece en el foro: tu nombre de
  usuario de Discord, un nombre anónimo o un nombre de usuario a tu elección, modificable cada siete días — los mensajes
  ya publicados le siguen.
- **Bot de Discord: prefijos del foro ↔ etiquetas del canal Foro**, en ambos sentidos: poner un prefijo en un tema pone
  la etiqueta en el hilo, y al revés.
- **Bot de Discord: el gestor de errores en un canal Foro** (función que se activa en *Discord → Funcionalidades*).
  Cada ticket se convierte en un hilo: su tipo y su estado son sus etiquetas, que el bot crea en el canal y mantiene al
  día. El título, la descripción y la prioridad siguen; el hilo se archiva cuando se cierra el ticket, y un duplicado
  remite a su ticket original. Los comentarios pasan en ambos sentidos. Desde Discord, `/bug` e `/idee` abren un ticket
  a través de una pequeña ventana (título, descripción), y un hilo abierto a mano en el canal se convierte en un ticket
  — para un miembro que ha vinculado su cuenta. Los tickets aún abiertos reciben su hilo cuando se elige el canal. El
  sitio manda: una etiqueta cambiada a mano en Discord vuelve a ponerse como dice el ticket.
- **Bot de Discord: los roles temporales** (función que se activa). El comando `/role`, reservado a quienes pueden
  gestionar los roles, da a un miembro un rol durante un tiempo fijado (una sanción, un acceso de prueba, un rol de
  evento), lo retira o muestra los que están en curso. El bot lo retira al vencer, incluso tras un reinicio, y se lo
  devuelve a un miembro que sale del servidor y vuelve a entrar para librarse de él. Se rechazan los roles vinculados a
  un grupo del sitio, los roles gestionados por Discord y los roles situados al nivel del rol más alto del bot o de quien
  los da, o por encima. La página *Discord → Roles temporales* los lista, con «Quitar ahora».
- **API**: dos permisos nuevos, «Leer el Bugtracker» (`bugtracker:read`) y «Escribir en el Bugtracker»
  (`bugtracker:write`). Permiten a un programa leer los tickets (`GET bugtracker/tickets`, solo los abiertos con
  `open=1`) y sus comentarios, abrir un ticket en nombre de un miembro, comentarlo en nombre de un miembro o de una
  cuenta de Discord, y modificar o eliminar esos comentarios. El prefijo de un tema del foro también se puede cambiar
  por la API (`PATCH forum/topics/{id}`). El bot recibe los puntos de acceso `discord/*` de sus nuevas funciones: roles
  temporales (`discord/timed-roles`), configuración del servidor, vinculación de cuentas.

### Seguridad

- **Un título escrito a través de la API ya no puede inyectar código en las páginas del sitio.** El título de un tema
  creado por la API — por ejemplo el nombre de un hilo de Discord copiado por el bot — se guardaba tal cual, mientras
  que el foro muestra sus títulos sin volver a codificarlos: una etiqueta HTML en ese título se ejecutaba en la página
  del foro. La API guarda ahora sus textos como el sitio guarda los suyos, y el título de la pestaña siempre se codifica.

### Corregido

- **Los acentos del gestor de errores** se mostraban como `r&eacute;agit` en la página de un ticket abierto con el
  formulario, y la búsqueda «¿Ya informado?» no encontraba ninguna palabra con acento. Los textos que devuelve la API —
  nombres de usuario, títulos, descripciones, comentarios — están también en texto plano: el bot de Discord los copiaba
  codificados. Las vistas previas al compartir enlaces (Discord incluido) ya no muestran sus acentos como código HTML.
- **Gestor de errores**: elegir el estado «Duplicado» sin un número de ticket válido mantenía el estado antiguo mientras
  anunciaba «Ticket actualizado»; el mensaje dice ahora lo que no se ha aplicado.
- **Bot de Discord: eliminar en Discord la copia de un mensaje del sitio ya no borra el original.** Cuando el bot ya no
  tenía ese mensaje en memoria (tras un reinicio), eliminar en Discord la copia de un mensaje escrito en el sitio por un
  miembro con la cuenta vinculada borraba también el mensaje del sitio. En ese caso, el bot solo borra ya en el sitio
  los mensajes que venían de Discord.
- **El wiki**: en la página de inicio del wiki, una página de primer nivel sin subpáginas aparecía como una sección
  vacía; ahora muestra sus primeras secciones (o el principio de su texto) y un enlace para leerla, y la barra lateral
  la convierte en un enlace.
- **Las páginas no encontradas y prohibidas ya no son páginas vacías.** En todos los temas públicos, una dirección que
  no lleva a ninguna parte (404) o una página restringida (403) solo mostraban la cabecera y el pie de página, sin una
  palabra — un defecto heredado de NeoFrag. Ahora dicen qué pasa, en los seis idiomas, con un botón para volver a la
  página de inicio (al panel en la administración), y la pestaña del navegador dice «Página no encontrada» o «Acceso no
  autorizado» en lugar del nombre del módulo.

## [1.2.11] — 2026-10-01

### Añadido

- **El bot de Discord** (versión 0.1.0), que une un sitio y su servidor de Discord:
  - **roles y nombres de usuario** — un miembro que ha vinculado su cuenta de Discord recibe, en el servidor, los roles
    vinculados a sus grupos, y los pierde al salir de esos grupos; si la opción está marcada, su apodo en el servidor es
    su nombre de usuario del sitio. El sitio manda, y un rol al que no hay nada vinculado nunca se toca;
  - **foro ↔ canal Foro de Discord**, en ambos sentidos — un tema del sitio se convierte en un hilo, un hilo en un
    tema; las respuestas, las ediciones y las eliminaciones siguen. Los mensajes que vienen del sitio aparecen con el
    nombre y el avatar de su autor; los de Discord, con la cuenta del miembro vinculado o con su nombre de usuario de
    Discord. Canal por canal: sincronizarlo todo, o solo los hilos que un moderador marca con una reacción.

  Es un programa aparte (Node.js 22.9 o posterior, en una máquina siempre encendida), incluido en su propio archivo con
  cada versión. En su máquina solo guarda la dirección del sitio y una clave de acceso. Guía: «El bot de Discord» en el
  wiki.
- **El módulo Discord** (opcional, requiere el módulo API): la clave del bot, **guardada cifrada**, el servidor, el
  interruptor en marcha / pausa, el reinicio, el estado del bot (en línea, en pausa, desconectado) y su **registro en el
  idioma del administrador**, las correspondencias canal ↔ foro y grupo ↔ rol, y el enlace que invita al bot solo con
  los permisos que necesita — nunca «Administrador».
- **API**: el permiso `discord:bot` y los puntos de acceso `discord/*` que necesita el bot.

## [1.2.10] — 2026-10-01

### Añadido

- **Una API REST** (módulo **API**, opcional), para los programas que hablan con el sitio sin navegador — ante todo el
  futuro bot de Discord. Un administrador crea **claves de acceso** con solo los permisos deseados; una clave se muestra
  una sola vez, el sitio solo conserva su huella, y se revoca con un clic. Los puntos de acceso `/api/v1/…` devuelven
  JSON: el estado del sitio, un miembro (por ID o por cuenta de Discord vinculada), los grupos, el foro (árbol, temas,
  mensajes) y un **flujo de eventos** (nuevo tema, nuevo mensaje, cambio de grupo…). También **escribe** en el foro en
  nombre de un miembro, o de una cuenta de Discord no vinculada — publicada con su nombre de usuario de Discord, marcada
  con el logo de Discord. Limitada a 120 peticiones por minuto y por clave; los errores tienen códigos estables. Guía:
  «La API REST» en el wiki.
- **Mis cuentas vinculadas** (área de miembros): ver sus cuentas de Discord, GitHub o Google vinculadas, vincular una,
  desvincular una — salvo si es el único medio de iniciar sesión.
- **Registrarse con Discord** (o GitHub, Google): una cuenta que nadie ha vinculado crea un miembro, vinculado desde el
  principio, cuando el registro está abierto. Hasta ahora recibía un error «cuenta desconocida».

### Corregido

- **Vincular tu cuenta de Discord te conectaba a la cuenta de otro miembro** cuando ese Discord ya estaba vinculado a
  este último: la sesión pasaba a él. La vinculación se rechaza ahora, con un mensaje claro, y una misma cuenta externa ya
  no puede vincularse a dos miembros.

## [1.2.9] — 2026-10-01

### Corregido

- **Blog: el índice de una entrada seguía titulado «Sommaire» en los demás idiomas**, y dejaba una advertencia en el
  registro del sitio. Un mensaje de la administración de los miembros («Grupos del miembro actualizados») tenía el mismo
  defecto.
- **Blog: la pastilla de categoría se estiraba a todo el ancho de las tarjetas** en lugar de seguir siendo una pequeña
  etiqueta.

## [1.2.8] — 2026-10-01

### Añadido

- **Blog: las series.** Una entrada en varias partes: cada parte muestra la lista de las demás y su lugar («Parte 2 de
  3»), y la serie tiene su propia página. Las series se gestionan en la administración del Blog, junto a una nueva página
  que por fin lista las categorías.
- **Blog: una página por autor** (sus entradas) y **archivos por mes** en los que se puede hacer clic.
- **Blog: compartir muestra la portada.** Una entrada compartida en una red social muestra su portada y no el logo del
  sitio; los buscadores reciben sus datos estructurados (título, autor, fecha, imagen).
- **Seis widgets del Blog**: las últimas entradas con su miniatura, las más leídas, la entrada destacada, las
  categorías, las etiquetas y los archivos.

### Corregido

- **Foro: la lista de los foros desaparecía para los miembros conectados** en cuanto un foro tenía temas solo en sus
  subforos. Los visitantes no se veían afectados.
- **Darse de baja del boletín no daba de baja a nadie**: la página de baja fallaba con un error. Lo mismo al eliminar
  un suscriptor en la administración.
- **Eliminar tu cuenta** marcaba la cuenta como eliminada, y luego fallaba con un error antes de cerrar sus sesiones y
  borrar sus códigos de recuperación.
- **Desactivar la autenticación en dos pasos** fallaba con un error una vez desactivada, y dejaba los antiguos códigos
  de recuperación en la base de datos.
- **Blog: una categoría vacía no se podía eliminar** (el Blog siempre la creía en uso), y su eliminación no estaba
  protegida contra los enlaces falsificados. Ahora pide una confirmación.

## [1.2.7] — 2026-10-01

### Corregido

- **Foro: un foro-enlace muestra su propio icono.** El icono elegido en su formulario de administración sustituye ahora
  al globo que siempre mostraba.

## [1.2.6] — 2026-10-01

### Añadido

- **Foro: la respuesta «solución».** El autor de un tema (o un moderador) marca la respuesta que lo resuelve. Se
  destaca bajo la pregunta, se señala en el hilo, y el tema aparece como «Resuelto» en la lista. Una solución eliminada
  o movida no deja el tema resuelto.
- **Foro: los prefijos de los temas** («Pregunta», «Tutorial», «Importante»…), creados por el administrador con su
  color, y traducibles a cada idioma activo. Los miembros eligen uno al abrir su tema, y la lista de un foro se puede
  filtrar por ellos.
- **Foro: un icono por foro**, elegido en la administración.

### Corregido

- **El widget «Estadísticas» del foro** contaba también las categorías que el visitante no puede leer, y los mensajes
  eliminados: ahora solo cuenta lo que el visitante puede ir a ver.
- **Los datos de demostración del foro** contaban el primer mensaje como una respuesta («4 respuestas» en la lista
  para un tema que tiene 3).

## [1.2.5] — 2026-10-01

### Añadido

- **Un verdadero Blog.** El módulo Artículos se convierte en el Blog, en `/blog` (las antiguas direcciones
  `/articles/…` redirigen de forma permanente). La lista se abre con una entrada **destacada**, luego tarjetas
  ilustradas, filtros por categoría y una columna lateral (búsqueda, más leídas, categorías, etiquetas, archivos). La
  página de una entrada tiene una imagen de portada con su título, una barra de progreso de lectura, un índice que sigue
  la sección que se está leyendo, el autor, las entradas anterior y siguiente, y «Para leer a continuación». El
  administrador elige la disposición de la lista y de la página de entrada; los visitantes pasan de cuadrícula a filas,
  y su elección se recuerda.
- **Gestor de errores: la lista se puede filtrar por tipo** («Bug», «Petición de funcionalidad», «Pregunta», «Otro»), y
  «Nuevo ticket» conserva el tipo elegido. Un enlace a la lista filtrada (por ejemplo `/bugtracker?type=bug`) muestra
  los tickets ya señalados de ese tipo antes de abrir uno nuevo.
- **Gestor de errores: «¿Ya informado?»** Mientras se escribe el título de un nuevo ticket, debajo aparecen los tickets
  abiertos que se le parecen, para comentar el existente en lugar de abrir un segundo. Un ticket también se puede marcar
  como **duplicado** de otro: su página remite al original.

## [1.2.4] — 2026-10-01

### Añadido

- **Foro: categorías y foros traducibles.** Cada uno conserva su título por defecto y puede recibir, en su formulario de
  administración, un título por idioma activo (y, para un foro, una descripción). Los visitantes ven la traducción de su
  idioma, si no el título por defecto. Un enlace funciona tanto con el título por defecto como con cada traducción.

### Corregido

- **Una migración ya aplicada ya no bloquea la actualización.** Aplicada a mano sin quedar registrada, fallaba («column
  already exists») y habría anulado toda la actualización. Las migraciones se aplican ahora instrucción a instrucción,
  saltándose solo lo que ya está hecho.
- **Los módulos incluidos con el núcleo reciben por fin sus cambios de base de datos.** Solo la actualización de un
  addon a través del marketplace los aplicaba: un módulo como el foro o el calendario recibía su código nuevo con la
  actualización del núcleo, nunca su base de datos. Ahora se aplican junto con los del núcleo, con el botón como tras un
  envío por FTP.
- **Artículos**: los seis permisos del módulo (añadir, modificar, eliminar, y sus equivalentes para las categorías) no
  se comprobaban en ninguna parte. Ahora sí, acciones en lote incluidas.
- **El contenido programado ya no aparece antes de su fecha**, ni el contenido enviado a la papelera: el widget «Últimos
  artículos», el mapa del sitio enviado a los buscadores, la búsqueda en las noticias, la actividad de un miembro y el
  bloque «Últimos artículos» los mostraban. El widget Etiquetas de las noticias ya no mezcla los idiomas, y el widget de
  categorías ya no cuenta los borradores.

## [1.2.3] — 2026-10-01

### Corregido

- **La actualización por fin se actualiza a sí misma.** Su código — junto con el de la copia de seguridad, la
  restauración y el marketplace — vivía en la carpeta `install/`, que la actualización nunca reescribía: un sitio
  conservaba el del día de su instalación, y sus correcciones no le llegaban. Ahora vive en el núcleo
  (`neofrag/installer.php`), reescrito con cada versión, y la Monitorización comprueba su integridad. El resto de
  `install/` sigue también las versiones; solo el bloqueo `install/db.txt` sigue siendo propio del sitio. En un sitio
  existente, `install/` se actualiza a partir de la actualización siguiente a esta.
- **Un sitio que elimina su carpeta `install/` tras la instalación**, como se aconseja a menudo, conserva su
  actualización y su marketplace; sus mensajes se quedan entonces en francés.

## [1.2.2] — 2026-10-01

### Corregido

- **Los equipos, juegos, categorías, socios y premios ya no desaparecen en los demás idiomas.** La administración
  guarda un título solo en el idioma en que se escribe, y estos objetos solo existían en ese: en la demo, tres equipos
  en francés, ninguno en inglés. El grupo de un equipo, con los permisos que dependen de él, desaparecía con él. Ahora se
  muestran en el idioma pedido, si no en francés, si no en el que exista. Las listas que devolvían una fila por
  traducción (reclutamientos, partidos, flujo de actividad de la administración) ya solo devuelven una. Las listas de
  contenido (noticias, artículos, páginas) se quedan en el idioma pedido.
- **Una actualización aplica por fin los cambios de base de datos del núcleo.** Hasta ahora, solo la instalación los
  aplicaba: un sitio actualizado conservaba su base antigua bajo un código nuevo. Con el botón, se aplican durante la
  actualización, que se anula si fallan. Por FTP, se aplican en la primera página servida por el código nuevo. (Los
  cambios propios de los módulos siguieron en la 1.2.4.)
- **Inicio de sesión con Discord**: la dirección de un avatar animado (102 caracteres) superaba el espacio que la base
  de datos le reservaba (100), y el inicio de sesión fallaba; ese espacio pasa a 255 para todas las cuentas vinculadas.
  Un miembro de Discord sin avatar recibía una imagen rota.

### Cambiado

- **El envío de correos pasa a PHPMailer 7** (7.1.1). Su único cambio incompatible afecta a las clases que lo
  extienden, y NeoFrag no tiene ninguna. Probado con un envío real usando los ajustes de un sitio, por SMTP como con
  `mail()`.

## [1.2.1] — 2026-10-01

### Seguridad

- **Dos vulnerabilidades corregidas en la biblioteca Markdown** (`league/commonmark` 2.10.3): una denegación de servicio
  mediante tablas preparadas para ralentizar el servidor (gravedad alta, GHSA-3q6v-r5mr-hxv8), y una forma de saltarse el
  filtro que retira el HTML no permitido (gravedad moderada, GHSA-97jj-33gv-5xf9). Hechas públicas el 21 de septiembre
  de 2026. La biblioteca de limpieza de HTML (`ezyang/htmlpurifier` 4.19.1) pasa también a su última versión.

### Corregido

- **La Monitorización ya no señala archivos presentes como «ausentes».** Desde la publicación de la 1.2.0, comparaba el
  sitio con la lista oficial de archivos de la versión, pero excluía las fuentes Sass (archivos de estilo antes de la
  compilación) solo del lado del sitio: cuatro archivos señalados ausentes por error, y un estado de salud «¡El barco se
  hunde!».

### Cambiado

- **Una actualización del núcleo se anota en el registro de auditoría**: quién la lanzó, cuándo, de qué versión a qué
  versión, cuántos archivos se escribieron y cuántos archivos antiguos se retiraron. Hasta ahora se escribía en el
  registro de errores, donde parecía una anomalía.

## [1.2.0] — 2026-09-23

### Añadido

- **Las actualizaciones del núcleo, en un solo clic** (2026-09-23). A partir de esta versión, **Administración →
  Monitorización** señala las nuevas versiones de NeoFrag Reborn y las instala: copia de seguridad del sitio,
  verificación de la huella del paquete, aplicación del paquete, actualización de la base de datos, y vuelta a la copia
  de seguridad si algo falla. Es la primera versión publicada por este camino; se probó de principio a fin en un sitio
  nuevo antes de su publicación. Un sitio en la 1.1.0 aún no tiene este botón: pasa a la 1.2.0 a mano, sustituyendo sus
  archivos. Para los mantenedores: `tools/build-release.php` produce los tres archivos que se publican juntos
  (`neofrag-reborn-update-<v>.zip`, `version.json`, `checksum.json`); el procedimiento está en
  `docs/guide/marketplace.md`.

- **Los anuncios en Discord** (2026-09-23). Un webhook que apunta a un canal de Discord recibe ahora un verdadero
  mensaje, con título, enlace y color: nueva noticia, nuevo artículo, nuevo miembro, nuevo comentario, nuevo tema del
  foro. Hasta ahora Discord rechazaba lo que enviaba el módulo, aunque su descripción prometía Discord.

- **«Fulano está en directo»** (2026-09-23). Cuando un canal de Twitch o de YouTube que sigue el widget «Estado en
  directo» empieza a emitir, los webhooks suscritos al evento «Canal en directo» lo anuncian, una sola vez por emisión,
  con la miniatura de la emisión.

- **La demo habla también inglés en su contenido** (2026-09-23): sus noticias, artículos, páginas, categorías y galerías
  tienen una versión en inglés, que muestra el multilingüismo.

- **Todo el producto habla los seis idiomas** (2026-09-23). La administración, los módulos, los widgets, los temas, los
  ajustes, los botones, los mensajes y los scripts: cerca de 800 cadenas estaban escritas directamente en francés y se
  quedaban así en un sitio en inglés, alemán, español, italiano o portugués. Ahora todas pasan por las traducciones, en
  los seis idiomas. En particular:

  - **El asistente de instalación está traducido**, con un selector de idioma; al principio sigue el idioma del
    navegador. El instalador de línea de comandos acepta `--lang=en`, o el idioma del terminal.
  - **El marketplace** muestra el nombre y la descripción de cada addon en el idioma del sitio.
  - **Los países** (perfil, rivales) se muestran en el idioma del sitio.
  - **Los roles y las plantillas de correo incluidos** se muestran traducidos; un nombre cambiado por el administrador se
    queda tal como lo escribió el administrador.
  - **La moderación** muestra el estado, el motivo y el tipo de una denuncia, y el tipo de una sanción, escritos con
    todas las letras — mostraba los códigos (`pending`, `forum_message`, `ban_temp`).
  - **Un grupo creado en un idioma ya no desaparece** cuando el sitio se muestra en otro — salía de la lista, y sus
    permisos con él.
  - El tema de la administración se declaraba escrito en inglés: en un sitio en inglés, sus cadenas francesas nunca se
    traducían. Está corregido, igual que los scripts de los módulos, que buscaban sus traducciones en el lugar
    equivocado.
  - La confirmación de eliminación de la cuenta pide escribir la palabra en el idioma del sitio («ELIMINAR» en
    español), y ya no «SUPPRIMER» en todas partes.

  Una comprobación, `check-textes-en-dur`, rechaza ahora en la CI cualquier texto de la interfaz escrito en el código.

- **Los navegadores de los visitantes pueden conservar los archivos del sitio, si lo pides** (2026-09-22). El
  manifiesto ya hacía el sitio instalable; ahora el sitio puede conservar sus imágenes, estilos y scripts en los
  navegadores de los visitantes. **Desactivado por defecto**, se activa en **Administración → Ajustes → Preferencias
  generales** («Aplicación instalable»).

  **Las páginas, en cambio, nunca se conservan.** Una página guardada en memoria sobreviviría a un nuevo despliegue:
  a quien ya hubiera venido se le serviría un sitio de varios días, sin que nada lo indicara. Un cambio sigue así
  visible al instante.

  **Desmarcar desinstala de verdad.** Es la parte delicada de esta técnica: un componente de este tipo, una vez
  instalado en un navegador, se queda allí aunque el archivo se retire del servidor. El interruptor no retira, pues,
  nada — sirve una versión que se desinstala sola en la siguiente visita. Por eso la función está desactivada por
  defecto, y por eso una actualización del núcleo nunca la activa por su cuenta.

- **El sitio se puede instalar como aplicación** (2026-09-21). `/manifest.webmanifest` lo genera el propio sitio —
  nombre, descripción, color del tema, dirección de inicio y favicon vienen de los ajustes — en lugar de colocarse como
  un archivo, que sería falso en cualquier otro sitio.

- **Las carpetas sensibles también están cerradas con Apache** (2026-09-21). Los `.htaccess` del paquete protegían
  **menos** que la configuración de Caddy incluida: con Apache, el instalador seguía accesible una vez instalado el sitio
  (solo su propia comprobación le impedía ejecutarse), y en un sitio instalado desde el repositorio git, `docs/`,
  `tools/` y `tests/` también eran accesibles — la documentación se podía leer en texto plano. `cache/`, `config/`,
  `install/`, `tools/`, `tests/` y `docs/` tienen ahora cada uno su propia protección, la raíz bloquea también los
  archivos `.neon` y `.md`, y `tools/check-htaccess.php` vuelve a leer la lista en cada ejecución, con el motivo de
  cada línea.

- **Una dirección desconocida ya no escribe un error en el registro del sitio** (2026-09-21). El módulo `pages`, que
  recibe como último recurso las direcciones que nada más reconoce, escribía una línea de **error** por cada una —
  cada robot que sondeaba añadía algunas. Para los desarrolladores de addons: un controlador puede ahora declarar un
  rechazo como corriente (`Module_Checker::refus_ordinaire()`); el diagnóstico se sigue mostrando en pantalla en modo de
  depuración. La comprobación `check-journal` rechaza ahora toda línea que el propio producto escriba en el registro de
  errores.

- **Dos comprobaciones hacen respetar las reglas del repositorio** (2026-09-21), para los colaboradores.
  `tools/check-tools.php` verifica que cada herramienta sigue las reglas comunes: cabecera descriptiva, uso de la
  biblioteca común en lugar de código copiado, veredicto y códigos de salida estándar, puerto reservado, catálogo al día.
  `tools/check-docs.php` comprueba la documentación: cifras exactas, enlaces y anclas válidos, ningún documento
  huérfano, ninguna mención de una herramienta que ya no existe, ningún pasaje copiado de un documento a otro,
  documentos que siguen siendo legibles. Las dos se ejecutan en la integración continua.

- **Ocho addons nuevos, todos opcionales** (2026-09-20). Una actualización no los instala: en un sitio existente, se
  añaden desde **Administración → Temas y addons**, y se retiran del mismo modo. Una instalación nueva los incluye con
  el perfil *Completo*, ofrecido por defecto, pero no con los demás perfiles.

  | Addon | Lo que hace |
  |---|---|
  | **Efecto de temporada** (widget) | Nieve, confeti u hojas por toda la pantalla, durante un intervalo de fechas. Las fechas se escriben `MM-DD` **sin año** — la temporada vuelve sola — y pueden pasar por Año Nuevo (`del 12-15 al 01-06`). Fuera de temporada, la página no recibe **absolutamente nada**: ni imagen, ni script. Se respeta el ajuste del sistema que pide menos movimiento (`prefers-reduced-motion`), y la animación se detiene cuando la pestaña pasa a segundo plano. |
  | **Lector de canales** (widget) | Los últimos artículos de un canal RSS o Atom externo. **Una página casi nunca espera a un sitio ajeno**: lee una caché, e incluso sirve contenido caducado antes que hacer esperar a los visitantes; la actualización se hace en la tarea programada. Solo la primerísima visualización, con la caché vacía, espera al canal, tres segundos como máximo. Un canal caído se aparta diez minutos en lugar de volver a intentarse en cada visita. |
  | **Citas** (módulo) | Una colección clasificada, con autor y fuente. |
  | **Recetas** (módulo) | Ingredientes y pasos introducidos uno por línea, tiempos y número de raciones, marcado `schema.org/Recipe` que leen los buscadores. |
  | **Glosario** (módulo) | Un léxico ordenado por letra. «Éclaireur» va en la E, «Æther» en la A, «1v1» en #. La letra se calcula, nunca se escribe. |
  | **Mapa de lugares** (módulo) | Lugares en un mapa de OpenStreetMap, con dirección, descripción y enlace. La biblioteca del mapa la **aloja el sitio**; solo las teselas del mapa de fondo vienen de OpenStreetMap. **La página sigue siendo útil sin JavaScript** — la lista, las direcciones y los enlaces están ahí. |
  | **Radio web** (módulo) | Un reproductor de emisión, lo que suena ahora, y la programación de la semana. Un programa puede pasar de medianoche: «sábado 22:00 → 02:00» sigue siendo el programa del sábado. |
  | **Zona de pruebas** (módulo) | Solo para miembros: allí se prueba un formato y se ve la representación **exacta** del sitio. Sobre todo, muestra **lo que el sitio ha retirado** — es la única página que responde a «¿por qué ha desaparecido mi tabla?». |

  Los tres módulos de contenido (citas, recetas, glosario) comparten el patrón de la FAQ: categorías, búsqueda, filtro,
  orden y borradores.

- **Elegir el contenido en la instalación.** El instalador web ofrece ahora un **perfil del sitio** entre los requisitos
  y la base de datos: *Completo*, *Gaming / eSports*, *Comunidad* o *Solo el núcleo*. Los módulos se pueden seguir
  desmarcando uno a uno, y los que un módulo necesita se añaden automáticamente (el palmarés necesita los equipos), con
  un mensaje. Sin JavaScript, el formulario sigue funcionando — el servidor solo conserva lo que pertenece al perfil
  elegido.

  **Ninguna lista de addons se escribe a mano**: los perfiles se construyen a partir de las declaraciones `presets` de
  cada addon. Es la diferencia con el intento de junio de 2026, abandonado porque una instalación aligerada terminaba en
  un error del servidor (500): unos módulos del núcleo consultaban las tablas de módulos opcionales sin comprobar que
  existían. Esas comprobaciones existen ahora y se verifican en cada ejecución de la CI.

- **Cada módulo, widget y tema incluido declara su papel**: en su `__info()`, dice si pertenece al núcleo, de qué
  perfiles de instalación forma parte, y qué módulos necesita. La pertenencia al núcleo era hasta ahora un efecto
  secundario de estar en el paquete — así acabaron allí `emojis`, `files` y `webhooks` sin que se tomara ninguna
  decisión.

- **Tres nuevas salvaguardas de integración continua**:

  | Comprobación | Lo que rechaza |
  |---|---|
  | `check-addon-declarations.php` | un addon que no declara nada, una dependencia inexistente, **un addon del núcleo que depende de uno opcional**, un addon no distribuible publicado en el catálogo, una declaración en desacuerdo con `seed.sql` |
  | `check-addon-coupling.php` | un acoplamiento **fatal** (tabla, clase) ni declarado ni anotado; un ciclo duro. Leído en el código con el **tokenizador de PHP**, no con expresiones regulares — así que sin falsos positivos en los comentarios |
  | `check-install-profiles.php` | un perfil que no arranca. **Instala de verdad** en una base de datos desechable, sirve el sitio y falla al menor 5xx — también en las rutas de los módulos ausentes, que deben devolver un 404 limpio |

- **Encontrar a un miembro desde la barra de búsqueda.** La búsqueda del sitio solo encontraba contenido — foro,
  noticias, páginas. Buscar a alguien obligaba a abrir el directorio de miembros y recorrerlo, aunque es una de las
  cosas que más se buscan en un sitio comunitario. Los miembros aparecen ahora en la búsqueda global **y** en las
  sugerencias instantáneas, por nombre de usuario, nombre o apellido.

  No se expone nada nuevo de paso: estos tres campos ya se muestran en el perfil público de cada miembro, y la búsqueda
  aplica exactamente el mismo filtro que el directorio — una cuenta eliminada no aparece.

- **Gestionar una serie de eventos de una vez.** Crear un evento recurrente ya generaba todas sus repeticiones con un
  clic — volver sobre ellas obligaba luego a abrirlas una por una.

  Ahora, una repetición de una serie tiene un botón **«Eliminar toda la serie»**, cuya confirmación indica cuántas
  repeticiones se irán, y el formulario de edición ofrece **«Aplicar a todas las repeticiones de la serie»**. El título,
  el tipo, las descripciones, el lugar, la imagen y el estado de publicación se copian en cada una; **las fechas nunca**
  — son lo que distingue una sesión de la siguiente.

- **Deshacer una actualización y restaurar una copia de seguridad.** El CMS ya hacía una copia de seguridad completa —
  archivos y base de datos — justo antes de cada actualización del núcleo. No sabía volver a usarla: la red de
  seguridad estaba ahí, pero nadie sabía caer en ella.

  Ahora, si la colocación de los archivos o una migración falla, **el sitio vuelve automáticamente al estado en que
  estaba**; el mensaje dice a la vez qué falló y si la vuelta atrás tuvo éxito. Cada copia de seguridad de la lista
  tiene también un botón **Restaurar**, para los casos en que el daño no viene de una actualización — un módulo de
  terceros, una acción de la que uno se arrepiente. La confirmación indica lo que se perderá.

  Lo que la restauración **no** toca, a propósito: la configuración del sitio (volver a poner credenciales de base de
  datos caducadas cortaría el sitio de su propia base), los registros (son la memoria del incidente que se está
  reparando) y la caché (se vacía para que se reconstruya, en lugar de volver al estado de otra versión).

  De paso, la copia de seguridad incluye ahora `vendor/`, que el paquete de actualización incluye y que ella no
  incluía: sin él, una vuelta atrás habría puesto el código antiguo sobre las dependencias nuevas.

- **Responder a un comentario.** El botón «Responder», desactivado en el código original, funciona ahora: la respuesta
  se muestra sangrada bajo el comentario al que responde. Solo se puede responder a un comentario de primer nivel del
  mismo contenido que no haya sido eliminado (un nivel de respuestas); el servidor lo comprueba.
- **Para los creadores de temas: zonas llamadas por su nombre** (idea tomada de HiddenCMS). Una vista llama ahora a una
  zona por su nombre — `$this->output->region('content')` — en lugar de por su número — `zone(2)`. El tema declara la
  correspondencia entre nombres y zonas en su `__info()` (`regions`). La antigua llamada `zone()` sigue funcionando, y
  un tema sin esta declaración no cambia. Los cinco temas públicos (Nebula, Forge, Granite, Blockcraft, Extend) usan los
  nombres.
- **Instalar por línea de comandos** (`install/cli.php`, idea tomada de HiddenCMS): una alternativa al asistente web,
  práctica para instalar un servidor de forma reproducible o automatizada. Sus opciones cubren la base de datos
  (`--db-*`, `--create-db`), la cuenta de administrador (`--admin-*`), el nombre y la dirección del sitio
  (`--site-name`, `--site-url`), el idioma (`--lang`), los datos de demostración (`--demo`) y la ejecución (`--force`,
  `--yes`, `--dry-run`, `--no-lock`). Sin opciones, hace las preguntas una a una y oculta la contraseña al teclearla;
  `--admin-pass-env` lee la contraseña de una variable de entorno para que no aparezca en la lista de procesos. Usa el
  mismo código que el asistente web e instala todos los addons incluidos, como el perfil *Completo*.

- **Un lanzador para toda la batería**: `php tools/check-all.php`. Las comprobaciones no tenían un punto de entrada
  común — la CI las llama una a una, y en local cada uno escribía su propio bucle a mano, nunca el mismo. El lanzador
  descubre las comprobaciones presentes (una comprobación nueva se suma sin que nadie lo toque), ejecuta primero
  `composer audit`, limita la duración de cada comprobación, e indica al final **lo que no ha ejecutado**: las pruebas en
  navegador (`--navigateur`) y las comprobaciones con un objetivo explícito, entre ellas `check-restauration`, que daña
  el sitio a propósito.

- **Cada addon muestra cómo es** (2026-09-22). La página «Temas y addons» de la administración y el marketplace
  muestran una miniatura: una verdadera captura del módulo, widget, tema o idioma tal como funciona en la demo. Los 61
  addons del marketplace tienen una, en 960×600 para los módulos y los widgets. Para ello, se instalaron en la demo seis
  módulos y dos widgets con contenido de ejemplo — un glosario, citas, recetas, un mapa de lugares y una programación de
  radio web —, y los widgets que no muestran nada sin ajustes se capturaron con ajustes de ejemplo. Lo que no tiene nada
  que mostrar conserva su icono, como los conectores de Discord, GitHub y Google, cuyo icono es su logo. La demo lleva
  además por fin la identidad de su comunidad — nombre, tipo, fecha de creación y descripción —, que se había quedado
  vacía.

- **Diez addons tienen por fin una descripción** (2026-09-22): los seis idiomas y los cuatro addons de inicio de sesión
  externo (el de base y sus conectores Discord, GitHub y Google). Se muestra en el idioma del sitio.

- **Campos de perfil definidos por el administrador** (2026-09-20). El perfil de un miembro solo tenía campos fijos.
  **Administración → Usuarios → Campos del perfil** permite añadir los propios — nombre en el juego, plataforma,
  rango… —, de entre ocho tipos de campo. Los miembros los rellenan en el panel «Información adicional» de su perfil.
  Un campo es privado por defecto; una vez hecho público, se muestra en la página del miembro. Los valores se incluyen
  en la exportación de los datos personales del miembro y se eliminan con su cuenta.

- **La fuente del sitio se puede elegir en la administración** (2026-09-20). **Administración → Ajustes → Preferencias
  generales → Fuente del sitio** ofrece doce fuentes, que sustituyen la de cada tema. Por defecto, «Fuente del tema»
  conserva la fuente propia de cada tema y no llama a ningún servicio externo; las doce fuentes ofrecidas las sirve
  Google Fonts.

- **El historial de inicios de sesión ya no se guarda para siempre** (2026-09-20). Anota la dirección IP, el navegador
  y la fecha de cada inicio de sesión, y nada lo borraba nunca. Ahora se purga más allá de 395 días (trece meses), una
  duración que se ajusta en **Administración → Ajustes → Preferencias generales**; 0 desactiva la purga.

- **Recordatorios de los eventos seguidos del calendario** (2026-09-20). Un miembro que sigue un evento del calendario
  recibe un recordatorio antes de su inicio, como los participantes de un evento del módulo Eventos. El plazo se ajusta
  en la configuración del calendario («Recordatorio antes de un evento seguido», 24 horas por defecto, 0 para
  desactivarlo).

- **El paquete de actualización dice lo que protege y lo que retira** (2026-09-20). Lleva un archivo
  `nf-manifest.json`: las carpetas del sitio en las que una actualización nunca escribe (`config/`, `install/`,
  `upload/`, `logs/`, `backups/`, `cache/`), y los archivos que la versión retira. Hasta ahora la actualización deducía
  sola lo que había que eliminar; un paquete sin este archivo sigue aceptándose.

### Cambiado

- **Toda la interfaz habla Bootstrap 5** (2026-09-23). Quedaban unos 470 usos de clases de Bootstrap 3 y 4, mantenidos
  en funcionamiento por una hoja de estilos de compatibilidad: campos de formulario, pastillas de color, botones de
  cerrar, botones a todo el ancho, bloques «avatar y texto», grupos de entrada, casillas, rejillas de formulario. Todo
  el marcado está migrado a sus equivalentes de Bootstrap 5; lo que es propio del producto lleva ahora un nombre propio
  del producto. Una comprobación rechaza cualquier nombre antiguo que vuelva, incluso redefinido. Lo que se ve:
  - las **listas desplegables** recuperan su flecha — un centenar, en la administración y en el sitio, parecían campos
    de texto;
  - las **pastillas de color** (estados, grupos, roles, sanciones) son legibles en todos los temas, en claro como en
    oscuro: su texto suspendía la comprobación de contraste;
  - los grupos **«Moderador» y «Moderador sénior»** se muestran: su nombre se escribía en blanco sobre fondo blanco;
  - los **botones de cerrar** de los diálogos tienen una zona de clic suficiente en los teléfonos.

- **El JavaScript lo revisa un linter** (2026-09-22). Setenta y un archivos del proyecto escritos a lo largo de los
  años, sin que nada los comprobara más allá de su sintaxis y de la ausencia de jQuery. ESLint ve lo que la sintaxis no
  dice: una variable global creada por olvidar un `var`, un `eval` disfrazado, código después de un `return`, un
  `innerHTML` alimentado por un valor calculado. No se despliega ningún paquete npm: es una herramienta de desarrollo,
  igual que el análisis estático de PHP.

  Las reglas se eligen con un único criterio — haber mordido ya aquí. Es la familia de «`$` is not defined», que había
  dejado sin funcionar la ordenación de tablas en toda la administración durante tres meses. El desorden existente (usos
  de `innerHTML` que sanear) se **congela tal cual** en lugar de reescribirse de golpe: su número ya no puede subir.

- **La línea de base de PHPStan se regenera con una orden, y la clase `Config` del núcleo está anotada** (2026-09-21).
  Tres errores nuevos llevaron a reabrir `phpstan-baseline.neon`: las entradas añadidas a mano estaban escritas de otra
  forma que las del propio PHPStan, y cerca de trescientos bloques ya no correspondían a nada — toda una familia
  «Function NeoFrag not found», muerta desde que el archivo de arranque declara la función. La configuración vive en
  `phpstan-base.neon`, `phpstan.neon` solo le añade la línea de base, y `composer stan:baseline` la regenera entera.
  `Config` anota ahora el idioma actual, la lista de idiomas y los ajustes del núcleo que leen los módulos: estos accesos
  se analizan en lugar de quedar congelados.

- **Las herramientas de `tools/` rehechas sobre una biblioteca común** (2026-09-21). Una sesentena de herramientas,
  escritas cada una a su manera, copiaban la misma fontanería: arrancar un servidor PHP, abrir una sesión de
  administrador, lanzar Chrome, leer `config/db.php`. Una lección aprendida en una no llegaba a las demás. `tools/lib/`
  lleva ahora esa base — conexión, sesión, servidor, navegador, recorrido del repositorio, SQL, opciones, veredictos —,
  y cada herramienta se reduce a su propia lógica. Quedan cincuenta y dos, sin pérdida de funciones: `check-js-syntax` y
  `check-js-jquery` forman `check-js-sources`; `check-docs-counts` y `check-docs-liens` forman `check-docs`;
  `check-lang-args` se integra en `check-langs`; `seed-wiki-docs` y `dump-wiki` forman `wiki-docs`; `smoke-test` pasa a
  ser `check-smoke`, como toda comprobación; `addons-manifest` y `table-map`, que son datos, viven en la biblioteca. Cada
  herramienta tiene ahora su propio puerto reservado (varias compartían uno), tres códigos de salida que distinguen
  «nada que señalar», «rechazado» y «no ha podido juzgar», y una cabecera a partir de la cual se genera el catálogo de
  `tools/README.md`. `bs5-codemod`, la herramienta de la migración de Bootstrap 4 a 5 de junio, se retira:
  `check-classes-bs4` cubre todo lo que comprobaba, incluidos los puntos de ruptura de las utilidades direccionales.

- **`declare(strict_types=1)` en todo el ámbito útil** (2026-09-21): **1365 archivos** frente a 78 el día anterior. El
  trabajo avanzaba en pequeños lotes desde junio; está terminado. Los 209 archivos restantes son las plantillas
  `views/**.tpl.php`, donde la declaración sería sintácticamente válida y no protegería nada.

  La conversión se hizo por orden de **riesgo decreciente** — el número de llamadas a funciones nativas por archivo —,
  con la batería de pruebas pasada entre cada lote.

  Una consecuencia de fondo, propia de este producto: PHP en modo estricto **rechaza `__toString()` para las funciones
  internas**. Ahora bien, `lang()` devuelve un objeto de traducción *diferido*, resuelto en el momento de mostrar, cuando
  el idioma por fin se conoce, y las fechas también son objetos. La barra de la administración, la página de cada
  noticia y varias pantallas fallaban, pues, con un error fatal — módulo fuera de combate, página truncada, sin decir
  nada. La conversión se hace ahora en el **punto de escape** (`htmlspecialchars`), es decir, allí donde el valor debe
  convertirse en cadena por definición: cerca de 500 lugares en 86 archivos.

  La oleada exigió también once conversiones explícitas, tres de ellas en caminos que se toman en cada página — el orden
  de presentación de los idiomas y de los grupos, leído al empezar cada sesión, y el cálculo del token que protege cada
  formulario contra las peticiones falsificadas (CSRF): sin ellas, un sitio con dos idiomas activos habría devuelto un
  error 500 en todas sus páginas.

- **El registro dice ahora DÓNDE, no solo qué** (2026-09-21). Cuando un módulo lanza una excepción, su representación se
  abandona y la página se muestra truncada — en silencio. La línea del registro daba el mensaje sin nombrar nunca un
  archivo. Ahora nombra el primer lugar del código propio del producto en la pila de llamadas. Encontrar el origen de los
  errores anteriores pasó de ser una investigación a una simple lectura.

- **El compilador SCSS pasa a la 2.1** (2026-09-20). En PHP 8.5, cada compilación de estilos escribía **diez avisos de
  obsolescencia** en el registro; ya no queda ninguno. La 2.x minimiza un poco más (`.25rem` en lugar de `0.25rem`,
  `#ccc` en lugar de `#CCCCCC`), lo que cambia tres hojas de estilos — las de los módulos addons y palmarés y la de la
  página de mantenimiento. La equivalencia no se dedujo del texto: los dos juegos de CSS se dieron al motor de Chrome,
  que ve en ellos exactamente las mismas reglas, propiedades y valores calculados.

- **La documentación se ha releído entera frente al código** (2026-09-17). Las guías de instalación, de conceptos y de
  desarrollo (crear un módulo, un widget, un tema, el framework) databan de junio y describían el CMS de antes: una
  instalación sin elección de perfil, un tema que cargaba jQuery, rejillas de Bootstrap 4. Ahora dicen lo que hace el
  producto — perfiles de instalación, declaraciones de los addons, regiones con nombre, front end sin jQuery bajo una
  política de seguridad estricta, ajustes de widget con valores de respaldo, vocabulario de colores compartido. El
  README, la guía de contribución, la política de seguridad y la referencia técnica están alineados igualmente.

- **Lo que permite un addon se lee en su declaración**, ya no en listas de nombres escritas en el código. Tres de ellas
  decidían la suerte de los addons, y las tres eran falsas: la lista de los widgets protegía siete nombres que no son
  widgets; la de los temas protegía un tema `default` inexistente **dejando `nebula`, el único tema público del núcleo,
  eliminable** en cuanto estaba inactivo; la de los módulos duplicaba, en desacuerdo, lo que ya decía `__info()`.
  Comprobado addon por addon frente a la lógica antigua: ningún cambio en lo que se puede desactivar, ningún cambio de
  estado activo, y **39 addons pasan a no poder eliminarse** — que es el objetivo de la corrección.

- **La Papelera ya no conoce a los demás módulos.** Llevaba escrita en el código la lista de las tablas, claves
  primarias y métodos de restauración de `news`, `articles`, `gallery`, `comments` y `forum`: no podía, pues, pertenecer
  al núcleo sin arrastrar consigo cuatro módulos opcionales — `comments` pertenece él mismo al núcleo. Cada módulo
  declara ahora sus tipos restaurables; el núcleo los recoge. Misma inversión para los descriptores de contenido
  (reacciones, suscripciones, revisiones).

- **La lista de los addons del núcleo y opcionales ya no se mantiene a mano.** `tools/lib/addons-manifest.php`, una tabla
  escrita a mano y congelada en junio, se deduce ahora de las declaraciones. Las dos ya se habían separado: `emojis` estaba
  listado como núcleo cuando se declara a la carta — se podía desmarcar en la instalación pero faltaba en el catálogo, y
  era por tanto **imposible de reinstalar**. Catálogo: 52 → **53 addons** ese día (61 al salir la 1.2.0).

- **El instalador, rehecho visualmente**: disposición en dos columnas, tokens de estilo tomados de `nebula` (azul marino
  y verde azulado, acento `#2dd4bf`), degradado sacado de detrás del texto, logo puesto en una pastilla.

### Corregido

- **La copia de seguridad de la Monitorización vuelve a funcionar** (2026-09-23). Desde el 21 de septiembre, se detenía
  en la primerísima fila de la base de datos: el botón «Iniciar copia de seguridad», y la actualización del núcleo, que
  empieza por una copia de seguridad, fallaban en todos los sitios. Encontrado al probar la actualización antes de
  publicarla.

- **Cambiar de idioma en una noticia traducida ya no lleva a una página no encontrada** (2026-09-23). El selector de
  idioma conserva la dirección, y con ella el título en el idioma anterior: la versión traducida lo rechazaba. Ahora
  redirige a su propia dirección.

- **Una página traducida se muestra en el idioma correcto** (2026-09-23). Las páginas públicas se servían en la versión
  que la base de datos devolvía primero, fuera cual fuera el idioma del visitante.

- **El tema elegido por un visitante ya no se extiende a otro sitio del mismo dominio** (2026-09-23). La elección se
  recordaba para todo el dominio: un visitante que pasaba un sitio a Forge veía también en Forge otro sitio instalado en
  el mismo dominio. La elección se recuerda ahora **por cada sitio por separado**, y un ajuste nuevo, **Preferencias
  generales → Elección del tema**, permite desactivarla: el menú desaparece y el tema por defecto se aplica a todos.

- **La demo vuelve a restablecerse** (2026-09-23). Un error en su conjunto de datos hacía fallar el restablecimiento de
  cada cuarto de hora desde la víspera: lo que cambiaban los visitantes allí se quedaba. Una comprobación rechaza ahora
  este defecto antes de que nada se publique, y un restablecimiento fallido se escribe en el registro en lugar de pasar
  desapercibido.

- **La cuenta de emergencia de la demo ya no aparece en ninguna parte** (2026-09-23): ni en la lista de miembros, ni en
  la búsqueda, ni entre los miembros en línea, ni en la administración; su página responde «no encontrado».

- **El sitio en un teléfono** (2026-09-23), a partir de capturas tomadas en un teléfono:

  - **El menú siempre está al alcance.** En el tema Nebula, desaparecía por debajo de 860 píxeles de ancho sin ningún
    botón para volver a abrirlo: ahora lo abre un botón «hamburguesa». En los demás temas, un menú demasiado largo para
    una línea se repliega tras un botón «Menú», en lugar de pasar de línea hasta llenar la pantalla; un menú corto sigue
    desplegado.
  - **Los bloques apilados ya no se tocan**: el área de miembros estaba pegada al bloque de encima.
  - **El texto ya no se pega a los avatares de los miembros** (comentarios, conversaciones, autor de una noticia,
    candidaturas, perfil).
  - **Los puntos del carrusel** son verdaderos botones, centrados bajo la imagen, y siguen siendo **claros** en los temas
    oscuros, como las flechas y la leyenda: Bootstrap los volvía negros.
  - **La búsqueda del tema Extend** conserva su botón junto al campo.
  - **Las fotos de un álbum** llenan su tarjeta.
  - La cabecera de los comentarios muestra el título antes del contador, que antes lo precedía.
  - Las fechas abreviadas se escriben en el idioma del sitio («30 août 2026», «Sonntag»), y ya no en inglés; la línea del
    autor de una noticia recupera un tamaño de texto normal.

- **La página de una foto de la galería existe** (2026-09-23). El widget «Imagen aleatoria», el carrusel de la galería y
  el enlace de un comentario escrito sobre una foto llevaban todos a una página no encontrada: nunca se había declarado.
  Muestra la foto, su descripción, el camino de vuelta al álbum y los comentarios. Una foto sigue además las reglas de su
  álbum: ya no hay acceso por su dirección cuando el álbum está en la papelera o aún no está publicado.

- **Los últimos restos de Bootstrap 3 y 4** (2026-09-23), que sus nombres de clase no delataban: la estructura del
  carrusel, las cruces de cerrar que mostraban una «×» encima del icono, dos acordeones (FAQ, enlaces de navegación)
  todavía construidos a la manera de Bootstrap 4 (el de la FAQ se abría, pero sin el aspecto ni la flecha del
  componente), campos que se envolvían en un grupo vacío, la flecha de las burbujas que se quedaba blanca bajo una
  burbuja oscura, y dos reglas del widget de los socios. `check-classes-bs4` lee ahora también la ESTRUCTURA de los
  componentes y los estilos escritos en las vistas.

- **Páginas de error que no lo eran** (2026-09-23). En la moderación, y en la gestión de los roles, las plantillas de
  correo, los miembros y el carrusel, una dirección que apuntaba a un elemento retirado debía responder «página no
  encontrada»: llamaba a una función que no existe, escribía una advertencia en el registro, y la página de moderación se
  mostraba vacía. Encontrado al enseñar al análisis estático las funciones «mágicas» del framework — lo que quitó 455
  apariciones de su lista de excepciones.

- **El botón de los correos enviados a los miembros es legible** (2026-09-23): la validación de la cuenta, la contraseña
  perdida y las demás plantillas tenían un botón blanco sobre turquesa claro (contraste de 2,3:1). La corrección se
  aplica a las plantillas incluidas y a las ya instaladas, salvo las que un administrador ha personalizado.

- **Últimos retoques de presentación** (2026-09-23): los enlaces del wiki en el tono legible del tema; los botones de
  «éxito» en los temas oscuros; el estado de una candidatura, cuyo recuadro no tenía fondo (clases de un antiguo tema de
  administración); las redes sociales en las tarjetas de miembro; los títulos truncados en la administración; un
  visitante que sigue el enlace para participar en un evento recibe «Acceso no autorizado» en lugar de una página no
  encontrada.

- **El texto es legible en todas partes, en modo claro como en modo oscuro** (2026-09-23). Los enlaces y botones de la
  administración, los avisos, las fechas y las cabeceras de tabla de los temas, las categorías de las noticias, la
  página del marketplace en los temas claros, el botón «Aceptar todo» del banner de cookies, las pastillas de
  reputación y de tipo de evento: todos alcanzan ahora el contraste mínimo recomendado (4,5:1). En modo oscuro, la
  administración tiene sus propios colores de estado, y el panel «Salud del sitio» y la zona de subida de imágenes de la
  galería siguen al tema.

- **La administración en un teléfono** (2026-09-23). La barra superior pasa a dos líneas — la miga de pan completa, y
  luego los botones — en lugar de truncar el título de la página; los objetivos pequeños (icono de inicio, contadores)
  son lo bastante grandes para un dedo; la cabecera de las categorías del foro ya no se desliza bajo sus botones.

- **El foro en un teléfono** (2026-09-23): en un tema, el autor pasa encima del mensaje en lugar de dejarle solo 200 px
  de ancho, y los botones de un mensaje ya no tapan su fecha.

- **Los plurales traducidos** (2026-09-23). 47 cadenas habían perdido su forma plural en la traducción: un sitio en
  inglés mostraba «3 topic», «5 image», «2 year». El contador de las categorías del foro, escrito en francés en todos los
  idiomas, está ahora traducido.

- **La documentación del sitio está al día** (2026-09-23). Las diez páginas del wiki incluidas con el producto y las de
  la demo llevaban una semana de retraso respecto a las guías; «Crear un widget» todavía enseñaba una clase de
  Bootstrap 4. Una comprobación las compara ahora con las guías.

- **El formulario de respuesta del foro cabe en un teléfono** (2026-09-23): la lista de los tipos de adjuntos
  permitidos, escrita sin espacios, formaba una sola palabra demasiado ancha para la pantalla.

- **Todo el sitio revisado, en cada tema, cada modo y cada ancho** (2026-09-23). Una comprobación automática muestra
  ahora cada página de la administración en su tema y cada página pública en cada tema público, en claro y en oscuro,
  conectado como administrador y como visitante, en ocho anchos de 360 a 2560 px, con y sin contenido. Esta primera
  pasada ha corregido:
  - **iconos rotos** en las listas de categorías de la galería, los juegos, los equipos y las noticias, cuando una
    categoría no tenía icono;
  - el **país de los perfiles** de la demo, escrito con todas las letras: el perfil no mostraba ningún país y buscaba una
    bandera que no se encontraba;
  - los **asuntos de las plantillas de correo**, que mostraban `{{site_name}}` tal cual en la lista;
  - **tablas demasiado anchas para un teléfono** (gestor de errores, descargas, historial del wiki, moderación,
    sesiones, participantes de un evento, palmarés): ahora se desplazan dentro de su marco en lugar de ensanchar toda la
    página;
  - las **listas del foro** en un teléfono: cuatro columnas se repartían 360 px y el último mensaje se cortaba; cada
    entrada se apila ahora — icono y título, y luego estadísticas y último mensaje;
  - la **barra del tema Nebula** en un teléfono, que desbordaba la pantalla;
  - tres archivos pedidos por páginas sin existir, y dos líneas escritas en el registro en cada comprobación de
    actualizaciones mientras no hay ninguna versión publicada.

- **Los botones «Modificar» y «Eliminar» ya no están desalineados** (2026-09-22). En las listas de la administración a
  dos columnas — categorías de las noticias, categorías de la galería… — el segundo botón pasaba a la línea siguiente,
  desplazado bajo el primero. Ahora se quedan uno al lado del otro.

- **La tarjeta «Almacenamiento» de la monitorización se muestra bien** (2026-09-22). El indicador se dibujaba como un
  círculo completo en lugar del semicírculo previsto, con una cifra en bruto en su centro, y el valor y el porcentaje,
  colocados arriba, se solapaban con el trazo; ahora se sitúan en el hueco del indicador. El título «Información del
  servidor» de la misma página pasa a dos líneas cuando la columna es estrecha, en lugar de cortarse. En el grupo «Envío
  de correo», la línea «Transporte de correo» mostraba `[object Object]` en lugar del método usado.

- **Un widget colocado sin ajustes se muestra con sus valores por defecto** (2026-09-22). Cuando un tema coloca un widget
  en la instalación, o una disposición antigua lo restaura, llega sin ajustes: escribía entonces advertencias en el
  registro en cada página mostrada, y el menú de navegación simplemente desaparecía. Ahora recibe los valores que su
  formulario habría guardado por defecto. Los widgets ya configurados no cambian.

- **La página de los eventos vuelve a mostrarse** (2026-09-22). Mostraba una página de error «no encontrado» en cuanto
  tenía eventos que repartir en varias páginas — con un título perfectamente normal, lo que la hacía difícil de notar. El
  ajuste del número de eventos por página se guardaba como texto, y el código, hecho más estricto la víspera, lo
  rechazaba. Todas las listas paginadas aceptan ahora este ajuste en sus dos formas.

- **Los votos de las encuestas se registran** (2026-09-22). El votante leía «¡Gracias por tu voto!», y el voto nunca se
  contaba: ninguna opción se reconocía como perteneciente a la encuesta. Un voto que no apunta a ninguna opción de la
  encuesta se rechaza ahora con un mensaje que lo dice, en lugar de ser bien recibido.

- **El widget «Foro» muestra por fin temas y mensajes** (2026-09-22). Sus dos tipos, «Últimos temas» y «Últimas
  publicaciones», no seleccionaban ninguna categoría del foro, y por eso se quedaban vacíos en todos los sitios, hubiera
  los mensajes que hubiera.

- **Otras tres funciones caían en la misma trampa que las encuestas** (2026-09-22), encontradas por la comprobación
  escrita para este defecto:
  - **abrir una candidatura con campos personalizados** hacía fallar la página, del lado del candidato como en la
    administración;
  - **una notificación que enlaza a un evento del calendario** fallaba al construir su enlace;
  - **la profundidad máxima de las respuestas del foro** nunca se aplicaba: el cálculo se detenía en el primer nivel.

- **Volver a la página 1 de una lista ya no lleva a una página no encontrada** (2026-09-22). En las listas de la
  administración cuyo número de elementos por página se puede ajustar, el botón «1» construía una dirección que el sitio
  no reconocía.

- **La pantalla de edición de un campo de perfil personalizado se abre** (2026-09-22). La pantalla fallaba cada vez que
  se abría: no daba etiqueta a su botón de guardar.

- **Dos botones «Volver» llevaban a una página no encontrada** (2026-09-22): desde la página de una imagen, hacia su
  álbum; desde un modo de juego, hacia su juego.

- **«Postular» ya no aparece donde la candidatura sería rechazada** (2026-09-22): en una oferta cerrada o completa, y
  para quien no tiene derecho a presentarse. Del mismo modo, un administrador que lee una conversación reservada al
  equipo puede ahora invitar a ella y denunciar un mensaje en ella, en lugar de ser rechazado tras haber visto el botón.

- **Los submenús del widget de navegación se abren** (2026-09-22). Un enlace que agrupa otros enlaces no desplegaba
  nada al hacer clic: seguía llevando el atributo de la versión antigua de Bootstrap, que su propio script ya no buscaba.

- **Las demás burbujas de ayuda de los formularios aparecen al pasar el ratón** (2026-09-22). La corrección de la víspera
  solo cubría una parte de los formularios: el icono (i) junto a los campos de las demás pantallas de la administración,
  y las burbujas de las etiquetas, seguían sin mostrar nada — el mismo defecto de atributo, en las dos bibliotecas que
  los construyen.

- **Las etiquetas de menú con acentos se traducen** (2026-09-22). Un enlace de menú guardado desde la administración —
  «Actualités», «Équipes» — se almacena codificado, y su traducción ya no se encontraba: se quedaba en francés en los
  otros cinco idiomas, con una advertencia en el registro en cada página mostrada.

- **Los archivos del marketplace son reproducibles** (2026-09-22). Llevaban los mapas de fuente `.map`, que el
  compilador de estilos regenera en cada instalación y que el repositorio ignora: dos archivos del mismo addon,
  construidos en dos máquinas, diferían aunque el código no hubiera cambiado. Los archivos ya no llevan estos ficheros.

- **El widget «Palmarés» se puede instalar por fin subiendo un archivo** (2026-09-22). Era el único de los 61 addons
  distribuibles que no declaraba su dependencia del núcleo; el instalador, que la exige para reconocer un addon, se lo
  saltaba SIN DECIR NADA — ni mensaje, ni rastro.

- **La página de mantenimiento ya no está en blanco** (2026-09-22). Los dos campos «Título» y «Contenido» se incluyen
  VACÍOS, así que la página no mostraba NADA más que el nombre del sitio — aunque la vista previa de la administración
  prometía un título y un texto. Un título y un mensaje por defecto, traducidos a los seis idiomas, llenan el hueco; en
  cuanto se rellenan los campos, son ellos los que se muestran.

- **Los botones de contorno recuperan su borde** (2026-09-22). Los temas solo redefinían la variante «primary» del botón
  de contorno, y su propia regla borraba el borde de las demás: las otras seis variantes usadas por el producto — 90 usos
  en el código — parecían simples enlaces. El más visible era «Abrir el sitio», en el banner de mantenimiento, que solo
  tenía borde al pasar el ratón.

- **El código técnico ya no es rosa en la administración** (2026-09-22). El tema solo fijaba la fuente de los fragmentos
  `<code>`, que conservaban por tanto el rosa de Bootstrap — un color ajeno a todas las paletas del producto. Muy visible
  en la lista de los roles.

- **El botón «Cerrar» de las ventanas cierra por fin la ventana** (2026-09-22). En TODAS las ventanas del producto, el
  botón «Cerrar» (o «Cancelar») de abajo no hacía nada: hacía falta la cruz de arriba a la derecha. La causa: Bootstrap 5
  renombró el atributo que desencadena el cierre, y el núcleo seguía poniendo el nombre antiguo; un navegador ignora un
  atributo desconocido **sin decir nada**. El botón también se ha convertido en un verdadero botón — se representaba como
  un `<span>`, y era por tanto inaccesible con el teclado. Dos salvaguardas impiden que vuelva: la comprobación del código
  rechaza el nombre antiguo, y una pasada en un navegador, lanzada a mano, HACE CLIC en el botón.

- **El tema Nebula ya no muestra su menú dos veces** (2026-09-22). Cada instalación nueva mostraba dos veces el nombre del
  sitio y dos veces el menú: el tema dibuja su propia barra, y la instalación colocaba ADEMÁS un título y un menú justo
  debajo. El menú vive ahora en la barra superior, donde solo aparece una vez — y es **configurable** desde la
  administración, lo que antes no ocurría: los enlaces de la barra estaban escritos en el código, así que faltaba
  «Contacto» y un módulo desactivado conservaba su enlace. Los sitios ya instalados se corrigen con una migración, que
  solo toca la cabecera si se ha quedado como se incluyó.

- **Cambiar de idioma ya no lleva a una página de código** (2026-09-22). El selector de idioma del pie de página — el de
  **cinco temas**, entre ellos el tema por defecto — enviaba al visitante a una página que mostraba
  `{"redirect":"/en/…"}` como texto en bruto, en lugar de llevarlo a la página traducida. Había que volver atrás para
  salir.

  La causa: este selector es un verdadero formulario, que nada interceptaba, y apuntaba a una dirección técnica que
  siempre responde en JSON. El servidor distingue ahora una llamada de la ventana «Elegir mi idioma», que espera JSON, de
  un envío de formulario corriente, que espera ser llevado a la página. Encontrado siguiendo el gesto en un navegador
  real, cosa que ninguna comprobación había hecho hasta entonces.

- **El contenido escrito en un solo idioma ya no envía los demás idiomas a una página de error** (2026-09-21). Una
  noticia escrita en francés seguía ofreciendo los otros cinco idiomas en su selector, y los cinco respondían 404:
  medido en la demo, **60 direcciones muertas de 72**. El gesto más natural de un visitante extranjero llevaba a un
  error. Ahora se sirve la versión que existe, con un aviso que lo dice en el idioma del visitante y un enlace al
  original. Esto se aplica a las noticias, los artículos y los álbumes (y sus categorías), así como a los equipos.

  Dos precauciones inseparables, sin las cuales el remedio costaría más que la enfermedad: la página solo anuncia ya a
  los buscadores los idiomas que **existen de verdad**, y designa el original como dirección canónica — si no, se
  indexarían seis direcciones para un solo texto. **En la administración, nada de repliegue**: una versión vacía debe
  mostrarse vacía, es lo que uno viene a rellenar. `tools/check-langues-contenu.php` hace cumplir las tres propiedades,
  en la integración continua.

- **La integración continua vuelve a estar en verde** (2026-09-21); en la rama principal, estaba en rojo desde el 10 de
  junio. Casi todas las causas estaban en las herramientas de prueba y la configuración de la CI, no en el producto. La
  excepción: con el servidor integrado de PHP 8.3, el sitio podía calcular mal su dirección base y redirigir hojas de
  estilo y scripts; ahora solo la deduce de un `SCRIPT_NAME` que termine en `index.php`. Una batería de pruebas que no
  puede ejecutarse falla ahora en lugar de saltarse sin decir nada, y la mayoría de los trabajos de la CI tienen un
  límite de tiempo.

- **El logo del instalador no se mostraba en una instalación nueva** (2026-09-21): la ruta era relativa a la carpeta
  `install/`, que el servidor no sirve en esa dirección. La imagen está ahora incrustada en la página.

- **Componentes que ya no se abrían desde el paso a Bootstrap 5** (2026-09-21). Bootstrap 5 puso el prefijo `bs-` a todos
  sus atributos de datos. Un atributo que se queda con el nombre antiguo no provoca ningún error: el componente
  simplemente no hace nada. Estos estaban muertos en silencio:

  | Lo que ya no funcionaba | Dónde |
  |---|---|
  | **El acordeón de la FAQ** no se abría | `modules/faq` |
  | **La lista de participantes de un evento** no se desplegaba | `modules/events` |
  | **Las ayudas emergentes de una parte de los formularios** estaban vacías | núcleo — `neofrag/libraries/form.php` |
  | La ventana emergente del perfil del foro, la de eliminar un enlace de navegación | `modules/forum`, `widgets/navigation` |
  | La colocación de las burbujas en el editor en vivo, el intervalo del carrusel | `modules/live_editor`, `widgets/slider` |

- **Disposiciones rotas, también silenciosas** (2026-09-21). Bootstrap 5 renombró sus utilidades direccionales; una clase
  renombrada ya no está definida en ninguna parte, y el elemento conserva su disposición por defecto sin que nada lo
  señale.

  - **Los pies de los paneles y de las ventanas modales no se alineaban a la derecha.** El núcleo construía la clase por
    concatenación (`'float-'.$align`), así que ninguna búsqueda de `float-right` podía encontrarla. El único parche de
    compatibilidad del producto solo cubría las tablas del tema de la administración: nada para los temas públicos.
    Misma causa para la alineación de las celdas de tabla, en las dos bibliotecas.
  - **Ningún campo de formulario rechazado se marcaba**, en ningún tema. Las dos bibliotecas ponían un nombre heredado —
    `has-error` (Bootstrap 3) y `has-danger` (una versión preliminar de Bootstrap 4) — que ninguna hoja de estilos
    servida define. El mensaje sí aparecía, pero nada indicaba el campo afectado. Se sustituyen por un marcador propio,
    definido una sola vez.
  - **El pie de página** llevaba `<div class="float-right">`: «Desarrollado por NeoFrag Reborn» bajaba a una nueva línea,
    a la izquierda. El valor viene de lejos — una migración de Alpha 0.2.2 lo había cambiado de `pull-right` a
    `float-right` en tiempos de Bootstrap 4, y el paso siguiente nunca se dio. Una migración
    (`2026_09_20_bootstrap5_float`) corrige los sitios existentes; la semilla incluye ahora `float-end`.
  - Y las últimas clases muertas en las vistas: la barra de depuración en un teléfono, el margen de las listas de
    eventos, las etiquetas del editor de menús, las dos barras del voto de reclutamiento — que salían del **mismo
    color**, de modo que el gráfico ya no distinguía las opiniones favorables de las desfavorables.

- **El cuerpo de un artículo podía borrarse al mostrarse** (2026-09-21). La construcción del índice sustituía el texto por
  el resultado de un tratamiento que puede rendirse ante un artículo largo: la página respondía entonces con normalidad,
  con un artículo **vacío**, y nada en los registros.

- **Un widget colocado arriba o abajo de la página desaparecía.** El tema *Nebula* ofrecía las ubicaciones «Cabecera» y
  «Pie de página» en el editor de disposiciones, pero no las mostraba. El widget sí se guardaba, y no aparecía nada —
  sin ningún mensaje. Los otros cuatro temas públicos no se veían afectados.

- **El widget de navegación ofrecía dos módulos internos** (`live_editor` y `pages`) que debía ocultar. El mismo defecto
  hacía perder su nombre de módulo a las estadísticas de la administración, sin efecto visible mientras dos módulos no
  usen el mismo nombre de estadística.

- **El registro de depuración crecía sin límite** cuando estaba activado. Ahora tiene un tope: más allá de 64 MB, vuelve a
  empezar conservando la generación anterior.

- **En un sitio en inglés, «No» se quedaba en francés («Non»).**

- **Cinco páginas de la administración no ofrecían ningún camino de vuelta** (2026-09-20): la ficha de un miembro, el
  registro de auditoría, añadir un grupo, la lista de sesiones y los archivos de la monitorización. La miga de pan solo
  hace clicable el nombre del módulo si el módulo declara tener una página de inicio de administración; `user` y
  `monitoring` declaraban lo contrario aunque la tienen. Una vez en esas páginas, había que pasar por el menú lateral o
  por el botón de volver del navegador.

- **Los buscadores recibían JSON en lugar del mapa del sitio.** Pedido en la raíz, sin prefijo de idioma — la única forma
  en que lo pide un robot —, `/sitemap.xml` respondía `{"redirect":"/fr/sitemap.xml"}` con el código de estado 200, y
  `robots.txt` y `humans.txt` hacían lo mismo. La redirección que añade el prefijo de idioma respondía en JSON siempre
  que la dirección terminaba en `.txt`, `.xml` o `.json`. Como estos archivos no tienen versión por idioma, ya no pasan
  por esta redirección: se sirven directamente, con su tipo de contenido real.

- **`/favicon.ico` devolvía 404.** Los navegadores piden esta dirección declare lo que declare la página; ahora lleva al
  favicon configurado en los ajustes, o al del CMS.

- **Activar la autenticación en dos pasos fallaba.** Al guardar los códigos de recuperación, un error interrumpía la
  página: la cuenta acababa marcada como «protegida» sin un solo código de emergencia. Encontrado por una prueba escrita
  al reforzar el tipado de esta biblioteca; corregido, y la prueba se queda.

- **Un límite de «un solo intento» nunca bloqueaba.** El umbral solo se comprobaba a partir del segundo intento. Sin
  efecto en los umbrales incluidos, pero el límite de denuncias de la moderación, que se puede bajar hasta 1, nunca
  bloqueaba entonces. El umbral se aplica ahora desde el primer intento.

- **Algunos textos se quedaban en francés en los demás idiomas, y el registro se seguía llenando.** Tres causas, todas
  corregidas: el título de una página del wiki, de un tipo de evento o de una campaña de donaciones pasaba por la
  traducción como si fuera texto de la interfaz (no tiene traducción: es contenido); un texto ya traducido volvía a pasar
  por la traducción — «Espace membre» se convertía en «Member area», y luego «Member area» se buscaba como clave y se
  señalaba como ausente en cada visualización; y treinta cadenas de la interfaz (el campo «Nombre de usuario o dirección
  de correo» del formulario de inicio de sesión, títulos de panel en la administración de los juegos y del
  reclutamiento, las redes sociales del perfil…) no tenían traducción en **ningún** idioma, porque la comprobación que
  verifica los seis idiomas solo miraba las llamadas de traducción explícitas, no los títulos de campos y cabeceras
  traducidos más adelante. La comprobación los ve ahora; las treinta cadenas están traducidas al inglés, alemán,
  español, italiano y portugués.

- **Google Analytics espera ahora tu consentimiento.** Si hay un identificador de Analytics configurado, el script de
  Google solo se carga ahora tras un clic en «Aceptar todo» en el banner de cookies, nunca antes. Además, la política de
  seguridad del sitio bloqueaba este script en todos los casos: Analytics no funcionaba, y nada lo decía. Ahora funciona,
  y solo con el consentimiento del visitante.

- **Tres funciones de la administración habían dejado de responder desde junio.** Ordenar las tablas haciendo clic en
  una cabecera de columna, reordenar arrastrando las categorías, foros y subforos, y los botones Abierto/Cerrado de las
  páginas de Mantenimiento y Registros: sus scripts aún suponían la presencia de jQuery, aunque se retiró del CMS en
  junio de 2026, y se detenían en seco al cargar la página — sin ningún mensaje, los botones simplemente se quedaban
  inertes. Los tres se han reescrito sin jQuery, están cubiertos por pruebas en navegador, y una comprobación automática
  rechaza ahora cualquier script que vuelva a llamar a jQuery.

- **Una dirección mal formada provocaba un error del servidor.** Una petición como `/fr/a:80` o `/:80`, de las que envían
  los robots, hacía fallar el cálculo de la extensión de la dirección y devolvía un error 500 al visitante. El sitio
  responde ahora con un 404 corriente.

- **El formulario de contacto escribía «De:» con la dirección del visitante.** Enviar un mensaje desde tu servidor en
  nombre de `visiteur@example.org` es exactamente lo que rechazan las protecciones contra la suplantación de los
  proveedores de correo (SPF, DMARC): el mensaje acababa en spam o era rechazado. El remitente es ahora la dirección de
  contacto del sitio, y la dirección del visitante se pone en «Responder a», lo que en la práctica viene a ser lo mismo.
  Misma corrección para las respuestas a una candidatura, que podían salir con la dirección personal del administrador.

- **El instalador podía guardar una dirección de contacto no válida.** Cuando el sitio se instalaba accediendo a él por
  su dirección IP, la dirección de contacto pasaba a ser `noreply@<IP>`, que los proveedores de correo rechazan — y ya no
  salía ningún correo, sin ningún mensaje de error visible. La dirección solo se deduce ya de un verdadero nombre de
  dominio.

- **Aparecía un banner vacío en las páginas en las que una zona de la disposición está vacía.** En el tema Extend, todas
  las páginas salvo la de inicio mostraban una ancha banda de color que no contenía nada, así como una sección vacía
  encima del contenido.

  La causa no era el tema: una zona declarada pero sin widget devolvía espacios en lugar de nada en absoluto, así que los
  temas dibujaban su marco alrededor de nada. Corregido una sola vez para todos los temas. Si construyes un tema, puedes
  escribir `if ($zone = $this->output->region('banner'))` (o `zone(n)`): la condición dice ahora la verdad.

- **El tema Extend mostraba dos veces su mención «Desarrollado por NeoFrag Reborn».** Su disposición incluida colocaba
  en el pie de página un bloque que repetía lo que el tema ya escribe él mismo. Los sitios ya instalados se corrigen
  automáticamente al actualizar; los nuevos nunca lo tendrán.

- **Buscar una palabra común devolvía una página de error.** Una búsqueda como «le» o «de» mostraba sus resultados en
  pantalla, pero el servidor respondía **404** — que es lo que ven los buscadores, las herramientas de supervisión y los
  navegadores, y que hacía parecer desaparecida una página perfectamente válida.

  Resaltar las palabras encontradas suponía que la palabra buscada aparecía necesariamente en el texto mostrado. Es
  falso por partida doble: la búsqueda examina **varios campos** y solo muestra uno — un tema del foro titulado «Salut
  tout le monde !» coincide con «le» por su título, no por el mensaje mostrado debajo — y la base de datos compara **sin
  tener en cuenta los acentos**, mientras que el código sí los tenía en cuenta: «éléphant» salía en la búsqueda «ele»,
  sin que se resaltara en él ninguna palabra.

  Los dos casos están resueltos: el resaltado reconoce ahora las variantes acentuadas de una letra, como hace la base de
  datos, y cuando la palabra de verdad no está en el texto mostrado, el extracto empieza simplemente por su comienzo.

- **Las actualizaciones automáticas del núcleo no podían funcionar** — cinco defectos, cada uno suficiente por sí solo:
  la dirección para comprobar las nuevas versiones no estaba definida en ninguna parte, así que el botón nunca aparecía;
  un interruptor global, que había que poner en la configuración, bloqueaba la operación; la descarga apuntaba a las
  versiones del NeoFrag original, que habrían sobrescrito NeoFrag Reborn; el paquete guardaba sus archivos en una
  subcarpeta, así que una actualización **no habría sustituido nada, sin el menor error**; y el archivo se extraía con
  una interfaz de PHP obsoleta desde PHP 8.0.

  El interruptor se sustituye por cuatro garantías: el origen de las versiones debe estar en una **lista de direcciones
  autorizadas** (la misma que para el marketplace); el archivo de versión solo da un **nombre de archivo**, nunca una
  dirección; la huella **SHA-256** del paquete se comprueba **antes** de tocar un solo archivo; cada archivo del paquete
  se controla (ninguna ruta que salga del sitio, ningún enlace simbólico). Las carpetas `config/` e `install/` nunca se
  reescriben si ya existen.

- **El catálogo del marketplace era inaccesible desde cualquier sitio.** El servidor que lo distribuye respondía
  `{"redirect":"/fr/…"}` **con el código de estado 200** en lugar del archivo estático — un cuerpo perfectamente válido
  como JSON, y perfectamente falso, así que un fallo silencioso que hacía que todos los sitios recurrieran a su catálogo
  local. La causa era la configuración de ese servidor, ya corregida: no hay nada que hacer en los sitios. En el código,
  la actualización del núcleo valida ahora la forma de sus manifiestos, y no solo si se leen como JSON.

- **El botón «Eliminar» de un tema**, en «Temas y addons», solo protegía el tema de la administración, por su nombre
  escrito en el código. Ahora sigue la declaración del tema, y protege por tanto también a Nebula.

- **Acoplamientos del núcleo hacia lo opcional.** Siete módulos consultaban las tablas de módulos opcionales: cinco del
  núcleo (`admin`, `moderation`, `settings`, `statistics`, `user`), más `games` y `teams`. Dos consultas estaban sin
  protección: en `teams`, la comprobación de existencia de `recruits` estaba colocada **después** de la consulta que
  debía proteger, y en `games`, eliminar un juego consultaba la tabla de los equipos sin comprobar que existe. Las demás
  estaban protegidas pero mudas: llevan ahora una anotación que verifica la CI. El único ciclo duro del paquete (`games`
  ↔ `teams`) está roto.

- **Editor en vivo: los cambios aparecen sin recargar la página.** Eliminar un widget lo dejaba en pantalla y añadir una
  fila no la mostraba hasta recargar la página, aunque el servidor había guardado el cambio. El script esperaba otro
  formato de respuesta que el que envía el servidor; el defecto venía de la retirada de jQuery.
- **Editor en vivo: arrastrar y soltar widgets vuelve a funcionar.** Con el modo Columnas activado, un widget añadido se
  quedaba atascado al final de su columna y no se podía mover por encima del contenido de la página. Las filas y las
  columnas no se veían afectadas.
- **Editor en vivo: los widgets sin ajustes se pueden volver a añadir.** Diez widgets que no tienen pantalla de ajustes
  (`breadcrumb`, `copyright`, `downloads`, `forum`, `module`, `news`, `newsletter`, `slider`, `surveys`, `teams`) no se
  podían añadir: añadirlos fallaba con un error 404 y sin ningún mensaje útil.
- **Un error del servidor ya no se muestra como contenido.** Cuando una acción hecha sin recargar la página recibía un
  error del servidor (una página 404, por ejemplo), algunas pantallas insertaban esa página de error en la interfaz. El
  error se trata ahora como un fallo.
- **Editor en vivo: el formulario de ajustes corresponde siempre al widget elegido.** Al cambiar rápido de widget o de
  tipo, la respuesta más lenta podía mostrar el formulario de otro widget. La petición superada se anula ahora, y el
  formulario ya no se recarga (ni se pierde lo que se está escribiendo) cuando la selección no ha cambiado.
- **El puerto de la base de datos se respeta ahora.** El CMS se conectaba siempre por el puerto 3306, fuera cual fuera el
  puerto introducido en el asistente de instalación: no se podía instalar ni hacer funcionar en un servidor MySQL o
  MariaDB que escuchara en otro. El puerto se guarda ahora en `config/db.php` (solo si difiere de 3306) y se usa en cada
  conexión.
- **Un tema activado ya no está vacío.** Activar Forge, Granite, Blockcraft o Extend cambiaba el tema sin aplicar su
  disposición por defecto, que solo se aplicaba con el botón «Reinstalar predeterminado»: el sitio no mostraba ningún
  widget. La activación aplica ahora la disposición por defecto del tema si aún no tiene ninguna, sin sobrescribir nunca
  una disposición personalizada.
- **Cambiar el tema por defecto se aplica ahora también a los visitantes que habían elegido uno.** El selector de tema
  del pie de página recordaba la elección de un visitante durante un año, y esa elección prevalecía sobre el tema por
  defecto: cuando el administrador cambiaba de tema, los visitantes, y también el administrador, se quedaban en el
  antiguo, sin salida desde un tema sin selector. Una elección hecha antes del último cambio de tema por defecto se olvida
  ahora; una hecha después se sigue respetando.
- **El navegador ya no conserva copias caducadas de las páginas.** A falta de instrucciones, podía volver a mostrar una
  versión antigua de una página: un cambio de tema, por ejemplo, solo aparecía tras una recarga forzada (Ctrl+F5). Las
  páginas y respuestas del sitio prohíben ahora esta caché (`Cache-Control: no-store`); las imágenes, estilos y scripts,
  servidos directamente por el servidor web, no se ven afectados.

### Seguridad

- **Un valor guardado ya no puede inyectar etiquetas en un formulario** (2026-09-23). El formulario de edición colocaba
  el valor de un campo en la página escapando las comillas como lo haría una cadena de PHP — lo que no tiene ningún
  efecto en HTML. Un valor que no hubiera pasado por el propio formulario (importación, datos de demostración, otro
  módulo) podía por tanto salirse de su campo y añadir código a la pantalla de administración: una cita cuya fuente era
  `"><img src=x>` lo demostró. Los campos de texto, las áreas de texto, las listas, las casillas y los botones de opción
  codifican ahora su valor; la representación de un valor introducido normalmente no cambia. Las notificaciones («Cita
  modificada», etc.) se pasan también al script de la página en una forma que ya no puede romperlo.

- **La política de seguridad declara ahora una regla para los medios** (`media-src`). No tenía ninguna y recurría a la
  regla general (`default-src 'self'`). Ahora permite los medios del propio sitio y los medios incrustados en la página
  (`'self' data:`); la dirección de la emisión de una radio web se añade **solo si hay una emisión configurada**: un sitio
  sin radio web no permite ningún origen externo adicional.

- **Once avisos de seguridad cerrados en las dependencias** (2026-09-20): `league/commonmark` pasa de la **2.8.2 a la
  2.10.1** (diez avisos, ocho de ellos graves) y `phpseclib` de la **3.0.52 a la 3.0.57** (un aviso de gravedad media).

  Nueve de los diez avisos de commonmark son **denegaciones de servicio mediante Markdown preparado** — análisis en
  tiempo cuadrático, títulos con anclas que colisionan, notas al pie duplicadas, bloques de atributos adyacentes, salida
  XML profundamente anidada — y el décimo es una **vulnerabilidad XSS**: el filtro de los atributos `on*` se podía
  saltar con un salto de página U+000C. La biblioteca representa el Markdown del wiki, de los artículos y de la FAQ,
  escrito por los miembros. Varios de estos avisos afectan a extensiones que el producto no activa (notas al pie,
  atributos, anclas de títulos, salida XML); la actualización los cierra todos.

  La representación no cambia, y no es solo una impresión: **doce casos representativos** — títulos, tabla, lista de
  tareas, bloque de código, HTML inyectado, enlace `javascript:`, cita, texto tachado, enlace automático, listas
  anidadas, imagen, entidades y nota al pie, anidamiento profundo —, representados por las dos versiones con la
  configuración real del producto, dan el mismo HTML **byte a byte** (1568 bytes, la misma huella).

  Nota: Composer **se niega ya a instalar** la 2.8.2, precisamente por estos diez avisos. Hubo que desactivar la política
  en una carpeta desechable para hacer la comparación.

## [1.1.0] — 2026-08-23

### Seguridad
- **Revisión de seguridad de todo el código**, y corrección de los fallos graves que encontró:
  - **Copias de seguridad**: los archivos de copia de seguridad (una copia de la base de datos y de la carpeta
    `config/`, que contiene las contraseñas) ya no se pueden descargar desde una simple dirección web, ni con Apache ni
    con nginx; solo la administración (Monitorización) los entrega. Su nombre incluye ahora una parte aleatoria,
    imposible de adivinar. La carpeta de los registros (`logs/`) está protegida de la misma forma.
  - **Tokens secretos**: los identificadores de sesión, los tokens de los formularios y los enlaces enviados por correo
    (contraseña olvidada, validación de la cuenta) salen de un generador aleatorio seguro; eran predecibles. Estos
    enlaces caducan al cabo de una hora, y una nueva solicitud anula la anterior.
  - **Inicio de sesión**: la autenticación en dos pasos (2FA) y los baneos se comprueban sea cual sea la forma de
    iniciar sesión — contraseña, cuenta de Discord, Google o GitHub, enlace «contraseña olvidada» —, y ya no solo con la
    contraseña.
  - **Enlaces en los correos**: la dirección del sitio usada en los enlaces enviados por correo, al volver de un inicio
    de sesión con Discord, Google o GitHub y al volver de un pago con Stripe es la guardada en la instalación
    (`config/url.php`), y ya no la que anuncia la petición: un atacante ya no puede hacer enviar un enlace que lleve a
    su propio sitio.
  - **Páginas de administración**: un nombre de archivo adjunto trampeado (en una denuncia de moderación) o una
    dirección IP falsa (cabecera `X-Real-IP`, en el historial de sesiones) podían inyectar código en ellas; ahora se
    comprueban y se neutralizan. El mensaje de bienvenida de un servidor de juego, proporcionado por un servicio
    externo, se limpia antes de mostrarse.
  - **Acciones de administración**: eliminar, activar o desactivar, cerrar, aprobar, restaurar, purgar… exigen ahora un
    token de seguridad en los 23 módulos que no lo tenían. Eran simples enlaces o formularios sin token: un enlace
    trampeado, pulsado por un administrador con la sesión iniciada, bastaba para desencadenar la acción.
  - **Herramientas**: los scripts de la carpeta `tools/` solo se ejecutan desde la línea de comandos, nunca desde una
    dirección web; en cada cambio, la integración continua comprueba que ninguna dependencia PHP tenga un fallo conocido
    (`composer audit`).
  - **Botones silenciados por la política de seguridad (CSP)**: la CSP estricta del sitio bloquea las acciones escritas
    en el HTML (`onclick="…"`), así que estos botones no hacían nada: banner de cookies, número de filas de las tablas
    de administración, confirmaciones de la papelera y de las revisiones, nombre del archivo adjunto en la mensajería,
    acciones en lote de las menciones del foro, selección de un campo al hacer clic, botón de la ventana de eliminación.
    Se conectan de otra forma y funcionan también en el contenido cargado sin recargar la página. El selector del
    número de filas llamaba además a jQuery, que ya se había retirado.
- **Política de seguridad del contenido (CSP) estricta**: servida por el sitio con un token propio de cada página,
  rechaza los scripts escritos en la página sin ese token (`unsafe-inline`), el código construido sobre la marcha
  (`unsafe-eval`) y los objetos incrustados (`object-src 'none'`); los scripts solo vienen ya del sitio y de una breve
  lista de direcciones, ya que TinyMCE y CodeMirror pasan a estar alojados en el propio sitio.
- **Sesión renovada al iniciar sesión**: el identificador de sesión cambia al iniciar sesión; un identificador impuesto
  antes del inicio de sesión ya no da acceso a la sesión abierta.
- **Secretos cifrados en la base de datos**: la contraseña SMTP y la clave de la autenticación en dos pasos (2FA) están
  cifradas (AES-256-GCM); una copia de la base de datos ya no las entrega en claro. Un valor existente se cifra la
  próxima vez que se guarda.
- **Webhooks**: un webhook ya no puede apuntar a la red interna del servidor (direcciones privadas, locales o
  reservadas rechazadas, redirecciones no seguidas).

### Añadido
- **Archivos CSS y JavaScript siempre al día**: cada archivo se pide con su fecha de modificación (`?v=…`), de modo que
  el navegador lo recarga en cuanto cambia — incluido un archivo sustituido por un tema o subido a mano —, sin tener
  que aumentar `nf_version_css` (que se sigue usando cuando no se encuentra el archivo).
- **Marketplace**: la ventana «Actualizaciones» de la página *Temas & Addons* avisa de que hay una nueva versión de
  NeoFrag Reborn disponible; la actualización del núcleo en sí sigue siendo manual.
- **Actualizaciones de los addons**: la misma ventana compara los addons instalados con el catálogo del marketplace e
  instala las nuevas versiones, con sus migraciones de base de datos.
- **Instalador**: nueva presentación — logotipo de NeoFrag Reborn, banner «Fork no oficial de NeoFrag», crédito a
  Michaël BILCOT y Jérémy VALENTIN (licencia LGPLv3) en el pie de página, tipografías Inter y Space Grotesk, colores
  turquesa.
- **Archivos**: un gestor de archivos (árbol de carpetas, subida, permisos de lectura por archivo o por carpeta),
  portado de HiddenCMS, de HiddenBlob (LGPLv3). Incluido con el núcleo.
- **Emojis personalizados** (módulo Emojis, incluido con el núcleo): la administración añade imágenes con nombre, que
  `:nombre:` muestra en los mensajes y firmas del foro, las noticias, las páginas, los eventos y el reclutamiento, entre
  otros.
- **Newsletter**: envío programado en la fecha elegida, en pequeños lotes tratados por la tarea programada del sitio
  (cron) en lugar de enviarlo todo de golpe; seguimiento de aperturas (tasa de apertura por campaña, medida mediante
  una imagen invisible); plantillas de correo reutilizables; elección de destinatarios: todos los suscriptores, solo
  los miembros o un grupo.
- **Eventos**: un evento puede repetirse cada día, cada semana o cada mes, el número de veces elegido — cada fecha se
  convierte en un evento por derecho propio. Los participantes, salvo los que han declinado, reciben un recordatorio en
  el sitio (campana de notificaciones) antes del evento, 24 horas antes por defecto; los recordatorios pasan por la
  tarea programada del sitio (cron).
- **Reacciones con emoji**: seis reacciones a elegir (👍❤️😂😮😢😡) en lugar de un solo corazón, como en Discord o
  Facebook, cada una con su recuento; los antiguos «me gusta» se convierten en ❤️.
- **Widget «Estado en directo»** (el antiguo widget de Twitch): sigue varios canales a la vez, en Twitch y en YouTube
  (credenciales de Twitch y clave de API de YouTube necesarias), con juego, espectadores, título, miniatura y
  reproductor integrado.
- **Páginas compuestas**: bajo su texto, una página puede mostrar bloques de otros módulos (últimas noticias, noticias
  de una categoría, últimos artículos, descargas populares), colocados en el orden deseado y configurados uno a uno al
  editar la página. El código corto acepta también ajustes, por ejemplo `[block:news.latest count=2]`.
- **Tema Extend**, de Chewbaka, portado a NeoFrag Reborn (Bootstrap 5, modos día y noche), con licencia
  CC BY-NC-SA 4.0; disponible en el marketplace.
- **Búsqueda instantánea**: el widget de búsqueda propone resultados desde la segunda letra escrita (noticias, foro,
  páginas).
- **Ordenación de las listas de administración**: diez listas (noticias, artículos, anuncios clasificados, descargas,
  FAQ, libro de visitas, enlaces, medios, encuestas, wiki) se ordenan según el criterio elegido.
- **Avatares animados**: un GIF animado subido como avatar sigue animado — siempre si el servidor dispone de Imagick,
  y si no, mientras no supere los 250 × 250 píxeles.
- **Permisos**: la cuadrícula de permisos se abre en una ventana, sin salir de la página.
- **Gestor de archivos del webmaster** (Monitorización, botón «Gestionar archivos»): recorrer los archivos del sitio,
  editarlos en un editor (CodeMirror), crearlos, renombrarlos o eliminarlos. Las carpetas `config/`, `logs/` y
  `backups/` quedan fuera de alcance; cada guardado conserva una copia `.nfbak` y entra en el registro de auditoría.
  Toda escritura exige la **contraseña de webmaster**, distinta de la de la cuenta y guardada fuera de la base de datos
  (definida en la instalación o en Monitorización, tarjeta «Seguridad webmaster»); una vez definida, se vuelve a pedir
  también para eliminar o purgar copias de seguridad y para cambiar la clave de la tarea programada.
- **Notas de versión de una sola fuente**: `tools/changelog-section.php` extrae una versión de este registro de cambios
  (`CHANGELOG.md`), en Markdown o en HTML; las notas de las releases de GitHub salen de ahí, y sus textos ya no pueden
  divergir.

### Cambiado
- **Instalador «todo incluido»**: el asistente pasa a cuatro pasos (Requisitos → Base de datos → Administrador →
  Terminado). Todos los módulos, widgets y temas incluidos se instalan y activan en el paso «Base de datos», y la página
  de inicio muestra las noticias en cuanto termina la instalación; el paso «Módulos» y sus perfiles desaparecen.
- **Bootstrap 4.6.2 → 5.3.8, y jQuery ya no se carga**: el JavaScript del sitio se apoya en una pequeña herramienta
  propia (`window.NF`). Nueve extensiones de jQuery o de Bootstrap 4 se sustituyen (notificaciones → toasts de
  Bootstrap 5, selectize → Tom Select, selector de fecha → flatpickr, FullCalendar 3 → 6, selectores de color y de
  icono, árbol y medidores → código propio, barras de desplazamiento → desplazamiento nativo), y jQuery UI por
  SortableJS. Cuatro scripts olvidados seguían llamando a jQuery: se reescriben en la 1.2.0.
- **Modo oscuro**: las tablas, tarjetas, menús desplegables y el editor de texto toman los colores oscuros del tema en
  lugar de quedarse blancos, en todos los temas.
- **Ajustes de los widgets y disposición de las páginas**: guardados en JSON en lugar del formato de serialización de
  PHP, lo que cierra una vía de inyección de código; los ajustes antiguos se siguen leyendo.
- **Gráficos de las estadísticas**: Chart.js (licencia MIT) sustituye a Highstock, una biblioteca comercial que impedía
  redistribuir libremente el CMS.

### Corregido
- **MySQL 8**: abrir un tema o responder en el foro, dividir o fusionar temas, crear una conversación en la mensajería,
  reordenar las imágenes del Slider… fallaban con un error fatal en MySQL 8, que rechazaba la forma en que el sitio
  abría sus transacciones. Ahora pasan por las funciones previstas de mysqli.
- **Emojis**: un emoji — o cualquier carácter de 4 bytes — en un texto provocaba un error de base de datos. La conexión
  y todas las tablas pasan a `utf8mb4` (intercalación `utf8mb4_unicode_ci`, reconocida tanto por MySQL 8 como por
  MariaDB 10; las intercalaciones propias de MariaDB 11, que hacían fallar la instalación en otros servidores,
  desaparecen).
- **`where('col', [])`** generaba una condición vacía (→ `DELETE`/`UPDATE` sobre toda la tabla): ahora produce `1 = 0`
  (conjunto vacío).
- **Fechas posteriores a 2038**: el envío programado de una newsletter, la publicación programada de una noticia, un
  artículo, una galería o una página, y las fechas de un evento no podían pasar del 19 de enero de 2038: más allá,
  MariaDB rechazaba guardarlas, sin ningún mensaje. Estas fechas llegan ahora hasta el año 9999; las instalaciones
  existentes se actualizan mediante una migración.
- **`/user/login` y `/user/registration` respondían «página no encontrada»**: los botones «Iniciar sesión» y «Registro»
  de los temas llevan allí cuando JavaScript está desactivado o el enlace se abre en una nueva pestaña. Estas
  direcciones abren ahora la ventana de inicio de sesión o de registro.
- **Nebula**: el botón «Registro» seguía visible cuando los registros estaban cerrados, y llevaba a una página no
  encontrada. Ahora se oculta en ese caso.
- **Widget de Discord**: da la verdadera causa de un fallo (widget desactivado en el servidor, identificador
  desconocido, red) en lugar de un mensaje genérico, muestra el verdadero logotipo del servidor (leído en la
  invitación pública) y cuenta bien los miembros en línea — la lista que proporciona Discord se detiene en 100, lo que
  falseaba el recuento de los servidores grandes.
- **Widget de TeamSpeak, vista en árbol**: fallaba con PHP 8 y volvía a la tarjeta simple. El árbol de canales y
  clientes lo dibuja ahora el propio widget, con iconos de Font Awesome (ya no hace falta un paquete de iconos); un
  error de red muestra un mensaje genérico, sin revelar la dirección ni el puerto del servidor.
- **Widgets de Discord, Twitch y Servidor de juego**: mostraban «inaccesible» incluso cuando el servicio respondía (la
  respuesta se decodificaba dos veces). Las peticiones del sitio envían además un identificador de navegador
  (User-Agent): sin él, los servicios protegidos por Cloudflare respondían con un rechazo (error 403).
- **Copias de seguridad y Monitorización**: crear una copia de seguridad fallaba en los servidores con PHP-FPM, y
  fallaba con nginx o Plesk, que respondían «página no encontrada» a las direcciones en `.json`; eliminar o descargar
  una copia de seguridad llevaba a una página no encontrada; el árbol de la Monitorización mostraba un error en la
  primera actualización. Todo corregido.
- **Marketplace inaccesible**: URL por defecto cambiada a **sin www** (`https://neofrag-reborn.xyz/marketplace`).
- **Subida de varios archivos**: el primer archivo de una subida múltiple hacía fallar la subida («página no
  encontrada») — era lo que bloqueaba la subida en el módulo Archivos. Para los desarrolladores: `uploaded_file()`
  tomaba el índice `0` por la ausencia de índice.
- **Webhooks**: el registro de un miembro (`user.registered`), un nuevo comentario (`comment.created`) y un nuevo tema
  del foro (`forum.topic`) disparan por fin su webhook; estos eventos se ofrecían pero nunca se emitían.

### Documentación
- **Documentación** (guías de la wiki): para los desarrolladores, los formularios (`form()` y `form2()`) y la
  validación de los ajustes de un widget; para los administradores, la solución de problemas de la instalación, un
  ejemplo de creación de un rol y qué hacer cuando el marketplace es inaccesible. Una nota explica cómo configurar nginx
  o Plesk, que respondían «página no encontrada» a las direcciones en `.json`.

---

## [1.0.0] — 2026-06-06 · base de Reborn (sobre Alpha 0.2.4)

Primer ciclo del fork: modernización de la base, refuerzo de la seguridad y una amplia ola de nuevas
funciones.

### Añadido

**Plataforma y herramientas**
- **Migraciones de base de datos** numeradas (`tools/migrate.php`, con un comando `baseline` para retomar una base de
  datos existente) y primeras pruebas automatizadas (PHPUnit).
- **Nuevo instalador web**, que sustituye al del NeoFrag original: un asistente en cinco pasos (Requisitos → Base de
  datos → Módulos → Administrador → Terminado). El paso «Módulos» proponía tres perfiles de sitio (Comunidad,
  Gaming / eSports, Sitio sencillo) y podía añadir addons desde el marketplace.
- **Marketplace remoto** (catálogo y archivos servidos por neofrag-reborn.xyz): el instalador (paso «Módulos») y la
  administración (botón «Marketplace» de la página *Temas & Addons*) añaden con un clic los addons del catálogo oficial
  que no vienen con el sitio. Seguridad: HTTPS obligatorio, huella SHA-256 verificada, archivo comprobado entrada por
  entrada (ninguna ruta que salga de la carpeta del addon, ningún enlace simbólico), dirección del catálogo fija,
  tamaños y tiempos de espera limitados.
- **Herramientas para fabricar los paquetes**: `package-addons` prepara el catálogo del marketplace, `build-release`
  los archivos que se suben por FTP.
- **Script de mantenimiento** (`tools/maintenance.php`, para lanzar con una tarea programada): vacía la papelera de lo
  que lleva en ella más de 30 días (ajustable) y elimina las cuentas nunca confirmadas al cabo de 7 días, cuando la
  validación de los registros está activada — nunca un administrador.

**Temas e interfaz**
- **Cuatro temas creados para NeoFrag Reborn**: Nebula, pensado para los sitios de comunidad, Forge, Blockcraft y
  Granite. Unas variables de color comunes (`--nf-*`) visten módulos y widgets con los colores de cada tema.
- **Rediseño completo de la administración**: nueva presentación, barra lateral, paleta de comandos (Ctrl+K), modos
  claro y oscuro.
- Selectores de **tema** y de **idioma** en el pie de página (visitantes incluidos).
- **Menús**: constructor de menús de varios niveles, utilizable en el widget Navegación (opción «Menú gestionado»).

**Contenido**
- **Noticias y artículos**: publicación programada — el contenido aparece a la hora elegida, y las notificaciones,
  webhooks y puntos solo salen en ese momento, ya no al guardar (una dirección a la que llama una tarea programada,
  protegida por una clave); acciones en lote; búsqueda, filtros y paginación en la administración; una página propia
  para leer una noticia. Los artículos reciben una imagen destacada, como ya tenían las noticias, y el contador de
  visitas de las noticias, que nunca subía, por fin cuenta.
- **Publicación programada** también en páginas, galerías y eventos (fecha de publicación distinta de la fecha en que
  se celebra, para los eventos).
- **Wiki**: sirve de documentación en el sitio (índice en tarjetas, renderizado Markdown fiable); búsqueda y
  paginación; comparación visual de dos revisiones de una página, línea por línea.
- **Medios**: título y descripción editables para cada archivo, búsqueda, filtro por tipo y paginación. Búsqueda,
  filtros y paginación también en la administración de Descargas, el Directorio de enlaces, FAQ, Encuestas y el Libro
  de visitas.
- Feed **RSS 2.0** (noticias + artículos), botones para **compartir** en redes sociales, **SEO** (meta description,
  canonical, Open Graph, Twitter Card, meta por página).

**Comunidad y participación**
- **Centro de notificaciones** en el sitio (campana y número de no leídas), con **suscripciones** (seguir un contenido
  o una categoría). Avisan de los comentarios, las respuestas del foro, las menciones @usuario, los mensajes privados y
  las invitaciones a un evento.
- **Reacciones** «me gusta» en las noticias, artículos, comentarios y mensajes del foro; **revisiones** de los
  contenidos (historial y restauración); widget «Últimos comentarios».
- **Papelera** común: las noticias, artículos, galerías, comentarios y mensajes del foro eliminados pasan por ella, y
  pueden restaurarse desde ella.
- **Foro**: categoría reservada a los miembros VIP, imagen por categoría.

**Gaming**
- **Eventos**: gestión completa de los adversarios (lista, edición, eliminación; el NeoFrag original solo permitía
  añadir uno desde un partido), cuenta atrás en directo, notificación de invitación.

**Monetización y gamificación**
- Karma/reputación → **puntos** (escala configurable) → **tienda** (pago en puntos) → estado **VIP**.
- **Stripe** (compra de puntos y de paquetes VIP), **gestión de publicidad** (oculta para los miembros VIP),
  **donaciones** (módulo y widget portados del «Donation v3» de HiddenBlob, a partir de majiid; LGPLv3).

**Vínculos entre módulos**
- **Estadísticas** ampliadas a 19 módulos (el NeoFrag original cubría 3: comentarios, foro, miembros); **muro de
  actividad** en el perfil, que reúne la actividad de un miembro en todos los módulos; panel **«Por gestionar»** en la
  administración; **bloques de página**: el código corto `[block:…]` inserta en una página un bloque de un módulo
  (noticias, artículos, descargas).

**Webhooks y auditoría**
- **Webhooks** salientes firmados (HMAC), gestionados desde la administración. El **registro de auditoría** registra
  también la instalación y desinstalación de un addon, la activación de un tema o de un módulo y el guardado de los
  ajustes.

**Administración de las cuentas**
- **Miembros**: exportación de los miembros en CSV o JSON (RGPD); los moderadores que tienen el permiso ajustan la
  moderación desde su área de miembro. La edición y la eliminación de un miembro desde la administración, ya presentes
  en el NeoFrag original, vuelven a estar conectadas a la nueva lista de miembros.

### Cambiado
- Versionado normalizado en **SemVer puro** en las dependencias de los addons (retirada de las etiquetas «Alpha»).
- **Páginas** forma parte ahora del núcleo; **Noticias**, **Foro** y **Galerías** se pueden desinstalar.
- **Editor de texto enriquecido**: por fin funciona — el HTML que produce se limpia en lugar de mostrarse como texto.

### Eliminado
- Los dos temas del NeoFrag original, «Thème par défaut» (`default`) y Azuro, sustituidos por los temas de NeoFrag
  Reborn.
- El editor BBCode del NeoFrag original (WysiBB) y la conversión del BBCode: el editor de texto enriquecido lo
  sustituye, y un texto escrito en BBCode se muestra ahora tal cual.

### Corregido
- **Diez defectos corregidos**, entre ellos: los eventos sin participantes ni partido no aparecían; editar o eliminar
  un modo de juego fallaba; eliminar una oferta de reclutamiento fallaba; el enlace a un socio llevaba a una página no
  encontrada (ahora lleva a su sitio); el autor de una denuncia nunca se mostraba, ni siquiera a los moderadores
  autorizados; los widgets Galerías y Foro comprobaban mal sus ajustes o los permisos. Los contadores de visitas ya solo
  cuentan una vez por visita e ignoran a los robots.
- Un contenido programado ya no es visible por su dirección directa antes de su hora. Funcionan botones que no hacían
  nada: periodos de las estadísticas, activación de las plantillas de correo, redes sociales (se muestran las seis),
  botones «Añadir» de las noticias, páginas y galerías.
- **Temas**: contraste de los botones elevado al nivel WCAG AA, campos de inicio de sesión unidos a su icono,
  alineaciones corregidas.
- **Pantallas pequeñas y grandes** (comprobado de 390 a 2560 píxeles de ancho): en la administración, menú lateral
  deslizante y contenido a todo lo ancho en el móvil; perfil de miembro; barra de cuenta compacta en Nebula, Forge,
  Blockcraft y Granite.
- Las páginas Miembros, Estadísticas y Editor en vivo de la administración mostraban un error 500; algunos contadores
  se quedaban bloqueados en 1 (distintivo de moderación, suscriptores de la newsletter, estadísticas de los medios), y
  una categoría vacía de Descargas, FAQ o enlaces no se podía eliminar. (Para los desarrolladores: errores de tipado
  estricto en `timetostr()` y los helpers; `(int)` sobre `row(FALSE)` de un `COUNT`/`SUM` valía siempre 1.)

### Seguridad
- El HTML enviado por los miembros se limpia en el servidor (HTMLPurifier) contra la inyección de código; un archivo
  subido se comprueba por su contenido real, no solo por su extensión; HSTS activado (los navegadores ya no vuelven a
  HTTP).
- **Tokens anti-CSRF** comparados en tiempo constante. **Dirección IP**: la limitación de frecuencia y la lista de IP
  baneadas ya no confían en cabeceras falsificables (`X-Forwarded-For`…), salvo si se declara un proxy de confianza.
  **Permisos**: comprobaciones de permisos añadidas donde faltaban (Libro de visitas, Juegos, Galerías, Eventos,
  Estadísticas).
- Los antiguos scripts de actualización ya no pueden recrear objetos PHP arbitrarios al releer los ajustes
  (`unserialize` limitado por `allowed_classes`).
- **Huella de sesión** contra el robo de sesión: un miembro con la sesión iniciada se desconecta si su navegador
  (user-agent) cambia notablemente durante la sesión — una simple actualización del navegador no basta para ello. La
  dirección de la publicación programada está protegida por una clave comparada en tiempo constante.
