# Créer un widget

Un **widget** est un bloc réutilisable plaçable dans n'importe quelle zone d'un thème, avec l'éditeur
en direct. C'est l'addon le plus simple à écrire : une classe, un contrôleur, une vue.

Nous allons créer un widget `hello` qui affiche un message de bienvenue paramétrable. Tout est vérifié
contre le code ; `widgets/html` et `widgets/about` sont de bons exemples réels.

## Structure des fichiers

```
widgets/hello/
├── hello.php                 # la classe du widget (métadonnées, déclarations, types)
├── controllers/
│   ├── index.php             # le contrôleur : prépare et rend la vue
│   ├── checker.php           # valide et complète les réglages (recommandé dès qu'il y a des réglages)
│   └── admin.php             # le formulaire de réglages (optionnel)
├── views/
│   ├── index.tpl.php         # le gabarit HTML
│   └── admin.tpl.php         # gabarit des réglages (optionnel)
├── css/
│   └── hello.css             # styles (optionnel)
├── images/
│   └── thumbnail.jpg         # sa vignette, 960 × 600 — photographiée, pas dessinée
└── js/
    └── hello.js              # optionnel — vanilla, jamais jQuery
```

## 1. La classe — `hello.php`

```php
<?php
namespace NF\Widgets\Hello;

use NF\NeoFrag\Addons\Widget;

class Hello extends Widget
{
    protected function __info()
    {
        return [
            'title'       => $this->lang('Bienvenue'),
            'description' => $this->lang('Affiche un message de bienvenue personnalisable.'),
            'icon'        => 'fas fa-hand-spock',   // OBLIGATOIRE, et doit exister dans le FontAwesome embarqué
            'author'      => 'Ton Nom',
            'license'     => 'LGPLv3',
            'version'     => '1.0.0',

            // Déclarations de découplage — OBLIGATOIRES (la CI refuse un addon muet).
            'core'        => FALSE,
            'presets'     => ['gaming', 'communaute'],
            'requires'    => [],                     // ex. ['teams'] si le widget lit nf_teams

            // Optionnel : plusieurs types = plusieurs méthodes du contrôleur, choisies à la pose.
            'types'       => [
                'index' => $this->lang('Message'),
            ],
        ];
    }
}
```

- Le `namespace` **doit** suivre le dossier : `NF\Widgets\<Name>`.
- `icon` est obligatoire : l'assistant d'ajout de widget de l'éditeur en direct présente les widgets par
  **cartes à icône**, et `tools/check-addon-declarations.php` refuse un nom absent du FontAwesome
  embarqué (des icônes ont été renommées entre la version 5 et la 6).
- `core`, `presets`, `requires` : mêmes règles que pour un module — voir
  [Créer un module](create-a-module.md), « Les trois déclarations de découplage ».
- Les **réglages** ne se déclarent pas dans cette classe mais dans `controllers/admin.php` (§5).

## 2. Le contrôleur — `controllers/index.php`

```php
<?php
namespace NF\Widgets\Hello\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
    public function index($settings = [])
    {
        return $this->css('hello')->view('index', [
            'message' => $settings['message'],
        ]);
    }
}
```

Le contrôleur reçoit les `$settings` — **déjà validés et complétés par le checker** (§6) —, prépare les
données, charge son CSS et rend sa vue. Le chaînage `->css('hello')->view('index', $data)` est le
motif standard. Un widget à plusieurs `types` a une méthode par type.

## 3. La vue — `views/index.tpl.php`

```php
<div class="hello-widget">
    <i class="fas fa-hand-spock"></i>
    <span><?php echo htmlspecialchars($message) ?></span>
</div>
```

**Échappe toujours** ce qui vient d'un réglage ou de la base (`htmlspecialchars`). Un titre écrit par un
membre posé en `innerHTML` côté JS deviendrait une injection stockée, servie à tous.

## 4. Le CSS — `css/hello.css` (optionnel)

```css
.hello-widget { display: flex; align-items: center; gap: 10px; padding: 14px 16px; color: var(--nf-text); }
.hello-widget i { color: var(--nf-accent); }
```

**N'emploie que le vocabulaire partagé `--nf-*`**, celui que **tous** les thèmes définissent :
`--nf-bg`, `--nf-bg-elevated`, `--nf-surface`, `--nf-surface-2`, `--nf-surface-3`, `--nf-hover`,
`--nf-border`, `--nf-border-strong`, `--nf-text`, `--nf-text-strong`, `--nf-text-soft`,
`--nf-text-muted`, `--nf-muted`, `--nf-muted-2`, `--nf-accent`, `--nf-accent-soft`,
`--nf-accent-strong`, `--nf-accent-text`, `--nf-on-accent`, `--nf-success`, `--nf-info`,
`--nf-warning`, `--nf-danger`, `--nf-danger-soft`, `--nf-radius`, `--nf-radius-sm`.

Une variable qu'un thème ne définirait pas retomberait sur son repli — c'est ainsi que des **blocs
blancs** sont apparus en thème sombre. `tools/check-css-variables.php` refuse toute variable employée
par un widget ou un module que tous les thèmes ne définissent pas. Ne pose pas de valeur de repli :
elle masquerait le défaut au lieu de le révéler.

