# Le framework

Référence des briques que tu manipules en écrivant des addons. Pour l'architecture interne complète,
voir [`docs/architecture.md`](../architecture.md). Tout ce qui suit est vérifié contre le code.

## Le service locator — `NeoFrag()`

Tout le framework est accessible via le singleton global `NeoFrag()` et, dans une classe d'addon, via
`$this` (qui y délègue). Les services s'obtiennent par méthodes magiques :

```php
$this->db        // accès base de données
$this->config    // configuration du site (nf_name, nf_default_theme…)
$this->user      // membre courant
$this->url       // requête / segments / base
$this->lang(...) // traduction
$this->events    // événements internes (fire / on)
$this->module('forum');        // un module — NULL s'il n'est pas installé
NeoFrag()->model2('addon');    // un modèle
```

> `__call` **avale les appels inconnus** : `$this->methode_inexistante()` ne casse rien. Pour éprouver
> un contrôle, injecte un défaut que la magie ne rattrape pas (une fonction globale absente).

## Routing

Un module déclare ses routes dans `__info().routes` : `motif => méthode`.

```php
'routes' => [
    ''                 => 'index',   // page d'accueil du module
    '{id}/{url_title}' => '_show',   // /module/42/slug
    'admin{pages}'     => 'index',   // administration paginée
],
```

- Placeholders **fixes** : `{id}` (entier), `{key_id}`, `{url_title}` (slug), `{url_title*}`, `{page}`
  et `{pages}` (pagination). Un placeholder inconnu → 404 silencieux.
- Cycle : le **checker** (`controllers/checker.php`) valide la route et charge les données ; ce qu'il
  **retourne** devient les arguments de la méthode homonyme du **contrôleur** (`controllers/index.php`).
- Le routeur d'aujourd'hui est `route → module → page → 404`. Les **régions nommées** des thèmes
  (`region('content')`) sont la fondation du futur routage centré sur les pages (outlines, routes
  réservées), pas encore écrit.

> En test ou avec `curl`, le CMS traite les agents non-navigateur comme des robots et **n'ouvre pas la
> session** : envoie un **User-Agent de navigateur**.

### Un refus de checker se lit dans le journal

Quand un checker échoue — champ POST manquant, cible inexistante — le site répond **404** ; le motif
(route, checker, méthode, champ absent et ce qui est arrivé à la place) est **toujours journalisé**
(`[checker] …`). Quand le débogage est visible — l'outil de débogage allumé, pour un administrateur
connecté —, le motif est rendu au client et le statut est **400** : « ta requête est mal formée » plutôt
que « cette adresse n'existe pas ». `post_check('a', 'b?')` : le suffixe `?`
rend un champ facultatif (`NULL` s'il manque).

## Base de données

Query builder fluide ; les tables portent le préfixe `nf_`.

```php
$rows = $this->db
    ->select('id', 'title', 'created')
    ->from('nf_news')
    ->where('published', '1')
    ->where('author_id', $user_id)
    ->order_by('created DESC')
    ->limit(10)
    ->get();                 // tableau de lignes ; ->row() pour une seule

$id = $this->db->insert('nf_news', ['title' => $t, 'body' => $b]);  // renvoie l'id
$this->db->where('id', $id)->update('nf_news', ['title' => $t2]);
$this->db->where('id', $id)->delete('nf_news');

$this->db->transaction(); … $this->db->commit();   // ou ->rollback()
```

- **`table_exists('nf_teams')`** — vrai si la table existe. Le paquet s'installe à la carte : un module
  du cœur qui lit la table d'un module optionnel **doit** se garder ainsi (ou déclarer la dépendance
  dans `requires`). Résultat mis en cache pour la requête.
