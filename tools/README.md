# Les outils

Tout ce qui se lance en ligne de commande vit ici : les **contrôles** (`check-*.php`), qui vérifient
et ne changent rien, et les **outils** qui agissent — construire les paquets, régénérer le SQL livré,
publier, installer. Ce document est leur seule description : les autres documents y renvoient, ils
ne recopient pas la liste.

## En une commande

```bash
php tools/check-all.php               # composer audit + tous les contrôles statiques (secondes)
php tools/check-all.php --navigateur  # + ceux qui servent le site et ouvrent un navigateur (minutes)
php tools/check-all.php --liste       # ce qui existe, classé, sans rien lancer
php tools/check-all.php --seul=langs,docs --sauf=liens --minutes=20
```

`check-all` **découvre** les contrôles présents et lit leur famille dans leur en-tête : il n'y a
aucune liste à tenir. Il joue `composer audit` en tête, borne chaque contrôle en durée, et énonce à
la fin ce qu'il n'a **pas** joué — sans quoi « tout est vert » trompe. La suite de tests et l'analyse
statique ne sont pas des outils : `composer test` et `composer stan` (cf. [docs/development.md](../docs/development.md)).

## Le catalogue

Produit depuis l'en-tête de chaque outil par `php tools/check-tools.php --catalogue`, et vérifié à
chaque passage : quand un outil change, `php tools/check-tools.php --ecrire` met cette section à jour.

<!-- catalogue:début -->
### Contrôles statiques

Joués par défaut par `check-all`. Ils lisent les sources, sans base ni serveur.

