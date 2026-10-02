# Le panel d'administration

L'administration se trouve sous **/admin**. Elle adopte la charte NeoFrag Reborn
(dark navy + teal, clair/sombre au choix) et s'organise autour d'une **barre latérale**.

## Repères

- **Barre latérale** : les modules sont rangés en neuf rubriques, plus *Système* (Paramètres,
  Utilisateurs, Permissions, Thèmes & Addons, Monitoring…). Les sections se déplient en accordéon ; une
  rubrique vide est masquée.

  | Rubrique | Modules |
  |---|---|
  | *Contenu* | Pages, Blog, Actualités, Slider, Citations, Recettes, Menus |
  | *Communauté* | Forum, Discussion, Commentaires, Livre d'or, Emojis |
  | *Animation* | Calendrier, Sondages, Gamification, Petites annonces |
  | *Gaming* | Événements gaming, Équipes, Jeux / Cartes, Recrutements, Palmarès, Partenaires |
  | *Savoir* | Wiki, FAQ, Dictionnaire, Téléchargements, Annuaire de liens, Carte des lieux |
  | *Médias* | Médias, Galeries, Fichiers, Webradio |
  | *Diffusion* | Newsletter, Templates emails, Flux RSS, Discord, Webhooks, API |
  | *Support* | Contact, Bugtracker, Modération |
  | *Monétisation* | Boutique, Dons, Paiements, Régie publicitaire |

  Une extension de la place de marché que ce rangement ne connaît pas va dans *Autres modules*. Le menu
  *Navigation* de l'éditeur en direct suit les mêmes rubriques, en sous-menus, avec une recherche.
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
| Gérer le forum et les commentaires ; la modération | *Communauté* ; *Support → Modération* |
| Créer les préfixes de sujet du forum (« Question », « Tutoriel »…), choisir l'icône d'un forum | *Communauté → Forum*, bouton « Préfixes » ; l'icône dans le formulaire du forum |
| Gérer membres, groupes, sessions | *Système → Utilisateurs* |
| Régler qui peut faire quoi | *Système → Permissions (matrice)* — grille rôle × action (vert = autorisé, gris = défaut, rouge = jamais) |
| Changer le thème / installer un addon | *Système → Thèmes & Addons* |
| Réglages du site (nom, accueil, inscriptions, sécurité, copyright) | *Système → Paramètres* |
| Composer les pages à la souris | *Système → Éditeur en direct* |
| Relier le site à un serveur Discord : rôles, pseudos, forum ↔ salon Forum, Bugtracker, rôles temporaires | *Diffusion → Discord* — voir [Le bot Discord](bot-discord.md) |
| Sauvegardes, mises à jour, état du site | *Système → Monitoring* |
| Lire les erreurs du site, retrouver celle qu'un visiteur a signalée | *Système → Monitoring → Journal des erreurs* |
| Relire les actions sensibles (réglages, addons, comptes, clés d'API) | *Système → Utilisateurs → Journal d'audit* |

> **Monitoring** réunit la **santé du site** (vérifications d'intégrité des fichiers, espace disque, infos
> serveur), les **mises à jour**, les **sauvegardes** (créer / télécharger / restaurer) et l'adresse de la
> **tâche planifiée** (cron) qui publie les contenus programmés.

### Le journal des erreurs

*Monitoring → Journal des erreurs* montre ce qui a échoué sur le site, sans passer par le FTP : les
erreurs regroupées (une même erreur vue cent fois reste une ligne), de la plus récente à la plus
ancienne, classées par gravité — **fatale**, **erreur**, **avertissement**, **information** —, avec leur
dernière occurrence complète à déplier. On filtre par gravité et par période, et on cherche un mot.

Quand une page plante, le visiteur lit « Une erreur est survenue » et une **référence** de huit
caractères (`3F9A1C07`) ; s'il vous la transmet, cherchez-la dans le journal : elle mène à la ligne
exacte. Les chemins du serveur, les mots de passe, les clés, les adresses e-mail et les adresses IP sont
masqués à l'écran ; le fichier entier se **télécharge** (pour le transmettre à qui vous aide) et se
**vide** (l'ancien est gardé à côté, `logs/php.log.1`). Le Monitoring signale aussi les erreurs des
dernières 24 heures, et un dossier `logs/` où le site ne peut plus écrire.

### Les outils de diagnostic

La carte *Monitoring → Diagnostic* allume trois outils pour une heure ; chacun s'éteint tout seul, ou
d'un clic. Plus besoin de modifier `config/neofrag.php` par FTP.

- **Mode débogage** : une barre apparaît en bas des pages (requêtes à la base, temps de calcul, mémoire,
  données de la visite), et une page qui plante montre le détail de l'erreur — **pour les
  administrateurs connectés seulement** : les visiteurs voient le site normal. Le journal sert à savoir
  qu'un problème existe ; le mode débogage, à le comprendre en le reproduisant.
- **Trace des pages** : chaque page servie, avec ses requêtes à la base et leur durée. *Lire la trace*
  montre les dernières pages, la plus récente d'abord, à chercher par adresse ; les valeurs passées aux
  requêtes sont masquées à l'écran, le fichier complet se télécharge. Volumineuse : le temps d'un
  diagnostic.
- **Relevé des traductions** : les textes sans traduction dans la langue de la visite sont notés, et
  listés dans *Traductions manquantes*, avec l'extension qui les emploie. Pendant ce temps, les
  administrateurs voient le drapeau de la langue devant chaque texte traduit : un texte sans drapeau
  n'est pas passé par la traduction.

Un outil allumé dans `config/neofrag.php` ne s'éteint que là ; la carte le signale.

### L'adresse du site

Les liens des courriels (mot de passe oublié, validation d'inscription), les retours des connexions
externes et les partages se construisent sur l'adresse enregistrée à l'installation. Après un
changement de domaine, la carte *Monitoring → Adresse du site* le signale : ouvrez l'administration par
la nouvelle adresse, puis *Utiliser https://…* l'enregistre (mot de passe webmaster demandé s'il est
défini).

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
d'accueil, fuseau horaire, gestion des inscriptions, sécurité anti-bots (captcha), maintenance,
copyright, réseaux sociaux. Ces réglages alimentent les thèmes et les widgets.

### Le fuseau horaire

Chacun voit les heures du site dans son propre fuseau horaire :

- un **membre** peut choisir le sien dans son profil (**Profil → Fuseau horaire**) ;
- sinon, le site prend celui de son **navigateur**, dès sa deuxième page ;
- sinon — première visite, robots, tâches automatiques —, celui du **site**, réglé dans
  **Paramètres → Préférences générales → Fuseau horaire**.

Une heure saisie dans un formulaire (le début d'un événement, la réouverture après une maintenance)
se comprend dans le fuseau de celui qui la saisit. Une date seule — un anniversaire, une journée
entière au calendrier — ne change jamais de jour. La grille d'une webradio est écrite à l'heure du
site.
