# Primeiros passos

O teu site acabou de ser instalado ([Instalação](installation.md)): eis, por ordem, o que o transforma de página vazia
num site que podes abrir à tua comunidade. Conta com uma hora. Cada passo remete para o guia que o explica em pormenor.

## 1. Entrar na administração

Inicia sessão com a conta criada no fim do instalador e abre `/pt/admin` (ou a ligação *Administração* na barra de
membro). A coluna lateral arruma os módulos por secções — *Conteúdo*, *Comunidade*, *Sistema*… —; `Ctrl + K` abre a
pesquisa rápida. A visita completa está em [O painel de administração](admin.md).

## 2. Dar um nome ao site

*Sistema → Definições*: o nome do site, a sua descrição, o endereço de contacto, o fuso horário. O nome aparece no
cabeçalho do tema e no separador do navegador; a descrição serve aos motores de pesquisa enquanto uma página não der a
sua própria ([Referenciação](admin.md#referenciação)).

## 3. Escolher o tema e as cores

*Sistema → Temas & Addons*: cada tema mostra a sua página inicial em miniatura. Ativa aquele que se parece com a tua
comunidade e abre as suas definições: a página das **cores** mostra a mudança ao vivo, e recusa uma cor que tornaria o
texto ilegível. O que é um tema, as suas zonas e as suas regiões: [Conceitos](concepts.md).

## 4. Compor a página inicial

*Sistema → Editor ao vivo*: o site aparece como os visitantes o veem, com as suas regiões delimitadas. Arrasta para lá
**widgets** — as últimas notícias, o fórum, os membros online, um diaporama — e regula cada um com um clique. Uma
disposição vale para uma página ou para todo o site.

## 5. Construir o menu

*Conteúdo → Menus*: adiciona as ligações da barra de navegação — início, o fórum, as notícias, uma página. Uma ligação
para um módulo escolhe-se numa lista; uma ligação externa cola-se.

## 6. Publicar uma primeira notícia

*Conteúdo → Notícias → Adicionar notícia*: um título, uma imagem, o texto. Aparece na página inicial se lá colocaste o
widget das notícias, e os leitores podem comentá-la se os comentários estiverem abertos.

## 7. Abrir o fórum

*Comunidade → Fórum*: cria uma **categoria** («Geral»), depois os seus **fóruns** («Apresentações», «Ajuda»). Os
**prefixos** («Pergunta», «Resolvido») ajudam a arrumar os tópicos. Quem lê e quem escreve regula-se por categoria, com o
botão do cadeado.

## 8. Acolher os membros

*Sistema → Definições → Inscrições*: abertas, por convite ou fechadas, com ou sem captcha
([Inscrições](admin.md#inscrições)). Os **cargos** decidem o que cada um pode fazer: cria um cargo «Moderador», marca os
seus direitos na matriz, e dá-o aos membros de confiança ([Permissões](admin.md#permissões)).

## 9. Acrescentar o que falta

O site foi instalado com um perfil — gaming, comunidade, associação. Um módulo em falta (o calendário, as doações, os
coletivos, o bot do Discord…) adiciona-se em *Sistema → Temas & Addons* ou no [marketplace](marketplace.md), sem
reinstalar.

## 10. Pô-lo em segurança

*Sistema → Monitorização*: cria uma primeira **cópia de segurança**, e anota o endereço da **tarefa agendada**.
Entrega-a ao teu alojamento (uma tarefa cron, de hora a hora): é ela que publica os conteúdos programados, envia os
lembretes e recolhe o que os serviços externos não anunciam ([Monitorização](admin.md#monitorização)).

O teu site fala várias línguas? Lê a seguir [Um site em várias línguas](multilingue.md).
