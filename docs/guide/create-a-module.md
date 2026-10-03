# Créer un module

Un **module** est une fonctionnalité complète : ses pages publiques, ses routes, son administration,
ses données et ses permissions. C'est l'addon le plus riche.

Nous allons créer un module `notes` qui affiche une liste de notes publiques. Tout ce qui suit est
vérifié contre le code ; les modules livrés (`modules/contact`, `modules/news`)
sont les meilleurs exemples à lire ensuite.

## Structure des fichiers

```
modules/notes/
├── notes.php                 # classe : métadonnées, déclarations, routes, permissions
├── controllers/
│   ├── checker.php           # valide la route et charge les données
│   ├── index.php             # rend les pages publiques
│   └── admin.php             # interface d'administration (optionnel)
├── install/
│   ├── install.sql           # tables du module (joué à l'installation, idempotent)
│   ├── uninstall.sql         # suppression des tables
│   └── migrations/           # évolutions du schéma entre deux versions (optionnel)
├── views/
│   └── index.tpl.php
├── css/                      # optionnel
├── js/                       # optionnel — vanilla, jamais jQuery
└── langs/
    └── fr.php                # traductions (optionnel)
```

## 1. La classe — `notes.php`

```php
<?php
namespace NF\Modules\Notes;

use NF\NeoFrag\Addons\Module;

class Notes extends Module
{
    protected function __info()
    {
        return [
            'title'       => $this->lang('Notes'),
            'description' => $this->lang('Petites notes publiques.'),
            'icon'        => 'fas fa-note-sticky',   // doit exister dans le FontAwesome embarqué
            'author'      => 'Ton Nom',
            'license'     => 'LGPLv3',
            'version'     => '1.0.0',
            'admin'       => TRUE,                   // expose une page d'administration

            // Déclarations de découplage — OBLIGATOIRES (la CI refuse un addon muet).
            'core'        => FALSE,                  // TRUE = livré toujours et non désinstallable
            'presets'     => ['communaute'],         // profils d'installation qui le pré-cochent
            'requires'    => [],                     // addons dont il a BESOIN (sans eux, il casse)

            'routes'      => [
                ''                 => 'index',       // /notes
                '{id}/{url_title}' => '_show',       // /notes/42/ma-note
            ],
        ];
    }
}
```

### Les trois déclarations de découplage

Depuis le 2026-09-15, le paquet s'installe **à la carte** : l'installeur propose des profils
(*Complet*, *Gaming / eSport*, *Communauté*, *Association / club*, *Cœur seul*) composés **à partir de ces déclarations**.
Aucune liste n'est écrite à la main ailleurs.

- `core` — `TRUE` pour un module d'infrastructure ou de CMS livré toujours et non désinstallable ;
  `FALSE` pour tout ce qui est optionnel. Un module du cœur ne peut **jamais** dépendre d'un
  optionnel (règle 3 de `tools/check-addon-declarations.php`).
- `presets` — les profils qui le pré-cochent : `'gaming'`, `'communaute'`, `'association'` (les étiquettes de
  `install/lib/presets.php`). Vide : il n'apparaît
  que dans le profil *Complet*.
- `requires` — les addons dont il a besoin **pour ne pas casser** : une table lue, une classe nommée.
  L'installeur les ajoute d'office quand on coche ton module. Une dépendance **molle** (un service qui
  peut manquer) ne se déclare pas ici : elle se **garde** dans le code (voir §5).

Ces trois clés sont vérifiées statiquement en CI, ainsi que l'existence de l'icône déclarée (règle 7 :
FontAwesome a renommé des icônes entre la 5 et la 6). Un addon qu'on ne veut pas au catalogue du
marketplace ajoute `'distributed' => FALSE`.

### Les routes

