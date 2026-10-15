# Un site en plusieurs langues

NeoFrag Reborn parle six langues : français, anglais, allemand, espagnol, italien et portugais. Ce guide
explique ce qui se traduit tout seul, ce que tu traduis toi-même, et ce que voit un visiteur quand une
traduction manque.

## Deux sortes de textes

- **L'interface** — les boutons, les menus de l'administration, les messages, les courriels : elle arrive
  traduite avec les **packs de langue** (des addons, livrés avec le produit). Rien à faire.
- **Ton contenu** — tes actualités, tes pages, tes catégories, ton menu, les titres de tes widgets, les
  pages de ton wiki, tes campagnes de dons… : c'est toi qui l'écris, et tu l'écris **langue par langue**.

## Écrire dans une autre langue

Un écran d'édition écrit dans **la langue de l'administration**, et le dit sous le champ : « Écrit pour
Français ». Pour traduire, change de langue — le sélecteur de langue du site, ou l'adresse elle-même :
`/en/admin/…` au lieu de `/fr/admin/…` — et rouvre le même contenu : ses champs sont vides là où rien
n'est encore écrit dans cette langue. Enregistre : les autres langues gardent leur texte.

Ce qui n'a pas de langue reste commun à toutes : une date, une image, un prix, un ordre d'affichage, un
auteur. Tu ne le saisis qu'une fois.

## Ce que voit le visiteur

Le visiteur lit le site dans la langue de l'adresse (`/de/…`), qu'il choisit avec le sélecteur de langue
du thème. Pour chaque contenu, il voit **la meilleure traduction qui existe** : celle de sa langue, sinon
le français, sinon la première langue écrite. Un contenu écrit dans une seule langue reste donc visible
partout : il n'est simplement pas traduit.

L'administration montre ce qui reste à traduire : les listes signalent les contenus sans texte dans la
langue de l'écran, et le relevé des traductions note les textes de l'interface qui manquent dans une
langue ([Le panel d'administration](admin.md)).

## Ce qui ne se traduit pas

- Ce que **les membres** écrivent — un sujet du forum, un message, un commentaire, une petite annonce —
  reste dans la langue où il a été écrit.
- Les **émojis personnalisés** gardent leur nom partout : c'est un code, pas un texte.

## Les moteurs de recherche

Chaque langue a son plan du site, et chaque page indique ses autres langues. Un contenu qui n'a pas de
langue à lui — un sujet du forum, ou une page dont le texte est le même dans toutes les langues — se
déclare dans la langue première du site seulement : six copies d'un même texte, chacune « officielle »,
feraient qu'aucune ne serait retenue. Le détail : [Le référencement](admin.md#le-référencement).

## Pour les développeurs

Un module qui stocke du contenu traduisible le range dans une table `<table>_lang` (une ligne par langue),
la lit avec `join_lang()` et l'écrit avec `ecrire_lang()` : [Le framework](framework.md).
