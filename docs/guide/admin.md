# Le panel d'administration

L'administration se trouve sous **/admin**. Elle adopte la charte NeoFrag Reborn
(dark navy + teal, clair/sombre au choix) et s'organise autour d'une **barre latérale**.

## Repères

- **Barre latérale** : les modules sont regroupés par catégories claires — *Contenu*,
  *Communauté*, *Connaissance* (wiki, FAQ), *Média*, *Gaming*, *Monétisation* — plus *Système* et
  *Monitoring*. Les sections se déplient en accordéon ; une catégorie vide est masquée.
- **Épingles** : survole un module dans la sidebar et clique l'épingle pour l'ajouter à
  *Épinglé* (raccourcis en haut, mémorisés dans ton navigateur).
- **Recherche rapide** : `Ctrl/Cmd + K` ouvre la palette de commandes pour sauter à
  n'importe quel module ou action.
- **En-tête** : fil d'ariane + actions contextuelles (Permissions, Configuration, Aide),
  « Voir le site » et bascule clair/sombre.

## Tâches courantes

| Je veux… | J'y vais |
|---|---|
| Publier une actu / un article / une page | *Contenu* → le module concerné |
| Ranger les billets du Blog en catégories et en séries (un billet en plusieurs parties) | *Contenu → Blog*, boutons « Catégories » et « Séries » ; la série et le rang d'un billet se choisissent dans son formulaire |
| Gérer le forum, les commentaires, la modération | *Communauté* |
| Créer les préfixes de sujet du forum (« Question », « Tutoriel »…), choisir l'icône d'un forum | *Communauté → Forum*, bouton « Préfixes » ; l'icône dans le formulaire du forum |
| Gérer membres, groupes, sessions | *Système → Utilisateurs* |
| Régler qui peut faire quoi | *Système → Permissions (matrice)* — grille rôle × action (vert = autorisé, gris = défaut, rouge = jamais) |
| Changer le thème / installer un addon | *Système → Thèmes & Addons* |
| Réglages du site (nom, accueil, inscriptions, sécurité, copyright) | *Système → Paramètres* |
| Composer les pages à la souris | *Système → Live Editor* |
| Sauvegardes, mises à jour, état du site, journal d'audit | *Monitoring* |

> **Monitoring** réunit la **santé du site** (vérifications d'intégrité des fichiers, espace disque, infos
> serveur, état des tâches cron), les **sauvegardes** (créer / télécharger / restaurer) et le **journal
> d'audit** des actions sensibles (réglages, addons, comptes).

## Permissions

NeoFrag Reborn fonctionne par **rôles**. La **matrice de permissions** (par module)
règle, pour chaque rôle, l'accès et les actions. Tu assignes les rôles aux membres et
aux groupes depuis *Système*.

**Exemple — créer un rôle « Modérateur »** :

1. Dans *Système → Permissions*, **crée un rôle** « Modérateur ».
2. Sur sa colonne de la **matrice**, coche les actions voulues (modérer le forum, gérer les
   commentaires…) → **vert = autorisé**.
3. Dans *Système → Utilisateurs*, édite un membre et **assigne-lui ce rôle** : il hérite aussitôt de ces accès.

## Réglages essentiels

Dans **Paramètres** : titre et description du site, favicon, email de contact, page
d'accueil, gestion des inscriptions, sécurité anti-bots (captcha), maintenance,
copyright, réseaux sociaux. Ces réglages alimentent les thèmes et les widgets.
