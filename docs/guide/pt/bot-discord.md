# O bot do Discord

O bot do Discord do NeoFrag Reborn liga o teu site e o teu servidor do Discord. As suas funcionalidades ligam-se uma a
uma:

- **cargos e alcunhas** — um membro que ligou a sua conta do Discord recebe no servidor os cargos ligados aos seus
  grupos, e o seu nome do site se quiseres;
- **fórum e canais Fórum** — um fórum do site e um canal Fórum do Discord vivem juntos: um tópico torna-se uma thread,
  uma thread um tópico, as respostas seguem nos dois sentidos, e os prefixos do fórum tornam-se as etiquetas do canal;
- **conta e aparência** — o comando `/forum`: ligar a conta a partir do Discord, e escolher como se aparece no fórum sem
  conta ligada;
- **Bugtracker** — cada ticket torna-se uma thread, o seu tipo e o seu estado etiquetas; `/bug` e `/idee` abrem um
  ticket a partir do Discord;
- **cargos temporários** — o comando `/role`: dar um cargo por um tempo limitado, que o bot retira no fim;
- **sanções ligadas** — os silêncios e banimentos do site aplicam-se no Discord, e os de um moderador ligado podem passar
  para o site;
- **coletivos** — cada coletivo do site recebe um cargo e um canal privado, que só os seus membros veem.

**Regula-se inteiramente a partir da administração** do site: o seu token, o seu servidor, as suas funcionalidades e as
suas definições, o interruptor a funcionar / em pausa, o reinício, as correspondências, e até a criação de canais e
cargos no servidor. A administração mostra também o seu estado e o seu registo, na tua língua.

O bot é um **programa à parte** que funciona sem parar. Precisa de uma máquina ligada — um VPS, um Raspberry Pi, um
PC — com **Node.js 22.9 ou posterior**. Um alojamento partilhado não o consegue executar; o site, esse, não precisa dele
e fica onde está.

## 1. Criar a aplicação do Discord

