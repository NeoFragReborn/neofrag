# Développement & déploiement — NeoFrag Reborn

Comment on fait tourner, éprouve et déploie le CMS, sur n'importe quelle machine. L'architecture est dans
[architecture.md](architecture.md) ; chaque outil de `tools/` est décrit dans
[tools/README.md](../tools/README.md) — ce document ne recopie pas leur liste.

## 1. Environnement

Toute machine avec **PHP 8.2 à 8.5** (les extensions sont listées dans le
[guide d'installation](guide/installation.md#prérequis)) et **MySQL ou MariaDB** convient. Trois façons de servir :

```bash
composer install
php install/cli.php --db-name=neofrag --create-db --db-user=… --db-pass=… --admin-user=admin \
    --admin-email=admin@exemple.test --admin-pass-env=NF_ADMIN_PASS --site-url=http://127.0.0.1:8080 --yes
php -S 127.0.0.1:8080 tools/router-builtin.php      # serveur PHP intégré, routeur fourni
```

- **Apache** (`.htaccess` livré), **nginx** (`nginx.conf`) ou **Caddy** (`Caddyfile`).
- **Docker** : `docker-compose.yml` monte Apache + PHP, MariaDB, phpMyAdmin et Mailpit. Fourni pour qui
  peut l'employer ; ce n'est pas la voie de référence.
- `php tools/ci-install.php` fait une installation complète non interactive (c'est ce qu'emploie la CI) ;
  `php tools/seed-demo.php` remplit un jeu de démonstration cohérent.

Le projet vit dans deux dépôts : `neofrag` porte le cœur et les addons qu'installent les profils,
`extensions` les addons à la carte. Le paquet d'installation réunit les deux, et c'est ce produit entier
que décrivent la documentation, la NOTICE et la suite de tests. `php tools/assembler.php
--extensions=../extensions` pose les addons à la carte dans l'arbre, sans que git les voie ; sans eux,
`check-docs` et `check-notice` refusent de juger plutôt que de se tromper.

## 2. La boucle de travail

1. **modifier** ;
2. **éprouver** — la batterie (§ 4) sur une installation servie, de préférence identique à celle de
   la production (même PHP, même serveur web, même base), puis **lire son journal PHP** : un site qui
   répond 200 partout peut écrire une erreur à chaque page sans que rien d'autre ne le dise ;

   ```bash
   php tools/check-journal.php --journal=logs/php.log --depuis=24h
   ```
3. **commiter** — le message dit *pourquoi*, et ce qui a été vérifié ;
4. **lire la CI** : elle rejoue la batterie sur quatre versions de PHP et sur une base neuve, deux
   choses qu'un poste ne fait pas.

Trois habitudes tirées des défauts passés : **vérifier plutôt que déduire** (lire le code, mesurer la
page servie, plutôt que supposer) ; **éprouver un contrôle à l'envers** — casser exprès ce qu'il garde
et exiger qu'il refuse — avant de lui faire confiance ; **regarder la page rendue**, dans chaque thème,
car un défaut visuel ne se lit pas dans le code.

## 3. La base de test et les migrations

Les suites `integration` et `headless` ont besoin d'une base dédiée, **jamais celle du site** :

```bash
php tools/prepare-test-db.php          # crée la base, le compte et le schéma, puis ÉCRIT config/db-test.php
vendor/bin/phpunit --fail-on-skipped   # aucun test sauté : un saut voudrait dire que la base n'a pas été jointe
```

`config/db-test.php` n'est pas versionné ; les classes de base des tests le relisent quand les
variables `NF_TEST_DB_*` ne sont pas posées. Il remplace les cinq variables d'environnement qu'il
fallait reposer à chaque séance — faute de quoi dix-huit suites se sautaient en silence, soit près
de 200 assertions qui ne vérifiaient plus rien. Le nom de la base doit finir par `_test`.
`InstallerDbTest` demande en plus un compte capable de **créer et supprimer** une base (`root_user`,
`root_pass` dans le même fichier).

Le schéma du cœur évolue par migrations versionnées (`migrations/*.up.sql`, suivies dans
`nf_migrations`) que `tools/migrate.php` applique, annule ou adopte — les commandes sont dans
[le guide du framework](guide/framework.md#migrations). Deux choses que le guide ne dit pas : le DDL
MySQL est auto-commit, donc **sauvegarde avant `up` en production** ; et un code neuf applique seul ses
migrations à la première page vue — démonstration comprise (`index.php`, `nf_migrations_du_code()`, cf.
le guide). **Une migration neuve du cœur a sa ligne dans l'historique de `install/schema.sql`** (avec
son effet porté dans le schéma ou `install/seed.sql`) : une installation neuve part de là et ne doit rien
rejouer. `check-instantanes` le vérifie sans base ; l'oubli, fait avec la migration du captcha, n'était
vu que par la CI. Les **addons** ont leurs propres
migrations (`<addon>/install/migrations/*.up.sql`, suivies dans `nf_addon_migrations`) — voir
[Créer un module](guide/create-a-module.md).

## 4. La batterie

```bash
vendor/bin/phpunit --fail-on-skipped                          # Unit, Integration, Headless
vendor/bin/phpstan analyse --no-progress --memory-limit=2G    # aucune erreur ; baseline dans phpstan-baseline.neon
php tools/check-all.php                                       # composer audit + les contrôles statiques (secondes)
php tools/check-all.php --navigateur                          # + ceux qui servent le site et ouvrent Chrome (minutes)
```

`phpstan-baseline.neon` gèle le legs. Il se **régénère** par `composer stan:baseline`, jamais à la
main : des entrées écrites à la main n'avaient pas la graphie de PHPStan, et la baseline portait
plusieurs centaines de blocs morts — dont toute une famille « Function NeoFrag not found » —
avant d'être régénérée le 2026-09-21. Une baseline régénérée se **compare** à l'ancienne avant
d'être adoptée : une entrée qui apparaît est une erreur neuve, à corriger plutôt qu'à geler.
`composer stan:baseline` (`tools/stan-baseline.php`) fait les deux : il régénère, compare, et refuse
d'adopter une liste qui contient quoi que ce soit de neuf.

`check-all` découvre les contrôles présents, les classe par famille (statique, navigateur, à cible
explicite) et **énonce à la fin ce qu'il n'a pas joué**. Le catalogue, les conventions et la
bibliothèque commune des outils sont dans [tools/README.md](../tools/README.md).

**Ce que chaque couche attrape** — aucune ne suffit seule :

| Le défaut | La couche qui le voit |
|---|---|
| Une classe qui fatalise au chargement, une fonction native redéclarée sur PHP 8.5 | PHPStan (il charge tout), la matrice PHP 8.2 → 8.5 de la CI |
| Une requête qui échoue à l'exécution, une date rejetée par MariaDB en mode strict | PHPUnit **contre une base** — le job `e2e-smoke`, après `ci-install` |
| Un 500 sur un POST, un fatal de rendu, un 503 de maintenance | `check-smoke`, contre un site qui tourne |
| Un profil d'installation qui ne démarre pas sans tel module | `check-install-profiles`, qui installe pour de vrai |
| Un écran de l'assistant d'installation qui casse, un refus qui ne refuse pas, un compte créé qui ne se connecte pas | `check-assistant`, qui joue l'assistant comme un visiteur, profil par profil |
| Un écran de réglages d'addon qui plante à l'ouverture ou une fois enregistré | `check-reglages --enregistrer`, qui ouvre, enregistre tel quel et rouvre chacun |
| Une configuration de serveur livrée qui laisse passer un fichier sensible, exécute un script interne ou ne démarre pas | `check-serveur-web`, sur un vrai Apache, nginx ou Caddy (`installation.yml`) |
| Une commande du README ou du guide du contributeur qui échoue chez un nouveau venu | `check-nouveau-venu`, qui les joue telles quelles sur une machine vierge (`nouveau-venu.yml`) |
| Un addon du marketplace qui ne s'installe pas sur un site qui n'a que le cœur | `check-extensions`, par la fenêtre du marketplace puis par « Ajouter » |
| Un hébergement auquel manque une extension PHP, qui ne saurait pas laquelle | `check-prerequis-absents`, qui la retire tour à tour |
| Le paquet qui ne s'installerait pas chez un hébergeur mutualisé (pas de shell, `open_basedir`, 128 Mo) | `check-assistant` sur le paquet publié, sous un Apache bridé (`installation.yml`) |
| Une erreur survenue APRÈS l'envoi des en-têtes | `check-journal`, le seul qui lise le journal PHP |
| Un script qui plante au chargement, une violation CSP | `check-js-console`, dans un vrai navigateur |
| Un lien mort, un débordement, un contraste insuffisant, un retour absent | `check-liens`, `check-responsive`, `check-contraste`, `check-admin-back` |
| Une page qui répond 200 mais écrit une alerte au journal pendant qu'elle se rend | `check-liens`, qui relit le journal après son parcours ; `check-journal --depuis=24h` sur une installation servie |
| Une requête à une colonne dont on lit les valeurs comme des lignes | `check-db-colonne` |
| Une heure affichée avec `date()`, donc à l'heure du serveur et non dans le fuseau de celui qui regarde ; une date montrée avec un format chiffré figé (`timetostr('d/m/Y H:i')`, `'Y-m-d H:i'`) qui ignore la langue | `check-heures` |
| Un compteur (vues, clics) qui fait avancer la date de modification d'une ligne dont la colonne suit `ON UPDATE current_timestamp` | `check-db-compteurs` |
| Une liste que le checker découpe en pages, rendue sans ses liens de pages : les éléments suivants sont inatteignables | `check-pagination` |
| Une suppression `->delete()` sans sa table, qui lève une `ArgumentCountError` | `check-db-delete` |
| Un bouton d'administration hors de la charte : « modifier » coloré, « supprimer » sans contour rouge ni corbeille, un bouton plein dans une ligne | `check-actions-admin` |
| Un module qui touche à la configuration laissé ouvert sur la démonstration, ou verrouillé alors que la remise à zéro ne le rétablit pas | `check-demo-lock` (les déclarations) ; `check-demo-ecriture` (l'épreuve : il essaie d'écrire, connecté en `demo`) |
| Un secret — clé, mot de passe, jeton, dans les réglages du site ou d'un widget — qui partirait dans `install/demo.sql`, publié avec le dépôt ; une liste d'exclusion qui nomme un réglage inventé | `check-demo-lock` (la liste et le motif dans `tools/lib/demo.php`, confrontés au code et au fichier produit) |
| Ce qu'un moteur de recherche lit de travers sans que l'écran le montre — plan du site aux adresses relatives ou qui oublie un module, canonique sur une redirection ou sur `/index`, liens entre langues incomplets, titre qui répète le nom, recherche indexée | `check-seo` (navigateur ; `--base=` pour un site en ligne) ; les fonctions dans `neofrag/helpers/seo.php`, testées par `HelpersSeoTest` |
| Un défaut visuel qu'on ne voit que dans UN thème, UN mode, à UNE largeur, connecté ou en visiteur, avec ou sans contenu — débordement, escalier, texte tronqué ou chevauchant, image cassée, icône vide, texte technique affiché, contraste, cible trop petite, erreur JavaScript, fichier introuvable | `check-mise-en-page` (famille cible, une demi-heure ; `--vierge` ajoute une installation neuve) |
| Un nom de classe de Bootstrap 3 ou 4 — dans un gabarit, une feuille de thème, les données livrées, ou fabriqué par concaténation — même quand une feuille le redéfinit ; une grille sur une cellule de tableau ; une liste déroulante sans flèche | `check-classes-bs4` |
| Une page du wiki livré ou de la démonstration en retard sur son guide `docs/guide/` | `check-wiki-docs` |
| Un contenu rédigé dans une seule langue qui rend 404 dans les autres | `check-langues-contenu`, qui mesure aussi le bandeau, les `hreflang` et le `canonical` |
| Un texte d'interface écrit en dur en français — gabarit, contrôleur, script, installeur | `check-textes-en-dur` (les sources) ; `check-mise-en-page --langue=en` (les pages rendues : il relève tout texte resté en français et dit d'où il vient) |
| Une « traduction » recopiée du français, une forme du pluriel perdue | `check-langs` |
| Une globale JavaScript créée par oubli d'un `var`, un `eval` déguisé, un `innerHTML` calculé | `check-js-lint` (ESLint), après `npm install` |
| Un service worker qui ne se retire plus, ou qui se mettrait à garder le HTML | `check-service-worker`, qui bascule le réglage et mesure les deux états |
| Un lien qu'aucun clic n'atteint, une modale qui ne s'ouvre pas, un formulaire qui renvoie sur une page technique | `check-parcours`, le seul contrôle qui CLIQUE (famille cible, hors batterie) |
| Une archive de la place de marché périmée, un addon qui ne s'installerait pas par ZIP, une entrée sans aperçu ou un aperçu hors format | `check-marketplace`, qui compare le code, les archives et le catalogue |
| Un addon sans vignette, une vignette hors du format 960 × 600, deux vignettes identiques, une exemption devenue fausse | `check-vignettes` (le format et les exemptions motivées dans `tools/lib/vignettes.php`) |
| Une convention rompue : couplage non déclaré, contrat de carrefour, classe Bootstrap 4, clé de traduction, chiffre de doc faux | les contrôles statiques |

**Écrire un texte d'interface.** Tout texte qu'un visiteur ou un administrateur peut lire — thèmes
compris — passe par `lang()`, écrit en **français**, la langue source : sa clé est
l'empreinte CRC32 de ce texte, et `check-langs` exige sa traduction dans les six langues.

- Classe : `$this->lang('…')` ; vue : `<?php echo $this->lang('…') ?>` ; méthode statique d'un module :
  `NeoFrag()->module('nom')->lang('…')` ; cœur : `NeoFrag()->lang('…')`.
- Script `.js` : il est servi à travers PHP, **au nom de son addon** (`Output::asset()`), et ses
  traductions se cherchent donc dans celles de l'addon : `'<?php echo addslashes($this->lang('…')) ?>'`.
- Une phrase entière, les variables en `%s` — jamais des morceaux autour d'une variable, l'ordre des
  mots change d'une langue à l'autre. Pluriel : `lang('%d sujet|%d sujets', $n, $n)`.
- `->title()`, `->heading()`, `->tooltip()`, `->label()`, `->modal()`, `->popover()`, `->placeholder()`
  et `->info()` traduisent déjà leur littéral ; `->no_data()` non, on lui passe `$this->lang('…')`.
- Une DONNÉE livrée en français (titre d'un rôle, d'un modèle d'e-mail, nom de zone d'un thème) se
  traduit à l'affichage, `$this->lang($role['title'])` : un texte renommé par l'administrateur reste le
  sien. Sa traduction se déclare dans le dictionnaire avec `"_addons": ["modules/access"]`.
- Les addons de `addons/` (langues, authentificateurs) n'ont pas de fichiers de langue à eux : leurs
  textes vont dans `neofrag/langs/`. Aucun addon ne se déclare `'language' => 'en'` : le thème
  d'administration le faisait, et ses textes français n'étaient jamais traduits en anglais.
- L'assistant d'installation tourne avant le CMS : il a sa propre `lang()` (`install/lib/langue.php`)
  et ses fichiers `install/langs/`, vérifiés de la même façon.

La marche : écrire `lang('…')` ; `php tools/check-langs.php --fix` ajoute la clé **française** ;
`php tools/fill-langs.php --tous --dictionnaire=traductions.json` écrit les cinq autres langues —
d'abord en reprenant ce que le produit sait déjà dire (deux addons qui écrivent la même phrase
portent la même clé), puis depuis le dictionnaire, dont il refuse toute traduction qui perdrait un
`%s`, une balise ou une forme du pluriel ; `check-langs --toutes` et `check-textes-en-dur` confirment.

