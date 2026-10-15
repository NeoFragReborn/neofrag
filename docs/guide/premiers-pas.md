# Premiers pas

Ton site vient d'être installé ([Installation](installation.md)) : voici, dans l'ordre, ce qui le fait passer
d'une page vide à un site que tu peux ouvrir à ta communauté. Compte une heure. Chaque étape renvoie au guide
qui la détaille.

## 1. Entrer dans l'administration

Connecte-toi avec le compte créé à la fin de l'assistant, puis ouvre `/fr/admin` (ou le lien
*Administration* de la barre du membre). La barre latérale range les modules en rubriques — *Contenu*,
*Communauté*, *Système*… — ; `Ctrl + K` ouvre la recherche rapide. Le tour complet est dans
[Le panel d'administration](admin.md).

## 2. Donner son nom au site

*Système → Paramètres* : le nom du site, sa description, l'adresse de contact, le fuseau horaire. Le nom
s'affiche dans l'en-tête du thème et dans l'onglet du navigateur ; la description sert aux moteurs de
recherche tant qu'une page n'en donne pas une à elle ([Le référencement](admin.md#le-référencement)).

## 3. Choisir son thème et ses couleurs

*Système → Thèmes & Addons* : chaque thème montre sa page d'accueil en vignette. Active celui qui
ressemble à ta communauté, puis ouvre ses réglages : la page de **couleurs** montre le changement en
direct, et refuse une couleur qui rendrait le texte illisible. Ce qu'est un thème, ses zones et ses
régions : [Concepts](concepts.md).

## 4. Composer l'accueil

*Système → Éditeur en direct* : le site s'affiche tel que les visiteurs le voient, avec ses régions
encadrées. Glisse-y des **widgets** — les dernières actualités, le forum, les membres en ligne, un
diaporama — et règle chacun d'un clic. Une disposition vaut pour une page ou pour tout le site.

## 5. Construire le menu

*Contenu → Menus* : ajoute les liens de la barre de navigation — l'accueil, le forum, les actualités,
une page. Un lien vers un module se choisit dans une liste ; un lien externe se colle.

## 6. Publier une première actualité

*Contenu → Actualités → Nouvelle actualité* : un titre, une image, le texte. Elle paraît à l'accueil si
tu y as placé le widget des actualités, et ses lecteurs peuvent la commenter si les commentaires sont
ouverts.

## 7. Ouvrir le forum

*Communauté → Forum* : crée une **catégorie** (« Général »), puis ses **forums** (« Présentations »,
« Entraide »). Les **préfixes** (« Question », « Résolu ») aident à classer les sujets. Qui lit et qui
écrit se règle par catégorie, avec le bouton cadenas.

## 8. Accueillir les membres

*Système → Paramètres → Inscriptions* : ouvertes, sur invitation ou fermées, avec ou sans captcha
([Les inscriptions](admin.md#les-inscriptions)). Les **rôles** décident de ce que chacun peut faire :
crée un rôle « Modérateur », coche ses droits dans la matrice, et donne-le aux membres de confiance
([Permissions](admin.md#permissions)).

## 9. Ajouter ce qui manque

Le site s'est installé avec un profil — gaming, communauté, association. Un module absent (le
calendrier, les dons, les collectifs, le bot Discord…) s'ajoute depuis *Système → Thèmes & Addons* ou
le [marketplace](marketplace.md), sans réinstaller.

## 10. Le mettre à l'abri

*Système → Monitoring* : crée une première **sauvegarde**, et note l'adresse de la **tâche planifiée**.
Confie-la à ton hébergeur (une tâche cron, toutes les heures) : c'est elle qui publie les contenus
programmés, envoie les rappels et relève ce que les services extérieurs n'annoncent pas
([Le Monitoring](admin.md#le-monitoring)).

Ton site parle plusieurs langues ? Lis ensuite [Un site en plusieurs langues](multilingue.md).