1. No [portal de programadores do Discord](https://discord.com/developers/applications): *New Application*, dá-lhe um
   nome.
2. Separador *General Information*: anota o **Application ID**.
3. Separador *Bot*: *Reset Token*, e copia o **token do bot** — só é mostrado uma vez.
4. Ainda em *Bot*, marca os dois **intents privilegiados**:
   - **Server Members Intent** — para os cargos, as alcunhas e os cargos temporários;
   - **Message Content Intent** — para copiar para o site o texto das mensagens do Discord (fórum, Bugtracker).

   Sem eles, o bot funciona em modo reduzido e di-lo no seu registo: nunca pede ao Discord mais do que a aplicação tem
   direito a receber.

## 2. Configurar o módulo Discord

O bot apoia-se em dois módulos do site, **API** e **Discord**. O perfil de instalação *Completo* instala os dois; senão,
*Sistema → Temas & Addons* adiciona-os (o Discord, um addon opcional, vem do marketplace se não estiver no servidor). A
sincronização do fórum precisa também do módulo **Fórum**, a dos tickets do módulo **Bugtracker**.

*Administração → Discord → Ligação*:

| Campo | Onde o encontrar |
|---|---|
| Application ID | o *Application ID* do passo 1 |
| ID do servidor | no Discord, com o modo de programador ativo: clique direito no servidor → *Copiar ID do servidor* |
| Token do bot | o token do passo 1. É **guardado cifrado** e nunca mais é mostrado; deixa o campo vazio para manter o guardado |

Depois **Convidar o bot** (página inicial do módulo): a ligação adiciona o bot ao servidor só com as permissões de que
precisa — nunca «Administrador»:

| Permissão | Para |
|---|---|
| Ver canais, Enviar mensagens, Ler o histórico de mensagens | ler e escrever nos canais ligados |
| Enviar mensagens em threads, Criar threads públicas, Gerir threads | copiar um tópico ou um ticket como thread, mudar-lhe o nome, arquivá-la, pôr as suas etiquetas |
| Gerir canais | a configuração do servidor: criar uma categoria, canais Fórum e as suas etiquetas |
| Gerir mensagens | levar para o Discord uma eliminação feita no site |
| Gerir webhooks | publicar as mensagens do site com o nome e o avatar do seu autor |
| Adicionar reações, Incorporar ligações, Anexar ficheiros | mensagens completas |
| Gerir cargos, Gerir alcunhas | os cargos, as alcunhas e os cargos temporários |
| Usar comandos da aplicação | `/forum`, `/bug`, `/idee`, `/role` |

Nas definições do servidor (*Cargos*), coloca o cargo do bot **acima** dos cargos que atribui: o Discord proíbe um bot
de dar um cargo colocado mais acima do que o seu.

## 3. Instalar o bot na sua máquina

O bot transfere-se como arquivo já compilado, `neofrag-reborn-bot-X.Y.Z.tar.gz`, na
[página de versões](https://github.com/NeoFragReborn/bot-discord/releases/latest) do seu repositório. Tem os seus
próprios números de versão, independentes dos do site.

```bash
tar xzf neofrag-reborn-bot-X.Y.Z.tar.gz && cd bot
npm ci --omit=dev
cp .env.example .env
```

*Administração → Discord → Criar a chave de acesso*: a página dá **duas linhas** — o endereço do site e a chave de acesso
do bot — a colar em `.env`. Só são mostradas uma vez; criar uma nova chave revoga a antiga. Depois:

```bash
npm start
```

Para que funcione como serviço, arranque com a máquina e reinicie se cair: `neofrag-bot.service` (systemd, reforçado)
explica a sua instalação no início do ficheiro. O seu registo: `journalctl -u neofrag-bot -f` — e, sobretudo, a
administração do site.

Na máquina ficam só essas duas linhas. Todo o resto chega pelo site.

**Atualizar o bot**: substitui a pasta pela do novo arquivo mantendo a tua configuração (`.env`, ou
`/etc/neofrag-bot/bot.env` com systemd), depois `npm ci --omit=dev` e reinicia o serviço. Uma nova versão do bot que
precise de mais direitos no site di-lo na administração: cria então uma nova chave de acesso.

## 4. Conduzi-lo a partir da administração

*Administração → Discord* mostra:

- o seu **estado** — *Online*, *Em pausa* (vivo, interruptor desligado), *A ligar ao Discord…* ou *Offline* (sem
  notícias há um minuto e meio) —, a sua versão, o seu último sinal de vida;
- os botões **Pôr a funcionar / Pôr em pausa**, **Reiniciar** e **Ressincronizar** (voltar a pôr tudo de acordo: cargos
  e alcunhas, e o que foi escrito no Discord durante uma ausência do bot), que aplica no minuto seguinte;
- o seu **registo**: ligações, sincronizações e o que o impede de funcionar (intent por marcar, cargo mal colocado,
  permissão em falta, chave de acesso demasiado antiga), com o que é preciso fazer.

**Mudar o token do bot** (fuga, regeneração): cola o novo em *Ligação*. O bot volta a lê-lo e liga-se de novo sozinho;
não se mexe na máquina.

### Funcionalidades

*Discord → Funcionalidades* lista o que o bot sabe fazer — é ele próprio que as declara em cada sinal de vida: uma
funcionalidade adicionada ao bot aparece lá sem mexer no site. Cada uma **liga-se** ou **desliga-se**, e as suas
**definições** mudam-se lá; o bot aplica a mudança no minuto seguinte, sem reiniciar. O Bugtracker, os cargos
temporários, as sanções ligadas e os coletivos estão desligados no início.

### Configuração do servidor

*Discord → Configuração do servidor* cria no Discord o que é preciso para ligar o site, sem fazer nada à mão:

- uma **categoria** (reutilizada se já existir);
- um **canal Fórum por cada fórum** escolhido, com os prefixos do fórum como etiquetas;
- um **cargo por cada grupo** escolhido, colocado abaixo do do bot para que o possa atribuir.

Um canal ou um cargo com o mesmo nome é reutilizado em vez de ser duplicado. Uma **pré-visualização** mostra o que vai
ser criado e reutilizado; **Aplicar** fá-lo, e as correspondências (canal ↔ fórum, grupo ↔ cargo) ficam definidas
sozinhas. **Anular a última configuração** elimina o que criou — nunca o que reutilizou.

## Cargos e alcunhas

*Discord → Grupos e cargos* liga um grupo do site a um cargo do servidor. **O site faz fé**:

- um membro ligado que está no grupo recebe o cargo; quem já não está perde-o;
- um cargo que nada liga **nunca é tocado**; nem um membro que não ligou a sua conta;
- com a definição *Alcunhas*, o membro ligado usa no servidor o seu nome do site (exceto o dono do servidor, que o
  Discord protege);
- um cargo ligado tem o **nome e a cor do seu grupo** (bot 0.2.5): um grupo com o nome ou a cor alterados no site
  também o tem no servidor. Um cargo ligado a vários grupos mantém os seus, e um grupo sem cor deixa a do cargo. A
  definição «Dar aos cargos ligados o nome e a cor do seu grupo» desliga isto.

O bot aplica uma mudança de grupo em meio minuto, a chegada de um membro ligado ao servidor e a ligação de uma conta de
imediato, e volta a passar por todos ao arrancar, em cada mudança de definição e no intervalo escolhido (dez minutos por
predefinição).

## A conta e a aparência: `/forum`

Um membro liga a sua conta iniciando sessão no site com o Discord, a partir de *A minha conta → As minhas contas
associadas*, ou **a partir do Discord**:

- `/forum account link` dá uma ligação de uso único, válida quinze minutos. No site, com sessão iniciada, o membro
  confirma a ligação — e pode recuperar em seu nome o que tinha publicado a partir do Discord;
- `/forum account unlink` desliga, exceto se o Discord for a sua única forma de iniciar sessão no site.

Quem não ligou a sua conta escolhe como aparece no fórum do site:

- `/forum visibility public` — com o seu nome do Discord;
- `/forum visibility guest` — com um nome anónimo;
- `/forum visibility custom` — com um nome à sua escolha (2 a 32 caracteres, alterável a cada sete dias, nunca o de um
  membro do site);
- `/forum visibility status` — o que está definido.

As suas mensagens já publicadas seguem a sua escolha. Todas as respostas são privadas, na língua do Discord de cada um.

## O fórum e os canais Fórum

*Discord → Canais e fóruns* liga um fórum do site a um **canal Fórum** do Discord (não um canal de texto). Dois modos,
canal a canal:

- **Tudo**: cada thread do canal torna-se um tópico do site, e cada tópico do fórum uma thread;
- **A pedido**: uma thread só passa para o site quando alguém que pode *gerir as threads* do canal põe a **reação**
  escolhida (📌 por predefinição) — a thread inteira, e depois o que se segue. Os tópicos do site, já públicos, vão sempre
  para o Discord.

O que segue, e como:

| | Do site para o Discord | Do Discord para o site |
|---|---|---|
| Novo tópico / thread | uma thread, com o nome e o avatar do autor | um tópico |
| Resposta | uma mensagem na thread | uma resposta (a citação de uma mensagem é mantida) |
| Edição | a mensagem é reescrita, o título da thread acompanha | a mensagem é reescrita |
| Eliminação | a mensagem desaparece; um tópico eliminado leva a sua thread | a mensagem vai para o lixo do fórum |
| Prefixo / etiqueta | é posta a etiqueta correspondente | é posto o prefixo correspondente |

- **Prefixos e etiquetas** (botão de cada canal ligado): um prefixo do fórum ↔ uma etiqueta do canal. A configuração do
  servidor liga-os sozinha.
- Uma mensagem vinda do Discord é publicada com a **conta do membro** que ligou o seu Discord; senão, com a sua
  **identidade do Discord**, tal como a escolheu com `/forum visibility`, marcada com o logótipo do Discord.
- As **sanções de moderação** do site seguem o membro ligado: silenciado ou banido do fórum, as suas mensagens do
  Discord não são copiadas; privado de ligações externas, uma mensagem que as tenha também não (bot 0.2.5). O registo do
  bot anota-o, sem dizer nada no canal; `/bug` e `/idee` respondem só ao autor.
- Uma mensagem demasiado longa para o Discord (2000 caracteres) é cortada lá, com uma ligação para o resto.
- As **imagens** de uma mensagem do site vão para o Discord como pré-visualizações, por baixo do texto; as ligações da
  mensagem não mostram lá um cartão de pré-visualização.
- Uma **imagem anexada no Discord** (JPEG, PNG, GIF ou WebP, 5 MB no máximo) é guardada no site e mostrada na mensagem
  do fórum — um site demasiado antigo para a receber mantém a ligação. Os outros anexos, e todos os do Bugtracker, são
  assinalados com uma ligação para a mensagem do Discord.
- Definições: uma ligação para o site por baixo de cada tópico copiado, e a **recuperação** ao arrancar do que foi
  escrito no Discord enquanto o bot estava desligado.
- As **permissões** de cada canal ligado seguem os direitos do seu fórum no site (bot 0.2.5, definição ligada por
  predefinição). Todo o servidor conta como os membros do site: quem não pode ler o fórum não vê o canal, quem não pode
  escrever nele não publica lá — a escrita só no modo **Tudo**; no modo **A pedido**, o Discord continua a ser um lugar
  de conversa. Um cargo ligado dá ao seu grupo o que os membros não têm: o canal de um fórum da equipa só se mostra aos
  cargos da equipa. O bot só toca na visualização e na escrita, para @everyone, os cargos ligados e ele próprio;
  qualquer outra definição feita à mão fica.
- O site, por seu lado, recusa escrever em nome de quem não tem esse direito: uma mensagem vinda do Discord num fórum
  onde o seu autor não pode escrever fica no Discord, e o registo do bot di-lo.
- Não são transpostas: a eliminação de uma thread inteira no Discord (o tópico fica, o registo assinala-o), a da sua
  mensagem inicial, e no Discord a edição de uma mensagem escrita no Discord (pertence ao seu autor). A cópia no Discord
  de uma mensagem do site, eliminada por um moderador, não elimina o original.

## O Bugtracker

Nas definições da funcionalidade, escolhe o **canal Fórum dos tickets** (não um canal já ligado a um fórum). O bot cria
lá as etiquetas dos tipos (Bug, Ideia, Pergunta, Outro) e dos estados (Aberto, Em curso, Resolvido, Fechado, Não será
feito, Duplicado), na língua do site.

Para separar as ideias dos bugs, escolhe também um **canal Fórum das sugestões** (bot 0.2.5): os tickets do tipo Ideia
têm lá a sua thread, com a etiqueta Ideia e as dos estados; os outros ficam no canal dos tickets.

- Cada ticket torna-se uma **thread**, com o nome e o avatar do seu autor, com o seu número, o seu tipo, a sua
  prioridade e uma ligação para o site. Os tickets ainda abertos recebem a sua thread quando escolhes o canal — e,
  quando escolhes o canal das sugestões, as ideias ainda abertas passam para lá.
- Um ticket que **muda de tipo** (um bug que passa a ideia) muda de canal: o Discord não move uma thread, por isso o bot
  abre uma nova no canal certo. A thread antiga, fechada, remete para ela, e a nova remete para a antiga. No site, o
  ticket mantém todos os seus comentários.
- Quando o ticket muda no site, a thread acompanha: etiquetas, título, descrição. **Arquiva-se** quando o ticket é
  fechado; um duplicado remete para o seu ticket original; um ticket eliminado leva a sua thread.
- Os **comentários** passam nos dois sentidos. No Discord, quem não ligou a sua conta comenta com o seu nome do
  Discord.
- `/bug` e `/idee` abrem um ticket através de uma pequena janela (título, descrição), e uma thread aberta à mão num dos
  canais torna-se um ticket — uma ideia no canal das sugestões. Um ticket pertence a um membro: é preciso ter ligado a
  conta (senão o bot lembra-o).
- **O site faz fé**: uma etiqueta mudada à mão no Discord volta a ser posta como diz o ticket.

## Os cargos temporários: `/role`

Reservado a quem pode **gerir os cargos** no servidor:

- `/role give` — dar um cargo a um membro por um tempo (minutos, horas, dias, semanas), com um motivo se se quiser. Dado
  outra vez, prolonga-se;
- `/role remove` — retirá-lo de imediato;
- `/role list` — os que estão em curso, para todo o servidor ou para um membro.

O bot retira o cargo no fim do prazo (verificado a cada minuto), **mesmo depois de um reinício**: a lista vive no site,
em *Discord → Cargos temporários*, onde **Retirar agora** antecipa o fim. Um membro que sai do servidor e volta antes do
fim recupera o seu cargo (definição *Devolver o cargo*).

São recusados: `@everyone` e os cargos de uma integração, os cargos **ligados a um grupo** do site (é a sincronização dos
grupos que os gere), e — como faz o Discord para este direito — um cargo colocado acima do bot, ou ao nível de quem o dá
ou acima. A duração é limitada pela definição *Duração máxima* (90 dias por predefinição, um ano no máximo).

## As sanções ligadas

Desligada por predefinição: *Discord → Funcionalidades → Sanções ligadas*. Dois sentidos, cada um com a sua definição.

**Do site para o Discord** (*Aplicar no Discord os silêncios e banimentos de todo o site*, ligada com a funcionalidade):
uma sanção de **todo o site** segue a conta do Discord ligada do membro.

| No site | No Discord |
|---|---|
| Silêncio | um isolamento temporário até ao fim do prazo — renovado pelo bot para lá dos 28 dias que o Discord permite |
| Banimento temporário | um banimento, levantado pelo bot no fim do prazo |
| Banimento definitivo | um banimento |

Um aviso, uma restrição, um shadow ban (tem de ficar invisível) e a sanção de um só módulo (o fórum, os salões…) nunca
passam. Uma sanção à espera de validação passa quando é validada. Levantada no site, é levantada no Discord — o bot só
retira o que ele próprio pôs (o seu motivo tem «NeoFrag #número») ou o que vinha do Discord. Volta a ler as sanções em
curso ao arrancar, e depois a cada dez minutos: um fim de prazo passado enquanto estava parado é recuperado.

**Do Discord para o site** (*Fazer subir ao site as sanções aplicadas no Discord*, desligada por predefinição): um
banimento ou um isolamento temporário aplicado no Discord passa para o site se o moderador for uma pessoa cuja conta do
Discord esteja ligada a uma conta do site que tenha esse direito — *moderar* para um isolamento (um silêncio no site),
*banir definitivamente* para um banimento —, e se o membro sancionado tiver ligado a sua. O site volta a julgá-la como
se a tivesse aplicado ele próprio (hierarquia, validação); senão fica no Discord, e o registo do bot diz porquê. Uma
sanção vinda do Discord levanta-se no Discord (desbanir, retirar o isolamento) ou no site; uma sanção do site só se
levanta no site.

A ligação de convite não dá estes direitos ao bot: acrescenta-os ao seu cargo — *Banir membros* e *Isolar membros* para
o primeiro sentido, *Ver o registo de auditoria* para o segundo. O bot diz no seu registo o que lhe falta. O Discord
recusa sancionar o dono do servidor e um membro colocado acima do cargo do bot.

## Coletivos

Desligada por predefinição: *Discord → Funcionalidades → Coletivos*. Cada coletivo do site cuja caixa *Discord* esteja
marcada ([Coletivos](admin.md#coletivos)) recebe no servidor:

- um **cargo**, com o seu nome — é o grupo do coletivo, ligado a este cargo: a funcionalidade *Cargos e alcunhas* dá-o
  aos seus membros que ligaram a sua conta, e retira-o a quem sai;
- um **canal de texto** na categoria *Collectifs* (o nome regula-se; o bot cria-a), fechado a `@everyone`, aberto a este
  cargo.

O bot cria o que falta ao arrancar, quando a configuração muda (um coletivo criado ou com o nome alterado) e a cada cinco
minutos: um cargo ou um canal eliminado à mão volta, um coletivo com o nome alterado é renomeado. Um coletivo eliminado,
ou cuja caixa é desmarcada: o bot elimina o seu cargo, e o seu canal **fica**, visível só para os administradores do
servidor. O cargo do bot precisa de *Gerir cargos* e *Gerir canais* (na ligação de convite), e tem de estar acima dos
cargos que cria.

## Segurança

- Na máquina do bot: o endereço do site e a sua chave de acesso, nada mais.
- O token do bot está cifrado na base de dados do site com a chave própria do site. Só uma chave de acesso com o direito
  `discord:bot` o pode ler — a que a administração cria para o bot, revogável a qualquer momento.
- O bot nunca menciona ninguém: uma mensagem copiada não pode notificar `@everyone`.
- Não precisa de «Administrador», e a ligação de convite não o pede. Se o tiver mesmo assim (um cargo dado à mão),
  di-lo no seu registo em cada arranque: retira-lho — um bot administrador cujo token fuja abre todo o servidor. Indica
  também as permissões da ligação de convite que faltam ao seu cargo.
- O que chega do Discord — um nome de thread, um nome de utilizador, um comentário — é guardado no site como o que lá se
  escreve: é mostrado, não executado.

## Escrever uma funcionalidade

O bot alarga-se com **funcionalidades**, cada uma na sua pasta, em TypeScript. Como fazer — o contrato de uma
funcionalidade, as suas definições, os seus comandos, os seus textos traduzidos, os seus testes — está no guia do
contribuidor do bot, `CONTRIBUTING.md`, na raiz do seu código:
[NeoFragReborn/bot-discord](https://github.com/NeoFragReborn/bot-discord).