| Outil | Ce qu'il fait | Usage |
|---|---|---|
| [`check-actions-admin`](check-actions-admin.php) | dans l'administration, un bouton « modifier » est neutre, un bouton « supprimer » est un contour rouge avec une corbeille. | `php tools/check-actions-admin.php` |
| [`check-addon-contracts`](check-addon-contracts.php) | les contrats des carrefours : la méthode qu'un carrefour appelle existe, publique, avec la bonne signature. | `php tools/check-addon-contracts.php` |
| [`check-addon-coupling`](check-addon-coupling.php) | le couplage réel entre addons, lu au tokeniseur : tout couplage fatal est déclaré ou annoté. | `php tools/check-addon-coupling.php` |
| [`check-addon-declarations`](check-addon-declarations.php) | chaque addon déclare `core`, `presets` et `requires`, et le cœur ne dépend jamais d'un optionnel. | `php tools/check-addon-declarations.php` |
| [`check-addon-name`](check-addon-name.php) | aucune lecture `->name` sur un addon chargé : elle rend FALSE en silence, le nom est dans `info()->name`. | `php tools/check-addon-name.php` |
| [`check-assets`](check-assets.php) | deux fichiers d'asset homonymes dont l'un masque l'autre, et les cartes de source absentes. | `php tools/check-assets.php` |
| [`check-classes-bs4`](check-classes-bs4.php) | aucun legs de Bootstrap 3 ou 4 : classes disparues, attributs `data-*` sans `bs`, classes fabriquées par concaténation. | `php tools/check-classes-bs4.php` |
| [`check-css-variables`](check-css-variables.php) | toute variable CSS qu'un module ou un widget emploie est définie par tous les thèmes. | `php tools/check-css-variables.php` |
| [`check-db-colonne`](check-db-colonne.php) | une requête à UNE colonne rend des valeurs, pas des lignes : aucune n'est lue comme un tableau. | `php tools/check-db-colonne.php` |
| [`check-db-compteurs`](check-db-compteurs.php) | un compteur (vues, clics) ne fait pas avancer la date de modification de sa ligne. | `php tools/check-db-compteurs.php` |
| [`check-db-delete`](check-db-delete.php) | une suppression par le constructeur de requêtes nomme sa table : `->delete('nf_…')`, jamais `->delete()`. | `php tools/check-db-delete.php` |
| [`check-demo-lock`](check-demo-lock.php) | le verrou de la démonstration et sa remise à zéro se répondent, et l'instantané publié n'emporte aucun secret. | `php tools/check-demo-lock.php` |
| [`check-docs`](check-docs.php) | la documentation respecte ses règles : chiffres justes, renvois vivants, rien d'orphelin ni de recopié. | `php tools/check-docs.php` |
| [`check-double-codage`](check-double-codage.php) | un texte se pose dans une page par nf_texte(), qui décode puis échappe : jamais codé deux fois. | `php tools/check-double-codage.php` |
| [`check-heures`](check-heures.php) | une heure montrée à quelqu'un passe par timetostr(), qui la met dans SON fuseau horaire. | `php tools/check-heures.php` |
| [`check-htaccess`](check-htaccess.php) | les trois configurations livrées (Apache, nginx, Caddy) refusent les mêmes dossiers et fichiers sensibles. | `php tools/check-htaccess.php` |
| [`check-instantanes`](check-instantanes.php) | les SQL livrés s'importent (aucune clé en double) ; l'historique des migrations est complet. | `php tools/check-instantanes.php` |
| [`check-js-lint`](check-js-lint.php) | passe ESLint sur nos sources JavaScript, PHP interpolé neutralisé. | `php tools/check-js-lint.php` |
| [`check-js-sources`](check-js-sources.php) | les sources JavaScript : syntaxe (`node --check`) et vocabulaire (aucun jQuery). | `php tools/check-js-sources.php` |
| [`check-langs`](check-langs.php) | tout ce qui concerne lang() : clés manquantes, formats de date, arguments du pluriel. | `php tools/check-langs.php` |
| [`check-notice`](check-notice.php) | la NOTICE dit qui a écrit chaque addon, sous quelle licence, et ce que le produit embarque d'autrui. | `php tools/check-notice.php` |
| [`check-pagination`](check-pagination.php) | une liste que le checker découpe en pages affiche les liens de ses pages. | `php tools/check-pagination.php` |
| [`check-prerequis`](check-prerequis.php) | les prérequis annoncés sont ceux que l'installation exige, écrits à un seul endroit. | `php tools/check-prerequis.php` |
| [`check-source-hygiene`](check-source-hygiene.php) | aucun caractère invisible dans les sources : contrôle, BOM, espace insécable, `?>` dans un commentaire. | `php tools/check-source-hygiene.php` |
| [`check-strict-types`](check-strict-types.php) | le nombre de fichiers en `declare(strict_types=1)` ne baisse jamais (cliquet). | `php tools/check-strict-types.php` |
| [`check-textes-en-dur`](check-textes-en-dur.php) | aucun texte d'interface écrit en dur en français : tout passe par les traductions. | `php tools/check-textes-en-dur.php` |
| [`check-theme-zones`](check-theme-zones.php) | toute zone qu'un thème déclare est rendue par ses gabarits. | `php tools/check-theme-zones.php` |
| [`check-tools`](check-tools.php) | les outils de `tools/` respectent leurs propres conventions. | `php tools/check-tools.php` |
| [`check-vignettes`](check-vignettes.php) | chaque addon a sa vignette, au bon format, ou une exemption écrite qui dit pourquoi. | `php tools/check-vignettes.php` |
| [`check-widget-reglages`](check-widget-reglages.php) | aucun checker de widget ne lit un réglage sans valeur de repli. | `php tools/check-widget-reglages.php` |
| [`check-wiki-docs`](check-wiki-docs.php) | le wiki livré et celui de la démonstration disent ce que disent les guides. | `php tools/check-wiki-docs.php` |

### Contrôles en navigateur

Ajoutés par `check-all --navigateur`. Ils servent le site, et la plupart ouvrent un Chrome sans interface.