## 5. Intégration continue

`.github/workflows/ci.yml`, à chaque push sur `main` et à chaque PR — sauf quand l'envoi ne touche que
la documentation, que `docs.yml` vérifie en une minute —, cinq jobs : **statique** (`php -l`, les
gardes légères et tous les contrôles statiques — `check-tools` le vérifie — ; puis PHPStan, `check-wiki-docs`, `composer audit`, les
épreuves de `tests/Browser/` et le bot Discord : compilation TypeScript, tests, `npm audit`),
**test** (PHPUnit sur PHP 8.2, 8.3, 8.4, 8.5), **db-smoke** (schéma, migrations,
`check-install-profiles`), **interface** (`check-admin-back`, `check-js-console`, `check-liens`,
`check-journal`, puis un échantillon de `check-mise-en-page` sur une installation montée par
`ci-install`) et **e2e-smoke** (installation complète sous Apache, `check-smoke`,
`check-widget-contract`, `check-reglages`, puis **PHPUnit complet contre la base installée**, sans
aucun test sauté).
Dans **statique**, chaque contrôle joue même si un précédent a échoué : un seul passage donne tous
les verdicts.

Un envoi joue la batterie **rapide** : tout sauf **interface**, et **test** sur PHP 8.2 seulement.
La batterie **entière** — **interface** et les quatre PHP — joue avant chaque version.

