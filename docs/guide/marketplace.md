# Le marketplace

Le **marketplace** sert à **mettre à jour** tes addons et à **ajouter des addons tiers** non livrés
dans le paquet. NeoFrag Reborn s'installe déjà **complet** (tous les modules/widgets/thèmes du paquet,
modèle « tout bundlé ») ; le marketplace intervient *après* l'installation, depuis l'administration.

Le catalogue et les archives sont servis depuis **neofrag-reborn.xyz**. Chaque archive est vérifiée
par **empreinte SHA-256** au téléchargement (intégrité), via HTTPS.

## Installer un addon tiers

Deux chemins :

### 1. En un clic depuis l'administration

**Admin → Thèmes & Addons → Marketplace**. La fenêtre liste les addons disponibles non installés ;
coche-les et clique **Installer** — NeoFrag télécharge, vérifie l'empreinte SHA-256, extrait et
enregistre l'addon (ainsi que son widget apparié, le cas échéant).

### 2. Manuellement (archive ZIP)

La page **Marketplace** (front) présente chaque addon en fiche avec un bouton **Télécharger**. Tu peux
aussi : télécharger le `.zip`, puis **Admin → Thèmes & Addons → Ajouter** et envoyer l'archive (l'upload
est validé : les archives au contenu non sûr sont refusées).

## Mettre à jour ou retirer

- **Mettre à jour** : **Admin → Thèmes & Addons → Mises à jour** compare la version installée de chaque
  addon à celle du catalogue et liste celles à mettre à jour ; applique-les en un clic (téléchargement +
  SHA-256, fichiers remplacés, **migrations de schéma en attente appliquées**). Sinon, envoyer un nouveau
  `.zip` via *Ajouter* met aussi à jour (une version supérieure remplace l'ancienne).
- **Retirer** : depuis la fiche de l'addon dans **Thèmes & Addons**, **Supprimer** (l'addon doit être
  désactivé). Ses tables et données sont retirées.

> Les **connecteurs d'authentification** (Discord, GitHub, Google) font partie du **cœur** : ils sont
> déjà présents, à configurer dans les réglages — ils ne passent pas par le marketplace.

> **Marketplace injoignable ?** Si l'écran « Mises à jour » affiche « Marketplace injoignable », vérifie
> que ton serveur peut sortir en **HTTPS** vers `neofrag-reborn.xyz`. Le catalogue est fixé à cette origine ;
> un administrateur peut la surcharger (vers un hôte autorisé, en HTTPS) via le réglage `nf_marketplace_url`.

## Sécurité

