# Créer un thème

Un **thème** donne au site son identité visuelle complète : la mise en page (navigation, régions, pied
de page), la charte (couleurs, typographies) et les **dispositions** par défaut (quels widgets, où).

Nous esquissons un thème `aurora`. Le plus simple pour démarrer est de **cloner `themes/nebula/`** (le
thème public livré) puis d'adapter. Tout ce qui suit est vérifié contre le code.

## Structure des fichiers

```
themes/aurora/
├── aurora.php                # classe : zones, régions, assets, dispositions
├── views/
│   ├── body.tpl.php          # le squelette de page (rend les régions)
│   └── live_editor/          # row.tpl.php, widget.tpl.php : styles proposés dans l'éditeur en direct
├── css/
│   ├── style.css             # la charte — définit TOUT le vocabulaire --nf-*
│   └── sass/                 # optionnel : SCSS compilé côté serveur
├── js/
│   ├── theme.js              # mode clair/sombre du thème
│   └── aurora.js             # interactions du thème
├── images/
│   └── thumbnail.jpg         # vignette 480 × 270 pour la carte d'addon
└── install/
    └── migrations/           # évolutions des dispositions livrées (optionnel)
```

## 1. La classe — `aurora.php`

```php
<?php
namespace NF\Themes\Aurora;

use NF\NeoFrag\Addons\Theme;

class Aurora extends Theme
{
    protected function __info()
    {
        return [
            'title'       => 'Aurora',
            'description' => $this->lang('Thème communautaire sombre et néon.'),
            'author'      => 'Ton Nom',
            'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
            'version'     => '1.0.0',

            // Déclarations de découplage — OBLIGATOIRES.
            'core'        => FALSE,
            'presets'     => [],
            'requires'    => [],

            // Les zones, dans l'ordre, par leur TITRE (c'est ce titre qu'emploie install()) …
            'zones'       => ['Header', 'Avant-contenu', 'Contenu', 'Post-contenu', 'Footer'],
            // … et les RÉGIONS nommées que les gabarits rendent. OBLIGATOIRE pour un thème public.
            'regions'     => [
                'header'         => 'Header',
                'before_content' => 'Avant-contenu',
                'content'        => 'Contenu',
                'after_content'  => 'Post-contenu',
                'footer'         => 'Footer',
            ],
        ];
    }

    public function __init()
    {
        // Les assets de chaque page, dans l'ordre d'inclusion. Ceux du cœur d'abord : Bootstrap 5, le
        // socle du produit (css/nf-bs5-bridge.css : ses composants et les couleurs de Bootstrap tirées
        // de la palette du thème), FontAwesome ; puis la charte du thème ; puis les scripts.
        $this->css('bootstrap.min')->css('nf-bs5-bridge')
             ->css('icons/fontawesome.min')
             ->css('style')
             ->js('bootstrap.bundle.min')
             ->js('modal')->js('notify')->js('confirm')
             ->js('theme')->js('aurora');
    }

    public function styles_row()    { return $this->view('live_editor/row'); }
    public function styles_widget() { return $this->view('live_editor/widget'); }

    public function install($dispositions = [])
    {
        $dispositions = $this->array();
        // … dispositions par défaut (voir §3) …
        return parent::install($dispositions);
    }
}
```

- `zones` déclare les zones dans l'ordre ; `regions` leur donne un **nom stable** que les gabarits
  emploient. `tools/check-addon-contracts.php` **exige** la clé `regions` sur tout thème public : c'est
  la fondation du chantier page-builder, et un gabarit qui rendrait `zone(2)` dépendrait de l'ordre.
- **Pas de jQuery** : il n'est plus chargé par le cœur, et `tools/check-js-sources.php` refuse tout
  script qui l'appelle. Bootstrap 5 se charge par `bootstrap.bundle.min` (Popper inclus).
- `styles_row()` / `styles_widget()` rendent les listes de styles de lignes et de panneaux proposées
  dans l'éditeur en direct ; copie `themes/nebula/views/live_editor/` pour commencer.

## 2. Le squelette — `views/body.tpl.php`

Le `body.tpl.php` est le HTML de la page. Il **rend les régions** par leur nom :

```php
<nav class="au-nav">
    <a href="<?php echo url('') ?>"><?php echo htmlspecialchars($this->config->nf_name) ?></a>
    <?php echo $this->output->region('header') ?>
</nav>

<main>
    <?php if ($zone = $this->output->region('before_content')): ?><section class="au-banner"><?php echo $zone ?></section><?php endif ?>
    <?php if ($zone = $this->output->region('content')): ?><div class="container"><?php echo $zone ?></div><?php endif ?>
    <?php if ($zone = $this->output->region('after_content')): ?><div class="container"><?php echo $zone ?></div><?php endif ?>
</main>

<footer>
    <?php echo $this->output->region('footer') ?>
</footer>
```

