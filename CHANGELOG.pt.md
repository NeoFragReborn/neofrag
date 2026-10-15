# Changelog

Esta é a tradução portuguesa de [CHANGELOG.md](CHANGELOG.md); a versão francesa é a de referência.

Todas as alterações relevantes do **NeoFrag Reborn** ficam registadas aqui.

Formato inspirado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/);
os números de versão inspiram-se no [versionamento semântico](https://semver.org/lang/pt-BR/); uma versão que só muda o
último número (1.2.x) pode, ainda assim, trazer novidades e remoções.

O NeoFrag Reborn é a continuação comunitária do **NeoFrag** (base Alpha 0.2.4), criado originalmente por
Michaël BILCOT e Jérémy VALENTIN — um projeto de código aberto com licença LGPLv3.

---

## [1.2.50] — 2026-10-15

### Adicionado

- **Um tema mostra o seletor de idioma numa linha**: `nf_selecteur_langue()`, ao lado de
  `nf_selecteur_theme()` e `nf_liens_legaux()`. Os temas incluídos copiavam cada um o seu formulário.
- **A documentação e as notas de versão, nos seis idiomas.** Os guias da wiki — instalação, primeiros passos,
  administração, marketplace, site multilingue, bot Discord, framework, criar um tema, um módulo ou um widget — só
  existiam em francês e em inglês: escrevem-se agora também em alemão, espanhol, italiano e português, com as etiquetas
  exatas que o ecrã mostra em cada idioma, e a wiki serve-os no idioma do visitante. As notas de versão seguem o mesmo
  caminho: a página «Notas de versão» da wiki e as publicações do Blog leem-se nos seis idiomas. O francês continua a
  ser a referência; um teste recusa uma tradução que salte uma versão ou a date de outra forma.
- **As miniaturas dos módulos e dos widgets, refeitas.** Em *Temas & addons* e no marketplace, a pré-visualização de
  uma extensão era uma fotografia dela a funcionar — ilegível quando não tinha dados para mostrar: uma moldura, um
  título, umas linhas. Cada módulo e cada widget recebe agora um cartão desenhado na linguagem visual do projeto: o seu
  ícone, o seu nome e o que faz, legíveis em qualquer tamanho. Os temas mantêm a fotografia da sua página inicial: um
  tema mostra-se a si próprio.
- **As bibliotecas incluídas atualizadas, e uma salvaguarda para que assim continuem.** O editor de texto passa para o
  TinyMCE 8 (a linha 7 perdia o suporte a 14 de novembro, e a 8.9.3 corrigia uma falha do seu plugin «media») e os
  ícones para o Font Awesome 7 — os 363 ícones usados pelo site foram comparados com a nova coleção antes da mudança, e
  não falta nenhum. Os ficheiros de tipo de letra em duplicado (.ttf) desaparecem: a versão 7 só serve o formato
  moderno, menos 715 KB no pacote. Sobretudo, uma nova verificação está atenta: não dá o alarme a cada lançamento dos
  projetos de origem, mas recusa quando as versões entregues não são comparadas com o registo há mais de três meses.
  Uma dependência não se torna perigosa de repente — vai-se desviando, e esse desvio está agora à vista.
- **As notificações push.** Um membro pode receber as suas notificações no navegador, mesmo com o site fechado: uma
  mensagem do fórum, uma reação, um lembrete de um evento. O administrador propõe-no (Definições → Preferências gerais →
  Notificações push); cada membro ativa-o depois dispositivo a dispositivo, nas suas preferências de notificações,
  escolhe tipo a tipo o que recebe assim, e pode enviar a si próprio um teste. Sem bibliotecas acrescentadas: a mensagem
  é cifrada só para esse navegador (RFC 8291) e assinada em nome do site (VAPID, RFC 8292) — o serviço push do navegador
  transporta-a sem a poder ler. O servidor só escreve para serviços push conhecidos (Google, Mozilla, Apple,
  Microsoft), nunca para um endereço que um membro pudesse ter inventado.
- **O Nebula e o tema montra ganham a sua página de cores, e os oito temas uma pré-visualização ao vivo.** Destaque,
  fundo, texto: as paletas do Nebula e da montra estavam escritas nas suas folhas de estilo; ajustam-se agora como as
  dos outros seis temas, com a mesma recusa de uma cor ilegível. E por baixo dos seletores de cada tema, uma página real
  do site — a inicial, as notícias ou o fórum — adota as cores à medida que são escolhidas, sem guardar nada, com os
  contrastes medidos ao vivo: vê-se o resultado antes de clicar em «Guardar».
- **As ligações dos menus, as salas públicas, o título dado a um widget e as páginas da wiki traduzem-se.** Com elas, já
  nenhum texto escrito pelo responsável do site fica sem idioma. Uma página da wiki guarda um histórico POR IDIOMA:
  traduzir para italiano não arquiva nada da versão francesa, e o histórico italiano só mostra as suas próprias
  revisões. Uma conversa entre membros mantém um único nome para todos os participantes — só as salas públicas se
  traduzem. A página «Notas de versão» da wiki passa a existir também em inglês.
- **A loja, os donativos, a publicidade, os pacotes de pagamento e o recrutamento traduzem-se.** O nome e a descrição
  de um artigo da loja, o título e a descrição de uma campanha de donativos, o título de um anúncio, a etiqueta de um
  pacote e uma oferta de recrutamento inteira — título, introdução, descrição, requisitos e as perguntas do seu
  formulário de candidatura — escrevem-se agora idioma a idioma. As perguntas não tinham ecrã para serem editadas (só
  se podiam acrescentar ou eliminar): agora abre-as um lápis, e é aí que se traduzem. Continua comum o que não é texto:
  o preço, o stock, o endereço de uma campanha, o código de uma rede publicitária, o cargo atribuído na equipa — e as
  respostas dos candidatos, que mantêm a pergunta tal como a leram.
- **Os modos de jogo, os mapas, os programas da rádio web e os campos de perfil traduzem-se.** «Compétitif 5v5» passa a
  «Competitive 5v5» para um visitante anglófono, «Le réveil du serveur» passa a «The server wake-up», e a etiqueta de
  um campo de perfil escreve-se em cada idioma. Um nome de mapa continua a ser um nome próprio, mas também se escreve
  por idioma, para quem quiser traduzir «Faille de l'invocateur». Foi feita uma escolha, e o ecrã di-lo: a LISTA DE
  OPÇÕES de um campo de perfil continua comum — uma opção é guardada tal como está na resposta do membro, e traduzi-la
  tornaria impossível encontrar as respostas já dadas.
- **Os tipos de evento, os cargos de equipa e as rubricas dos anúncios classificados traduzem-se.** «Torneio»,
  «Capitão», «Material»: três nomes curtos que o responsável do site escreve, e que um site em seis idiomas mostrava num
  só. Escrevem-se agora idioma a idioma, e o visitante vê-os no seu em todo o lado onde aparecem — a lista dos eventos e
  as suas fichas, o calendário, o plantel de uma equipa, o diretório dos anúncios classificados e os ecrãs da
  administração.
- **Uma sondagem edita-se sem perder os seus votos — e traduz-se.** Até agora, guardar uma sondagem apagava todas as
  suas respostas para as recriar, e todos os VOTOS com elas: corrigir uma vírgula na pergunta custava a sondagem inteira
  (o ecrã avisava, o que não a tornava menos cara). Agora uma resposta mantém os seus votos enquanto continuar na lista
  — corrigida, movida ou traduzida; só uma resposta RETIRADA perde os seus, e o ecrã di-lo contando-os. É o que permite
  finalmente traduzir uma sondagem: a pergunta, a sua descrição e a etiqueta de cada resposta escrevem-se idioma a
  idioma, e escrever a versão italiana já não mexe na francesa.
- **As transferências, as ligações úteis e a FAQ traduzem-se.** O título e a descrição de um ficheiro ou de uma ligação,
  a pergunta e a sua resposta, e os títulos das suas categorias, não tinham idioma: um site em seis idiomas mostrava-os
  num só — via-se na demonstração em inglês, que anunciava «Pack de fonds d'écran» e «Chaîne Twitch». Escrevem-se agora
  idioma a idioma, como as notícias ou os eventos: o ecrã de edição abre o idioma em que se trabalha e diz qual, e o
  visitante vê-os no seu idioma — na biblioteca, no diretório de ligações, nos widgets e nos blocos. O que não tem
  idioma (endereço, tamanho, tipo, versão, contadores) continua comum, e o que só está escrito num idioma continua
  visível nos outros cinco.
- **Uma cor de tema que tornaria o site ilegível é recusada.** Os seis temas que deixam escolher a cor de destaque, do
  texto e do fundo aceitavam qualquer coisa: um destaque quase idêntico ao fundo, e as ligações e os botões
  desapareciam; um texto demasiado pálido, e a página deixava de se ler. Nada o avisava, e era preciso reencontrar
  sozinho a cor anterior. Cada cor é agora medida contra as outras duas — as guardadas, já que os dois formulários se
  guardam separadamente — e recusada, com o seu valor, quando não se destaca o suficiente: 4,5 para 1 no texto, 3 para
  1 num destaque, como exige a norma de acessibilidade. As cores originais dos nove temas passam todas.
- **As bibliotecas incluídas atualizadas.** O editor de texto (TinyMCE 7.6.1 → 7.9.3, que traz um limpador de HTML
  mais recente), os gráficos das estatísticas (Chart.js 4.4.7 → 4.5.1) e as listas pendentes (Tom Select 2.6.1 →
  2.6.2). As outras três — Bootstrap, flatpickr, Sortable — já estavam na última versão publicada. As traduções do
  editor, corrigidas à mão, são mantidas.
- **Trinta e um títulos de ecrã falavam francês nos seis idiomas.** «Atualizações», «Novo mapa», «Minha opinião sobre a
  candidatura», «Gerir a minha conta», «Parceiros», «Ofertas de recrutamento»…: os títulos de painéis, janelas e botões
  não passavam pelas traduções, e apareciam em francês fosse qual fosse o idioma do site. A sua tradução já existia, nos
  seis idiomas: só faltava a chamada. A verificação que vigia os textos escritos no código deixava-os passar porque
  supunha que esses títulos eram traduzidos pelo produto — o que só as etiquetas de FORMULÁRIO fazem realmente; agora
  distingue os dois casos.
- **Três ecrãs recuperam o desenho do seu tema.** A folha de estilo de um widget é servida depois da do tema: onde as
  duas diziam o mesmo com o mesmo peso, ganhava o widget, e o tema desenhava no vazio — um ecrã apresentável, um desenho
  pretendido nunca servido, e nada que o dissesse. O painel direito do *Extend* (quem está online, a sala de conversa) e
  os seus números grandes recuperam o seu tipo de letra e as suas cores; os separadores do perfil do *Forge* voltam a
  ser quadrados, sublinhados e em maiúsculas, como nos seus temas irmãos; e o indicador de saúde da *Monitorização*
  cabe finalmente no seu círculo, em vez de transbordar. Uma verificação recusa agora qualquer declaração de tema que
  nunca se pudesse aplicar.
- **Os eventos traduzem-se.** O título de um evento, a sua descrição, a sua descrição privada e o seu local não tinham
  idioma: um site em seis idiomas mostrava-os num só. Escrevem-se agora idioma a idioma — o ecrã de edição abre o
  idioma em que se trabalha, e diz qual. O visitante vê um evento no seu idioma em todo o lado onde aparece: a lista, a
  ficha, o calendário, os lembretes por e-mail, o mapa do site e o widget. O que não tem idioma (as datas, o tipo, a
  imagem, o autor, os participantes) continua comum; um evento escrito num só idioma continua visível nos outros cinco.
- **O palmarés traduz-se.** O nome de um título conquistado, o seu local e o seu relato não tinham idioma: um site em
  seis idiomas mostrava-os num só. Escrevem-se agora idioma a idioma, como o resto — o ecrã de edição abre o idioma em
  que se trabalha, e diz qual. O que não tem idioma (a data, a classificação, o número de participantes, a plataforma,
  a equipa, o jogo) continua comum.
- **O diaporama traduz-se.** O título e a legenda de um diapositivo não tinham idioma: um site em seis idiomas
  mostrava-os num só. Escrevem-se agora idioma a idioma, como uma página ou uma categoria — a administração escreve no
  idioma em que trabalha, e di-lo por cima do campo. O visitante vê o diapositivo no seu idioma; um diapositivo
  traduzido num só idioma continua visível nos outros cinco (o texto do idioma pedido, senão o francês, senão o
  primeiro escrito), e um diapositivo sem nenhum texto mantém a sua imagem. Nada se perde na atualização: o texto de
  hoje é copiado para todos os idiomas instalados, já que é o que todos os visitantes veem hoje.
- **As sanções do site seguem o membro no Discord.** Com o bot 0.3.0 e a sua funcionalidade *Sanções ligadas*
  (desligada de início), os silenciamentos e os banimentos de todo o site aplicam-se à conta Discord ligada do membro —
  um castigo temporário para um silenciamento, um banimento para um banimento — e são levantados no Discord quando são
  levantados no site. Um aviso, uma restrição ou um shadow ban nunca passam. No sentido inverso, e só se for ligado: um
  moderador cuja conta Discord está ligada a uma conta do site com esse direito pode banir ou castigar no Discord, e o
  site recebe a sanção e volta a julgá-la como se tivesse sido decidida nele. A lista das sanções diz agora de onde vem
  cada uma.
- **Os coletivos.** Um novo módulo reúne membros à volta de um projeto, longe dos outros: uma redação, uma equipa de
  moderação, um grupo de criadores. Criado na administração (*Comunidade → Coletivos*), um coletivo recebe uma secção
  privada do fórum, que só os seus membros leem, e — com o bot 0.3.0 e a sua funcionalidade *Coletivos* — um cargo e
  um canal privado no Discord. O seu responsável acrescenta membros e dá-lhes uma patente (administrador, moderador,
  membro) a partir da página do coletivo, sem ser administrador do site; os moderadores moderam a sua secção. Se um
  coletivo for eliminado, a sua secção e o seu canal ficam, reservados aos administradores: nada do que lá foi escrito
  se perde.
- **As quotas.** Uma associação cobra as suas quotas no site: um membro paga online (Stripe) — ou a administração
  regista uma quota paga em dinheiro ou por cheque — e torna-se **aderente** durante o período escolhido. Recebe o
  cargo «Aderente», que se pode abrir ao que se quiser (uma secção reservada do fórum, páginas), e o distintivo do grupo
  «Aderentes». Sete dias antes do fim do prazo é avisado; quando o prazo termina, o cargo é-lhe retirado. As quotas não
  precisam do módulo Gamificação, que os pagamentos exigiam até agora.
- **Os donativos feitos noutros sítios registam-se sozinhos.** Ko-fi, HelloAsso, TipeeeStream e o botão PayPal das
  campanhas: cada donativo chega à campanha escolhida, uma única vez, e a barra de progresso avança sem introdução
  manual. Nada se regista sem prova — o token do Ko-fi, a encomenda relida no HelloAsso, a confirmação do PayPal. Uma
  adesão paga no HelloAsso torna aderente o membro do site com o mesmo endereço de e-mail. Os nomes dos doadores do
  HelloAsso e do PayPal não são mostrados publicamente.

### Segurança

- **O bot Discord assinala que é administrador do servidor.** Não precisa de o ser — a ligação de convite não o pede
  —, mas um cargo atribuído à mão podia dar-lhe esse direito, e um bot administrador cujo token se divulgue abre o
  servidor inteiro. Sempre que entra no servidor, o registo do bot, que a administração mostra, di-lo, e indica as
  permissões que faltam ao seu cargo.
- **A ligação de «/forum account link» nem sempre ligava a conta certa.** Quem recebia essa ligação de outra pessoa
  ligava a conta Discord *dessa pessoa* à SUA PRÓPRIA conta do site — e como uma conta ligada permite iniciar sessão,
  quem tinha enviado a ligação podia depois entrar na conta da sua vítima. A ligação é agora confirmada junto do
  Discord: o site compara o identificador que o Discord lhe devolve com o que a ligação indica, e não liga nada se
  forem diferentes. Um aviso fazia as vezes desta verificação; não bastava.

### Corrigido

- **A definição «Tipo de letra do site» dizia que os tipos de letra eram servidos pelo Google Fonts.** São servidos
  pelo próprio site desde que nenhum visitante é enviado para a Google: a descrição agora di-lo.
- **Uma atualização de addon voltava a executar todas as suas migrações.** A leitura das migrações já feitas voltava
  sempre vazia: a cada atualização de um módulo, widget ou tema — pelo marketplace ou por um arquivo —, todas as suas
  migrações eram executadas de novo, e as que escrevem dados escreviam-nos uma segunda vez. Agora só as novas são
  executadas. `check-db-query` vigia a forma que causava o defeito: uma consulta preparada sem chegar a ser enviada.
- **A contagem decrescente dos eventos futuros ficava vazia.** O seu script vivia num tema retirado: a lista de
  eventos e a página de um evento mostravam uma ampulheta ao lado de nada, em todos os temas. Agora vive com o
  módulo, e escreve o tempo que falta no idioma da página («11 dias 7 h 22 min», «11d 7h 22m»).
- **Logo após uma atualização, o site ainda podia servir o código antigo.** A cache do PHP (OPcache) guarda o código
  compilado de um ficheiro até o voltar a verificar: dois segundos em geral, e até ao reinício do PHP nos alojamentos
  que desligam essa verificação. A atualização do núcleo, a de um addon pelo marketplace e o envio de um arquivo por
  «Adicionar» não a avisavam: uma página de um módulo atualizado respondia «página não encontrada» até os seus novos
  endereços serem relidos, e um site podia funcionar com uma mistura de código antigo e novo. Os três avisam agora a
  cache de cada ficheiro que escrevem.
- **Atualizar um addon enviando o seu arquivo saltava as suas migrações — e apagava a disposição de um tema.**
  «Adicionar», em *Temas & addons*, reinstalava o addon em vez de o atualizar: as alterações da sua base de dados
  ficavam marcadas como feitas sem o serem, e um tema perdia todas as suas disposições e os respetivos widgets,
  substituídos pela disposição de origem. Um arquivo mais recente atualiza-se agora como pelo marketplace: a base de
  dados acompanha, e a disposição continua a ser a do administrador.
- **No editor ao vivo do Nebula, as pré-visualizações dos estilos eram as do Forge**: laranja sobre castanho.
  Passam a ter as cores do Nebula, as que o administrador escolheu. A mesma correção para o tema montra.
- **Um widget de navegação que mostrava um menu guardava uma cópia congelada dele.** Copiava as ligações do menu no
  momento em que era guardado, e esquecia até qual tinha sido escolhido: uma ligação acrescentada ou renomeada depois
  nunca chegava à navegação. O menu é agora relido a cada apresentação, no idioma da página.
- **Uma ligação para uma secção de uma página da wiki levava ao topo da página.** Só os títulos de primeiro nível
  tinham uma âncora: uma ligação para um subtítulo não encontrava nada, e a documentação, que remete muitas vezes para
  uma secção precisa («ver Referenciação»), deixava o leitor no início da página, em todos os idiomas. Os subtítulos
  têm agora a sua âncora, e cada remissão da documentação leva ao título que indica.
- **Uma página da wiki colocada debaixo de uma das suas próprias subpáginas desaparecia do índice**, com toda a sua
  secção: o ecrã propunha as subpáginas como página-mãe. Já não o faz, e um ciclo destes enviado mesmo assim é
  recusado.
- **O formulário de candidatura respondia «Erro 500» assim que uma oferta fazia uma pergunta facultativa.** Uma regra
  «nenhuma» escrita como texto vazio não era entendida pela biblioteca de formulários, e a página inteira caía.
  Encontrado ao testar a tradução das perguntas.
- **O widget *Recrutamento* escondia as ofertas sem candidaturas** — ou seja, todas as ofertas acabadas de abrir. O
  módulo tinha sido corrigido; o widget não.
- **Uma confirmação de eliminação aberta em página inteira não mostrava nada.** Na administração, seguir uma ligação
  «Eliminar» num novo separador — ou sem JavaScript — levava a uma página em branco: nem mensagem, nem botão, nem
  caminho de volta. Mostra agora o que vai eliminar, o botão que o faz e uma ligação de volta ao módulo.
- **As páginas Moderação e Monitorização não tinham o aspeto do resto da administração.** Os seus cartões de números,
  os seus cabeçalhos e o pulsar do indicador de saúde vinham de uma folha de estilo de recurso — prevista para os sites
  que mostram esses ecrãs fora da administração — que, servida em último lugar, se sobrepunha ao tema. O recurso só se
  aplica agora onde o tema não diz nada.
- **Em português, uma conta não podia ser eliminada.** O ecrã pedia para escrever «EXCLUIR» e o site esperava
  «ELIMINAR»: a confirmação falhava sempre. Os outros cinco idiomas estavam certos. Uma verificação recusa agora que uma
  instrução cite uma palavra diferente da que o site espera.
- **O português misturava o Brasil e Portugal.** Oitenta expressões revistas: *usuário* passa a *utilizador*, *senha*
  a *palavra-passe*, *excluir* a *eliminar*, *registro* a *registo*, e *arquivo* passa a *ficheiro* quando se trata de
  um ficheiro — e continua *arquivo* quando se trata de um arquivo.
- **Algumas frases inglesas desajeitadas**: «All posts will be also deleted» passa a «will also be deleted», nas quatro
  mensagens de eliminação onde ainda estava.
- **Traduzir um conteúdo pela primeira vez: o texto perdia-se, sem uma palavra.** Escrever uma notícia em francês,
  passar a administração para inglês, traduzi-la e guardar: nada ficava guardado. A linha desse idioma ainda não
  existia, e a gravação não tocava em nenhuma linha — em silêncio. Isto valia para as notícias, as publicações do blog,
  as páginas, as categorias, as galerias, os jogos, as equipas, os grupos e os parceiros. Uma tradução cria agora a sua
  linha.
- **Um conteúdo ainda não traduzido era impossível de encontrar na administração desse idioma.** As listas das
  notícias, das publicações do blog e das páginas só mostravam o que existia no idioma em que se trabalha: o resto era
  invisível, logo impossível de abrir para traduzir. As listas mostram agora tudo, com o melhor título disponível, e o
  ecrã de edição abre-se com os campos vazios, prontos para a tradução.
- **Mudar o nome de um grupo apagava todas as suas traduções.** A gravação não visava nenhum idioma em particular:
  escrevia o mesmo título em todas as linhas do grupo — e falhava assim que o site tinha mais do que uma.
- **Um parceiro só podia ter um idioma.** A tabela das traduções do módulo *Parceiros* tinha como chave apenas o número
  do parceiro: um segundo idioma era recusado ou substituía o primeiro. Tem agora uma linha por idioma, como as outras
  dezasseis tabelas de tradução, e descrever um parceiro num novo idioma cria a sua linha em vez de não escrever nada.
- **O próximo encontro: o dia da semana colado ao número do dia.** Na caixa da data do widget *O próximo encontro*, as
  três linhas — dia da semana, número do dia, mês — tocavam-se, e sobrepunham-se assim que um tema aplicava o seu tipo
  de letra de títulos: os algarismos saíam de uma altura de linha mais curta do que a sua tinta. As três linhas mantêm
  agora o seu espaçamento nos sete temas, em modo claro e escuro. A mesma correção para os números grandes do widget
  *O site em números*.
- **O Pulse desenha finalmente os seus widgets.** O tema previa o seu próprio aspeto para a caixa da data do
  calendário, os números de *O site em números*, a semana e as miniaturas da galeria: nenhum aparecia, porque a folha de
  estilo do widget é servida depois da do tema e ganhava em igualdade de especificidade. O mosaico do Pulse (cantos
  arredondados, algarismos grandes, cinzento-ardósia) aparece agora como a maqueta o mostrava.

## [1.2.49] — 2026-10-10

Um lote grande: um endereço com o título errado leva ao certo, a lista das páginas não encontradas, o editor de texto
na língua da página, o módulo Eventos utilizável sem jogos — para uma associação ou um clube —, as imagens nos tickets
que chegam do Discord, e uma longa lista de correções encontradas ao reler todo o registo de alterações, entre elas a
página de um jogo que já não mostrava o seu jogo. O bot do Discord passa para a **versão 0.2.7** (vê o seu próprio
registo de alterações).

### Adicionado

- **Imagens num ticket do Gestor de bugs**: uma captura de ecrã anexada no Discord a um ticket ou a uma ideia, ao
  abri-lo ou numa resposta, aparece agora no ticket do site — com o bot 0.2.7, que a entrega ao site como já faz para o
  fórum. O texto de um ticket continua a ser texto simples: só lá aparecem as imagens guardadas pelo site; o endereço
  de uma imagem alojada noutro lado continua a ser texto.
- **A lista das páginas não encontradas**, em *Monitorização → Diagnóstico*: cada endereço que o site não conseguiu
  encontrar — sem a língua nem os parâmetros —, quantos visitantes e robôs o pediram, quando pela última vez e a última
  página de onde vinham os visitantes. Um botão «Redirecionar» abre o formulário de redirecionamento com o endereço já
  preenchido; as sondagens de robôs à procura de uma falha são listadas à parte. Nenhum endereço IP, nenhum navegador;
  um endereço que ninguém pede há 90 dias é esquecido.
- **As imagens abandonadas do editor**, em *Monitorização → Diagnóstico*: uma imagem enviada para um texto e depois
  retirada, ou a imagem de um rascunho nunca guardado, ficava no disco. A página encontra-as — nenhum texto do site as
  mostra já, nenhum conteúdo, mensagem, definição, revisão nem elemento da reciclagem, e têm mais de 30 dias —,
  mostra-as e elimina as que marcares.
- **O editor de texto fala a língua da página**: os seus menus, botões e janelas estavam em inglês em todo o lado;
  estão agora em francês, alemão, espanhol, italiano ou português conforme a língua da página.
- **Um GIF animado continua animado** no editor: tornava-se uma imagem fixa. É reescrito mantendo só as suas imagens, o
  seu ritmo e o seu ciclo. Um GIF com mais de 2000 px de lado ou mais de 1000 imagens continua a tornar-se uma imagem
  fixa, como antes.
- **Eventos sem jogos**: o módulo Eventos já não precisa dos módulos Jogos e Equipas. Uma associação ou um clube
  instala-o sozinho — convites, respostas, recorrência, lembretes; os jogos, os seus adversários e os seus resultados
  chegam com os módulos Jogos e Equipas. Entra nos perfis de instalação «Comunidade» e «Associação / clube».

### Corrigido

- **Um endereço com o título errado redireciona para o certo** (redirecionamento permanente, 301): o título do conteúdo
  mudou, ou a ligação vem de outra língua ou tem uma gralha. Os artigos do blog, as categorias, os autores e as séries,
  o palmarés, os tickets do Gestor de bugs, os eventos do calendário, os classificados, as sondagens e as receitas
  respondiam a qualquer título — duplicados para os motores de pesquisa; as notícias, os eventos, as ofertas de
  recrutamento, as equipas, os álbuns e as imagens da galeria, os fóruns e os tópicos, os perfis, os grupos e os
  parceiros respondiam «não encontrado» no endereço antigo de um título alterado. A página de uma categoria do blog
  mostra o seu título, já não o texto do endereço; a página de um tipo de evento ou de uma equipa que não existe
  responde agora «não encontrado».
- **Uma imagem enviada no editor já não transborda** da página nas páginas que não a limitavam (o separador «Sobre» de
  um perfil, eventos, parceiros, álbuns, ofertas de recrutamento, equipas) nem num telemóvel: ocupa no máximo a largura
  da coluna.
- Um tamanho inferior a 1000 bytes lê-se «685 B», já não «685.00 B».
- **Forge**, em modo de dia: o texto atenuado (datas, autores, locais, «Registado em…») é um pouco mais escuro — ficava
  logo abaixo do limiar de legibilidade.
- **Forge e Extend**: as linhas finas que escolhem a imagem do diaporama são fáceis de tocar num telemóvel (só tinham
  3 px de altura); visualmente nada muda.
- **Chronique**: o número de notificações por ler, no ícone da conta, lê-se muito melhor, de dia e de noite.
- **Discord**: quando um tópico ou uma mensagem do fórum, um ticket ou um comentário de ticket é eliminado, o registo
  que o liga à sua thread do Discord é agora retirado automaticamente uma semana depois; ficava para sempre na base de
  dados.
- **Nada colado à margem**: a lista dos IP banidos e o seu formulário para adicionar (a frase de ajuda e o botão
  tocavam nas margens do seu cartão), o campo de pesquisa da lista de eventos, a paginação de uma tabela no Granite, a
  paginação e o botão «Seguir» no Nebula, um nome de utilizador longo na lista de membros. Num telemóvel, as tabelas do
  diaporama e das permissões deslocam-se dentro do seu cartão em vez de o transbordar.
- **Acabaram-se os botões colados**: «Convidar», «Sair»… numa conversa, num telemóvel; «Voltar» e «Publicar o tópico»
  no fórum; os dois botões da pesquisa do fórum na administração.
- **Forge**: num telemóvel, o menu da área de membros voltou — escorregava para baixo do cartão da página; num
  computador, fica à vista ao deslocar. O botão verde «Ver a minha candidatura» volta a ser legível.
- **A página de um jogo** volta a mostrar o seu jogo — a equipa, o adversário, o resultado, o jogo e as rondas, que já
  não mostrava. A equipa, o resultado e o adversário cabem numa linha, tal como cada ronda, num telemóvel como num
  computador.
- **Recrutamento**: a página «Minha candidatura» volta a mostrar o posto e a equipa certos — mostrava o nome do ícone em
  vez do posto. **Fórum**: editar um fórum, na administração, volta a mostrar a sua descrição e a sua categoria —
  mostrava o título em vez disso, e guardar podia mover o fórum para outro lado.
- **Moderação**: uma sanção é eliminada automaticamente três anos depois de terminar (expirada ou levantada; um aviso,
  três anos depois de ser dado); uma sanção sem fim (um banimento definitivo, por exemplo) fica enquanto durar. Eram
  guardadas sem limite.
- O botão de denúncia (a bandeirinha «Denunciar este conteúdo») é fácil de tocar num telemóvel; na lista *Modelos de
  email* da administração, as variáveis no assunto de um e-mail (`{{date}}`) destacam-se do texto.
- **Newsletter**: o destino «Grupo» e a lista de campanhas nomeiam cada grupo na língua da administração; tomavam-no
  numa língua ao acaso («Criador de addons», «Mitwirkender»).
- **Etiquetas certas**: no perfil de um membro, o botão «Contacter» mostrava «Contact» em francês; o tema Extend
  escreve «Em destaque» e «Descobrir» na língua da página (ficavam em francês); o botão «Grelha» da página do blog e
  dos addons já não é traduzido por «Schedule» em inglês, nem pelo equivalente em alemão, espanhol e italiano; o tipo
  de ticket francês «Demande de feature» passa a «Demande de fonctionnalité». Em inglês, o botão de início de sessão
  diz «Log in», o menu «Log out», e «View my profile» é usado em todo o lado. Na Monitorização, o cartão
  «Armazenamento» escrevia «Sauvegarde», «Libre» e «Total» em francês em todas as línguas.
- **Pulse, de noite**: o número de notificações por ler, no ícone da conta, é legível — um algarismo escuro sobre o
  tijolo claro; a branco, só chegava a 3,9:1.
- **O widget Doações** mostra a inicial de um doador cujo nome começa por uma letra acentuada; mostrava «&».
- **Repor a palavra-passe** recusa, como a inscrição e a alteração, uma palavra-passe idêntica ao nome de utilizador: a
  reposição não a comparava.
- O bot do Discord passa para a **versão 0.2.7** (0.2.6, depois 0.2.7): entrega ao site as imagens anexadas aos tickets
  (ver acima), e o seu registo já não anota as religações normais ao Discord, só uma falha de mais de um minuto (vê o
  seu próprio registo de alterações).

## [1.2.48] — 2026-10-09

Um lote grande: uma auditoria de segurança (acessos, injeções, contas, ficheiros), uma moderação que finalmente
funciona — as suas sanções aplicam-se agora a tudo o que os membros escrevem —, um novo endereço de e-mail confirmado
antes de contar, a eliminação das contas inativas, e o início de sessão com Discord, GitHub ou Google reparado. O bot do
Discord passa para a **versão 0.2.5** (vê o seu próprio registo de alterações). **Num site em HTTPS, toda a gente volta
a iniciar sessão uma vez depois da atualização**: o cookie de sessão muda de nome.

### Segurança

- **A pesquisa do fórum, aberta aos visitantes, permitia uma injeção SQL** através de uma palavra escrita entre aspas; a
  pesquisa das mensagens privadas tinha a mesma falha. Essa palavra é agora limpa como as outras, e escapada pela
  própria base de dados.
- **Um envio vindo de outro site é recusado** (POST, PUT, DELETE…), conforme o que o navegador diz (`Sec-Fetch-Site`,
  senão `Origin`): uma página armadilhada já não consegue pôr um membro com sessão iniciada a agir sem o saber. Um
  cliente sem navegador — o bot, um serviço de pagamento — não é afetado.
- **Vinte ações que mudavam algo com uma simples ligação** exigem agora o token de sessão, ou só aceitam POST: seguir um
  tópico, torná-lo um anúncio, marcar tudo como lido, sair de uma conversa, arquivá-la, eliminá-la ou restaurá-la,
  reagir, marcar as notificações como lidas, dar a sua disponibilidade para um evento, abrir ou fechar o site e as
  inscrições, lançar uma cópia de segurança ou uma atualização, eliminar um campo de recrutamento… Terminar a sessão
  por uma ligação sem token pede uma confirmação com um botão.
- **Em HTTPS, para um site instalado na raiz do seu domínio, o cookie de sessão tem o prefixo `__Host-`**: um
  subdomínio (uma demonstração, um webmail) já não o recebe e já não consegue impor um. Um site instalado numa subpasta
  mantém um cookie sem este prefixo, que só muda de nome.
- **Contas**:
  - a ligação «palavra-passe esquecida» funcionava também como ligação de confirmação da inscrição, que inicia sessão
    sem pedir nada durante dois dias: cada ligação só funciona agora para o seu próprio fim;
  - mudar a palavra-passe anula um novo endereço pendente e as ligações «palavra-passe esquecida»;
  - as falhas de início de sessão contam-se por conta — seja como for que esteja escrita — e por rede: um endereço IPv6
    conta pela sua rede /64, em todos os limites de tentativas do site;
  - ligar a autenticação de dois fatores ou ligar uma conta Discord, GitHub ou Google volta a pedir a palavra-passe;
    cinco erros bloqueiam esta confirmação durante um quarto de hora;
  - «palavra-passe esquecida» responde o mesmo quer um endereço esteja registado quer não, e a inscrição só diz que um
    endereço já está ocupado cinco vezes por hora e por rede;
  - o histórico de inícios de sessão regista o endereço da ligação, já não um cabeçalho que o navegador escolhe.
- **Doze fugas de acesso fechadas**: o histórico e as revisões de uma página da wiki em rascunho; as categorias VIP do
  fórum, através da pesquisa e dos perfis; os grupos ocultos; a imagem de um álbum fechado; os jogos de um tipo
  restrito; a presença que os participantes tinham escondido; os comentários e reações sobre conteúdo oculto; denunciar
  uma conversa de que não se faz parte.
- **Dois endereços antigos das mensagens privadas devolviam as mensagens de qualquer conversa**, mesmo privada, a
  qualquer pessoa com o direito de leitura — visitantes incluídos: retirados. Publicar uma imagem na galeria só exigia o
  direito de ver o álbum.
- **Os anexos do fórum e das conversas são servidos pelo site**, a quem pode ler o tópico ou a conversa, já não pelo
  servidor web a quem tivesse o seu endereço. Uma imagem ou um PDF é mostrado, tudo o resto é transferido; um anexo que
  um navegador executaria é recusado no envio. No Apache, o `upload/.htaccess` incluído trata disso; no nginx ou no
  Caddy, copia para a configuração do teu servidor a regra que bloqueia `upload/forum/` e `upload/talks/` (vê os
  `nginx.conf` e `Caddyfile` incluídos).
- **O que o servidor vai buscar ele próprio** (retransmissão de imagens, feeds RSS, webhooks) fica limitado a endereços
  estritamente públicos, nas portas 80 e 443.
- **As chaves do Stripe já não se leem em claro** na administração: guardadas cifradas, nunca mais mostradas.
- **Demonstração**: os endereços IP dos visitantes, os nomes de anfitrião e os sites de origem já não são lá
  mostrados; um anúncio, uma ligação, um parceiro que leva para fora do site é mostrado numa página que diz para onde
  leva, em vez de redirecionar para lá.
- **Uma ligação `javascript:` já não pode aparecer** no lugar de um endereço: a página de onde vinha uma sessão
  (administração), as ligações de um jogo (site do adversário, stream, artigo). A ligação que uma denúncia dá para o
  conteúdo denunciado só pode levar a uma página do site.

### Adicionado

- **Um novo endereço de e-mail espera pela sua confirmação**: a ligação vai para o novo endereço (válida dois dias), o
  antigo é avisado, e «A minha conta» propõe reenviar a ligação ou cancelar. Até agora, uma gralha deixava o membro sem
  nada do que o site envia.
- **As contas inativas são eliminadas**: sem visitas há três anos — ajustável em *Definições*, `0` para nunca —, um
  e-mail avisa o membro um mês antes, uma visita anula tudo. Nunca um administrador.
- **Sancionar um membro sem esperar por uma denúncia**, a partir do seu histórico de moderação, para o qual a sua página
  de administração remete.
- **O botão de denúncia** (a bandeirinha «Denunciar este conteúdo») no livro de visitas, nas imagens da galeria, nos
  classificados, nos tickets e nos seus comentários.
- **«As minhas sanções»** na área de membros, para quem teve uma no ano: o seu tipo, o seu motivo, o seu período.
- **A mediação**: a partir de uma denúncia, o moderador propõe uma conversa privada entre ele, o membro denunciado e quem
  denunciou. O membro denunciado verá lá quem o denunciou, por isso só se abre com o acordo de quem denunciou, dado numa
  página que explica o que aceita; se recusar, a sua denúncia continua anónima e segue como habitualmente.

### Corrigido

- **As sanções de moderação aplicam-se a tudo o que os membros escrevem**: fórum, mensagens privadas, comentários, livro
  de visitas, Gestor de bugs, recrutamento, classificados, galeria, perfil, imagens enviadas no editor de texto. Só se
  verificava o silêncio do fórum e das mensagens, e depois do envio. O formulário dá lugar a um aviso que diz o que a
  sanção proíbe, até quando e porquê; a sanção «Restrição: ligações» recusa um texto que tenha uma ligação, sem o
  perder.
- **Não se podia aplicar nenhuma sanção a partir do ecrã de moderação**: um erro interno impedia-o, por isso a escalada
  automática nunca disparava. Revistos ao mesmo tempo: as definições (caixas que não se podiam desmarcar, limiar de
  denunciante suspeito), os âmbitos, as durações (um banimento temporário sem duração tornava-se definitivo), a
  validação por um moderador de posição superior, o painel dos moderadores, as denúncias duplicadas, as páginas a
  partir da 2, a lista de IP. Um moderador já não pode levantar uma sanção que o visa, nem a de um membro de posição
  igual ou superior, nem uma aplicada por um moderador de posição superior.
- **O shadow ban funciona**: o que escreve um membro com shadow ban só é mostrado a ele e aos moderadores, e nada é
  difundido — nenhuma notificação, nada de Discord, nenhum subscritor.
- **Um cargo adicional — Moderador — retirava direitos a um membro**: um moderador perdia o fórum e as mensagens
  privadas. Um cargo acrescenta direitos, já não retira nenhum.
- **Os e-mails são enviados na língua de quem os recebe** — sanções, respostas no fórum, mensagens privadas —, já não
  na do membro que os provocou.
- **O início de sessão com Discord, GitHub ou Google falhava** assim que um serviço estava configurado (a biblioteca
  OAuth tinha mudado), e enviava ao serviço um endereço de regresso que ele recusava. Um regresso recusado diz agora
  porquê em vez de uma página de erro, e o avatar do Discord volta a ser importado.
- **Livro de visitas, administração**: aprovar, rejeitar ou eliminar uma mensagem levava a um erro «página não
  encontrada».
- **O erro de um campo de formulário é escrito por baixo do campo**, num telemóvel como num computador: só se podia ler
  numa dica ao passar o rato.
- **A barra superior da administração fica no topo** ao deslocar; num telemóvel, só o menu e o trilho de navegação
  ficam fixos.
- **Trinta e quatro erros de tradução** em doze ficheiros de língua («There are 1 user», confirmações de eliminação que
  mostravam `< br / >`, plurais ingleses…).
- O endereço do sítio web declarado por 48 addons do núcleo é o do projeto, já não o do NeoFrag original. A página
  *Modelos de email* da administração já não lista os modelos de um módulo não instalado. Um erro em alguns
  formulários (limite do livro de visitas atingido, eliminação da conta, autenticação de dois fatores, palavra-passe,
  criação de um membro na administração) já não leva a um erro «página não encontrada».
- **Demonstração**: um membro comum não podia abrir um tópico, responder em lado nenhum nem candidatar-se a nada.
- **A exportação «Os meus dados» inclui as denúncias que o membro fez**: faltavam no arquivo.

### Alterado

- **Um widget ligado a um módulo depende agora dele** (21 widgets): sem esse módulo, ou se estiver desativado, o widget
  já não é mostrado e o Editor ao vivo já não o oferece; «Adicionar» recusa um arquivo de widget sem o seu módulo.
- **A documentação do produto já não é incluída** na wiki de um site novo, que chega vazia; vive no site do projeto.
  Uma instalação nova já não recebe as definições de temas que não tem.

### Removido

- Os antigos endereços `ajax/talks` das mensagens privadas, e a sua antiga denúncia de mensagens, que só escrevia no
  registo de auditoria, com a sua página de administração «Denúncias de mensagens»: uma mensagem denuncia-se agora
  através da moderação.

## [1.2.47] — 2026-10-09

A barra de objetos do Blockcraft, nos telemóveis.

### Corrigido

- **A barra de objetos do Blockcraft ocupa toda a largura do telemóvel**: as suas casas dividem o ecrã por igual e
  cada nome cabe na sua — em duas linhas para «O meu espaço», com hífen para uma palavra alemã ou portuguesa demasiado
  longa («Novi-dades»). Mantinha a mesma largura fosse qual fosse o ecrã (sete casas de 47 px): um espaço vazio à
  direita, casas estreitas e «Notícias» a sair da sua casa. A moldura branca da casa escolhida já não toca no seu nome.

## [1.2.46] — 2026-10-08

As barras do topo da página já não tapam nada, o sino chega ao Nebula, e a página *Temas & Addons* volta a conseguir
reinstalar um tema.

### Corrigido

- **O que fica colado ao topo do ecrã coloca-se agora por baixo das barras do topo da página** — a barra da
  demonstração, a da manutenção e a da pré-visualização das permissões. Assim que se deslocava a página, metade dos
  cabeçalhos do Extend, do Chronique, do Pulse e do Nebula deslizava para baixo da barra da demonstração; o fundo da
  barra lateral do Forge e da barra lateral da administração saía do ecrã; nos telemóveis, o brasão e a conta do Forge
  ficavam escondidos. O modelo principal empilha agora as barras e publica a sua altura; os temas, a administração e o
  Editor ao vivo alinham-se por baixo delas, e as notificações no ecrã aparecem por baixo.
- **As colunas fixas dos módulos alinham-se por baixo do cabeçalho do tema**, seja ele qual for: o menu da área de
  membros (a 16 px do topo, deslizava para baixo de um cabeçalho fixo de 60 a 70 px), o índice da wiki, o índice e a
  caixa lateral de um artigo. Uma coluna mais alta do que o espaço que lhe resta já não fica colada: o seu fundo ficava
  fora do ecrã até ao fim da página — o menu da área de membros do Extend, no ecrã de um portátil.
- **Uma ligação para uma âncora para por baixo do cabeçalho fixo** — uma mensagem do fórum, uma secção da wiki ou de um
  artigo — em vez de deixar o seu título por baixo dele: cada tema definia este desvio à mão, sem contar as barras.
- **«Reinstalar predefinição» e «Eliminar» um tema funcionam a partir da página «Temas & Addons»**, e a ordem das
  línguas e dos autenticadores é guardada: a confirmação era recusada, «token de segurança inválido».
- **A barra da demonstração é legível**: o seu texto, branco sobre verde (contraste de 2,4:1), passa a escuro.
- **Nos telemóveis, os botões de um cabeçalho da administração passam para baixo do título**: na página dos artigos,
  «Categorias», «Séries» e «Novo artigo» tapavam «4 publicados», e o último saía do ecrã.
- **Um site aberto com outro nome que não o da sua configuração** (com ou sem `www.`, por exemplo) mostra as imagens
  escritas com o endereço configurado: a política de segurança do navegador bloqueava-as, porque a seus olhos vinham de
  outro site; passam agora pela retransmissão de imagens do site.
- **Para os contribuidores**: a ferramenta `check-mise-en-page` já não assinala falsas sobreposições dentro de um bloco
  fechado (`<details>`), que o Chrome mantém na página sem o mostrar — 48 falsos alertas na página *Registo de erros*
  da Monitorização —, nem nas mensagens de erro que essa página mostra de propósito.

### Adicionado

- **O sino das notificações no Nebula**, como nos outros temas: a área de membros anuncia-o «no topo de cada página», e
  o Nebula não tinha.
- **Para os autores de temas**: o cabeçalho fixo de um tema tem `data-nf-entete`, e o que fica colado ao topo do ecrã
  alinha-se por `--nf-haut` (e uma coluna por `--nf-entete`); uma nova verificação, `check-colles`, controla-o —
  passada sobre a 1.2.45, encontrou vinte regras CSS defeituosas (*Criar um tema*).

### Removido

- **Regras de estilo sem uso na folha de estilo do Nebula** (uma barra, botões e um rodapé herdados de outro tema):
  nenhum modelo do Nebula as usava.

## [1.2.45] — 2026-10-08

O sino das notificações, revisto em todos os temas que o mostram, e o início de sessão de dois fatores de uma só vez.

### Corrigido

- **Com a autenticação de dois fatores, o código é pedido logo a seguir à palavra-passe**: a janela de início de sessão
  dá lugar à do código, sem recarregar a página. Fechava-se, e era preciso voltar a clicar em «Iniciar sessão» para ver
  o código; a partir do formulário da área de membros, a janela do código abre-se ao recarregar.
- **O distintivo do sino fica no seu canto** em todos os temas que mostram o sino: a folha de estilo do módulo,
  carregada depois da do tema, devolvia-o ao fluxo, e no Extend e no Forge caía a meio do botão, em cima do sino.
- **O sino fica no centro do seu botão** quando tem um distintivo: um ícone que já não estava sozinho na sua ligação
  ganhava uma margem direita e deslizava três píxeis.
- **«Notificações» e «Marcar tudo como lido» já não se tocam** no topo da lista: mantém a sua largura quando o tema
  estreita os seus menus, e os dois textos mantêm um espaço, mudando de linha se for preciso.
- **As datas são mostradas no formato da língua e no fuso horário do visitante** na lista do sino, no histórico de
  revisões e na dica de uma denúncia, que mostravam o carimbo temporal bruto da base de dados.

## [1.2.44] — 2026-10-08

Uma correção da 1.2.43.

### Corrigido

- **O filtro de terceiros funciona com PHP-FPM e Apache.** Corre no fim do pedido, quando a pasta atual já não é a do
  site: já não encontrava a chave da retransmissão de imagens, e a página saía sem filtro. As imagens de outros sites,
  que a política de segurança agora recusa, não apareciam (os avatares do widget do Discord), e um vídeo incorporado
  ter-se-ia carregado sem o seu aviso. O defeito não aparecia com o servidor integrado do PHP, que não muda de pasta no
  fim do pedido.
- **O registo de erros do PHP guarda também os erros do fim do pedido**, erros fatais incluídos: o seu caminho era
  relativo, e era então resolvido a partir da raiz do sistema de ficheiros (`/`).

## [1.2.43] — 2026-10-08

Privacidade: nenhum visitante é já enviado a um terceiro sem o seu consentimento.

### Adicionado

- **Uma verdadeira escolha sobre os serviços de outras empresas.** O banner de cookies só controlava o Google
  Analytics, enquanto os vídeos, o widget do Discord e os captchas se carregavam respondesse o visitante o que
  respondesse. A janela **«Gerir os meus cookies»**, aberta a partir do rodapé de cada página, mostra os cookies
  próprios do site e depois cada serviço, para aceitar ou recusar um a um. No lugar de um vídeo do YouTube, Twitch,
  Vimeo ou Dailymotion, de uma faixa do Spotify ou do SoundCloud, ou do widget do Discord, um aviso diz quem receberia o
  endereço IP do visitante, com «Mostrar» e «Permitir sempre…». O banner só se abre se o site tiver mesmo algo a
  perguntar à partida (medição de audiência, um captcha gerido por um terceiro); «Aceitar tudo» e «Recusar tudo» têm o
  mesmo peso. A escolha é guardada seis meses, e o seu registo, sem o endereço IP, treze meses.
- **Definições → Privacidade**: as páginas de aviso legal e de política de privacidade, escolhidas entre as páginas
  publicadas. O rodapé de cada tema remete para elas, ao lado de «Gerir os meus cookies».

### Alterado

- **Os tipos de letra são servidos pelo site**: os temas e a definição «Tipo de letra do site» iam buscá-los ao Google
  Fonts, e o navegador de cada visitante enviava o seu endereço IP à Google, em cada página.
- **As imagens de outros sites são servidas pelo site** — um avatar do Discord, do Steam ou do Twitch, um GIF numa
  conversa, uma imagem colada num artigo, no fórum ou num classificado, um banner publicitário: o site vai buscá-las ele
  próprio e guarda-as sete dias, e o navegador já não contacta o seu servidor. Os mosaicos do mapa de locais também.
- **A política de segurança só aceita agora o próprio site** para as imagens e as ligações de scripts (exceto o Google
  Analytics, se estiver configurado). Deixa agora aparecer os leitores de serviços conhecidos (YouTube, Twitch, Vimeo,
  Dailymotion, Spotify, SoundCloud, Discord), que antes bloqueava: um vídeo num artigo não aparecia. Um script de outro
  site colado num widget «Código HTML» ou num anúncio já não corre: só passam o Google Analytics e o captcha escolhido.
- **Enquanto o visitante não aceitar um captcha de um terceiro, o ALTCHA substitui-o**, servido pelo site: ninguém tem de
  entregar os seus dados à Google, à hCaptcha ou à Cloudflare para escrever no site ou inscrever-se.
- **Acabou-se o píxel de seguimento na newsletter**, e a taxa de abertura: um rastreador que a subscrição não tinha
  pedido (recomendação da CNIL de 12 de março de 2026 sobre os píxeis de seguimento nos e-mails).
- **Prazos de conservação**: o registo de auditoria um ano, os contadores anti-abuso um dia, as subscrições da
  newsletter nunca confirmadas trinta dias, a fila de envio da newsletter noventa dias; para uma denúncia tratada há
  mais de um ano, são apagados o endereço IP do seu autor e a cópia do conteúdo. O cookie de sessão termina quando o
  navegador fecha, exceto com «Lembrar-me» (um ano), que já não vem marcado por predefinição.
- **Acabaram-se os mapas da Google nos textos**: um mapa do Google Maps incorporado num texto já não é aceite. O mapa do
  produto é o módulo «Mapa de locais», baseado no OpenStreetMap.
- A ligação «Desenvolvido por NeoFrag Reborn» dos temas, a palavra-chave de copyright `{neofrag}` e as ligações do
  rodapé da administração levam ao site do projeto; levavam ao site do NeoFrag original.

### Corrigido

- **Nenhum endereço IP é já enviado para neofr.ag**: a bandeira do país ao lado dos endereços das sessões foi
  retirada; para a obter, o navegador do administrador enviava todos esses endereços ao site do NeoFrag original.
- **Eliminar uma conta como administrador apaga mesmo** o perfil, as contas associadas, o histórico de inícios de sessão
  e as notificações, como a eliminação que um membro pede ele próprio; antes só fechava a conta.
- **O arquivo «Os meus dados» transfere-se agora**: três das suas listas (tópicos do fórum, comentários, mensagens das
  conversas) liam colunas que não existem, e o arquivo falhava.
- As dicas da lista de sessões e da galeria aparecem agora: tinham um atributo do Bootstrap 4, que o Bootstrap 5
  ignora, e ficavam vazias.
- O endereço do avatar do widget do Steam era escrito tal como estava na página; agora é escapado, como o resto do
  widget.
- A dica «Abrir o canal» e o botão «Fechar» do leitor do widget do Twitch, que tinham ficado em francês, estão
  traduzidos.

## [1.2.42] — 2026-10-07

Um tema redesenhado: **Extend**, o site como lançador de um jogo online.

### Adicionado

- **Extend 2.0.0 «Launcher»** — uma paginação totalmente nova, escolhida entre três maquetas. Uma barra de separadores
  no topo, em maiúsculas apertadas, o separador aberto iluminado por uma linha azul; na página inicial, uma **grande
  montra** (o diaporama, o seu título em letras grandes e o seu botão «Descobrir»; sem diaporama, o nome do site sobre a
  imagem do tema), depois os próximos eventos em cartões, as notícias em miniaturas, os últimos resultados; noutras
  páginas, o título da página sobre essa imagem. À direita, em cada página, um **painel sempre aberto**: quem está
  online, com avatares, e depois o canal de conversa (e, nas páginas do fórum, as suas estatísticas). Em baixo, uma
  **barra de estado**: o lugar dos widgets «Servidor de jogo», «Servidor TeamSpeak 3» e «Servidor Discord», a língua,
  depois «Desenvolvido por NeoFrag Reborn», o copyright e as ligações legais. O fórum como biblioteca, a área de membros
  como ficha de jogador (banner, avatar com aro, nível de gamificação na barra). Nos telemóveis, os separadores passam
  para o fundo do ecrã, com «Online», que abre o painel como uma gaveta, e «Mais» a partir de quatro secções. Azul-aço
  sobre azul-marinho, noite por predefinição, dia a pedido; títulos em Saira Condensed, texto em Albert Sans. O
  logótipo, a imagem da montra, o fundo e as cores são ajustáveis. Baseado no tema de Chewbaka, de quem mantém o nome e
  a licença.
- **«Quem está online? (lista)»**, uma nova apresentação do widget Membros: as três contagens (administradores,
  membros, visitantes) e depois as próprias pessoas com o seu avatar e a sua última atividade.
- **«Um canal público: as suas últimas mensagens»**, uma nova apresentação do widget Conversas: as quatro últimas
  mensagens de um canal aberto a todos os membros, e a ligação para escrever nele. Um visitante não lê as mensagens: é
  convidado a iniciar sessão, como nas próprias mensagens; o canal da equipa nunca é mostrado.

### Corrigido

- **As publicações relacionadas de um artigo do blog já não transbordam nos telemóveis**: a caixa de uma publicação
  sem imagem tirava a sua largura da altura da sua linha e saía do ecrã alguns píxeis (a 414 px, em todos os temas).
- **Blockcraft, revisto na demonstração publicada**: num telemóvel, o banner da página mostra toda a paisagem
  (recortado, só mostrava troncos de árvores), sem o sol nem a lua, por cima dos quais passava um título longo; a
  demonstração define um endereço de servidor de exemplo (`play.example.org`), para que se veja o botão «Copiar o
  endereço».

## [1.2.41] — 2026-10-07

Um tema redesenhado: **Blockcraft**, o site de um servidor de um jogo de blocos.

### Adicionado

- **Blockcraft 2.0.0 «Spawn + Inventário»** — uma nova paginação, escolhida entre quatro maquetas. No topo, um céu sobre
  uma paisagem de blocos (colinas, árvores, pedra), o sol e nuvens a passar, a lua e as estrelas à noite; na página
  inicial, o nome do servidor em letras grandes e **o seu endereço, copiado com um clique** (definição «Endereço do
  servidor»), e o Discord quando está definido. A navegação é uma **barra de objetos**: cada secção na sua casa, com o
  objeto que a representa (o livro para as notícias, o mapa para o fórum…); num telemóvel, fica colada ao fundo do ecrã,
  ao alcance do polegar. Blocos de cantos retos com sombras duras, o **fórum como baús** (uma tampa de tábuas por
  categoria, cada fórum na sua casa, uma casa encantada que brilha quando há novidades), a **área de membros como ecrã
  da personagem**, um rodapé de pedra. Títulos em Jersey 10, texto em Rubik, algarismos em VT323; «prado» de dia, «céu
  estrelado» de noite. Cada píxel é desenhado para o tema. De dia, os textos do céu são escuros: a branco, só seriam
  legíveis graças à sua sombra.

### Corrigido

- **A coluna lateral do Pulse, do Chronique e do Blockcraft já não deforma os widgets colocados nela**: a sua
  paginação chegava também ao interior dos widgets, e «Quem está online?» afastava lá os seus números das suas
  etiquetas.
- **O separador escolhido do perfil de um membro é legível no Pulse, no Chronique e no Granite**: estes temas mudavam o
  seu texto sem mudar o seu fundo, que o módulo pinta com a cor de destaque — texto escuro sobre o destaque.
- **O ícone de um grupo já não toca no seu nome** (por exemplo «Equipa principal», no perfil e na área de membros): a
  base comum dos temas não deixava espaço entre o ícone e o nome — nos seis temas que a carregam.
- **Os comentários de um evento mantêm o seu ícone** no Pulse, no Chronique e no Granite: estes temas retiravam o ícone
  de todas as ligações do rodapé de um cartão, e a ligação só dizia «0 →». Agora só os rodapés dos widgets («Ver o
  calendário →») perdem o seu ícone.
- **A pastilha escolhida do módulo de mensagens é legível** («Todos», «Arquivos»…) no Pulse e no Chronique: a base
  comum dava ao seu texto a cor das ligações e ao seu ícone a cor de destaque, sobre um fundo da cor de destaque
  (1,1:1).
- **Granite**: os botões no rodapé de um cartão («Postar uma imagem», «Ver a minha candidatura», a paginação das
  sessões) tomavam o vermelho das ligações sobre a tinta (2:1); «Levantar esta sanção» só tinha 3,7:1; num telemóvel, a
  capitular da primeira página deslizava sobre a linha «Por … em …» por baixo; os botões pequenos têm 24 px de altura.
- **Pulse, de noite**: o contador do menu da área de membros é legível: branco sobre o tijolo noturno mais claro, só
  tinha 3,9:1; mantém agora o tijolo do dia.
- **O menu da área de membros, num telemóvel, encaixa no início de um separador**: centrado ao píxel, deixava o
  separador anterior cortado no início do seu nome («…ficações»).
- **Ligar o «Modo de depuração» a partir da Monitorização já não faz falhar a sua página**: a resposta falhava assim que
  uma notificação estava à espera («Cannot use object of type stdClass as array»).
- **Os guias contam os temas do pacote**: «Conceitos» anunciava quatro (faltavam o Chronique e o Pulse), e «Criar um
  tema» nomeava quatro temas sobre a base comum em vez de seis.

### Alterado

- **`check-mise-en-page` já não dá falsos alarmes**: o texto do banner de cookies ou de uma barra colada ao fundo do ecrã
  passa de propósito por cima de um gráfico (só a mesma camada cria um conflito); a parte oculta de um texto — o código
  de um bloco com deslocação, uma descrição cortada — já não «se sobrepõe» à coluna seguinte; um separador numa faixa
  com deslocação já não está «perdido para lá da margem esquerda». Menos defeitos falsos, e os verdadeiros continuam a
  aparecer (verificado numa página de teste armadilhada, com a sonda antiga e a nova).

## [1.2.40] — 2026-10-07

A indexação pelos motores de pesquisa, revista depois dos alertas da Search Console no site oficial: a Google não
indexava os tópicos do fórum nem as páginas da wiki, e eram-lhe oferecidas páginas vazias. E a mensagem de boas-vindas,
que já não era enviada.

### Corrigido

- **O conteúdo sem língua já não se apresenta como duplicado**: um tópico do fórum, uma página da wiki, um ticket, um
  evento, um classificado, uma receita, uma sondagem, uma oferta de emprego, um prémio, uma campanha de doações —
  escritos uma só vez — respondiam sob cada língua do site, só com os menus traduzidos, e cada endereço se declarava
  canónico. A Google escolhia outro e não indexava nenhum («Duplicate, Google chose different canonical than user»: no
  site oficial, em seis línguas, 59 conteúdos listados seis vezes, 354 dos 580 endereços do seu mapa do site). O seu
  canónico está agora na primeira língua do site, a única anunciada em `hreflang` e no mapa do site. O mesmo para as
  páginas feitas desse conteúdo: a FAQ, o glossário, as citações, as ligações, as transferências, a loja e a lista de
  eventos.
- **As páginas vazias já não são oferecidas aos motores de pesquisa**: a rádio web sem emissão nem programa, o livro de
  visitas sem mensagens e a página de doações sem campanha estavam no mapa do site, em todas as línguas — «soft 404»
  para a Google. Ficam de fora enquanto estiverem vazias, e declaram `noindex`.
- **`/fr/forum/` redireciona agora de forma permanente para `/fr/forum`**: o redirecionamento de um endereço terminado
  por uma barra, ou por uma barra dupla, era temporário (302) — e o primeiro perdia tudo o que vinha depois do `?`; é
  agora permanente (301) e mantém o fim do endereço.
- **A mensagem de boas-vindas volta a ser enviada**: o módulo Membros não encontrava o módulo de mensagens, e um membro
  novo não recebia nada. Um módulo que carregava outro (`$this->module(…)`) obtinha sempre «nada» — o mesmo defeito
  privava os webhooks dos comentários do título e do endereço do conteúdo comentado.
- **As cópias de segurança das atualizações já não se acumulam**: o site guarda sempre as cinco mais recentes e retira
  as outras ao fim de trinta dias, mas atualizações muito próximas deixavam-nas passar todas — o site oficial tinha
  quarenta, 663 MB. Agora nunca ficam mais de dez, seja qual for a sua idade.

### Alterado

- **`check-seo` compara as línguas entre si**: tira o mesmo caminho em cada mapa do site e assinala o mesmo texto
  servido em várias línguas, cada uma canónica. Declarava o site oficial saudável; agora encontra lá estes duplicados.
  Um módulo pode declarar o seu conteúdo sem língua: uma entrada do mapa do site marcada `'sans_langue' => TRUE` só é
  listada no mapa da primeira língua, e `nf_seo_sans_langue()` define o canónico da página (guia «Criar um módulo»).

## [1.2.39] — 2026-10-06

Pulse, revisto na demonstração publicada: o fórum num telemóvel já não cola os seus botões, em todos os temas; e uma
publicação do blog que ainda saía da margem do ecrã nos telemóveis.

### Corrigido

- **O fórum num telemóvel já não cola os seus botões**: a barra de um tópico (Voltar, Responder e as ferramentas de
  moderação) e a barra de um fórum deixam um espaço entre os seus botões e entre as suas linhas; os botões de uma
  mensagem passam para cima da sua data, que já não se parte em três linhas; o título de um tópico já não toca no botão
  «Seguir». Em todos os temas.
- **Uma publicação do blog já não transborda num telemóvel**: um pedaço longo de código (um caminho de ficheiro, por
  exemplo) parte-se, e uma tabela que continue demasiado larga desloca-se dentro da sua coluna (a publicação da 1.2.0
  saía da margem do ecrã).

## [1.2.38] — 2026-10-06

Um tema novo: **Pulse**, a casa comum das associações, clubes e comunidades, com três novidades que servem todos os
temas — o site em números, o próximo encontro e as últimas fotos.

### Adicionado

- **O tema Pulse.** Uma barra clara que fica no topo, com as secções como pastilhas, a conta e o apelo «Aderir» sempre à
  vista (um botão «Menu» num telemóvel); a página inicial é um mosaico de mosaicos — o nome e o lema do site sobre a
  imagem inicial, o próximo encontro, o site em números, as notícias, a agenda, a sondagem, as conversas, os documentos,
  as fotos, os parceiros — que o Editor ao vivo reorganiza mosaico a mosaico (claro, sol ou ardósia). O fórum lê-se como
  cartões, com as suas estatísticas e a sua atividade ao lado; o rodapé escuro tem o mapa do site e as redes sociais.
  Claro de dia, «ardósia» de noite à escolha do visitante; títulos em Bricolage Grotesque, texto em Manrope.
  Definições: cores, imagem inicial, logótipo, e o endereço do botão «Aderir» (uma página de adesão, um formulário noutra
  plataforma, ou a inscrição do site).
- **O widget «O site em números»**: até quatro números, escolhidos entre os membros, as conversas, as mensagens do fórum,
  as notícias, os próximos eventos e as fotos — contando só o que o visitante tem o direito de ver (os fóruns restritos e
  os álbuns de grupo ficam fora da contagem). No Pulse os números sobem quando o mosaico aparece, a não ser que o
  visitante tenha pedido menos movimento.
- **O calendário mostra também «O próximo encontro»**: a sua data em letra grande, o seu título, daqui a quantos dias, a
  que horas (ou «Todo o dia») e onde, o início da sua descrição, e um botão para o abrir.
- **A galeria mostra também «As últimas fotos»**, em grelha — só dos álbuns que o visitante pode ver.

### Corrigido

- **O estilo «Título colorido» do Chronique e do Granite**: o título do painel tinha a cor do seu fundo (verde-azulado
  sobre verde-azulado, vermelho sobre vermelho), por isso era invisível. Agora tem cor, sobre o papel, como estes temas
  pretendiam — e a pré-visualização do estilo no Editor ao vivo mostra-o assim.
- **O estilo «Widget colorido» do Granite**: o texto, as etiquetas dos formulários e o botão de um widget com este
  estilo podiam ficar brancos sobre o papel (ou tinta sobre tinta, de noite). Mantêm as cores do jornal. A verificação
  de contraste pode agora medir um widget em cada tema e cada estilo de painel.
- **O trilho de navegação por cima de uma notícia**: todos os temas o previam, nunca aparecia — a regra que o colocava
  visava um endereço que não existe. Aparece agora nas notícias e na lista de notícias, em todos os temas.
- **Duas caixas lado a lado ficam alinhadas**: por baixo de uma notícia, «Outras notícias deste autor» ficava 14 a 22 px
  mais abaixo do que «Sobre o autor» (o mesmo na página de um evento e na caixa de entrada).
- **Chronique: um artigo mantém as suas margens** dentro do seu cartão; o título e o texto tocavam na margem.
- **O texto sobre um degradê é legível**: o botão «Entrar no servidor» do widget do Discord, as iniciais do brasão do
  Forge, o título e o lema do banner do Blockcraft de dia. A verificação de contraste mede agora o texto sobre um
  degradê, e como membro com sessão iniciada (área de membros incluída).
- **Uma publicação do Blog com um só título já não aparece espremida**: sem índice, a página mantinha a sua coluna, o
  texto apertava-se em 200 px e a caixa lateral (autor, partilha) ocupava o seu lugar — as notas de versão da 1.2.32, da
  1.2.33 e da 1.2.37 no site oficial. Obrigado ao Blober por o ter assinalado.
- **`admin.php` e `ajax.php` respondem «página não encontrada»** em vez de um erro do servidor (e duas linhas de registo
  de cada vez que um robô sonda estes endereços).
- **A cronologia da temporada**: «Em 5 dias» já não se parte em duas linhas num telemóvel.

## [1.2.37] — 2026-10-06

Chronique, revisto na demonstração tal como é visto — como visitante e com sessão iniciada, de dia, de noite, num
telemóvel —, e uma demonstração que vive no presente.

### Corrigido

- **A área de membros do Chronique.** No seu painel, «Esqueceu-se da sua palavra-passe?» ficava por cima de «Iniciar
  sessão» assim que a coluna os punha um por baixo do outro; com a sessão iniciada, os ícones do menu ficavam
  cinzento-escuro sobre a ardósia e «Sair» caía num retângulo branco (preto de noite). São agora ligações brancas, um único botão
  preenchido e um espaço nos dois sentidos — também com «Criar uma conta», quando as inscrições estão abertas.
- **O Chronique num telemóvel mantém o início de sessão no cabeçalho**, como ícone: a palavra «Iniciar sessão»
  desaparecia, e era preciso deslocar até ao fim da página para iniciar sessão.
- **Os botões do painel de início de sessão mantêm o seu espaço quando mudam de linha**, nos cinco temas que partilham a
  base comum (Blockcraft, Chronique, Extend, Forge, Granite).
- **Extend: os ícones dos campos do painel colorido aparecem** (nome de utilizador e palavra-passe): eram brancos sobre
  uma caixa clara.
- **A lista de membros, de noite**: uma rede social que um membro não tinha preenchido tornava-se um ponto cinzento
  colado ao seguinte. É um ícone esbatido sem fundo, e os botões das redes preenchidas têm um verdadeiro espaço — numa
  linha num cartão estreito.
- **A demonstração vive no presente.** Reposta a cada quarto de hora, continuava a mostrar o conteúdo do dia em que o
  seu instantâneo foi tirado: eventos e jogos «próximos» que tinham passado há semanas, um calendário vazio, uma
  cronologia da temporada que se teria esvaziado. Cada reposição avança agora as datas do conteúdo o tempo decorrido (os
  intervalos entre elas não mudam); os membros mantêm as suas.

## [1.2.36] — 2026-10-06

Um tema novo: **Chronique**, o caderno da temporada para associações e clubes, com duas funcionalidades novas que servem
todos os temas — a cronologia da temporada e a semana do calendário.

### Adicionado

- **O tema Chronique.** O site como caderno da temporada: um cabeçalho discreto que fica no topo, com uma linha fina que
  segue a tua leitura, um botão «Sumário» que abre todo o site em ecrã inteiro (cada secção numerada) e o apelo «Aderir»
  para os visitantes; a página inicial abre-se com uma grande manchete — a temporada, o nome do site, o seu slogan — e a
  semana em curso; depois a cronologia conta a temporada mês a mês, ao lado de uma coluna que fica no lugar (a área de
  membros, os documentos, os parceiros). O fórum lê-se como capítulos numerados, a área de membros como um caderno, e o
  rodapé fecha o livro. «Papel» de dia, «luz de candeeiro» de noite; títulos em Fraunces, texto em Work Sans, datas em
  IBM Plex Mono. Definições: cores, imagem de abertura, logótipo, e o endereço do botão «Aderir» (uma página de adesão,
  um formulário noutra plataforma, ou a inscrição do site).
- **O widget «Cronologia da temporada»**: o que o site vive, mês a mês, numa linha do tempo — os próximos eventos do
  calendário (o seu ponto pulsa), as notícias, as conversas do fórum e os álbuns de fotos, cada um se o seu módulo
  estiver instalado, e só o que o visitante pode ler (os fóruns restritos e os álbuns de grupo ficam escondidos). O
  número de entradas e os meses passados são ajustáveis.
- **O calendário mostra também «Esta semana»**: os sete dias da semana em curso, os que têm um evento marcados, hoje
  rodeado, e o próximo encontro — no fuso horário de quem olha.

### Corrigido

- **Em modo de dia, os componentes do Bootstrap tomam as cores do tema**: um acordeão da FAQ, uma caixa de verificação,
  uma tabela ou um menu pendente ficavam brancos, com texto cinzento-escuro, sobre o papel do Granite. Verificado
  elemento a elemento no Forge, no Granite, no Blockcraft e no Extend: só as suas cores mudam, e o modo de noite fica
  como estava.
- **«Modo dia» e «Modo noite» estão traduzidos**: a dica e o nome, lido pelos leitores de ecrã, do botão que os alterna
  ficavam em francês num site noutra língua (Forge, Granite, Blockcraft, Extend). A verificação dos textos escritos no
  código conhece agora as palavras «jour» e «nuit».
- **A página de personalização de um tema já não escreve um aviso no registo** quando as suas definições ainda não
  existem (um tema registado sem que a sua instalação tenha criado as suas definições): a posição de uma imagem volta ao
  seu valor predefinido.

## [1.2.35] — 2026-10-06

O segundo tema refeito de raiz: **Granite 2.0.0 «Gazette»**, o jornal das associações e dos clubes.

### Alterado

- **Granite 2.0.0 «Gazette»: uma paginação nova, não uma nova pintura.** O site torna-se o jornal do clube: a data do
  dia e a conta numa linha bem no topo, o nome do site impresso em muito grande (o logótipo por cima, se houver), um
  filete duplo, depois as secções em versaletes entre dois filetes. Na página inicial, uma linha «Breves» faz desfilar
  os últimos tópicos do fórum; para ao passar o rato, com o teclado e com o seu botão de pausa, e fica parada quando o
  visitante pede menos movimento. A primeira página abre-se com a notícia principal em letra grande, com a sua
  capitular ornamentada, e depois as seguintes em colunas separadas por filetes; a coluna da direita tem o calendário,
  a sondagem e quem está online. Os blocos tornam-se painéis sem caixa, titulados em versaletes sobre um filete; um
  artigo lê-se como o corpo de texto de um jornal; o fórum toma o aspeto de uma página de cartas dos leitores; a área de
  membros, o de um cartão de sócio carimbado com o primeiro grupo do membro; o rodapé torna-se uma ficha técnica (os
  parceiros, depois quem publica). Uma barra de leitura fina segue a página. O dia é papel, a noite uma página
  «a tinta», à escolha do visitante; títulos em Playfair Display, texto em Source Serif. Nos telemóveis, as secções
  deslocam-se de lado e tudo passa para uma só coluna.
- **As zonas do Granite têm o nome do seu lugar**: «Secções», «Breves», «Conteúdo», «Pós-conteúdo», «Rodapé».
- **As definições do Granite**: a imagem do banner passa a ser a imagem do título, e a cor do título, uma vez alterada,
  põe uma faixa por trás do nome, como o cabeçalho de um diário; o logótipo é finalmente usado (por cima do nome); uma
  definição «Capitulares» substitui a da barra superior fixa, que já não tem razão de ser.

### Corrigido

- **Acabou-se o azul do Bootstrap entre as cores de um tema.** A barra de uma sondagem, a caixa marcada e o botão de
  opção escolhido (também na administração), a página ativa, a entrada premida de um menu, a seta de um acordeão, o halo
  do foco do teclado e os estados de um botão principal mantinham o azul predefinido do Bootstrap. Tomam a cor de
  destaque do tema, em todos os temas; uma nova verificação, `check-bleu-bootstrap`, procura esse azul nas páginas
  servidas, em repouso e com o foco, de dia e de noite.
- **As secções definidas pelo Forge e pelo Granite estão traduzidas**: «Matchs», «Nous rejoindre», «À la une», «Agenda»
  e «Documents» ficavam em francês num site noutra língua. A verificação das traduções controla agora cada título de
  menu que um tema define.
- **O Editor ao vivo mostra o site com os seus próprios tipos de letra**: impunha os seus ao conteúdo das zonas que
  enquadra.
- **A verificação de contraste mede também o texto sobre um grão** (papel, uma trama): tomava-o por uma imagem e
  desistia de o medir.

## [1.2.34] — 2026-10-06

O primeiro tema refeito de raiz: **Forge 2.0.0 «Coulée»**, para os clãs competitivos.

### Alterado

- **Forge 2.0.0 «Coulée»: uma paginação nova, não uma nova pintura.** A navegação deixa o topo da página por uma barra
  de aço lateral, com o logótipo do site em cima e a conta em baixo (avatar, notificações, modo dia, menu da área de
  membros); nos telemóveis, o logótipo e a conta passam para uma barra superior e a navegação para uma barra de
  separadores no fundo do ecrã, e para lá de cinco entradas as últimas vão para «Mais». A página inicial abre-se com uma
  lareira de lava de onde sobem brasas: o diaporama, o seu título fundido em metal, e a placa dos últimos resultados.
  Por baixo, um painel de bordo: as notícias (a primeira em destaque) e, ao lado, os próximos jogos, o palmarés e quem
  está online. Os blocos são placas de cantos cortados cuja margem brilha ao passar o rato; o fórum põe cada fórum numa
  gaveta de aço com um indicador de calor para a sua atividade; a área de membros e o perfil público recebem a placa de
  identidade e separadores biselados; o rodapé é rebitado e tem os parceiros. O modo dia aclara o conteúdo, enquanto a
  barra e a lareira continuam aço e fogo. Todo o movimento para quando o visitante pede menos movimento. Os blocos dos
  jogos, do palmarés e dos parceiros só são colocados quando os seus módulos estão instalados.
- **As zonas do Forge têm o nome do seu lugar**: «Barra de navegação lateral», «Topo da página», «Conteúdo»,
  «Pós-conteúdo», «Rodapé».
- **As definições do Forge**: o logótipo é finalmente usado (o emblema da barra; sem logótipo, as iniciais do site); a
  imagem do banner passa a ser a imagem da lareira; uma definição «Brasas da lareira» (nenhumas, suaves, vivas)
  substitui a da barra superior fixa, que já não tem razão de ser.
- **O widget do palmarés** mostra o lugar no pódio («1.º», «2.º», «3.º») e o nome da competição em toda a largura
  disponível, com o local e a plataforma por baixo; a linha começava pela plataforma e cortava o nome aos vinte
  caracteres.
- **As imagens do diaporama da demonstração já não têm o seu título**: o diaporama já o escreve por cima, e aparecia duas
  vezes, atravessando a legenda nos telemóveis.

### Corrigido

- **Uma definição de um tema vê-se de imediato** (cor de destaque, imagens de fundo e de banner…): o endereço da folha de
  estilo só seguia a data do ficheiro, e o navegador mantinha a folha antiga depois de uma alteração.
- **A página de personalização dos temas Forge, Granite, Blockcraft e Extend** tem como título o nome do tema, em vez de
  um «Dashboard» não traduzido mostrado em cada separador.
- **Em inglês, o palmarés já não diz «2th» nem «3th»**: os lugares do pódio têm as suas próprias palavras, os outros
  lugares leem-se «#4».
- **Reinstalar um tema já não escreve um aviso no registo** em modo de depuração (um aviso «Deprecated» do PHP 8.2).

## [1.2.33] — 2026-10-06

Uma versão de correção: a CI pública do repositório `neofrag`, partida na 1.2.32 por um problema de ferramentas, está
reparada. Nada muda para os sites.

### Corrigido

- **As verificações automáticas (CI) do repositório `neofrag` voltam a passar.** Desde a 1.2.32, o passo que procura a
  versão correspondente do repositório `extensions` podia falhar: deixava de ler a lista de versões assim que encontrava
  a certa, e o `git`, cortado a meio da escrita, falhava agora que a lista é longa. Agora lê a lista inteira; a mesma
  correção na CI de `extensions`.
- **A verificação de ligações dos documentos já não lê as das bibliotecas incluídas** (a licença do TinyMCE): não podem
  ser corrigidas do nosso lado, e o gnu.org fechava a ligação ao seu endereço antigo.

## [1.2.32] — 2026-10-06

Começa a renovação dos temas: o Forge, o Granite, o Blockcraft e o Extend partilham agora uma base comum. Nada muda no
ecrã; é o alicerce sobre o qual cada um receberá a sua própria identidade.

### Alterado

- **Os temas Forge, Granite, Blockcraft e Extend partilham uma base comum** (`css/nf-socle-themes.css`): 153 das regras
  que tinham em comum são agora escritas uma só vez, no núcleo; umas cinquenta outras ficam em cada tema, porque a sua
  posição na folha de estilo conta. Nada muda no ecrã — verificado elemento a elemento e píxel a píxel, nos quatro
  temas, de dia e de noite, em computador e em telemóvel. É o primeiro passo da sua renovação: cada um terá em breve o
  seu próprio aspeto. Os quatro temas passam para a 1.1.0 e exigem o núcleo 1.2.32.

## [1.2.31] — 2026-10-06

A área de membros, últimos passos: a segurança da conta — os dispositivos com sessão iniciada podem ser desligados, as
sanções de avatar e de assinatura aplicam-se finalmente — e as notificações, mudadas para a área de membros, onde cada
membro escolhe o que recebe. A renovação da área de membros está concluída.

### Adicionado

- **«Segurança» mostra os dispositivos onde a conta tem sessão iniciada, e permite desligá-los**: cada sessão com o seu
  navegador, o seu sistema, o seu endereço IP e a sua última atividade, «Este dispositivo» para aquele a partir do qual
  se olha; «Desligar» para outro, ou «Desligar todos os outros dispositivos». Até agora, «Segurança» só mostrava o
  histórico de inícios de sessão, e uma sessão aberta noutro lado não podia ser fechada. Num site de demonstração, cuja
  conta é partilhada, nenhuma sessão pode ser fechada.
- **Cada membro escolhe as notificações que recebe**, em «Preferências de notificações»: uma caixa por tipo de
  notificação (uma mensagem privada, uma resposta num tópico seguido, uma menção, um comentário, uma reação, um lembrete
  de evento…) para a receber no site, e uma caixa «Por email» para as que o site também envia por e-mail (mensagens
  privadas, respostas no fórum e menções). Sem nenhuma definição, recebe tudo, como antes. Para os autores de módulos: um
  módulo declara as suas através de `types_de_notification()` (vê o guia de criação de um módulo).
- **«As minhas notificações» entra na área de membros**, sob a entrada «Notificações» do seu menu, vinte por página: a
  página vivia à parte, em `/notifications`, e só mostrava as últimas cinquenta (agora até às últimas quinhentas). O
  endereço antigo leva ao novo.

### Corrigido

- **As sanções de avatar e de assinatura aplicam-se agora.** Um moderador podia aplicá-las, mas o perfil nunca as
  verificava: um membro sancionado mudava o seu avatar e a sua assinatura como antes. Vê agora o que a sanção proíbe, o
  seu motivo e até quando.
- **Oito modelos de dados do núcleo leem sempre a sua própria tabela** (sessões, histórico de inícios de sessão, addons,
  traduções, registos…): carregados a partir de um módulo, procuravam uma tabela que não existe. Nada o mostrava ainda no
  ecrã; a nova lista de dispositivos tê-lo-ia mostrado. É o mesmo defeito que o do favicon e dos ficheiros eliminados,
  corrigido na 1.2.29.
- **Uma menção silenciada no fórum já não engole a resposta.** Um membro mencionado num tópico que segue não recebia
  nada quando a menção estava desligada para ele — o e-mail de menção desligado pelo site («Notificar menções @user por
  email»), ou as menções desligadas nas suas preferências. Recebe agora a resposta. Quando a menção lhe chega, substitui
  a resposta, para que não seja notificado duas vezes.

## [1.2.30] — 2026-10-05

A área de membros, segundo passo: o perfil público, e o que cada um escolhe mostrar nele. E uma correção que importa:
um membro com sessão iniciada volta a poder ler e escrever no fórum.

### Adicionado

- **O perfil público de um membro torna-se uma verdadeira página.** A sua capa como banner (podia ser enviada mas não
  aparecia em lado nenhum), o seu avatar sobreposto, o seu nome de utilizador, a sua posição, os seus grupos e a sua
  presença; para um visitante com sessão iniciada, «Contactar» (se as mensagens privadas estiverem instaladas) e a
  bandeirinha «Denunciar este conteúdo», ou «Editar o meu perfil» no seu; depois separadores, cada um no seu próprio
  endereço: Sobre (citação, identidade, ligações, os campos públicos do site e as suas estatísticas), Atividade, e os
  que os módulos instalados trazem — Fórum, Blog, Equipas, Classificados. Um módulo sem nada para mostrar sobre este
  membro não acrescenta nenhum separador vazio.
- **Cada membro escolhe o que o seu perfil mostra**, em «Privacidade e dados»: os seus pontos, o seu karma e os seus dias
  VIP ficam para si até os mostrar (eram visíveis para todos, visitantes incluídos); a sua idade e a sua presença online
  continuam visíveis até as esconder. A sua posição continua pública. A escolha aplica-se em todo o lado: o cartão que
  se abre ao passar por cima de um nome de utilizador, o ponto do avatar, os plantéis das equipas, os widgets «Quem está
  online?» e «Atividade do fórum», a pesquisa de membros, os aniversários do relógio. No seu próprio perfil, um membro vê
  tudo, com um cadeado no que só ele pode ver.

### Corrigido

- **Um membro com sessão iniciada volta a poder ler e escrever no fórum**, e vê as galerias, páginas, tipos de evento e
  pastas abertos aos visitantes. Desde a renovação das permissões, o cargo «Membro» só tinha o que lhe tinha sido dado
  expressamente: um membro via «Sem fóruns» onde um visitante podia ler tudo, e não podia escrever em lado nenhum — só
  os administradores, que contornam as permissões, não viam nada de errado. As regras originais voltam: o que um
  visitante pode fazer, um membro pode fazer; o que só é recusado aos visitantes continua permitido aos membros. Uma
  migração repõe-nas em cada site, sem mexer numa regra já definida para os membros.
- **As posições de reputação (Novato, Bronze, Prata…) estão traduzidas**: apareciam em francês em todas as línguas. E a
  dica de uma posição já não revela a pontuação de karma.
- **Num site de demonstração, o widget «Atividade do fórum» já não mostra a conta de administrador criada na
  instalação** entre os membros online; «Quem está online?» já a deixava de fora.
- **Um sítio web introduzido sem «https://» num perfil leva agora a esse sítio web**: tornava-se uma ligação para uma
  página do próprio site.
- **Uma atualização já não substitui o catálogo do site que serve o marketplace.** O pacote de atualização trazia um
  catálogo antigo, gerado na 1.2.22: colocado nesse site, já não correspondia aos arquivos, e toda a instalação de addon
  era recusada até o catálogo ser regenerado. Um site comum lê o catálogo online e não era afetado.
- **Em «O meu espaço», o grupo do membro alinha-se à esquerda por baixo do seu nome de utilizador**: estava centrado a
  meio do cabeçalho.

## [1.2.29] — 2026-10-05

A área de membros, primeiro passo da sua renovação: uma só moldura, um só menu, as definições onde se espera
encontrá-las. E o marketplace já não oferece a um site um addon feito para uma versão mais recente do NeoFrag.

### Adicionado

- **Uma nova palavra-passe tem de ter pelo menos 10 caracteres.** É recusada se repetir o nome de utilizador, usar só
  um ou dois caracteres diferentes («aaaaaaaaaa», «ababababab») ou for uma das palavras-passe mais comuns
  («qwertyuiop», «1234567890»…). A regra aplica-se na inscrição, na alteração e na reposição (na reposição, o nome de
  utilizador não é comparado). As palavras-passe existentes continuam válidas.

### Alterado

- **A área de membros tem uma só moldura e um só menu.** Mudava de forma de uma página para a outra — um menu à
  esquerda por baixo do cartão do perfil, uma barra «Menu» fechada no topo, nada de nada — e uma página tinha três
  nomes. As páginas da conta têm agora todas o mesmo menu, no mesmo lugar: uma coluna à esquerda num computador, uma
  faixa de separadores com deslocação num telemóvel, a página atual em destaque. O menu leva também às páginas dos
  módulos: mensagens e notificações com o seu número de não lidas, as subscrições do fórum (às quais nenhuma ligação
  chegava), a moderação. A barra superior dos temas Blockcraft, Extend, Forge e Granite e o widget «Área de membros»
  usam este menu, com as mesmas palavras.
- **As definições estão onde se espera encontrá-las.** «A minha conta» reúne o nome de utilizador, o endereço de
  e-mail, a palavra-passe, a língua (que só se podia escolher com o seletor de língua do site) e o fuso horário (que
  estava a meio do perfil público); «Segurança» mantém a autenticação de dois fatores e o histórico de inícios de
  sessão; uma página «Privacidade e dados» contém a exportação dos teus dados e a eliminação da tua conta, arrumadas até
  agora em «Segurança (2FA)».
- **«O meu espaço» abre-se com um cabeçalho compacto** — avatar, nome de utilizador, grupos, «Ver o meu perfil» e
  «Editar o meu perfil» — em vez do grande cartão do perfil, que empurrava o menu um ecrã inteiro para baixo num
  telemóvel.

### Corrigido

- **O marketplace já não oferece a um site um addon feito para uma versão mais recente do NeoFrag.** Só a atualização
  de um addon verificava a versão do NeoFrag que ele exige, não a sua instalação: um site que tinha ficado para trás
  podia instalar um addon que chamava funções que a sua versão não tinha, e partir-se. O marketplace oficial envia agora
  a cada site o catálogo feito para a sua versão (um site mais antigo do que todos os catálogos guardados recebe o mais
  antigo). A janela do marketplace deixa também de fora um addon demasiado recente e diz para atualizar primeiro o site.
  O botão «Atualizações» anuncia uma nova versão do NeoFrag a partir da verificação de atualizações da Monitorização,
  já não só a partir do catálogo.
- **O favicon escolhido pelo administrador é também o ícone do site num telemóvel, e um ficheiro eliminado sai mesmo do
  disco.** Um erro interno impedia o site de encontrar estes ficheiros: o favicon faltava ao adicionar o site ao ecrã
  inicial de um telemóvel (com um aviso no registo de cada vez), e o ficheiro de um anexo eliminado do fórum ou das
  mensagens privadas ficava no disco e na base de dados, tal como o avatar e a capa de um membro que apaga a sua conta.

## [1.2.28] — 2026-10-05

Uma versão de correção: a área de membros de uma conta criada com Discord, GitHub ou Google, a exportação e a
eliminação dos dados pessoais, e o site de demonstração.

### Corrigido

- **Um membro inscrito com Discord, GitHub ou Google já não fica fechado na sua conta.** Sem palavra-passe, não podia
  mudar o seu nome de utilizador nem o seu endereço de e-mail, nem criar uma palavra-passe, nem desligar a autenticação
  de dois fatores, nem eliminar a sua conta: todos os formulários pediam a palavra-passe atual. Confirma agora a sua
  identidade voltando a passar pelo serviço associado; a confirmação dura dez minutos, e iniciar sessão por esse serviço
  conta como uma.
- **A exportação «Os meus dados» funciona, e contém mais.** Falhava com um erro: o membro transferia uma página de erro
  em vez dos seus dados. O arquivo contém agora também o perfil (nome, data de nascimento, localidade, assinatura,
  ligações), as contas associadas, o histórico de inícios de sessão, as notificações e o que os módulos do site guardam
  em seu nome, em texto simples e sem nenhum segredo, nem sequer os seus identificadores de sessão. Os tópicos do fórum,
  os comentários e as mensagens das conversas só entraram com a 1.2.43.
- **Eliminar a tua conta apaga o que a página prometia.** O nome de utilizador é anonimizado nas publicações e o perfil
  esvaziado; as contas associadas, o histórico de inícios de sessão e as notificações são apagados, e uma conta Discord,
  GitHub ou Google associada volta a ficar livre para uma nova inscrição. A mensagem «A tua conta foi eliminada» é
  finalmente mostrada.
- **Num site de demonstração, a conta partilhada já não pode ser alterada**: um visitante podia mudar a sua
  palavra-passe, ligar a sua autenticação de dois fatores ou eliminá-la, deixando todos os outros de fora até à
  reposição.
- **«Connexion» é traduzido no sentido de «iniciar sessão»** (Log in, Anmelden, Iniciar sesión, Accedi, Entrar): as
  outras cinco línguas diziam «ligação de rede». O botão da administração do Discord que liga o bot passa a «Ligar o
  bot».

## [1.2.27] — 2026-10-05

O bot do Discord passa para a **versão 0.2.4**: as imagens viajam entre o fórum e o Discord nos dois sentidos (vê o seu
próprio registo de alterações). A versão 0.2.3 continua a funcionar.

### Adicionado

- **Uma imagem enviada no Discord aparece no fórum** (com o bot do Discord 0.2.4): o bot guarda-a no site, verificada
  como uma imagem colada no editor, em vez de uma simples ligação para o Discord. A API ganha para isso o ponto de acesso
  `POST /api/v1/forum/images`.
- **A validação das inscrições por e-mail** (*Definições → Gestão das inscrições*, caixa «Validação por e-mail»,
  desligada por predefinição): o novo membro recebe uma ligação, válida dois dias. Não pode iniciar sessão antes de a
  abrir; se tentar, é-lhe enviada uma nova ligação. Esta funcionalidade, herdada do NeoFrag, tinha ficado por acabar e
  não tinha nenhuma definição na administração.

### Corrigido

- **O texto acentuado já não aparece codificado.** Um título, um nome de utilizador, uma etiqueta ou o nome do site que
  contivessem «é» ou «—» podiam aparecer como `&eacute;` ou `&mdash;`: nas conversas, na Loja, nas Doações, no índice de
  uma publicação, nos feeds RSS, na administração… Estes textos são agora todos mostrados da mesma maneira, e duas
  verificações automáticas impedem o erro de voltar (uma lê o código, a outra as páginas do site). As sugestões de
  menções e de pesquisa, os assuntos dos e-mails, os dados lidos pelos motores de pesquisa (nome do site, título e autor
  de uma publicação) e os webhooks recebem também texto simples.
- **A inicial de um avatar** mostrava «&» para um nome que começa por uma letra acentuada (administração, página de uma
  campanha de doações).
- **Inscrever-se com Discord, GitHub ou Google exige aceitar o regulamento**, quando o site tem um: um ecrã mostra-o, e
  a conta só é criada depois de marcada a caixa — como com o formulário de inscrição, que estas contas contornavam.
- **O menu já não leva a um módulo ausente**: a ligação de um módulo não instalado ou desligado (Fórum, Galeria,
  Notícias…) ficava no menu — um clique, uma página não encontrada. Desaparece, e volta quando o módulo é religado. Uma
  ligação para uma página personalizada publicada fica; uma para uma página não publicada desaparece também.
- **Num site sem o módulo Eventos**, a página das Equipas já não escreve um aviso no registo de erros.
- **O botão ☰ da administração funciona nos ecrãs grandes**: era lá mostrado sem fazer nada; agora fecha o menu
  lateral, e volta a abri-lo, lembrando a escolha. Num telemóvel, abre o menu como antes.
- **O avatar do autor de uma mensagem do fórum é maior**: 80 px (36 px num telemóvel), em vez dos 40 px que todos os
  temas lhe davam.
- **Uma imagem numa assinatura, ou na descrição de um álbum da galeria, é mostrada**: a página mostrava o código HTML
  (`<p><img …></p>`) em vez da imagem. As assinaturas e descrições já guardadas são mostradas sem ter de as voltar a
  escrever.
- **A versão de texto dos e-mails é legível**: os parágrafos ficavam colados, os acentos codificados, e uma ligação
  acabava seguida da palavra seguinte — inutilizável num cliente de e-mail que só mostra texto.
- **Um formulário adulterado já não faz falhar a página**: uma caixa enviada numa forma que nenhum navegador envia é
  ignorada, como uma caixa não marcada.
- **Num site de demonstração, a conta de administrador criada na instalação fica escondida ao passar o rato e no
  histórico de moderação**, como na sua página: o cartão de membro mostrado ao passar o rato dava o seu nome, os seus
  grupos e as suas datas de visita, e o histórico de moderação o seu nome.

## [1.2.26] — 2026-10-04

O bot do Discord passa para a **versão 0.2.3**: já não escreve um aviso do discord.js a cada resposta privada (vê o seu
próprio registo de alterações). A versão 0.2.2 continua a funcionar.

### Adicionado

- **O regulamento e a mensagem de boas-vindas traduzem-se língua a língua**: *Definições → Gestão das inscrições* tem
  um separador por língua do site, e cada visitante lê o regulamento — e recebe a mensagem — na língua da página. Uma
  língua que ainda não tem o seu próprio texto mostra o texto comum: um site que só tinha um mantém-no para todas as suas
  línguas, nada muda até ser traduzido.
- **O registo de alterações existe em inglês** (`CHANGELOG.en.md`; a versão francesa continua a ser a referência).
- **Cada addon tem agora a sua miniatura, no mesmo formato (960 × 600)**: os módulos API e Discord não tinham, os quatro
  temas do catálogo mantinham uma imagem mais antiga e mais pequena, e três widgets do núcleo não tinham nenhuma em
  *Temas & addons*. Algumas ainda mostram um addon vazio ou não configurado. Um addon novo já não pode ser publicado sem
  a sua.

### Alterado

- **As descrições do catálogo dizem o que faz cada addon**: trinta delas cabiam em poucas palavras ou só se apresentavam
  como «gaming». Descrevem agora o que o addon faz de verdade, e para quem é, nas seis línguas; cinco estavam mesmo
  erradas (o widget Equipas não mostra membros, o widget Transferências mostra os ficheiros mais transferidos…).
- **O módulo Eventos chama-se «Eventos»**, já não «Eventos gaming»: nenhum título do catálogo tem já a palavra
  «gaming».
- **O módulo Pagamentos exige o módulo Gamificação**, que credita os pontos e os dias VIP comprados: sem ele, um
  pagamento era cobrado e nada era creditado. Na instalação do site, escolher Pagamentos acrescenta Gamificação; a
  partir do marketplace, tem de ser instalado primeiro. Se faltar ou for desligado mais tarde, as vendas fecham e a
  administração di-lo; um pagamento recebido não é marcado como tratado, e o Stripe voltará a enviá-lo.

### Corrigido

- **Uma imagem colada (Ctrl+V) ou largada no editor de texto é guardada** — resposta no fórum, comentário, página da
  administração, sala da equipa das mensagens privadas: aparecia partida, e depois desaparecia ao publicar. É agora
  enviada ao site e fica na mensagem. Está reservado aos membros com sessão iniciada: uma imagem JPEG, PNG, GIF ou WebP
  de 5 MB no máximo. O site guarda uma cópia nova, que retira a informação escondida de uma foto (onde foi tirada, por
  exemplo). Acima de 2000 píxeis de lado, a imagem é reduzida; acima de 8192 píxeis de lado ou de cerca de 12,6 milhões
  de píxeis, é recusada. Um GIF animado torna-se uma imagem fixa. No site de demonstração, o envio de imagens continua
  fechado.
- **A mensagem de boas-vindas é mostrada limpa**: escrita no editor rico, chegava às mensagens com as etiquetas à vista
  (`<h3>`, `<p>`…). É agora transformada em texto (títulos, listas com marcas ou numeradas, ligações tornadas
  clicáveis), e o seu título já não mostra `&eacute;` em vez de uma letra acentuada.
- **A mensagem de boas-vindas é também enviada quando alguém se inscreve com Discord, GitHub ou Google**: só o
  formulário de inscrição a enviava.
- **O código QR da autenticação de dois fatores é mostrado**: o ecrã de ativação (*Segurança da conta → Ativar 2FA*)
  mostrava o código da imagem numa caixa de texto em vez da imagem a ler — os formulários não conheciam este tipo de
  campo e tratavam-no como texto.
- **Os logótipos do widget Parceiros levam ao site do parceiro** e contam a visita: levavam a uma página inexistente
  desde a 1.0.0, e o contador «Visitas» já não se mexia. A página dos Parceiros passa também pela visita contada.
- **Os botões «Pagar» (Pagamentos) e «Comprar» (Loja) funcionam**: chamavam um endereço inexistente.
- **Subscrever a newsletter a partir do widget funciona**: o endereço escrito perdia-se pelo caminho. A ligação de
  confirmação do e-mail era relativa (inútil num cliente de e-mail), tal como a ligação de cancelamento das campanhas:
  ambas são agora endereços completos. E se o e-mail não puder ser enviado, a subscrição é anulada e o visitante é
  avisado, em vez de ler «enviado».
- **As ligações dos e-mails do fórum e das mensagens** (menção, subscrição, conversa) são endereços completos:
  relativas, não levavam a lado nenhum.
- **As sondagens seguem a definição «Mostrar resultados»** (depois de votar, ao fechar, nunca) — na sua página como no
  seu widget, que os mostrava sempre. Os gestores veem-nos sempre, com uma nota.
- **Os widgets seguem as regras do seu módulo**: o widget Galerias já não mostra álbuns em rascunho, agendados, na
  reciclagem ou restritos a um grupo; o widget Fórum, nenhum excerto das categorias VIP nem linhas vazias para as
  mensagens eliminadas; o widget Eventos e o calendário, nenhum evento agendado antes da sua hora; o widget Recrutamento
  segue «Ocultar ofertas indisponíveis».
- **Uma ligação para uma página do site, inserida no editor, mantém o seu endereço completo**: o editor encurtava-a num
  caminho (`../../…`) que só funcionava a partir da página de edição — mostrada noutro lado, não levava a lado nenhum.
- **O roteiro** (`ROADMAP.md`) diz que o código está publicado desde 4 de outubro de 2026.

### Segurança

- **Dois endereços técnicos antigos das mensagens mostravam as mensagens sem as proteger**: uma mensagem com código
  podia ter corrido lá no navegador. Nenhuma página os usava já, e o acesso à conversa já era exigido; mostram agora as
  mensagens como a própria conversa.
- **Os botões Facebook e Twitter da página dos Parceiros já não seguem uma ligação `javascript:`** introduzida na
  administração: estes endereços são filtrados e escapados, como já o era o sítio web do parceiro.
- **O botão «Comprar» da Loja e o botão «Pagar» verificam agora que o pedido vem mesmo do site**: uma página maliciosa
  já não consegue fazer um membro com sessão iniciada comprar um artigo sem o saber.
- **Uma compra já não pode ser contada duas vezes**: o débito dos pontos, o stock e a posse são verificados e escritos
  numa só transação — duas compras simultâneas já não vendem duas vezes o último artigo, nem debitam duas vezes um
  saldo. Os pontos ganhos e os dias VIP também são somados de uma só vez: dois ganhos no mesmo instante já não se
  sobrepõem. Um artigo de 0 pontos pode agora ser obtido sem débito (era recusado, «pontos insuficientes»).
- **As inscrições na newsletter têm um limite de frequência**: 5 pedidos por hora e por endereço IP, 3 por dia e por
  endereço de e-mail — o suficiente para impedir o envio em massa de e-mails de confirmação para endereços escolhidos.
- **Uma sondagem cujos resultados estão ocultos já não mostra o seu número total de votos** na lista.

## [1.2.25] — 2026-10-04

O bot do Discord passa para a **versão 0.2.2**: as mensagens do fórum que retransmite perdem todas as suas etiquetas
HTML, mesmo as aninhadas (vê o seu próprio registo de alterações). A versão 0.2.1 continua a funcionar.

### Corrigido

- **A janela do marketplace oferece também os widgets que nenhum módulo fornece** (Sobre, Servidor de jogo, Leitor de
  feeds, Efeito sazonal, Grupo Steam, Servidor TeamSpeak 3, Estado ao vivo): só mostrava módulos e temas, e estes sete
  widgets só podiam ser instalados por «Adicionar», com o arquivo na mão. Um widget já fornecido por um módulo (o do
  Fórum, por exemplo) não aparece lá duas vezes.
- **O guia de administração nomeia o botão que guarda um novo endereço do site** pela sua etiqueta, *Usar*, seguida do
  endereço — e já não por um falso endereço `https://…` que um leitor tomou por uma ligação.

### Segurança

- **Os fluxos de verificação do repositório só recebem um token apenas de leitura** (`permissions: contents: read`):
  não têm nada para escrever, por isso um passo comprometido não poderia mudar nada. Assinalado pela análise de código
  do GitHub quando os repositórios foram abertos ao público.
- **O widget Vídeo só aceita, para um elemento da sua lista de reprodução, um endereço `http(s)` ou um caminho do
  site**: um endereço `javascript:` ou `data:` é ignorado ao clicar. O leitor não executava nada, mas um endereço desses
  não tinha nada a fazer lá. Assinalado pela mesma análise.

### Adicionado

- **Mais três testes no fluxo `installation.yml`**: `check-extensions` instala cada um dos addons do marketplace
  publicado num site que só tem o núcleo, através da janela do marketplace, e por «Adicionar» o que esta não oferece;
  `check-prerequis-absents` retira à vez cada extensão de PHP exigida, e verifica que o assistente e o instalador da
  linha de comandos dizem qual falta; o pacote publicado é instalado pelo assistente num alojamento partilhado simulado
  (Apache sem as funções que lançam um programa, `open_basedir`, 128 MB). E as ligações de todos os documentos são
  verificadas (lychee).

## [1.2.24] — 2026-10-04

**O NeoFrag Reborn abre-se no GitHub.** O código do CMS ([NeoFragReborn/neofrag](https://github.com/NeoFragReborn/neofrag)),
os addons à la carte ([NeoFragReborn/extensions](https://github.com/NeoFragReborn/extensions)) e o bot do Discord
([NeoFragReborn/bot-discord](https://github.com/NeoFragReborn/bot-discord)) são lá publicados com a história das suas
versões — desde a 1.0.0 para o CMS e os addons, desde a 0.1.0 para o bot: o código de cada versão, as suas notas, e os
pacotes a partir da 1.2.23.

### Alterado

- **As bases de dados indicadas são as que são testadas**: cada versão instala-se e passa todo o seu conjunto de testes,
  com PHP 8.2 e 8.5, em MySQL 5.7, 8.0 e 8.4 e em MariaDB 10.5, 10.6, 10.11, 11.4 e 11.8 — o guia de instalação cita
  essas versões, e já não «5.7+» e «10.5+».
- **Os guias dizem onde encontrar o projeto**: o pacote de instalação e o arquivo do bot na sua página de versões, os
  addons à la carte e o seu catálogo na de `extensions`, o código para quem quiser contribuir. Na wiki, as referências a
  um documento do repositório (implementação, arquitetura, ferramentas) levam a esse documento no GitHub, em vez de
  manterem só o seu título.

### Corrigido

- **As definições dos módulos voltam a abrir-se**: a janela «Configuração» dos módulos Fórum, Recrutamento, Notícias,
  Galeria, Eventos, Calendário e Artigos respondia «erro 500» de cada vez que era aberta, em todos os sites, desde a
  1.2.0: o campo «número» recusava um inteiro (o seu passo, e o valor lido da base de dados) desde que o código passou
  à tipagem estrita.
- **Equipas: a opção «Mostrar jogos realizados» volta a poder ser desmarcada.** Guardar as definições guardava a
  palavra «Array» em vez de um sim ou de um não, que era sempre lido como «sim».
- **As definições de um autenticador nunca configurado** (Google, Discord, GitHub) abrem-se sem escrever oito avisos no
  registo.
- **O painel e as estatísticas de um site sem Artigos, Gestor de bugs ou Fórum** já não escrevem avisos no registo de
  cada vez que são abertos: os cartões destes módulos só aparecem se estiverem instalados, e já não é consultada
  nenhuma tabela ausente.
- **O assistente de instalação volta a ler a configuração que acabou de escrever**: num alojamento cuja cache do PHP
  não volta a verificar os ficheiros, uma segunda tentativa do passo «Base de dados» ainda lia o antigo
  `config/db.php`.
- **Um site implementado a partir do git e instalado pelo assistente web funciona**: o assistente não escrevia
  `config/neofrag.php` (o pacote tem-no, um clone só tem o seu modelo), e cada página respondia «erro 500» sem uma
  única linha no registo.
- **O exemplo `nginx.conf` arranca**: a regra do TinyMCE, sem aspas, fazia o nginx recusar toda a configuração
  («missing closing parenthesis»). Os seus redirecionamentos já não têm o parâmetro interno `request_url`.
- **Os ficheiros enviados são servidos com Apache e PHP como módulo**: a proteção de `upload/` usava uma diretiva
  proibida num `.htaccess`, e toda essa pasta — avatares, galeria, mediateca — respondia «erro 500».
- **Com o Caddy, os ficheiros enviados que não são imagens** (um PDF da mediateca) são servidos: o exemplo só servia
  imagens.
- **`/index.php` leva à página inicial** (redirecionamento permanente) em vez de a uma página não encontrada; e um
  endereço com uma extensão que nenhuma página serve (`/newsletter/.env`, que os robôs tentam) responde 404 sem
  escrever uma anomalia no registo.
- **`tools/check-all.php` com PHP 8.2**: cada verificação contava lá como falha, mesmo quando passava — o código de
  saída era lido uma vez a mais, o que o PHP só permite a partir da 8.3. Para quem contribui com PHP 8.2.

### Segurança

- **O assistente de instalação já não volta a abrir-se num site instalado cuja base de dados não responde.** Se o
  bloqueio `install/db.txt` tivesse desaparecido, uma falha temporária da base de dados voltava a abrir o assistente,
  cujo passo «Base de dados» reescreve `config/db.php`: um visitante podia então desviar o site para a sua própria base
  de dados. O site mostra agora «temporariamente indisponível». E o passo «Administrador» recusa criar uma conta assim
  que o site tem uma.
- **O Apache só executa agora o `index.php` da raiz**: a regra de reescrita deixava correr diretamente qualquer ficheiro
  `index.php` de uma subpasta (mais de oitenta controladores internos).

### Adicionado

- **Mais quatro verificações**: `check-assistant` percorre o assistente de instalação do princípio ao fim, como um
  visitante, para cada perfil (ecrãs, recusas, conta criada, início de sessão com a palavra-passe introduzida, registo
  silencioso); `check-reglages` abre o ecrã de definições de cada addon, guarda-o tal como está e volta a abri-lo;
  `check-serveur-web` testa o Apache, o nginx e o Caddy, com as configurações incluídas, colocando sondas (pastas e
  ficheiros proibidos, scripts que não devem correr, reescrita, cabeçalhos de segurança); `check-nouveau-venu` segue à
  letra o README e o guia do contribuidor numa máquina limpa. Os fluxos `installation.yml` e `nouveau-venu.yml`
  voltam a corrê-los todas as semanas.
- **Os comandos do README funcionam numa máquina nova**: o seu bloco «Desenvolver» terminava num conjunto de testes que
  falhava por falta de uma base de testes; remete agora para o guia do contribuidor, que cria essa base
  (`php tools/prepare-test-db.php`).

## [1.2.23] — 2026-10-04

O bot do Discord passa para a **versão 0.2.1**: nada muda no seu funcionamento — torna-se um projeto próprio, com a
sua licença, o seu NOTICE e o seu próprio registo de alterações. Instalá-lo só é útil a quem quiser estes ficheiros; a
0.2.0 continua a funcionar.

### Alterado

- **O mapa do site já não lista uma secção vazia**: notícias, recrutamento, uma FAQ, equipas… sem nada para mostrar já
  não são oferecidas aos motores de pesquisa, que só lá encontrariam uma página «ainda nada». A secção volta sozinha com
  o seu primeiro conteúdo. O contacto, o livro de visitas, a newsletter, a rádio web e as doações continuam listados:
  têm sempre algo a oferecer.
- **As cópias de segurança já não se acumulam**: cada atualização faz uma completa (16 MB e mais), e nada as retirava.
  O site guarda sempre as cinco mais recentes, e retira as outras ao fim de trinta dias; um ficheiro colocado à mão em
  `backups/` nunca é tocado.
- **A instalação verifica tudo o que o CMS precisa.** O assistente não verificava nem `openssl` (sem ela, guardar um
  segredo — servidor de e-mail, captcha, autenticação de dois fatores — falha com um erro), nem `fileinfo` (sem ela,
  todo o envio de ficheiros é recusado), nem `iconv` (o código QR da autenticação de dois fatores), nem a cifra de
  palavras-passe Argon2, sem a qual a criação da conta de administrador falhava. Verifica-as agora e recusa continuar se
  faltar uma; `install/cli.php` exige exatamente a mesma lista, versão do PHP incluída, em vez de duas extensões. A
  *Monitorização* e o painel da administração mostram a mesma lista. É necessário PHP 8.2 ou posterior; o produto está
  testado até ao PHP 8.5.
- **nginx e Caddy**: o pacote traz finalmente os exemplos de configuração que os guias prometiam (`nginx.conf`,
  `Caddyfile`), genéricos, e que bloqueiam as mesmas pastas e ficheiros sensíveis que o `.htaccess` do Apache — o
  antigo exemplo do nginx deixava acessíveis `install/`, `tools/`, `tests/` e `docs/`, e o excerto do guia de
  implementação só protegia três pastas em oito.
- **Cada addon diz quem o escreveu.** Os addons do NeoFrag original mantêm os seus autores, Michaël BILCOT e Jérémy
  VALENTIN — os conectores de início de sessão e as línguas, que não assinavam nada, nomeiam-nos agora, e as traduções
  inglesas originais voltam a citar os seus tradutores, FoxLey e eResnova. As adaptações citam o seu autor: o Relógio
  (ArkaNiX), o tema Extend (Chewbaka), o gestor de ficheiros (HiddenBlob, HiddenCMS) e as Doações (HiddenBlob, a partir
  de majiid). O resto é assinado «NeoFrag Reborn». Os temas Nebula, Blockcraft, Forge e Granite passam para a LGPL,
  como o produto; o Extend mantém a licença do seu autor (CC BY-NC-SA). A licença de cada addon LGPL remete para o
  texto oficial da LGPL.
- **HSTS**: o cabeçalho definido pelo `.htaccess` já não se estende aos subdomínios e já não declara o site apto para a
  lista de pré-carregamento dos navegadores (`includeSubDomains` e `preload` retirados): definidos por predefinição,
  vinculavam durante um ano todos os subdomínios de quem instalava o CMS.

### Corrigido

- **Um site instalado a partir do pacote não registava nenhum erro.** O pacote não continha a pasta `logs/`, e nada a
  criava: o PHP escrevia os seus erros no registo do servidor, *Monitorização → Registo de erros* ficava vazio e dizia
  que a pasta estava «sem permissão de escrita». O pacote traz agora `logs/`, `cache/` e `backups/` com a sua proteção,
  e um site que não tenha `logs/` volta a criá-la sozinho: basta atualizar para reparar um site já instalado.
- **A documentação da wiki já não tem ligações mortas.** Três referências das suas páginas a documentos que o site não
  tem (o manual das ferramentas, uma parte do guia de implementação) levavam a uma página de erro; agora só mantêm o seu
  texto. O guia do marketplace já não menciona addons «de terceiros» que não existem, nem procedimentos reservados ao
  servidor do projeto.
- **Definições → Referenciação** recusava guardar assim que uma descrição, um slogan ou um título para os motores de
  pesquisa tinha muitas letras acentuadas: cada uma contava como vários caracteres, e uma descrição portuguesa de 139
  caracteres ultrapassava o limite de 160. Um código de verificação da Google ou do Bing colado como etiqueta inteira era
  recusado da mesma forma. Os redirecionamentos e a referenciação por conteúdo seguem a mesma regra.
- **O relatório de SEO** reconhece uma propriedade da Google Search Console verificada pelo DNS do domínio (em vez de a
  dizer «não declarada»), e aconselha submeter o mapa do site que reúne todas as línguas, `/sitemap.xml`, em vez do da
  língua mostrada apenas. Quando a Google está verificada, já não diz que o Bing está «não declarado»: o Bing Webmaster
  Tools pode importar um site a partir da Google Search Console sem colocar nada no site, por isso o relatório não o
  consegue ver.
- **`humans.txt`, `robots.txt` e a chave IndexNow**: um endereço ausente — um `humans.txt` vazio, uma chave
  desconhecida — responde um simples «não encontrado», sem escrever um erro no registo do site por cada robô que o
  pede.

---

## [1.2.22] — 2026-10-03

### Adicionado

- **A referenciação por conteúdo**: um botão «Referenciação» no cartão de edição de uma notícia, de uma publicação do
  Blog, de uma página ou de uma página da wiki dá, para cada língua, o título e a descrição mostrados pelos motores de
  pesquisa e pelas pré-visualizações de partilha. Deixados vazios, continuam automáticos.
- **Definições → Referenciação → Relatório de SEO**: o que um motor de pesquisa vê, medido no site — as páginas do mapa
  do site por módulo, os textos de cada língua, a imagem de partilha, Google e Bing, `robots.txt`, a manutenção —, com,
  para cada ponto, a ligação para o que o corrige.
- **Redirecionamentos**: um endereço antigo leva ao novo (301) em vez de responder «Página não encontrada», com o número
  de visitas que ainda recebe. Uma página ou uma página da wiki com o nome alterado deixa sozinha o seu; podem
  acrescentar-se outros à mão, para o endereço de um site antigo, por exemplo.
- **Avisar os motores (IndexNow)**: ligado em Definições → Referenciação, o site assinala, em poucos minutos, cada página
  que aparece, muda ou desaparece, ao Bing, Yandex, Seznam, Naver, Yep e Amazon. A Google não participa: para a Google,
  o mapa do site continua a ser o caminho. As páginas de cada módulo listado no mapa do site são assinaladas, sem nada a
  acrescentar; a tarefa agendada do site é necessária.

### Corrigido

- **Moderação**: na administração, um moderador sem o direito «conversas privadas» podia abrir a denúncia de uma
  mensagem privada, e esse acesso não era anotado no registo de auditoria. Quem denuncia uma mensagem privada vê
  finalmente o aviso que lhe é destinado.
- **Mensagens**: a mensagem enviada para a sala da equipa a partir da administração respondia sempre «Nenhuma sala da
  equipa configurada».
- Acabaram-se os avisos do PHP no registo quando a denúncia, a mensagem ou o membro em causa tinham sido eliminados.

---

## [1.2.21] — 2026-10-03

### Adicionado

- **Definições → Referenciação**: para cada língua, o slogan do título da página inicial e a descrição mostrada pelos
  motores de pesquisa; uma imagem de partilha (1200 × 630) para as pré-visualizações no Discord, no X ou no Facebook; os
  códigos de verificação da Google Search Console e do Bing Webmaster Tools.
- **Um mapa do site por língua**, reunidos na raiz (`/sitemap.xml`): cada módulo lista lá as suas próprias páginas
  públicas — o blog, o fórum e os seus tópicos, a wiki, a galeria, o calendário, os eventos… —, só as que um visitante
  pode ler e que existem na língua do mapa.
- Na página inicial, **dados estruturados** que descrevem o site, o seu logótipo e as suas redes sociais.

### Alterado

- **Os perfis dos membros e o diretório de membros** já não são oferecidos aos motores de pesquisa, nem os resultados
  da pesquisa: pouco conteúdo, e um membro não deve acabar na Google sem o ter escolhido.

### Corrigido

- **O mapa do site** só continha endereços relativos — ignorados pelos motores de pesquisa —, numa só língua, sem a
  wiki nem o fórum; `robots.txt` anunciava-o com um endereço relativo.
- **Cada página do Blog** declarava `/articles/…`, um redirecionamento, como o seu endereço canónico; a página inicial,
  `/index`. As ligações entre línguas estão completas, com a versão predefinida (`x-default`).
- **O título da página inicial** já não repete o nome do site («NeoFrag Reborn | NeoFrag Reborn»).
- **Google Analytics**: a definição só aceitava o antigo formato `UA-…`, que a Google abandonou em 2023; aceita os
  identificadores atuais, `G-…`.
- **Língua do navegador**: um visitante cujo navegador só anuncia `de-DE` chega em alemão, e não na língua predefinida
  do site.
- **O seletor de língua** e o aviso «Este conteúdo não existe em …», a partir do Blog, levavam ao seu endereço antigo.

### Segurança

- **Os endereços completos** do cabeçalho da página (canónico, línguas, partilha) são construídos a partir do endereço do
  site, e já não a partir do cabeçalho `Host` do pedido, que qualquer pessoa pode falsificar.

## [1.2.20] — 2026-10-03

### Adicionado

- **Um perfil de instalação «Associação / clube»**: notícias, fórum, galerias, calendário, doações, newsletter, wiki e
  FAQ, sem a parafernália esport. Junta-se a *Completo*, *Gaming / eSports*, *Comunidade* e *Só o núcleo*, nas seis
  línguas do assistente.

### Corrigido

- **A exportação dos membros em formato JSON** (RGPD, artigo 15) escreve agora a sua etiqueta «Exportação dos dados dos
  membros»: saía vazia.
- **Blog**: na página de um autor, «Ver o perfil» levava a uma página não encontrada.

### Segurança

- **O instantâneo da demonstração já não tem nenhum segredo.** O ficheiro que repõe a demonstração, incluído no pacote
  da demonstração, teria copiado o utilizador do envio de e-mail, a chave do serviço de tradução automática do NeoFrag
  original e as chaves dos widgets Twitch e TeamSpeak de um site onde tivessem sido introduzidas; ainda tinha a linha,
  vazia, da chave secreta do captcha. Nenhuma chave tinha fugido. Uma verificação controla agora a lista face ao código,
  e o próprio ficheiro incluído.

### Documentação

- O NeoFrag Reborn apresenta-se como **o CMS de código aberto para as comunidades, do gaming às associações**; o guia
  dos conceitos diz que perfil escolher para o site de uma associação ou de um clube.

## [1.2.19] — 2026-10-02

### Adicionado

- **Um captcha moderno, ativo desde a instalação: ALTCHA.** É alojado pelo próprio site — sem conta, sem chave, sem
  cookie, sem serviço de terceiros. O visitante vê uma caixa que se marca sozinha enquanto preenche o formulário: o seu
  navegador faz um pequeno cálculo, que o servidor verifica. Protege o formulário de contacto, a inscrição e o
  recrutamento. Na atualização, um site sem chaves reCAPTCHA passa para o ALTCHA; um site que as tinha mantém o
  reCAPTCHA. O ALTCHA torna caro para um robô o envio em massa; contra um atacante determinado, os fornecedores
  seguintes, que acrescentam as suas próprias verificações, são mais sólidos.
- **Em alternativa, Cloudflare Turnstile, hCaptcha ou Google reCAPTCHA v2**, em *Definições → Segurança anti-bot*, com a
  ligação para a consola de cada um. A chave secreta é cifrada e nunca mais é mostrada. Sem as suas duas chaves, um
  fornecedor é substituído pelo ALTCHA em vez de deixar o formulário aberto.
- O captcha e as suas mensagens existem nas seis línguas.

### Corrigido

- **reCAPTCHA**: a chave secreta era enviada no URL de verificação; é agora enviada no corpo do pedido, como a Google
  exige. A ligação desatualizada para a sua consola é substituída, e o endereço do visitante transmitido segue a regra
  usada pelo resto do site, que tem em conta um proxy declarado de confiança.
- Quando a verificação anti-bot não foi concluída, a mensagem é escrita por extenso por baixo do captcha, em vez de um
  ícone solitário cuja dica não aparece num ecrã tátil.

### Segurança

- **Passar o captcha uma vez já não dispensa de o voltar a passar.** Desde o NeoFrag original, um captcha passado
  dispensava de o voltar a passar em todos os envios seguintes do mesmo formulário, até ao fim da sessão: um robô que
  resolvesse um só desafio podia depois enviar sem limite. A dispensa só vale agora para um único reenvio, depois de
  outro erro no formulário.
- Uma solução ALTCHA só pode ser usada uma vez: voltar a apresentá-la é recusado.
- A política de segurança das páginas já não permite a Google por predefinição: só os endereços do fornecedor de captcha
  escolhido, e nenhum com o ALTCHA.

## [1.2.18] — 2026-10-02

### Corrigido

- **Reordenar arrastrando volta a funcionar**: as categorias e os fóruns do fórum, as equipas e os seus cargos, os
  parceiros, os grupos de membros, as línguas e os conectores de início de sessão, e as linhas, colunas e widgets do
  Editor ao vivo. A posição chegava do navegador como texto, e a deslocação parava com um erro — detetado no registo da
  demonstração.
- **Na demonstração, o Editor ao vivo** volta a abrir as definições de um widget, e qualquer alteração lá recebe a
  mensagem «Ação desativada no site de demonstração.» em vez de um erro silencioso. As disposições não podem lá ser
  alteradas: um widget HTML escrito por um visitante seria mostrado a todos os outros.

### Documentação

- **O guia de instalação por FTP** nomeia o pacote certo (`neofrag-reborn-public-<version>.zip`), descreve a atualização
  pelo botão, e já não pede para correr uma ferramenta que falta no pacote: as migrações aplicam-se sozinhas na primeira
  visita, demonstração incluída.
- **Os guias para programadores** descrevem como pôr um endereço introduzido numa ligação só depois de verificado
  (`nf_url_sure()`), o bloqueio do site de demonstração, as listas divididas em páginas, os contadores e as datas; os
  números do README e dos guias seguem o código (62 módulos, 40 widgets).

## [1.2.17] — 2026-10-02

### Segurança

- **Na demonstração, o separador Ficheiros da Monitorização** ainda mostrava a árvore real da instalação (nomes de
  pastas e ficheiros, sem o seu conteúdo): mostra agora uma árvore de exemplo, como o gestor de ficheiros.

## [1.2.16] — 2026-10-02

### Segurança

- **Uma ligação maliciosa já não corre a partir do endereço de uma denúncia.** O membro que denuncia fornece este
  endereço, que é depois mostrado ao moderador: um endereço «javascript:» corria no seu navegador ao clicar. A mesma
  proteção aplica-se às ligações de um feed RSS (widget), de um diaporama, do diretório de ligações, dos parceiros e a
  qualquer endereço que o site construa: só passam endereços web, de e-mail, de telefone ou internos do site.
- **Eliminar um cargo, criar a chave do bot do Discord, fechar um classificado ou trancar um tópico do fórum** faziam-se
  com uma simples ligação: bastava uma ligação maliciosa para desencadear a ação para um administrador, um autor ou um
  moderador com sessão iniciada. Estas ligações têm agora um token de segurança.
- **A demonstração blindada.** A sua conta partilhada (demo / demo) é administradora: podia ligar o modo de depuração
  para todos os visitantes, percorrer e ler os seus ficheiros, o registo de erros e o phpinfo, ver a chave da tarefa
  agendada, mudar as definições do site, criar chaves da API, banir para sempre endereços IP ou membros, desviar o
  endereço PayPal das doações e apagar imagens que a reposição não conseguia trazer de volta. Numa demonstração,
  qualquer ação para uma zona bloqueada é recusada antes de chegar ao seu ecrã, os ecrãs sensíveis mostram um aviso, o
  gestor de ficheiros só mostra uma árvore de exemplo, nenhum ficheiro é eliminado, e a reposição restaura também os
  mapas e os modos de jogo, os cargos das equipas, as notificações, os pontos e as permissões.

### Corrigido

- **Um ficheiro recusado no envio** (extensão proibida, ou demonstração) fazia falhar a página (erro 500), por exemplo
  ao mudar o avatar: o campo diz agora porquê.
- **As estatísticas sobre um período desmesurado** (séculos, hora a hora) esgotavam a memória do servidor: o período e
  o número de pontos estão limitados.
- **A exportação dos membros** escrevia um aviso do PHP 8.5 por linha no registo.
- **Na demonstração**, o banner verde tapava as notificações, e a conta de emergência aparecia na lista de jogadores de
  uma equipa e na atividade do painel.
- **Uma fina linha branca corria ao longo da margem direita do editor de texto em modo escuro** (comentários,
  mensagens, administração): o editor pintava a sua zona de escrita de branco, e esse branco transbordava uma fração de
  píxel.

## [1.2.15] — 2026-10-02

### Alterado

- **A pesquisa rápida da administração (Ctrl+K), refeita**: secções em versaletes discretos, alinhadas com os ícones, em
  vez de títulos colados à margem; o nome da secção já não se repete no fim de cada linha; uma lupa no campo; a parte
  encontrada em destaque. A pesquisa ignora os acentos (num site francês, «evenements» encontra «Événements gaming») e
  procura também nas secções («gaming» lista os seis módulos dessa secção).
- **A foto do membro na barra lateral da administração**, em vez da primeira letra do seu nome de utilizador, que se
  mantém para uma conta sem foto.

### Corrigido

- **As teclas de seta, na pesquisa rápida**, moviam uma seleção invisível: só o rato destacava uma linha.
- **«O que esta versão traz»**, na janela de atualização, abria o registo de alterações no topo da página, na versão
  anterior: leva agora à versão anunciada.
- **A caixa de comentários de uma publicação do Blog** perdia a sua margem interior e a linha por baixo do seu título:
  «Comentários» e o contador tocavam na margem. Cinco temas aplicavam a qualquer caixa aninhada uma regra pensada para o
  cabeçalho das caixas transparentes.

## [1.2.14] — 2026-10-02

### Alterado

- **A página Monitorização, em separadores**: um resumo sempre visível (versão e atualização, PHP, cópias de segurança,
  ferramentas de diagnóstico), e depois *Visão geral*, *Cópias de segurança*, *Diagnóstico*, *Servidor e segurança* e
  *Ficheiros*. Empilhava uma dezena de cartões numa coluna estreita, ao longo de quase 2700 píxeis; cada separador cabe
  agora num ecrã. A saúde do site diz-se com sobriedade («Está tudo bem», «Pontos a verificar», «Erros a corrigir»).
- **Os botões de ação da administração, iguais em todo o lado**: editar, acesso e ordenar em cinzento neutro; eliminar
  com contorno vermelho, com um caixote do lixo. Mudavam de uma página para a outra — azul-celeste, verde-azulado, botões
  cheios, cruzes ou caixotes. As regras de design da administração estão descritas no guia «Criar um módulo», e uma
  verificação fá-las cumprir.
- **Os distintivos de estado, suaves em todo o lado**: um fundo pálido e um texto mais marcado da mesma cor, como
  «Publicado». «Ativo» era escrito a branco sobre verde cheio, «Built-in» a preto. Acabaram-se os botões coloridos
  cheios nas linhas das listas (pré-visualizar, duplicar, restaurar…).
- **O botão de criar, no mesmo lugar em todo o lado**: no topo do cartão da lista que alimenta. Oito páginas punham-no
  na barra superior, no fim da lista ou por cima do cartão (publicidade, loja, doações, pagamentos, diaporama, equipas,
  jogos, cópias de segurança da Monitorização).
- **Temas & addons, compacto**: uma pesquisa, o número de extensões de cada tipo, filtros de tipo e de estado que se
  combinam («módulos inativos»), e uma vista em lista mais densa, mostrada por predefinição, ao lado da grelha. A página
  abre-se nos módulos; alinhava as cerca de 120 extensões como grandes cartões, ao longo de mais de 10 000 píxeis.
- **Os modelos de email, numa só tabela**: os modelos agrupados por família (contas, fórum, mensagens, moderação,
  newsletter), com o seu assunto, as suas línguas e o seu estado numa linha. A página empilhava um cartão por modelo, ao
  longo de 2000 píxeis; agora é metade.
- **Discord, em separadores**: *Visão geral*, *Funcionalidades*, *Configuração do servidor*, *Canais e fóruns*, *Grupos e
  cargos*, *Cargos temporários* — passa-se de um para o outro sem voltar à página principal. O registo do bot desloca-se
  dentro do seu cartão em vez de alongar a página.
- **As estatísticas, mais legíveis**: as datas e o intervalo cabem numa linha. O gráfico já não repete na sua legenda as
  caixas, que já têm a cor de cada série. As suas curvas já não descem abaixo de zero e o seu eixo para nos limites do
  período escolhido. As suas datas seguem a língua da página em vez da do navegador (numa página francesa, «2 oct.
  2025», não «Oct 2, 2025»). A partir de três séries, só linhas em vez de áreas sobrepostas.
- **Permissões → Vista em matriz**: os módulos agrupados por secção, como na barra lateral, em colunas. Estavam
  alinhados como uns quarenta mosaicos grandes, sem ordem.
- **O registo de auditoria, em páginas de 50**: alinhava 200 linhas num só bloco, ao longo de mais de 5000 píxeis.
- **Utilizadores: uma pesquisa** por nome de utilizador ou e-mail, por cima da lista de membros.
- **O gestor de ficheiros diz o que fazem os seus botões**: «Adicionar ficheiros», «Criar uma pasta», «Mover»,
  «Eliminar», em vez de quatro pastilhas coloridas só com ícone. As suas janelas têm um «Cancelar» neutro e um botão de
  confirmação com nome.
- **«Guardar» no botão que confirma uma alteração**, em todo o lado. Vinte formulários diziam «Editar» (fórum, notícias,
  páginas, eventos, equipas, jogos, galerias…).

### Corrigido

- **Algumas listas da administração paravam na sua primeira página**, sem ligação para o resto: os membros e os
  comentários para lá dos 20 primeiros, os prémios e os recrutamentos para lá dos 10 primeiros, assim como «As minhas
  subscrições» do fórum do lado do membro. O contador do cartão só dava a página mostrada («20 membros» para 25).
- **Datas introduzidas em inglês e em alemão.** Em inglês, o seletor escrevia o 2 de outubro como «10/02/2026 g:05 A»,
  preenchia um evento datado de 30/09/2026 como «06/09/2028», e o 2 de outubro era guardado como 10 de fevereiro. Em
  alemão, uma data mostrada como «02.10.2026» não era relida ao guardar. As datas curtas em inglês escrevem-se agora
  dia/mês/ano com um relógio de 24 horas, como nas outras línguas («02/10/2026 14:05»); um teste verifica, nas seis
  línguas, que uma data mostrada é relida.
- **Acabaram-se os falsos alertas de «ficheiro corrompido» antes de uma atualização.** A Monitorização comparava os
  ficheiros do site com a lista de impressões da última versão publicada, mesmo quando o site ainda não estava a esse
  nível: tudo o que a nova versão muda aparecia corrompido (102 alertas num site mesmo antes da sua passagem para a
  1.2.13). A verificação espera agora que o site esteja atualizado, e di-lo.
- **Estatísticas**: em alemão, o gráfico nunca carregava; os botões «7 dias», «30 dias», «90 dias» e «1 ano» não faziam
  nada, em nenhuma língua.
- **A data de modificação de uma página da wiki** mostrava a hora da última visita: o contador de visitas reescrevia a
  data de modificação. O mesmo defeito nos classificados.
- **Na janela de eliminação do gestor de ficheiros**, «Cancelar» e «Eliminar» eram dois caixotes vermelhos idênticos.
  Os textos franceses do módulo tinham perdido os acentos («Dossier cree avec succes», «Element deplace») e contavam em
  «élément(s)».
- **O painel e o registo de auditoria** mostram o que aconteceu («Modo de depuração ligado», «Definições guardadas») e
  já não identificadores técnicos (`monitoring.debogage.allume`).
- **A página Comentários da administração** mostrava código em vez da data de cada comentário.
- **Um título acentuado** já não mostra os seus acentos como código HTML no cabeçalho dos cartões da administração.
- **Em modo escuro**, os textos coloridos da administração (alertas, distintivos, mensagens de sucesso a verde) ficavam
  escuros sobre fundo escuro, quase ilegíveis.
- **As datas da moderação** (sanções, denúncias, histórico de um membro), **da reciclagem, das cópias de segurança, das
  notificações e do registo do bot do Discord** são mostradas na língua e no fuso horário do visitante
  («21/09/2026 22:54»), e já não tal como estão na base de dados («2026-09-21 20:54:11»).
- **Em francês, a etiqueta das estatísticas «Connexions de membres»** (inícios de sessão de membros) estava mal escrita,
  «Connections de membres».
- **As datas seguem a língua do visitante**: a wiki, o Gestor de bugs, os classificados, o livro de visitas, a
  newsletter, as conversas arquivadas, o gestor de ficheiros e o widget dos eventos escreviam-nas fixas — tal como estão
  na base de dados («2026-09-20 22:54») ou à francesa mesmo em alemão (esperava-se «02.10.2026»).

## [1.2.13] — 2026-10-02

### Adicionado

- **O registo de erros, na administração** (*Sistema → Monitorização → Registo de erros*). Já não é preciso FTP nem
  acesso ao servidor para saber o que falhou: os erros do site, agrupados, do mais recente ao mais antigo, ordenados por
  gravidade, filtráveis por período ou por palavra. O caminho de instalação, as palavras-passe, as chaves e os tokens
  ficam ocultos no ecrã, os endereços de e-mail e IP em parte; o ficheiro pode ser transferido e esvaziado (o antigo é
  guardado ao lado). A Monitorização assinala os erros das últimas 24 horas, e uma pasta `logs/` em que o site já não
  consegue escrever.
- **As ferramentas de diagnóstico ligam-se a partir da administração** (*Monitorização → Diagnóstico*), durante uma
  hora, sem editar `config/neofrag.php` por FTP:
  - **o modo de depuração** — o que mostra, a barra no fundo da página e o detalhe dos erros, só é mostrado agora aos
    administradores com sessão iniciada; ligado, era mostrado a todos os visitantes, consultas e dados de visita
    incluídos;
  - **o rastreio de páginas**, que finalmente se pode ler na administração: as últimas páginas servidas, as suas
    consultas e a sua duração, com os valores sensíveis ocultos;
  - **a verificação das traduções**, com a lista dos textos em falta por língua e por extensão; a bandeira que põe à
    frente dos textos traduzidos só é mostrada agora aos administradores.
- **A administração arrumada em nove secções**: *Conteúdo*, *Comunidade*, *Animação*, *Gaming*, *Conhecimento*,
  *Multimédia*, *Difusão*, *Suporte*, *Monetização* (e *Sistema*). As seis anteriores misturavam o calendário com a
  multimédia ou o Gestor de bugs com a comunidade, e treze módulos acabavam em «Outros módulos».
- **O menu «Navegação» do Editor ao vivo** segue as mesmas secções, em submenus que se abrem, com uma pesquisa; alinhava
  mais de quarenta páginas umas atrás das outras.
- **O endereço do site corrige-se a partir da administração** (*Monitorização → Endereço do site*). Depois de uma
  mudança de domínio, as ligações dos e-mails continuavam a levar ao antigo até se editar `config/url.php` por FTP.
- **Uma referência para cada erro.** Um visitante que encontre um erro lê uma referência de oito caracteres; o
  administrador procura-a no registo e chega à linha certa.
- **As horas no fuso horário de cada um.** Um membro escolhe o seu fuso horário no seu perfil; senão, o site usa o do
  seu navegador, e senão o do site, uma nova definição em *Definições → Preferências gerais*. As horas introduzidas nos
  formulários entendem-se no fuso horário de quem as introduz. A lista dos fusos horários está agrupada por região, com
  as cidades nomeadas na língua do site.

### Corrigido

- **As horas já não estão desfasadas.** O site mostrava-as na hora do servidor — a hora universal — para todos: duas
  horas de atraso para um visitante em França no verão. O fuso horário do perfil do membro, por seu lado, só se aplicava
  nas páginas do perfil, e deslocava as datas que esse membro guardava. O calendário, a wiki, o Gestor de bugs, as
  conversas arquivadas e o gestor de ficheiros escreviam as suas horas sem conversão; a emissão «em direto» da rádio web
  era lida na hora do servidor.
- **O calendário**: o início e o fim de um evento escolhem-se num seletor de datas, e já não num campo de texto no
  formato «AAAA-MM-DD HH:MM:SS»; um título acentuado já não mostra os seus acentos como código HTML; a descrição
  formatada já não mostra as suas etiquetas; a exportação do calendário recebe um texto limpo.
- **As listas com pesquisa** dizem «Sem resultados» na língua do site, e não «No results found».
- **O Editor ao vivo** oferece agora compor as páginas Fórum, Galerias, Equipas, Contacto e Palmarés: o seu menu
  deixava-as de fora, porque não reconhecia a sua página inicial.
- **Uma página que falha diz «Ocorreu um erro» (500)**, com a sua referência, em vez de «Página não encontrada», que
  fazia pensar num endereço errado.
- **Acabaram-se as páginas em branco.** Um erro fatal, ou um erro lançado fora de uma página, mostra uma página de erro
  na língua do visitante. O mesmo para uma base de dados inacessível (503), em vez de uma mensagem em bruto em inglês, e
  a falha fica finalmente anotada no registo.
- **Uma ação que falha di-lo**: uma janela que não se abre, um envio recusado, um servidor que já não responde mostram
  uma mensagem, com a referência do erro — em vez de um botão que fica cinzento sem uma palavra. A Monitorização, quando
  não consegue atualizar-se, para a sua ampulheta.
- **A cópia de segurança e a atualização dizem a verdade.** Anunciavam «Cópia de segurança guardada» ou «Atualização
  concluída com sucesso» mesmo depois de uma falha; agora só anunciam o sucesso se o servidor o confirmar, e senão dizem
  o que falhou. A cópia de segurança verifica também que escreveu mesmo o seu arquivo: numa pasta sem permissão de
  escrita ou num disco cheio, anunciava um sucesso — e a atualização, que se apoia nela para voltar atrás, não teria
  tido nada para restaurar.
- **O erro de um campo de formulário é mostrado por baixo do campo**, e já não só ao passar por cima de um pequeno
  ícone.
- Dois erros internos já não deixam rasto no registo: um formulário enviado sem um dos seus campos, e uma consulta à base
  de dados que não devolve nada para ler — esta última fazia falhar a página.
- **A barra de depuração volta a ser mostrada**: um valor numérico fazia-a falhar desde que o código verifica os seus
  tipos de forma estrita, e a sua linha do tempo estava errada.
- **O anúncio de atualização volta a ver-se.** Tinha encolhido para um «1.2.x» sem cor na barra superior: uma limpeza
  dos estilos da administração tinha levado os seus. Recupera uma caixa por baixo do logótipo — «Atualização disponível
  · NeoFrag X.Y.Z» — e um distintivo legível na barra superior.
- **A janela de atualização** mostrava um bloco vazio e deixava um aviso no registo: diz agora que versão chega, que o
  site é salvaguardado antes de começar e volta ao seu estado anterior em caso de falha, com uma ligação para o que a
  versão traz.

### Removido

- **`NEOFRAG_LOGS_DB`**, que nada lia: as instalações novas já não o escrevem em `config/neofrag.php`. Uma instalação
  existente pode mantê-lo; não tem nenhum efeito.

## [1.2.12] — 2026-10-02

O bot do Discord passa para a **versão 0.2.0**. Para o atualizar: substitui a sua pasta pela do novo arquivo, mantendo o
ficheiro `.env`, corre `npm ci --omit=dev` e depois reinicia-o. A seguir cria uma nova chave de acesso em
*Administração → Discord* — a página pede-o — e substitui a antiga em `.env`: sem ela, faltam ao bot as permissões do
Gestor de bugs. A configuração do servidor precisa também da permissão do Discord «Gerir canais»: volta a convidar um
bot convidado com a 1.2.11 pelo botão «Convidar o bot», ou dá essa permissão ao seu cargo nas definições do servidor.

### Adicionado

- **Bot do Discord: funcionalidades que se ligam uma a uma.** *Discord → Funcionalidades* lista o que o bot sabe fazer
  (é ele próprio que o declara). Cada uma liga-se, desliga-se e configura-se a partir da administração; a alteração
  aplica-se em menos de um minuto, sem reiniciar o bot. O botão **Ressincronizar** volta a pôr tudo de acordo, e ao
  arrancar o fórum recupera o que foi escrito no Discord durante a ausência do bot.
- **Bot do Discord: põe-se em dia com o site quando volta.** O que é escrito no site enquanto o bot está desligado vai
  para o Discord quando ele volta a arrancar, dentro dos 30 dias de eventos que o site guarda. Até agora recomeçava a
  partir do último evento, e o que tinha sido escrito entretanto nunca chegava ao Discord.
- **Bot do Discord: a configuração do servidor.** A partir da administração, o bot cria no Discord uma categoria, um
  canal Fórum por cada fórum escolhido (com os seus prefixos como etiquetas) e um cargo por cada grupo escolhido, define
  ele próprio as correspondências, e reutiliza o que já existe em vez de o duplicar. Primeiro vem uma pré-visualização, e
  a última configuração pode ser anulada.
- **Bot do Discord: `/forum`.** `/forum account link` dá uma ligação de uso único que associa a tua conta do Discord à
  tua conta do site; podes também escolher passar para o teu nome o que tinhas publicado a partir do Discord.
  `/forum account unlink` desfaz a associação. Sem conta associada, `/forum visibility` deixa escolher como se aparece no
  fórum: o teu nome de utilizador do Discord, um nome anónimo ou um nome de utilizador à tua escolha, alterável a cada
  sete dias — as mensagens já publicadas acompanham.
- **Bot do Discord: prefixos do fórum ↔ etiquetas do canal Fórum**, nos dois sentidos: pôr um prefixo num tópico põe a
  etiqueta na thread, e vice-versa.
- **Bot do Discord: o Gestor de bugs num canal Fórum** (funcionalidade a ligar em *Discord → Funcionalidades*). Cada
  ticket torna-se uma thread: o seu tipo e o seu estado são as suas etiquetas, que o bot cria no canal e mantém
  atualizadas. O título, a descrição e a prioridade acompanham; a thread é arquivada quando o ticket é fechado, e um
  duplicado remete para o seu ticket original. Os comentários passam nos dois sentidos. A partir do Discord, `/bug` e
  `/idee` abrem um ticket através de uma pequena janela (título, descrição), e uma thread aberta à mão no canal torna-se
  um ticket — para um membro que associou a sua conta. Os tickets ainda abertos recebem a sua thread quando o canal é
  escolhido. O site faz fé: uma etiqueta mudada à mão no Discord volta a ser posta como diz o ticket.
- **Bot do Discord: os cargos temporários** (funcionalidade a ligar). O comando `/role`, reservado a quem pode gerir os
  cargos, dá a um membro um cargo durante um tempo definido (uma sanção, um acesso de experiência, um cargo de evento),
  retira-o ou mostra os que estão em curso. O bot retira-o no fim do prazo, mesmo depois de um reinício, e devolve-o a um
  membro que sai do servidor e volta a entrar para lhe escapar. São recusados os cargos ligados a um grupo do site, os
  cargos geridos pelo Discord e os cargos colocados ao nível do cargo mais alto do bot ou de quem os dá, ou acima. A
  página *Discord → Cargos temporários* lista-os, com «Retirar agora».
- **API**: duas novas permissões, «Ler o Bugtracker» (`bugtracker:read`) e «Escrever no Bugtracker»
  (`bugtracker:write`). Permitem a um programa ler os tickets (`GET bugtracker/tickets`, só os abertos com `open=1`) e os
  seus comentários, abrir um ticket em nome de um membro, comentá-lo em nome de um membro ou de uma conta do Discord, e
  editar ou eliminar esses comentários. O prefixo de um tópico do fórum também pode ser mudado pela API
  (`PATCH forum/topics/{id}`). O bot recebe os pontos de acesso `discord/*` das suas novas funcionalidades: cargos
  temporários (`discord/timed-roles`), configuração do servidor, associação de contas.

### Segurança

- **Um título escrito através da API já não pode injetar código nas páginas do site.** O título de um tópico criado
  pela API — por exemplo o nome de uma thread do Discord copiada pelo bot — era guardado tal como estava, enquanto o
  fórum mostra os seus títulos sem os voltar a codificar: uma etiqueta HTML nesse título corria na página do fórum. A API
  guarda agora os seus textos como o site guarda os seus, e o título do separador é sempre codificado.

### Corrigido

- **Os acentos do Gestor de bugs** eram mostrados como `r&eacute;agit` na página de um ticket aberto pelo formulário, e a
  pesquisa «Já comunicado?» não encontrava nenhuma palavra acentuada. Os textos que a API devolve — nomes de utilizador,
  títulos, descrições, comentários — estão também em texto simples: o bot do Discord copiava-os codificados. As
  pré-visualizações de partilha de ligações (Discord incluído) já não mostram os seus acentos como código HTML.
- **Gestor de bugs**: escolher o estado «Duplicado» sem um número de ticket válido mantinha o estado antigo anunciando
  «Ticket atualizado»; a mensagem diz agora o que não foi aplicado.
- **Bot do Discord: eliminar no Discord a cópia de uma mensagem do site já não apaga o original.** Quando o bot já não
  tinha essa mensagem em memória (depois de um reinício), eliminar no Discord a cópia de uma mensagem escrita no site por
  um membro com a conta associada apagava também a mensagem no site. Nesse caso, o bot só apaga agora no site as
  mensagens que vinham do Discord.
- **A wiki**: na página inicial da wiki, uma página de primeiro nível sem subpáginas aparecia como uma secção vazia;
  mostra agora as suas primeiras secções (ou o início do seu texto) e uma ligação para a ler, e a barra lateral faz dela
  uma ligação.
- **As páginas não encontradas e proibidas já não são páginas vazias.** Em todos os temas públicos, um endereço que não
  leva a lado nenhum (404) ou uma página restrita (403) só mostravam o cabeçalho e o rodapé, sem uma palavra — um defeito
  herdado do NeoFrag. Dizem agora o que se passa, nas seis línguas, com um botão para voltar à página inicial (ao painel
  na administração), e o separador do navegador diz «Página não encontrada» ou «Acesso não autorizado» em vez do nome do
  módulo.

## [1.2.11] — 2026-10-01

### Adicionado

- **O bot do Discord** (versão 0.1.0), que liga um site e o seu servidor do Discord:
  - **cargos e nomes de utilizador** — um membro que associou a sua conta do Discord recebe, no servidor, os cargos
    ligados aos seus grupos, e perde-os ao sair desses grupos; se a opção estiver marcada, a sua alcunha no servidor é o
    seu nome de utilizador do site. O site faz fé, e um cargo a que nada está ligado nunca é tocado;
  - **fórum ↔ canal Fórum do Discord**, nos dois sentidos — um tópico do site torna-se uma thread, uma thread um
    tópico; as respostas, edições e eliminações acompanham. As mensagens vindas do site aparecem com o nome e o avatar do
    seu autor; as do Discord, com a conta do membro associado ou com o seu nome de utilizador do Discord. Canal a canal:
    sincronizar tudo, ou só as threads que um moderador marca com uma reação.

  É um programa à parte (Node.js 22.9 ou posterior, numa máquina sempre ligada), incluído no seu próprio arquivo com
  cada versão. Na sua máquina só guarda o endereço do site e uma chave de acesso. Guia: «O bot do Discord» na wiki.
- **O módulo Discord** (opcional, exige o módulo API): a chave do bot, **guardada cifrada**, o servidor, o interruptor a
  funcionar / em pausa, o reinício, o estado do bot (online, em pausa, offline) e o seu **registo na língua do
  administrador**, as correspondências canal ↔ fórum e grupo ↔ cargo, e a ligação que convida o bot só com as permissões
  de que precisa — nunca «Administrador».
- **API**: a permissão `discord:bot` e os pontos de acesso `discord/*` de que o bot precisa.

## [1.2.10] — 2026-10-01

### Adicionado

- **Uma API REST** (módulo **API**, opcional), para os programas que falam com o site sem navegador — antes de mais o
  futuro bot do Discord. Um administrador cria **chaves de acesso** só com as permissões pretendidas; uma chave só é
  mostrada uma vez, o site só guarda a sua impressão, e revoga-se com um clique. Os pontos de acesso `/api/v1/…`
  devolvem JSON: o estado do site, um membro (por ID ou por conta do Discord associada), os grupos, o fórum (árvore,
  tópicos, mensagens) e um **fluxo de eventos** (novo tópico, nova mensagem, mudança de grupo…). Também **escreve** no
  fórum em nome de um membro, ou de uma conta do Discord não associada — publicada com o seu nome de utilizador do
  Discord, marcada com o logótipo do Discord. Limitada a 120 pedidos por minuto e por chave; os erros têm códigos
  estáveis. Guia: «A API REST» na wiki.
- **As minhas contas associadas** (área de membros): ver as suas contas Discord, GitHub ou Google associadas, associar
  uma, desassociar uma — exceto se for a única forma de iniciar sessão.
- **Inscrever-se com Discord** (ou GitHub, Google): uma conta que ninguém associou cria um membro, associado desde o
  início, quando as inscrições estão abertas. Até agora recebia um erro «conta desconhecida».

### Corrigido

- **Associar a sua conta do Discord iniciava sessão na conta de outro membro** quando esse Discord já estava associado a
  este último: a sessão passava para ele. A associação é agora recusada, com uma mensagem clara, e uma mesma conta
  externa já não pode ser associada a dois membros.

## [1.2.9] — 2026-10-01

### Corrigido

- **Blog: o índice de uma publicação continuava intitulado «Sommaire» nas outras línguas**, e deixava um aviso no
  registo do site. Uma mensagem da administração dos membros («Grupos do membro atualizados») tinha o mesmo defeito.
- **Blog: o distintivo da categoria estendia-se por toda a largura dos cartões** em vez de continuar a ser uma pequena
  etiqueta.

## [1.2.8] — 2026-10-01

### Adicionado

- **Blog: as séries.** Uma publicação em várias partes: cada parte mostra a lista das outras e o seu lugar («Parte 2 de
  3»), e a série tem a sua própria página. As séries gerem-se na administração do Blog, ao lado de uma nova página que
  finalmente lista as categorias.
- **Blog: uma página por autor** (as suas publicações) e **arquivos mensais** clicáveis.
- **Blog: a partilha mostra a capa.** Uma publicação partilhada numa rede social mostra a sua capa e não o logótipo do
  site; os motores de pesquisa recebem os seus dados estruturados (título, autor, data, imagem).
- **Seis widgets do Blog**: as últimas publicações com a sua miniatura, as mais lidas, a publicação em destaque, as
  categorias, as etiquetas e os arquivos.

### Corrigido

- **Fórum: a lista dos fóruns desaparecia para os membros com sessão iniciada** assim que um fórum só tinha tópicos nos
  seus subfóruns. Os visitantes não eram afetados.
- **Cancelar a subscrição da newsletter não cancelava ninguém**: a página de cancelamento falhava com um erro. O mesmo ao
  eliminar um subscritor na administração.
- **Eliminar a tua conta** marcava a conta como eliminada, e depois falhava com um erro antes de fechar as suas sessões e
  apagar os seus códigos de recuperação.
- **Desligar a autenticação de dois fatores** falhava com um erro depois de desligada, e deixava os antigos códigos de
  recuperação na base de dados.
- **Blog: uma categoria vazia não podia ser eliminada** (o Blog julgava-a sempre em uso), e a sua eliminação não estava
  protegida contra ligações falsificadas. Pede agora uma confirmação.

## [1.2.7] — 2026-10-01

### Corrigido

- **Fórum: um fórum-ligação mostra o seu próprio ícone.** O ícone escolhido no seu formulário de administração
  substitui agora o globo que mostrava sempre.

## [1.2.6] — 2026-10-01

### Adicionado

- **Fórum: a resposta «solução».** O autor de um tópico (ou um moderador) marca a resposta que o resolve. Fica em
  destaque por baixo da pergunta, assinalada no fio, e o tópico aparece como «Resolvido» na lista. Uma solução eliminada
  ou movida não deixa o tópico resolvido.
- **Fórum: os prefixos dos tópicos** («Pergunta», «Tutorial», «Importante»…), criados pelo administrador com a sua cor, e
  traduzíveis em cada língua ativa. Os membros escolhem um ao abrir o seu tópico, e a lista de um fórum pode ser filtrada
  por eles.
- **Fórum: um ícone por fórum**, escolhido na administração.

### Corrigido

- **O widget «Estatísticas» do fórum** contava também as categorias que o visitante não pode ler, e as mensagens
  eliminadas: conta agora só o que o visitante pode ir ver.
- **Os dados de demonstração do fórum** contavam a primeira mensagem como uma resposta («4 respostas» na lista para um
  tópico que tem 3).

## [1.2.5] — 2026-10-01

### Adicionado

- **Um verdadeiro Blog.** O módulo Artigos torna-se o Blog, em `/blog` (os antigos endereços `/articles/…` redirecionam
  de forma permanente). A lista abre-se com uma publicação **em destaque**, depois cartões ilustrados, filtros por
  categoria e uma coluna lateral (pesquisa, mais lidas, categorias, etiquetas, arquivos). A página de uma publicação tem
  uma imagem de capa com o seu título, uma barra de progresso da leitura, um índice que segue a secção em leitura, o
  autor, as publicações anterior e seguinte, e «Para ler a seguir». O administrador escolhe a paginação da lista e da
  página da publicação; os visitantes passam da grelha às linhas, e a sua escolha fica memorizada.
- **Gestor de bugs: a lista pode ser filtrada por tipo** («Bug», «Pedido de funcionalidade», «Pergunta», «Outro»), e
  «Novo ticket» mantém o tipo escolhido. Uma ligação para a lista filtrada (por exemplo `/bugtracker?type=bug`) mostra
  os tickets já comunicados desse tipo antes de se abrir um novo.
- **Gestor de bugs: «Já comunicado?»** Enquanto se escreve o título de um novo ticket, aparecem por baixo os tickets
  abertos que se lhe assemelham, para comentar o existente em vez de abrir um segundo. Um ticket pode também ser marcado
  como **duplicado** de outro: a sua página remete para o original.

## [1.2.4] — 2026-10-01

### Adicionado

- **Fórum: categorias e fóruns traduzíveis.** Cada um mantém o seu título predefinido e pode receber, no seu formulário
  de administração, um título por língua ativa (e, para um fórum, uma descrição). Os visitantes veem a tradução da sua
  língua, senão o título predefinido. Uma ligação funciona com o título predefinido como com cada tradução.

### Corrigido

- **Uma migração já aplicada já não bloqueia a atualização.** Aplicada à mão sem ser registada, falhava («column already
  exists») e teria anulado toda a atualização. As migrações aplicam-se agora instrução a instrução, saltando só o que já
  está feito.
- **Os módulos incluídos com o núcleo recebem finalmente as suas alterações à base de dados.** Só a atualização de um
  addon através do marketplace as aplicava: um módulo como o fórum ou o calendário recebia o seu código novo com a
  atualização do núcleo, nunca a sua base de dados. Aplicam-se agora junto com as do núcleo, pelo botão como depois de um
  envio por FTP.
- **Artigos**: as seis permissões do módulo (adicionar, editar, eliminar, e os seus equivalentes para as categorias) não
  eram verificadas em lado nenhum. Agora são, ações em massa incluídas.
- **O conteúdo agendado já não aparece antes da sua data**, nem o conteúdo enviado para a reciclagem: o widget «Últimos
  artigos», o mapa do site enviado aos motores de pesquisa, a pesquisa nas notícias, a atividade de um membro e o bloco
  «Últimos artigos» mostravam-nos. O widget Etiquetas das notícias já não mistura as línguas, e o widget das categorias
  já não conta os rascunhos.

## [1.2.3] — 2026-10-01

### Corrigido

- **A atualização finalmente atualiza-se a si própria.** O seu código — junto com o da cópia de segurança, da restauração
  e do marketplace — vivia na pasta `install/`, que a atualização nunca reescrevia: um site mantinha o do dia da sua
  instalação, e as suas correções não lhe chegavam. Vive agora no núcleo (`neofrag/installer.php`), reescrito a cada
  versão, e a Monitorização verifica a sua integridade. O resto de `install/` segue também as versões; só o bloqueio
  `install/db.txt` continua próprio do site. Num site existente, `install/` é atualizado a partir da atualização seguinte
  a esta.
- **Um site que apaga a sua pasta `install/` depois da instalação**, como muitas vezes se aconselha, mantém a sua
  atualização e o seu marketplace; as suas mensagens ficam então em francês.

## [1.2.2] — 2026-10-01

### Corrigido

- **As equipas, os jogos, as categorias, os parceiros e os prémios já não desaparecem nas outras línguas.** A
  administração guarda um título só na língua em que se escreve, e estes objetos só existiam nessa: na demonstração, três
  equipas em francês, nenhuma em inglês. O grupo de uma equipa, com as permissões que dele dependem, desaparecia com
  ela. São agora mostrados na língua pedida, senão em francês, senão na que existir. As listas que devolviam uma linha
  por tradução (recrutamentos, jogos, fluxo de atividade da administração) só devolvem agora uma. As listas de conteúdo
  (notícias, artigos, páginas) ficam na língua pedida.
- **Uma atualização aplica finalmente as alterações à base de dados do núcleo.** Até agora, só a instalação as aplicava:
  um site atualizado mantinha a sua base antiga sob código novo. Pelo botão, são aplicadas durante a atualização, que é
  anulada se falharem. Por FTP, são aplicadas na primeira página servida pelo código novo. (As alterações próprias dos
  módulos seguiram na 1.2.4.)
- **Início de sessão com Discord**: o endereço de um avatar animado (102 caracteres) ultrapassava o espaço que a base de
  dados lhe reservava (100), e o início de sessão falhava; esse espaço passa para 255 para todas as contas associadas. Um
  membro do Discord sem avatar recebia uma imagem partida.

### Alterado

- **O envio de e-mails passa para o PHPMailer 7** (7.1.1). A sua única alteração incompatível diz respeito às classes que
  o estendem, e o NeoFrag não tem nenhuma. Testado com um envio real com as definições de um site, por SMTP como através
  de `mail()`.

## [1.2.1] — 2026-10-01

### Segurança

- **Duas vulnerabilidades corrigidas na biblioteca Markdown** (`league/commonmark` 2.10.3): uma negação de serviço através
  de tabelas preparadas para abrandar o servidor (gravidade alta, GHSA-3q6v-r5mr-hxv8), e uma forma de contornar o filtro
  que retira o HTML não permitido (gravidade moderada, GHSA-97jj-33gv-5xf9). Tornadas públicas a 21 de setembro de
  2026. A biblioteca de limpeza de HTML (`ezyang/htmlpurifier` 4.19.1) passa também para a sua última versão.

### Corrigido

- **A Monitorização já não assinala ficheiros presentes como «em falta».** Desde a saída da 1.2.0, comparava o site com a
  lista oficial de ficheiros da versão, mas excluía as fontes Sass (ficheiros de estilo antes da compilação) só do lado
  do site: quatro ficheiros assinalados em falta por erro, e um estado de saúde «O navio está a afundar!».

### Alterado

- **Uma atualização do núcleo é registada no registo de auditoria**: quem a lançou, quando, de que versão para que
  versão, quantos ficheiros foram escritos e quantos ficheiros antigos foram retirados. Até agora era escrita no registo
  de erros, onde parecia uma anomalia.

## [1.2.0] — 2026-09-23

### Adicionado

- **As atualizações do núcleo, com um só clique** (2026-09-23). A partir desta versão, **Administração →
  Monitorização** assinala as novas versões do NeoFrag Reborn e instala-as: cópia de segurança do site, verificação da
  impressão do pacote, aplicação do pacote, atualização da base de dados, e regresso à cópia de segurança se algo
  falhar. É a primeira versão publicada por este caminho; foi testada do princípio ao fim num site novo antes da
  publicação. Um site na 1.1.0 ainda não tem este botão: passa para a 1.2.0 à mão, substituindo os seus ficheiros. Para
  os mantenedores: `tools/build-release.php` produz os três ficheiros a publicar juntos
  (`neofrag-reborn-update-<v>.zip`, `version.json`, `checksum.json`); o procedimento está em
  `docs/guide/marketplace.md`.

- **Os anúncios no Discord** (2026-09-23). Um webhook que aponta para um canal do Discord recebe agora uma verdadeira
  mensagem, com título, ligação e cor: nova notícia, novo artigo, novo membro, novo comentário, novo tópico do fórum. Até
  agora o Discord recusava o que o módulo enviava, embora a sua descrição prometesse o Discord.

- **«Fulano está em direto»** (2026-09-23). Quando um canal do Twitch ou do YouTube seguido pelo widget «Estado ao vivo»
  entra em direto, os webhooks subscritos ao evento «Canal em direto» anunciam-no, uma só vez por emissão, com a
  miniatura da emissão.

- **A demonstração fala também inglês no seu conteúdo** (2026-09-23): as suas notícias, artigos, páginas, categorias e
  galerias têm uma versão inglesa, que mostra o multilinguismo.

- **Todo o produto fala as seis línguas** (2026-09-23). A administração, os módulos, os widgets, os temas, as
  definições, os botões, as mensagens e os scripts: perto de 800 cadeias estavam escritas diretamente em francês e
  ficavam assim num site em inglês, alemão, espanhol, italiano ou português. Passam agora todas pelas traduções, nas seis
  línguas. Em particular:

  - **O assistente de instalação está traduzido**, com um seletor de língua; segue no início a língua do navegador. O
    instalador da linha de comandos aceita `--lang=en`, ou a língua do terminal.
  - **O marketplace** mostra o nome e a descrição de cada addon na língua do site.
  - **Os países** (perfil, adversários) são mostrados na língua do site.
  - **Os cargos e os modelos de e-mail incluídos** são mostrados traduzidos; um nome mudado pelo administrador fica tal
    como o administrador o escreveu.
  - **A moderação** mostra o estado, o motivo e o tipo de uma denúncia, e o tipo de uma sanção, escritos por extenso —
    mostrava os códigos (`pending`, `forum_message`, `ban_temp`).
  - **Um grupo criado numa língua já não desaparece** quando o site é mostrado noutra — saía da lista, e as suas
    permissões com ele.
  - O tema da administração declarava-se escrito em inglês: num site em inglês, as suas cadeias francesas nunca eram
    traduzidas. Está corrigido, tal como os scripts dos módulos, que procuravam as suas traduções no sítio errado.
  - A confirmação da eliminação da conta pede para escrever a palavra na língua do site («ELIMINAR» em português), e já
    não «SUPPRIMER» em todo o lado.

  Uma verificação, `check-textes-en-dur`, recusa agora na CI qualquer texto da interface escrito no código.

- **Os navegadores dos visitantes podem guardar os ficheiros do site, se o pedires** (2026-09-22). O manifesto já tornava
  o site instalável; o site pode agora guardar as suas imagens, estilos e scripts nos navegadores dos visitantes.
  **Desligado por predefinição**, liga-se em **Administração → Definições → Preferências gerais** («Aplicação
  instalável»).

  **As páginas, essas, nunca são guardadas.** Uma página guardada em memória sobreviveria a uma nova implementação: a
  quem já tivesse vindo seria servido um site com dias, sem que nada o indicasse. Uma alteração continua assim visível de
  imediato.

  **Desmarcar desinstala mesmo.** É a parte delicada desta técnica: um componente deste tipo, uma vez instalado num
  navegador, fica lá mesmo que o ficheiro seja retirado do servidor. O interruptor não retira, portanto, nada — serve
  uma versão que se desinstala sozinha na visita seguinte. Por isso a funcionalidade está desligada por predefinição, e
  por isso uma atualização do núcleo nunca a liga sozinha.

- **O site pode ser instalado como aplicação** (2026-09-21). `/manifest.webmanifest` é gerado pelo próprio site — nome,
  descrição, cor do tema, endereço inicial e favicon vêm das definições — em vez de ser colocado como ficheiro, que
  estaria errado em qualquer outro lado.

- **As pastas sensíveis também estão fechadas com o Apache** (2026-09-21). Os `.htaccess` do pacote protegiam **menos**
  do que a configuração do Caddy incluída: com o Apache, o instalador continuava acessível depois de o site estar
  instalado (só a sua própria verificação o impedia de correr), e num site instalado a partir do repositório git,
  `docs/`, `tools/` e `tests/` também estavam acessíveis — a documentação lia-se em texto simples. `cache/`, `config/`,
  `install/`, `tools/`, `tests/` e `docs/` têm agora cada uma a sua proteção, a raiz bloqueia também os ficheiros `.neon`
  e `.md`, e `tools/check-htaccess.php` volta a ler a lista em cada execução, com o motivo de cada linha.

- **Um endereço desconhecido já não escreve um erro no registo do site** (2026-09-21). O módulo `pages`, que recebe em
  último recurso os endereços que mais nada reconhece, escrevia uma linha de **erro** para cada um — cada robô que
  sondava acrescentava algumas. Para os programadores de addons: um controlador pode agora declarar uma recusa como
  corrente (`Module_Checker::refus_ordinaire()`); o diagnóstico continua a ser mostrado no ecrã em modo de depuração. A
  verificação `check-journal` recusa agora qualquer linha que o próprio produto escreva no registo de erros.

- **Duas verificações fazem respeitar as regras do repositório** (2026-09-21), para os contribuidores.
  `tools/check-tools.php` verifica que cada ferramenta segue as regras comuns: cabeçalho descritivo, recurso à biblioteca
  comum em vez de código copiado, veredicto e códigos de saída padrão, porta reservada, catálogo atualizado.
  `tools/check-docs.php` controla a documentação: números exatos, ligações e âncoras válidas, nenhum documento órfão,
  nenhuma menção de uma ferramenta que já não existe, nenhuma passagem copiada de um documento para outro, documentos que
  continuam legíveis. Ambas correm na integração contínua.

- **Oito addons novos, todos opcionais** (2026-09-20). Uma atualização não os instala: num site existente, adicionam-se
  a partir de **Administração → Temas & addons**, e retiram-se da mesma forma. Uma instalação nova inclui-os com o perfil
  *Completo*, oferecido por predefinição, mas não com os outros perfis.

  | Addon | O que faz |
  |---|---|
  | **Efeito sazonal** (widget) | Neve, confetes ou folhas por todo o ecrã, durante um intervalo de datas. As datas escrevem-se `MM-DD` **sem ano** — a estação volta sozinha — e podem passar pelo Ano Novo (`de 12-15 a 01-06`). Fora de época, a página não recebe **absolutamente nada**: nem imagem, nem script. A definição do sistema que pede menos movimento (`prefers-reduced-motion`) é respeitada, e a animação para quando o separador passa para segundo plano. |
  | **Leitor de feeds** (widget) | Os últimos artigos de um feed RSS ou Atom externo. **Uma página quase nunca espera por um site alheio**: lê uma cache, e serve até conteúdo desatualizado em vez de fazer esperar os visitantes; a atualização faz-se na tarefa agendada. Só a primeiríssima apresentação, com a cache vazia, espera pelo feed, três segundos no máximo. Um feed em baixo fica de lado dez minutos em vez de ser tentado de novo a cada visita. |
  | **Citações** (módulo) | Uma coleção por categorias, com autor e fonte. |
  | **Receitas** (módulo) | Ingredientes e passos introduzidos um por linha, tempos e número de doses, marcação `schema.org/Recipe` que os motores de pesquisa leem. |
  | **Glossário** (módulo) | Um léxico ordenado por letra. «Éclaireur» fica no E, «Æther» no A, «1v1» no #. A letra é calculada, nunca introduzida. |
  | **Mapa de locais** (módulo) | Locais num mapa OpenStreetMap, com endereço, descrição e ligação. A biblioteca do mapa é **alojada pelo site**; só os mosaicos do mapa de fundo vêm do OpenStreetMap. **A página continua útil sem JavaScript** — a lista, os endereços e as ligações estão lá. |
  | **Rádio web** (módulo) | Um leitor de emissão, o que está no ar, e a programação da semana. Um programa pode passar a meia-noite: «sábado 22:00 → 02:00» continua a ser o programa de sábado. |
  | **Área de testes** (módulo) | Reservado aos membros: experimenta-se lá uma formatação e vê-se a apresentação **exata** do site. Sobretudo, mostra **o que o site retirou** — é a única página que responde a «porque é que a minha tabela desapareceu?». |

  Os três módulos de conteúdo (citações, receitas, glossário) partilham o padrão da FAQ: categorias, pesquisa, filtro,
  ordenação e rascunhos.

- **Escolher o conteúdo na instalação.** O instalador web oferece agora um **perfil do site** entre os requisitos e a
  base de dados: *Completo*, *Gaming / eSports*, *Comunidade* ou *Só o núcleo*. Os módulos podem sempre ser desmarcados
  um a um, e os de que um módulo precisa são adicionados automaticamente (o palmarés precisa das equipas), com uma
  mensagem. Sem JavaScript, o formulário continua a funcionar — o servidor só mantém o que pertence ao perfil escolhido.

  **Nenhuma lista de addons é escrita à mão**: os perfis são construídos a partir das declarações `presets` de cada
  addon. É a diferença para a tentativa de junho de 2026, abandonada porque uma instalação aligeirada acabava num erro do
  servidor (500): módulos do núcleo consultavam as tabelas de módulos opcionais sem verificar que existiam. Essas
  verificações existem agora e são controladas em cada execução da CI.

- **Cada módulo, widget e tema incluído declara o seu papel**: no seu `__info()`, diz se pertence ao núcleo, de que
  perfis de instalação faz parte, e de que módulos precisa. A pertença ao núcleo era até agora um efeito secundário da
  presença no pacote — foi assim que `emojis`, `files` e `webhooks` lá foram parar sem que nenhuma decisão fosse tomada.

- **Três novas salvaguardas de integração contínua**:

  | Verificação | O que recusa |
  |---|---|
  | `check-addon-declarations.php` | um addon que não declara nada, uma dependência inexistente, **um addon do núcleo que depende de um opcional**, um addon não distribuível publicado no catálogo, uma declaração em desacordo com `seed.sql` |
  | `check-addon-coupling.php` | um acoplamento **fatal** (tabela, classe) nem declarado nem anotado; um ciclo duro. Lido no código com o **tokenizador do PHP**, não com expressões regulares — portanto sem falsos positivos nos comentários |
  | `check-install-profiles.php` | um perfil que não arranca. **Instala mesmo** numa base de dados descartável, serve o site e falha ao menor 5xx — também nas rotas dos módulos ausentes, que têm de devolver um 404 limpo |

- **Encontrar um membro a partir da barra de pesquisa.** A pesquisa do site só encontrava conteúdo — fórum, notícias,
  páginas. Procurar alguém obrigava a abrir o diretório de membros e percorrê-lo, embora seja uma das coisas que mais se
  procuram num site de comunidade. Os membros aparecem agora na pesquisa global **e** nas sugestões instantâneas, por
  nome de utilizador, nome próprio ou apelido.

  Nada de novo é exposto pelo caminho: estes três campos já são mostrados no perfil público de cada membro, e a pesquisa
  aplica exatamente o mesmo filtro que o diretório — uma conta eliminada não aparece.

- **Gerir uma série de eventos de uma só vez.** Criar um evento recorrente já gerava todas as suas ocorrências com um
  clique — voltar a elas obrigava depois a abri-las uma a uma.

  Agora, uma ocorrência de uma série tem um botão **«Eliminar toda a série»**, cuja confirmação indica quantas
  ocorrências vão desaparecer, e o formulário de edição oferece **«Aplicar a todas as ocorrências da série»**. O título,
  o tipo, as descrições, o local, a imagem e o estado de publicação são copiados para cada uma; **as datas nunca** — são
  elas que distinguem uma sessão da seguinte.

- **Desfazer uma atualização e restaurar uma cópia de segurança.** O CMS já fazia uma cópia de segurança completa —
  ficheiros e base de dados — mesmo antes de cada atualização do núcleo. Não a sabia voltar a usar: a rede de segurança
  estava lá, mas ninguém sabia cair nela.

  Agora, se a colocação dos ficheiros ou uma migração falhar, **o site é automaticamente reposto no estado em que
  estava**; a mensagem diz ao mesmo tempo o que falhou e se o regresso atrás resultou. Cada cópia de segurança da lista
  tem também um botão **Restaurar**, para os casos em que o estrago não vem de uma atualização — um módulo de terceiros,
  uma ação de que nos arrependemos. A confirmação indica o que se vai perder.

  O que a restauração **não** toca, de propósito: a configuração do site (repor credenciais da base de dados
  desatualizadas cortaria o site da sua própria base), os registos (são a memória do incidente que se está a reparar) e
  a cache (esvaziada para ser reconstruída, em vez de reposta no estado de outra versão).

  Pelo caminho, a cópia de segurança inclui agora `vendor/`, que o pacote de atualização traz e que ela não incluía: sem
  ele, um regresso atrás teria posto o código antigo por cima das novas dependências.

- **Responder a um comentário.** O botão «Responder», desativado no código original, funciona agora: a resposta é
  mostrada indentada por baixo do comentário a que responde. Só se pode responder a um comentário de primeiro nível do
  mesmo conteúdo que não tenha sido eliminado (um nível de respostas); o servidor verifica-o.
- **Para os criadores de temas: zonas chamadas pelo nome** (ideia tirada do HiddenCMS). Uma vista chama agora uma zona
  pelo seu nome — `$this->output->region('content')` — em vez de pelo seu número — `zone(2)`. O tema declara a
  correspondência entre nomes e zonas no seu `__info()` (`regions`). A antiga chamada `zone()` continua a funcionar, e
  um tema sem esta declaração fica inalterado. Os cinco temas públicos (Nebula, Forge, Granite, Blockcraft, Extend) usam
  os nomes.
- **Instalar pela linha de comandos** (`install/cli.php`, ideia tirada do HiddenCMS): uma alternativa ao assistente
  web, prática para instalar um servidor de forma reprodutível ou automatizada. As suas opções cobrem a base de dados
  (`--db-*`, `--create-db`), a conta de administrador (`--admin-*`), o nome e o endereço do site (`--site-name`,
  `--site-url`), a língua (`--lang`), os dados de demonstração (`--demo`) e a execução (`--force`, `--yes`,
  `--dry-run`, `--no-lock`). Sem opções, faz as perguntas uma a uma e esconde a palavra-passe enquanto é escrita;
  `--admin-pass-env` lê a palavra-passe de uma variável de ambiente para que não apareça na lista de processos. Usa o
  mesmo código que o assistente web e instala todos os addons incluídos, como o perfil *Completo*.

- **Um lançador para toda a bateria**: `php tools/check-all.php`. As verificações não tinham um ponto de entrada comum —
  a CI chama-as uma a uma, e localmente cada um escrevia o seu próprio ciclo à mão, nunca o mesmo. O lançador descobre as
  verificações presentes (uma verificação nova junta-se sem que ninguém lhe toque), corre primeiro `composer audit`,
  limita a duração de cada verificação, e diz no fim **o que não correu**: os testes no navegador (`--navigateur`) e as
  verificações com um alvo explícito, entre elas `check-restauration`, que danifica o site de propósito.

- **Cada addon mostra o seu aspeto** (2026-09-22). A página «Temas & addons» da administração e o marketplace mostram
  uma miniatura: uma verdadeira captura do módulo, widget, tema ou língua tal como corre na demonstração. Os 61 addons do
  marketplace têm uma, em 960×600 para os módulos e os widgets. Para lá chegar, foram instalados na demonstração seis
  módulos e dois widgets com conteúdo de exemplo — um glossário, citações, receitas, um mapa de locais e uma programação
  de rádio web —, e os widgets que não mostram nada sem definições foram capturados com definições de exemplo. O que não
  tem nada para mostrar mantém o seu ícone, como os conectores Discord, GitHub e Google, cujo ícone é o seu logótipo. A
  demonstração tem também finalmente a identidade da sua comunidade — nome, tipo, data de criação e descrição —, que
  tinha ficado vazia.

- **Dez addons têm finalmente uma descrição** (2026-09-22): as seis línguas e os quatro addons de início de sessão
  externo (o de base e os seus conectores Discord, GitHub e Google). É mostrada na língua do site.

- **Campos de perfil definidos pelo administrador** (2026-09-20). O perfil de um membro só tinha campos fixos.
  **Administração → Utilizadores → Campos do perfil** permite acrescentar os seus — nome no jogo, plataforma,
  posição… —, entre oito tipos de campo. Os membros preenchem-nos no painel «Informações adicionais» do seu perfil. Um
  campo é privado por predefinição; tornado público, é mostrado na página do membro. Os valores estão incluídos na
  exportação dos dados pessoais do membro e são eliminados com a sua conta.

- **O tipo de letra do site escolhe-se na administração** (2026-09-20). **Administração → Definições → Preferências
  gerais → Tipo de letra do site** oferece doze tipos de letra, que substituem o de cada tema. Por predefinição, «Tipo
  de letra do tema» mantém o tipo de letra próprio de cada tema e não chama nenhum serviço externo; os doze tipos de
  letra oferecidos são servidos pelo Google Fonts.

- **O histórico de inícios de sessão já não é guardado para sempre** (2026-09-20). Regista o endereço IP, o navegador e a
  data de cada início de sessão, e nada o apagava. É agora purgado para lá de 395 dias (treze meses), uma duração
  definida em **Administração → Definições → Preferências gerais**; 0 desliga a purga.

- **Lembretes dos eventos seguidos do calendário** (2026-09-20). Um membro que segue um evento do calendário recebe um
  lembrete antes do seu início, como os participantes de um evento do módulo Eventos. O prazo define-se na configuração
  do calendário («Lembrete antes de um evento seguido», 24 horas por predefinição, 0 para desligar).

- **O pacote de atualização diz o que protege e o que retira** (2026-09-20). Tem um ficheiro `nf-manifest.json`: as
  pastas do site em que uma atualização nunca escreve (`config/`, `install/`, `upload/`, `logs/`, `backups/`, `cache/`),
  e os ficheiros que a versão retira. Até agora a atualização deduzia sozinha o que apagar; um pacote sem este ficheiro
  continua a ser aceite.

### Alterado

- **Toda a interface fala Bootstrap 5** (2026-09-23). Restavam cerca de 470 usos de classes do Bootstrap 3 e 4, mantidos a
  funcionar por uma folha de estilo de compatibilidade: campos de formulário, distintivos de cor, botões de fechar,
  botões em toda a largura, blocos «avatar e texto», grupos de entrada, caixas de verificação, grelhas de formulário. Toda
  a marcação foi migrada para os seus equivalentes do Bootstrap 5; o que é próprio do produto tem agora um nome próprio do
  produto. Uma verificação recusa qualquer nome antigo que volte, mesmo redefinido. O que se vê:
  - as **listas pendentes** recuperam a sua seta — uma centena, na administração e no site, pareciam campos de texto;
  - os **distintivos de cor** (estados, grupos, cargos, sanções) são legíveis em todos os temas, em claro como em
    escuro: o seu texto chumbava na verificação de contraste;
  - os grupos **«Moderador» e «Moderador sénior»** são mostrados: o seu nome estava escrito a branco sobre fundo branco;
  - os **botões de fechar** das janelas têm uma zona de clique suficiente nos telemóveis.

- **O JavaScript é revisto por um linter** (2026-09-22). Setenta e um ficheiros do projeto escritos ao longo dos anos, sem
  que nada os verificasse para lá da sua sintaxe e da ausência de jQuery. O ESLint vê o que a sintaxe não diz: uma
  variável global criada por esquecer um `var`, um `eval` disfarçado, código depois de um `return`, um `innerHTML`
  alimentado por um valor calculado. Nenhum pacote npm é implementado: é uma ferramenta de desenvolvimento, tal como a
  análise estática do PHP.

  As regras são escolhidas por um único critério — já ter mordido aqui. É a família de «`$` is not defined», que tinha
  posto fora de serviço a ordenação das tabelas em toda a administração durante três meses. A desordem existente (usos de
  `innerHTML` a sanear) é **congelada tal como está** em vez de reescrita de uma vez: o seu número já não pode subir.

- **A linha de base do PHPStan regenera-se com um comando, e a classe `Config` do núcleo está anotada** (2026-09-21).
  Três erros novos levaram a reabrir `phpstan-baseline.neon`: as entradas acrescentadas à mão estavam escritas de forma
  diferente das do próprio PHPStan, e perto de trezentos blocos já não correspondiam a nada — toda uma família «Function
  NeoFrag not found», morta desde que o ficheiro de arranque declara a função. A configuração vive em
  `phpstan-base.neon`, `phpstan.neon` só lhe acrescenta a linha de base, e `composer stan:baseline` regenera-a por
  inteiro. `Config` anota agora a língua atual, a lista das línguas e as definições do núcleo que os módulos leem: estes
  acessos são analisados em vez de ficarem congelados.

- **As ferramentas de `tools/` refeitas sobre uma biblioteca comum** (2026-09-21). Umas sessenta ferramentas, escritas
  cada uma à sua maneira, copiavam a mesma canalização: arrancar um servidor PHP, abrir uma sessão de administrador,
  lançar o Chrome, ler `config/db.php`. Uma lição aprendida numa não chegava às outras. `tools/lib/` tem agora esta base —
  ligação, sessão, servidor, navegador, percurso do repositório, SQL, opções, veredictos —, e cada ferramenta reduz-se à
  sua própria lógica. Restam cinquenta e duas, sem perda de funções: `check-js-syntax` e `check-js-jquery` formam
  `check-js-sources`; `check-docs-counts` e `check-docs-liens` formam `check-docs`; `check-lang-args` junta-se a
  `check-langs`; `seed-wiki-docs` e `dump-wiki` formam `wiki-docs`; `smoke-test` passa a `check-smoke`, como qualquer
  verificação; `addons-manifest` e `table-map`, que são dados, vivem na biblioteca. Cada ferramenta tem agora a sua
  própria porta reservada (várias partilhavam uma), três códigos de saída que distinguem «nada a assinalar», «recusado» e
  «não conseguiu julgar», e um cabeçalho a partir do qual se gera o catálogo de `tools/README.md`. `bs5-codemod`, a
  ferramenta da migração do Bootstrap 4 para o 5 de junho, foi retirada: `check-classes-bs4` cobre tudo o que ela
  verificava, incluindo os pontos de quebra dos utilitários direcionais.

- **`declare(strict_types=1)` em todo o perímetro útil** (2026-09-21): **1365 ficheiros** contra 78 na véspera. O
  trabalho avançava em pequenos lotes desde junho; está concluído. Os 209 ficheiros restantes são os modelos
  `views/**.tpl.php`, onde a declaração seria sintaticamente válida e não protegeria nada.

  A conversão foi feita por ordem de **risco decrescente** — o número de chamadas a funções nativas por ficheiro —, com
  o conjunto de testes corrido entre cada lote.

  Uma consequência de fundo, própria deste produto: o PHP em modo estrito **recusa `__toString()` para as funções
  internas**. Ora, `lang()` devolve um objeto de tradução *diferido*, resolvido no momento da apresentação, quando a
  língua é finalmente conhecida, e as datas também são objetos. A barra da administração, a página de cada notícia e
  vários ecrãs falhavam, portanto, com um erro fatal — módulo fora de combate, página truncada, sem uma palavra. A
  conversão faz-se agora no **ponto de escape** (`htmlspecialchars`), ou seja, onde o valor tem de se tornar uma cadeia
  por definição: perto de 500 sítios em 86 ficheiros.

  A vaga exigiu também onze conversões explícitas, três delas em caminhos percorridos em cada página — a ordem de
  apresentação das línguas e dos grupos, lida no início de cada sessão, e o cálculo do token que protege cada formulário
  contra os pedidos falsificados (CSRF): sem elas, um site com duas línguas ativas teria devolvido um erro 500 em todas as
  suas páginas.

- **O registo diz agora ONDE, não só o quê** (2026-09-21). Quando um módulo lança uma exceção, a sua apresentação é
  abandonada e a página mostrada truncada — em silêncio. A linha do registo dava a mensagem sem nunca nomear um
  ficheiro. Nomeia agora o primeiro sítio do código próprio do produto na pilha de chamadas. Encontrar a origem dos erros
  acima passou de uma investigação a uma simples leitura.

- **O compilador SCSS passa para a 2.1** (2026-09-20). No PHP 8.5, cada compilação de estilos escrevia **dez avisos de
  obsolescência** no registo; já não resta nenhum. A 2.x minimiza um pouco mais (`.25rem` em vez de `0.25rem`, `#ccc` em
  vez de `#CCCCCC`), o que muda três folhas de estilo — as dos módulos addons e palmarés e a da página de manutenção. A
  equivalência não foi deduzida do texto: os dois conjuntos de CSS foram dados ao motor do Chrome, que vê neles
  exatamente as mesmas regras, propriedades e valores calculados.

- **A documentação foi relida por inteiro face ao código** (2026-09-17). Os guias de instalação, de conceitos e de
  desenvolvimento (criar um módulo, um widget, um tema, o framework) datavam de junho e descreviam o CMS de antes: uma
  instalação sem escolha de perfil, um tema que carregava o jQuery, grelhas do Bootstrap 4. Dizem agora o que o produto
  faz — perfis de instalação, declarações dos addons, regiões com nome, front end sem jQuery sob uma política de
  segurança estrita, definições de widget com valores de recurso, vocabulário de cores partilhado. O README, o guia de
  contribuição, a política de segurança e a referência técnica estão alinhados da mesma forma.

- **O que um addon permite lê-se na sua declaração**, já não em listas de nomes escritas no código. Três delas decidiam
  a sorte dos addons, e as três estavam erradas: a lista dos widgets protegia sete nomes que não são widgets; a dos temas
  protegia um tema `default` inexistente **deixando `nebula`, o único tema público do núcleo, eliminável** assim que
  estivesse inativo; a dos módulos duplicava, em desacordo, o que `__info()` já dizia. Verificado addon a addon face à
  lógica antiga: nenhuma mudança no que pode ser desativado, nenhuma mudança de estado ativo, e **39 addons passam a não
  poder ser eliminados** — que é o objetivo da correção.

- **A Reciclagem já não conhece os outros módulos.** Tinha escrita no código a lista das tabelas, chaves primárias e
  métodos de restauro de `news`, `articles`, `gallery`, `comments` e `forum`: não podia, portanto, pertencer ao núcleo
  sem arrastar consigo quatro módulos opcionais — `comments` pertence ele próprio ao núcleo. Cada módulo declara agora os
  seus tipos restauráveis; o núcleo recolhe-os. A mesma inversão para os descritores de conteúdo (reações, subscrições,
  revisões).

- **A lista dos addons do núcleo e opcionais já não é mantida à mão.** `tools/lib/addons-manifest.php`, uma tabela
  escrita à mão e congelada em junho, é agora deduzida das declarações. As duas já tinham divergido: `emojis` estava
  listado como núcleo quando se declara à la carte — podia portanto ser desmarcado na instalação mas faltava no catálogo,
  e era por isso **impossível de reinstalar**. Catálogo: 52 → **53 addons** nesse dia (61 à saída da 1.2.0).

- **O instalador, refeito visualmente**: paginação em duas colunas, tokens de estilo tirados do `nebula` (azul-marinho e
  verde-azulado, destaque `#2dd4bf`), degradê retirado de trás do texto, logótipo posto num distintivo.

### Corrigido

- **A cópia de segurança da Monitorização volta a funcionar** (2026-09-23). Desde 21 de setembro, parava na primeiríssima
  linha da base de dados: o botão «Iniciar backup», e a atualização do núcleo, que começa por uma cópia de segurança,
  falhavam em todos os sites. Encontrado ao testar a atualização antes de a publicar.

- **Mudar de língua numa notícia traduzida já não leva a uma página não encontrada** (2026-09-23). O seletor de língua
  mantém o endereço, e com ele o título na língua anterior: a versão traduzida recusava-o. Redireciona agora para o seu
  próprio endereço.

- **Uma página traduzida é mostrada na língua certa** (2026-09-23). As páginas públicas eram servidas na versão que a base
  de dados devolvia primeiro, fosse qual fosse a língua do visitante.

- **O tema escolhido por um visitante já não se estende a outro site do mesmo domínio** (2026-09-23). A escolha era
  memorizada para todo o domínio: um visitante que passava um site para o Forge via também no Forge outro site instalado
  no mesmo domínio. A escolha é agora memorizada **para cada site separadamente**, e uma nova definição, **Preferências
  gerais → Escolha do tema**, permite desligá-la: o menu desaparece e o tema predefinido aplica-se a todos.

- **A demonstração volta a repor-se** (2026-09-23). Um erro no seu conjunto de dados fazia falhar a reposição de quarto em
  quarto de hora desde a véspera: o que os visitantes lá mudavam ficava. Uma verificação recusa agora este defeito antes
  de qualquer coisa ir para o ar, e uma reposição falhada é escrita no registo em vez de passar despercebida.

- **A conta de emergência da demonstração já não aparece em lado nenhum** (2026-09-23): nem na lista de membros, nem na
  pesquisa, nem entre os membros online, nem na administração; a sua página responde «não encontrado».

- **O site num telemóvel** (2026-09-23), a partir de capturas tiradas num telemóvel:

  - **O menu está sempre ao alcance.** No tema Nebula, desaparecia abaixo dos 860 píxeis de largura sem nenhum botão para
    o reabrir: agora abre-o um botão «hambúrguer». Nos outros temas, um menu demasiado longo para uma linha recolhe-se
    atrás de um botão «Menu», em vez de mudar de linha até encher o ecrã; um menu curto continua aberto.
  - **Os blocos empilhados já não se tocam**: a área de membros estava colada ao bloco de cima.
  - **O texto já não se cola aos avatares dos membros** (comentários, conversas, autor de uma notícia, candidaturas,
    perfil).
  - **Os pontos do diaporama** são verdadeiros botões, centrados por baixo da imagem, e continuam **claros** nos temas
    escuros, como as setas e a legenda: o Bootstrap tornava-os pretos.
  - **A pesquisa do tema Extend** mantém o seu botão ao lado do campo.
  - **As fotos de um álbum** preenchem o seu cartão.
  - O cabeçalho dos comentários mostra o título antes do contador, que antes o precedia.
  - As datas abreviadas são escritas na língua do site («30 août 2026», «Sonntag»), e já não em inglês; a linha do autor
    de uma notícia recupera um tamanho de texto normal.

- **A página de uma foto da galeria existe** (2026-09-23). O widget «Imagem aleatória», o diaporama da galeria e a ligação
  de um comentário escrito sobre uma foto levavam todos a uma página não encontrada: nunca tinha sido declarada. Mostra a
  foto, a sua descrição, o caminho de volta ao álbum e os comentários. Uma foto segue também as regras do seu álbum: já
  não há acesso pelo seu endereço quando o álbum está na reciclagem ou ainda não foi publicado.

- **Os últimos restos do Bootstrap 3 e 4** (2026-09-23), que os seus nomes de classe não denunciavam: a estrutura do
  diaporama, as cruzes de fechar que mostravam um «×» por cima do ícone, dois acordeões (FAQ, ligações de navegação)
  ainda construídos à maneira do Bootstrap 4 (o da FAQ abria-se, mas sem o aspeto nem a seta do componente), campos que se
  envolviam num grupo vazio, a seta das dicas que ficava branca por baixo de uma dica escura, e duas regras do widget dos
  parceiros. `check-classes-bs4` lê agora também a ESTRUTURA dos componentes e os estilos escritos nas vistas.

- **Páginas de erro que não o eram** (2026-09-23). Na moderação, e na gestão dos cargos, dos modelos de e-mail, dos
  membros e do diaporama, um endereço que apontava para um elemento retirado devia responder «página não encontrada»:
  chamava uma função que não existe, escrevia um aviso no registo, e a página da moderação aparecia vazia. Encontrado ao
  ensinar à análise estática as funções «mágicas» do framework — o que tirou 455 ocorrências da sua lista de exceções.

- **O botão dos e-mails enviados aos membros é legível** (2026-09-23): a validação da conta, a palavra-passe perdida e os
  outros modelos tinham um botão branco sobre turquesa claro (contraste de 2,3:1). A correção aplica-se aos modelos
  incluídos e aos já instalados, exceto os que um administrador personalizou.

- **Últimos retoques de apresentação** (2026-09-23): as ligações da wiki na tonalidade legível do tema; os botões de
  «sucesso» nos temas escuros; o estado de uma candidatura, cuja caixa não tinha fundo (classes de um antigo tema de
  administração); as redes sociais nos cartões de membro; os títulos truncados na administração; um visitante que segue a
  ligação para participar num evento recebe «Acesso não autorizado» em vez de uma página não encontrada.

- **O texto é legível em todo o lado, em modo claro como em modo escuro** (2026-09-23). As ligações e os botões da
  administração, os alertas, as datas e os cabeçalhos de tabela dos temas, as categorias das notícias, a página do
  marketplace nos temas claros, o botão «Aceitar tudo» do banner de cookies, os distintivos de reputação e de tipo de
  evento: todos atingem agora o contraste mínimo recomendado (4,5:1). Em modo escuro, a administração tem as suas
  próprias cores de estado, e o painel «Saúde do site» e a zona de envio de imagens da galeria seguem o tema.

- **A administração num telemóvel** (2026-09-23). A barra superior passa para duas linhas — o trilho de navegação
  completo, e depois os botões — em vez de truncar o título da página; os alvos pequenos (ícone de início, contadores)
  são suficientemente grandes para um dedo; o cabeçalho das categorias do fórum já não desliza para baixo dos seus botões.

- **O fórum num telemóvel** (2026-09-23): num tópico, o autor passa para cima da mensagem em vez de lhe deixar só 200 px de
  largura, e os botões de uma mensagem já não tapam a sua data.

- **Os plurais traduzidos** (2026-09-23). 47 cadeias tinham perdido a sua forma plural na tradução: um site em inglês
  mostrava «3 topic», «5 image», «2 year». O contador das categorias do fórum, escrito em francês em todas as línguas,
  está agora traduzido.

- **A documentação do site está atualizada** (2026-09-23). As dez páginas da wiki incluídas com o produto e as da
  demonstração estavam uma semana atrasadas em relação aos guias; «Criar um widget» ainda ensinava uma classe do
  Bootstrap 4. Uma verificação compara-as agora com os guias.

- **O formulário de resposta do fórum cabe num telemóvel** (2026-09-23): a lista dos tipos de anexo permitidos, escrita
  sem espaços, formava uma só palavra demasiado larga para o ecrã.

- **Todo o site passado a pente fino, em cada tema, cada modo e cada largura** (2026-09-23). Uma verificação automática
  mostra agora cada página da administração no seu tema e cada página pública em cada tema público, em claro e em escuro,
  com sessão iniciada como administrador e como visitante, em oito larguras de 360 a 2560 px, com e sem conteúdo. Esta
  primeira passagem corrigiu:
  - **ícones partidos** nas listas de categorias da galeria, dos jogos, das equipas e das notícias, quando uma categoria
    não tinha ícone;
  - o **país dos perfis** da demonstração, escrito por extenso: o perfil não mostrava nenhum país e procurava uma bandeira
    que não se encontrava;
  - os **assuntos dos modelos de e-mail**, que mostravam `{{site_name}}` tal como estava na lista;
  - **tabelas demasiado largas para um telemóvel** (Gestor de bugs, transferências, histórico da wiki, moderação,
    sessões, participantes de um evento, palmarés): deslocam-se agora dentro da sua moldura em vez de alargar toda a
    página;
  - as **listas do fórum** num telemóvel: quatro colunas dividiam 360 px e a última mensagem era cortada; cada entrada
    empilha-se agora — ícone e título, depois estatísticas e última mensagem;
  - a **barra do tema Nebula** num telemóvel, que transbordava do ecrã;
  - três ficheiros pedidos por páginas sem existirem, e duas linhas escritas no registo a cada verificação de
    atualizações enquanto nenhuma versão estiver publicada.

- **Os botões «Editar» e «Eliminar» já não estão desalinhados** (2026-09-22). Nas listas da administração em duas colunas
  — categorias das notícias, categorias da galeria… — o segundo botão passava para a linha seguinte, desviado por baixo do
  primeiro. Ficam agora lado a lado.

- **O cartão «Armazenamento» da monitorização é mostrado corretamente** (2026-09-22). O indicador era desenhado como um
  círculo completo em vez do semicírculo previsto, com um número em bruto no centro, e o valor e a percentagem, colocados
  em cima, sobrepunham-se ao traço; ficam agora no côncavo do indicador. O título «Informações do servidor» da mesma
  página passa para duas linhas quando a coluna é estreita, em vez de ser cortado. No grupo «Envio de e-mail», a linha
  «Transporte de email» mostrava `[object Object]` em vez do método usado.

- **Um widget colocado sem definições é mostrado com os seus valores predefinidos** (2026-09-22). Quando um tema coloca
  um widget na instalação, ou uma disposição antiga o restaura, chega sem definições: escrevia então avisos no registo em
  cada página mostrada, e o menu de navegação simplesmente desaparecia. Recebe agora os valores que o seu formulário
  teria guardado por predefinição. Os widgets já configurados não mudam.

- **A página dos eventos volta a ser mostrada** (2026-09-22). Mostrava uma página de erro «não encontrado» assim que
  tinha eventos para distribuir por várias páginas — sob um título perfeitamente normal, o que tornava difícil dar por
  isso. A definição do número de eventos por página era guardada como texto, e o código, tornado mais estrito na véspera,
  recusava-a. Todas as listas paginadas aceitam agora esta definição nas suas duas formas.

- **Os votos das sondagens são registados** (2026-09-22). Quem votava lia «Obrigado pelo teu voto!», e o voto nunca era
  contado: nenhuma opção era reconhecida como pertencendo à sondagem. Um voto que não visa nenhuma opção da sondagem é
  agora recusado com uma mensagem que o diz, em vez de ser bem recebido.

- **O widget «Fórum» mostra finalmente tópicos e mensagens** (2026-09-22). Os seus dois tipos, «Tópicos recentes» e
  «Publicações recentes», não selecionavam nenhuma categoria do fórum, e ficavam por isso vazios em todos os sites,
  houvesse as mensagens que houvesse.

- **Outras três funções caíam na mesma armadilha que as sondagens** (2026-09-22), encontradas pela verificação escrita
  para este defeito:
  - **abrir uma candidatura com campos personalizados** fazia falhar a página, do lado do candidato como na
    administração;
  - **uma notificação que leva a um evento do calendário** falhava ao construir a sua ligação;
  - **a profundidade máxima das respostas do fórum** nunca era aplicada: o cálculo parava no primeiro nível.

- **Voltar à página 1 de uma lista já não leva a uma página não encontrada** (2026-09-22). Nas listas da administração
  cujo número de elementos por página se pode ajustar, o botão «1» construía um endereço que o site não reconhecia.

- **O ecrã de edição de um campo de perfil personalizado abre-se** (2026-09-22). O ecrã falhava de cada vez que era
  aberto: não dava etiqueta ao seu botão de guardar.

- **Dois botões «Voltar» levavam a uma página não encontrada** (2026-09-22): da página de uma imagem para o seu álbum; de
  um modo de jogo para o seu jogo.

- **«Candidatar-se» já não aparece onde a candidatura seria recusada** (2026-09-22): numa oferta fechada ou completa, e
  para quem não tem o direito de se candidatar. Da mesma forma, um administrador que lê uma conversa reservada à equipa
  pode agora convidar para ela e denunciar nela uma mensagem, em vez de ser recusado depois de ver o botão.

- **Os submenus do widget de navegação abrem-se** (2026-09-22). Uma ligação que agrupa outras ligações não abria nada ao
  clicar: ainda tinha o atributo da versão antiga do Bootstrap, que o seu próprio script já não procurava.

- **As restantes dicas de ajuda dos formulários aparecem ao passar o rato** (2026-09-22). A correção da véspera só cobria
  uma parte dos formulários: o ícone (i) ao lado dos campos dos outros ecrãs da administração, e as dicas das etiquetas,
  continuavam a não mostrar nada — o mesmo defeito de atributo, nas duas bibliotecas que os constroem.

- **As etiquetas de menu acentuadas são traduzidas** (2026-09-22). Uma ligação de menu guardada a partir da
  administração — «Actualités», «Équipes» — é guardada codificada, e a sua tradução já não se encontrava: ficava em
  francês nas outras cinco línguas, com um aviso no registo em cada página mostrada.

- **Os arquivos do marketplace são reprodutíveis** (2026-09-22). Tinham os mapas de fonte `.map`, que o compilador de
  estilos regenera em cada instalação e que o repositório ignora: dois arquivos do mesmo addon, construídos em duas
  máquinas, diferiam mesmo sem o código ter mudado. Os arquivos já não têm estes ficheiros.

- **O widget «Palmarés» pode finalmente ser instalado enviando um arquivo** (2026-09-22). Era o único dos 61 addons
  distribuíveis que não declarava a sua dependência do núcleo; o instalador, que a exige para reconhecer um addon,
  saltava-o SEM UMA PALAVRA — nem mensagem, nem rasto.

- **A página de manutenção já não está em branco** (2026-09-22). Os dois campos «Título» e «Conteúdo» vêm VAZIOS, por
  isso a página não mostrava NADA além do nome do site — embora a pré-visualização da administração prometesse um título
  e um texto. Um título e uma mensagem predefinidos, traduzidos nas seis línguas, preenchem o vazio; assim que os campos
  são preenchidos, são eles os mostrados.

- **Os botões de contorno recuperam a sua margem** (2026-09-22). Os temas só redefiniam a variante «primary» do botão de
  contorno, e a sua própria regra apagava a margem das outras: as outras seis variantes usadas pelo produto — 90 usos no
  código — pareciam simples ligações. O mais visível era «Abrir o sítio», no banner de manutenção, que só tinha margem ao
  passar o rato.

- **O código técnico já não é cor-de-rosa na administração** (2026-09-22). O tema só definia o tipo de letra dos excertos
  `<code>`, que mantinham por isso o cor-de-rosa do Bootstrap — uma cor estranha a todas as paletas do produto. Muito
  visível na lista dos cargos.

- **O botão «Fechar» das janelas fecha finalmente a janela** (2026-09-22). Em TODAS as janelas do produto, o botão
  «Fechar» (ou «Cancelar») em baixo não fazia nada: era preciso a cruz no canto superior direito. A causa: o Bootstrap 5
  mudou o nome do atributo que desencadeia o fecho, e o núcleo ainda punha o nome antigo; um navegador ignora um atributo
  desconhecido **em silêncio**. O botão tornou-se também um verdadeiro botão — era apresentado como um `<span>`, e
  portanto inacessível pelo teclado. Duas salvaguardas impedem que volte: a verificação do código recusa o nome antigo, e
  uma passagem num navegador, lançada à mão, CLICA no botão.

- **O tema Nebula já não mostra o seu menu duas vezes** (2026-09-22). Cada instalação nova mostrava duas vezes o nome do
  site e duas vezes o menu: o tema desenha a sua própria barra, e a instalação colocava TAMBÉM um título e um menu logo
  por baixo. O menu vive agora na barra superior, onde só aparece uma vez — e é **configurável** a partir da
  administração, o que antes não acontecia: as ligações da barra estavam escritas no código, por isso faltava «Contacto»
  e um módulo desativado mantinha a sua ligação. Os sites já instalados são corrigidos por uma migração, que só toca no
  cabeçalho se tiver ficado como foi incluído.

- **Mudar de língua já não vai parar a uma página de código** (2026-09-22). O seletor de língua do rodapé — o de **cinco
  temas**, entre eles o predefinido — mandava o visitante para uma página que mostrava `{"redirect":"/en/…"}` como texto
  em bruto, em vez de o levar à página traduzida. Era preciso voltar atrás para sair.

  A causa: este seletor é um verdadeiro formulário, que nada intercetava, e visava um endereço técnico que responde
  sempre em JSON. O servidor distingue agora uma chamada da janela «Escolher o meu idioma», que espera JSON, de um envio
  de formulário comum, que espera ser levado à página. Encontrado ao seguir o gesto num navegador real, o que nenhuma
  verificação tinha feito até então.

- **O conteúdo escrito numa só língua já não manda as outras línguas para uma página de erro** (2026-09-21). Uma notícia
  escrita em francês continuava a oferecer as outras cinco línguas no seu seletor, e as cinco respondiam 404: medido na
  demonstração, **60 endereços mortos em 72**. O gesto mais natural de um visitante estrangeiro ia parar a um erro. A
  versão que existe é agora servida, com um aviso que o diz na língua do visitante e uma ligação para o original. Isto
  aplica-se às notícias, aos artigos e aos álbuns (e às suas categorias), bem como às equipas.

  Duas precauções inseparáveis, sem as quais o remédio custaria mais do que a doença: a página só anuncia agora aos
  motores de pesquisa as línguas que **existem mesmo**, e designa o original como endereço canónico — senão seriam
  indexados seis endereços para um só texto. **Na administração, nenhum recurso**: uma versão vazia tem de aparecer vazia,
  é o que se vem lá preencher. `tools/check-langues-contenu.php` faz cumprir as três propriedades, na integração
  contínua.

- **A integração contínua volta a estar verde** (2026-09-21); no ramo principal, estava vermelha desde 10 de junho. Quase
  todas as causas estavam nas ferramentas de teste e na configuração da CI, não no produto. A exceção: com o servidor
  integrado do PHP 8.3, o site podia calcular mal o seu endereço base e redirecionar folhas de estilo e scripts; agora só
  o deduz de um `SCRIPT_NAME` terminado em `index.php`. Um conjunto de testes que não consegue correr falha agora em vez
  de se saltar em silêncio, e a maioria dos trabalhos da CI tem um limite de tempo.

- **O logótipo do instalador não aparecia numa instalação nova** (2026-09-21): o caminho era relativo à pasta `install/`,
  que o servidor não serve nesse endereço. A imagem está agora incorporada na página.

- **Componentes que já não se abriam desde a passagem ao Bootstrap 5** (2026-09-21). O Bootstrap 5 pôs o prefixo `bs-` em
  todos os seus atributos de dados. Um atributo que fique com o nome antigo não provoca nenhum erro: o componente
  simplesmente não faz nada. Estes estavam mortos em silêncio:

  | O que já não funcionava | Onde |
  |---|---|
  | **O acordeão da FAQ** não se abria | `modules/faq` |
  | **A lista de participantes de um evento** não se abria | `modules/events` |
  | **Os balões de ajuda de uma parte dos formulários** estavam vazios | núcleo — `neofrag/libraries/form.php` |
  | O balão do perfil do fórum, o de eliminar uma ligação de navegação | `modules/forum`, `widgets/navigation` |
  | A colocação das dicas no Editor ao vivo, o intervalo do carrossel | `modules/live_editor`, `widgets/slider` |

- **Paginações partidas, também silenciosas** (2026-09-21). O Bootstrap 5 mudou o nome dos seus utilitários direcionais;
  uma classe com o nome mudado já não está definida em lado nenhum, e o elemento mantém a sua paginação predefinida sem
  que nada o assinale.

  - **Os rodapés dos painéis e das janelas modais não se alinhavam à direita.** O núcleo construía a classe por
    concatenação (`'float-'.$align`), por isso nenhuma pesquisa de `float-right` a podia encontrar. A única ponte de
    compatibilidade do produto só cobria as tabelas do tema da administração: nada para os temas públicos. A mesma causa
    para o alinhamento das células das tabelas, nas duas bibliotecas.
  - **Nenhum campo de formulário recusado era marcado**, em nenhum tema. As duas bibliotecas punham um nome herdado —
    `has-error` (Bootstrap 3) e `has-danger` (uma versão preliminar do Bootstrap 4) — que nenhuma folha de estilo servida
    define. A mensagem aparecia, mas nada indicava o campo em causa. São substituídos por um marcador próprio, definido
    uma só vez.
  - **O rodapé** tinha `<div class="float-right">`: «Desenvolvido por NeoFrag Reborn» descia para uma nova linha, à
    esquerda. O valor vem de longe — uma migração da Alpha 0.2.2 tinha-o mudado de `pull-right` para `float-right` no
    tempo do Bootstrap 4, e o passo seguinte nunca foi dado. Uma migração (`2026_09_20_bootstrap5_float`) corrige os sites
    existentes; a semente traz agora `float-end`.
  - E as últimas classes mortas nas vistas: a barra de depuração num telemóvel, a margem das listas de eventos, as
    etiquetas do editor de menus, as duas barras do voto do recrutamento — que saíam da **mesma cor**, deixando o gráfico
    de distinguir as opiniões favoráveis das desfavoráveis.

- **O corpo de um artigo podia ser apagado na apresentação** (2026-09-21). A construção do índice substituía o texto pelo
  resultado de um tratamento que pode desistir perante um artigo longo: a página respondia então normalmente, com um
  artigo **vazio**, e nada nos registos.

- **Um widget colocado no topo ou no fundo da página desaparecia.** O tema *Nebula* oferecia os lugares «Cabeçalho» e
  «Rodapé» no editor de disposições, mas não os mostrava. O widget era mesmo guardado, e nada aparecia — sem mensagem. Os
  outros quatro temas públicos não eram afetados.

- **O widget de navegação oferecia dois módulos internos** (`live_editor` e `pages`) que devia esconder. O mesmo defeito
  fazia as estatísticas da administração perder o seu nome de módulo, sem efeito visível enquanto dois módulos não usarem
  o mesmo nome de estatística.

- **O registo de depuração crescia sem limite** quando estava ligado. Tem agora um teto: para lá de 64 MB, recomeça
  mantendo a geração anterior.

- **Num site em inglês, «No» ficava em francês («Non»).**

- **Cinco páginas da administração não ofereciam nenhum caminho de volta** (2026-09-20): a ficha de um membro, o registo
  de auditoria, a adição de um grupo, a lista de sessões e os ficheiros da monitorização. O trilho de navegação só torna
  clicável o nome do módulo se o módulo declarar ter uma página inicial de administração; `user` e `monitoring`
  declaravam o contrário embora a tenham. Uma vez nessas páginas, era preciso passar pelo menu lateral ou pelo botão de
  voltar do navegador.

- **Os motores de pesquisa recebiam JSON em vez do mapa do site.** Pedido na raiz, sem prefixo de língua — a única forma
  como um robô o pede —, `/sitemap.xml` respondia `{"redirect":"/fr/sitemap.xml"}` com o código de estado 200, e
  `robots.txt` e `humans.txt` faziam o mesmo. O redirecionamento que acrescenta o prefixo de língua respondia em JSON
  sempre que o endereço terminava em `.txt`, `.xml` ou `.json`. Como estes ficheiros não têm versão por língua, já não
  passam por este redirecionamento: são servidos diretamente, com o seu verdadeiro tipo de conteúdo.

- **`/favicon.ico` respondia 404.** Os navegadores pedem este endereço seja o que for que a página declare; leva agora ao
  favicon configurado nas definições, ou ao do CMS.

- **Ligar a autenticação de dois fatores falhava.** Ao guardar os códigos de recuperação, um erro interrompia a página: a
  conta acabava marcada «protegida» sem um único código de emergência. Encontrado por um teste escrito ao reforçar a
  tipagem desta biblioteca; corrigido, e o teste fica.

- **Um limite de «uma só tentativa» nunca bloqueava.** O limiar só era verificado a partir da segunda tentativa. Sem
  efeito nos limiares incluídos, mas o limite de denúncias da moderação, que se pode baixar até 1, nunca bloqueava então.
  O limiar aplica-se agora desde a primeira tentativa.

- **Alguns textos ficavam em francês nas outras línguas, e o registo continuava a encher-se.** Três causas, todas
  corrigidas: o título de uma página da wiki, de um tipo de evento ou de uma campanha de doações passava pela tradução
  como se fosse texto da interface (não tem tradução: é conteúdo); um texto já traduzido voltava a passar pela tradução —
  «Espace membre» tornava-se «Member area», e depois «Member area» era procurado como chave e assinalado em falta em cada
  apresentação; e trinta cadeias da interface (o campo «Nome de utilizador ou endereço de e-mail» do formulário de início
  de sessão, títulos de painel na administração dos jogos e do recrutamento, as redes sociais do perfil…) não tinham
  tradução em **nenhuma** língua, porque a verificação que controla as seis línguas só olhava para as chamadas de
  tradução explícitas, não para os títulos de campos e cabeçalhos traduzidos mais à frente. A verificação vê-os agora; as
  trinta cadeias estão traduzidas em inglês, alemão, espanhol, italiano e português.

- **O Google Analytics espera agora pelo teu consentimento.** Se houver um ID do Analytics configurado, o script da Google
  só é carregado agora depois de um clique em «Aceitar tudo» no banner de cookies, nunca antes. Além disso, a política de
  segurança do site bloqueava este script em todos os casos: o Analytics não funcionava, e nada o dizia. Funciona agora,
  e só com o consentimento do visitante.

- **Três funções da administração tinham deixado de responder desde junho.** A ordenação das tabelas clicando num
  cabeçalho de coluna, a reordenação por arrastamento das categorias, fóruns e subfóruns, e os botões Aberto/Fechado das
  páginas Manutenção e Inscrições: os seus scripts ainda pressupunham a presença do jQuery, embora tenha sido retirado do
  CMS em junho de 2026, e paravam de repente ao carregar a página — sem mensagem, os botões ficavam simplesmente inertes.
  Os três foram reescritos sem jQuery, estão cobertos por testes no navegador, e uma verificação automática recusa agora
  qualquer script que volte a chamar o jQuery.

- **Um endereço mal formado provocava um erro do servidor.** Um pedido como `/fr/a:80` ou `/:80`, dos que os robôs
  enviam, fazia falhar o cálculo da extensão do endereço e devolvia um erro 500 ao visitante. O site responde agora com um
  404 comum.

- **O formulário de contacto escrevia «De:» com o endereço do visitante.** Enviar uma mensagem do teu servidor em nome de
  `visiteur@example.org` é exatamente o que as proteções contra a falsificação dos fornecedores de e-mail (SPF, DMARC)
  recusam: a mensagem acabava no spam ou era recusada. O remetente é agora o endereço de contacto do site, e o endereço do
  visitante é posto em «Responder a», o que na prática vem dar ao mesmo. A mesma correção para as respostas a uma
  candidatura, que podiam sair com o endereço pessoal do administrador.

- **O instalador podia guardar um endereço de contacto inválido.** Quando o site era instalado acedendo a ele pelo seu
  endereço IP, o endereço de contacto passava a `noreply@<IP>`, que os fornecedores de e-mail recusam — e já não saía
  nenhum e-mail, sem nenhuma mensagem de erro visível. O endereço só é agora deduzido de um verdadeiro nome de domínio.

- **Aparecia um banner vazio nas páginas em que uma zona da disposição está vazia.** No tema Extend, todas as páginas
  exceto a inicial mostravam uma larga faixa colorida que não continha nada, assim como uma secção vazia por cima do
  conteúdo.

  A causa não era o tema: uma zona declarada mas sem widget devolvia espaços em vez de nada, por isso os temas desenhavam
  a sua moldura à volta do nada. Corrigido de uma vez para todos os temas. Se construíres um tema, podes escrever
  `if ($zone = $this->output->region('banner'))` (ou `zone(n)`): a condição diz agora a verdade.

- **O tema Extend mostrava duas vezes a sua menção «Desenvolvido por NeoFrag Reborn».** A sua disposição incluída punha
  no rodapé um bloco que repetia o que o tema já escreve ele próprio. Os sites já instalados são corrigidos
  automaticamente na atualização; os novos nunca a terão.

- **Procurar uma palavra comum devolvia uma página de erro.** Uma pesquisa como «le» ou «de» mostrava os seus resultados
  no ecrã, mas o servidor respondia **404** — que é o que veem os motores de pesquisa, as ferramentas de monitorização e
  os navegadores, e que fazia parecer desaparecida uma página perfeitamente válida.

  O destaque das palavras encontradas pressupunha que a palavra procurada aparecia forçosamente no texto mostrado. É
  duplamente falso: a pesquisa examina **vários campos** e só mostra um — um tópico do fórum intitulado «Salut tout le
  monde !» corresponde a «le» pelo seu título, não pela mensagem mostrada por baixo — e a base de dados compara **sem ter
  em conta os acentos**, enquanto o código os tinha em conta: «éléphant» aparecia na pesquisa «ele», sem que nenhuma
  palavra fosse destacada.

  Os dois casos estão tratados: o destaque reconhece agora as variantes acentuadas de uma letra, como faz a base de dados,
  e quando a palavra não está mesmo no texto mostrado, o excerto começa simplesmente pelo seu início.

- **As atualizações automáticas do núcleo não podiam funcionar** — cinco defeitos, cada um suficiente por si só: o
  endereço para verificar as novas versões não estava definido em lado nenhum, por isso o botão nunca aparecia; um
  interruptor global, a definir na configuração, bloqueava a operação; a transferência apontava para as versões do
  NeoFrag original, que teriam substituído o NeoFrag Reborn; o pacote guardava os seus ficheiros numa subpasta, por isso
  uma atualização **não teria substituído nada, sem o menor erro**; e o arquivo era extraído através de uma interface PHP
  obsoleta desde o PHP 8.0.

  O interruptor é substituído por quatro garantias: a origem das versões tem de estar numa **lista branca de endereços**
  (a mesma que para o marketplace); o ficheiro de versão só dá um **nome de ficheiro**, nunca um endereço; a impressão
  **SHA-256** do pacote é verificada **antes** de tocar num único ficheiro; cada ficheiro do arquivo é controlado
  (nenhum caminho que saia do site, nenhuma ligação simbólica). As pastas `config/` e `install/` nunca são reescritas se
  já existirem.

- **O catálogo do marketplace estava inacessível a partir de qualquer site.** O servidor que o distribui respondia
  `{"redirect":"/fr/…"}` **com o código de estado 200** em vez do ficheiro estático — um corpo perfeitamente válido como
  JSON, e perfeitamente errado, portanto uma falha silenciosa que fazia todos os sites recorrer ao seu catálogo local. A
  causa era a configuração desse servidor, entretanto corrigida: nada a fazer nos sites. No código, a atualização do
  núcleo valida agora a forma dos seus manifestos, já não só se se leem como JSON.

- **O botão «Eliminar» de um tema**, em «Temas & addons», só protegia o tema da administração, pelo seu nome escrito no
  código. Segue agora a declaração do tema, e protege portanto também o Nebula.

- **Acoplamentos do núcleo para o opcional.** Sete módulos consultavam as tabelas de módulos opcionais: cinco do núcleo
  (`admin`, `moderation`, `settings`, `statistics`, `user`), mais `games` e `teams`. Duas consultas estavam sem
  proteção: em `teams`, a verificação de existência de `recruits` estava colocada **depois** da consulta que devia
  proteger, e em `games`, eliminar um jogo consultava a tabela das equipas sem verificar que existe. As outras estavam
  protegidas mas mudas: têm agora uma anotação verificada pela CI. O único ciclo duro do pacote (`games` ↔ `teams`) está
  quebrado.

- **Editor ao vivo: as alterações aparecem sem recarregar a página.** Eliminar um widget deixava-o no ecrã e acrescentar
  uma linha não a mostrava até a página ser recarregada, embora o servidor tivesse guardado a alteração. O script esperava
  outro formato de resposta do que o que o servidor envia; o defeito vinha da retirada do jQuery.
- **Editor ao vivo: arrastar e largar widgets volta a funcionar.** Com o modo Colunas ligado, um widget acrescentado
  ficava preso no fundo da sua coluna e não podia ser movido para cima do conteúdo da página. As linhas e as colunas não
  eram afetadas.
- **Editor ao vivo: os widgets sem definições voltam a poder ser acrescentados.** Dez widgets que não têm ecrã de
  definições (`breadcrumb`, `copyright`, `downloads`, `forum`, `module`, `news`, `newsletter`, `slider`, `surveys`,
  `teams`) não podiam ser acrescentados: a adição falhava com um erro 404 e nenhuma mensagem útil.
- **Um erro do servidor já não é mostrado como conteúdo.** Quando uma ação feita sem recarregar a página recebia um erro
  do servidor (uma página 404, por exemplo), alguns ecrãs inseriam essa página de erro na interface. O erro é agora
  tratado como uma falha.
- **Editor ao vivo: o formulário de definições corresponde sempre ao widget escolhido.** Ao mudar depressa de widget ou
  de tipo, a resposta mais lenta podia mostrar o formulário de outro widget. O pedido ultrapassado é agora anulado, e o
  formulário já não é recarregado (nem o que se está a escrever perdido) quando a seleção não mudou.
- **A porta da base de dados é agora respeitada.** O CMS ligava-se sempre pela porta 3306, fosse qual fosse a porta
  introduzida no assistente de instalação: não podia ser instalado nem funcionar num servidor MySQL ou MariaDB à escuta
  noutro lado. A porta é agora guardada em `config/db.php` (só se for diferente de 3306) e usada em cada ligação.
- **Um tema ativado já não está vazio.** Ativar o Forge, o Granite, o Blockcraft ou o Extend mudava o tema sem aplicar a
  sua disposição predefinida, que só se aplicava pelo botão «Reinstalar predefinição»: o site não mostrava nenhum widget.
  A ativação aplica agora a disposição predefinida do tema se ainda não tiver nenhuma, sem nunca substituir uma
  disposição personalizada.
- **Mudar o tema predefinido aplica-se agora também aos visitantes que tinham escolhido um.** O seletor de tema do rodapé
  memorizava a escolha de um visitante durante um ano, e essa escolha prevalecia sobre o tema predefinido: quando o
  administrador mudava de tema, os visitantes, e o administrador também, ficavam no antigo, sem saída a partir de um tema
  sem seletor. Uma escolha feita antes da última mudança de tema predefinido é agora esquecida; uma feita depois continua
  a ser respeitada.
- **O navegador já não guarda cópias desatualizadas das páginas.** Na falta de instruções, podia voltar a mostrar uma
  versão antiga de uma página: uma mudança de tema, por exemplo, só aparecia depois de um recarregamento forçado
  (Ctrl+F5). As páginas e respostas do site proíbem agora esta cache (`Cache-Control: no-store`); as imagens, os estilos e
  os scripts, servidos diretamente pelo servidor web, não são afetados.

### Segurança

- **Um valor guardado já não pode injetar etiquetas num formulário** (2026-09-23). O formulário de edição punha o valor
  de um campo na página escapando as aspas como o faria uma cadeia PHP — o que não tem nenhum efeito em HTML. Um valor que
  não tivesse passado pelo próprio formulário (importação, dados de demonstração, outro módulo) podia portanto sair do
  seu campo e acrescentar código ao ecrã de administração: uma citação cuja fonte era `"><img src=x>` demonstrou-o. Os
  campos de texto, as áreas de texto, as listas, as caixas de verificação e os botões de opção codificam agora o seu
  valor; a apresentação de um valor introduzido normalmente não muda. As notificações («Citação modificada», etc.) são
  também passadas ao script da página numa forma que já não o pode partir.

- **A política de segurança declara agora uma regra para os media** (`media-src`). Não tinha nenhuma e recorria à regra
  geral (`default-src 'self'`). Permite agora os media do próprio site e os media incorporados na página (`'self' data:`);
  o endereço da emissão de uma rádio web é acrescentado **só se houver uma emissão configurada**: um site sem rádio web não
  permite nenhuma origem externa adicional.

- **Onze avisos de segurança fechados nas dependências** (2026-09-20): `league/commonmark` passa da **2.8.2 para a
  2.10.1** (dez avisos, oito deles graves) e `phpseclib` da **3.0.52 para a 3.0.57** (um aviso de gravidade média).

  Nove dos dez avisos do commonmark são **negações de serviço através de Markdown preparado** — análise em tempo
  quadrático, títulos com âncoras em colisão, notas de rodapé duplicadas, blocos de atributos adjacentes, saída XML
  profundamente aninhada — e o décimo é uma **vulnerabilidade XSS**: o filtro dos atributos `on*` podia ser contornado com
  uma quebra de página U+000C. A biblioteca apresenta o Markdown da wiki, dos artigos e da FAQ, escrito pelos membros.
  Vários destes avisos visam extensões que o produto não ativa (notas de rodapé, atributos, âncoras de títulos, saída
  XML); a atualização fecha-os todos.

  A apresentação não muda, e não é só uma impressão: **doze casos representativos** — títulos, tabela, lista de tarefas,
  bloco de código, HTML injetado, ligação `javascript:`, citação, texto riscado, ligação automática, listas aninhadas,
  imagem, entidades e nota de rodapé, aninhamento profundo —, apresentados pelas duas versões com a configuração real do
  produto, dão o mesmo HTML **byte a byte** (1568 bytes, a mesma impressão).

  Nota: o Composer **recusa agora instalar** a 2.8.2, precisamente por causa destes dez avisos. Foi preciso desligar a
  política numa pasta descartável para fazer a comparação.

## [1.1.0] — 2026-08-23

### Segurança
- **Revisão de segurança de todo o código**, e correção das falhas graves que encontrou:
  - **Cópias de segurança**: os arquivos de cópia de segurança (uma cópia da base de dados e da pasta `config/`, que
    contém as palavras-passe) já não podem ser transferidos a partir de um simples endereço web, nem com Apache nem com
    nginx; só a administração (Monitorização) os entrega. O seu nome inclui agora uma parte aleatória, impossível de
    adivinhar. A pasta dos registos (`logs/`) está protegida da mesma forma.
  - **Tokens secretos**: os identificadores de sessão, os tokens dos formulários e as ligações enviadas por e-mail
    (palavra-passe esquecida, validação da conta) saem de um gerador aleatório seguro; eram previsíveis. Estas ligações
    expiram ao fim de uma hora, e um novo pedido anula o anterior.
  - **Início de sessão**: a autenticação de dois fatores (2FA) e os banimentos são verificados seja qual for a forma de
    iniciar sessão — palavra-passe, conta Discord, Google ou GitHub, ligação «palavra-passe esquecida» —, e já não só
    com a palavra-passe.
  - **Ligações nos e-mails**: o endereço do site usado nas ligações enviadas por e-mail, no regresso de um início de
    sessão pelo Discord, Google ou GitHub e no regresso de um pagamento Stripe é o guardado na instalação
    (`config/url.php`), e já não o que o pedido anuncia: um atacante já não consegue fazer enviar uma ligação que leve
    ao seu próprio site.
  - **Páginas de administração**: um nome de anexo armadilhado (numa denúncia de moderação) ou um endereço IP falso
    (cabeçalho `X-Real-IP`, no histórico das sessões) podiam injetar código nelas; são agora verificados e
    neutralizados. A mensagem de boas-vindas de um servidor de jogo, fornecida por um serviço externo, é limpa antes de
    ser mostrada.
  - **Ações de administração**: eliminar, ativar ou desativar, fechar, aprovar, restaurar, purgar… exigem agora um
    token de segurança nos 23 módulos que não o tinham. Eram simples ligações ou formulários sem token: uma ligação
    armadilhada, clicada por um administrador com sessão iniciada, bastava para desencadear a ação.
  - **Ferramentas**: os scripts da pasta `tools/` só correm a partir da linha de comandos, nunca a partir de um
    endereço web; a cada alteração, a integração contínua verifica que nenhuma dependência PHP tem uma falha conhecida
    (`composer audit`).
  - **Botões silenciados pela política de segurança (CSP)**: a CSP estrita do site bloqueia as ações escritas no HTML
    (`onclick="…"`), por isso estes botões não faziam nada: banner de cookies, número de linhas das tabelas de
    administração, confirmações da reciclagem e das revisões, nome do ficheiro anexado nas mensagens, ações em lote das
    menções do fórum, seleção de um campo com um clique, botão da janela de eliminação. Estão ligados de outra forma e
    funcionam também no conteúdo carregado sem recarregar a página. A escolha do número de linhas chamava além disso o
    jQuery, que já tinha sido retirado.
- **Política de segurança do conteúdo (CSP) estrita**: servida pelo site com um token próprio de cada página, recusa os
  scripts escritos na página sem esse token (`unsafe-inline`), o código construído em tempo de execução (`unsafe-eval`)
  e os objetos incorporados (`object-src 'none'`); os scripts só vêm agora do site e de uma curta lista de endereços,
  já que o TinyMCE e o CodeMirror passam a ser alojados pelo próprio site.
- **Sessão renovada ao iniciar sessão**: o identificador de sessão muda quando se inicia sessão; um identificador imposto
  antes do início de sessão já não dá acesso à sessão aberta.
- **Segredos cifrados na base de dados**: a palavra-passe SMTP e a chave da autenticação de dois fatores (2FA) estão
  cifradas (AES-256-GCM); uma cópia da base de dados já não as entrega em claro. Um valor existente é cifrado da próxima
  vez que for guardado.
- **Webhooks**: um webhook já não pode visar a rede interna do servidor (endereços privados, locais ou reservados
  recusados, redirecionamentos não seguidos).

### Adicionado
- **Ficheiros CSS e JavaScript sempre atualizados**: cada ficheiro é pedido com a sua data de modificação (`?v=…`), de
  modo que o navegador o recarrega assim que muda — incluindo um ficheiro substituído por um tema ou enviado à mão —,
  sem ter de aumentar `nf_version_css` (que continua a ser usado quando o ficheiro não é encontrado).
- **Marketplace**: a janela «Atualizações» da página *Temas & Addons* indica que está disponível uma nova versão do
  NeoFrag Reborn; a atualização do núcleo propriamente dita continua a ser manual.
- **Atualizações dos addons**: a mesma janela compara os addons instalados com o catálogo do marketplace e instala as
  novas versões, com as suas migrações de base de dados.
- **Instalador**: novo aspeto — logótipo do NeoFrag Reborn, banner «Fork não oficial do NeoFrag», crédito a Michaël
  BILCOT e Jérémy VALENTIN (licença LGPLv3) no rodapé, tipos de letra Inter e Space Grotesk, cores turquesa.
- **Ficheiros**: um gestor de ficheiros (árvore de pastas, envio, permissões de leitura por ficheiro ou por pasta),
  portado do HiddenCMS, da HiddenBlob (LGPLv3). Incluído no núcleo.
- **Emojis personalizados** (módulo Emojis, incluído no núcleo): a administração acrescenta imagens com nome, que
  `:nome:` mostra nas mensagens e assinaturas do fórum, nas notícias, nas páginas, nos eventos e no recrutamento, entre
  outros.
- **Newsletter**: envio agendado na data escolhida, em pequenos lotes tratados pela tarefa agendada do site (cron) em
  vez de enviar tudo de uma vez; acompanhamento das aberturas (taxa de abertura por campanha, medida por uma imagem
  invisível); modelos de e-mail reutilizáveis; escolha dos destinatários: todos os subscritores, só os membros ou um
  grupo.
- **Eventos**: um evento pode repetir-se todos os dias, todas as semanas ou todos os meses, o número de vezes escolhido
  — cada data torna-se um evento de pleno direito. Os participantes, exceto os que recusaram, recebem um lembrete no
  site (sino das notificações) antes do evento, 24 horas antes por predefinição; os lembretes passam pela tarefa
  agendada do site (cron).
- **Reações com emoji**: seis reações à escolha (👍❤️😂😮😢😡) em vez de um só coração, como no Discord ou no Facebook,
  cada uma com a sua contagem; os antigos «gosto» passam a ❤️.
- **Widget «Estado ao vivo»** (o antigo widget Twitch): segue vários canais ao mesmo tempo, no Twitch e no YouTube
  (credenciais Twitch e chave de API do YouTube necessárias), com jogo, espectadores, título, miniatura e leitor
  integrado.
- **Páginas compostas**: por baixo do seu texto, uma página pode mostrar blocos de outros módulos (últimas notícias,
  notícias de uma categoria, últimos artigos, transferências populares), colocados pela ordem pretendida e configurados
  um a um ao editar a página. O código curto aceita também definições, por exemplo `[block:news.latest count=2]`.
- **Tema Extend**, de Chewbaka, portado para o NeoFrag Reborn (Bootstrap 5, modos dia e noite), com licença
  CC BY-NC-SA 4.0; disponível no marketplace.
- **Pesquisa instantânea**: o widget de pesquisa propõe resultados a partir da segunda letra escrita (notícias, fórum,
  páginas).
- **Ordenação das listas da administração**: dez listas (notícias, artigos, anúncios classificados, transferências,
  FAQ, livro de visitas, ligações, multimédia, sondagens, wiki) ordenam-se segundo o critério escolhido.
- **Avatares animados**: um GIF animado enviado como avatar continua animado — sempre, se o servidor tiver Imagick, e
  caso contrário desde que não ultrapasse 250 × 250 píxeis.
- **Permissões**: a grelha das permissões abre-se numa janela, sem sair da página.
- **Gestor de ficheiros do webmaster** (Monitorização, botão «Gerir ficheiros»): percorrer os ficheiros do site,
  editá-los num editor (CodeMirror), criá-los, mudar-lhes o nome ou eliminá-los. As pastas `config/`, `logs/` e
  `backups/` ficam fora de alcance; cada gravação guarda uma cópia `.nfbak` e entra no registo de auditoria. Qualquer
  escrita exige a **palavra-passe de webmaster**, distinta da da conta e guardada fora da base de dados (definida na
  instalação ou na Monitorização, cartão «Segurança webmaster»); uma vez definida, é também pedida de novo para
  eliminar ou purgar cópias de segurança e para mudar a chave da tarefa agendada.
- **Notas de versão a partir de uma única fonte**: `tools/changelog-section.php` extrai uma versão deste registo de
  alterações (`CHANGELOG.md`), em Markdown ou em HTML; as notas das releases do GitHub saem daí, e os seus textos já
  não podem divergir.

### Alterado
- **Instalador «tudo incluído»**: o assistente passa a ter quatro passos (Requisitos → Base de dados → Administrador →
  Concluído). Todos os módulos, widgets e temas incluídos são instalados e ativados no passo «Base de dados», e a página
  inicial mostra as notícias assim que a instalação termina; o passo «Módulos» e os seus perfis desaparecem.
- **Bootstrap 4.6.2 → 5.3.8, e o jQuery já não é carregado**: o JavaScript do site apoia-se numa pequena ferramenta
  própria (`window.NF`). Nove extensões jQuery ou Bootstrap 4 são substituídas (notificações → toasts do Bootstrap 5,
  selectize → Tom Select, seletor de data → flatpickr, FullCalendar 3 → 6, seletores de cor e de ícone, árvore e
  medidores → código próprio, barras de deslocamento → deslocamento nativo), e o jQuery UI pelo SortableJS. Quatro
  scripts esquecidos ainda chamavam o jQuery: foram reescritos na 1.2.0.
- **Modo escuro**: tabelas, cartões, menus pendentes e o editor de texto adotam as cores escuras do tema em vez de
  ficarem brancos, em todos os temas.
- **Definições dos widgets e disposição das páginas**: guardadas em JSON em vez do formato de serialização do PHP, o
  que fecha uma via de injeção de código; as definições antigas continuam a ser lidas.
- **Gráficos das estatísticas**: o Chart.js (licença MIT) substitui o Highstock, uma biblioteca comercial que impedia
  redistribuir livremente o CMS.

### Corrigido
- **MySQL 8**: abrir um tópico ou responder no fórum, dividir ou juntar tópicos, criar uma conversa nas mensagens,
  reordenar as imagens do Slider… falhavam com um erro fatal no MySQL 8, que recusava a forma como o site abria as suas
  transações. Passam agora pelas funções do mysqli previstas para isso.
- **Emojis**: um emoji — ou qualquer carácter de 4 bytes — num texto provocava um erro de base de dados. A ligação e
  todas as tabelas passam a `utf8mb4` (colação `utf8mb4_unicode_ci`, reconhecida tanto pelo MySQL 8 como pelo
  MariaDB 10; as colações próprias do MariaDB 11, que faziam falhar a instalação noutros servidores, desaparecem).
- **`where('col', [])`** gerava uma condição vazia (→ `DELETE`/`UPDATE` sobre toda a tabela): produz agora `1 = 0`
  (conjunto vazio).
- **Datas posteriores a 2038**: o envio agendado de uma newsletter, a publicação agendada de uma notícia, de um artigo,
  de uma galeria ou de uma página, e as datas de um evento não podiam ultrapassar 19 de janeiro de 2038: para lá disso,
  o MariaDB recusava a gravação, sem nenhuma mensagem. Estas datas vão agora até ao ano 9999; as instalações existentes
  são atualizadas por uma migração.
- **`/user/login` e `/user/registration` respondiam «página não encontrada»**: os botões «Entrar» e «Registo» dos temas
  levam lá quando o JavaScript está desativado ou a ligação é aberta num novo separador. Estes endereços abrem agora a
  janela de início de sessão ou de registo.
- **Nebula**: o botão «Registo» continuava visível quando as inscrições estavam fechadas, e levava a uma página não
  encontrada. Passa a estar escondido nesse caso.
- **Widget Discord**: indica a verdadeira causa de uma falha (widget desativado no servidor, identificador
  desconhecido, rede) em vez de uma mensagem genérica, mostra o verdadeiro logótipo do servidor (lido no convite
  público) e conta corretamente os membros online — a lista fornecida pelo Discord para nos 100, o que falseava a
  contagem dos servidores grandes.
- **Widget TeamSpeak, vista em árvore**: falhava no PHP 8 e recorria ao cartão simples. A árvore dos canais e dos
  clientes é agora desenhada pelo próprio widget, com ícones Font Awesome (já não é preciso um pacote de ícones); um
  erro de rede mostra uma mensagem genérica, sem revelar o endereço nem a porta do servidor.
- **Widgets Discord, Twitch e Servidor de jogo**: mostravam «inacessível» mesmo quando o serviço respondia (a resposta
  era descodificada duas vezes). Os pedidos do site enviam além disso um identificador de navegador (User-Agent): sem
  ele, os serviços protegidos pela Cloudflare respondiam com uma recusa (erro 403).
- **Cópias de segurança e Monitorização**: criar uma cópia de segurança falhava nos servidores com PHP-FPM, e falhava
  com nginx ou Plesk, que respondiam «página não encontrada» aos endereços em `.json`; eliminar ou transferir uma cópia
  de segurança levava a uma página não encontrada; a árvore da Monitorização mostrava um erro na primeira atualização.
  Tudo corrigido.
- **Marketplace inacessível**: URL predefinido passado para **sem www** (`https://neofrag-reborn.xyz/marketplace`).
- **Envio de vários ficheiros**: o primeiro ficheiro de um envio múltiplo fazia falhar o envio («página não
  encontrada») — era o que bloqueava o envio no módulo Ficheiros. Para os programadores: `uploaded_file()` tomava o
  índice `0` pela ausência de índice.
- **Webhooks**: o registo de um membro (`user.registered`), um novo comentário (`comment.created`) e um novo tópico do
  fórum (`forum.topic`) desencadeiam finalmente o seu webhook; estes eventos eram propostos mas nunca emitidos.

### Documentação
- **Documentação** (guias da wiki): para os programadores, os formulários (`form()` e `form2()`) e a validação das
  definições de um widget; para os administradores, a resolução de problemas da instalação, um exemplo de criação de um
  cargo e o que fazer quando o marketplace está inacessível. Uma nota explica como configurar o nginx ou o Plesk, que
  respondiam «página não encontrada» aos endereços em `.json`.

---

## [1.0.0] — 2026-06-06 · base do Reborn (sobre a Alpha 0.2.4)

Primeiro ciclo do fork: modernização da base, reforço da segurança e uma ampla vaga de novas
funcionalidades.

### Adicionado

**Plataforma e ferramentas**
- **Migrações de base de dados** numeradas (`tools/migrate.php`, com um comando `baseline` para retomar uma base de
  dados existente) e primeiros testes automatizados (PHPUnit).
- **Novo instalador web**, que substitui o do NeoFrag original: um assistente em cinco passos (Requisitos → Base de
  dados → Módulos → Administrador → Concluído). O passo «Módulos» propunha três perfis de site (Comunidade,
  Gaming / eSports, Site simples) e podia acrescentar addons a partir do marketplace.
- **Marketplace remoto** (catálogo e arquivos servidos por neofrag-reborn.xyz): o instalador (passo «Módulos») e a
  administração (botão «Marketplace» da página *Temas & Addons*) acrescentam com um clique os addons do catálogo
  oficial que não vêm com o site. Segurança: HTTPS obrigatório, impressão SHA-256 verificada, arquivo controlado
  entrada a entrada (nenhum caminho que saia da pasta do addon, nenhuma ligação simbólica), endereço do catálogo fixo,
  tamanhos e tempos limite delimitados.
- **Ferramentas de construção dos pacotes**: `package-addons` prepara o catálogo do marketplace, `build-release` os
  arquivos a enviar por FTP.
- **Script de manutenção** (`tools/maintenance.php`, a lançar por uma tarefa agendada): esvazia a reciclagem do que lá
  está há mais de 30 dias (ajustável) e elimina as contas nunca confirmadas ao fim de 7 dias, quando a validação das
  inscrições está ativada — nunca um administrador.

**Temas e interface**
- **Quatro temas criados para o NeoFrag Reborn**: Nebula, pensado para os sites de comunidade, Forge, Blockcraft e
  Granite. Variáveis de cor comuns (`--nf-*`) vestem módulos e widgets com as cores de cada tema.
- **Redesenho completo da administração**: novo aspeto, barra lateral, paleta de comandos (Ctrl+K), modos claro e
  escuro.
- Seletores de **tema** e de **idioma** no rodapé (visitantes incluídos).
- **Menus**: construtor de menus com vários níveis, utilizável no widget Navegação (opção «Menu gerido»).

**Conteúdo**
- **Notícias e artigos**: publicação agendada — o conteúdo aparece à hora escolhida, e as notificações, webhooks e
  pontos só partem nesse momento, já não ao guardar (um endereço a chamar por uma tarefa agendada, protegido por uma
  chave); ações em lote; pesquisa, filtros e paginação na administração; uma página própria para ler uma notícia. Os
  artigos recebem uma imagem de destaque, como as notícias já tinham, e o contador de visualizações das notícias, que
  nunca subia, conta finalmente.
- **Publicação agendada** também nas páginas, galerias e eventos (data de publicação distinta da data em que o evento
  se realiza, para os eventos).
- **Wiki**: serve de documentação no site (índice em cartões, apresentação Markdown fiável); pesquisa e paginação;
  comparação visual de duas revisões de uma página, linha a linha.
- **Multimédia**: título e descrição editáveis para cada ficheiro, pesquisa, filtro por tipo e paginação. Pesquisa,
  filtros e paginação também na administração das Transferências, do Diretório de ligações, da FAQ, das Sondagens e do
  Livro de visitas.
- Feed **RSS 2.0** (notícias + artigos), botões de **partilha** nas redes sociais, **SEO** (meta description,
  canonical, Open Graph, Twitter Card, meta por página).

**Comunidade e envolvimento**
- **Centro de notificações** no site (sino e número de não lidas), com **subscrições** (seguir um conteúdo ou uma
  categoria). As notificações assinalam os comentários, as respostas do fórum, as menções @utilizador, as mensagens
  privadas e os convites para um evento.
- **Reações** «gosto» nas notícias, artigos, comentários e mensagens do fórum; **revisões** dos conteúdos (histórico e
  restauro); widget «Últimos comentários».
- **Reciclagem** comum: as notícias, artigos, galerias, comentários e mensagens do fórum eliminados passam por lá, e
  podem ser restaurados.
- **Fórum**: categoria reservada aos membros VIP, imagem por categoria.

**Gaming**
- **Eventos**: gestão completa dos adversários (lista, edição, eliminação; o NeoFrag original só permitia acrescentar
  um a partir de um jogo), contagem decrescente ao vivo, notificação de convite.

**Monetização e gamificação**
- Karma/reputação → **pontos** (escala configurável) → **loja** (pagamento em pontos) → estatuto **VIP**.
- **Stripe** (compra de pontos e de pacotes VIP), **gestão de publicidade** (escondida para os membros VIP),
  **donativos** (módulo e widget portados do «Donation v3» da HiddenBlob, a partir de majiid; LGPLv3).

**Ligações entre módulos**
- **Estatísticas** alargadas a 19 módulos (o NeoFrag original cobria 3: comentários, fórum, membros); **mural de
  atividade** no perfil, que reúne a atividade de um membro em todos os módulos; painel **«Por tratar»** na
  administração; **blocos de página**: o código curto `[block:…]` insere numa página um bloco de um módulo (notícias,
  artigos, transferências).

**Webhooks e auditoria**
- **Webhooks** de saída assinados (HMAC), geridos a partir da administração. O **registo de auditoria** regista também
  a instalação e desinstalação de um addon, a ativação de um tema ou de um módulo e a gravação das definições.

**Administração das contas**
- **Membros**: exportação dos membros em CSV ou JSON (RGPD); os moderadores com essa permissão ajustam a moderação a
  partir da sua área de membro. A edição e a eliminação de um membro a partir da administração, já presentes no NeoFrag
  original, voltam a estar ligadas à nova lista de membros.

### Alterado
- Versionamento normalizado em **SemVer puro** nas dependências dos addons (retirada das etiquetas «Alpha»).
- **Páginas** faz agora parte do núcleo; **Notícias**, **Fórum** e **Galerias** podem ser desinstalados.
- **Editor de texto rico**: funciona finalmente — o HTML que produz é limpo em vez de ser mostrado como texto.

### Removido
- Os dois temas do NeoFrag original, «Thème par défaut» (`default`) e Azuro, substituídos pelos temas do NeoFrag
  Reborn.
- O editor BBCode do NeoFrag original (WysiBB) e a conversão do BBCode: o editor de texto rico substitui-o, e um texto
  escrito em BBCode é agora mostrado tal como está.

### Corrigido
- **Dez defeitos corrigidos**, entre eles: os eventos sem participantes nem jogo não apareciam; editar ou eliminar um
  modo de jogo falhava; eliminar uma oferta de recrutamento falhava; a ligação para um parceiro levava a uma página não
  encontrada (leva agora ao seu site); o autor de uma denúncia nunca era mostrado, nem sequer aos moderadores
  autorizados; os widgets Galerias e Fórum verificavam mal as suas definições ou as permissões. Os contadores de
  visualizações só contam agora uma vez por visita e ignoram os robôs.
- Um conteúdo agendado já não é visível pelo seu endereço direto antes da hora. Botões que não faziam nada funcionam
  agora: períodos das estatísticas, ativação dos modelos de e-mail, redes sociais (as seis são mostradas), botões
  «Adicionar» das notícias, páginas e galerias.
- **Temas**: contraste dos botões elevado ao nível WCAG AA, campos de início de sessão unidos ao seu ícone,
  alinhamentos corrigidos.
- **Ecrãs pequenos e grandes** (verificado de 390 a 2560 píxeis de largura): na administração, menu lateral deslizante
  e conteúdo em toda a largura no telemóvel; perfil de membro; barra da conta compacta no Nebula, Forge, Blockcraft e
  Granite.
- As páginas Membros, Estatísticas e Editor ao vivo da administração mostravam um erro 500; alguns contadores ficavam
  bloqueados em 1 (distintivo da moderação, subscritores da newsletter, estatísticas da multimédia), e uma categoria
  vazia de Transferências, FAQ ou ligações não podia ser eliminada. (Para os programadores: erros de tipagem estrita em
  `timetostr()` e nos helpers; `(int)` sobre `row(FALSE)` de um `COUNT`/`SUM` valia sempre 1.)

### Segurança
- O HTML enviado pelos membros é limpo do lado do servidor (HTMLPurifier) contra a injeção de código; um ficheiro
  enviado é verificado pelo seu conteúdo real, não só pela sua extensão; HSTS ativado (os navegadores já não recuam
  para HTTP).
- **Tokens anti-CSRF** comparados em tempo constante. **Endereço IP**: a limitação de frequência e a lista de IP
  banidos já não confiam em cabeçalhos falsificáveis (`X-Forwarded-For`…), a não ser que esteja declarado um proxy de
  confiança. **Permissões**: verificações de permissões acrescentadas onde faltavam (Livro de visitas, Jogos, Galerias,
  Eventos, Estatísticas).
- Os antigos scripts de atualização já não podem recriar objetos PHP arbitrários ao reler as definições
  (`unserialize` limitado por `allowed_classes`).
- **Impressão digital da sessão** contra o roubo de sessão: um membro com sessão iniciada é desligado se o seu
  navegador (user-agent) mudar de forma marcada durante a sessão — uma simples atualização do navegador não basta para
  isso. O endereço da publicação agendada está protegido por uma chave comparada em tempo constante.