Le marketplace ne télécharge que depuis une **origine fixe** en **HTTPS** (jamais une URL saisie par
l'utilisateur), vérifie le **SHA-256** de chaque archive contre le catalogue, et **refuse toute archive
piégée** (anti-zip-slip : chemins absolus, `..`, symlinks). Aucun code distant n'est exécuté lors de
l'ajout/mise à jour d'un addon (extraction + `install.sql`/migrations SQL idempotents uniquement).

## Pour les auteurs d'addons

Le catalogue est généré depuis le dépôt par `tools/package-addons.php`, qui zippe chaque addon
(dossier `<name>/` à la racine de l'archive) et produit `marketplace/catalog.json` :

```json
{
  "schema": 1, "base_version": "1.0.0",
  "addons": [{
    "type": "module", "name": "wiki", "tier": 2, "category": "contenu",
    "title": "Wiki", "version": "1.0", "file": "modules/wiki.zip",
    "size": 15114, "sha256": "…", "provides_widgets": []
  }]
}
```

`catalog.json` et les `.zip` doivent être publiés **ensemble** (jeu cohérent du même run : les empreintes
SHA-256 dépendent du run). Pour proposer ton addon, suis les guides
[créer un module](create-a-module.md), [un widget](create-a-widget.md) ou
[un thème](create-a-theme.md), puis zippe son dossier.

## Héberger le catalogue (opérateur)

Le marketplace est un **jeu de fichiers statiques** servi sur le domaine de la marketplace : `catalog.json`
+ les `.zip` (rangés sous `modules/`, `widgets/`, `themes/`).

1. `php tools/package-addons.php` → (re)génère `marketplace/catalog.json` + les zips à jour.
2. Uploade le contenu de `marketplace/` à la racine du site → `https://<host>/marketplace/catalog.json`.
3. Le CMS pointe vers ce catalogue via **`nf_marketplace_url`** (défaut : `https://neofrag-reborn.xyz/marketplace`,
   **sans `www`**). Origines autorisées (anti-SSRF, HTTPS:443) : `neofrag-reborn.xyz` et `www.neofrag-reborn.xyz` ;
   pour un autre domaine, adapte `nf_marketplace_url` **et** `MARKETPLACE_HOSTS` dans `install/lib/installer.php`.

À refaire **à chaque changement d'addon** (version ou fichiers) : les SHA-256 du catalogue doivent
correspondre aux zips publiés (même run).

## Mise à jour du cœur (NeoFrag lui-même)

Le catalogue porte `base_version` (la version du CMS pour laquelle il a été bâti). **Admin → Thèmes & Addons
→ Mises à jour** la compare à la version installée et **signale** une nouvelle version du cœur le cas échéant.

Deux chemins pour l'appliquer.

### En un clic depuis le Monitoring

**Admin → Monitoring → Mettre à jour**. Le site prend d'abord une **sauvegarde**, puis télécharge le paquet
de mise à jour, vérifie son empreinte, superpose les fichiers, applique les migrations en attente et
recompile les feuilles de style.

Ce bouton était désactivé sur ce fork (`NEOFRAG_ALLOW_AUTOUPDATE`) parce que l'ancien mécanisme téléchargeait
la release **upstream** (`neofrag.download`) et l'étalait par-dessus, ce qui aurait écrasé le code Reborn
divergé. Un interrupteur global empêchait toutefois aussi les mises à jour légitimes. Il est remplacé par
quatre garanties de nature :

1. l'**origine** vient de la même allow-list que le marketplace — `neofrag.download` n'y est pas, et une
   valeur injectée en base ne peut pas l'y faire entrer ;
2. `version.json` ne fournit qu'un **nom de fichier**, jamais une URL : ni hôte, ni chemin, donc ni
   redirection ni remontée de répertoire ;
3. l'empreinte **SHA-256** est vérifiée **avant** qu'un seul fichier du site ne soit touché ;
4. l'archive est contrôlée **entrée par entrée** (anti-zip-slip, symlinks refusés).

`config/` et `install/` ne sont jamais réécrits quand ils existent déjà : la configuration d'un site en
service est préservée.

### À la main

Télécharge la release et remplace les fichiers (hors `config/`, `upload/`, `backups/`), puis visite le
site — les migrations s'appliquent.

### Publier une mise à jour (opérateur)

`php tools/build-release.php` produit, en plus des paquets d'installation, **trois fichiers à publier
ensemble** sur l'origine de mise à jour (`https://neofrag-reborn.xyz/update/` par défaut, surchargeable
via `nf_monitoring_check_url` vers un hôte autorisé) :

| Fichier | Rôle |
|---|---|
| `neofrag-reborn-update-<v>.zip` | le paquet, **à plat** (aucun dossier racine) |
| `version.json` | version publiée, nom du zip, son SHA-256, sa taille |
| `checksum.json` | une empreinte MD5 par fichier livré, pour le contrôle d'intégrité du Monitoring |

> Le paquet de mise à jour est **plat**, contrairement aux paquets d'installation qui rangent tout sous
> `neofrag-reborn/`. C'est essentiel : l'updater écrit chaque entrée à son propre chemin, donc un paquet
> à dossier racine créerait un sous-dossier `neofrag-reborn/` au lieu de remplacer quoi que ce soit — la
> mise à jour « réussirait » sans rien mettre à jour.

> Les trois fichiers forment un **jeu cohérent d'un même run** : le SHA-256 de `version.json` et les
> empreintes de `checksum.json` ne valent que pour ce zip précis. Publier l'un sans les autres fait
> échouer la vérification côté site.
