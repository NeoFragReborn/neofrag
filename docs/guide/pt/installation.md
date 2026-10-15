# Instalação

O NeoFrag Reborn instala-se num alojamento web comum, **alojamento partilhado incluído**: não são precisos Docker, nem
Composer, nem acesso à shell — o pacote traz as suas dependências.

## Requisitos

- **PHP 8.2 a 8.5** com as extensões `mysqli`, `mbstring`, `openssl`, `curl`, `gd`, `zip`, `intl`, `fileinfo` e
  `iconv`, e um PHP capaz de cifrar as palavras-passe com **Argon2** (quase todos o são). Ativam-se no painel do teu
  alojamento, muitas vezes em «Versão do PHP» ou «Extensões». O instalador verifica-as todas e recusa continuar se faltar
  uma.
- **MySQL 5.7, 8.0 ou 8.4**, ou **MariaDB 10.5, 10.6, 10.11, 11.4 ou 11.8**, com o motor **InnoDB** e o conjunto de
  caracteres **utf8mb4**. São as versões em que cada lançamento do CMS é testado antes de sair, com PHP 8.2 e 8.5:
  instalação completa, todo o conjunto de testes e cada perfil de instalação.
- **Apache** com `mod_rewrite` (um ficheiro `.htaccess` vem incluído); ou **nginx** (`nginx.conf`); ou **Caddy**
  (`Caddyfile`).
- Uma base de dados **vazia** e as suas credenciais.
- Um verdadeiro **nome de domínio** que aponte para o servidor: o instalador tira dele o endereço de contacto do site
  (`noreply@o-teu-dominio`) — instalado num endereço IP, um site não teria um endereço de remetente válido.

## O instalador

O instalador decorre em **cinco passos**: *Requisitos* → *Perfil do site* → *Base de dados* → *Administrador* →
*Concluído*.