| Outil | Ce qu'il fait | Usage |
|---|---|---|
| [`check-admin-back`](check-admin-back.php) | chaque sous-page d'administration offre un retour au module, fil d'Ariane ou bouton, dans le HTML servi. | `php tools/check-admin-back.php` |
| [`check-contraste`](check-contraste.php) | le contraste WCAG du texte, thème par thème et mode par mode, mesuré dans un navigateur. | `php tools/check-contraste.php` |
| [`check-demo-ecriture`](check-demo-ecriture.php) | éprouve, pour de vrai, ce qu'un visiteur peut et ne peut pas écrire en démo. | `php tools/check-demo-ecriture.php` |
| [`check-journal`](check-journal.php) | sert des pages puis lit le journal PHP, ou relit une fenêtre de temps : rien ne doit s'y être écrit. | `php tools/check-journal.php` |
| [`check-js`](check-js.php) | exécute les épreuves JS de tests/Browser/ dans un vrai navigateur. | `php tools/check-js.php` |
| [`check-js-console`](check-js-console.php) | balaye les pages dans un vrai navigateur et refuse toute erreur JavaScript, violation CSP ou script introuvable. | `php tools/check-js-console.php` |
| [`check-langues-contenu`](check-langues-contenu.php) | un contenu rédigé dans une seule langue doit rester consultable dans les autres. | `php tools/check-langues-contenu.php` |
| [`check-liens`](check-liens.php) | parcourt le site et refuse tout lien interne qui ne mène nulle part. | `php tools/check-liens.php` |
| [`check-responsive`](check-responsive.php) | mesure le débordement horizontal des pages, à plusieurs largeurs. | `php tools/check-responsive.php` |
| [`check-seo`](check-seo.php) | ce que lit un moteur de recherche : robots.txt, plans du site, et l'en-tête des pages qu'ils annoncent. | `php tools/check-seo.php` |
| [`check-service-worker`](check-service-worker.php) | le worker se retire quand on l'éteint, et ne met jamais le HTML en cache. | `php tools/check-service-worker.php` |
| [`check-widget-contract`](check-widget-contract.php) | chaque couple widget/type répond à l'éditeur en direct, et se rend SANS réglages sans rien écrire au journal. | `php tools/check-widget-contract.php` |

### Contrôles à cible explicite

Jamais lancés d'office : chacun exige un argument, une base jetable, ou abîme le site pour l'éprouver.

| Outil | Ce qu'il fait | Usage |
|---|---|---|
| [`capturer-apercus`](capturer-apercus.php) | produit la VIGNETTE de chaque addon, par capture d'écran réelle. | `php tools/capturer-apercus.php` |
| [`check-assistant`](check-assistant.php) | l'assistant d'installation web, joué de bout en bout comme un visiteur, profil par profil. | `php tools/check-assistant.php` |
| [`check-extensions`](check-extensions.php) | chaque addon du marketplace s'installe sur un site qui n'a que le cœur, par le marketplace ou par son archive. | `php tools/check-extensions.php --port=8116` |
| [`check-important`](check-important.php) | mesure quels `!important` d'une feuille servent réellement à quelque chose. | `php tools/check-important.php --feuille=themes/admin/css/style.css` |
| [`check-install-profiles`](check-install-profiles.php) | chaque profil d'installation doit démarrer et répondre, installé pour de vrai. | `php tools/check-install-profiles.php --profil=core` |
| [`check-marketplace`](check-marketplace.php) | le catalogue publié dit-il la vérité sur les archives qu'il propose ? | `php tools/check-marketplace.php` |
| [`check-mise-a-jour`](check-mise-a-jour.php) | un site neuf, à la version précédente, se met à jour par le vrai bouton depuis l'origine publiée, et arrive à la version annoncée. | `php tools/check-mise-a-jour.php` |
| [`check-mise-en-page`](check-mise-en-page.php) | chaque page publique et d'administration, dans chaque thème, chaque mode et à chaque largeur : aucun défaut visuel que le navigateur sait constater. | `php tools/check-mise-en-page.php` |
| [`check-nouveau-venu`](check-nouveau-venu.php) | le README et le guide du contributeur, suivis à la lettre sur une machine vierge, mènent à un site qui tourne et à une batterie verte. | `php tools/check-nouveau-venu.php --dossier=… --depot-neofrag=https://github.com/<org>/<candidate>.git` |
| [`check-parcours`](check-parcours.php) | suit un visiteur d'un écran au suivant, dans un vrai navigateur. | `php tools/check-parcours.php` |
| [`check-prerequis-absents`](check-prerequis-absents.php) | à un PHP auquel manque une extension exigée, l'assistant web et l'installeur en ligne de commande disent laquelle, et s'arrêtent. | `php tools/check-prerequis-absents.php` |
| [`check-reglages`](check-reglages.php) | l'écran de réglages de chaque addon installé s'ouvre, s'enregistre et se rouvre, sans rien écrire au journal. | `php tools/check-reglages.php` |
| [`check-restauration`](check-restauration.php) | éprouve, pour de vrai, le cycle sauvegarde → casse → restauration. | `php tools/check-restauration.php --compte=admin --motdepasse=… --site-jetable` |
| [`check-serveur-web`](check-serveur-web.php) | un vrai serveur web (Apache, nginx, Caddy) refuse ce qu'il doit refuser, et sert le site comme il faut. | `php tools/check-serveur-web.php --url=http://localhost:8080` |
| [`check-smoke`](check-smoke.php) | frappe les flux critiques d'un site qui tourne, et échoue au moindre 5xx. | `php tools/check-smoke.php http://localhost:8080 --email=<membre existant>` |

