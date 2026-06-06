# Le marketplace

Le **marketplace** est le catalogue public des compléments de NeoFrag Reborn : modules,
widgets, thèmes et connecteurs d'authentification, prêts à télécharger.

## Trouver un addon

La page **Marketplace** présente tous les addons en fiches : icône, type, version,
description et taille. Le filtre en haut permet de n'afficher qu'un type (Modules,
Widgets, Thèmes, Connecteurs).

Chaque fiche propose un bouton **Télécharger** qui te livre une archive `.zip`.

## Installer un addon

1. **Télécharge** le `.zip` depuis la fiche.
2. Va dans **Admin → Thèmes & Addons → Ajouter**.
3. **Envoie l'archive** : NeoFrag détecte le type, copie les fichiers et enregistre
   l'addon.
4. Active-le si besoin (les modules sont installés mais tu contrôles leur visibilité via
   les permissions ; un thème s'active depuis sa fiche).

> **Connecteurs d'authentification** (Discord, GitHub, Google…) : l'upload ZIP ne les
> gère pas. Dépose le dossier sur le serveur, puis **Admin → Thèmes & Addons → Scanner
> le disque** et coche-le.

## Mettre à jour ou retirer

- **Mettre à jour** : envoie le nouveau `.zip` via *Ajouter* — la version supérieure
  remplace l'ancienne (une version égale ou inférieure est refusée).
- **Retirer** : depuis sa fiche, **Supprimer** (l'addon doit être désactivé).

## Pour les auteurs d'addons

Le catalogue est généré à partir du dépôt par `tools/package-addons.php`, qui zippe
chaque addon optionnel (dossier `<name>/` à la racine de l'archive — la structure
attendue par l'install ZIP) et produit `marketplace/catalog.json` (type, titre,
description, version, taille, checksum SHA-256).

Pour proposer ton propre addon, suis les guides : [créer un module](create-a-module.md),
[un widget](create-a-widget.md) ou [un thème](create-a-theme.md), puis zippe son dossier.