1. **Envia** os ficheiros do NeoFrag Reborn para a raiz do teu site: o conteúdo da pasta `neofrag-reborn/` do pacote
   `neofrag-reborn-public-<versão>.zip` (por FTP, ou descompactando-o no servidor). O pacote transfere-se a partir da
   [página de versões](https://github.com/NeoFragReborn/neofrag/releases/latest) do projeto.
2. Abre o teu domínio: o instalador arranca e lista os **requisitos** com o valor que encontrou (versão do PHP,
   extensões presentes).
3. Escolhe o **perfil do site**:

   | Perfil | O que instala |
   |---|---|
   | **Completo** | o núcleo e todos os módulos do pacote |
   | **Gaming / eSports** | o núcleo e a identidade gaming: fórum, equipas, jogos, eventos, recrutamento, palmarés… |
   | **Comunidade** | o núcleo, notícias, fórum, galeria, eventos, coletivos e a API (para o bot do Discord e as integrações) |
   | **Associação / clube** | o núcleo, notícias, fórum, galeria, eventos, calendário, coletivos, doações, quotas (pagamentos), newsletter, wiki e FAQ |
   | **Só o núcleo** | contas, permissões, páginas, definições — nada mais |

   Os módulos podem ainda ser desmarcados um a um; os módulos de que um módulo precisa são adicionados automaticamente,
   e o ecrã final di-lo. O formulário funciona também sem JavaScript.
4. Preenche a **ligação à base de dados**: o instalador importa o esquema, instala os módulos, widgets e temas
   escolhidos, e prepara as disposições predefinidas.
5. Cria a **conta de administrador**. Está feito.

Os módulos que não instalaste adicionam-se mais tarde em **Administração → Temas & Addons**, e os que não estão no
pacote a partir do [marketplace](marketplace.md).

## Instalação pela linha de comandos

Para uma implementação **automatizável** (servidor, aprovisionamento), `install/cli.php` faz o que o instalador faz, sem
navegador — requisitos incluídos: recusa continuar se faltar um. Instala o perfil **Completo**.

```bash
# Palavra-passe do administrador por uma variável de ambiente (escondida da lista de processos):
export NF_ADMIN_PASS='a-minha-palavra-passe-forte'
php install/cli.php \
  --db-host=localhost --db-name=neofrag --db-user=neofrag --db-pass=secret \
  --admin-user=admin --admin-email=admin@site.tld --admin-pass-env=NF_ADMIN_PASS \
  --site-name="A minha comunidade" --site-url=https://site.tld --yes
```

Sem argumentos, passa para o **modo interativo** (palavra-passe oculta). Opções: `--db-port`, `--create-db` (cria a
base), `--dry-run` (valida a configuração e testa a ligação sem escrever nada), `--force` (reinstala), `--no-lock` (não
põe o bloqueio), `--lang=fr|en|de|es|it|pt` (a língua das suas mensagens), `--help`.

## Proteger o acesso depois da instalação

No fim, o instalador põe um **bloqueio** (`install/db.txt`): voltar a `/install/` já não mostra nada de utilizável. Por
precaução, **apaga ou muda o nome da pasta `install/`** — já não é necessária. Ativa o **HTTPS** se o teu alojamento não
o fez. Com nginx ou Caddy, parte dos exemplos na raiz (`nginx.conf`, `Caddyfile`): já bloqueiam o acesso às pastas e
ficheiros sensíveis, como faz o ficheiro `.htaccess` com o Apache.

## Primeiras definições

1. **Administração → Definições**: nome do site, descrição, favicon, **endereço de contacto** (verifica que é válido e
   que um servidor de correio consegue enviar — sem isso, o formulário de contacto e os e-mails de inscrição nunca
   chegam; um servidor SMTP configura-se em *Definições → E-mail*, e *E-mails → Envio de teste* prova-o), página
   inicial.
2. **Administração → Temas & Addons**: o teu tema, os teus módulos.
3. **Administração → Editor ao vivo**: compõe as tuas páginas (coloca os teus widgets nas regiões).
4. **Administração → Utilizadores / Permissões**: cria os teus cargos e regula os acessos.
5. **Tarefas agendadas**: as publicações programadas, as newsletters e os lembretes de eventos saem pelo **cron** do
   site (a Monitorização dá o seu endereço e a sua chave). Adiciona uma tarefa a cada cinco minutos no teu alojamento;
   sem ela, nada do que está programado é enviado.

Os passos seguintes, por ordem: [Primeiros passos](premiers-pas.md).

## Resolução de problemas

- **«Impossível ligar à base de dados»**: verifica o anfitrião (muitas vezes `localhost`, por vezes um endereço dedicado
  nos alojamentos partilhados), a porta (`3306`), o nome da base — **tem de existir e estar vazia** — e as credenciais.
- **«Extensão PHP em falta»**: ativa a extensão indicada no painel do teu alojamento; na linha de comandos, `php -m`.
- **Pasta `config/` sem permissão de escrita**: o instalador tem de escrever lá `db.php` e os segredos. Dá permissão de
  escrita a `config/`, `cache/`, `logs/`, `upload/`, `backups/`.
- **Instalação interrompida**: o MySQL não consegue desfazer um `CREATE TABLE` executado a meio — **cria uma nova base
  vazia** antes de tentar outra vez.
- **Endereços em 404 ou AJAX partido com nginx ou Plesk**: o `.htaccess` só é lido pelo Apache; vê a nota
  «nginx / Plesk» no [guia de implementação](../../deploy-ftp.md).
- **Nenhum e-mail sai**: consulta *Sistema → Monitorização → Registo de erros* (uma linha `[email] échec envoi …`); o
  endereço de contacto tem de ser válido e tem de existir um transporte (um servidor SMTP configurado, ou um agente de
  correio local).
- **«Ocorreu um erro», com uma referência**: a página falhou. O detalhe está no registo de erros; a referência encontra
  a linha certa.

## Desenvolvimento

O código está no GitHub: o CMS em [NeoFragReborn/neofrag](https://github.com/NeoFragReborn/neofrag), os addons opcionais
em [NeoFragReborn/extensions](https://github.com/NeoFragReborn/extensions) — `tools/assembler.php` coloca-os na árvore do
CMS. Para desenvolver ou testar, serve qualquer máquina com PHP 8.2+ e MariaDB ou MySQL; é fornecida uma pilha Docker
(`docker-compose.yml`). Os pormenores (base de dados, migrações, testes, verificações, implementação):
[`docs/development.md`](../../development.md).