Grille : `col-12 col-lg-6`, jamais `col-6` seul (Bootstrap 5 l'applique dès 0 px).

## 5. Les réglages — `controllers/admin.php` (optionnel)

Pour qu'un widget soit **configurable**, ajoute `controllers/admin.php` : il rend le formulaire de
réglages, et ses champs alimentent les `$settings` que reçoit le contrôleur `index`. La classe étend
`Controller` (pas `Widget`) :

```php
<?php
namespace NF\Widgets\Hello\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
    public function index($settings = [])
    {
        $settings = (array) $settings + ['message' => ''];

        return $this->view('admin', [
            'message' => $settings['message'] ?: $this->lang('Bienvenue sur le site !'),
        ]);
    }
}
```

La vue `views/admin.tpl.php` rend les champs ; chaque champ est nommé `settings[<clé>]`. Chaque
ligne porte `nf-field`, la classe de champ du produit : elle donne l'espacement que chaque thème règle
pour tous les formulaires.

```php
<div class="nf-field row">
    <label for="settings-message" class="col-12 col-lg-4 col-form-label"><?php echo $this->lang('Message') ?></label>
    <div class="col-12 col-lg-8">
        <input class="form-control" type="text" name="settings[message]" id="settings-message"
               value="<?php echo htmlspecialchars($message) ?>">
    </div>
</div>
```

Dans l'éditeur en direct, l'assistant d'ajout affiche l'étape « Configuration » **si et seulement si**
ce contrôleur rend quelque chose : un widget sans réglages n'a simplement pas de `controllers/admin.php`.

## 6. Valider et compléter les réglages — `controllers/checker.php`

**Un widget peut arriver sans réglages** : posé par l'`install()` d'un thème, ajouté dans l'éditeur en
direct avant qu'on ouvre son formulaire, ou restauré depuis une disposition ancienne. Son checker doit
rendre des réglages utilisables même quand on ne lui en donne aucun. Douze widgets ne le faisaient pas ;
l'un employait une clé absente comme diviseur — division par zéro, widget mort.

La méthode `index($settings)` reçoit les réglages **bruts** et **retourne** un tableau **validé et
complété de ses défauts**, qui devient les `$settings` du contrôleur :

```php
<?php
namespace NF\Widgets\Hello\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
    public function index($settings = [])
    {
        // Le repli en tête : `+` ne remplit que les clés absentes, les valeurs fournies restent intactes.
        $settings = (array) $settings + ['message' => '', 'align' => ''];

        return [
            'message' => $settings['message'] !== '' ? $settings['message'] : $this->lang('Bienvenue !'),
            'align'   => in_array($settings['align'], ['text-start', 'text-center', 'text-end'], TRUE)
                       ? $settings['align'] : 'text-start',
        ];
    }
}
```

`tools/check-widget-reglages.php` refuse toute lecture `$settings['clé']` qui n'est ni protégée sur sa
ligne (`isset`, `??`, `empty`, `array_key_exists`) ni couverte par ce repli en tête de méthode. Exemple
réel complet : `widgets/about/controllers/checker.php`.

## 7. Du JavaScript dans un widget

- Charge-le par `->js('hello')` dans le contrôleur ; le fichier est `js/hello.js`.
- **Vanilla, sans jQuery** : `NF.ready(fn)`, `NF.data(el, 'clé')`, `NF.ajax({url, method, data, dataType})`,
  `NF.post(url, data)`, `NF.setHtml(el, html)` (les `<script>` insérés sont ré-exécutés avec le bon
  nonce). `tools/check-js-sources.php` refuse `$(…)`.
- Les adresses AJAX se lisent sur le balisage (`data-url="…"`) plutôt qu'en PHP interpolé dans le `.js`,
  ce qui rend le fichier éprouvable tel quel dans le harnais (`tests/Browser/*.test.html`).
- Un script qui plante avant d'attacher ses écouteurs ne casse rien de visible :
  `tools/check-js-console.php` ouvre les pages dans un vrai navigateur et le voit.

## 8. Installer, placer, distribuer

1. Dépose `widgets/hello/` sur ton site.
2. **Administration → Thèmes & Addons → Scanner le disque**, coche `hello`, installe.
3. Dans l'**éditeur en direct**, ajoute-le dans une zone : l'assistant propose le widget, son type, un
   titre, puis sa configuration s'il en a une. Un widget peut être posé plusieurs fois, avec des réglages
   différents.
4. Pour le distribuer : zippe le dossier (`widgets/hello/` à la racine de l'archive) — il s'installe via
   **Ajouter (ZIP)** — ou laisse `tools/package-addons.php` l'inscrire au catalogue du marketplace.
5. Sa vignette : `php tools/capturer-apercus.php --type=widget --nom=hello`, sur un site d'essai peuplé.
   Le widget y est photographié là où il est posé, ou seul sur la page de contact le temps de la photo ;
   s'il ne rend rien sans réglages, donne-lui des réglages d'exemple dans la table `REGLAGES` de l'outil.
   Format et contrôles : [Créer un thème, § 5](create-a-theme.md).

`tools/check-widget-contract.php` interroge réellement chaque couple widget/type pour vérifier qu'aucun
ne casse à la pose.