- **`import($sql)`** — exécute du SQL multi-instructions (DDL d'installation) hors du pipeline des
  requêtes préparées.
- Une **jointure qui ne sert qu'à compter ou à décorer doit être `LEFT`** : sept listes faisaient
  disparaître tout contenu sans enfant (un forum sans message, un album sans image) par une jointure
  stricte.
- Un **compteur** (vues, clics) sur une table dont `updated_at` porte `ON UPDATE current_timestamp()`
  réécrit la date de modification à chaque visite : la page du wiki annonçait « modifiée le » à l'heure
  du dernier lecteur. Écris la colonne sur elle-même — `SET views = views + 1, updated_at = updated_at`
  — ; `check-db-compteurs` refuse l'oubli.
- `MATCH … AGAINST` (FULLTEXT) **ne voit pas** une ligne insérée dans une transaction non validée :
  InnoDB n'indexe qu'au commit. Un test de recherche FULLTEXT ne peut pas s'envelopper dans la
  transaction annulée du socle de test.

Pour les entités gérées (addons, fichiers…), passe par les **modèles** : `NeoFrag()->model2('addon')`,
`NeoFrag()->model2('file', $id)->delete()` ; pour itérer un ensemble typé,
`NeoFrag()->collection('addon')->get()`.

## Formulaires — `form()` & `form2()`

Deux API coexistent (décision actée : la v1 est gelée, pas migrée en masse) :

- **`form()`** — l'API des **écrans d'administration** : champs déclarés en tableau via
  `add_rules([...])`, traitée par `is_valid($post)`, rendue par `->display()`. Exemple complet dans
  [Créer un module](create-a-module.md) (§7).
- **`form2()`** — fluide, chaque champ est un objet `form_*()`. Pour les formulaires **publics** ou
  **riches**, et la **seule** qui valide un formulaire de **confirmation seule** (sans champ) — une
  confirmation de suppression n'accepte **que** le champ `delete` : une case ajoutée la ferait échouer
  en silence.

```php
return $this->form2()
    ->rule($this->form_text('title')->title($this->lang('Titre'))->required())
    ->rule($this->form_textarea('body')->title($this->lang('Contenu')))
    ->captcha()
    ->success(function ($data) {
        $this->db->insert('nf_news', $data);
        notify($this->lang('Enregistré'));
    })
    ->submit($this->lang('Publier'));
```

Les deux portent leur propre jeton CSRF. Une action déclenchée par un **lien** ou un **POST écrit à la
main** doit le vérifier elle-même : `csrf_url()`, `check_csrf()`, `csrf_token()` (trait `Admin_Helpers`) ;
hors d'un contrôleur, le même jeton se lit par `nf_jeton_csrf()`.

**L'éditeur riche** — le type `editor` en `form()`, `form_textarea()->editor()` en `form2()` — est
TinyMCE 7, auto-hébergé ; le HTML qui en sort passe par `sanitize_html()`. Une image **collée ou glissée**
y est envoyée au site (`ajax/user/editeur-image` : membre connecté, jeton de session, débit borné), puis
insérée par son adresse définitive. Le site lit son vrai type dans ses octets (JPEG, PNG, GIF, WebP),
refuse un fichier qui porte du code, borne le poids (5 Mo) et les dimensions, la **ré-encode** — plus de
métadonnées, l'orientation d'une photo appliquée — et la range sous `upload/editeur/AAAA/MM/` avec un nom
aléatoire. Un `tinymce.init()` écrit à la main (un gabarit, un écran à part) place
`Editeur_Images::tinymce()` en tête de ses réglages (`NF\NeoFrag\Libraries\Editeur_Images`) : sans lui,
une image collée reste cassée, puis disparaît à l'enregistrement. `EditeurImagesTest` le vérifie pour
tout le produit.

**Le captcha** — `->captcha()` en `form2()`, `add_captcha()` en `form()` — affiche le fournisseur choisi
dans *Paramètres → Captcha* et vérifie sa réponse côté serveur ; un membre connecté n'en voit pas. Le
défaut est **ALTCHA** : hébergé par le site, sans clé ni cookie, il fait faire au navigateur un petit
calcul (environ une seconde) que le serveur vérifie, et refuse qu'une même solution serve deux fois.
Turnstile, hCaptcha et reCAPTCHA v2 demandent leurs deux clés ; sans elles, ALTCHA prend le relais.
Ailleurs qu'un formulaire : `$this->captcha->element()` pour l'afficher, `$this->captcha->is_valid()`
pour vérifier (`NF\NeoFrag\Libraries\Captcha`).

## Tables — `table2()`

Rend des listes paginées, **triables par clic sur l'en-tête** (Maj + clic : tri multi-colonnes ;
Ctrl + clic : retirer) et filtrables, à partir d'une requête. Exemple : la liste des membres,
`modules/user/controllers/admin.php`. Le tri est servi par `js/table2.js` en vanilla.

