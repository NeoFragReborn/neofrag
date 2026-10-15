# Conceitos

Compreender estas cinco noções basta para dominar o NeoFrag Reborn.

## Módulos

Um **módulo** é uma funcionalidade completa: o fórum, as notícias, a galeria, os membros, a loja… Cada módulo traz as
suas **páginas públicas**, os seus **ecrãs de administração**, os seus **dados** (tabelas) e as suas **permissões**.

O pacote inclui **63 módulos**, mas não os instalas todos: na instalação, um **perfil do site** — *Completo*, *Gaming /
eSports*, *Comunidade*, *Associação / clube* ou *Só o núcleo* — marca de antemão o que corresponde, e podes desmarcar
módulo a módulo. O que um módulo precisa é adicionado automaticamente (o palmarés precisa das equipas). Os módulos do
**núcleo** (contas, permissões, definições, páginas, ferramentas…) estão sempre lá e não se desinstalam.

Depois, em **Administração → Temas & Addons**, instalas, ativas, desativas ou desinstalas cada módulo. Um módulo
desativado desaparece do site mas guarda os seus dados. Os endereços de um módulo ausente respondem com um **404
limpo**, nunca com um erro.

## Widgets

Um **widget** é um pequeno bloco reutilizável: o menu de navegação, a área de membros, os últimos comentários, uma
contagem decrescente, um leitor do Twitch… Um mesmo widget pode ser colocado várias vezes, em sítios diferentes, com
definições diferentes. O pacote inclui **42 widgets**.

Os widgets colocam-se nas **regiões** do tema com o **Editor ao vivo**: um assistente em quatro passos (widget, tipo,
título, configuração), e arrastar e largar para os mover.

## Temas

Um **tema** dá ao site o seu aspeto: cores, tipos de letra, paginação, navegação, rodapé, e o **modo claro ou escuro**.
Declara **zonas**, dá-lhes **nomes de região** estáveis (`header`, `content`, `footer`…) e coloca as suas
**disposições** predefinidas.

O pacote inclui o **Nebula**, um tema comunitário generalista (azul-marinho e turquesa, glassmorphism), pensado para uma
equipa, um clã, um clube ou uma associação. Outros seis temas — **Granite**, **Forge**, **Blockcraft**, **Extend**,
**Chronique**, **Pulse** — estão no pacote e instalam-se em **Administração → Temas & Addons**; uma instalação que não os
tenha encontra-os no [marketplace](marketplace.md). O tema ativo escolhe-se em **Administração → Temas & Addons**; se
houver vários temas públicos instalados, os visitantes podem mudar com o seletor do rodapé.

Todos os temas falam o **mesmo vocabulário de cores** (`--nf-accent`, `--nf-surface`…): um widget adota automaticamente
o aspeto do tema ativo.

## Zonas, regiões e disposições

Um tema divide a página em **zonas**: normalmente *Header*, *Antes do conteúdo*, *Conteúdo*, *Depois do conteúdo*,
*Footer*, cada uma conhecida por um **nome de região** que os modelos usam.

Uma **disposição** descreve, para uma dada página (ou um padrão de páginas), que widgets ocupam que zona e em que grelha.
Os padrões vão do mais geral ao mais preciso:

| Padrão | Aplica-se a |
|---|---|
| `*` | todas as páginas |
| `/` | só a página inicial |
| `forum/*` | todas as páginas do fórum |

Ganha o padrão mais preciso: uma disposição `*` põe o conteúdo em oito colunas mais uma coluna lateral, uma disposição
`marketplace*` pode passar a mesma página a largura total.

Mudas as disposições com o rato no **Editor ao vivo**. Uma página pode também ter **blocos**: instâncias de módulos
ordenadas e configuradas, inseridas com o shortcode `[block:…]`.

## Addons e marketplace

**Addon** é o termo genérico para tudo o que se instala: módulos, widgets, temas, conectores de autenticação (Discord,
GitHub, Google) e pacotes de língua (seis línguas incluídas).

O **[marketplace](marketplace.md)** é o catálogo do projeto: **deteta as atualizações** dos addons instalados e permite
**adicionar** os que não estão no pacote, com verificação de integridade SHA-256. O **núcleo do CMS** atualiza-se com
um clique a partir da **Monitorização**, com cópia de segurança antes de escrever e reversão automática em caso de
falha.

Tecnicamente, um addon não passa de uma **pasta de ficheiros PHP**: podes versioná-lo, partilhá-lo e reinstalá-lo em
qualquer site NeoFrag Reborn. É o que torna o CMS extensível — vê os guias para programadores.

## Na prática: montar uma comunidade

Para uma equipa de jogo: na instalação, escolhe o perfil **Gaming / eSports** (fórum, equipas, eventos, recrutamento,
palmarés…) e o tema Nebula fica instalado; depois, com o Editor ao vivo, coloca o widget **navegação** na região
`header` e um widget de conteúdo (últimos tópicos, próximos jogos…) na coluna lateral da página inicial. Tudo se regula
na administração, **sem uma linha de código**.

Para uma associação ou um clube: escolhe o perfil **Associação / clube** — notícias, fórum, galerias, calendário,
coletivos, doações e quotas, newsletter, wiki e FAQ, sem a parafernália esport. Os membros, as páginas e o formulário de
contacto estão sempre lá; as equipas, se forem precisas, adicionam-se com um clique.

## Sobrepor sem fazer um fork

Qualquer ficheiro incluído (vista, classe, recurso) pode ser **sobreposto** sem modificar o código original. O framework
resolve por esta ordem (ganha o primeiro encontrado):

1. `overrides/{type}/{file}` — sobreposição global
2. `themes/{tema ativo}/overrides/{type}/{file}` — sobreposição por tema
3. o original incluído

Assim adaptas um módulo ou um widget ao teu site sem nunca tocar no núcleo, e sem estragar as atualizações.

Para continuar: [Primeiros passos](premiers-pas.md) e [Um site em várias línguas](multilingue.md).