Deux workflows jouent chaque semaine sur un dépôt public, et à la main ailleurs : `compatibilite.yml`
(PHP 8.2 et 8.5 face à chaque version de MariaDB et de MySQL annoncée), `installation.yml` (l'assistant
d'installation joué de bout en bout par `check-assistant`, sous PHP 8.2 et 8.5, paquet public et paquet de
démonstration ; les réglages de chaque addon par `check-reglages` ; et Apache, nginx et Caddy, avec les
configurations livrées, par `check-assistant`, `check-serveur-web` et `check-smoke` ; le paquet publié chez un
hébergeur mutualisé simulé ; les addons du marketplace par `check-extensions` ; les prérequis absents par
`check-prerequis-absents` ; les liens de tous les documents par lychee) et `nouveau-venu.yml`
(le README et le guide du contributeur joués à la lettre par `check-nouveau-venu`).

`composer.json` fixe la **plateforme** à PHP 8.2.0 : le verrou reste installable sur toute version que
le produit supporte, quelle que soit celle du poste qui l'a produit. Sans cela, un `composer update`
joué sous PHP 8.5 avait verrouillé une bibliothèque exigeant 8.4, et cinq jobs de la CI, qui
tournent sous 8.3, échouaient dès `composer install`.

## 6. Configuration et SCSS

