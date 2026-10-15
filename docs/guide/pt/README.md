# Documentação do NeoFrag Reborn

Bem-vindo à documentação pública do **NeoFrag Reborn**, o CMS livre para comunidades, do gaming às associações: monta o
site da tua equipa, do teu clã, do teu clube ou da tua associação sem escrever uma linha de código, e alarga-o quando te
apetecer.

> O NeoFrag Reborn é a continuação comunitária do **NeoFrag**, criado originalmente por **Michaël BILCOT** e
> **Jérémy VALENTIN** ([neofr.ag](https://neofr.ag)). Um projeto de código aberto sob a licença **LGPLv3**. Estes guias
> são traduzidos dos guias em francês, que são **verificados no código**; também são publicados na wiki de
> [neofrag-reborn.xyz](https://neofrag-reborn.xyz).

## Para os utilizadores

| Guia | O que aprendes |
|---|---|
| [Instalação](installation.md) | Pôr o NeoFrag Reborn online: requisitos, o instalador em cinco passos e os seus perfis, a linha de comandos, as primeiras definições |
| [Primeiros passos](premiers-pas.md) | Os dez passos que transformam uma instalação nova num site pronto a abrir: nome, tema, página inicial, menu, fórum, membros, cópias de segurança |
| [Conceitos](concepts.md) | Como o CMS está organizado: módulos, widgets, temas, zonas e regiões, disposições, addons |
| [O painel de administração](admin.md) | Gerir o teu site: conteúdo, membros, permissões, definições, monitorização |
| [O marketplace](marketplace.md) | Encontrar e instalar addons, atualizar os addons e o núcleo |
| [Um site em várias línguas](multilingue.md) | O que se traduz sozinho, o que traduzes tu, o que um visitante vê quando falta uma tradução |
| [O bot do Discord](bot-discord.md) | Ligar o teu site ao teu servidor do Discord: cargos e alcunhas, fórum ↔ canal Fórum, tudo regulado a partir da administração — e escrever uma funcionalidade do bot |

## Para os programadores

| Guia | O que aprendes |
|---|---|
| [O framework](framework.md) | Localizador de serviços, encaminhamento, base de dados, formulários, tabelas, traduções, o front end sem jQuery sob uma CSP estrita, testes e verificações |
| [Criar um módulo](create-a-module.md) | Uma funcionalidade completa: declarações, rotas, checker, dados e migrações, dependências, concentradores, permissões, administração |
| [Criar um widget](create-a-widget.md) | Um bloco reutilizável: definições com valores de recurso, vocabulário CSS partilhado, JavaScript puro |
| [Criar um tema](create-a-theme.md) | Zonas e regiões, esqueleto, disposições predefinidas, o vocabulário `--nf-*` completo, cores ajustáveis, miniatura, migrações do tema |
| [A API REST](api.md) | Pôr um programa a falar com o site — um bot, uma integração: chaves de acesso, direitos, endereços, erros, limite de frequência |

## Em resumo

O NeoFrag Reborn instala-se num alojamento **PHP 8.2+ / MySQL ou MariaDB** comum (alojamento partilhado incluído),
**à la carte**: um perfil do site na instalação, depois módulos a ativar ou a adicionar. Uma vez no sítio, constróis o
teu site com o rato: colocas **widgets** nas **regiões** do teu **tema** com o Editor ao vivo, e transferes extras do
**marketplace**.

Tudo pode ser **sobreposto sem fazer um fork** (um sistema de sobreposição em três níveis), e cada extensão é uma simples
pasta de ficheiros PHP que podes versionar e partilhar.

Outras línguas: [Français](../README.md) · [English](../en/README.md) · [Deutsch](../de/README.md) · [Español](../es/README.md) · [Italiano](../it/README.md)
