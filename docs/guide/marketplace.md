# Le marketplace

Le **marketplace** sert à **mettre à jour** tes addons et à **installer ceux qui ne sont pas sur ton
serveur**. Il ne propose que les addons officiels de NeoFrag Reborn, que le paquet d'installation contient
déjà tous : il sert donc surtout aux mises à jour, et à reprendre un addon que tu avais supprimé. Il
intervient *après* l'installation, depuis l'administration.

Le catalogue et les archives sont servis depuis **neofrag-reborn.xyz**, en HTTPS ; chaque archive est
vérifiée par **empreinte SHA-256** au téléchargement. Chaque version les publie aussi, avec le catalogue,
sur la [page des versions](https://github.com/NeoFragReborn/extensions/releases) du dépôt des addons à la carte.

## Installer un addon

Deux chemins :

### 1. En un clic depuis l'administration

**Admin → Thèmes & Addons → Marketplace**. La fenêtre liste les addons disponibles non installés ;
coche-les et clique **Installer** — NeoFrag télécharge, vérifie l'empreinte SHA-256, extrait et
enregistre l'addon (ainsi que son widget apparié, le cas échéant).

### 2. Manuellement (archive ZIP)

La page **Marketplace** (front) présente chaque addon en fiche avec un bouton **Télécharger**. Tu peux
aussi : télécharger le `.zip`, puis **Admin → Thèmes & Addons → Ajouter** et envoyer l'archive (l'upload
est validé : les archives au contenu non sûr sont refusées). C'est aussi le chemin d'un addon que tu as
écrit toi-même : zippe son dossier et envoie-le.

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
> que ton serveur peut sortir en **HTTPS** vers `neofrag-reborn.xyz`. Le catalogue est fixé à cette
> origine : la liste des hôtes autorisés est écrite dans le code (`MARKETPLACE_HOSTS`,
> `neofrag/installer.php`), et le réglage `nf_marketplace_url`, sans écran d'administration, ne peut
> désigner qu'une adresse HTTPS sur ces hôtes.

## Sécurité

Le marketplace ne télécharge que depuis une **origine fixe** en **HTTPS** (jamais une URL saisie par
l'utilisateur), vérifie le **SHA-256** de chaque archive contre le catalogue, et **refuse toute archive
piégée** (anti-zip-slip : chemins absolus, `..`, symlinks). Aucun code distant n'est exécuté lors de
l'ajout/mise à jour d'un addon (extraction + `install.sql`/migrations SQL idempotents uniquement).

## Pour les auteurs d'addons

Le code des addons à la carte vit dans [NeoFragReborn/extensions](https://github.com/NeoFragReborn/extensions) : y proposer un addon
ou une correction, c'est y ouvrir une demande de fusion. Le catalogue est généré par
`tools/package-addons.php`, qui zippe chaque addon (dossier `<name>/` à la
racine de l'archive) et produit `marketplace/catalog.json`. Une entrée réelle, abrégée :

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

`catalog.json` et les `.zip` se publient **ensemble** : les empreintes SHA-256 ne valent que pour les
archives du même passage. Le catalogue officiel est publié à chaque version, et un site ne peut pas en
suivre un autre sans modifier `MARKETPLACE_HOSTS`. Un addon que tu écris — guides :
[créer un module](create-a-module.md), [un widget](create-a-widget.md), [un thème](create-a-theme.md) —
s'installe sur n'importe quel site par *Thèmes & Addons → Ajouter*.

## Mise à jour du cœur (NeoFrag lui-même)

Le catalogue porte `base_version` (la version du CMS pour laquelle il a été bâti). **Admin → Thèmes & Addons
→ Mises à jour** la compare à la version installée et **signale** une nouvelle version du cœur le cas échéant.

Deux chemins pour l'appliquer.

### En un clic depuis le Monitoring

**Admin → Monitoring → Mettre à jour**. Le site prend d'abord une **sauvegarde**, puis télécharge le paquet
de mise à jour, vérifie son empreinte, superpose les fichiers, applique les migrations en attente et
recompile les feuilles de style.

Le bouton n'accepte que la bonne mise à jour, par quatre garanties :

1. l'**origine** vient de la même liste blanche que le marketplace, et une valeur injectée en base ne
   peut pas en sortir ;
2. `version.json` ne fournit qu'un **nom de fichier**, jamais une URL : ni hôte, ni chemin, donc ni
   redirection ni remontée de répertoire ;
3. l'empreinte **SHA-256** est vérifiée **avant** qu'un seul fichier du site ne soit touché ;
4. l'archive est contrôlée **entrée par entrée** (anti-zip-slip, symlinks refusés).

Les fichiers de `config/` déjà présents et le verrou `install/db.txt` ne sont jamais réécrits : la
configuration d'un site en service est préservée. Le reste d'`install/` est du code du produit, et suit
les versions.

### À la main

Télécharge `neofrag-reborn-public-<version>.zip` sur la [page des versions](https://github.com/NeoFragReborn/neofrag/releases)
et remplace les fichiers (hors `config/`, `upload/`,
`backups/`), puis visite le site — les migrations s'appliquent ([guide de déploiement](../deploy-ftp.md#mettre-à-jour-un-site-déjà-en-ligne)).

### Ce que publie chaque version

Chaque version publie sur l'origine de mise à jour (`https://neofrag-reborn.xyz/update/`) trois fichiers
qui forment un jeu cohérent : le paquet de mise à jour, `version.json` (la version, le nom du paquet, son
SHA-256 et sa taille) et `checksum.json` (une empreinte par fichier livré, pour le contrôle d'intégrité du
Monitoring). Le bouton vérifie chacun par les autres : publier l'un sans les autres fait échouer la mise à
jour, sans rien toucher au site.
