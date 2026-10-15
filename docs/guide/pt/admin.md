# O painel de administração

A administração fica em **/admin**. Segue o visual do NeoFrag Reborn (azul-marinho + turquesa, claro ou escuro à
escolha) e organiza-se em torno de uma **barra lateral**.

## Pontos de referência

- **Barra lateral**: os módulos estão arrumados em nove secções, mais *Sistema* (Definições, Utilizadores, Permissões,
  Temas & Addons, Monitorização…). As secções abrem-se em acordeão; uma secção vazia fica escondida.

  | Secção | Módulos |
  |---|---|
  | *Conteúdo* | Páginas, Blog, Notícias, Slider, Citações, Receitas, Menus |
  | *Comunidade* | Fórum, Coletivos, Conversa, Comentários, Livro de visitas, Emojis |
  | *Animação* | Calendário, Sondagens, Gamificação, Classificados |
  | *Gaming* | Eventos, Equipas, Jogos / mapas, Recrutamentos, Palmarés, Parceiros |
  | *Conhecimento* | Wiki, FAQ, Dicionário, Transferências, Diretório de ligações, Mapa de locais |
  | *Multimédia* | Multimédia, Galerias, Ficheiros, Rádio web |
  | *Difusão* | Newsletter, Modelos de email, Feed RSS, Discord, Webhooks, API |
  | *Suporte* | Contacto, Bugtracker, Moderação |
  | *Monetização* | Loja, Doações, Pagamentos, Gestão publicitária |

  Uma extensão do marketplace que esta arrumação não conhece vai para *Outros módulos*. O menu *Navegação* do Editor ao
  vivo segue as mesmas secções, em submenus, com uma pesquisa.
- **Afixados**: passa por cima de um módulo na barra lateral e clica no pionés para o adicionar a *Afixados* (atalhos no
  topo, memorizados pelo teu navegador).
- **Pesquisa rápida**: `Ctrl/Cmd + K` abre a paleta de comandos para ir a qualquer módulo ou ação.
- **Cabeçalho**: trilho de navegação + ações contextuais (Permissões, Configuração, Ajuda), «Ver o site» e o
  interruptor claro/escuro.

## Tarefas frequentes