### Les autres outils

Ils agissent — construire, publier, régénérer, installer — plutôt qu'ils ne vérifient.

| Outil | Ce qu'il fait | Usage |
|---|---|---|
| [`assembler`](assembler.php) | pose les addons à la carte du dépôt extensions dans cet arbre, pour éprouver le produit entier. | `php tools/assembler.php --extensions=../extensions` |
| [`build-release`](build-release.php) | produit les paquets prêts à uploader par FTP (hébergement mutualisé). | `php tools/build-release.php` |
| [`capture`](capture.php) | capture d'écran des pages du site, y compris celles qui exigent une session d'administrateur. | `php tools/capture.php` |
| [`changelog-section`](changelog-section.php) | extrait une section de CHANGELOG.md, la source unique des notes de version. | `php tools/changelog-section.php [<sélecteur>] [--html] [--with-title]` |
| [`check-all`](check-all.php) | lance toute la batterie de contrôles d'un coup, et dit ce qu'elle n'a pas joué. | `php tools/check-all.php` |
| [`ci-install`](ci-install.php) | installation non interactive, pour la CI ou un montage local rapide. | `php tools/ci-install.php` |
| [`dump-demo`](dump-demo.php) | fige l'état de la démo (après tools/seed-demo.php) en install/demo.sql. | `php tools/dump-demo.php` |
| [`dump-schema`](dump-schema.php) | régénère le schéma de référence et le seed d'installation depuis la base vive, en cœur lean. | `php tools/dump-schema.php` |
| [`extract-module-sql`](extract-module-sql.php) | génère le SQL d'install/désinstall embarqué de chaque module. | `php tools/extract-module-sql.php` |
| [`fill-langs`](fill-langs.php) | écrit les traductions qui manquent : celles que le produit possède déjà, puis celles d'un dictionnaire. | `php tools/fill-langs.php modules/quotes` |
| [`find-csp-hash`](find-csp-hash.php) | retrouve le script inline qui correspond à une empreinte CSP. | `php tools/find-csp-hash.php <empreinte> <url> [url…]` |
| [`init-dispositions`](init-dispositions.php) | donne sa mise en page par défaut à chaque thème installé qui n'en a pas. | `php tools/init-dispositions.php` |
| [`installer-addon`](installer-addon.php) | installe un addon déjà présent sur le disque, comme le fait l'installeur du site. | `php tools/installer-addon.php module api` |
| [`maintenance`](maintenance.php) | tâches de maintenance périodiques, à lancer par un cron externe. | `php tools/maintenance.php [trash|accounts|all] [--pretend]` |
| [`migrate`](migrate.php) | le runner des migrations SQL du cœur. | `php tools/migrate.php status` |
| [`package-addons`](package-addons.php) | zippe chaque addon distribuable et génère le catalogue du marketplace. | `php tools/package-addons.php` |
| [`prepare-test-db`](prepare-test-db.php) | prépare la base de données des tests d'intégration. | `php tools/prepare-test-db.php` |
| [`seed-demo`](seed-demo.php) | peuple un site de données de DÉMO réalistes (gaming/communauté). | `php tools/seed-demo.php` |
| [`stan-baseline`](stan-baseline.php) | régénère la liste d'exceptions de PHPStan, et refuse d'y geler une erreur neuve. | `php tools/stan-baseline.php` |
| [`wiki-docs`](wiki-docs.php) | transfère docs/guide/*.md dans le module wiki, puis fige le wiki en install/wiki.sql. | `php tools/wiki-docs.php` |
<!-- catalogue:fin -->

Hors catalogue, un fichier qui n'est pas un outil à lancer : `router-builtin.php`, le routeur que le
serveur intégré de PHP charge quand on sert le site avec `php -S`.

## Les familles, et ce qu'elles coûtent

| Famille | Ce qu'elle exige | Quand elle se joue |
|---|---|---|
| **statique** | rien : elle lit les sources | par défaut, en CI (job `statique`) |
| **navigateur** | une installation configurée, un serveur PHP intégré, souvent un Chrome sans interface | `--navigateur` ; en CI sur une installation montée par `ci-install` |
| **cible** | un argument, une base jetable, une adresse — ou elle abîme le site pour l'éprouver | jamais d'office : `check-all` rappelle sa commande |
| **outil** | selon l'outil | à la main, ou par le workflow de release |

## Les conventions

Elles sont **vérifiées** par `check-tools`, joué dans la batterie : un outil qui s'en écarte fait
échouer la CI. C'est ce qui empêche le dossier de redevenir ce qu'il était le 2026-09-21 — soixante
fichiers écrits chacun à sa manière, quatorze copies du même lancement de serveur, deux styles
d'indentation, trois gardes HTTP manquantes.

1. **L'en-tête** dit tout ce qu'il faut savoir sans ouvrir le code : le nom, une phrase, la famille,
   le *pourquoi* (le défaut réel, daté, qui a justifié l'outil), et l'*usage*. Gabarit plus bas.
2. **La première instruction** est `require __DIR__.'/lib/outil.php';` — c'est lui qui porte la garde
   contre toute exécution hors ligne de commande. Un outil ne l'écrit pas lui-même.
3. **La plomberie vit dans `tools/lib/`**, une fois : ouvrir la base, ouvrir une session
   d'administrateur, servir le site, lancer Chrome, parcourir le dépôt. Un outil qui en recopie une
   ligne est refusé.
4. **Un contrôle conclut par un verdict** en dernière ligne, toujours de la même forme, et sort avec
   un code qui distingue trois situations :

   | Code | Verdict | Sens |
   |---|---|---|
   | 0 | `check-x OK : …` | le contrôle a mesuré, et n'a rien à reprocher |
   | 1 | `check-x ÉCHEC : …` | il a mesuré, et refuse |
   | 2 | `check-x REFUS : …` | il n'a **pas pu** juger : prérequis absent, port occupé, page muette |

   La distinction entre 1 et 2 compte : un contrôle aveugle qui se dirait vert est pire qu'un contrôle
   absent. Une page qui ne rend pas de verdict n'est pas une page sans défaut. Et jamais
   d'`exit("message")` : une chaîne passée à `exit` s'affiche et rend le code **zéro** — la CI a
   enchaîné sur un refus ainsi masqué. `check-tools` le refuse.
5. **Quatre espaces**, jamais de tabulation (`.editorconfig`) ; `declare(strict_types=1)` en ligne 2.
6. **Un port par outil** qui sert le site, réservé dans `NF_PORTS` (`tools/lib/outil.php`), tous
   distincts — deux outils sur le même port interrogent le serveur l'un de l'autre sans le savoir.
   `--port=` puis la variable `NF_PORT` le surchargent.
7. **Tout contrôle neuf est éprouvé à l'envers** avant d'entrer dans la batterie : réintroduire le
   défaut qu'il vise, exiger le refus, remettre en état. Deux contrôles écrits le 2026-09-20 étaient
   faux au premier jet — l'un criait sur ses propres commentaires, l'autre ne voyait plus rien.
   `check-classes-bs4` porte cette épreuve dans son code et la rejoue à chaque lancement.
8. **Le catalogue ci-dessus est engendré**, pas écrit : l'en-tête est la seule source. La table de
   la bibliothèque aussi, depuis la première ligne de chaque fichier de `tools/lib/`.
9. **Chaque fichier dit s'il part dans le dépôt public** : une ligne `Diffusion : publique`, ou
   `Diffusion : interne — <raison>` pour ce qui ne sert que chez nous (notre serveur, notre
   publication, notre site officiel). Un fichier public ne cite jamais un fichier interne — ni dans
   son code, ni dans ses commentaires, ni dans la prose de ce document : la copie publique ne l'a
   pas, le renvoi y serait mort. On nomme le besoin, pas l'outil.

## La bibliothèque commune (`tools/lib/`)

Engendrée elle aussi par `check-tools`, depuis la première ligne de chaque fichier.

<!-- bibliotheque:début -->
| Fichier | Ce qu'il donne | Ce qu'il définit |
|---|---|---|
| [`addons-manifest.php`](lib/addons-manifest.php) | les trois tiers d'addons (cœur, identité, à la carte), dérivés des déclarations. | — |
| [`banc.php`](lib/banc.php) | poser un widget sur une page le temps d'une mesure, puis tout remettre. | `nf_banc_widget()` |
| [`demo.php`](lib/demo.php) | ce que l'instantané de la démonstration ne porte jamais. | `NF_DEMO_REGLAGES_EXCLUS`, `NF_DEMO_MOTIF_SECRET`, `NF_DEMO_REGLAGES_PUBLICS`, `nf_demo_reglages_widget()` |
| [`depot.php`](lib/depot.php) | parcourir les fichiers du dépôt, toujours avec les mêmes exclusions. | `NF_EXCLUS`, `NF_DOSSIERS_PRODUIT`, `NF_DOSSIERS_JS`, `nf_fichiers()`, `nf_parcourir()`, `nf_supprimer()`, `nf_relatif()`, `nf_addons()`, `nf_themes_publics()`, `nf_extensions_absentes()`, `nf_exiger_assemblage()` |
| [`entetes.php`](lib/entetes.php) | ce que l'en-tête d'un outil déclare : sa famille, son usage, sa batterie, sa diffusion. | `NF_FAMILLES`, `NF_DIFFUSIONS`, `nf_diffusion()`, `nf_resume()`, `nf_entete_outil()` |
| [`interdits.php`](lib/interdits.php) | ce qu'un serveur web ne doit jamais servir : les dossiers, les extensions, les fichiers. | `NF_DOSSIERS_INTERDITS`, `NF_EXTENSIONS_INTERDITES`, `NF_FICHIERS_INTERDITS` |
| [`journal.php`](lib/journal.php) | lire le journal PHP d'une installation, classer ses lignes, et les montrer regroupées. | `nf_journal_preparer()`, `nf_journal_taille()`, `nf_journal_depuis_octet()`, `nf_journal_depuis_date()`, `nf_journal_montrer()` |
| [`langues.php`](lib/langues.php) | lire et écrire les fichiers de langue (`langs/<code>.php`) sans les exécuter. | `NF_LANGUES`, `nf_langue_cle()`, `nf_langue_valeurs()`, `nf_langue_cles()`, `nf_langue_echapper()`, `nf_langue_ajouter()`, `nf_langue_jokers()` |
| [`navigateur.php`](lib/navigateur.php) | ouvrir une page dans un Chrome sans interface, et relire ce qu'une sonde y a écrit. | `nf_chrome()`, `nf_chrome_utilisable()`, `nf_chrome_dom()`, `nf_chrome_capture()`, `nf_chrome_commande()`, `nf_sonde_verdict()`, `nf_chrome_menage()` |
| [`outil.php`](lib/outil.php) | le socle que chaque outil de `tools/` charge en première ligne. | `NF_RACINE`, `NF_OK`, `NF_ECHEC`, `NF_REFUS`, `NF_PORTS`, `nf_outil()`, `nf_racine()`, `nf_options()`, `nf_port()`, `nf_temp()`, `nf_avertir()`, `nf_ok()`, `nf_echec()`, `nf_refus()`, `nf_sans_commentaires()` |
| [`paquet.php`](lib/paquet.php) | ce qui a le droit d'entrer dans un paquet d'installation ou de mise à jour. | `NF_PAQUET_RACINE`, `NF_PAQUET_ENGENDRES`, `nf_paquet_exclu()` |
| [`parcours.php`](lib/parcours.php) | suivre les liens internes d'un site servi, sans jamais ouvrir une adresse qui agit. | `NF_ADRESSES_QUI_AGISSENT`, `nf_parcourir_site()` |
| [`profils.php`](lib/profils.php) | ce qu'un site installé selon un profil doit servir, et le vérifier en le frappant. | `NF_ROUTES_COEUR`, `NF_ROUTES_MODULES`, `nf_frapper_profil()`, `nf_tables_hors_profil()` |
| [`routeur-outil.php`](lib/routeur-outil.php) | le routeur du serveur intégré quand c'est un OUTIL qui sert le site. | — |
| [`serveur.php`](lib/serveur.php) | servir le site avec le serveur intégré de PHP, et lui parler en HTTP. | `NF_AGENT`, `nf_serveur()`, `nf_encoder_adresse()`, `nf_http()`, `nf_statut()`, `nf_formulaire()`, `nf_balisage()` |
| [`site.php`](lib/site.php) | l'installation sur laquelle l'outil travaille : sa base, ses réglages, un administrateur. | `nf_config_db()`, `nf_connexion()`, `nf_connexion_admin()`, `nf_scalar()`, `nf_colonne()`, `nf_table_existe()`, `nf_type_id()`, `nf_reglage()`, `nf_reglage_poser()`, `nf_reglage_temporaire()`, `nf_themes_installes()`, `nf_premier_admin()`, `nf_session_admin()`, `nf_session_fermer()`, `nf_mode_demo()`, `nf_theme_temporaire()` |
| [`sql.php`](lib/sql.php) | produire et jouer du SQL depuis la base vive. | `nf_sql_entete()`, `nf_sql_tables()`, `nf_sql_show_create()`, `nf_sql_commentaires_de_colonnes()`, `nf_sql_reposer_commentaires()`, `nf_sql_collation_portable()`, `nf_sql_inserts()`, `nf_sql_upserts()`, `nf_sql_lignes()`, `nf_sql_jouer()`, `nf_sql_jouer_fichier()`, `nf_sql_tuples()`, `nf_sql_valeur()` |
| [`table-map.php`](lib/table-map.php) | quelle table appartient à quel module, pour le SQL embarqué de chaque module. | — |
| [`vierge.php`](lib/vierge.php) | une installation NEUVE, sans contenu, montée le temps d'un outil, puis détruite. | `nf_site_vierge()` |
| [`vignettes.php`](lib/vignettes.php) | le format des vignettes d'addons, et la liste motivée de ceux qui n'en ont pas. | `NF_VIGNETTE_LARGEUR`, `NF_VIGNETTE_HAUTEUR`, `NF_VIGNETTE_POIDS_MAX`, `NF_VIGNETTES_EXEMPTEES`, `nf_vignette_defaut()` |
| [`wiki.php`](lib/wiki.php) | la documentation publique : des guides Markdown (`docs/guide/`) aux pages du module wiki. | `nf_wiki_sections()`, `nf_wiki_convertir()`, `nf_wiki_attendu()` |
<!-- bibliotheque:fin -->

Chaque fonction est documentée dans son fichier, avec le défaut qui l'a fait naître.

## Écrire un contrôle

```php
<?php
declare(strict_types=1);

/**
 * check-<nom> — une phrase qui dit ce qu'il refuse.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le défaut réel, daté, que rien ne voyait, et ce qu'il a coûté.
 *
 * Usage
 * -----
 *   php tools/check-<nom>.php
 *   php tools/check-<nom>.php --verbeux
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o] = nf_options(['verbeux' => FALSE]);

$fautes = [];

foreach (nf_fichiers(NF_DOSSIERS_PRODUIT, ['php']) as $relatif => $chemin)
{
    // … mesurer, et remplir $fautes avec [fichier, ligne, quoi]
}

if (!$fautes)
{
    nf_ok('rien à reprocher, sur N fichiers');
}

foreach ($fautes as [$fichier, $ligne, $quoi])
{
    printf("  ✗ %s:%d — %s\n", $fichier, $ligne, $quoi);
}

nf_echec(count($fautes).' défaut(s)');
```

Une ligne ` * Batterie : --toutes` dans l'en-tête donne à `check-all` les arguments à passer. Un
contrôle qui sert le site déclare `Famille : navigateur`, réserve son port dans `NF_PORTS`, et
passe par `nf_serveur()` ; sa sonde JavaScript, s'il en a une, est écrite dans un fichier que le
routeur injecte avec le nonce de la page (`NF_OUTIL_SONDE`, `NF_OUTIL_SONDE_OU`).

Puis : l'éprouver à l'envers (règle 7), et `php tools/check-tools.php --ecrire` pour le catalogue.

## Les variables d'environnement

| Variable | Rôle |
|---|---|
| `NF_DB_HOST`, `NF_DB_PORT`, `NF_DB_USER`, `NF_DB_PASS`, `NF_DB_NAME` | surchargent `config/db.php` (intégration continue, base jetable) |
| `NF_DB_NAME_PREFIX` | préfixe des bases jetables de `check-install-profiles` |
| `NF_TEST_DB_*` | la base des tests d'intégration (`prepare-test-db` les écrit dans `config/db-test.php`) |
| `NF_PORT` | le port du serveur intégré, pour tout outil, quand `--port=` n'est pas passé |
| `NF_CHROME`, `NF_BROWSER` | le chemin d'un Chromium, si l'outil ne le trouve pas seul |
| `NF_NODE` | le chemin de `node`, pour `check-js-sources` |
| `NF_ADMIN_EMAIL`, `NF_ADMIN_PASS` | l'administrateur créé par `ci-install` |
