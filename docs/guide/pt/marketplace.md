# O marketplace

O **marketplace** serve para **atualizar** os teus addons e para **instalar os que não estão no teu servidor**. Só
oferece os addons oficiais do NeoFrag Reborn, que o pacote de instalação já contém todos: serve, portanto, sobretudo
para as atualizações, e para recuperar um addon que tinhas eliminado. Intervém *depois* da instalação, a partir da
administração.

O catálogo e os arquivos são servidos a partir de **neofrag-reborn.xyz**, em HTTPS; cada arquivo é verificado pela sua
**impressão SHA-256** ao ser transferido. Cada versão publica-os também, com o catálogo, na
[página de versões](https://github.com/NeoFragReborn/extensions/releases) do repositório dos addons opcionais.

O teu site recebe **o catálogo da sua versão**: a um site que ainda não está atualizado são oferecidos os addons feitos
para a versão que executa, não os da última, que poderiam chamar funções que o seu núcleo ainda não tem. E se um addon
pedir uma versão mais recente do NeoFrag, a janela do marketplace não o oferece: diz-te para atualizar primeiro o teu
site.

## Instalar um addon

Dois caminhos:

### 1. Com um clique a partir da administração

**Admin → Temas & Addons → Marketplace**. A janela lista os addons disponíveis não instalados; marca-os e clica em
**Instalar** — o NeoFrag transfere, verifica a impressão SHA-256, extrai e regista o addon (e o seu widget associado, se
houver).

### 2. À mão (arquivo ZIP)

A página (pública) **Marketplace** apresenta cada addon num cartão com um botão **Transferir**. Podes também transferir
o `.zip` e depois **Admin → Temas & Addons → Adicionar** e enviar o arquivo (o envio é validado: os arquivos com
conteúdo não seguro são recusados). É também o caminho de um addon que tenhas escrito: comprime a sua pasta e envia-a.

## Atualizar ou retirar

- **Atualizar**: **Admin → Temas & Addons → Atualizações** compara a versão instalada de cada addon com a do catálogo e
  lista as que há que atualizar; aplica-as com um clique (transferência + SHA-256, ficheiros substituídos, **migrações de
  esquema pendentes aplicadas**). Senão, enviar um novo `.zip` por *Adicionar* também atualiza (uma versão superior
  substitui a antiga).
- **Retirar**: no cartão do addon em **Temas & Addons**, **Eliminar** (o addon tem de estar desativado). As suas tabelas
  e os seus dados são retirados.

> Os **conectores de autenticação** (Discord, GitHub, Google) fazem parte do **núcleo**: já lá estão, e configuram-se
> nas definições — não passam pelo marketplace.

> **Marketplace inacessível?** Se o ecrã «Atualizações» mostrar «Marketplace inacessível», verifica que o teu servidor
> consegue sair em **HTTPS** para `neofrag-reborn.xyz`. O catálogo está fixado a essa origem: a lista dos anfitriões
> autorizados está escrita no código (`MARKETPLACE_HOSTS`, `neofrag/installer.php`), e a definição
> `nf_marketplace_url`, sem ecrã de administração, só pode designar um endereço HTTPS nesses anfitriões.

## Segurança

O marketplace só transfere a partir de uma **origem fixa** em **HTTPS** (nunca um URL escrito pelo utilizador), verifica
o **SHA-256** de cada arquivo face ao catálogo e **recusa qualquer arquivo adulterado** (anti zip-slip: caminhos
absolutos, `..`, ligações simbólicas). Nenhum código remoto é executado ao adicionar ou atualizar um addon (só extração +
`install.sql` e migrações SQL idempotentes).

## Para os autores de addons

O código dos addons opcionais vive em [NeoFragReborn/extensions](https://github.com/NeoFragReborn/extensions): propor um
addon ou uma correção é abrir lá um pedido de integração. O catálogo é gerado por `tools/package-addons.php`, que
comprime cada addon (uma pasta `<name>/` na raiz do arquivo) e produz `marketplace/catalog.json`. Uma entrada real,
abreviada:

```json
{
  "schema": 1, "base_version": "1.2.22",
  "addons": [{
    "type": "module", "name": "quotes", "tier": 2, "category": "contenu",
    "title": "Citations", "description": "Recueil de citations classées, avec leur auteur et leur source.",
    "i18n": { "en": { "title": "Quotes", "description": "…" } },
    "version": "1.0", "author": "NeoFrag Reborn", "license": "LGPLv3 <https://neofr.ag/license>",
    "file": "modules/quotes.zip", "preview": "modules/quotes.jpg", "size": 60556, "sha256": "…",
    "requires": { "base": ">=1.2.22", "addons": [] }, "provides_widgets": [], "install": "zip"
  }]
}
```

`catalog.json` e os `.zip` publicam-se **juntos**: as impressões SHA-256 só valem para os arquivos da mesma passagem. O
catálogo oficial é publicado com cada versão, e um site não pode seguir outro sem modificar `MARKETPLACE_HOSTS`. Um
addon que escrevas — guias: [criar um módulo](create-a-module.md), [um widget](create-a-widget.md),
[um tema](create-a-theme.md) — instala-se em qualquer site por *Temas & Addons → Adicionar*.

## Atualizar o núcleo (o próprio NeoFrag)

**Admin → Temas & Addons → Atualizações** **assinala** uma nova versão do núcleo se existir: a que o canal de atualização
anuncia (a Monitorização consulta-o) e, senão, o `base_version` do catálogo, a versão do CMS para a qual foi construído.

Dois caminhos para a aplicar.

### Com um clique a partir da Monitorização

**Admin → Monitorização → Atualizar**. O site faz primeiro uma **cópia de segurança**, depois transfere o pacote de
atualização, verifica a sua impressão, sobrepõe os ficheiros, aplica as migrações pendentes e recompila as folhas de
estilo.

O botão só aceita a atualização certa, graças a quatro garantias:

1. a **origem** vem da mesma lista branca que o marketplace, e um valor injetado na base de dados não pode sair dela;
2. `version.json` só fornece um **nome de ficheiro**, nunca um URL: nem anfitrião, nem caminho, portanto nem
   redirecionamento nem subida de pastas;
3. a impressão **SHA-256** é verificada **antes** de tocar num único ficheiro do site;
4. o arquivo é controlado **entrada a entrada** (anti zip-slip, ligações simbólicas recusadas).

Os ficheiros já presentes em `config/` e o bloqueio `install/db.txt` nunca são reescritos: a configuração de um site em
funcionamento é preservada. O resto de `install/` é código do produto, e segue as versões.

### À mão

Transfere `neofrag-reborn-public-<version>.zip` da [página de versões](https://github.com/NeoFragReborn/neofrag/releases)
e substitui os ficheiros (exceto `config/`, `upload/`, `backups/`), depois visita o site — as migrações aplicam-se
sozinhas ([guia de implementação](../../deploy-ftp.md#mettre-à-jour-un-site-déjà-en-ligne)).

### O que cada versão publica

Cada versão publica na origem de atualização (`https://neofrag-reborn.xyz/update/`) três ficheiros que formam um
conjunto coerente: o pacote de atualização, `version.json` (a versão, o nome do pacote, o seu SHA-256 e o seu tamanho) e
`checksum.json` (uma impressão por ficheiro incluído, para o controlo de integridade da Monitorização). O botão verifica
cada um face aos outros: publicar um sem os outros faz falhar a atualização, sem tocar em nada do site.