**Une liste découpée en pages affiche ses liens de pages.** Quand un checker découpe une liste —
`->paginate($page)` sur une collection, `->pagination->get_data($lignes, $page)` sur un tableau —, la
méthode du contrôleur qui la rend appelle `->pagination->get_pagination()` (ou passe par `table2()`,
qui la rend lui-même). Sans cela, seuls les premiers éléments sont visibles et les autres
inatteignables. Une liste qui tient toujours sur une page le dit par un commentaire
`// pagination : <raison>` dans la méthode. `check-pagination` le vérifie.

## Traductions

`$this->lang('Clé')` renvoie la traduction. Les fichiers `langs/fr.php` mappent le **crc32b de la
clé source** vers la traduction :

```php
return [
    hash('crc32b', 'Bienvenue') => 'Bienvenue',   // ou directement '467f39a3' => '…'
];
```

- `$this->lang('%d élément(s)', $n)` accepte des arguments (style `sprintf`).
- Le **français est la langue source** ; six langues sont livrées et `tools/check-langs.php --toutes`
  refuse une clé manquante dans l'une d'elles. `--fix` ajoute les clés manquantes au **français seul**.
- **Ne traduis jamais du contenu de la base** (un titre écrit par l'administrateur) : `lang()` le
  cherche en vain et journalise un avertissement à chaque visite. Emballe-le dans
  `$this->no_translate(...)`.

## Dates et fuseaux horaires

Le produit **enregistre** ses dates dans le fuseau qu'a PHP au démarrage — l'heure universelle en
production — et ne le change jamais : la base aligne sa session sur lui (`drivers/mysqli.php`).
Il les **affiche** dans le fuseau de celui qui regarde, donné par `nf_fuseau()` : le choix du
membre (`nf_user_profile.timezone`, appliqué à l'ouverture de la session), sinon son navigateur
(cookie `nf_fuseau`, posé par `views/theme/main.tpl.php`), sinon le réglage du site
(`nf_timezone`), sinon le fuseau d'enregistrement. Tout est dans `neofrag/helpers/time.php`.

- **Écrire en base** : `date('Y-m-d H:i:s')`, `NOW()` ou `now()` — le fuseau d'enregistrement.
- **Afficher** : `timetostr($format, $quand)` ou la bibliothèque Date (`$this->date($quand)`) ;
  jamais `date()`, que `check-heures` refuse hors des formats de machine. Pour une colonne date et
  heure lue telle quelle (une liste, une fiche) : `nf_date_heure($valeur)` — `d/m/Y H:i` dans la
  langue et le fuseau du visiteur, une chaîne vide si la valeur l'est ; pour une date seule,
  `nf_date($valeur)`. Jamais un format chiffré écrit en dur dans `timetostr()` (`'d/m/Y'`,
  `'Y-m-d H:i'`) : l'allemand écrit `02.10.2026`, et `check-heures` le refuse.
- **Lire une saisie** : les champs `datetime` des deux systèmes de formulaires convertissent
  d'eux-mêmes ; à la main, `nf_heure_saisie()` (et `nf_heure_affichee()` pour l'inverse).
- **Une date seule ou une heure seule** (`Y-m-d`, `H:i`) n'est pas un instant : `timetostr()` et
  Date ne la convertissent pas. Une journée entière enregistrée dans une colonne date et heure se
  passe en `Y-m-d` (cf. `Calendar::format_dt()`).
- **Une heure à l'heure du site**, quel que soit le visiteur (une grille de programmes) :
  `new \DateTime('now', nf_fuseau_site())`.

## Le front : Bootstrap 5, vanilla, CSP stricte

- **Bootstrap 5.3**, **sans jQuery**. Les classes de grille portent toujours un point de rupture
  (`col-12 col-lg-8`) ; `tools/check-classes-bs4.php` refuse les classes de Bootstrap 4.
- **CSP stricte à nonce** : chaque réponse HTML porte un nonce aléatoire, posé sur tous les `<script>`
  inline par le filtre d'`index.php`, et `script-src` n'autorise que `'self'`, ce nonce, les origines
  du fournisseur de captcha **actif** — aucune avec ALTCHA, le défaut — et Google Analytics **si** un
  identifiant est configuré. **Aucun script depuis un CDN.** Le JS inséré dynamiquement passe par `NF.setHtml` / `NF.insertHtml` / `NF.replaceHtml`, qui
  ré-exécutent les `<script>` ajoutés avec le bon nonce, y compris dans l'iframe de l'éditeur en direct.
- **`window.NF`**, défini dans le gabarit principal, remplace les quelques primitives dont on avait
  besoin — et rien de plus (ce n'est pas un mini-jQuery) :

| Primitive | Rôle |
|---|---|
| `NF.ready(fn)` | exécute `fn` quand le DOM est prêt (ou tout de suite s'il l'est déjà) |
| `NF.data(el, 'ma-cle')` | lit `data-ma-cle` avec la coercition de jQuery (nombre, booléen, JSON) |
| `NF.ajax({url, method, data, dataType, headers, signal})` | `fetch` avec `X-Requested-With` sur la même origine, corps `form-urlencoded`, tableaux en `clé[]` ; **rejette** sur un statut d'erreur ; `dataType: 'text'` sinon JSON |
| `NF.post(url, data)` | raccourci POST |
| `NF.setHtml(el, html)` | `innerHTML` + exécution des scripts |
| `NF.insertHtml(cible, position, html)` | `insertAdjacentHTML` + exécution des seuls scripts ajoutés |
| `NF.replaceHtml(el, html)` | remplace l'élément, scripts exécutés |
| `NF.runScripts(root)`, `NF.loadScript(src)` | ré-exécuter, charger avec le nonce |
| `NF.bandeaux()` | range les bandeaux du haut de page et republie `--nf-haut` et `--nf-entete` (cf. *Créer un thème*) ; à rappeler après avoir ajouté un bandeau |

- Les modales (`js/modal.js`), les notifications (`js/notify.js`) et les confirmations (`js/confirm.js`)
  sont chargées par les thèmes.
- Deux contrôles gardent ce front : `check-js-sources` (syntaxe, PHP interpolé toléré, et aucun `$(`),
  `check-js-console` (les vraies pages, dans un vrai navigateur : aucune erreur, aucune violation CSP).
  Le harnais `tests/Browser/*.test.html` + `tools/check-js.php` éprouve le **contrat** d'un script sur
  le vrai fichier, avec des doubles de `fetch`.

## Sécurité : ce qui existe déjà

Avant d'écrire le tien : `Rate_Limit` (par clé, ex. `contact:ip:<ip>`), `Audit_Log` (actions
sensibles), `File_Jail` (chemins d'upload), `Moderation`, TOTP pour la double authentification,
`sanitize_html()` (HTMLPurifier) pour le HTML riche, `is_dangerous_upload()`, et le mode démo
(`nf_demo()`) qui verrouille l'écriture des modules sensibles.

**Une adresse saisie par quelqu'un et placée dans un `href` ou un `src`** passe par
`nf_url_sure($url)` : elle rend `FALSE` pour un schéma autre que `http`, `https`, `mailto`, `tel` ou
`geo` — un `javascript:` s'exécuterait au clic avec les droits de celui qui clique. Une adresse relative
passe. `htmlspecialchars()` n'y suffit pas : il échappe les guillemets, pas le schéma. `url()` rend
`#` pour une adresse absolue refusée.

### Le site de démonstration

La démonstration est partagée : son compte `demo` est administrateur. Le filet est dans
`neofrag/helpers/system.php`, et tient devant tout contrôleur (`core/output.php`) :

- **`NF_DEMO_MODULES_VERROUILLES`** — les modules dont aucune action ne passe en démo : tout POST,
  tout lien porteur d'un jeton (`?_=`), toute méthode dont le nom contient un mot d'action
  (`NF_DEMO_MOTS_D_ACTION` : export, test, toggle…) est refusé — un 403 en JSON pour un appel AJAX,
  sinon une notification et un retour à la page précédente (`nf_demo_requete_refusee()`).
  **Un module neuf qui touche à la configuration du site s'ajoute à cette liste** : c'est une liste de
  refus, un module absent est ouvert. `check-demo-lock` le rappelle.
- **`NF_DEMO_LECTURES`** — les rares lectures permises dans un module verrouillé
  (`controleur::methode`), qui rendent alors des exemples fictifs.
- **`nf_demo_ecriture_permise($module)`** — la question à poser dans un code qui écrit hors du filet ;
  vraie hors démonstration.
- **`nf_compte_masque()`** — le compte de secours de la démonstration, qu'aucune liste de membres ne
  montre : `->where('u.id !=', nf_compte_masque())`. Hors démonstration, il vaut 0.
- En démonstration, `File::delete()` ne supprime rien et l'envoi de fichiers est refusé. Une page qui
  montrerait un fichier, un journal ou un réglage sensible montre un exemple à la place.
- Le compte partagé ne se modifie pas depuis l'espace membre : ni identifiant, ni adresse, ni mot de
  passe, ni double authentification, ni suppression — un visiteur le fermait aux autres jusqu'à la
  remise à zéro. Le filet ne couvrant que l'administration, ces pages font le test elles-mêmes
  (`nf_demo()`, `modules/user/controllers/index.php`).

## Événements

`$this->events->fire('forum.post.created', $message_id, $topic_id, $user_id)` côté émetteur ;
`$this->events->on('forum.post.created', function (...) { … })` côté abonné. Les écouteurs restent
actifs pour toute la requête.

## Helpers utiles

- `url('forum/42')` — construit une URL routée (préfixe de langue inclus).
- `notify('Message')` — notification à l'écran après redirection.
- `htmlspecialchars(...)` / `sanitize_html(...)` — échappement / nettoyage anti-XSS.
- `nf_url_sure($url)` — une adresse saisie peut-elle aller dans un lien (cf. Sécurité).
- `nf_date_heure($valeur)` / `nf_date($valeur)` — une date de la base, à montrer (cf. Dates).
- `nf_demo()` — vrai sur le site de démonstration.

## Migrations

Le schéma du **cœur** évolue par migrations versionnées, suivies dans `nf_migrations` :

```bash
php tools/migrate.php status                 # appliqué / en attente
php tools/migrate.php up [--pretend]          # applique (simulation avec --pretend)
php tools/migrate.php down [--step=N]         # annule
php tools/migrate.php baseline --until=NAME   # adopter une base existante
```

Fichiers : `migrations/AAAA_MM_JJ_nom.up.sql` (+ `.down.sql`). Le DDL MySQL est auto-commit →
**sauvegarde avant `up` en production**. Les **addons** ont leurs propres migrations
(`<addon>/install/migrations/`, suivies dans `nf_addon_migrations`) — voir
[Créer un module](create-a-module.md).

**Un code neuf applique seul ses migrations**, une fois, à la première page vue après la mise à jour —
par le bouton comme par FTP : celles du cœur, puis celles des addons installés (`nf_migrations_du_code()`,
appelée par `index.php` quand le réglage `nf_migrations_version` diffère de `NEOFRAG_VERSION`). Cela vaut
pour tout site, démonstration comprise. Un échec n'empêche pas la page de s'afficher : il est journalisé,
et la tentative suivante attend dix minutes.

## Surcharge à trois niveaux

Résolution des vues, classes et fichiers (premier trouvé gagne) :

1. `overrides/{type}/{fichier}` — global
2. `themes/{thème actif}/overrides/{type}/{fichier}` — par thème
3. l'original livré

Tu personnalises sans forker et sans casser les mises à jour. Attention : deux fichiers homonymes à deux
niveaux, et `path()` sert l'un ou l'autre selon l'appelant — un assistant s'est affiché sans style à
cause d'un doublon (`tools/check-assets.php` le refuse désormais).

## Environnement de développement

Le projet tourne sur **tout PHP 8.2+ avec MySQL ou MariaDB** (Apache + `mod_rewrite`, ou nginx, ou
Caddy). La voie de référence du projet est une **installation d'épreuve sur un serveur**, où tourne
la batterie complète : `php tools/check-all.php --navigateur`, `vendor/bin/phpunit --fail-on-skipped`
et `composer stan`. L'environnement, la base de test et la boucle de travail sont décrits dans
[`docs/development.md`](../development.md) ; chaque outil est décrit dans
[`tools/README.md`](../../tools/README.md).