Le garde `if ($zone = …)` dit la vérité : une région déclarée mais **sans widget** rend une chaîne vide
(depuis le 2026-09-17 ; avant, des blancs faisaient dessiner un bandeau décoré autour de rien). Tu peux
donc envelopper chaque région d'un conteneur stylé sans risque.

> **Détecter l'accueil** : `((string) $this->url->request === '')`.

Tu es libre : soit tu rends les régions telles quelles (navigation pilotée par les widgets de la région
`header`), soit tu **codes une barre de navigation ou un pied de page sur mesure** dans le gabarit, comme
Nebula, pour une identité unique. Si tu écris un pied de page, **ne pose pas aussi** un widget de
copyright dans la région `footer` de tes dispositions : le thème Extend l'affichait deux fois.

Si tu proposes aux visiteurs de changer de thème, écris `<?php echo nf_selecteur_theme() ?>` dans ton pied
de page : le cœur rend le menu des thèmes installés (hors `admin`) et retient le choix dans un cookie propre
au site. Il ne s'affiche que s'il y a plus d'un thème public, et que l'administrateur n'a pas fermé le
choix (**Préférences générales → Choix du thème**).

## 3. Les dispositions par défaut — `install()`

`install()` décrit, par motif de page et par **titre de zone**, la grille de widgets posée à
l'installation du thème :

```php
// Toutes les pages : module principal (8 colonnes) + colonne latérale (4 colonnes).
$dispositions->set('*', 'Contenu', $this->array([
    $this->row(
        $this->col(
            $this->widget($this->db->insert('nf_widgets', ['widget' => 'module', 'type' => 'index']))
        )->size('col-12 col-lg-8'),
        $this->col(
            $this->widget($this->db->insert('nf_widgets', ['widget' => 'user', 'type' => 'index']))->style('panel-color')
        )->size('col-12 col-lg-4')
    )->style('row-default')
]));

// Accueil uniquement : un carrousel pleine largeur avant le contenu.
$dispositions->set('/', 'Avant-contenu', $this->array([
    $this->row($this->col(
        $this->widget($this->db->insert('nf_widgets', ['widget' => 'slider', 'type' => 'index']))
    ))->style('row-default')
]));
```

- `set($page, $zone, [...lignes])` : `$page` est un motif (`*`, `/`, `forum/*`…), le plus précis l'emporte.
- `$this->widget($id)` reçoit l'**id** d'une ligne `nf_widgets` fraîchement insérée (widget, type,
  réglages en JSON). Un widget posé **sans réglages** doit s'en sortir : c'est le rôle de son checker.
- `->size('col-12 col-lg-8')` — grille Bootstrap 5, **toujours avec un point de rupture** : `col-8` seul
  s'applique dès 0 px et écrase la page sur téléphone. `->style('row-default' | 'row-dark')` et
  `->style('panel-default' | 'panel-color' | 'panel-header')` stylent lignes et panneaux.
- Ne pose dans les dispositions livrées que des widgets **du cœur** : le paquet s'installe à la carte,
  un widget optionnel peut ne pas être là.

Une installation existante ne rejoue jamais `install()` : pour corriger une disposition livrée chez ceux
qui ont déjà le thème, écris une **migration de thème** (`install/migrations/AAAA_MM_JJ_nom.up.sql`,
suivie dans `nf_addon_migrations`, jouée à la mise à jour). Exemple réel :
`themes/extend/install/migrations/`.

## 4. La charte — `css/style.css`

**Chaque thème définit la totalité du vocabulaire `--nf-*`**, dans `:root` (et dans sa variante claire
ou sombre). Les feuilles des modules et des widgets n'emploient que ce vocabulaire ; une variable qu'un
thème oublie retombe sur un repli — des **blocs blancs** en thème sombre sont apparus ainsi.
`tools/check-css-variables.php` refuse toute variable employée par un module ou un widget que **tous**
les thèmes ne définissent pas. Le vocabulaire complet — vingt-six jetons — avec des valeurs **d'exemple**
(Nebula, lui, fait pointer chacun vers sa palette privée `--fg-*`, ce qui lui permet d'avoir un mode
clair et un mode sombre en ne changeant que celle-ci) :