- `config/` porte les secrets et n'est pas versionné : `db.php`, `email.php`, `neofrag.php` (débogage,
  mode démonstration), `crypt.php`, `password.php`, `url.php` (origine canonique des URL absolues, figée à
  l'installation), `webmaster.php` (optionnel), `db-test.php` (la base de test). Les gabarits `*.php.dist`
  à côté montrent la forme de chaque fichier : [config/README.md](../config/README.md).
- **SCSS** compilé côté serveur (scssphp) par `Theme::install()`, qui passe par l'API du module `tools`,
  et par la mise à jour du cœur. Il n'existe pas de commande en ligne : `index.php` n'a pas de mode
  CLI, et l'ancienne consigne `php index.php cli/tools/scss reload` fatalisait dès la lecture de l'URL.

## 7. Surcharge à trois niveaux

Vues, classes et fichiers se résolvent dans cet ordre (premier trouvé gagne) : `overrides/{type}/{fichier}`
(global), `themes/{thème actif}/overrides/{type}/{fichier}` (par thème), l'original livré. On personnalise
sans toucher au code livré. Deux fichiers homonymes à deux niveaux se masquent : `check-assets` le refuse.

## 8. Déploiement

- **Cible** : PHP ≥ 8.2 + MariaDB 10.5+ / MySQL 5.7+ ; Apache, nginx ou Caddy.
- **Paquets** : `php tools/build-release.php` produit le site principal, le site de démonstration, la
  distribution générique, et le paquet de **mise à jour du cœur** avec ses deux manifestes
  (`version.json`, `checksum.json`) à publier ensemble sur l'origine de mise à jour. Le Monitoring des
  sites installés les télécharge, vérifie l'empreinte, sauvegarde, applique, et revient en arrière si une
  étape échoue. Publier une version revient aux mainteneurs : voir « Versions et publication » dans
  [.github/CONTRIBUTING.md](../.github/CONTRIBUTING.md#versions-et-publication).
- **Par FTP** (mutualisé) : [deploy-ftp.md](deploy-ftp.md).
- **Sur un serveur** : copier les fichiers, les rendre à l'utilisateur du serveur web, redémarrer
  PHP-FPM — sans quoi l'opcache continue de servir l'ancien code —, puis lire `logs/php.log`.

### Les erreurs : ce que voit le visiteur, ce que lit l'administrateur

- Une page qui plante rend une **erreur interne (500)** dans le thème, avec une **référence** de huit
  caractères ; une erreur fatale, une exception hors des pages ou la base injoignable rendent une page
  **autonome** (`nf_page_erreur_autonome()`), qui ne demande rien au site et lit ses textes dans
  `neofrag/langs/`. Plus de page blanche ni de « Page introuvable » pour une panne.
- **Journaliser une erreur** : `nf_journaliser_erreur('etiquette', $message, $origine)` écrit
  `[etiquette] MÉTHODE /adresse : message — origine (réf. XXXXXXXX)` et rend la référence — à montrer à
  l'utilisateur quand il voit l'échec (`$this->error->interne($reference)`, ou un flux qui la renvoie).
  L'étiquette dit la gravité : `[checker]`, `[banlist]`, `[marketplace]`, `[theme]` sont des
  avertissements, toute autre étiquette une erreur.
- **Lire le journal** : `neofrag/helpers/journal.php` est l'unique définition d'une entrée, de sa gravité,
  de son regroupement et du masquage des données sensibles — la page *Monitoring → Journal des erreurs*
  et `tools/lib/journal.php` (`check-journal`) la chargent toutes deux.
- **Côté navigateur** : un rejet de `NF.ajax` que l'appelant ne rattrape pas devient un message (statut,
  et la référence lue dans l'en-tête `X-NF-Reference`). Un appelant qui doit remettre son interface en
  état (bouton grisé, sablier) le fait dans un `.catch()` qui **relance** l'erreur.
- **Les opérations en flux** (sauvegarde, mise à jour) finissent par `[100, "OK"]` ou par
  `[99, message, référence]` : le navigateur n'annonce le succès que sur le premier.
- **Les outils de diagnostic** s'allument dans `config/neofrag.php`, ou depuis *Monitoring → Diagnostic*
  pour une heure (`cache/diagnostic.json`, `nf_diagnostic_regler()`) : le mode débogage
  (`NEOFRAG_DEBUG_BAR`), la trace des pages (`NEOFRAG_LOGS`, `logs/neofrag.log`), le relevé des
  traductions (`NEOFRAG_LOGS_I18N`, table `nf_log_i18n`). Le code ne lit jamais ces constantes : il
  demande `nf_debogage_actif()`, `nf_trace_active()`, `nf_traductions_actives()` pour COLLECTER, et
  `nf_debogage_visible()`, `nf_traductions_visibles()` pour AFFICHER (la barre, l'erreur en brut, le
  diagnostic d'un checker, le drapeau des textes traduits) — l'affichage n'est que pour un
  administrateur connecté. Tout est dans `neofrag/helpers/erreurs.php`.
- **Migrations en production** : elles s'appliquent seules au premier passage d'un code neuf ; pour les
  jouer à la main avant : `status` → `up --pretend` → sauvegarde → `up`.
- **Tâches planifiées** : le cron du site (`/fr/monitoring/cron?key=…`, toutes les 5 minutes) porte les
  publications programmées, les newsletters et les rappels ; `tools/maintenance.php` purge la corbeille et
  les comptes jamais confirmés.

### La place de marché

Trois choses doivent rester d'accord : le code, les archives et le catalogue.

```bash
php tools/capturer-apercus.php          # les vignettes, sur un site d'essai PEUPLÉ (contenu de démo)
php tools/check-vignettes.php           # chaque addon a la sienne, en 960 × 600 (statique, joué en CI)
php tools/package-addons.php            # zippe les addons distribuables + écrit marketplace/catalog.json
php tools/check-marketplace.php         # le catalogue dit-il la vérité sur ce qu'il propose ?
php tools/check-marketplace.php /var/www/neofrag    # … et sur une installation servie
```

- Les **vignettes** sont des captures d'écran réelles, en JPEG de **960 × 600**. Elles vivent dans
  `<type>s/<nom>/images/thumbnail.jpg`, partent dans l'archive, et sont recopiées à côté d'elle pour que
  la place de marché les serve sans ouvrir le zip. Une entrée du catalogue sans aperçu est un écart
  pour `check-marketplace` ; un addon sans vignette en est un pour `check-vignettes`, dès le dépôt.
- `capturer-apercus` **écrit dans le site** qu'il photographie : une session d'administrateur, un widget
  posé le temps d'une photo sur la page de contact, des réglages d'exemple, et avec `--basculer-theme`
  le thème par défaut. Il se lance donc sur un site d'essai installé avec `install/cli.php --demo` —
  jamais sur la démonstration publique ni sur la production. Les données qu'une vignette montre —
  clés d'API, serveur Discord, vidéos — y sont des **exemples** : aucune vraie clé, aucun vrai identifiant.
- Un addon qui n'a rien à photographier (un module sans page, un connecteur dont le bouton ne vit que
  dans la fenêtre de connexion) figure dans `tools/lib/vignettes.php` avec sa raison. Toute autre
  absence est une faute.
- Après toute modification d'un addon distribuable, **repackager** : `check-marketplace` compare le
  contenu des archives au dépôt, fichier par fichier, et refuse une archive périmée — même si le
  numéro de version n'a pas bougé.
- Le catalogue est **mis en cache** côté site (`cache/marketplace-catalog.json`) : après un
  déploiement, le supprimer, sinon la page sert l'ancien pendant une heure.

## 9. Conventions

Français pour les commentaires, les commits et la documentation ; identifiants en anglais dans le code
hérité (suivre le fichier voisin). Fichiers en `snake_case`, classes en `PascalCase`.
`declare(strict_types=1)` sur tout fichier neuf, avec ses types et un test qui l'exerce — le cliquet de
`check-strict-types` ne baisse jamais. Le périmètre utile est **entièrement converti** depuis le
2026-09-21 ; seuls les gabarits `views/**.tpl.php` ne le sont pas, et c'est voulu : ils n'assemblent
que du HTML, la déclaration y serait valide et ne protégerait rien. Le reste est dans
[.github/CONTRIBUTING.md](../.github/CONTRIBUTING.md).

### Les dates et les fuseaux horaires

Comment le produit enregistre, affiche et lit une date — et ce que `check-heures` refuse : dans
[le guide du framework](guide/framework.md#dates-et-fuseaux-horaires), publié dans le wiki.
