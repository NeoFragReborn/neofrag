# Documentación de NeoFrag Reborn

Bienvenido a la documentación pública de **NeoFrag Reborn**, el CMS libre para comunidades, del gaming a las
asociaciones: monta el sitio de tu equipo, tu clan, tu club o tu asociación sin escribir una línea de código, y amplíalo
cuando te apetezca.

> NeoFrag Reborn es la continuación comunitaria de **NeoFrag**, creado originalmente por **Michaël BILCOT** y
> **Jérémy VALENTIN** ([neofr.ag](https://neofr.ag)). Un proyecto de código abierto bajo licencia **LGPLv3**. Estas
> guías están traducidas de las guías en francés, que están **comprobadas contra el código**; también se publican en el
> wiki de [neofrag-reborn.xyz](https://neofrag-reborn.xyz).

## Para los usuarios

| Guía | Lo que aprendes |
|---|---|
| [Instalación](installation.md) | Poner NeoFrag Reborn en línea: requisitos, el instalador en cinco pasos y sus perfiles, la línea de comandos, los primeros ajustes |
| [Primeros pasos](premiers-pas.md) | Los diez pasos que convierten una instalación nueva en un sitio listo para abrir: nombre, tema, página de inicio, menú, foro, miembros, copias de seguridad |
| [Conceptos](concepts.md) | Cómo se organiza el CMS: módulos, widgets, temas, zonas y regiones, disposiciones, addons |
| [El panel de administración](admin.md) | Gestionar tu sitio: contenido, miembros, permisos, ajustes, monitorización |
| [El marketplace](marketplace.md) | Encontrar e instalar addons, actualizar los addons y el núcleo |
| [Un sitio en varios idiomas](multilingue.md) | Lo que se traduce solo, lo que traduces tú, lo que ve un visitante cuando falta una traducción |
| [El bot de Discord](bot-discord.md) | Vincular tu sitio a tu servidor de Discord: roles y apodos, foro ↔ canal Foro, todo ajustado desde la administración — y escribir una funcionalidad del bot |

## Para los desarrolladores

| Guía | Lo que aprendes |
|---|---|
| [El framework](framework.md) | Localizador de servicios, enrutamiento, base de datos, formularios, tablas, traducciones, el front end sin jQuery bajo una CSP estricta, pruebas y comprobaciones |
| [Crear un módulo](create-a-module.md) | Una funcionalidad completa: declaraciones, rutas, checker, datos y migraciones, dependencias, concentradores, permisos, administración |
| [Crear un widget](create-a-widget.md) | Un bloque reutilizable: ajustes con valores de respaldo, vocabulario CSS compartido, JavaScript puro |
| [Crear un tema](create-a-theme.md) | Zonas y regiones, esqueleto, disposiciones por defecto, el vocabulario `--nf-*` completo, colores ajustables, miniatura, migraciones del tema |
| [La API REST](api.md) | Dejar que un programa hable con el sitio — un bot, una integración: claves de acceso, derechos, direcciones, errores, límite de frecuencia |

## En resumen

NeoFrag Reborn se instala en un alojamiento **PHP 8.2+ / MySQL o MariaDB** corriente (alojamiento compartido incluido),
**a la carta**: un perfil del sitio en la instalación, luego módulos que activar o añadir. Una vez en marcha, construyes
tu sitio con el ratón: colocas **widgets** en las **regiones** de tu **tema** con el Editor en vivo, y descargas
complementos desde el **marketplace**.

Todo puede **sobrescribirse sin hacer un fork** (un sistema de sobrescritura en tres niveles), y cada extensión es una
simple carpeta de archivos PHP que puedes versionar y compartir.

Otros idiomas: [Français](../README.md) · [English](../en/README.md) · [Deutsch](../de/README.md) · [Italiano](../it/README.md) · [Português](../pt/README.md)