| Quero… | Vou a |
|---|---|
| Publicar uma notícia / um artigo do blog / uma página | *Conteúdo* → o módulo em causa |
| Arrumar os artigos do blog em categorias e em séries (um artigo em várias partes) | *Conteúdo → Blog*, botões «Categorias» e «Séries»; a série de um artigo e a sua posição escolhem-se no seu formulário |
| Gerir o fórum e os comentários; a moderação | *Comunidade*; *Suporte → Moderação* |
| Criar uma equipa privada — uma redação, uma equipa de moderação — com a sua secção do fórum e o seu canal do Discord | *Comunidade → Coletivos* — ver [Coletivos](#coletivos) |
| Receber as quotas, acompanhar os aderentes | *Monetização → Pagamentos* — ver [Quotas](#quotas) |
| Ligar Ko-fi, HelloAsso, TipeeeStream ou PayPal às campanhas de doação | *Monetização → Doações*, cartão «Serviços ligados» — ver [Doações ligadas](#doações-ligadas) |
| Criar os prefixos dos tópicos do fórum («Pergunta», «Tutorial»…), escolher o ícone de um fórum | *Comunidade → Fórum*, botão «Prefixos»; o ícone no formulário do fórum |
| Gerir membros, grupos, sessões | *Sistema → Utilizadores* |
| Decidir quem pode fazer o quê | *Sistema → Permissões (matriz)* — grelha cargo × ação (verde = permitido, cinzento = predefinido, vermelho = nunca) |
| Mudar o tema / instalar um addon | *Sistema → Temas & Addons* |
| Definições do site (nome, página inicial, inscrições, segurança, copyright) | *Sistema → Definições* |
| Compor as páginas com o rato | *Sistema → Editor ao vivo* |
| Ligar o site a um servidor do Discord: cargos, alcunhas, fórum ↔ canal Fórum, Bugtracker, cargos temporários, sanções | *Difusão → Discord* — ver [O bot do Discord](bot-discord.md) |
| Cópias de segurança, atualizações, saúde do site | *Sistema → Monitorização* |
| Ler os erros do site, encontrar o que um visitante assinalou | *Sistema → Monitorização → Registo de erros* |
| Rever as ações sensíveis (definições, addons, contas, chaves da API) | *Sistema → Utilizadores → Registo de auditoria* |

> A **Monitorização** reúne a **saúde do site** (verificações de integridade dos ficheiros, espaço em disco, informações
> do servidor), as **atualizações**, as **cópias de segurança** (criar / transferir / restaurar) e o endereço da
> **tarefa agendada** (cron) que publica os conteúdos programados.

### Monitorização

*Sistema → Monitorização* resume o essencial no topo — versão e atualização, PHP, cópias de segurança, ferramentas de
diagnóstico ligadas — e depois lê-se em cinco separadores: *Visão geral* (alertas, saúde do site, espaço, endereço do
site), *Cópias de segurança*, *Diagnóstico*, *Servidor e segurança* (configuração, palavra-passe do webmaster, tarefa
agendada) e *Ficheiros* (a instalação comparada com a versão publicada). A página volta ao separador em que estavas; um
endereço como `admin/monitoring#diagnostic` abre o certo.

Cada atualização pelo botão faz primeiro uma **cópia de segurança** completa do site, base de dados incluída. O site
guarda sempre as **cinco mais recentes**; as outras apagam-se sozinhas ao fim de **trinta dias**, e nunca restam mais de
**dez**. Transfere a que quiseres guardar mais tempo.

### O registo de erros

*Monitorização → Registo de erros* mostra o que falhou no site, sem passar pelo FTP: os erros agrupados (o mesmo erro
visto cem vezes fica uma linha), do mais recente ao mais antigo, ordenados por gravidade — **fatal**, **erro**,
**aviso**, **informação** —, com a sua última ocorrência completa para expandir. Filtras por gravidade e por período, e
procuras uma palavra.

Quando uma página falha, o visitante lê «Ocorreu um erro» e uma **referência** de oito caracteres (`3F9A1C07`); se ta
transmitir, procura-a no registo: leva à linha exata. Os caminhos do servidor, as palavras-passe, as chaves, os endereços
de e-mail e os endereços IP ficam ocultos no ecrã; o ficheiro inteiro pode ser **transferido** (para enviar a quem te
ajuda) e **esvaziado** (o antigo fica guardado ao lado, `logs/php.log.1`). A Monitorização assinala também os erros das
últimas 24 horas, e uma pasta `logs/` em que o site já não consegue escrever.

### As ferramentas de diagnóstico

O separador *Monitorização → Diagnóstico* liga três ferramentas durante uma hora; cada uma desliga-se sozinha, ou com um
clique. Acabou-se editar `config/neofrag.php` por FTP.

- **Modo de depuração**: aparece uma barra no fundo das páginas (consultas à base de dados, tempo de cálculo, memória,
  dados da visita), e uma página que falha mostra o detalhe do erro — **só para os administradores com sessão
  iniciada**: os visitantes veem o site normal. O registo diz-te que há um problema; o modo de depuração ajuda-te a
  compreendê-lo reproduzindo-o.
- **Rastreio de páginas**: cada página servida, com as suas consultas à base de dados e a sua duração. *Ler o rastreio*
  mostra as últimas páginas, da mais recente, com pesquisa por endereço; os valores passados às consultas ficam ocultos no
  ecrã, o ficheiro completo pode ser transferido. Pesado: para o tempo de um diagnóstico.
- **Verificação das traduções**: os textos sem tradução na língua da visita são registados e listados em *Traduções em
  falta*, com a extensão que os usa. Entretanto, os administradores veem a bandeira da língua à frente de cada texto
  traduzido: um texto sem bandeira não passou pela tradução.

Uma ferramenta ligada em `config/neofrag.php` só se desliga lá; o cartão di-lo.

### O endereço do site

As ligações dos e-mails (palavra-passe esquecida, confirmação da inscrição), os regressos das autenticações externas e as
partilhas constroem-se sobre o endereço registado na instalação. Depois de uma mudança de domínio, o cartão *Endereço do
site* (Monitorização, separador *Visão geral*) assinala-o: abre a administração pelo novo endereço, e o botão *Usar*,
seguido desse endereço, regista-o (palavra-passe do webmaster pedida se existir uma).

## Permissões

O NeoFrag Reborn funciona por **cargos**. A **matriz de permissões** (por módulo) define, para cada cargo, os acessos e
as ações. Atribuis os cargos aos membros e aos grupos em *Sistema*.

**Exemplo — criar um cargo «Moderador»**:

1. Em *Sistema → Permissões*, **cria um cargo** «Moderador».
2. Na sua coluna da **matriz**, marca as ações pretendidas (moderar o fórum, gerir os comentários…) →
   **verde = permitido**.
3. Em *Sistema → Utilizadores*, edita um membro e **atribui-lhe este cargo**: obtém logo estes acessos.

### Coletivos

Um **coletivo** junta membros em torno de um projeto, longe dos outros: uma redação, uma equipa de moderação, um grupo
de criadores. Não é uma equipa de jogo (*Gaming → Equipas*, que se mostra ao público): tudo o que um coletivo recebe lhe
está reservado.

*Comunidade → Coletivos → Novo coletivo*: o seu nome, a sua descrição e o seu **chefe** (um nome de utilizador). O site
cria de uma só vez:

- uma **secção privada do fórum** — uma categoria e o seu fórum «Conversas» —, que só os seus membros podem ler e onde
  só eles podem escrever;
- dois **cargos** do site, *Coletivo «nome»* e *… — moderação*, que o módulo dá e retira sozinho: não os atribuas à
  mão;
- se a caixa *Discord* estiver marcada e o bot a funcionar (funcionalidade *Coletivos*), um **cargo e um canal
  privado** no servidor — ver [O bot do Discord](bot-discord.md#coletivos).

O chefe gere depois os membros a partir da página do coletivo (*Os meus coletivos*, na área de membros), sem ser
administrador do site. As posições:

| Posição | Pode |
|---|---|
| Chefe | adicionar membros, nomear administradores e moderadores, retirar qualquer pessoa abaixo dele |
| Administrador | adicionar membros, nomear moderadores, retirar moderadores e membros |
| Moderador | moderar a secção do fórum (editar, eliminar, afixar, fechar) |
| Membro | ler e escrever na secção |

O chefe só se nomeia na administração: um novo chefe substitui lá o anterior, que continua administrador do coletivo.
Eliminar um coletivo retira o seu espaço aos seus membros; a sua secção do fórum e o seu canal do Discord **ficam**,
visíveis só para os administradores — nada do que lá foi escrito se perde; elimina-os à mão se quiseres.

### Quotas

*Monetização → Pagamentos*. Uma quota faz de um membro um **aderente** durante um período: recebe o cargo
**«Aderente»** — abre-lhe o que quiseres em *Sistema → Permissões* (uma secção do fórum, páginas) — e entra no grupo
**«Aderentes»** (o seu distintivo, e um cargo do Discord se o ligares). Duas formas de receber:

- **online**, com o Stripe: um pacote do tipo *Adesão (dias)* («Quota anual», 365 dias) que o membro paga na página
  *Pagamentos*;
- **de outra forma** (dinheiro, cheque, transferência): o cartão *Aderentes* → *Registar uma quota* (nome de
  utilizador, duração).

Uma nova quota prolonga a adesão a partir da sua data de fim. Sete dias antes, a tarefa agendada avisa o membro (uma
notificação); na data de fim, retira-lhe o cargo. *Terminar a adesão* para-a de imediato (um erro de introdução, um
reembolso). As quotas não precisam da Gamificação — os pontos e o VIP sim.

### Doações ligadas

*Monetização → Doações*, cartão **«Serviços ligados»**: uma doação feita num destes serviços regista-se sozinha na
campanha escolhida, **uma única vez**, mesmo que o serviço avise duas vezes. As chaves são guardadas cifradas e nunca
mais são mostradas.

| Serviço | O que é preciso | Como o site verifica |
|---|---|---|
| **Ko-fi** | no Ko-fi, *More → API → Webhooks*: o endereço mostrado no cartão, e o token de verificação | o token, que só o site conhece |
| **HelloAsso** | no espaço da associação, *A minha conta → Integrações e API*: o ID e o segredo do cliente, e o endereço de notificação mostrado | volta a ler a encomenda pela API do HelloAsso: a notificação sozinha não faz fé |
| **TipeeeStream** | a chave da API da conta | nada a verificar: o site lê as doações ele próprio, em cada tarefa agendada, a partir do dia em que a chave é registada |
| **PayPal** | a caixa *Registar as doações automaticamente* | o PayPal confirma cada notificação, e o dinheiro tem de ter ido para o endereço PayPal da campanha |

Uma **adesão** paga no HelloAsso torna aderente o membro do site com o mesmo endereço de e-mail, pela duração que
definires (módulo Pagamentos). Os doadores do HelloAsso e do PayPal não aparecem na página da campanha — não deram o
seu consentimento —; os do Ko-fi e do TipeeeStream que o escolheram, sim. O total conta todas as doações. Para
experimentar sem dinheiro a sério: as caixas *sandbox* do HelloAsso e do PayPal.

## Definições essenciais

Em **Definições**: título e descrição do site, favicon, e-mail de contacto, página inicial, fuso horário,
referenciação, inscrições, segurança anti-bot (captcha), manutenção, copyright, redes sociais. Estas definições
alimentam os temas e os widgets.

### Inscrições

*Definições → Registo* abre ou fecha as inscrições, pode fazer os novos membros **confirmar o seu endereço de e-mail**,
e define dois textos, **língua a língua** (um separador por língua do site).

**A confirmação por e-mail** (desligada por predefinição): na inscrição, o membro recebe uma ligação, válida dois dias,
e não pode iniciar sessão antes de a abrir; uma tentativa de início de sessão envia uma nova (três por hora no máximo).
Uma inscrição pelo Discord, GitHub ou Google não precisa dela. Os dois textos:

- o **regulamento**, que o visitante aceita ao inscrever-se — pelo formulário como pelo Discord, GitHub ou Google, que o
  mostram antes de criar a conta;
- a **mensagem de boas-vindas**, enviada pelas mensagens privadas ao novo membro — também quando se inscreve com
  Discord, GitHub ou Google —, na língua da página onde se inscreveu. `[pseudo]` torna-se o seu nome de utilizador.
  Chega como texto: títulos, listas e ligações ficam, a formatação não; um endereço escrito por extenso torna-se uma
  ligação.

Uma língua que ainda não tem o seu texto mostra o texto comum: o primeiro texto guardado serve para todas as línguas até
serem traduzidas.

### Referenciação

O que os motores de pesquisa leem faz-se **sozinho**:

- um **mapa do site** por língua, reunidos na raiz (`/sitemap.xml`) e anunciados pelo `robots.txt` — cada módulo
  acrescenta as suas páginas públicas (o blog, o fórum e os seus tópicos, a wiki, a galeria…), só as que um visitante
  pode ler;
- em cada página, o seu **endereço de referência** (canónico), as **ligações para as suas outras línguas**, e o que as
  pré-visualizações de partilha mostram (título, descrição, imagem);
- na página inicial, os **dados estruturados** que descrevem o site, o seu logótipo e as suas redes sociais
  (*Definições → Redes sociais*);
- a pesquisa, os perfis e o diretório de membros **não** são indexados.

O que preenches, em **Definições → Referenciação**:

- para cada língua, um **slogan** (o título da página inicial passa a «Nome do site — slogan», 60 caracteres no máximo)
  e uma **descrição** (o que os motores mostram por baixo do título, 160 caracteres no máximo; vazia, usa-se a das
  Preferências gerais);
- uma **imagem de partilha** de 1200 × 630 píxeis, mostrada quando uma ligação para o site é colada no Discord, no X ou
  no Facebook — senão, o logótipo;
- o código de verificação do **Google Search Console** e do **Bing Webmaster Tools**: cria a propriedade do site na
  ferramenta, escolhe a verificação por **etiqueta HTML**, cola a etiqueta (ou só o código), e submete lá o mapa do
  site, `https://o-teu-site/sitemap.xml`;
- **Avisar os motores (IndexNow)**: marcada, o site assinala cada página que aparece, muda ou desaparece ao Bing,
  Yandex, Seznam, Naver, Yep e Amazon, em poucos minutos, em vez de esperar pela sua próxima passagem. O Google não
  participa: para o Google, é o mapa do site. A **tarefa agendada** do site tem de estar a funcionar (a *Monitorização*
  dá a linha a instalar). A primeira passagem só regista as páginas existentes; depois, só as mudanças são enviadas.

Mais três ecrãs, na mesma secção:

- **Relatório de SEO** — o que um motor de pesquisa vê, medido no site: o número de páginas do mapa e o seu módulo, os
  textos da página inicial em cada língua, a imagem de partilha, Google e Bing, IndexNow, `robots.txt`, a manutenção.
  Cada ponto remete para o que o corrige.
- **Redirecionamentos** — um endereço antigo que leva ao novo, em vez de «Página não encontrada»: uma posição
  conquistada não se perde. Uma página ou uma página da wiki **com o nome alterado** deixa sozinha o seu
  redirecionamento; acrescenta à mão os de um site antigo (`page.php`, `/fr/pagina-antiga`…). A coluna *Visitas* diz se
  o endereço antigo ainda é usado.
- **A referenciação de um conteúdo** — no cartão de edição de uma notícia, de um artigo do blog, de uma página ou de uma
  página da wiki, o botão **Referenciação**: um título para os motores (60 caracteres no máximo, o nome do site vem a
  seguir) e uma descrição (160 no máximo), em cada língua. Vazios, são o título e o início do texto.

### O captcha

O formulário de contacto e a inscrição pedem aos visitantes uma verificação anti-robô. Em **Definições → Captcha**,
escolhe o fornecedor:

- **ALTCHA** (predefinido, recomendado): nada a configurar. É alojado pelo teu site, sem conta e sem cookie; o visitante
  vê uma caixa que se marca sozinha enquanto preenche o formulário.
- **Cloudflare Turnstile**, **hCaptcha** ou **Google reCAPTCHA v2**: cria um site na consola deles (ligações na página),
  e cola a **chave do site** e a **chave secreta**. A chave secreta é cifrada e nunca mais é mostrada. Sem as suas duas
  chaves, o fornecedor é substituído pelo ALTCHA.
- **Nenhum**: desaconselhado, os robôs escrevem então à vontade.

O ALTCHA torna caro para um robô o envio em massa; contra um atacante determinado, o Turnstile ou o hCaptcha julgam
mais.

### O fuso horário

Cada um vê as horas do site no seu próprio fuso horário:

- um **membro** pode escolher o seu no perfil (**Perfil → Fuso horário**);
- senão, o site usa o do seu **navegador**, a partir da sua segunda página;
- senão — primeira visita, robôs, tarefas automáticas —, o do **site**, definido em **Definições → Preferências gerais →
  Fuso horário**.

Uma hora introduzida num formulário (o início de um evento, a reabertura depois de uma manutenção) entende-se no fuso
horário de quem a introduz. Uma data sozinha — um aniversário, um dia inteiro no calendário — nunca muda de dia. A
programação de uma rádio web escreve-se na hora do site.

### As notificações push

Um membro pode receber as suas notificações no navegador, **mesmo com o site fechado** — uma mensagem no fórum, uma
reação, um lembrete de evento. Para o oferecer, marca **Definições → Preferências gerais → Notificações push**. O site
tem de ser servido em **https**, e o servidor capaz de cifrar (OpenSSL com a curva P-256, e cURL: quase todos os
alojamentos os têm; senão a caixa fica por marcar e o site diz porquê).

Nada chega a ninguém enquanto não o pedir: cada membro ativa-o **dispositivo a dispositivo**, em **A minha conta →
Preferências de notificação → Este dispositivo**, e o seu navegador pede-lhe o consentimento. Lá escolhe também, tipo a
tipo, o que recebe desta forma, e pode enviar a si próprio um teste.

O que precisas de saber, e de dizer na tua **política de privacidade**: o navegador passa pelo seu serviço push — Google
para o Chrome e o Edge, Mozilla para o Firefox, Apple para o Safari. O site guarda lá o endereço de subscrição de cada
dispositivo enquanto o membro não desligar o push (ou não eliminar a sua conta). A mensagem é cifrada só para esse
navegador: o serviço transporta-a sem a poder ler.