```css
:root {
    /* fonds et surfaces */
    --nf-bg: #070a10;            --nf-bg-elevated: #0d1220;
    --nf-surface: rgba(255,255,255,.035);  --nf-surface-2: rgba(255,255,255,.06);  --nf-surface-3: rgba(255,255,255,.09);
    --nf-hover: rgba(255,255,255,.06);
    /* traits */
    --nf-border: rgba(255,255,255,.09);    --nf-border-strong: rgba(255,255,255,.18);
    /* textes */
    --nf-text: #e7eef6;  --nf-text-strong: #ffffff;  --nf-text-soft: #b7c2d0;  --nf-text-muted: #8593a6;
    --nf-muted: #8593a6; --nf-muted-2: #66748a;
    /* accent — et la couleur qui se pose DESSUS */
    --nf-accent: #2dd4bf;  --nf-accent-soft: rgba(45,212,191,.15);  --nf-accent-strong: #14b8a6;  --nf-accent-text: #99f6e4;
    --nf-on-accent: #041014;
    /* états */
    --nf-success: #22c55e;  --nf-info: #38bdf8;  --nf-warning: #f59e0b;  --nf-danger: #ef4444;
    --nf-danger-soft: rgba(239,68,68,.18);
    /* formes */
    --nf-radius: 14px;  --nf-radius-sm: 8px;
}
```

Deux pièges mesurés sur les thèmes livrés :

- **`--nf-on-accent` se recalcule quand l'accent change.** Si ta seconde palette (mode clair, variante)
  remplace `--nf-accent`, redéfinis aussi la couleur posée dessus, sinon le texte des boutons garde
  celle de l'autre accent — du blanc sur du turquoise, 1,86:1. `tools/check-contraste.php` mesure le
  contraste WCAG de tous les thèmes dans les deux modes.
- **Pas de `.row { margin: -15px }`** : c'est la gouttière de Bootstrap 4 ; Bootstrap 5 emploie −12 px
  via `--bs-gutter-x`, et les 3 px d'écart font déborder la page. `tools/check-responsive.php` mesure
  le débordement horizontal à 390, 768 et 1400 px ; `tools/check-classes-bs4.php` refuse les classes
  disparues de Bootstrap **3 et 4** (`card-columns`, `float-right`, `panel-body`, `badge-danger`…),
  dans les gabarits comme dans les feuilles de style — redéfinir `.badge-danger` dans ton thème ne
  la fait pas revivre, le contrôle la refuse —, les attributs `data-*` restés sans le préfixe `bs` —
  un `data-target` ne fait plus rien, en silence — et les classes que le code **fabrique** par
  concaténation. Un thème doit employer les noms de Bootstrap 5 : `float-end`, `text-start`, `g-0`,
  `ms-2`, `bg-danger-subtle text-danger-emphasis`. Pour teinter ces couleurs à ta charte, redéfinis
  les variables de Bootstrap (`--bs-danger-text-emphasis`…) plutôt que les classes.

Le CSS peut être un **gabarit PHP** ; le `?v=` est basé sur la date du fichier, toute édition invalide
le cache. Un dossier `css/sass/` est compilé côté serveur (scssphp) à l'installation et depuis
Administration → Outils.

**Mode clair / sombre.** Chaque thème gère le sien dans `js/theme.js` (attribut `data-theme` sur
`<html>`, préférence mémorisée côté navigateur sous **sa propre clé**, pas une clé commune). Un thème
« sombre seulement » force `document.documentElement.setAttribute('data-theme', 'dark')`.

## 5. La vignette — `images/thumbnail.jpg`

La carte du thème dans l'administration et le catalogue attend `images/thumbnail.jpg` en **480 × 270**.
Fais-en une **vraie capture** de l'accueil, pas une maquette : `tools/capture-vignettes.php` le fait pour
tous les thèmes installés et refuse deux vignettes identiques.

## 6. Installer, activer, éprouver

1. Dépose `themes/aurora/`.
2. **Administration → Thèmes & Addons → Scanner le disque** → coche `aurora` → installe.
3. **Activer** sur sa fiche (définit le thème par défaut). Si le thème a été enregistré sans dispositions,
   l'activation lance `install()` d'elle-même. Le bouton **Réinstaller par défaut** ré-exécute `install()`.
4. Avant de livrer, fais passer : `check-addon-declarations`, `check-addon-contracts` (la clé `regions`),
   `check-css-variables`, `check-classes-bs4`, `check-js-sources`, puis `check-responsive`,
   `check-contraste` et `check-js-console` sur une installation où le thème est actif. Regarde la page
   rendue : un bandeau vide, un copyright en double ou un commentaire illisible ne se voient pas dans le
   code.

## Bonnes pratiques

- **Polices** : Google Fonts est autorisé par la politique de sécurité (feuilles) ; préfère un `<link>` à
  un `@import` en tête de CSS, qui bloque le rendu.
- **Aucun script depuis un CDN** : la CSP stricte (`script-src 'self' 'nonce-…'`) le refuserait, et le
  projet ne dépend d'aucun tiers. Tout JS vit dans le thème ou le cœur.
- **Crédite** le projet d'origine (NeoFrag, Michaël BILCOT & Jérémy VALENTIN, LGPLv3) si tu pars d'un
  thème existant.
