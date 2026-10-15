# Primeros pasos

Tu sitio acaba de instalarse ([Instalación](installation.md)): aquí está, en orden, lo que lo convierte de una página
vacía en un sitio que puedes abrir a tu comunidad. Calcula una hora. Cada paso remite a la guía que lo detalla.

## 1. Entrar en la administración

Inicia sesión con la cuenta creada al final del instalador y abre `/es/admin` (o el enlace *Administración* de la barra
del miembro). La barra lateral ordena los módulos en secciones — *Contenido*, *Comunidad*, *Sistema*… —; `Ctrl + K`
abre la búsqueda rápida. La visita completa está en [El panel de administración](admin.md).

## 2. Ponerle nombre al sitio

*Sistema → Ajustes*: el nombre del sitio, su descripción, la dirección de contacto, la zona horaria. El nombre aparece en
la cabecera del tema y en la pestaña del navegador; la descripción la usan los buscadores mientras una página no dé una
propia ([Posicionamiento](admin.md#posicionamiento)).

## 3. Elegir el tema y los colores

*Sistema → Temas & Addons*: cada tema muestra su página de inicio en miniatura. Activa el que se parezca a tu comunidad
y abre sus ajustes: la página de **colores** muestra el cambio en directo y rechaza un color que haría ilegible el
texto. Qué es un tema, sus zonas y sus regiones: [Conceptos](concepts.md).

## 4. Componer la página de inicio

*Sistema → Editor en vivo*: el sitio aparece como lo ven los visitantes, con sus regiones enmarcadas. Arrastra
**widgets** — las últimas noticias, el foro, los miembros en línea, un pase de diapositivas — y ajusta cada uno con un
clic. Una disposición vale para una página o para todo el sitio.

## 5. Construir el menú

*Contenido → Menús*: añade los enlaces de la barra de navegación — el inicio, el foro, las noticias, una página. Un
enlace a un módulo se elige en una lista; un enlace externo se pega.

## 6. Publicar una primera noticia

*Contenido → Noticias → Nueva noticia*: un título, una imagen, el texto. Aparece en la página de inicio si colocaste allí
el widget de noticias, y los lectores pueden comentarla si los comentarios están abiertos.

## 7. Abrir el foro

*Comunidad → Foro*: crea una **categoría** («General») y luego sus **foros** («Presentaciones», «Ayuda»). Los
**prefijos** («Pregunta», «Resuelto») ayudan a clasificar los temas. Quién lee y quién escribe se ajusta por categoría,
con el botón del candado.

## 8. Recibir a los miembros

*Sistema → Ajustes → Registros*: abiertos, por invitación o cerrados, con o sin captcha ([Registros](admin.md#registros)).
Los **roles** deciden lo que puede hacer cada uno: crea un rol «Moderador», marca sus derechos en la matriz y dáselo a
los miembros de confianza ([Permisos](admin.md#permisos)).

## 9. Añadir lo que falta

El sitio se instaló con un perfil — gaming, comunidad, asociación. Un módulo que falta (el calendario, las donaciones,
los colectivos, el bot de Discord…) se añade desde *Sistema → Temas & Addons* o el [marketplace](marketplace.md), sin
reinstalar.

## 10. Ponerlo a salvo

*Sistema → Monitorización*: crea una primera **copia de seguridad** y anota la dirección de la **tarea programada**.
Dásela a tu proveedor (una tarea cron, cada hora): es la que publica los contenidos programados, envía los recordatorios
y recoge lo que los servicios externos no anuncian ([Monitorización](admin.md#monitorización)).

¿Tu sitio habla varios idiomas? Lee después [Un sitio en varios idiomas](multilingue.md).
