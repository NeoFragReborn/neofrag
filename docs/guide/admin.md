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

### Le Monitoring

*Système → Monitoring* résume l'essentiel en haut de page — version et mise à jour, PHP, sauvegardes,
outils de diagnostic allumés —, puis se lit en cinq onglets : *Vue d'ensemble* (alertes, santé du site,
stockage, adresse du site), *Sauvegardes*, *Diagnostic*, *Serveur et sécurité* (configuration, mot de
passe webmaster, tâche planifiée) et *Fichiers* (l'installation comparée à la version publiée). La page
revient sur l'onglet où l'on était ; une adresse comme `admin/monitoring#diagnostic` ouvre le bon.

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

L'onglet *Monitoring → Diagnostic* allume trois outils pour une heure ; chacun s'éteint tout seul, ou
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
changement de domaine, la carte *Adresse du site* (Monitoring, onglet *Vue d'ensemble*) le signale : ouvrez l'administration par
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
d'accueil, fuseau horaire, référencement, gestion des inscriptions, sécurité anti-bots (captcha),
maintenance, copyright, réseaux sociaux. Ces réglages alimentent les thèmes et les widgets.

### Le référencement

Ce que lisent les moteurs de recherche se fait **tout seul** :

- un **plan du site** par langue, réuni à la racine (`/sitemap.xml`) et annoncé par `robots.txt` —
  chaque module y donne ses pages publiques (le blog, le forum et ses sujets, le wiki, la galerie…),
  seulement celles qu'un visiteur peut lire ;
- dans chaque page, son **adresse de référence** (canonique), les **liens vers ses autres langues**, et
  ce que montrent les aperçus de partage (titre, description, image) ;
- sur l'accueil, des **données structurées** qui décrivent le site, son logo et ses réseaux sociaux
  (*Paramètres → Réseaux sociaux*) ;
- la recherche, les profils et l'annuaire des membres ne sont **pas** indexés.

Ce que tu remplis, dans **Paramètres → Référencement** :

- pour chaque langue, une **accroche** (le titre de l'accueil devient « Nom du site — accroche », 60
  caractères au plus) et une **description** (ce que les moteurs affichent sous le titre, 160 caractères
  au plus ; vide, celle des Préférences générales sert) ;
- une **image de partage** de 1 200 × 630 pixels, montrée quand on colle un lien du site sur Discord, X
  ou Facebook — à défaut, le logo ;
- le code de vérification de **Google Search Console** et de **Bing Webmaster Tools** : crée la
  propriété du site dans l'outil, choisis la vérification par **balise HTML**, colle la balise (ou le
  code seul), puis soumets-y le plan du site, `https://ton-site/sitemap.xml`.

### Le captcha

Le formulaire de contact et l'inscription demandent aux visiteurs une vérification anti-robot. Dans
**Paramètres → Captcha**, choisis le fournisseur :

- **ALTCHA** (par défaut, recommandé) : rien à configurer. Il est hébergé par ton site, sans compte ni
  cookie ; le visiteur voit une case qui se coche d'elle-même pendant qu'il remplit le formulaire.
- **Cloudflare Turnstile**, **hCaptcha** ou **Google reCAPTCHA v2** : crée un site dans leur console
  (liens sur la page), puis colle la **clé de site** et la **clé secrète**. La clé secrète est chiffrée
  et n'est jamais réaffichée. Sans ses deux clés, le fournisseur est remplacé par ALTCHA.
- **Aucun** : déconseillé, les robots écrivent alors librement.

ALTCHA rend l'envoi en masse coûteux pour un robot ; contre un attaquant obstiné, Turnstile ou hCaptcha
jugent davantage.

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