Une route mappe un **motif d'URL** vers une **méthode de contrôleur**. Le motif est relatif au nom du
module (`''` = la page d'accueil du module, ici `/notes`). Les routes d'administration commencent par
`admin` (`'admin{pages}' => 'index'` pour une liste paginée sous `/admin/notes`).

> Les placeholders sont un ensemble **fixe** : `{id}` (entier), `{key_id}`, `{url_title}` (slug),
> `{url_title*}` (slug avec des `/`), `{page}` et `{pages}` (pagination). Un placeholder inconnu
> produit un 404 silencieux. Pour une fiche, le motif idiomatique est `{id}/{url_title}` : l'`id`
> identifie la fiche, le slug est cosmétique.

## 2. Le checker — `controllers/checker.php`

Le **checker** s'exécute avant le contrôleur : il valide la route et charge les données. Renvoyer
`FALSE` ou rien déclenche un 404. Ce qu'il **retourne devient les arguments** de la méthode de même
nom du contrôleur.

```php
<?php
namespace NF\Modules\Notes\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
    public function index()
    {
        $notes = $this->db->select('id', 'title', 'body')
                          ->from('nf_notes')
                          ->order_by('id DESC')
                          ->get();

        return [$notes];   // → index($notes)
    }

    public function _show($id, $url_title)
    {
        if ($note = $this->db->from('nf_notes')->where('id', $id)->row())
        {
            return [$note];   // → _show($note)
        }
        // rien : 404
    }
}
```

Pour un point d'entrée qui reçoit un **POST** (AJAX, formulaire écrit à la main), le checker lit les
champs avec `post_check('titre', 'corps', 'options?')` : chaque nom est **obligatoire**, sauf s'il porte
le suffixe `?`, auquel cas il vaut `NULL` s'il manque. Un champ obligatoire absent fait échouer le
checker : réponse **404** en production (le motif exact — quel champ, ce qui est arrivé à la place —
est **journalisé**) et **400 avec le motif** quand le débogage est visible (un administrateur connecté,
l'outil de débogage allumé). Ce comportement vient de dix widgets qu'on
ne pouvait plus ajouter dans l'éditeur en direct parce qu'un champ facultatif était exigé.

## 3. Le contrôleur public — `controllers/index.php`

```php
<?php
namespace NF\Modules\Notes\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
    public function index($notes)
    {
        $this->title($this->lang('Notes'))
             ->icon('fas fa-note-sticky')
             ->breadcrumb();

        return $this->css('notes')->view('index', ['notes' => $notes]);
    }
}
```

`title()`, `icon()`, `breadcrumb()` renseignent l'en-tête de page. Le contrôleur rend ensuite sa vue,
ou un panneau via `$this->panel()->title()->body($html)` pour du HTML construit en PHP.

> **Ne passe jamais du contenu de la base par `$this->title()` puis par la traduction.** Un titre écrit
> par l'administrateur n'a pas de traduction : `lang()` le cherche en vain et journalise un
> avertissement à chaque visite dans une autre langue. Emballe ce qui vient de la base dans
> `$this->no_translate(...)`.

## 4. La vue — `views/index.tpl.php`

```php
<div class="notes">
    <?php if (empty($notes)): ?>
        <div class="alert alert-info"><?php echo $this->lang('Aucune note.') ?></div>
    <?php else: foreach ($notes as $n): ?>
        <article class="note">
            <h3><?php echo htmlspecialchars($n['title']) ?></h3>
            <p><?php echo htmlspecialchars($n['body']) ?></p>
        </article>
    <?php endforeach; endif ?>
</div>
```

Les variables passées à `view()` sont disponibles directement. **Échappe toujours** ce qui vient de la
base (`htmlspecialchars`) ; pour du HTML riche saisi par un membre, `sanitize_html()` (HTMLPurifier). Une
**adresse** saisie qui finit dans un `href` passe en plus par `nf_url_sure()`, qui refuse `javascript:`
et les autres schémas exécutables (cf. [Le framework](framework.md#sécurité--ce-qui-existe-déjà)).

**Le front est Bootstrap 5, sans jQuery, sous une CSP stricte.**
- Les classes de grille s'écrivent `col-12 col-lg-8`, jamais `col-8` seul (qui s'applique dès 0 px et
  casse le téléphone) ; `tools/check-classes-bs4.php` refuse les classes Bootstrap 4 disparues.
- Un `<script>` inline dans une vue reçoit automatiquement le **nonce** de la réponse (filtre
  d'`index.php`) ; un fichier JS se charge par `->js('nom')` (dossier `js/` du module).
- Écris le JavaScript avec les primitives du cœur — `NF.ready`, `NF.data(el, 'clé')`,
  `NF.ajax({url, method, data, dataType})`, `NF.post(url, data)`, `NF.setHtml`, `NF.insertHtml`,
  `NF.replaceHtml` — jamais `$(…)` : jQuery n'est plus chargé, et `tools/check-js-sources.php`
  refuse tout appel. Les adresses dont le script a besoin se posent en `data-*` sur le balisage plutôt
  qu'en PHP interpolé dans le `.js`.

## 5. Les données

Un module **livre ses tables** dans `install/install.sql` (et leur suppression dans `uninstall.sql`).
Ce SQL est joué **à l'installation du module** (assistant d'installation, scan de l'administration,
ZIP du marketplace), idempotent grâce à `CREATE TABLE IF NOT EXISTS` :

```sql
-- modules/notes/install/install.sql
CREATE TABLE IF NOT EXISTS nf_notes (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body  TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

> **Pas de tables propres ? Pas de `install.sql`.** Un module qui réutilise des tables du cœur (par
> exemple `nf_file` via `model2('file')`) s'installe sans toucher au schéma.

### Faire évoluer le schéma — migrations par addon

`install.sql` crée les tables manquantes mais ne **modifie pas** une table déjà présente chez les
utilisateurs. Pour un changement de schéma entre deux versions de ton module :

```
modules/notes/install/migrations/2026_10_01_add_pinned.up.sql
```
```sql
ALTER TABLE nf_notes ADD COLUMN pinned TINYINT(1) NOT NULL DEFAULT 0;
```

- Nom = **préfixe daté** (`AAAA_MM_JJ_description`), exécution dans l'ordre.
- Suivi dans `nf_addon_migrations` : chaque migration ne s'exécute **qu'une fois**.
- À l'installation neuve (`install.sql` porte déjà le schéma à jour), les migrations sont
  **baselinées** — marquées sans être jouées. À la mise à jour (marketplace → « Mises à jour »),
  seules les nouvelles sont **exécutées** (`Addon::update()`). Un module livré avec le cœur reçoit les
  siennes au premier passage du code neuf (`nf_migrations_du_code()`, cf. [le framework](framework.md#migrations)).
- Règle d'or : une migration pour une vraie évolution d'un schéma **déjà livré** ; une nouvelle table
  va dans `install.sql`, jamais dans une migration.
- Sur le site de démonstration, une mise à jour d'addon lancée depuis l'administration ne joue rien
  (`Addon::migrate()` sort quand `nf_demo()` est vrai) ; les migrations arrivées avec un code neuf s'y
  appliquent comme ailleurs.

Le dossier `migrations/` **à la racine** du projet est réservé aux évolutions transverses du cœur.

### Dépendre d'un autre module sans casser

Le paquet s'installe à la carte : le module dont tu as besoin peut **ne pas être là**. Quatre façons
de dépendre, et ce qui se passe s'il manque (`tools/check-addon-coupling.php` les relève au tokenizer) :

| Moyen | Si l'addon manque | À faire |
|---|---|---|
| lire **sa table** (`from('nf_teams')`) | **fatal** — « Table doesn't exist » | le déclarer dans `requires`, ou garder par `$this->db->table_exists('nf_teams')` et **annoter** |
| nommer **sa classe** | **fatal** — « Class not found » | idem |
| appeler **son service** (`$this->module('teams')`, `model2('team')`) | tolérant : rend `NULL` | tester le retour : `if ($teams = $this->module('teams'))` |
| écrire **son URL** (`url('teams/…')`) | cosmétique : lien mort | acceptable |

Un couplage fatal **ni déclaré ni annoté fait échouer la CI**. L'annotation se pose à l'endroit exact,
en commentaire : `// couplage(teams): purge à la suppression d'un jeu — gardé par table_exists()`, ou
en tête de fichier `couplage(teams): …` quand les références y sont étalées.

### Types de contenu : réactions, abonnements, révisions, corbeille

Si ton module publie du **contenu** (comme les actualités, les articles, les sujets), déclare-le : les
modules transverses — réactions, notifications, révisions, corbeille, gamification — le collectent au
lieu de porter chacun leur propre liste de tables.

```php
public function declare_content_types()
{
    return [
        'note' => [
            'table' => 'nf_notes', 'pk' => 'id', 'author' => 'user_id',
            'reactable' => TRUE, 'subscribable' => TRUE, 'revisable' => FALSE,
        ],
    ];
}
```

Exemple réel : `modules/news/news.php`.

### Les carrefours : statistiques, activité, tableau de bord, recherche, plan du site

Un module se branche sur une page qui **agrège** en posant un contrôleur du nom du carrefour. Le
carrefour appelle la méthode du même nom **sans rien vérifier** : renommée, rendue privée ou dotée d'un
paramètre obligatoire de plus, l'erreur n'apparaît qu'à l'ouverture de la page — c'est pourquoi
`tools/check-addon-contracts.php` fige ces contrats en CI.

| Contrôleur | Méthode attendue | Appelé par |
|---|---|---|
| `controllers/statistics.php` | `statistics()` | la page Statistiques de l'administration |
| `controllers/activity.php` | `activity($user_id, $limit)` | le profil d'un membre |
| `controllers/dashboard.php` | `dashboard()` | le tableau de bord de l'administration |
| `controllers/block.php` | `block()` | les blocs `[block:…]` des pages |
| `controllers/search.php` | `search()` **et** `suggest()` | la recherche globale et la suggestion instantanée — un module qui n'a que `search()` est **ignoré en silence** |
| `controllers/sitemap.php` | `sitemap()` | le plan du site (`/sitemap.xml`, un par langue) : rend `[['adresse' => 'monmodule/12/titre', 'date' => …], …]`, des chemins comme ceux que prend `url()`, seulement ce qu'un **visiteur** peut lire (`$this->access('monmodule', 'lire', $id, 'visitors')`) et ce qui existe **dans la langue du plan** — sans lui, le module est absent des moteurs |

## 6. Les permissions (optionnel)

```php
public function permissions()
{
    return [
        'default' => [
            'access' => [
                [
                    'title'  => $this->lang('Notes'),
                    'icon'   => 'fas fa-note-sticky',
                    'access' => [
                        'add_note'    => ['title' => $this->lang('Ajouter'),   'icon' => 'fas fa-plus', 'admin' => TRUE],
                        'delete_note' => ['title' => $this->lang('Supprimer'), 'icon' => 'far fa-trash-alt', 'admin' => TRUE],
                    ],
                ],
            ],
        ],
    ];
}
```

Les permissions deviennent éditables dans **Administration → Permissions** (matrice rôle × action).
Dans le code : `$this->access('notes', 'add_note')`. Exemple réel : `modules/news/news.php`.

## 7. L'administration (optionnel)

Si `'admin' => TRUE`, ajoute `controllers/admin.php` (classe `Admin extends Controller_Module`). Sa
méthode `index()` est servie sous `/admin/notes`.

```php
<?php
namespace NF\Modules\Notes\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
    public function index()
    {
        $this->title($this->lang('Notes'))->icon('fas fa-note-sticky');

        $this->form()
             ->add_rules([
                 'title' => ['label' => $this->lang('Titre'),   'type' => 'text',   'rules' => 'required'],
                 'body'  => ['label' => $this->lang('Contenu'), 'type' => 'editor'],
             ])
             ->add_submit($this->lang('Ajouter'));

        if ($this->form()->is_valid($post))
        {
            $this->db->insert('nf_notes', ['title' => $post['title'], 'body' => $post['body']]);
            notify($this->lang('Note ajoutée.'));
            redirect('admin/notes');
        }

        return $this->admin_card('fas fa-note-sticky', $this->lang('Notes'), $this->form()->display());
    }
}
```

C'est **`form()`** (`add_rules()` / `is_valid()` / `display()`) qui est l'API de formulaire des écrans
d'administration ; `form2()` (cf. [Le framework](framework.md)) sert aux formulaires publics, riches ou
de confirmation seule. Le trait `Admin_Helpers`, disponible sur tout contrôleur de module, habille le
contenu : `admin_card()`, `admin_create()`, `admin_back()`, `admin_split()`, `admin_action_bar()`,
`admin_empty()`, `admin_stats()`, `sort_select()`. Toute sous-page doit offrir un retour (`admin_back()`
ou le fil d'Ariane) : `tools/check-admin-back.php` le vérifie sur chaque page d'administration.

### Actions mutantes : exiger un jeton CSRF

Toute action qui **modifie** quelque chose déclenchée par un **lien GET** ou un **POST écrit à la main**
(hors `form()` / `form2()`, qui portent leur propre jeton) doit être protégée : `SameSite=Lax` ne
suffit pas.

```php
// Génération du lien : jeton en query.
$html .= '<a href="'.$this->csrf_url('admin/notes/delete/'.$id).'">'.icon('far fa-trash-alt').'</a>';

// Contrôleur : vérifie le jeton AVANT de muter, sinon redirige.
public function _delete($note)
{
    $this->check_csrf('admin/notes');
    $this->db->where('id', $note['id'])->delete('nf_notes');
    notify($this->lang('Note supprimée.'));
    redirect('admin/notes');
}
```

Pour un POST manuel, le jeton va en champ caché : `<input type="hidden" name="_" value="<?php echo $this->csrf_token() ?>">`.

### Tables d'administration

`table2()` rend des listes paginées, triables par clic sur l'en-tête et filtrables (exemple :
`modules/user/controllers/admin.php`). Le tri est géré par `js/table2.js`, en vanilla. Une liste que le
checker découpe à la main (`->paginate()`) rend ses liens de pages : le contrat est dans
[Le framework](framework.md#tables--table2), et `check-pagination` le vérifie.

### La charte de l'administration

L'administration est **sobre** : le fond s'efface derrière les données, la couleur d'accent est rare, et
les couleurs vives sont réservées à ce qui appelle une action ou signale un état. Une page de module se
compose ainsi :

- **Une carte par liste** : `admin_card(icône, titre, corps, sous-titre, actions)` — le sous-titre porte
  le compte (« 3 publiées · 1 brouillon »), les actions le bouton qui crée.
- **Le bouton qui crée** (« Nouvelle citation ») : `admin_create(url, libellé)`, un `btn btn-primary
  btn-sm` dans l'en-tête de la carte de la liste qu'il alimente. La barre du haut garde les outils de la page — Permissions, Configuration,
  Aide.
- **Les actions d'une ligne** : des boutons à icône seule, petits. *Modifier* (crayon), *accès*
  (cadenas), *trier* : `btn-outline-secondary`, neutres. *Supprimer* : `btn-outline-danger` avec
  l'icône `far fa-trash-alt`. Jamais de bouton plein dans une ligne. `button_update()`,
  `button_access()` et `button_delete()` les rendent ainsi ; un bouton écrit à la main suit la même
  règle, que `tools/check-actions-admin.php` vérifie.
- **Les autres actions d'une ligne** (aperçu, dupliquer, restaurer) : un contour, jamais un bouton
  plein ; une teinte seulement si elle porte un sens (`btn-outline-warning` pour un aperçu qui change
  le mode de navigation, `btn-outline-success` pour restaurer).
- **Les pastilles d'état** : `badge text-bg-success` (publié, actif), `text-bg-secondary` (brouillon,
  inactif), `text-bg-warning` (en attente), `text-bg-danger` (erreur) — le thème d'administration les
  rend douces (fond pâle, texte appuyé), en clair comme en sombre.
- **Un tableau** pour des données en colonnes (titre, compte, statut) ; **des cartes**
  (`nf-content-card`) pour des contenus rédigés qu'on reconnaît à leur extrait. Une liste qui se range
  par famille (Templates emails) reste **un seul tableau**, une ligne `<tr class="nf-table-groupe">` en
  tête de chaque famille, plutôt qu'une carte par famille. Une date de la base s'y affiche avec
  `nf_date_heure()`, jamais telle quelle.
- **L'état vide** : `admin_empty(icône, titre, texte)`, qui dit quoi faire pour commencer.
- **Le bouton qui envoie un formulaire** : « Enregistrer » pour une fiche qui existe — jamais
  « Éditer », on est déjà en train de la modifier —, « Ajouter » ou « Créer » pour une nouvelle, comme
  le titre de la page (« Ajouter un forum »).
- **Plusieurs vues sur un même sujet** (Monitoring, Utilisateurs, Discord) : des onglets `nf-local-nav` /
  `nf-local-tab` — en liens vers d'autres adresses, ou en boutons qui basculent des panneaux.

## 8. Éprouver le module

Le projet a un filet, et un module neuf doit y entrer :

- **Tests** — `tests/Unit` (sans base), `tests/Headless` (le framework booté, de vrais modèles contre la
  base de test, chaque test dans une transaction annulée — `HeadlessTestCase`), `tests/Integration`
  (miroir SQL), `tests/Browser/*.test.html` (contrat d'un script dans un vrai navigateur, via
  `tools/check-js.php`). `vendor/bin/phpunit --fail-on-skipped`.
- **Contrôles** à faire passer avant de livrer : `check-addon-declarations`, `check-addon-coupling`,
  `check-addon-contracts`, `check-js-sources`, `check-langs --toutes`, `check-textes-en-dur` (aucun
  texte visible hors de `lang()`), `check-actions-admin` (la charte des boutons), `check-pagination`,
  `check-heures` (aucune date affichée sans fuseau), `check-db-compteurs`, `check-demo-lock` (un module
  qui touche à la configuration est verrouillé en démonstration),
  `check-strict-types` (le compteur ne doit jamais baisser — déclare `declare(strict_types=1)` dans tes
  fichiers neufs), puis `check-install-profiles` (installe chaque profil pour de vrai et frappe les
  routes des modules absents, qui doivent rendre un **404 propre**, jamais un 500) et
  `check-js-console` (ouvre les pages d'administration dans un navigateur et refuse toute erreur JS).
- PHPStan : `vendor/bin/phpstan analyse`.

La liste complète est dans [`tools/README.md`](../../tools/README.md) ; `php tools/check-all.php --navigateur`
les joue tous.

## 9. Installer et distribuer

1. Dépose `modules/notes/`.
2. **Administration → Thèmes & Addons → Scanner le disque** → coche `notes` → installe (l'installation
   joue `install/install.sql`, baseline les migrations, pose les permissions).
3. Pour le distribuer : zippe le dossier (`modules/notes/` à la racine de l'archive), il s'installe via
   **Ajouter (ZIP)**. Pour le publier au catalogue du marketplace du projet, il doit avoir
   `'core' => FALSE` et pas de `'distributed' => FALSE` ; `tools/package-addons.php` zippe et
   inscrit tous les addons optionnels dans `marketplace/catalog.json` avec leur empreinte SHA-256.
