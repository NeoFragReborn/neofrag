# Um site em várias línguas

O NeoFrag Reborn fala seis línguas: francês, inglês, alemão, espanhol, italiano e português. Este guia explica o que se
traduz sozinho, o que traduzes tu e o que um visitante vê quando falta uma tradução.

## Dois tipos de texto

- **A interface** — os botões, os menus da administração, as mensagens, os e-mails: chega traduzida com os **pacotes de
  língua** (addons, incluídos no produto). Nada a fazer.
- **O teu conteúdo** — as tuas notícias, as tuas páginas, as tuas categorias, o teu menu, os títulos dos teus widgets,
  as páginas da tua wiki, as tuas campanhas de doação…: escreves tu, e escreves **língua a língua**.

## Escrever noutra língua

Um ecrã de edição escreve na **língua da administração**, e di-lo por baixo do campo: «Escrito para Português». Para
traduzir, muda de língua — o seletor de língua do site, ou o próprio endereço: `/en/admin/…` em vez de `/pt/admin/…` —
e volta a abrir o mesmo conteúdo: os seus campos estão vazios onde ainda não há nada escrito nessa língua. Guarda: as
outras línguas mantêm o seu texto.

O que não tem língua continua comum a todas: uma data, uma imagem, um preço, uma ordem de apresentação, um autor.
Introduzes isso uma só vez.

## O que o visitante vê

O visitante lê o site na língua do endereço (`/pt/…`), que escolhe com o seletor de língua do tema. Para cada conteúdo,
vê **a melhor tradução que existe**: a da sua língua, senão a francesa, senão a da primeira língua escrita. Um conteúdo
escrito numa só língua continua visível em todo o lado: simplesmente não está traduzido.

A administração mostra o que falta traduzir: as listas assinalam os conteúdos sem texto na língua do ecrã, e a
verificação das traduções anota os textos da interface em falta numa língua
([O painel de administração](admin.md)).

## O que não se traduz

- O que **os membros** escrevem — um tópico do fórum, uma mensagem, um comentário, um classificado — fica na língua em
  que foi escrito.
- Os **emojis personalizados** mantêm o seu nome em todo o lado: é um código, não um texto.

## Os motores de pesquisa

Cada língua tem o seu mapa do site, e cada página indica as suas outras línguas. Um conteúdo que não tem língua própria
— um tópico do fórum, ou uma página cujo texto é o mesmo em todas as línguas — declara-se só na primeira língua do site:
seis cópias de um mesmo texto, cada uma «oficial», fariam com que nenhuma fosse mantida. O pormenor:
[Referenciação](admin.md#referenciação).

## Para os programadores

Um módulo que guarda conteúdo traduzível coloca-o numa tabela `<table>_lang` (uma linha por língua), lê-o com
`join_lang()` e escreve-o com `ecrire_lang()`: [O framework](framework.md).
