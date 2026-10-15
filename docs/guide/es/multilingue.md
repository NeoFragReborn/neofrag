# Un sitio en varios idiomas

NeoFrag Reborn habla seis idiomas: francés, inglés, alemán, español, italiano y portugués. Esta guía explica lo que se
traduce solo, lo que traduces tú y lo que ve un visitante cuando falta una traducción.

## Dos clases de texto

- **La interfaz** — los botones, los menús de la administración, los mensajes, los correos: llega traducida con los
  **paquetes de idioma** (addons, incluidos con el producto). Nada que hacer.
- **Tu contenido** — tus noticias, tus páginas, tus categorías, tu menú, los títulos de tus widgets, las páginas de tu
  wiki, tus campañas de donaciones…: lo escribes tú, y lo escribes **idioma por idioma**.

## Escribir en otro idioma

Una pantalla de edición escribe en **el idioma de la administración**, y lo dice bajo el campo: «Escrito para Español».
Para traducir, cambia de idioma — el selector de idioma del sitio, o la propia dirección: `/en/admin/…` en lugar de
`/es/admin/…` — y vuelve a abrir el mismo contenido: sus campos están vacíos donde aún no hay nada escrito en ese
idioma. Guarda: los demás idiomas conservan su texto.

Lo que no tiene idioma sigue siendo común a todos: una fecha, una imagen, un precio, un orden de presentación, un autor.
Lo introduces una sola vez.

## Lo que ve el visitante

El visitante lee el sitio en el idioma de la dirección (`/es/…`), que elige con el selector de idioma del tema. Para cada
contenido, ve **la mejor traducción que existe**: la de su idioma, si no la francesa, si no la del primer idioma
escrito. Un contenido escrito en un solo idioma sigue siendo visible en todas partes: simplemente no está traducido.

La administración muestra lo que queda por traducir: las listas señalan los contenidos sin texto en el idioma de la
pantalla, y la revisión de traducciones anota los textos de la interfaz que faltan en un idioma
([El panel de administración](admin.md)).

## Lo que no se traduce

- Lo que escriben **los miembros** — un tema del foro, un mensaje, un comentario, un anuncio clasificado — se queda en el
  idioma en que se escribió.
- Los **emojis personalizados** conservan su nombre en todas partes: es un código, no un texto.

## Los buscadores

Cada idioma tiene su mapa del sitio, y cada página indica sus otros idiomas. Un contenido que no tiene idioma propio — un
tema del foro, o una página cuyo texto es el mismo en todos los idiomas — se declara solo en el primer idioma del sitio:
seis copias de un mismo texto, cada una «oficial», harían que no se conservara ninguna. El detalle:
[Posicionamiento](admin.md#posicionamiento).

## Para los desarrolladores

Un módulo que guarda contenido traducible lo coloca en una tabla `<table>_lang` (una fila por idioma), lo lee con
`join_lang()` y lo escribe con `ecrire_lang()`: [El framework](framework.md).
