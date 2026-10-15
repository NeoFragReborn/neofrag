# Conceptos

Entender estas cinco nociones basta para dominar NeoFrag Reborn.

## Módulos

Un **módulo** es una funcionalidad completa: el foro, las noticias, la galería, los miembros, la tienda… Cada módulo
aporta sus **páginas públicas**, sus **pantallas de administración**, sus **datos** (tablas) y sus **permisos**.

El paquete incluye **63 módulos**, pero no los instalas todos: en la instalación, un **perfil del sitio** — *Completo*,
*Gaming / eSport*, *Comunidad*, *Asociación / club* o *Solo el núcleo* — marca de antemano lo que corresponde, y puedes
desmarcar módulo a módulo. Lo que un módulo necesita se añade automáticamente (el palmarés necesita los equipos). Los
módulos del **núcleo** (cuentas, permisos, ajustes, páginas, herramientas…) están siempre y no se desinstalan.

Después, desde **Administración → Temas & Addons**, instalas, activas, desactivas o desinstalas cada módulo. Un módulo
desactivado desaparece del sitio pero conserva sus datos. Las direcciones de un módulo ausente responden con un **404
limpio**, nunca con un error.

## Widgets

Un **widget** es un pequeño bloque reutilizable: el menú de navegación, el área de miembros, los últimos comentarios,
una cuenta atrás, un reproductor de Twitch… Un mismo widget puede colocarse varias veces, en lugares distintos, con
ajustes distintos. El paquete incluye **42 widgets**.

Los widgets se colocan en las **regiones** del tema con el **Editor en vivo**: un asistente en cuatro pasos (widget,
tipo, título, configuración), y arrastrar y soltar para moverlos.

## Temas

Un **tema** da al sitio su aspecto: colores, tipografías, maquetación, navegación, pie de página, y el **modo claro u
oscuro**. Declara **zonas**, les da **nombres de región** estables (`header`, `content`, `footer`…) y coloca sus
**disposiciones** por defecto.

El paquete incluye **Nebula**, un tema comunitario generalista (azul marino y turquesa, glassmorphism), pensado para un
equipo, un clan, un club o una asociación. Otros seis temas — **Granite**, **Forge**, **Blockcraft**, **Extend**,
**Chronique**, **Pulse** — están en el paquete y se instalan desde **Administración → Temas & Addons**; una instalación
que no los tenga los encuentra en el [marketplace](marketplace.md). El tema activo se elige en **Administración → Temas
& Addons**; si hay varios temas públicos instalados, los visitantes pueden cambiar con el selector del pie de página.

Todos los temas hablan el **mismo vocabulario de colores** (`--nf-accent`, `--nf-surface`…): un widget adopta
automáticamente el aspecto del tema activo.

## Zonas, regiones y disposiciones

Un tema divide la página en **zonas**: normalmente *Header*, *Antes del contenido*, *Contenido*, *Después del
contenido*, *Footer*, cada una conocida por un **nombre de región** que usan las plantillas.

Una **disposición** describe, para una página dada (o un patrón de páginas), qué widgets ocupan qué zona y en qué
rejilla. Los patrones van del más general al más preciso:

| Patrón | Se aplica a |
|---|---|
| `*` | todas las páginas |
| `/` | solo la página de inicio |
| `forum/*` | todas las páginas del foro |

Gana el patrón más preciso: una disposición `*` pone el contenido en ocho columnas más una columna lateral, una
disposición `marketplace*` puede pasar la misma página a ancho completo.

Cambias las disposiciones con el ratón en el **Editor en vivo**. Una página también puede llevar **bloques**: instancias
de módulos ordenadas y configuradas, insertadas con el shortcode `[block:…]`.

## Addons y marketplace

**Addon** es el término genérico para todo lo que se instala: módulos, widgets, temas, conectores de autenticación
(Discord, GitHub, Google) y paquetes de idioma (seis idiomas incluidos).

El **[marketplace](marketplace.md)** es el catálogo del proyecto: **detecta las actualizaciones** de los addons
instalados y permite **añadir** los que no están en el paquete, con verificación de integridad SHA-256. El **núcleo del
CMS** se actualiza con un clic desde la **Monitorización**, con copia de seguridad antes de escribir y vuelta atrás
automática si falla.

Técnicamente, un addon no es más que una **carpeta de archivos PHP**: puedes versionarlo, compartirlo y reinstalarlo en
cualquier sitio NeoFrag Reborn. Es lo que hace extensible el CMS — mira las guías para desarrolladores.

## En la práctica: montar una comunidad

Para un equipo de juego: en la instalación, elige el perfil **Gaming / eSport** (foro, equipos, eventos, reclutamiento,
palmarés…) y el tema Nebula queda instalado; después, con el Editor en vivo, coloca el widget **navegación** en la
región `header` y un widget de contenido (últimos temas, próximos partidos…) en la columna lateral de la página de
inicio. Todo se ajusta en la administración, **sin una línea de código**.

Para una asociación o un club: elige el perfil **Asociación / club** — noticias, foro, galerías, calendario, colectivos,
donaciones y cuotas, boletín, wiki y FAQ, sin la parafernalia esport. Los miembros, las páginas y el formulario de
contacto están siempre; los equipos, si hacen falta, se añaden con un clic.

## Sobrescribir sin hacer un fork

Cualquier archivo incluido (vista, clase, recurso) puede **sobrescribirse** sin modificar el código original. El
framework resuelve en este orden (gana el primero encontrado):

1. `overrides/{type}/{file}` — sobrescritura global
2. `themes/{tema activo}/overrides/{type}/{file}` — sobrescritura por tema
3. el original incluido

Así adaptas un módulo o un widget a tu sitio sin tocar nunca el núcleo, y sin romper las actualizaciones.

Para seguir: [Primeros pasos](premiers-pas.md) y [Un sitio en varios idiomas](multilingue.md).
