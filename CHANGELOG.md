# Changelog

Tous les changements notables de **NeoFrag Reborn** sont consignés ici.

Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) ;
le projet suit le [versionnage sémantique](https://semver.org/lang/fr/).

NeoFrag Reborn est la continuité communautaire de **NeoFrag** (base Alpha 0.2.4), créé à l'origine par
Michaël BILCOT & Jérémy VALENTIN — projet open source sous licence LGPLv3.

---

## [1.2.23] — 2026-10-04

Le bot Discord passe en **version 0.2.1** : rien ne change dans son fonctionnement — il devient un projet
à part entière, avec sa licence, sa NOTICE et son propre journal des versions. L'installer n'est utile
qu'à qui veut ces fichiers ; la 0.2.0 continue de fonctionner.

### Modifié

- **Le plan du site n'annonce plus une rubrique vide** : des actualités, un recrutement, une FAQ, des
  équipes… sans rien à montrer ne sont plus proposés aux moteurs de recherche, qui n'y trouveraient
  qu'une page « rien pour l'instant ». La rubrique y revient d'elle-même avec son premier contenu. Le
  contact, le livre d'or, la newsletter, la webradio et les dons restent annoncés : ils ont toujours
  quelque chose à offrir.
- **Les sauvegardes ne s'accumulent plus** : chaque mise à jour en prend une complète (16 Mo et plus),
  et rien ne les retirait. Le site garde toujours les cinq plus récentes, et retire les autres passé
  trente jours ; un fichier déposé à la main dans `backups/` n'est jamais touché.
- **L'installation vérifie tout ce dont le CMS a besoin.** L'assistant ne contrôlait ni `openssl` (sans
  lui, enregistrer un secret — serveur d'e-mail, captcha, double authentification — tombe en erreur), ni
  `fileinfo` (sans lui, tout envoi de fichier est refusé), ni `iconv` (le QR code de la double
  authentification), ni le hachage des mots de passe en Argon2, faute duquel la création du compte
  administrateur échouait. Il les vérifie désormais et refuse de continuer s'il en manque un ;
  `install/cli.php` exige exactement la même liste, version de PHP comprise, au lieu de deux extensions.
  *Monitoring* et le tableau de bord de l'administration montrent la même liste. PHP 8.2 à 8.5.
- **nginx et Caddy** : le paquet livre enfin les exemples de configuration que les guides promettaient
  (`nginx.conf`, `Caddyfile`), génériques, et qui refusent les mêmes dossiers et fichiers sensibles que
  le `.htaccess` d'Apache — l'ancien exemple nginx laissait `install/`, `tools/`, `tests/` et `docs/`
  joignables, et l'extrait du guide de déploiement ne protégeait que trois dossiers sur huit.
- **Chaque addon dit qui l'a écrit.** Les addons du NeoFrag d'origine gardent leurs auteurs, Michaël
  BILCOT et Jérémy VALENTIN — les connecteurs de connexion et les langues, qui ne signaient rien, les
  nomment désormais, et les traductions anglaises d'origine ont retrouvé leurs pseudos, FoxLey et eResnova.
  Les portages créditent leur auteur : l'Horloge (ArkaNiX), le thème Extend (Chewbaka), le gestionnaire
  de fichiers (HiddenBlob, HiddenCMS) et les Dons (HiddenBlob, d'après majiid). Le reste est signé
  « NeoFrag Reborn ». Les thèmes Nebula, Blockcraft, Forge et Granite passent sous LGPL, comme le
  produit ; Extend garde la licence de son auteur (CC BY-NC-SA). La licence de chaque addon renvoie au
  texte officiel de la LGPL.
- **HSTS** : l'en-tête que pose le `.htaccess` ne s'étend plus aux sous-domaines et n'inscrit plus le
  site à la liste de préchargement des navigateurs (`includeSubDomains` et `preload` retirés) : posés
  d'office, ils engageaient pour un an tous les sous-domaines de qui installait le CMS.

### Corrigé

- **Un site installé depuis le paquet n'enregistrait aucune erreur.** Le paquet ne contenait pas le
  dossier `logs/`, et rien ne le créait : PHP écrivait ses erreurs dans le journal du serveur, *Monitoring
  → Journal des erreurs* restait vide et disait le dossier « non inscriptible ». Le paquet livre désormais
  `logs/`, `cache/` et `backups/` avec leur protection, et un site qui n'a pas `logs/` le recrée de
  lui-même : la mise à jour suffit à réparer un site déjà installé.
- **La documentation du wiki n'a plus de liens morts.** Trois renvois de ses pages vers des documents
  que le site n'a pas (la notice des outils, une partie du guide de déploiement) menaient à une page
  d'erreur ; ils ne gardent plus que leur texte. Le guide du marketplace ne parle plus d'addons « tiers »
  qui n'existent pas, ni de procédures réservées au serveur du projet.
- **Paramètres → Référencement** refusait l'enregistrement dès qu'une description, une accroche ou un
  titre pour les moteurs contenait des lettres accentuées en nombre : chacune comptait pour plusieurs
  caractères, et une description portugaise de 139 caractères dépassait la limite de 160. Le code de
  vérification de Google ou de Bing collé en balise entière était refusé de même. Les redirections
  et le référencement d'un contenu suivent la même règle.
- **Le bilan du référencement** reconnaît une Google Search Console vérifiée par le DNS du domaine (au
  lieu de la dire « non déclarée »), et conseille d'y soumettre le plan qui réunit toutes les langues,
  `/sitemap.xml`, et non celui de la seule langue affichée. Quand Google est vérifié, il ne dit plus Bing
  « non déclaré » : un site importé depuis Google Search Console n'y laisse aucune trace lisible.
- **`humans.txt`, `robots.txt` et la clé IndexNow** : une adresse absente — un `humans.txt` vide, une
  clé inconnue — répond un simple « introuvable », sans écrire une erreur au journal du site à chaque
  robot qui la demande.

---

## [1.2.22] — 2026-10-03

### Ajouté

- **Le référencement d'un contenu** : un bouton « Référencement » dans la carte d'édition d'une
  actualité, d'un billet du Blog, d'une page ou d'une page du wiki donne, pour chaque langue, le titre et
  la description que montrent les moteurs et les aperçus de partage. Vides, tout reste automatique.
- **Paramètres → Référencement → Bilan** : ce que voit un moteur de recherche, mesuré sur le site — les
  pages du plan par module, les textes de chaque langue, l'image de partage, Google et Bing, `robots.txt`,
  la maintenance —, avec pour chaque point le lien vers ce qui le corrige.
- **Les redirections** : une ancienne adresse mène à la nouvelle (301) au lieu de répondre « Page
  introuvable », avec le nombre de visites qu'elle reçoit encore. Une page ou une page du wiki renommée
  laisse la sienne d'elle-même ; on en ajoute à la main, pour l'adresse d'un ancien site par exemple.
- **Prévenir les moteurs (IndexNow)** : allumé dans Paramètres → Référencement, le site signale dans les
  minutes qui suivent chaque page qui paraît, change ou disparaît, à Bing, Yandex, Seznam, Naver, Yep et
  Amazon. Google n'y participe pas : pour lui, le plan du site reste la voie. Tout module qui annonce ses
  pages au plan du site est prévenu, sans rien de plus ; la tâche planifiée du site est nécessaire.

### Corrigé

- **Modération** : dans l'administration, un modérateur sans le droit « conversations privées » pouvait
  ouvrir le signalement d'un message privé, et cet accès n'était pas inscrit au journal d'audit. Celui
  qui signale un message privé voit de nouveau l'avertissement qui lui est destiné.
- **Messagerie** : le message envoyé au salon du staff depuis l'administration répondait toujours
  « Aucun salon staff configuré ».
- Des avertissements PHP au journal quand un signalement, un message ou un membre visés ont été
  supprimés.

---

## [1.2.21] — 2026-10-03

### Ajouté

- **Paramètres → Référencement** : pour chaque langue, l'accroche du titre de l'accueil et la
  description que montrent les moteurs ; une image de partage (1 200 × 630) pour les aperçus de Discord,
  X ou Facebook ; les codes de vérification de Google Search Console et de Bing Webmaster Tools.
- **Un plan du site par langue**, réuni à la racine (`/sitemap.xml`) : chaque module y annonce lui-même
  ses pages publiques — le blog, le forum et ses sujets, le wiki, la galerie, le calendrier, les
  événements… —, seulement celles qu'un visiteur peut lire et qui existent dans la langue du plan.
- Sur l'accueil, des **données structurées** qui décrivent le site, son logo et ses réseaux sociaux.

### Modifié

- **Les profils et l'annuaire des membres** ne sont plus proposés aux moteurs de recherche, ni les
  résultats d'une recherche : peu de contenu, et un membre n'a pas à se retrouver dans Google sans
  l'avoir choisi.

### Corrigé

- **Le plan du site** ne portait que des adresses relatives — ignorées des moteurs —, dans une seule
  langue, sans le wiki ni le forum ; `robots.txt` l'annonçait par une adresse relative.
- **Chaque page du Blog** se déclarait comme adresse de référence `/articles/…`, une redirection ;
  l'accueil, `/index`. Les liens entre langues sont complets, avec la version par défaut (`x-default`).
- **Le titre de l'accueil** ne répète plus le nom du site (« NeoFrag Reborn | NeoFrag Reborn »).
- **Google Analytics** : le réglage n'acceptait que l'ancien format `UA-…`, que Google a arrêté en
  2023 ; il accepte les identifiants actuels, `G-…`.
- **La langue du navigateur** : un visiteur dont le navigateur n'annonce que `de-DE` arrive en allemand,
  et non dans la langue par défaut du site.
- **Le sélecteur de langue** et le bandeau « ce contenu n'existe pas dans votre langue », depuis le
  Blog, menaient à son ancienne adresse.

### Sécurité

- **Les adresses complètes** de l'en-tête des pages (canonique, langues, partage) sont construites sur
  l'adresse du site, et non plus sur l'en-tête `Host` de la requête, que n'importe qui peut forger.

## [1.2.20] — 2026-10-03

### Ajouté

- **Un profil d'installation « Association / club »** : actualités, forum, galeries, calendrier, dons,
  newsletter, wiki et FAQ, sans l'attirail esport. Il s'ajoute à *Complet*, *Gaming / eSport*,
  *Communauté* et *Cœur seul*, dans les six langues de l'assistant.

### Corrigé

- **L'export des membres au format JSON** (RGPD, article 15) écrit de nouveau sa mention « Export des
  données membres » : elle sortait vide.
- **Blog** : sur la page d'un auteur, « Voir son profil » menait à une page introuvable.

### Sécurité

- **L'instantané de la démonstration n'emporte plus aucun secret.** Le fichier qui remet la démo à zéro,
  livré avec le paquet de démonstration, aurait recopié l'identifiant d'envoi des e-mails, la clé du
  service de traduction d'origine et les clés des widgets Twitch et TeamSpeak d'un site où elles
  auraient été saisies ; il portait encore la ligne, vide, de la clé secrète du captcha. Aucune clé
  n'avait fui. Un contrôle vérifie désormais la liste contre le code, et le fichier livré lui-même.

### Documentation

- NeoFrag Reborn se présente comme **le CMS libre des communautés, du gaming aux associations** ; le
  guide des concepts montre comment monter le site d'une association ou d'un club.

## [1.2.19] — 2026-10-02

### Ajouté

- **Un captcha moderne, actif dès l'installation : ALTCHA.** Il est hébergé par le site lui-même —
  sans compte, sans clé, sans cookie ni service tiers. Le visiteur voit une case qui se coche d'elle-même
  pendant qu'il remplit le formulaire : son navigateur fait un petit calcul, que le serveur vérifie. Il
  protège le formulaire de contact, l'inscription et le recrutement. À la mise à jour, un site sans clés
  reCAPTCHA passe sur ALTCHA ; un site qui en avait garde reCAPTCHA. ALTCHA rend l'envoi en masse coûteux
  pour un robot ; contre un attaquant obstiné, les fournisseurs ci-dessous jugent davantage.
- **Au choix, Cloudflare Turnstile, hCaptcha ou Google reCAPTCHA v2**, dans *Paramètres → Captcha*,
  avec le lien vers la console de chacun. La clé secrète est chiffrée et n'est jamais réaffichée. Sans
  ses deux clés, un fournisseur est remplacé par ALTCHA plutôt que de laisser le formulaire ouvert.
- Le captcha et ses messages existent dans les six langues.

### Corrigé

- **reCAPTCHA** : la clé secrète partait dans l'adresse de vérification ; elle part désormais dans le
  corps de la requête, comme Google le demande. Le lien vers sa console, périmé, est remplacé, et
  l'adresse du visiteur transmise suit la règle du reste du site, qui tient compte d'un mandataire
  déclaré de confiance.
- Quand la vérification anti-robot manque, le message s'écrit en clair sous le captcha, au lieu d'une
  icône seule dont la bulle n'apparaît pas sur un écran tactile.

### Sécurité

- **Réussir le captcha une fois ne dispense plus de le refaire.** Depuis NeoFrag, un captcha réussi
  dispensait de le repasser pour tous les envois suivants du même formulaire, jusqu'à la fin de la
  session : un robot qui résolvait un seul défi pouvait ensuite envoyer sans limite. La dispense ne
  vaut plus que pour un seul renvoi, après une autre erreur dans le formulaire.
- Une solution ALTCHA ne sert qu'une fois : la présenter de nouveau est refusé.
- La politique de sécurité des pages n'ouvre plus Google par défaut : seulement les adresses du
  fournisseur de captcha choisi, et aucune avec ALTCHA.

## [1.2.18] — 2026-10-02

### Corrigé

- **Réordonner par glisser-déposer fonctionne de nouveau** : les catégories et les forums du forum, les
  équipes et leurs rôles, les partenaires, les groupes de membres, les langues et les connecteurs de
  connexion, et les rangées, colonnes et widgets de l'éditeur en direct. La position arrivait du
  navigateur en texte, et le déplacement s'arrêtait sur une erreur — repéré dans le journal de la
  démonstration.
- **Sur la démonstration, l'éditeur en direct** ouvre de nouveau les réglages d'un widget, et toute
  modification y reçoit le message « Action désactivée sur le site de démonstration. » au lieu d'une
  erreur muette. Les mises en page n'y sont pas modifiables : un widget HTML écrit par un visiteur
  s'afficherait chez tous les autres.

### Documentation

- **Le guide d'installation par FTP** désigne le bon paquet (`neofrag-reborn-public-<version>.zip`),
  décrit la mise à jour par le bouton, et ne demande plus de lancer un outil absent du paquet : les
  migrations s'appliquent seules à la première visite, démonstration comprise.
- **Les guides du développeur** décrivent les adresses saisies dans un lien (`nf_url_sure()`), le verrou
  du site de démonstration, les listes découpées en pages, les compteurs et les dates ; les chiffres du
  README et des guides suivent le code (62 modules, 40 widgets).

## [1.2.17] — 2026-10-02

### Sécurité

- **Sur la démonstration, l'onglet Fichiers du Monitoring** montrait encore l'arborescence réelle de
  l'installation (des noms de dossiers et de fichiers, sans leur contenu) : il montre désormais un arbre
  d'exemple, comme le gestionnaire de fichiers.

## [1.2.16] — 2026-10-02

### Sécurité

- **Un lien piégé ne s'exécute plus depuis l'adresse d'un signalement.** Le membre qui signale fournit
  cette adresse, montrée ensuite au modérateur : un « javascript: » s'exécutait chez lui au clic. Même
  garde sur les liens d'un flux RSS (widget), d'un diaporama, de l'annuaire de liens, des partenaires et
  de toute adresse que le site fabrique : seules passent les adresses web, de courriel, de téléphone ou
  internes au site.
- **Supprimer un rôle, créer la clé du bot Discord, clôturer une petite annonce ou verrouiller un sujet
  du forum** se faisaient par un simple lien : un lien piégé suffisait à déclencher l'action chez un
  administrateur, un auteur ou un modérateur connecté. Ces liens portent désormais un jeton de sécurité.
- **La démonstration verrouillée.** Son compte partagé (demo / demo) est administrateur : il pouvait
  allumer le mode débogage pour tous les visiteurs, parcourir et lire ses fichiers, le journal des
  erreurs et le phpinfo, voir la clé de la tâche planifiée, modifier les réglages du site, créer des
  clés d'API, bannir des adresses IP ou des membres pour de bon, détourner l'adresse PayPal des dons, et
  effacer des images que la remise à zéro ne savait pas rendre. Sur une démonstration, toute action vers
  une partie verrouillée est refusée avant d'atteindre son écran, les écrans sensibles affichent un
  avis, le gestionnaire de fichiers ne montre qu'une arborescence d'exemple, aucun fichier n'est
  supprimé, et la remise à zéro rétablit aussi les cartes et modes de jeu, les rôles d'équipe, les
  notifications, les points et les permissions.

### Corrigé

- **Un fichier refusé à l'envoi** (extension interdite, ou démonstration) faisait tomber la page
  (erreur 500), par exemple en changeant d'avatar : le champ dit maintenant pourquoi.
- **Les statistiques sur une période démesurée** (des siècles, heure par heure) épuisaient la mémoire
  du serveur : la période et le nombre de points sont bornés.
- **L'export des membres** écrivait un avertissement PHP 8.5 par ligne au journal.
- **Sur la démonstration**, la bannière verte recouvrait les notifications, et le compte de secours
  apparaissait dans la liste des joueurs d'une équipe et l'activité du tableau de bord.
- **Un liseré blanc longeait le bord droit de l'éditeur de texte en mode sombre** (commentaires,
  messagerie, administration) : l'éditeur peignait son cadre de saisie en blanc, et ce blanc dépassait
  d'une fraction de pixel.

## [1.2.15] — 2026-10-02

### Modifié

- **La recherche rapide de l'administration (Ctrl+K), reprise** : les rubriques en petites capitales
  discrètes, alignées sur les icônes, au lieu de titres collés au bord ; le nom de la rubrique n'est
  plus répété au bout de chaque ligne ; une loupe dans le champ ; la partie trouvée en surbrillance. La
  recherche ignore les accents (« evenements » trouve « Événements gaming ») et cherche aussi dans les
  rubriques (« gaming » liste les six modules de la rubrique).
- **La photo du membre dans la barre latérale de l'administration**, au lieu de l'initiale de son
  pseudo, qui reste pour un compte sans photo.

### Corrigé

- **Les flèches du clavier, dans la recherche rapide**, déplaçaient une sélection invisible : seule la
  souris surlignait une ligne.
- **« Ce qu'apporte cette version »**, dans la fenêtre de mise à jour, ouvrait le journal des versions
  en haut de la page, sur la version précédente : il mène maintenant à la version annoncée.
- **Le cadre des commentaires d'un billet du Blog** perdait sa marge intérieure et la ligne sous son
  titre : « Commentaires » et le compteur touchaient le bord. Cinq thèmes appliquaient à tout cadre
  imbriqué une règle prévue pour l'en-tête des cadres transparents.

## [1.2.14] — 2026-10-02

### Modifié

- **La page Monitoring, en onglets** : un résumé toujours visible (version et mise à jour, PHP,
  sauvegardes, outils de diagnostic), puis *Vue d'ensemble*, *Sauvegardes*, *Diagnostic*, *Serveur et
  sécurité* et *Fichiers*. Elle empilait une dizaine de cartes dans une colonne étroite, sur près de
  2 700 pixels ; chaque onglet tient maintenant sur un écran. La santé du site se dit sobrement (« Tout
  va bien », « Des points à vérifier », « Des erreurs à corriger »).
- **Les boutons d'action de l'administration, les mêmes partout** : modifier, accès et trier en gris
  neutre ; supprimer en contour rouge, avec une corbeille. Ils changeaient d'une page à l'autre — bleu
  ciel, teal, boutons pleins, croix ou corbeille. La charte de l'administration est décrite dans le guide
  « Créer un module », et un contrôle la fait respecter.
- **Les pastilles d'état, douces partout** : un fond pâle et un texte appuyé de la même couleur, comme
  « Publié ». « Actif » s'écrivait blanc sur vert plein, « Built-in » en noir. Plus aucun bouton plein
  de couleur dans une ligne (aperçu, dupliquer, restaurer…).
- **Le bouton qui crée, au même endroit partout** : en haut de la carte de la liste qu'il alimente.
  Huit pages le mettaient dans la barre du haut, au pied de la liste ou au-dessus de la carte
  (publicités, boutique, dons, paiements, diaporama, équipes, jeux, sauvegardes du Monitoring).
- **Thèmes & addons, compact** : une recherche, le nombre d'extensions de chaque type, les filtres de
  type et de statut qui se combinent (« les modules inactifs »), et une vue en liste, par défaut, à côté
  de la grille, plus dense. La page s'ouvre sur les modules ; elle alignait les quelque 120 extensions en
  grandes cartes, sur plus de 10 000 pixels.
- **Templates emails, en un seul tableau** : les modèles rangés par famille (comptes, forum,
  messagerie, modération, newsletter), avec leur objet, leurs langues et leur statut sur une ligne. La
  page empilait une carte par modèle, sur 2 000 pixels ; elle en fait la moitié.
- **Discord, en onglets** : *Vue d'ensemble*, *Fonctionnalités*, *Mise en place du serveur*, *Salons et
  forums*, *Groupes et rôles*, *Rôles temporaires* — on passe de l'un à l'autre sans revenir à la page
  principale. Le journal du bot défile dans sa carte au lieu d'allonger la page.
- **Statistiques, plus lisibles** : les dates et le pas tiennent sur une ligne. Le graphique ne répète
  plus en légende les cases à cocher, qui portent déjà la couleur de chaque série. Ses courbes ne
  plongent plus sous zéro et son axe s'arrête aux bornes de la période choisie. Ses dates suivent la
  langue du site (« 2 oct. 2025 », et non « Oct 2, 2025 »). Au-delà de trois séries, des lignes seules
  plutôt que des aires qui se recouvrent.
- **Permissions → Vue matricielle** : les modules rangés par rubrique, comme dans la barre latérale, en
  colonnes. Ils s'alignaient en une quarantaine de grandes tuiles, sans ordre.
- **Le journal d'audit, par pages de 50** : il alignait 200 lignes d'un bloc, sur plus de 5 000 pixels.
- **Utilisateurs : une recherche** par pseudo ou par e-mail, au-dessus de la liste des membres.
- **Le gestionnaire de fichiers dit ce que font ses boutons** : « Ajouter des fichiers », « Créer un
  dossier », « Déplacer », « Supprimer », au lieu de quatre pastilles de couleur à icône seule. Ses
  fenêtres ont un « Annuler » neutre et une validation nommée.
- **« Enregistrer » sur le bouton qui valide une modification**, partout. Vingt formulaires disaient
  « Éditer » (forum, actualités, pages, événements, équipes, jeux, galeries…).

### Corrigé

- **Des listes de l'administration s'arrêtaient à leur première page**, sans lien vers la suite : les
  membres et les commentaires au-delà des 20 premiers, les palmarès et les recrutements au-delà des 10
  premiers, ainsi que « Mes abonnements » du forum côté membre. Le compteur de la carte ne disait que
  la page affichée (« 20 membres » pour 25).
- **Les dates saisies en anglais et en allemand.** En anglais, le sélecteur écrivait le 2 octobre
  « 10/02/2026 g:05 A », pré-remplissait un événement du 30/09/2026 au « 06/09/2028 », et le 2 octobre
  s'enregistrait 10 février. En allemand, une date affichée « 02.10.2026 » n'était pas relue à
  l'enregistrement. Les formats courts de l'anglais suivent désormais ceux de ses traductions
  (« 02/10/2026 14:05 ») ; un test vérifie, dans les six langues, qu'une date affichée se relit.
- **Plus de fausses alertes « fichier corrompu » avant une mise à jour.** Le Monitoring comparait les
  fichiers du site à la liste de contrôle de la dernière version publiée, même quand le site n'était
  pas encore à son niveau : tout ce que la nouvelle version change y paraissait corrompu (102 alertes
  sur un site juste avant son passage en 1.2.13). La vérification attend maintenant que le site
  soit à jour, et le dit.
- **Statistiques** : en allemand, le graphique ne se chargeait jamais ; les boutons « 7 jours »,
  « 30 jours », « 90 jours » et « 1 an » ne faisaient rien, dans aucune langue.
- **« Modifiée le », sur une page du wiki**, donnait l'heure de la dernière visite : le compteur de vues
  réécrivait la date de modification. Même défaut sur les petites annonces.
- **Dans la fenêtre de suppression du gestionnaire de fichiers**, « Annuler » et « Supprimer » étaient
  deux corbeilles rouges identiques. Les textes du module avaient perdu leurs accents (« Dossier cree
  avec succes », « Element deplace ») et comptaient en « élément(s) ».
- **Le tableau de bord et le journal d'audit** montrent ce qui s'est passé (« Mode débogage allumé »,
  « Paramètres enregistrés ») et non plus des identifiants techniques (`monitoring.debogage.allume`).
- **La page Commentaires de l'administration** affichait du code à la place de la date de chaque
  commentaire.
- **Un titre accentué** (« journ&amp;eacute;e ») ne s'affiche plus en code dans l'en-tête des cartes
  de l'administration.
- **En mode sombre**, les textes colorés de l'administration (alertes, pastilles, `.text-success`)
  restaient sombres sur fond sombre, presque illisibles.
- **Les dates de la modération** (sanctions, signalements, historique d'un membre), **de la corbeille,
  des sauvegardes, des notifications et du journal du bot Discord** s'affichent dans la langue et à
  l'heure du visiteur (« 21/09/2026 22:54 »), et non plus telles qu'en base (« 2026-09-21 20:54:11 »).
- **« Connexions de membres »**, dans les statistiques, s'écrivait « Connections ».
- **Les dates suivent la langue du visiteur** : le wiki, le Bugtracker, les petites annonces, le livre
  d'or, la newsletter, les conversations archivées, le gestionnaire de fichiers et le widget des
  événements les écrivaient en dur — à l'anglaise (« 2026-09-20 22:54 ») ou à la française même en
  allemand (« 02.10.2026 » attendu).

## [1.2.13] — 2026-10-02

### Ajouté

- **Le journal des erreurs, dans l'administration** (*Système → Monitoring → Journal des erreurs*). Plus
  besoin du FTP ni d'un accès au serveur pour savoir ce qui a échoué : les erreurs du site, regroupées,
  de la plus récente à la plus ancienne, classées par gravité, à filtrer par période ou par mot. Les
  chemins du serveur, les mots de passe, les clés, les adresses e-mail et IP sont masqués à l'écran ; le
  fichier se télécharge et se vide (l'ancien est gardé à côté). Le Monitoring signale les erreurs des
  dernières 24 heures, et un dossier `logs/` où le site ne peut plus écrire.
- **Les outils de diagnostic s'allument depuis l'administration** (*Monitoring → Diagnostic*), pour une
  heure, sans modifier `config/neofrag.php` par FTP :
  - **le mode débogage** — ce qu'il affiche, la barre en bas de page et le détail des erreurs, ne se
    montre plus qu'aux administrateurs connectés ; allumé, il s'affichait à tous les visiteurs, requêtes
    et données de la visite comprises ;
  - **la trace des pages**, qui se lit enfin dans l'administration : les dernières pages servies, leurs
    requêtes et leur durée, les valeurs sensibles masquées ;
  - **le relevé des traductions**, avec la liste des textes manquants par langue et par extension ; le
    drapeau qu'il ajoute devant les textes traduits ne se montre plus qu'aux administrateurs.
- **L'administration rangée en neuf rubriques** : *Contenu*, *Communauté*, *Animation*, *Gaming*,
  *Savoir*, *Médias*, *Diffusion*, *Support*, *Monétisation* (et *Système*). Les six précédentes
  mêlaient le calendrier aux médias ou le Bugtracker à la communauté, et treize modules finissaient dans
  « Autres modules ».
- **Le menu « Navigation » de l'éditeur en direct** suit les mêmes rubriques, en sous-menus repliables,
  avec une recherche ; il alignait plus de quarante pages à la suite.
- **L'adresse du site se corrige depuis l'administration** (*Monitoring → Adresse du site*). Après un
  changement de domaine, les liens des courriels menaient encore à l'ancien tant qu'on ne modifiait pas
  `config/url.php` par FTP.
- **Une référence pour chaque erreur.** Le visiteur qui tombe sur une erreur lit une référence de huit
  caractères ; l'administrateur la cherche dans le journal et tombe sur la bonne ligne.
- **Les heures dans le fuseau de chacun.** Un membre choisit son fuseau horaire dans son profil ; à
  défaut, le site prend celui de son navigateur, et à défaut celui du site, nouveau réglage de
  *Paramètres → Préférences générales*. Les heures saisies dans les formulaires se comprennent dans le
  fuseau de celui qui les saisit. La liste des fuseaux est groupée par région, les villes nommées dans
  la langue du site.

### Corrigé

- **Les heures n'ont plus deux heures de retard.** Le site les affichait à l'heure du serveur — l'heure
  universelle — pour tout le monde. Le fuseau du profil membre, lui, ne s'appliquait que sur les pages
  du profil, et décalait les dates que ce membre faisait enregistrer. Le calendrier, le wiki, le
  Bugtracker, les conversations archivées et le gestionnaire de fichiers écrivaient leurs heures sans
  conversion ; l'émission « en direct » de la webradio se lisait à l'heure du serveur.
- **Le calendrier** : le début et la fin d'un événement se choisissent dans un sélecteur de date, et
  non plus dans un champ texte au format « YYYY-MM-DD HH:MM:SS » ; un titre accentué ne s'affiche plus
  « journ&amp;eacute;e » ; la description mise en forme ne montre plus ses balises ; l'export agenda
  reçoit un texte propre.
- **Les listes avec recherche** disent « Aucun résultat » dans la langue du site, et non « No results
  found ».
- **L'éditeur en direct** proposait de composer le Forum, les Galeries, les Équipes, le Contact et le
  Palmarès : ils manquaient à son menu, faute d'une route déclarée pour leur page d'accueil.

- **Une page qui plante dit « Une erreur est survenue » (500)**, avec sa référence, au lieu de « Page
  introuvable », qui faisait croire à une mauvaise adresse.
- **Plus de page blanche.** Une erreur fatale, ou une exception hors des pages, affiche une page d'erreur
  dans la langue du visiteur. La base de données injoignable aussi (503), au lieu d'un message en anglais
  brut, et la panne est enfin notée au journal.
- **Une action qui échoue le dit** : une fenêtre qui ne s'ouvre pas, un envoi refusé, le serveur qui ne
  répond plus affichent un message, avec la référence de l'erreur — au lieu d'un bouton resté grisé sans
  un mot. Le Monitoring qui ne parvient pas à s'actualiser arrête son sablier.
- **La sauvegarde et la mise à jour disent la vérité.** Elles annonçaient « Sauvegarde réalisée » ou
  « Mise à jour effectuée avec succès » même après un échec ; elles n'annoncent plus le succès que si le
  serveur le confirme, et disent sinon ce qui a échoué. La sauvegarde vérifie aussi qu'elle a bien écrit
  son archive : dans un dossier non inscriptible ou sur un disque plein, elle se disait réussie — et la
  mise à jour, qui s'appuie sur elle pour revenir en arrière, n'aurait rien eu à remettre.
- **L'erreur d'un champ de formulaire se lit sous le champ**, et non plus seulement au survol d'une
  petite icône.
- Un formulaire envoyé sans un de ses champs, et une requête sans résultat lue comme une recherche, ne
  laissent plus d'alerte au journal — la seconde faisait tomber la page.
- **La barre de débogage s'affiche de nouveau** : un nombre la faisait tomber tout entière depuis le
  passage du code en typage strict, et son calcul de chronologie était faux.

- **L'annonce d'une mise à jour se voit de nouveau.** Elle n'était plus qu'un « 1.2.x » sans couleur
  dans la barre du haut : un nettoyage du style de l'administration avait emporté le sien. Elle
  retrouve un encart sous le logo — « Mise à jour disponible · NeoFrag X.Y.Z » — et une pastille
  lisible dans la barre du haut.
- **La fenêtre de mise à jour** montrait un bloc vide et laissait un avertissement au journal : elle dit
  maintenant quelle version arrive, que le site est sauvegardé avant de commencer et revient à son état
  d'avant en cas d'échec, avec un lien vers ce qu'apporte la version.

### Retiré

- **`NEOFRAG_LOGS_DB`**, que rien ne lisait : les installations neuves ne l'écrivent plus dans
  `config/neofrag.php`. Une installation existante peut le garder, il reste sans effet.

## [1.2.12] — 2026-10-02

Le bot Discord passe en **version 0.2.0** : remplace son dossier par celui de la nouvelle archive (en
gardant `.env`), puis crée une nouvelle clé d'accès dans l'administration — la page le demande — pour
qu'il reçoive les droits du Bugtracker.

### Ajouté

- **Bot Discord : des fonctionnalités qui s'allument une à une.** *Discord → Fonctionnalités* liste ce
  que le bot sait faire — il le déclare lui-même — ; chacune s'allume, s'éteint et se règle depuis
  l'administration, appliquée dans la minute sans redémarrer. Le bouton **Resynchroniser** remet tout
  d'accord, et le forum rattrape au démarrage ce qui s'est écrit sur Discord pendant une absence du
  bot.
- **Bot Discord : la mise en place du serveur.** Depuis l'administration, le bot crée sur Discord une
  catégorie, un salon Forum par forum choisi (ses préfixes en étiquettes) et un rôle par groupe, pose
  les correspondances lui-même, et reprend au lieu de dédoubler ce qui existe déjà. Un aperçu précède,
  et la dernière mise en place s'annule.
- **Bot Discord : `/forum`.** `/forum account link` relie son compte Discord à son compte du site par
  un lien à usage unique (et reprend à son nom ce qu'on avait publié depuis Discord) ; `/forum account
  unlink` le délie. Sans compte relié, `/forum visibility` choisit comment on paraît sur le forum : son
  pseudo Discord, un nom anonyme, ou un pseudo choisi, changeable tous les sept jours — les messages
  déjà publiés suivent.
- **Bot Discord : préfixes du forum ↔ étiquettes des salons Forum**, dans les deux sens : poser un
  préfixe sur un sujet pose l'étiquette sur le fil, et l'inverse.
- **Bot Discord : le Bugtracker dans un salon Forum** (fonctionnalité à allumer dans *Discord →
  Fonctionnalités*). Chaque ticket devient un fil : son type et son statut en sont les étiquettes, que
  le bot crée dans le salon et tient à jour. Le titre, la description et la priorité suivent ; le fil
  s'archive quand le ticket est clos, et un doublon renvoie à son ticket d'origine. Les commentaires
  passent dans les deux sens. Depuis Discord, `/bug` et `/idee` ouvrent un ticket par une petite
  fenêtre (titre, description), et un fil ouvert à la main dans le salon devient un ticket — pour un
  membre qui a relié son compte. Les tickets encore ouverts reçoivent leur fil quand on choisit le
  salon. Le site fait foi : une étiquette changée à la main sur Discord est remise comme le dit le
  ticket.
- **Bot Discord : les rôles temporaires** (fonctionnalité à allumer). La commande `/role`, réservée à
  qui peut gérer les rôles, donne un rôle à un membre pour une durée — une sanction, un accès d'essai,
  un rôle d'événement —, le retire ou montre ceux en cours. Le bot le retire à l'échéance, même après
  un redémarrage, et le redonne à un membre qui quitte puis rejoint le serveur pour y échapper. Les
  rôles reliés à un groupe du site, ceux tenus par Discord et ceux placés au-dessus du bot ou de qui
  les donne sont refusés. La page *Discord → Rôles temporaires* les liste, avec « Retirer maintenant ».
- **API** : la liste des tickets (`GET bugtracker/tickets`, les seuls ouverts avec `open=1`), et les
  rôles temporaires du bot (`discord/timed-roles`).

### Sécurité

- **Un titre écrit par l'API ne peut plus injecter de code dans les pages du site.** Le titre d'un
  sujet créé par l'API — par exemple le nom d'un fil Discord recopié par le bot — était rangé tel
  quel, alors que le forum affiche ses titres sans les réencoder : une balise dans ce titre
  s'exécutait sur la page du forum. L'API range maintenant ses textes comme le site range les siens,
  et le titre de l'onglet est toujours encodé.

### Corrigé

- **Les accents du Bugtracker** s'affichaient « r&eacute;agit » sur la page d'un ticket ouvert par le
  formulaire, et la recherche « Déjà signalé ? » ne trouvait aucun mot accentué. Les textes que l'API
  rend — pseudos, titres, descriptions, commentaires — sont aussi en clair : le bot Discord les
  recopiait encodés. Les aperçus de partage d'un lien (Discord compris) ne montrent plus
  « &amp;eacute; ».
- **Bugtracker** : choisir le statut « Doublon » sans numéro de ticket valable gardait l'ancien
  statut en annonçant « Ticket mis à jour » ; le message dit maintenant ce qui n'a pas été appliqué.
- **Le wiki** : une page sans sous-pages s'affichait comme un dossier vide sur l'accueil du wiki ; elle
  montre maintenant son sommaire.
- **Les pages introuvables et interdites ne sont plus des pages vides.** Sur tous les thèmes publics, une
  adresse qui ne mène à rien (404) ou une page réservée (403) n'affichait que l'en-tête et le pied de
  page, sans un mot — défaut hérité de NeoFrag. Elles disent maintenant ce qui se passe, dans les six
  langues, avec un bouton pour revenir à l'accueil (au tableau de bord en administration), et l'onglet
  du navigateur porte « Page introuvable » au lieu du nom du module.

## [1.2.11] — 2026-10-01

### Ajouté

- **Le bot Discord** (version 0.1.0), qui relie un site et son serveur Discord :
  - **rôles et pseudos** — un membre qui a lié son compte Discord reçoit sur le serveur les rôles reliés
    à ses groupes et les perd en les quittant ; il y porte son pseudo du site si l'option est cochée.
    Le site fait foi, et un rôle que rien ne relie n'est jamais touché ;
  - **forum ↔ salon Forum de Discord**, dans les deux sens — un sujet du site devient un fil, un fil
    devient un sujet ; réponses, modifications et suppressions suivent. Les messages venus du site
    paraissent sous le nom et l'avatar de leur auteur ; ceux venus de Discord, sous le compte du membre
    lié ou sous son pseudo Discord. Salon par salon : tout synchroniser, ou seulement les fils qu'un
    modérateur marque d'une réaction.

  C'est un programme à part (Node.js 22.9 ou plus, sur une machine allumée en permanence), livré dans
  sa propre archive à chaque version. Il ne garde sur sa machine que l'adresse du site et une clé
  d'accès. Guide : « Le bot Discord » dans le wiki.
- **Le module Discord** (optionnel, demande le module API) : la clé du bot, **gardée chiffrée**, le
  serveur, l'interrupteur marche / pause, le redémarrage, l'état du bot (en ligne, en pause, hors
  ligne) et son **journal dans la langue de l'administrateur**, les correspondances salons ↔ forums et
  groupes ↔ rôles, et le lien qui invite le bot avec ses seules permissions — jamais « Administrateur ».
- **API** : le droit `discord:bot` et les adresses `discord/*` dont le bot a besoin.

## [1.2.10] — 2026-10-01

### Ajouté

- **Une API REST** (module **API**, optionnel), pour les programmes qui parlent au site sans
  navigateur — en premier lieu le futur bot Discord. Un administrateur crée des **clés d'accès** avec
  les seuls droits voulus ; une clé n'est montrée qu'une fois, le site n'en garde que l'empreinte, et
  elle se révoque d'un clic. Les adresses `/api/v1/…` rendent du JSON : l'état du site, un membre (par
  identifiant ou par compte Discord lié), les groupes, le forum (arborescence, sujets, messages) et un
  **fil d'événements** (nouveau sujet, nouveau message, changement de groupe…). Elle **écrit** aussi
  sur le forum au nom d'un membre, ou d'un compte Discord non lié — publié sous son pseudo Discord,
  marqué du logo Discord. Débit limité à 120 requêtes par minute et par clé ; les erreurs ont des codes
  stables. Guide : « L'API REST » dans le wiki.
- **Mes comptes liés** (espace membre) : voir ses comptes Discord, GitHub ou Google liés, en lier un, en
  délier un — sauf s'il est le seul moyen de se connecter.
- **S'inscrire avec Discord** (ou GitHub, Google) : un compte que personne n'a lié crée un membre, lié
  d'emblée, quand les inscriptions sont ouvertes. Il recevait jusqu'ici « Compte inconnu ».

### Corrigé

- **Lier son compte Discord connectait au compte d'un autre membre** quand ce Discord était déjà lié à
  ce dernier : la session basculait sur lui. La liaison est désormais refusée, avec un message clair, et
  un même compte externe ne peut plus être lié à deux membres.

## [1.2.9] — 2026-10-01

### Corrigé

- **Blog : le sommaire d'un billet restait titré « Sommaire » dans les autres langues** (et l'écrivait au
  journal). Un message de l'administration des membres avait le même défaut.
- **Blog : la pastille de catégorie s'étirait sur toute la largeur des cartes** au lieu de rester une
  petite étiquette.

## [1.2.8] — 2026-10-01

### Ajouté

- **Blog : les séries.** Un billet en plusieurs parties : chaque partie affiche la liste des autres et
  sa place (« Partie 2 sur 3 »), et la série a sa page. Les séries se gèrent dans l'administration du
  Blog, à côté d'une nouvelle page qui liste enfin les catégories.
- **Blog : une page par auteur** (ses billets) et **des archives par mois** cliquables.
- **Blog : le partage montre la couverture.** Un billet partagé sur un réseau social affiche sa
  couverture et non le logo du site ; les moteurs de recherche reçoivent ses données structurées
  (titre, auteur, date, image).
- **Six widgets Blog** : les derniers billets avec leur vignette, les plus lus, le billet à la une, les
  catégories, les tags et les archives.

### Corrigé

- **Forum : la liste des forums disparaissait pour les membres connectés** dès qu'un forum n'avait
  de sujets que dans ses sous-forums. Les visiteurs n'étaient pas touchés.
- **La désinscription de la newsletter ne désinscrivait personne** : la page de désinscription tombait
  en erreur. La suppression d'un abonné dans l'administration aussi.
- **Supprimer son compte** marquait le compte supprimé, puis tombait en erreur avant de fermer ses
  sessions et d'effacer ses codes de secours.
- **Désactiver la double authentification** laissait les anciens codes de secours en base.
- **Blog : une catégorie vide ne pouvait pas être supprimée** — le Blog la croyait toujours occupée —, et
  sa suppression ne demandait pas de confirmation protégée.

## [1.2.7] — 2026-10-01

### Corrigé

- **Forum : un forum-lien affiche l'icône choisie** dans l'administration, au lieu du globe d'office.

## [1.2.6] — 2026-10-01

### Ajouté

- **Forum : la réponse « solution ».** L'auteur d'un sujet (ou un modérateur) marque la réponse qui
  le résout. Elle est mise en avant sous la question, signalée dans le fil, et le sujet s'affiche
  « Résolu » dans la liste. Une solution supprimée ou déplacée ne laisse pas le sujet résolu.
- **Forum : les préfixes de sujet** (« Question », « Tutoriel », « Important »…), créés par
  l'administrateur avec leur couleur et traduits dans chaque langue. Le membre en choisit un en
  ouvrant son sujet, et la liste d'un forum se filtre dessus.
- **Forum : une icône par forum**, choisie dans l'administration.

### Corrigé

- **Le widget « Statistiques » du forum** comptait aussi les catégories que le visiteur ne peut pas
  lire, et les messages supprimés : il ne compte plus que ce que le visiteur peut aller voir.
- **Les données de démonstration du forum** comptaient le premier message comme une réponse (« 4
  réponses » dans la liste pour un sujet qui en a 3).

## [1.2.5] — 2026-10-01

### Ajouté

- **Un vrai Blog.** Le module Articles devient le Blog, sous `/blog` (les anciennes adresses `/articles/…`
  redirigent définitivement). La liste s'ouvre sur un billet **à la une**, puis des cartes illustrées,
  des filtres par catégorie et une barre latérale (recherche, les plus lus, catégories, tags, archives).
  La fiche d'un billet a sa couverture titrée, une barre de progression de lecture, un sommaire qui suit
  la partie en cours, l'auteur, les billets voisins et « à lire aussi ». L'administrateur choisit la
  mise en page de la liste et de la fiche ; le visiteur passe de la grille aux lignes, et son choix est
  retenu.
- **Bugtracker : la liste se filtre par type** (bogue, demande de fonctionnalité, question, autre), et
  « Nouveau ticket » garde le type choisi. On voit ce qui est déjà signalé avant d'ouvrir un ticket.
- **Bugtracker : « Déjà signalé ? »** Pendant qu'on écrit le titre d'un nouveau ticket, les tickets
  ouverts qui lui ressemblent s'affichent dessous, pour commenter l'existant plutôt qu'en ouvrir un
  second. Un ticket peut aussi être marqué **doublon** d'un autre : sa page renvoie vers l'original.

## [1.2.4] — 2026-10-01

### Ajouté

- **Forum : des catégories et des forums traduisibles.** Chacun garde son titre par défaut et peut
  recevoir un titre (et une description) par langue active, dans son formulaire d'administration. Le
  visiteur voit la traduction de sa langue, sinon le titre par défaut. Une adresse reste valable avec
  le titre par défaut comme avec chaque traduction.

### Corrigé

- **Une migration déjà en place ne bloque plus la mise à jour.** Appliquée à la main sans être
  enregistrée, elle échouait (« colonne déjà présente ») et aurait annulé la mise à jour entière. Les
  migrations s'appliquent désormais instruction par instruction, en ignorant seulement ce qui est déjà
  fait.

- **Les modules livrés avec le cœur reçoivent enfin leurs changements de base de données.** Seule la
  mise à jour d'un addon par la place de marché les appliquait : un module comme le forum ou le
  calendrier recevait son code neuf par la mise à jour du cœur, jamais sa base. Ils s'appliquent
  désormais avec ceux du cœur, par le bouton comme après un dépôt FTP.
- **Articles** : les six droits du module (ajouter, modifier, supprimer, et leurs équivalents pour les
  catégories) n'étaient vérifiés nulle part. Ils le sont, actions groupées comprises.
- **Un contenu programmé n'apparaît plus avant sa date**, ni un contenu mis à la corbeille : le widget
  « Articles récents », le plan du site envoyé aux moteurs, la recherche des actualités, l'activité
  d'un membre et le bloc « derniers articles » les montraient. Le widget Tags des actualités ne mêle
  plus les langues, et celui des catégories ne compte plus les brouillons.

## [1.2.3] — 2026-10-01

### Corrigé

- **La mise à jour se met enfin à jour elle-même.** Son code — avec celui de la sauvegarde, de la
  restauration et de la place de marché — vivait dans le dossier `install/`, que la mise à jour ne
  réécrivait jamais : un site gardait celui du jour de son installation, et ses corrections ne
  l'atteignaient pas. Il vit désormais dans le cœur (`neofrag/installer.php`), réécrit à chaque
  version, et le Monitoring en vérifie l'intégrité. Le reste d'`install/` suit aussi les versions ;
  seul le verrou `install/db.txt` reste propre au site. Sur un site existant, `install/` se met à
  jour à partir de la mise à jour suivant celle-ci.
- **Un site qui supprime son dossier `install/` après l'installation**, comme on le conseille
  souvent, garde sa mise à jour et sa place de marché ; leurs messages restent alors en français.

## [1.2.2] — 2026-10-01

### Corrigé

- **Les équipes, jeux, catégories, partenaires et palmarès ne disparaissent plus dans les autres
  langues.** L'administration n'enregistre un titre que dans la langue où l'on écrit, et ces objets
  n'existaient que dans celle-là : sur la démonstration, trois équipes en français, aucune en anglais.
  Le groupe d'une équipe, avec les droits qui en dépendent, disparaissait avec elle. Ils s'affichent
  désormais dans la langue demandée, sinon en français, sinon dans celle qui existe. Les listes qui
  rendaient une ligne par traduction (recrutements, matchs, fil d'activité de l'administration) n'en
  rendent plus qu'une. Les listes de contenus (actualités, articles, pages) restent dans la langue
  demandée.
- **Une mise à jour applique enfin les changements de la base de données.** Jusqu'ici, seule
  l'installation les appliquait : un site mis à jour gardait sa base ancienne sous un code neuf. Par le
  bouton, ils s'appliquent pendant la mise à jour, qui est annulée s'ils échouent. Par FTP, ils
  s'appliquent à la première page servie par le nouveau code.
- **Connexion par Discord** : un avatar animé (102 caractères) ne tenait pas dans sa colonne, et la
  connexion échouait. Un membre sans avatar recevait une image cassée.

### Modifié

- **L'envoi des e-mails passe à PHPMailer 7** (7.1.1). Sa seule rupture concerne les classes qui en
  héritent, et NeoFrag n'en a pas. Éprouvé par un envoi réel avec les réglages d'un site,
  en SMTP comme par `mail()`.

## [1.2.1] — 2026-10-01

### Sécurité

- **Deux failles corrigées dans la bibliothèque Markdown** (`league/commonmark` 2.10.3) : un déni de
  service par des tableaux construits pour ralentir le serveur (gravité élevée), et un contournement
  du filtre qui retire le HTML interdit (gravité moyenne). Signalées le 30 septembre 2026. La
  bibliothèque de nettoyage du HTML (`ezyang/htmlpurifier` 4.19.1) passe aussi à sa dernière version.

### Corrigé

- **Le Monitoring ne déclare plus « manquants » des fichiers présents** (2026-10-01). Depuis la
  publication de la 1.2.0, il comparait le site au manifeste de la version publiée, mais ignorait les
  sources Sass d'un seul côté : quatre fausses erreurs, et un état de santé « Le navire coule ! ».

### Modifié

- **Une mise à jour du cœur s'inscrit au journal d'audit** (2026-09-23) : qui l'a lancée, quand, de
  quelle version vers quelle version, et combien de fichiers ont été remplacés. Elle s'écrivait
  jusqu'ici dans le journal d'erreurs, où elle passait pour une anomalie.

## [1.2.0] — 2026-09-23

### Ajouté

- **La mise à jour du cœur se fait en un clic** (2026-09-23). À partir de cette version, **Administration
  → Monitoring** signale les nouvelles versions de NeoFrag Reborn et les installe : sauvegarde du site,
  vérification de l'empreinte du paquet, application, mise à jour de la base, et retour à la sauvegarde
  si quelque chose échoue. C'est la première version publiée par ce chemin ; il a été éprouvé de bout en
  bout sur un site neuf avant la publication.

- **Les annonces sur Discord** (2026-09-23). Un webhook qui pointe vers un salon Discord reçoit
  désormais un vrai message, avec titre, lien et couleur : nouvelle actualité, nouvel article, nouveau
  membre, nouveau commentaire, nouveau sujet de forum. Discord refusait jusqu'ici ce que le module
  envoyait, alors que sa description promettait Discord.

- **« Untel est en direct »** (2026-09-23). Quand une chaîne Twitch ou YouTube suivie par le widget
  « Statut live » passe en direct, les webhooks abonnés à l'événement « Chaîne en direct » l'annoncent,
  une seule fois par direct, avec la miniature du stream.

- **La démonstration parle aussi anglais dans son contenu** (2026-09-23) : ses actualités, articles,
  pages, catégories et galeries ont une version anglaise, qui montre la fonction multilingue.

- **Tout le produit parle les six langues** (2026-09-23). L'administration, les
  modules, les widgets, les thèmes, les réglages, les boutons, les messages et les scripts : près
  de 800 textes étaient écrits directement en français et le restaient sur un site anglais,
  allemand, espagnol, italien ou portugais. Ils passent tous par les traductions, dans les six
  langues. En particulier :

  - **L'assistant d'installation se traduit**, avec un sélecteur de langue ; il suit d'abord la
    langue du navigateur. L'installeur en ligne de commande prend `--lang=en`, ou la langue du
    terminal.
  - **La place de marché** montre le nom et la description de chaque addon dans la langue du site.
  - **Les pays** (profil, adversaires) s'affichent dans la langue du site.
  - **Les rôles et les modèles d'e-mails livrés** s'affichent traduits ; un nom changé par
    l'administrateur reste le sien.
  - **La modération** montre le statut, la raison et le type d'un signalement, et le type d'une
    sanction, en toutes lettres — elle affichait les codes (`pending`, `forum_message`, `ban_temp`).
  - **Un groupe créé dans une langue ne disparaît plus** quand le site s'affiche dans une autre —
    il sortait de la liste, et ses droits avec.
  - Le thème d'administration se déclarait écrit en anglais : sur un site anglais, ses textes
    français ne se traduisaient jamais. C'est corrigé, comme les scripts des modules, qui
    cherchaient leurs traductions au mauvais endroit.
  - La confirmation de suppression de compte demande de taper le mot dans la langue du site
    (« DELETE » en anglais), et non plus « SUPPRIMER » partout.

  Un contrôle, `check-textes-en-dur`, refuse désormais en CI tout texte d'interface écrit en dur.

- **Les 61 addons de la place de marché ont une vignette** (2026-09-22). Chacun — module, widget
  ou thème — montre une capture d'écran réelle de ce qu'il fait, en 960×600. Pour y arriver, six
  modules et deux widgets ont été **installés sur la démonstration**, avec du contenu d'exemple :
  un dictionnaire, des citations, des recettes, une carte de lieux et une grille de webradio.
  La démonstration porte aussi enfin l'identité de sa communauté — nom, type, date de création et
  présentation — qui était restée vide.

- **Le site peut fonctionner sans réseau, si on le demande** (2026-09-22). Le manifeste rendait
  déjà le site installable ; il garde désormais ses images, ses styles et ses scripts dans le
  navigateur des visiteurs. **Éteint par défaut**, à allumer dans **Administration → Paramètres →
  Préférences générales**.

  **Les pages, elles, ne sont jamais gardées.** Une page en mémoire survivrait à une mise en
  ligne : on servirait un site d'avant-hier à qui l'a déjà visité, sans que rien ne le signale.
  Une modification reste donc visible immédiatement.

  **Décocher désinstalle vraiment.** C'est le point délicat de cette technologie : un composant
  de ce type, une fois installé dans un navigateur, y reste même si on retire le fichier du
  serveur. L'interrupteur ne retire donc rien — il fait servir une version qui se désinstalle
  d'elle-même à la prochaine visite. C'est pour cette raison que la fonction est éteinte par
  défaut, et qu'une mise à jour du cœur ne l'allume jamais toute seule.

- **Le site est installable comme une application** (2026-09-21). `/manifest.webmanifest` est produit par
  le site lui-même — nom, description, couleur de thème, adresse de départ et favicon viennent des
  réglages — et non déposé en fichier, qui serait faux partout ailleurs. Le *service worker*, lui,
  reste à venir : installé dans un navigateur il y demeure même si le fichier disparaît du serveur,
  ce qui demande un interrupteur de désinscription avant de le livrer.

- **Les dossiers sensibles sont fermés sous Apache aussi** (2026-09-21). Les `.htaccess` du paquet
  protégeaient **moins** que la configuration Caddy fournie : sur toute installation Apache,
  `cache/`, `install/`, `tools/`, `tests/` et `docs/` étaient joignables — la documentation
  se lisait en texte. Chaque dossier porte sa garde, la racine refuse aussi `.neon` et `.md`, et
  `tools/check-htaccess.php` relit la liste à chaque passage, avec la raison de chaque ligne.

- **Un checker peut déclarer que ses refus sont ordinaires** (2026-09-21). Le module `pages`, routeur
  de repli, écrivait une ligne d'**erreur** dans le journal du site pour chaque adresse
  inconnue — chaque robot qui sonde en ajoutait une. `Module_Checker::refus_ordinaire()` le déclare ;
  le diagnostic reste rendu à l'écran en mode débogage, et `check-journal` refuse désormais toute
  ligne que le produit écrit lui-même dans le journal des erreurs.

- **Deux contrôles tiennent les conventions du dépôt** (2026-09-21). `tools/check-tools.php` vérifie
  que chaque outil respecte les siennes — en-tête, garde par le socle commun, aucune plomberie
  recopiée, verdict et codes de sortie, port réservé, catalogue à jour — et `tools/check-docs.php`
  celles de la documentation : chiffres d'inventaire justes, renvois et ancres vivants, aucun
  document orphelin, aucun outil nommé qui n'existe plus, aucune phrase recopiée d'un document
  vivant à l'autre, documents qui restent lisibles. Tous deux sont en intégration continue.

- **Huit nouveaux addons, tous facultatifs** (2026-09-20). Aucun n'est installé d'office : ils
  s'ajoutent depuis **Administration → Addons**, et se retirent de même.

  | Addon | Ce qu'il fait |
  |---|---|
  | **Effet saisonnier** (widget) | Neige, confettis ou feuilles sur tout l'écran, pendant une plage de dates. Les dates s'écrivent `MM-JJ` **sans année** — la saison revient toute seule — et peuvent enjamber le Nouvel An (`du 12-15 au 01-06`). Hors saison, la page ne reçoit **rien du tout** : ni image, ni script. `prefers-reduced-motion` est respecté, et l'animation se met en pause quand l'onglet passe à l'arrière-plan. |
  | **Lecteur de flux** (widget) | Les derniers articles d'un flux RSS ou Atom extérieur. **Une page n'attend jamais un site tiers** : elle lit un cache, et sert même un contenu périmé plutôt que de faire patienter ; le rafraîchissement a lieu dans la tâche planifiée. Un flux tombé est mis de côté dix minutes au lieu d'être retenté à chaque visite. |
  | **Citations** (module) | Un recueil classé, avec auteur et source. |
  | **Recettes** (module) | Ingrédients et étapes saisis une par ligne, durées et nombre de parts, balisage `schema.org/Recipe` que lisent les moteurs de recherche. |
  | **Dictionnaire** (module) | Un lexique rangé par lettre. « Éclaireur » se range sous E, « Æther » sous A, « 1v1 » sous #. La lettre est calculée, jamais saisie. |
  | **Carte des lieux** (module) | Des lieux sur une carte OpenStreetMap, avec adresse, description et lien. La bibliothèque est **hébergée par le site** : aucun appel à un service tiers pour l'afficher. **La page reste utile sans JavaScript** — la liste, les adresses et les liens y sont. |
  | **Webradio** (module) | Un lecteur de flux, ce qui passe à l'antenne, et la grille de la semaine. Une émission peut enjamber minuit : « samedi 22:00 → 02:00 » reste l'émission du samedi. |
  | **Bac à sable** (module) | Réservé aux membres : on y essaie la mise en forme et on voit le rendu **exact** du site. Surtout, il affiche **ce que le site a retiré** — c'est la seule page qui réponde à « pourquoi mon tableau a-t-il disparu ». |

  Les trois modules de contenu (citations, recettes, dictionnaire) partagent le moule de la FAQ :
  catégories, recherche, filtre, tri et brouillons.

- **Choix du contenu à l'installation.** L'installateur web propose désormais un **profil de site**
  entre les prérequis et la base de données : *Complet*, *Gaming / eSport*, *Communauté* ou *Cœur seul*.
  Les modules restent décochables un par un, et ceux qu'un module réclame sont ajoutés automatiquement
  (le palmarès a besoin des équipes) avec un message. Sans JavaScript, le formulaire fonctionne quand
  même — le serveur ne retient que ce qui appartient au profil choisi.

  **Aucune liste d'addons n'est écrite à la main** : les profils se composent à partir des déclarations
  `presets` de chaque addon. C'est la différence avec la tentative de juin 2026, abandonnée parce qu'une
  installation allégée finissait en 500 — des modules du cœur interrogeaient des tables optionnelles
  sans garde. Ces gardes existent désormais et sont vérifiées à chaque exécution de la CI.

- **Déclarations de découplage sur les 99 addons livrés** : chacun déclare dans son `__info()` s'il
  appartient au cœur, dans quels profils il entre, et de quels modules il a besoin. L'appartenance au
  cœur était jusqu'ici un effet de bord de la présence dans le paquet — c'est ainsi qu'`emojis`, `files`
  et `webhooks` s'y étaient retrouvés sans qu'aucune décision ne soit prise.

- **Cinq garde-fous en intégration continue**, dont trois neufs :

  | Contrôle | Ce qu'il refuse |
  |---|---|
  | `check-addon-declarations.php` | un addon muet, une dépendance inexistante, **un addon du cœur qui dépend d'un optionnel**, un addon non distribuable publié au catalogue, une déclaration en désaccord avec `seed.sql` |
  | `check-addon-coupling.php` | un couplage **fatal** (table, classe) ni déclaré ni annoté ; un cycle dur. Lu dans le code au **tokenizer PHP**, pas à l'expression régulière — donc aucun faux positif sur les commentaires |
  | `check-install-profiles.php` | un profil qui ne démarre pas. Il **installe pour de vrai** sur une base jetable, sert le site et échoue au moindre 5xx — y compris sur les routes des modules absents, qui doivent rendre un 404 propre |

- **Mise à jour du cœur en un clic**, depuis **Administration → Monitoring**. Sauvegarde, téléchargement
  du paquet, vérification d'empreinte, superposition des fichiers, migrations en attente, recompilation
  des styles. `tools/build-release.php` produit les trois fichiers à publier ensemble
  (`neofrag-reborn-update-<v>.zip`, `version.json`, `checksum.json`) ; la procédure est dans
  `docs/guide/marketplace.md`.

- **Trouver un membre depuis la barre de recherche.** La recherche du site ne trouvait que du
  contenu — forum, actualités, pages. Chercher quelqu'un obligeait à ouvrir l'annuaire et à le
  feuilleter, alors que c'est l'une des choses qu'on cherche le plus souvent sur un site de
  communauté. Les membres apparaissent désormais dans la recherche globale **et** dans les
  suggestions instantanées, par pseudo, prénom ou nom.

  Rien de neuf n'est exposé au passage : ces trois champs sont déjà affichés sur la fiche publique
  de chaque membre, et la recherche applique exactement le même filtre que l'annuaire — un compte
  supprimé n'y apparaît pas.

- **Gérer une série d'événements d'un seul geste.** Créer un événement récurrent produisait déjà
  toutes ses occurrences en un clic — les reprendre demandait ensuite de les ouvrir une par une.

  Désormais, une occurrence de série porte un bouton **supprimer toute la série**, dont la
  confirmation annonce combien d'occurrences partiront, et le formulaire d'édition propose
  **« appliquer à toutes les occurrences »**. Le titre, le type, les descriptions, le lieu, l'image
  et la publication sont recopiés sur chacune ; **les dates ne le sont jamais** — ce sont elles qui
  distinguent une séance de la suivante.

- **Retour arrière d'une mise à jour, et restauration d'une sauvegarde.** Le CMS prenait déjà une
  sauvegarde complète — fichiers et base — juste avant chaque mise à jour du cœur. Il ne savait pas
  s'en reservir : le filet était tendu, personne ne savait tomber dedans.

  Désormais, si la pose des fichiers ou une migration échoue, **le site est automatiquement remis
  dans l'état où il était** ; le message dit à la fois ce qui a échoué et si le retour arrière a
  réussi. Chaque sauvegarde de la liste porte aussi un bouton **Restaurer**, pour les cas où le
  dégât ne vient pas d'une mise à jour — un module tiers, une manipulation regrettée. La
  confirmation annonce ce qui sera perdu.

  Ce que la restauration **ne** touche pas, volontairement : la configuration du site (rendre des
  identifiants de base périmés couperait le site de sa propre base), les journaux (c'est la trace de
  l'incident qu'on répare) et le cache (vidé pour être reconstruit, plutôt que remis dans l'état
  d'une autre version).

  Au passage, la sauvegarde embarque maintenant `vendor/`, que le paquet de mise à jour livre et
  qu'elle ne prenait pas : sans lui, un retour arrière aurait reposé l'ancien code sur les nouvelles
  dépendances.

- **Réponses aux commentaires** (fonctionnalité qui était désactivée `//TODO`) : le bouton « Répondre »
  est réactivé (classe `comment-reply` attendue par `comments.js`), le formulaire porte un champ caché
  `comment_id` posé au clic, et le back-end rattache la réponse en `parent_id` — **validé en base** (le
  parent doit exister, être de premier niveau, du même contenu, non supprimé), profondeur limitée à 1.
  L'affichage threadé (`comments-child`) existait déjà. Vérifié : rendu threadé correct en base.
- **Régions nommées dans les thèmes** (idée reprise de HiddenCMS) : les vues rendent une zone par **nom
  sémantique** — `$this->output->region('content')` — plutôt que par index — `zone(2)`. Le thème déclare
  une correspondance `regions` (nom → titre de zone) dans son `__info()` ; le cœur résout nom → titre →
  index → `zone()`. **Purement additif** : `zone()` reste utilisable, et un thème sans map se comporte
  normalement. Les 5 thèmes front (nebula, forge, granite, blockcraft, extend) migrés vers
  `region()`. Lisibilité accrue pour les créateurs de thèmes ; fondation pour les « outlines » à venir.
- **Installeur en ligne de commande** (`install/cli.php`) : alternative scriptable à l'assistant web,
  pratique pour un déploiement VPS reproductible. Flags `--db-*`, `--admin-*`, `--site-name`, `--site-url`,
  `--create-db`, `--demo`, `--force`, `--yes`, `--dry-run`, `--no-lock` + **mode interactif** (saisie
  masquée du mot de passe) et `--admin-pass-env` (mot de passe via variable d'environnement, invisible dans
  les process). Réutilise exactement la lib `Installer` et la séquence « tout bundlé » → résultat identique
  à l'installeur web. Idée reprise de HiddenCMS, portée sur le socle Reborn. Validé end-to-end
  (install complète : 132 tables, tous les addons, admin, wiki).

- **Un lanceur pour toute la batterie** : `php tools/check-all.php`. Les 26 contrôles n'avaient
  aucun point d'entrée commun — la CI les appelle un par un, et en local chacun refaisait une
  boucle à la main, jamais la même. Le lanceur découvre les contrôles présents (un contrôle neuf
  entre sans qu'on y touche), joue `composer audit` en tête, borne chaque contrôle en durée, et
  énonce à la fin **ce qu'il n'a pas joué** : les épreuves en navigateur (`--navigateur`) et les trois
  contrôles à cible explicite, dont `check-restauration` qui abîme volontairement le site.

- **Chaque addon montre à quoi il ressemble** (2026-09-22). La page « Thèmes & addons » et la
  place de marché affichent désormais une VIGNETTE pour 85 des 117 addons : une capture d'écran
  réelle du module, du widget ou de la langue tel qu'il tourne, prise sur la démonstration, où il
  y a des données à montrer. Ce qui n'a rien à montrer garde son icône : les connecteurs
  Discord, GitHub et Google, dont l'icône EST le logo, et les widgets qui n'affichent rien sans
  réglage.

- **Dix addons ont enfin une description** (2026-09-22) : les six langues — chacune dans sa
  propre langue — et les quatre connecteurs de connexion externe.

### Modifié

- **Toute l'interface parle Bootstrap 5** (2026-09-23). Il restait environ 470 emplois de classes
  de Bootstrap 3 et 4, que l'on faisait tenir par une feuille de compatibilité : champs de
  formulaire, pastilles de couleur, boutons de fermeture, boutons pleine largeur, blocs « avatar et
  texte », groupes de saisie, cases à cocher, grilles de formulaire. Tout le balisage est migré vers
  ses équivalents Bootstrap 5 ; ce qui est propre au produit porte désormais un nom du produit.
  Un contrôle refuse qu'un ancien nom revienne, même redéfini. Ce qui se voit :
  - les **listes déroulantes** ont retrouvé leur flèche — une centaine, dans l'administration et
    sur le site, ressemblaient à des champs texte ;
  - les **pastilles de couleur** (statuts, groupes, rôles, sanctions) sont lisibles dans tous les
    thèmes, en clair comme en sombre : leur texte échouait au contraste ;
  - les groupes **« Modérateur » et « Modérateur senior »** s'affichent : leur nom était écrit en
    blanc sur fond blanc ;
  - les **boutons de fermeture** des fenêtres ont une zone de clic suffisante sur téléphone.

- **Le JavaScript est relu par un linter** (2026-09-22). Soixante-dix-huit fichiers écrits sur des
  années, dont rien ne vérifiait autre chose que la syntaxe. ESLint voit ce que la syntaxe ne dit
  pas : une variable globale créée par oubli d'un `var`, un `eval` déguisé, du code après un
  `return`, un `innerHTML` nourri d'une valeur calculée. Aucun paquet npm n'est déployé : c'est de
  l'outillage de développement, au même titre que l'analyse statique PHP.

  Les règles sont choisies sur un seul critère — avoir déjà mordu ici. C'est la famille du
  « `$` is not defined » qui avait tué le tri des tables de toute l'administration pendant trois
  mois. Le désordre existant (des `innerHTML` à assainir) est **gelé en l'état** plutôt que réécrit
  d'un coup : son nombre ne peut plus monter.

- **La baseline PHPStan se régénère par une commande, et la classe `Config` du cœur est annotée**
  (2026-09-21). Trois erreurs neuves ont fait rouvrir `phpstan-baseline.neon` : des entrées ajoutées à
  la main portaient une autre écriture que celle de PHPStan, et près de trois cents blocs ne
  correspondaient plus à rien — toute une famille « Function NeoFrag not found », morte depuis que le
  fichier d'amorçage déclare la fonction. La configuration vit dans `phpstan-base.neon`,
  `phpstan.neon` n'y ajoute que la baseline, et `composer stan:baseline` la régénère entière. `Config`
  annote désormais la langue courante, la liste des langues et les réglages du cœur que lisent les
  modules : ces accès sont analysés au lieu d'être gelés.

- **Les outils de `tools/` refondus sur une bibliothèque commune** (2026-09-21). Soixante outils
  écrits chacun à sa manière recopiaient la même plomberie : quatorze lançaient leur serveur PHP,
  huit ouvraient leur session d'administrateur, six lançaient Chrome, vingt-cinq lisaient
  `config/db.php`. Une leçon apprise dans l'un ne se propageait pas aux autres. `tools/lib/` porte
  désormais ce socle — connexion, session, serveur, navigateur, parcours du dépôt, SQL, options,
  verdicts — et chaque outil tient dans sa logique propre. Cinquante-deux outils au lieu de
  soixante, sans perte de fonction : `check-js-syntax` et `check-js-jquery` forment
  `check-js-sources` ; `check-docs-counts` et `check-docs-liens` forment `check-docs` ;
  `check-lang-args` rejoint `check-langs` ;
  `seed-wiki-docs` et `dump-wiki` forment `wiki-docs` ; `smoke-test` devient
  `check-smoke`, comme tout contrôle ; `addons-manifest` et `table-map`, qui sont des données,
  vivent dans la bibliothèque. Chaque outil a désormais son port réservé (deux en partageaient un),
  trois codes de sortie qui distinguent « rien à reprocher », « refusé » et « n'a pas pu juger », et
  un en-tête dont le catalogue de `tools/README.md` est engendré. `bs5-codemod`, outil de la
  migration Bootstrap 4 → 5 de juin, est retiré : `check-classes-bs4` couvre tout ce qu'il
  vérifiait, points de rupture des utilitaires directionnels compris depuis ce jour.

- **`declare(strict_types=1)` sur tout le périmètre utile** (2026-09-21) : **1 365 fichiers** contre
  78 la veille. Le chantier avançait par lots de quatre à dix fichiers depuis dix mois ; il est
  terminé. Les 209 fichiers restants sont les gabarits `views/**.tpl.php`, où la déclaration serait
  syntaxiquement valide et ne protégerait rien.

  La conversion a été conduite par ordre de **risque décroissant** — le nombre d'appels de fonction
  native par fichier — avec la suite de tests jouée entre chaque lot.

  Conséquence de fond, propre à ce produit : PHP en mode strict **refuse `__toString()` pour les
  fonctions internes**. Or `lang()` rend un objet de traduction *différée*, résolu au rendu quand la
  langue est enfin connue, et les dates sont également des objets. La barre d'administration, la
  page de chaque actualité et plusieurs écrans tombaient donc en erreur fatale — module neutralisé,
  page amputée, sans un mot. La conversion est désormais posée au **point d'échappement**
  (`htmlspecialchars`), c'est-à-dire là où la valeur doit devenir une chaîne par définition :
  410 emplacements dans 84 fichiers.

- **Le journal dit désormais OÙ, pas seulement quoi** (2026-09-21). Quand un module lève une
  exception, son rendu est abandonné et la page s'affiche amputée — en silence. La ligne de journal
  donnait le message sans jamais nommer un fichier. Elle porte maintenant la première image de pile
  qui appartient au produit. Retrouver l'origine des erreurs ci-dessus est passé d'une enquête à une
  lecture.

- **Le compilateur SCSS passe en 2.1** (2026-09-20). Sur PHP 8.5, chaque compilation des styles écrivait
  **dix dépréciations** dans le journal ; il n'y en a plus aucune. La 2.x minifie un peu plus (`.25rem`
  au lieu de `0.25rem`, `#ccc` au lieu de `#CCCCCC`), ce qui change quatre feuilles — celles des modules
  addons, palmarès, commentaires et de la page de maintenance. L'équivalence n'a pas été déduite du
  texte : les deux jeux de CSS ont été donnés à lire au moteur de Chrome, qui y voit exactement les
  mêmes règles, propriétés et valeurs calculées.

- **La documentation a été relue en entier contre le code** (2026-09-17). Les guides d'installation, de
  concepts et de développement (créer un module, un widget, un thème, le framework) dataient de juin et
  décrivaient un CMS d'avant : une installation sans choix de profil, un thème qui chargeait jQuery, des
  grilles Bootstrap 4. Ils disent désormais ce que fait la 1.1.0 — profils d'installation, déclarations
  d'addons, régions nommées, front sans jQuery sous politique de sécurité stricte, réglages de widget avec
  valeurs de repli, vocabulaire de couleurs partagé — et sont republiés dans le wiki du site. Le README,
  le guide de contribution, la politique de sécurité et la référence technique sont alignés de même.

- **Ce qu'un addon autorise se lit dans sa déclaration**, plus dans des listes de noms en dur. Trois
  d'entre elles décidaient du sort des addons, et les trois étaient fausses : celle des widgets
  protégeait sept noms qui ne sont pas des widgets ; celle des thèmes protégeait un thème `default`
  inexistant **en laissant `nebula`, le seul thème public livré, supprimable** dès qu'il était inactif ;
  celle des modules dupliquait, en désaccord, ce que `__info()` disait déjà. Vérifié addon par addon
  contre l'ancienne logique : aucun changement de désactivabilité, aucun changement d'état actif, et
  **39 addons deviennent non supprimables** — ce qui est l'objet du correctif.

- **La Corbeille ne connaît plus les autres modules.** Elle tenait en dur la liste des tables, clés
  primaires et méthodes de restauration de `news`, `articles`, `gallery`, `comments` et `forum` : elle
  ne pouvait donc pas appartenir au cœur sans tirer cinq modules optionnels avec elle. Chaque module
  déclare maintenant ses types restaurables ; le cœur collecte. Même inversion pour les descripteurs de
  contenu (réactions, abonnements, révisions).

- **Une seule source de vérité pour les tiers.** `tools/addons-manifest.php`, table écrite à la main et
  figée en juin, dérive désormais des déclarations. Les deux avaient déjà divergé : `emojis` y figurait
  comme cœur alors qu'il se déclare à la carte — il était donc décochable à l'installation mais absent
  du catalogue, donc **impossible à réinstaller**. Catalogue : 52 → **53 addons**.

- **Installateur repris sur la forme** : mise en page à deux colonnes, jetons de style repris de `nebula`
  (marine et sarcelle, accent `#2dd4bf`), dégradé sorti de derrière le texte, logo posé dans une pastille.

### Corrigé

- **La sauvegarde du Monitoring fonctionne de nouveau** (2026-09-23). Depuis le 21 septembre, elle
  s'arrêtait dès la première ligne de la base : le bouton « Sauvegarde », et la mise à jour du cœur qui
  commence par une sauvegarde, échouaient sur tout site. Trouvé en éprouvant la mise à jour avant de la
  publier.

- **Changer de langue sur une actualité traduite ne mène plus à une page introuvable** (2026-09-23).
  Le sélecteur de langue garde l'adresse, donc le titre de la langue d'avant : la version traduite la
  refusait. Elle renvoie désormais vers sa propre adresse.

- **Une page traduite s'affiche dans la bonne langue** (2026-09-23). Les pages publiques étaient servies
  dans la première version que la base rendait, quelle que soit la langue du visiteur.

- **Le thème choisi par un visiteur ne déborde plus sur un autre site du même domaine** (2026-09-23).
  Le choix était retenu pour tout le domaine : un visiteur qui passait un site en Forge voyait aussi
  en Forge un autre site installé sous le même domaine. Le choix est désormais retenu **pour chaque
  site séparément**, et un nouveau réglage, **Préférences générales → Choix du thème**, permet de le
  fermer : le menu disparaît et le thème par défaut s'impose à tous.

- **La démonstration se remet de nouveau à zéro** (2026-09-23). Une erreur dans son jeu de données
  faisait échouer la remise à zéro des quinze minutes depuis la veille : ce que les visiteurs y
  modifiaient restait. Un contrôle refuse désormais ce défaut avant toute mise en ligne, et un échec
  de remise à zéro s'écrit au journal au lieu de passer inaperçu.

- **Le compte de secours de la démonstration n'apparaît plus nulle part** (2026-09-23) : ni dans la
  liste des membres, ni dans la recherche, ni parmi les membres en ligne, ni dans l'administration ;
  sa page répond « introuvable ».

- **Le site sur un téléphone** (2026-09-23), d'après des captures d'écran prises sur téléphone :

  - **Le menu est toujours atteignable.** Sur le thème Nebula, il disparaissait sous 860 pixels de
    large sans aucun bouton pour le rouvrir : un bouton « burger » l'ouvre désormais. Sur les autres
    thèmes, un menu trop long pour tenir sur une ligne se replie derrière un bouton « Menu »,
    au lieu de passer à la ligne jusqu'à remplir l'écran ; un menu court reste
    déplié.
  - **Les blocs empilés ne se touchent plus** : l'espace membre collait au bloc du dessus.
  - **Le texte ne colle plus à l'avatar** des membres (commentaires, discussions, auteur d'une
    actualité, candidatures, profil).
  - **Les points du diaporama** sont de vrais boutons, centrés sous l'image, et restent **clairs**
    sur les thèmes sombres, comme les flèches et la légende : Bootstrap les passait en noir.
  - **La recherche du thème Extend** garde son bouton à côté du champ.
  - **Les photos d'un album** remplissent leur carte.
  - L'en-tête des commentaires montre le titre avant le compteur, qui le précédait.
  - Les dates abrégées s'écrivent dans la langue du site (« 30 août 2026 », « Sonntag »), et non
    plus en anglais ; la ligne d'auteur d'une actualité a retrouvé une taille de texte normale.

- **La page d'une photo de la galerie existe** (2026-09-23). Le widget « Image aléatoire », le
  diaporama de la galerie et le lien d'un commentaire posé sur une photo menaient tous à une page
  introuvable : elle n'avait jamais été déclarée. Elle montre la photo, sa description, le retour à
  l'album et les commentaires. Une photo suit aussi les règles de son album : plus d'accès par son
  adresse quand l'album est à la corbeille ou pas encore publié.

- **Derniers restes de Bootstrap 3 et 4** (2026-09-23), que leur nom de classe ne trahissait pas :
  la structure du diaporama, les croix de fermeture qui affichaient un « × » en plus de l'icône,
  deux accordéons (FAQ, liens de la navigation), des champs qui s'enveloppaient dans un groupe vide,
  la flèche des bulles d'aide restée blanche sous une bulle sombre, et deux règles du widget des
  partenaires. `check-classes-bs4` lit désormais aussi la STRUCTURE des composants et les styles
  écrits dans les vues.

- **Les pages d'erreur qui n'en étaient pas** (2026-09-23). Dans la modération, la gestion des
  rôles, des modèles d'e-mails, des membres et du diaporama, une adresse vers un élément disparu
  devait répondre « page introuvable » : elle appelait une fonction qui n'existe pas, écrivait un
  avertissement au journal, et la page de modération s'affichait vide. Trouvé en apprenant à
  l'analyse statique les fonctions « magiques » du framework — ce qui a allégé de 455 occurrences sa
  liste d'exceptions.

- **Le bouton des e-mails envoyés aux membres est lisible** (2026-09-23) : validation du compte, mot
  de passe perdu et les autres modèles avaient un bouton blanc sur turquoise clair (2,3:1). Les
  modèles livrés comme ceux déjà installés — sauf ceux qu'un administrateur a personnalisés.

- **Dernières finitions de la passe complète** (2026-09-23) : liens du wiki, des lieux et de la
  webradio dans la teinte lisible du thème ; boutons « succès » des thèmes sombres ; statut d'une
  candidature, dont la boîte n'avait aucun fond (classes d'un ancien thème d'administration) ;
  index alphabétique du glossaire, qui n'avait aucune mise en forme ; réseaux sociaux des cartes de
  membres ; titres coupés dans l'administration ; un visiteur qui suit le lien « participer » d'un
  événement est invité à se connecter.

- **Les textes sont lisibles partout, en clair comme en sombre** (2026-09-23). Liens et boutons de
  l'administration, alertes, dates et en-têtes de tableau des thèmes, catégories d'actualités,
  page du marketplace sur les thèmes clairs, bouton « Tout accepter » du bandeau cookies,
  pastilles de réputation et de type d'événement : tous atteignent désormais le contraste minimal
  recommandé (4,5:1). En mode sombre, l'administration a ses propres couleurs d'état, le panneau
  « Santé du site » et la zone d'envoi d'images de la galerie suivent le thème.

- **L'administration sur un téléphone** (2026-09-23). La barre du haut passe sur deux lignes — le
  fil d'Ariane en entier, puis les boutons — au lieu de couper le titre de la page ; les petites
  cibles (icône d'accueil, compteurs) sont assez grandes pour le doigt ; l'en-tête des catégories du
  forum ne passe plus sous ses boutons.

- **Le forum sur un téléphone** (2026-09-23) : dans un sujet, l'auteur passe au-dessus du message au
  lieu de lui laisser 200 px, et les boutons d'un message ne recouvrent plus sa date.

- **Les pluriels traduits** (2026-09-23). 47 textes avaient perdu leur forme plurielle en
  traduction : un site en anglais affichait « 3 topic », « 5 image », « 2 year ». Le compteur des
  catégories du forum, écrit en français dans toutes les langues, est traduit.

- **La documentation du site est à jour** (2026-09-23). Les dix pages du wiki livrées avec le produit
  et celles de la démonstration avaient une semaine de retard sur les guides ; « Créer un widget »
  enseignait encore une classe de Bootstrap 4. Un contrôle les compare désormais aux guides.

- **Le formulaire de réponse du forum tient sur un téléphone** (2026-09-23) : la liste des types de
  pièces jointes autorisés, écrite sans espace, formait un seul mot trop large pour l'écran.

- **Tout le site passé au crible, dans chaque thème, chaque mode et à chaque largeur** (2026-09-23).
  Un contrôle automatique rend désormais chaque page publique et d'administration dans les six
  thèmes, en clair et en sombre, connecté et en visiteur, de 360 à 2560 px, avec et sans contenu.
  Ce premier passage a corrigé :
  - des **icônes cassées** dans les listes de catégories de la galerie, des jeux, des équipes et
    des actualités, quand une catégorie n'avait pas d'icône ;
  - le **pays des profils** de la démonstration, écrit en toutes lettres : le profil n'affichait
    aucun pays et cherchait un drapeau introuvable ;
  - les **sujets des modèles d'e-mails**, qui affichaient `{{site_name}}` en clair dans la liste ;
  - des **tableaux trop larges pour un téléphone** (bug tracker, téléchargements, historique du
    wiki, modération, sessions, participants d'un événement, palmarès) : ils défilent désormais
    dans leur cadre au lieu d'élargir toute la page ;
  - les **listes du forum** sur téléphone : quatre colonnes se partageaient 360 px et le dernier
    message était coupé ; chaque entrée s'empile désormais — icône et titre, puis statistiques et
    dernier message ;
  - la **barre du thème Nebula** sur téléphone, qui débordait de l'écran ;
  - trois fichiers réclamés par des pages sans exister, et deux lignes écrites au journal à chaque
    vérification des mises à jour tant qu'aucune version n'est publiée.

- **Les boutons « Modifier » et « Supprimer » ne sont plus en escalier** (2026-09-22). Dans les
  listes de l'administration à deux colonnes — catégories des actualités, de la galerie, groupes
  de membres… —, le second bouton passait à la ligne, décalé sous le premier. Ils restent
  désormais côte à côte.

- **La jauge « Stockage » ne touche plus son arc** (2026-09-22). La valeur, en haut du
  demi-cercle, empiétait sur le trait ; elle est descendue dans le creux, le pourcentage juste
  en dessous. Et le titre « Informations serveur » de la même page n'est plus coupé net quand la
  colonne est étroite : il passe sur deux lignes.

- **Un widget posé sans réglages s'affiche avec ses valeurs par défaut** (2026-09-22). Quand un
  thème pose un widget à l'installation, ou qu'une ancienne disposition le restaure, il arrive
  sans réglages : il écrivait alors des alertes dans le journal à chaque page affichée, et le
  menu de navigation disparaissait purement et simplement. Il reçoit désormais les valeurs que
  son formulaire aurait enregistrées par défaut. Les widgets déjà réglés ne changent pas.

- **La page des événements s'affiche de nouveau** (2026-09-22). Elle rendait une page d'erreur
  « introuvable » dès qu'elle avait des événements à répartir sur plusieurs pages — sous un
  titre parfaitement normal, ce qui la rendait difficile à remarquer. Le réglage « nombre
  d'événements par page » était enregistré comme du texte, et le code, rendu plus strict la
  veille, le refusait. Toutes les listes paginées acceptent désormais ce réglage sous les deux
  formes.

- **Les votes des sondages sont enregistrés** (2026-09-22). Le votant lisait « Merci pour ton
  vote ! », et le vote n'était jamais compté : aucune option n'était reconnue comme appartenant
  au sondage. Un vote qui ne vise aucune option du sondage est désormais refusé en le disant,
  au lieu d'être salué.

- **Le widget « Derniers sujets du forum » affiche enfin des sujets** (2026-09-22). Il ne
  retenait aucune catégorie de forum, et restait donc vide sur tous les sites, quel que soit
  le nombre de messages.

- **Trois autres fonctions tombaient dans le même piège que les sondages** (2026-09-22), trouvées
  par le contrôle écrit pour ce défaut :
  - **ouvrir une candidature qui porte des champs personnalisés** plantait la page, côté
    candidat comme dans l'administration ;
  - **une notification qui renvoie vers un événement du calendrier** plantait au moment de
    fabriquer son lien ;
  - **la profondeur maximale des réponses du forum** n'était jamais appliquée : le calcul
    s'arrêtait au premier niveau.

- **Revenir à la page 1 d'une liste ne mène plus à une page introuvable** (2026-09-22). Dans les
  listes de l'administration dont on peut régler le nombre d'éléments par page, le bouton « 1 »
  fabriquait une adresse que le site ne reconnaissait pas.

- **L'édition d'un champ de profil personnalisé s'ouvre** (2026-09-22). L'écran plantait à
  chaque ouverture : il ne donnait pas de libellé à son bouton d'enregistrement.

- **Deux boutons « Retour » menaient à une page introuvable** (2026-09-22) : depuis la fiche
  d'une image, vers son album ; depuis un mode de jeu, vers son jeu.

- **« Postuler » n'apparaît plus là où la candidature serait refusée** (2026-09-22) : sur une
  offre fermée ou complète, et pour qui n'a pas le droit de postuler. De même, un administrateur
  qui lit une conversation réservée à l'équipe peut désormais y inviter et en signaler un
  message, au lieu d'être refusé après avoir vu le bouton.

- **Les sous-menus du widget de navigation s'ouvrent** (2026-09-22). Un lien qui regroupe
  d'autres liens ne dépliait rien au clic : il portait encore l'attribut de l'ancienne
  version de Bootstrap, que son propre script ne cherchait plus.

- **Les bulles d'aide des formulaires s'affichent au survol** (2026-09-22). L'icône (i) posée
  à côté des champs de l'administration, et les info-bulles des étiquettes, ne montraient
  rien : même défaut d'attribut, sur les deux bibliothèques qui les fabriquent.

- **Les libellés accentués du menu sont traduits** (2026-09-22). Un lien de menu enregistré
  depuis l'administration — « Actualités », « Équipes » — est stocké sous forme codée, et sa
  traduction n'était plus trouvée : il restait en français dans les cinq autres langues, avec
  une alerte au journal à chaque page affichée.

- **Les archives de la place de marché sont reproductibles** (2026-09-22). Elles emportaient les
  cartes de source `.map`, que le compilateur de styles régénère sur chaque installation et que
  le dépôt ignore : deux archives du même addon, bâties sur deux machines, différaient sans que
  le code ait bougé. Une archive contient désormais ce que le dépôt contient, rien d'autre.

- **Le widget « Palmarès » s'installe enfin par dépôt d'archive** (2026-09-22). Il était le seul
  des 61 addons distribuables à ne pas déclarer sa dépendance au cœur ; l'installeur, qui l'exige
  pour reconnaître un addon, passait son chemin SANS RIEN DIRE — ni message, ni trace.

- **Le lecteur de flux RSS affiche ses dates au lieu de leur code** (2026-09-22). Sous chaque
  titre s'affichait `<time datetime="2026-09-11…">Le 11/09/2026…</time>` en toutes lettres : la
  vue échappait un élément que la bibliothèque de dates avait déjà composé. Les résumés, eux,
  laissaient passer le balisage des flux qui l'échappent.

- **Le lecteur de flux n'a plus besoin d'écrire son cache pour afficher** (2026-09-22). Il
  renvoyait ce que le cache voulait bien lui relire : quand l'écriture échouait, il affichait le
  vide alors qu'il avait les articles en main, et sans un mot nulle part.

- **La page de maintenance n'est plus blanche** (2026-09-22). Les deux champs « titre » et
  « texte » sont livrés VIDES, et la page ne montrait donc RIEN d'autre que le nom du site —
  alors que l'aperçu de l'administration promettait un titre et un texte. Un titre et un
  message par défaut, traduits dans les six langues, comblent le vide ; dès que les champs
  sont remplis, ce sont eux qui s'affichent.

- **Les boutons à contour ont retrouvé leur cadre** (2026-09-22). Les thèmes ne
  redéfinissaient qu'une seule variante de bouton « contour », et leur propre règle effaçait la
  bordure des six autres : **90 boutons** du produit ressemblaient à de simples liens. Le plus
  visible était « Ouvrir le site », dans le bandeau de maintenance, qui n'avait de cadre qu'au
  survol.

- **Le code technique n'est plus rose dans l'administration** (2026-09-22). Le thème ne fixait
  que la police des extraits `<code>`, qui gardaient donc le rose de Bootstrap — une couleur
  étrangère à toutes les palettes du produit. Très visible sur la liste des rôles.

- **La carte « Stockage » de la supervision s'affiche correctement** (2026-09-22). La valeur et
  le pourcentage se plaçaient tout en haut de la carte au lieu du creux de la jauge, et la
  jauge elle-même se dessinait en cercle entier au lieu du demi-cercle prévu, avec un nombre
  brut en son centre. La ligne « Envoi d'email » affichait `[object Object]` à la place du nom
  de la méthode employée.

- **Le bouton « Fermer » des fenêtres ferme enfin la fenêtre** (2026-09-22). Dans TOUTES les
  fenêtres du produit, le bouton « Fermer » (ou « Annuler ») du bas ne faisait rien : il fallait
  la croix en haut à droite. La cause : Bootstrap 5 a renommé l'attribut qui commande la
  fermeture, et le cœur posait encore l'ancien nom ; un navigateur ignore un attribut inconnu
  **sans rien dire**. Le bouton est aussi devenu un vrai bouton — il était rendu en `<span>`,
  donc inaccessible au clavier. Deux gardes l'empêchent de revenir : le contrôle du code refuse
  l'ancien nom, et un parcours de navigateur CLIQUE sur le bouton à chaque passage.

- **Le thème Nebula n'affiche plus son menu deux fois** (2026-09-22). Toute installation neuve
  montrait le nom du site deux fois et le menu deux fois : le thème dessine sa propre barre, et
  l'installation posait EN PLUS un titre et un menu juste en dessous. Désormais le menu vit dans
  la barre du haut, où il n'apparaît qu'une fois — et il reste **configurable** depuis
  l'administration, ce qui n'était pas le cas : les liens de la barre étaient écrits en dur, si
  bien que « Contact » n'y figurait pas et qu'un module désactivé gardait son lien. Les sites
  déjà installés sont corrigés par une migration, qui ne touche à l'en-tête que s'il est resté
  tel que livré.

- **Changer de langue ne renvoie plus sur une page de code** (2026-09-22). Le sélecteur de langue
  du pied de page — celui de **cinq thèmes**, dont celui par défaut — envoyait le visiteur sur une
  page affichant `{"redirect":"/en/…"}` en texte brut, au lieu de l'emmener sur la page traduite.
  Il fallait revenir en arrière pour s'en sortir.

  La cause : ce sélecteur est un vrai formulaire, que rien n'interceptait, et il visait une
  adresse technique qui répond toujours en JSON. Le serveur distingue désormais l'appel du menu
  « Choisir ma langue », qui attend du JSON, d'un envoi de formulaire ordinaire, qui attend d'être
  emmené sur la page. Trouvé en suivant le geste dans un vrai navigateur, ce qu'aucun contrôle ne
  faisait jusque-là.

- **Un contenu rédigé dans une seule langue ne renvoie plus les autres sur une page d'erreur**
  (2026-09-21). Une actualité écrite en français proposait quand même les cinq autres langues dans son
  sélecteur, et les cinq rendaient 404 : mesuré sur la démonstration, **60 adresses mortes sur 72**.
  Le geste le plus naturel d'un visiteur étranger tombait sur une erreur. La version qui existe est
  désormais servie, avec un bandeau qui le dit dans la langue du visiteur et un lien vers l'original.
  Cela vaut pour les actualités, les articles, les albums, les équipes et leurs catégories.

  Deux précautions indissociables, sans quoi le remède coûterait plus que le mal : la page n'annonce
  plus aux moteurs de recherche que les langues qui **existent vraiment**, et elle désigne l'original
  comme adresse de référence — sinon six adresses seraient indexées pour un seul texte.
  **En administration, aucun repli** : une version vide doit se voir vide, c'est ce qu'on vient
  remplir. `tools/check-langues-contenu.php` tient les trois propriétés, en intégration continue.

- **Webradio : le bouton « Configurer » menait à un 404** (2026-09-21). Il visait une route qui
  n'existait pas ; il ouvre désormais la modale de configuration commune à tous les addons, celle du
  bouton « Configuration » de la barre d'administration. Trouvé par `check-liens` en CI, où tous les
  modules sont installés.

- **La CI est de nouveau lisible : elle était rouge depuis le 26 août** (2026-09-21). Trois causes,
  aucune dans le produit. Le verrou Composer, régénéré sous PHP 8.5, exigeait 8.4 pour une
  bibliothèque : cinq jobs qui tournent sous 8.3 échouaient dès `composer install` ; la plateforme
  est désormais fixée à 8.2.0 dans `composer.json`. `check-docs` comptait les addons distribuables
  d'après les zips de `marketplace/`, qui ne sont pas versionnés : zéro en CI ; il lit le manifeste.
  Et `prepare-test-db` créait le compte de test pour `localhost` et `127.0.0.1` seulement, alors
  que MariaDB, dans son conteneur, voit le client arriver de la passerelle Docker ; l'ancienne
  version cachait ce refus derrière un `exit` à code zéro. Il crée aussi le compte pour l'hôte que
  le serveur voit, sans joker. Derrière, un quatrième défaut : les suites d'intégration se sautaient
  depuis `setUpBeforeClass()`, et `--fail-on-skipped` n'y voyait rien — le saut se prononce dans
  `setUp()`, test par test, et le drapeau le transforme bien en échec. Chaque job a enfin un budget de
  temps : celui des épreuves JS avait pendu six heures, deux fois. Et la bibliothèque des outils avait
  son défaut : `nf_http()` répondait « pas de réponse » à une redirection qu'on lui demandait de ne
  pas suivre, si bien que `check-install-profiles` comptait `/` et `/admin` en erreur serveur. Enfin,
  sous PHP 8.3, le serveur intégré présente `SCRIPT_NAME` égal au chemin demandé pour un asset sans
  fichier : `Url` en tirait une base tronquée et redirigeait feuilles et scripts vers la langue. Le
  routeur des outils se présente désormais comme un vrai serveur, et `Url` ne déduit la base que d'un
  `SCRIPT_NAME` qui finit par `index.php`.

- **Le logo de l'installateur ne s'affichait pas sur une installation neuve** (2026-09-21) : le
  chemin était relatif au dossier `install/`, que le serveur ne sert pas sous cette adresse. L'image
  est désormais embarquée dans la page.

- **Des composants qui ne s'ouvraient plus depuis le passage à Bootstrap 5** (2026-09-21). Bootstrap 5
  a préfixé tous ses attributs de données par `bs-`. Un attribut resté à l'ancien nom ne provoque
  aucune erreur : le composant se contente de ne rien faire. Étaient morts, en silence :

  | Ce qui ne marchait plus | Où |
  |---|---|
  | **L'accordéon de la FAQ** ne s'ouvrait pas | `modules/faq` |
  | **La liste des participants d'un événement** ne se dépliait pas | `modules/events` |
  | **Tous les popovers d'aide des formulaires** étaient vides | cœur — `neofrag/libraries/form.php` |
  | Le popover de profil du forum, celui de la suppression d'un lien de navigation | `modules/forum`, `widgets/navigation` |
  | Le placement des infobulles de l'éditeur en direct, le délai du carrousel | `modules/live_editor`, `widgets/slider` |

- **Des mises en page fausses, elles aussi silencieuses** (2026-09-21). Bootstrap 5 a renommé ses
  utilitaires directionnels ; une classe renommée n'est plus définie nulle part, et l'élément garde
  sa disposition par défaut sans que rien ne le signale.

  - **Les pieds de panneau et de modale ne s'alignaient pas à droite.** Le cœur fabriquait la classe
    par concaténation (`'float-'.$align`), donc aucune recherche sur `float-right` ne pouvait la
    trouver. Le seul rattrapage du produit ne couvrait que les tableaux du thème d'administration :
    rien sur les thèmes publics. Même cause pour l'alignement des cellules de tableau, dans les
    deux bibliothèques.
  - **Aucun champ de formulaire refusé n'était marqué**, sur tous les thèmes. Les deux bibliothèques
    posaient un nom hérité — `has-error` (Bootstrap 3) et `has-danger` (une préversion de Bootstrap 4)
    — qu'aucune feuille servie ne définit. Le message s'affichait bien, mais rien ne désignait lequel
    des dix champs le concernait. Ils sont remplacés par un marqueur maison, défini une fois.
  - **Le pied de page** portait `<div class="float-right">` : « Propulsé par NeoFrag » tombait à la
    ligne, à gauche. La valeur vient de loin — une migration de 2019 l'avait passée de `pull-right`
    à `float-right` au moment de Bootstrap 4, et le pas suivant n'a jamais été fait. Une migration
    (`2026_09_20_bootstrap5_float`) corrige les sites existants ; le seed livre désormais `float-end`.
  - Et les dernières classes mortes des vues : la barre de débogage sur téléphone, la gouttière des
    listes d'événements, les étiquettes de l'éditeur de menu, les deux barres du vote de recrutement
    — qui sortaient de la **même couleur**, le graphique ne distinguant plus les avis favorables des
    défavorables.

- **Onze coercitions de type, dont trois sur des chemins empruntés à chaque page** (2026-09-21),
  mises au jour par la vague `strict_types` ci-dessous :

  - `strnatcmp()` recevait l'ordre d'affichage d'une langue et d'un groupe — deux **entiers** — à
    l'initialisation de la session. Un site à deux langues actives aurait rendu un 500 sur toutes
    ses pages ;
  - `crypt::hash()` passait un flottant à `str_split()` ; c'est cette méthode qui fabrique le jeton
    anti-CSRF de **toute page à formulaire** ;
  - huit autres dans le diagnostic PHP, le flux de sauvegarde, le graphique par semaine et la barre
    de débogage — toutes dans des branches qu'aucun test n'emprunte.

- **Le corps d'un article pouvait être effacé à l'affichage** (2026-09-21). La construction du
  sommaire écrasait le contenu par le résultat de `preg_replace_callback()`, qui vaut `NULL` quand
  le moteur d'expressions régulières abandonne — ce qu'un article long peut provoquer. La page
  répondait 200 avec un article **vide**, sans rien dans les journaux.


- **Un widget placé en haut ou en bas de page disparaissait.** Le thème *Nebula*
  proposait les emplacements « Header » et « Footer » dans l'éditeur de mise en page, mais ne les
  affichait pas. Le widget était bien enregistré, et rien n'apparaissait — sans message. Les quatre
  autres thèmes publics n'étaient pas touchés.

- **Des pages s'affichaient normalement tout en répondant « page introuvable » aux moteurs de
  recherche.** Trois pages étaient dans ce cas, de façon intermittente : la même recette s'affichait
  correctement sans nombre de parts, et en erreur avec.

- **Les dépendances entre addons étaient toujours annoncées comme manquantes**, même quand le module
  requis était bel et bien installé. Deux autres conséquences du même défaut : les statistiques de
  l'administration se mélangeaient entre modules, et le widget de navigation proposait deux modules
  internes qu'il aurait dû masquer.

- **Le journal de débogage grossissait sans limite** quand il était activé. Il est désormais borné :
  au-delà de 64 Mo, il repart en conservant la génération précédente.

- **En anglais, « Non » s'affichait « Non »** au lieu de « No ».

- **Cinq pages d'administration n'offraient aucun retour** (2026-09-20) : la fiche d'un membre, le
  journal d'audit, l'ajout d'un groupe, la liste des sessions et les fichiers du monitoring. Le fil
  d'Ariane ne rend le nom du module cliquable que si le module déclare avoir une page d'accueil
  d'administration ; `user` et `monitoring` déclaraient le contraire alors qu'ils en ont une. Une fois
  arrivé sur ces pages, il fallait passer par le menu latéral ou le bouton du navigateur.

- **Les moteurs de recherche recevaient du JSON à la place du plan du site.** Demandé à la racine,
  sans préfixe de langue — la seule façon dont un robot les demande —, `/sitemap.xml` répondait
  `{"redirect":"/fr/sitemap.xml"}` avec un code 200, et `robots.txt` comme `humans.txt` faisaient de
  même. La redirection qui ajoute le préfixe de langue répondait en JSON dès que l'adresse finissait
  par `.txt`, `.xml` ou `.json`. Ces quatre fichiers n'ayant pas de version par langue, ils ne sont
  plus redirigés du tout : ils sont servis directement, avec leur vrai type de contenu.

- **`/favicon.ico` répondait 404.** Les navigateurs demandent cette adresse quoi qu'annonce la page ;
  elle mène désormais au favicon configuré dans les réglages, ou à celui du CMS.

- **Activer la double authentification plantait.** Au moment d'enregistrer les codes de récupération,
  une erreur interrompait la page : le compte se retrouvait marqué « protégé » sans aucun code de
  secours. Trouvé par un test écrit en durcissant le typage de cette bibliothèque ; corrigé, et le test
  reste.

- **Une limitation « une seule tentative » ne bloquait jamais.** Le seuil n'était vérifié qu'à partir
  de la deuxième tentative. Sans effet sur les réglages livrés (trois ou cinq tentatives), corrigé pour
  que le seuil vaille dès la première.

- **Des textes restaient en français dans les autres langues, et le journal se remplissait.** Trois
  causes, toutes corrigées : le titre d'une page du wiki, d'un type d'événement ou d'une campagne de dons
  passait par la traduction comme s'il était un texte de l'interface (il n'a pas de traduction : c'est du
  contenu) ; un texte déjà traduit repassait par la traduction — « Espace membre » devenait « Member
  area », puis « Member area » était cherché comme clé et signalé introuvable à chaque affichage ; et
  trente textes d'interface (le champ « Pseudo ou adresse email » de la connexion, des titres de panneaux
  de l'administration des jeux et du recrutement, les réseaux du profil…) n'avaient de traduction dans
  **aucune** langue, parce que le contrôle qui vérifie les six langues ne regardait que les appels
  explicites à la traduction, pas les titres de champs et d'en-têtes traduits en aval. Le contrôle les
  voit désormais ; les trente textes sont traduits en anglais, allemand, espagnol, italien et portugais.

- **Google Analytics attend désormais votre consentement.** Si un identifiant Analytics est
  configuré, le script de Google ne se charge plus qu'après un clic sur « Tout accepter » dans la
  bannière de cookies, jamais avant. Par ailleurs la politique de sécurité du site refusait ce script
  dans tous les cas : Analytics ne fonctionnait pas, sans le dire. Il fonctionne maintenant, et
  seulement avec l'accord du visiteur.

- **Trois fonctions de l'administration ne répondaient plus depuis juin.** Le tri des tables par
  clic sur un en-tête de colonne, le glisser-déposer pour réorganiser catégories, forums et
  sous-forums, et les boutons Ouvert/Fermé des pages Maintenance et Inscriptions : leurs scripts
  supposaient encore la présence de jQuery, retiré du CMS en juin 2026, et s'arrêtaient net au
  chargement de la page — sans message, les boutons restant simplement inertes. Les trois sont
  réécrits sans jQuery, couverts par des épreuves en navigateur, et un contrôle automatique refuse
  désormais tout script qui ferait de nouveau appel à jQuery.

- **Une adresse mal formée provoquait une erreur serveur.** Une requête comme `/fr/a:80` ou `/:80`,
  du genre qu'envoient les robots, faisait planter le calcul de l'extension de l'adresse et rendait
  une erreur 500 au visiteur. Le site répond désormais par un 404 ordinaire.

- **Le formulaire de contact écrivait « De : » avec l'adresse du visiteur.** Envoyer depuis votre
  serveur un message au nom de `visiteur@example.org`, c'est exactement ce que les protections
  anti-usurpation des messageries (SPF, DMARC) rejettent : le message finissait en spam ou refusé.
  L'expéditeur est désormais l'adresse de contact du site, et l'adresse du visiteur est placée en
  « Répondre à », ce qui revient au même à l'usage. Même correction pour la réponse à une candidature,
  qui pouvait partir avec l'adresse personnelle de l'administrateur.

- **L'installeur pouvait enregistrer une adresse de contact invalide.** Quand le site était installé
  en y accédant par son adresse IP, l'adresse de contact devenait `noreply@<IP>`, que les
  messageries refusent — et plus aucun e-mail ne partait, sans message d'erreur visible. L'adresse
  n'est plus déduite que d'un vrai nom de domaine.

- **Un bandeau vide s'affichait sur les pages dont une zone de mise en page est vide.** En thème
  Extend, toutes les pages autres que l'accueil montraient une large bande colorée ne contenant
  rien, ainsi qu'une section vide au-dessus du contenu.

  La cause ne venait pas du thème : une zone déclarée mais sans widget renvoyait des espaces plutôt
  que rien du tout, et les thèmes dessinaient donc leur cadre autour du vide. Corrigé une fois pour
  tous les thèmes. Si vous créez un thème, vous pouvez continuer d'écrire
  `if ($zone = $this->output->region('banner'))` : la condition dit désormais la vérité.

- **Le thème Extend affichait sa mention « Propulsé par NeoFrag Reborn » deux fois.** Sa mise en
  page livrée posait dans le pied un bloc reprenant ce que le thème écrit déjà lui-même. Les sites
  déjà installés sont corrigés automatiquement à la mise à jour ; les nouveaux ne l'auront jamais.

- **Chercher un mot courant renvoyait une page d'erreur.** Une recherche comme « le » ou « de »
  affichait bien ses résultats à l'écran, mais le serveur répondait **404** — ce que voient les
  moteurs de recherche, les outils de supervision et les navigateurs, et ce qui faisait passer une
  page parfaitement valide pour une page disparue.

  La mise en évidence des mots trouvés partait du principe que le mot cherché figurait forcément
  dans le texte affiché. C'est faux deux fois : la recherche examine **plusieurs champs** et n'en
  affiche qu'un — un sujet de forum intitulé « Salut tout le monde ! » répond à « le » par son
  titre, pas par le message montré dessous — et la base de données compare **sans tenir compte des
  accents** là où le code, lui, en tenait compte : « éléphant » ressortait bien pour la recherche
  « ele », sans qu'aucun mot n'y soit surligné.

  Les deux cas sont traités : le surlignage reconnaît désormais les variantes accentuées d'une
  lettre, comme la base de données, et quand le mot n'est réellement pas dans le texte affiché,
  l'extrait commence simplement à son début.

- **La mise à jour automatique du cœur ne pouvait pas fonctionner** — cinq défauts, chacun suffisant à
  lui seul : le réglage qui désigne la source des versions était **vide par défaut et déclaré nulle part**
  (le manifeste n'était donc jamais téléchargé, et le bouton n'apparaissait jamais) ; un interrupteur
  global bloquait la méthode ; l'URL de téléchargement était codée en dur vers la release **upstream**,
  qui aurait écrasé le code de ce fork ; le paquet produit rangeait tout sous un dossier racine alors que
  l'updater écrit à plat, donc une mise à jour aurait créé un sous-dossier **sans rien remplacer et sans
  la moindre erreur** ; et l'extraction passait par l'API zip procédurale, dépréciée depuis PHP 8.0.

  L'interrupteur est remplacé par quatre garanties : l'origine vient d'une **liste d'hôtes autorisés**
  (la même que le marketplace), le manifeste ne fournit qu'un **nom de fichier** et jamais une URL,
  l'empreinte **SHA-256** est vérifiée **avant** qu'un seul fichier ne soit touché, et l'archive est
  contrôlée entrée par entrée (chemins d'évasion et liens symboliques refusés). `config/` et `install/`
  ne sont jamais réécrits quand ils existent déjà.

- **Le catalogue du marketplace était injoignable depuis n'importe quel site.** Le serveur renvoyait
  `{"redirect":"/fr/…"}` **avec un code 200** au lieu du fichier statique — un corps parfaitement valide
  en JSON, et parfaitement faux, donc un échec silencieux qui faisait retomber chaque site sur son
  catalogue local. Corrigé sur le serveur du catalogue (les fichiers de distribution y sont servis en
  statique) **et** côté code (la forme des manifestes est validée,
  plus seulement leur décodabilité).

- **Un widget sans réglages ne pouvait pas être ajouté au Live Editor.** Le contrôle de formulaire
  exigeait le champ `settings` comme obligatoire : les widgets qui n'ont aucun réglage (Copyright, Fil
  d'Ariane, Recherche…) échouaient avant d'atteindre le contrôleur, sans message exploitable. Un nom de
  champ suffixé de `?` le rend facultatif.

- **Le bouton « Supprimer » de l'administration** ne protégeait que le thème `admin`, par son nom écrit
  en dur. Il s'appuie maintenant sur la déclaration, donc protège aussi `nebula`.

- **Couplages du cœur vers l'optionnel.** Six modules du cœur interrogeaient les tables de modules
  optionnels. Un seul était réellement cassé — dans `teams`, la garde d'existence de `recruits` était
  placée **après** la requête qu'elle devait protéger. Les autres étaient gardés mais muets : ils portent
  désormais une annotation vérifiée par la CI. Le seul cycle dur du paquet (`games` ↔ `teams`) est rompu.

- **Live Editor : les modifications ne s'affichaient qu'après rechargement.** `NF.post()` parse la
  réponse en JSON (`NF.ajax` fait `response.json()` sauf si `dataType: 'text'`), or **tous** les
  endpoints `admin/ajax/live-editor/*` répondent en `text/html` : un fragment de disposition, ou un
  corps vide pour les mutations. `response.json()` levait donc sur `<` (ou sur le corps vide), la
  promesse était rejetée, et les `.then()` qui mettent le DOM à jour ne tournaient jamais — alors que
  le serveur, lui, avait bien enregistré. Concrètement : on supprimait un widget et il restait à
  l'écran, on ajoutait une ligne et elle n'apparaissait pas, jusqu'au rechargement de la page. Les
  **16 appels** du module passent par un helper `nfLePost()` qui force `dataType: 'text'`, comme le
  faisaient déjà `js/delete.js` et `js/popover.js`. Régression introduite par la conversion vanilla :
  jQuery devinait le type de réponse, `fetch` non. *(Audit des autres appelants de
  `NF.post` : `monitoring.json`, `monitoring/sudo` et le file-manager visent bien du JSON — seul le
  Live Editor était touché.)*
- **Live Editor : le glisser-déposer des widgets ne fonctionnait pas.** SortableJS était branché sur
  `[data-col-id]`, mais les widgets sont enveloppés un cran plus bas dans `.live-editor-col` (le
  wrapper que `col.php` ajoute avec l'en-tête de colonne quand le mode Colonnes est actif). Or
  SortableJS ne déplace que les **enfants directs** de son conteneur, là où jQuery UI acceptait un
  sélecteur de descendants (`items: '[data-widget-id]'`). Symptôme : un widget ajouté restait collé en
  bas de colonne, impossible de le remonter au-dessus du module de la page. Le tri vise désormais le
  vrai parent (`.live-editor-col` s'il existe, `[data-col-id]` sinon — le wrapper n'existe pas quand le
  mode Colonnes est éteint), et `col_id` est relu via `closest()`. Les lignes et les colonnes
  n'étaient pas concernées : elles sont bien enfants directs de leur conteneur.
- **Live Editor : impossible d'ajouter 10 des 38 widgets** (`breadcrumb`, `copyright`, `downloads`,
  `forum`, `module`, `news`, `newsletter`, `slider`, `surveys`, `teams`) — `widget-add` répondait
  **404**. Le checker serveur fait un `post_check()` qui exige la **présence** du champ `settings` ;
  les widgets qui ont des réglages nomment leurs champs `settings[clé]` et le remplissent donc, mais
  ceux **sans `controllers/admin.php`** n'envoient rien. Le JS prévoyait ce cas avec un repli
  `settings: null`… que le sérialiseur de `NF.ajax` **omet** (`v !== null`), là où `$.param()` de
  jQuery écrivait `settings=`. Le repli est passé en chaîne vide, ce qui rétablit le format d'avant la
  dé-jQuery.
- **`NF.ajax` : un statut HTTP d'erreur ne rejetait pas.** `fetch` ne se contente pas de résoudre, il
  livre le **corps de la page d'erreur** : un appelant en `dataType: 'text'` insérait donc la page
  « 404 Not Found » dans le DOM comme une réponse valide. jQuery ne déclenchait pas `.done()` sur un
  404 ; le garde `response.ok` rétablit ce comportement. Touche tout le front — vérifié que les flux
  qui signalent une erreur applicative (le `sudo` du file-manager) répondent en 200 avec un JSON.
- **Live Editor : formulaire de réglages du mauvais widget.** `load_settings()` postait vers
  `widget-admin` sans numéro de séquence : deux changements rapides de widget ou de type et la réponse
  la plus lente écrasait la plus récente. La requête dépassée est désormais **annulée**
  (`AbortController`, déjà supporté par `NF.ajax`) et sa réponse ignorée si elle arrive quand même ;
  une clé `widget::type` évite en prime de recharger — donc de perdre la saisie en cours — quand la
  sélection n'a pas réellement changé.
- **Port de base de données ignoré (connexion possible qu'en 3306)** : le driver runtime
  (`neofrag/drivers/mysqli.php`) construisait `new mysqli(host, user, pass, db)` **sans port**, et
  `write_config` ne le persistait pas dans `config/db.php`. Résultat : impossible d'installer/faire tourner
  le CMS sur un port MySQL non standard (l'assistant web collectait bien le port mais il était perdu, et
  `install/cli.php --db-port` était silencieusement inopérant). Le port est désormais **persisté** (s'il
  diffère de 3306, pour garder les config standards propres) et **propagé** jusqu'à `mysqli()` (driver +
  `db.php` + `write_config` + les 3 installeurs). Le chemin 3306 par défaut est inchangé (aucune régression).
- **Thèmes vides à l'activation** : activer un thème ne faisait que changer `nf_default_theme` sans
  appliquer sa mise en page par défaut (son `install()`), lancée seulement au « Réinstaller par défaut »
  manuel. Résultat : `forge`, `granite`, `blockcraft`, `extend` s'affichaient **vides** (zones sans
  widgets). `enable()` applique désormais la disposition par défaut du thème s'il n'en a aucune (sans
  jamais écraser une personnalisation existante).
- **Changement de thème par défaut ignoré par le cookie visiteur** : le sélecteur de thème (footer) pose
  un cookie `nf_theme` (1 an) qui prime sur le défaut — l'admin changeait le thème mais les visiteurs (et
  lui-même) restaient collés sur leur ancien choix, sans moyen d'en sortir depuis un thème sans sélecteur.
  Introduit une **« époque »** (`nf_theme_epoch`) incrémentée à chaque changement de défaut : la préférence
  n'est honorée que si elle a été posée **depuis** le dernier changement, sinon le cookie est **ignoré et
  effacé** et le visiteur suit le nouveau défaut (l'admin décide). Un choix explicite postérieur reste respecté.
- **Pages dynamiques mises en cache par le navigateur** : aucune réponse n'envoyait d'en-tête de cache →
  cache heuristique du navigateur, d'où du contenu périmé (ex. thème changé côté admin visible seulement
  au Ctrl+F5). Les réponses HTML/JSON dynamiques envoient désormais `Cache-Control: no-store` ; les assets
  statiques (servis par le serveur web, cache-bustés par mtime) ne sont pas concernés.

### Sécurité

- **Une valeur enregistrée ne peut plus injecter de balises dans un formulaire** (2026-09-23). Le
  formulaire d'édition posait la valeur d'un champ dans la page en échappant les guillemets à la
  manière d'une chaîne PHP — sans effet en HTML. Une valeur qui n'était pas passée par le formulaire
  lui-même (import, données de démonstration, autre module) pouvait donc sortir de son champ et
  ajouter du code à l'écran d'administration : la source d'une citation valant `"><img src=x>`
  l'a montré. Champs texte, zones de texte, listes, cases à cocher et boutons radio encodent
  désormais leur valeur ; le rendu d'une valeur saisie normalement ne change pas. Les notifications
  (« Citation modifiée », etc.) sont aussi passées au script de la page sous une forme qui ne peut
  plus le casser.

- **La politique de sécurité déclare désormais une règle pour les médias** (`media-src`). Elle n'en
  avait aucune et héritait d'une règle plus large ; la déclarer explicitement la rend plus lisible.
  L'adresse d'un flux de webradio n'y est ajoutée **que si un flux est configuré** : un site sans
  webradio n'autorise rien de plus qu'avant. Le reste de la politique est inchangé.

- **Onze avis de sécurité fermés sur les dépendances** (2026-09-20) : `league/commonmark` passe de
  **2.8.2 à 2.10.1** (dix avis, dont huit graves) et `phpseclib` de **3.0.52 à 3.0.57** (un avis moyen).

  Neuf des dix avis de commonmark sont des **dénis de service par Markdown fabriqué** — analyse en temps
  quadratique, titres aux ancres colliionnantes, notes de bas de page dupliquées, blocs d'attributs
  adjacents, sortie XML profondément imbriquée — et le dixième est une **faille XSS** : le filtre des
  attributs `on*` se contournait avec un saut de page U+000C. La bibliothèque rend le Markdown du wiki,
  des articles et de la FAQ, écrit par des membres ; nos garde-fous (échappement du HTML, liens non
  sûrs refusés, imbrication bornée) couvraient le XSS mais rien des dénis de service.

  Le rendu ne change pas, et ce n'est pas une impression : **douze cas représentatifs** — titres,
  tableau, liste de tâches, bloc de code, HTML injecté, lien `javascript:`, citation, texte barré,
  lien automatique, listes imbriquées, image, entités et note de bas de page, imbrication profonde —
  rendus par les deux versions avec la configuration réelle du produit donnent le même HTML
  **octet pour octet** (1568 octets, même empreinte).

  À noter : Composer **refuse désormais d'installer** la 2.8.2, précisément à cause de ces dix
  avis. Il a fallu désactiver la politique dans un dossier jetable pour mener la comparaison.

## [1.1.0] — 2026-08-23

### Sécurité
- **Audit complet + durcissement** :
  - **Sauvegardes** : les archives (`backups/*.zip`, contenant le dump SQL + `config/`) ne sont plus
    servies en HTTP (`backups/.htaccess` + `logs/.htaccess`, parité dans `nginx.conf`) et leur nom est
    suffixé par `random_bytes` (plus devinable).
  - **Jetons** : `unique_id()` utilise un CSPRNG (`random_bytes`) — concerne ID de session, jetons CSRF,
    liens de reset/validation. Les liens de reset/validation **expirent en 1 h** et sont uniques par compte.
  - **Connexion sociale & reset** : le 2FA (TOTP) et le bannissement sont désormais vérifiés sur **tous**
    les chemins de connexion (mot de passe, OAuth, reset), pas seulement la voie mot de passe.
  - **Liens d'e-mail** : URL absolues construites sur une **origine canonique** (`config/url.php`, figée à
    l'installation) au lieu de l'en-tête `Host` (anti *host header injection* / *password-reset poisoning*).
    Idem callbacks OAuth et retours Stripe.
  - **XSS admin** : noms de pièces jointes (snapshot de modération) et IP `X-Real-IP` (forgeable) validés
    et échappés au stockage et au rendu ; MOTD de serveur de jeu (API tierce) assaini par HTMLPurifier.
  - **CSRF** : jeton exigé sur **toutes** les actions admin mutantes (suppression/bascule/clôture/
    approbation/activation/restauration/purge) de 22 modules — auparavant de simples liens GET.
  - **Outils** : `tools/*.php` refusent toute exécution hors CLI ; `composer audit` ajouté à la CI.
  - **CSP effective — fin des gestionnaires d'événements inline** : le `script-src` strict (nonce, sans
    `unsafe-inline`) bloque les attributs `on*="…"` inline (les nonces ne les couvrent pas). Tous les
    handlers inline restants (bandeau cookies, pagination des tables admin, confirmations trash/revisions,
    aperçu de fichier des messages privés, actions groupées des mentions forum, sélection au clic, modal de
    suppression) sont passés en `addEventListener` délégué (via `main.tpl`, `js/delete.js`, `js/confirm.js`).
    La délégation couvre en prime le contenu injecté en AJAX. Corrige aussi un `onchange` de pagination qui
    référençait encore `$()` (jQuery pourtant retiré).

### Corrigé (compatibilité base de données)
- **Transactions sur MySQL 8** : `START TRANSACTION` passait par le pipeline *prepared statement* (refusé
  par MySQL 8, erreur 1295) → contrôle transactionnel via l'API mysqli (`begin_transaction`/`commit`/
  `rollback`). Tous les écrits forum/talks/slider étaient fatals sur MySQL 8.
- **Emojis (utf8mb4)** : la connexion forçait `utf8` (= utf8mb3) → tout caractère 4 octets provoquait une
  erreur 1366. Connexion en `utf8mb4` + migration de toutes les tables en `utf8mb4_unicode_ci` (collation
  portable MySQL 8 / MariaDB 10, fin des `uca1400` spécifiques MariaDB 11).
- **`where('col', [])`** générait une condition vide (→ `DELETE`/`UPDATE` sur toute la table) : produit
  désormais `1 = 0` (ensemble vide).
- **Dates futures en `TIMESTAMP` (limite 2038)** : les colonnes stockant une date choisie dans le futur
  — envoi programmé de newsletter (`scheduled_at`), publication programmée de news/articles/gallery/pages
  (`date`), dates d'événement (`date`/`date_end`/`publish_date`) — étaient en `TIMESTAMP`, dont la plage
  s'arrête au 19/01/2038. Au-delà, MariaDB en mode strict **rejette l'écriture** (errno 1292) et
  l'insertion échouait silencieusement (driver en `mysqli_report(OFF)`). Passées en `DATETIME` (jusqu'à
  l'an 9999). Migrations fournies pour les installations existantes. Bug attrapé par
  `tests/Headless/NewsletterSchedulingTest` (jamais exécuté en CI faute de base de données branchée).

### Corrigé (routage & interface)
- **`/user/login` et `/user/registration` renvoyaient 404** : les thèmes exposent ces URLs en lien des
  boutons d'en-tête (repli sans JavaScript de la modale), mais les méthodes de contrôleur correspondantes
  n'existaient que côté AJAX. Ajoutées à `modules/user/controllers/index.php` + gardes dans `checker.php`.
- **Bouton « Inscription » mort quand les inscriptions sont fermées** : le thème Nebula
  affichait le bouton sans vérifier `nf_registration_status` → un clic menait à un 404.
  Masqué quand les inscriptions sont fermées.

### Ajouté
- **Cache-bust des assets par mtime** : chaque CSS/JS est servi `?v=<mtime du fichier résolu>`
  (overrides inclus, helper `asset_version()`) → **auto-invalidation par fichier** à chaque
  modification/upload, sans bumper `nf_version_css` à la main (repli sur `nf_version_css` si le fichier
  n'est pas localisable).
- **Marketplace** : signalement d'une nouvelle version du **cœur** (lit `base_version` du catalogue) dans
  l'écran « Mises à jour ».
- **Installeur** : refonte esthétique — logo NeoFrag (SVG vectoriel), bandeau « Fork non officiel de
  NeoFrag », crédit Michaël BILCOT & Jérémy VALENTIN (LGPLv3), polices Reborn (Inter + Space Grotesk),
  palette teal.
- Module **files** (gestionnaire de fichiers, arborescence + ACL par fichier/dossier) validé et embarqué
  → **54 modules**.
- Module **emojis** (cœur) : émojis personnalisés rendus partout via `:nom:` (helper `bbcode()`), CRUD admin.
- **Newsletter** : programmation d'envoi + file batchée pilotée par cron, suivi des ouvertures (pixel + taux),
  modèles d'e-mail réutilisables, segmentation (tous / membres / groupe).
- **Events** : événements récurrents (occurrences matérialisées) + rappels cron aux participants.
- **Réactions multi-emoji** (👍❤️😂😮😢😡, façon Discord/FB) sur le cœur de réactions polymorphe.
- Widget **« Statut live »** multi-chaînes / multi-plateformes (Twitch + YouTube, abstraction provider).
- **Page-builder** : blocs de module paramétriques `[block:clé p=v]` + blocs ordonnés/configurés par page
  (composer admin, table `nf_pages_instances`).
- Thème **Extend** (port BS5) distribuable via la marketplace ; **recherche instantanée** (typeahead),
  **tri** sur 10 grilles admin, **avatars GIF animés** préservés, **ACL** éditable en modale.
- **Chaîne de publication à source unique** : `tools/changelog-section.php` extrait une section de ce
  fichier (Markdown ou HTML) ; les notes de version en sont tirées, et leurs textes ne peuvent plus
  diverger.

### Modifié
- **Bootstrap 4.6.2 → 5.3.8** + **jQuery entièrement retiré** : JS 100 % vanilla derrière un helper minimal
  `window.NF` (ready/data/ajax/setHtml/loadScript avec nonce CSP). 9 plugins jQuery/BS4 remplacés
  (notify→toasts BS5, selectize→tom-select, datetimepicker→flatpickr, FullCalendar 3→6, color/iconpicker
  /treeview/knob→vanilla, mCustomScrollbar→scroll natif), jQuery UI→SortableJS.
- **Dark mode** harmonisé sur tous les thèmes (`data-bs-theme` + remap des variables BS5 sur les tokens
  `--nf-*`, TinyMCE suit le thème).
- **Réglages widgets & dispositions encodés en JSON** (remplace `serialize` PHP) — supprime la surface
  d'injection d'objet ; décodage rétro-compatible de l'ancien format.

### Corrigé
- **Widget Discord** : vrai diagnostic d'échec (widget désactivé / ID introuvable / réseau) au lieu d'un
  message générique, **vrai logo du serveur** (via l'invitation publique), compteur en ligne **autoritatif**
  (`presence_count`, la liste des membres est plafonnée à 100).
- **Widget TeamSpeak (mode arbre)** : le viewer du framework crashait sous PHP 8 → **rendu maison** (arbre
  canaux/clients, icônes FontAwesome 6, plus aucun pack d'icônes requis) ; erreurs réseau génériques (ne
  fuitent plus `host:port`).
- **Widgets réseau** : `Network` auto-décodait déjà le JSON → double-décodage = faux « inaccessible » (Discord,
  Twitch, gameserver) ; + **User-Agent par défaut** (sans lui, les API derrière Cloudflare renvoient 403).
- **Monitoring / sauvegarde sous PHP-FPM** : `_stream()` appelait `@apache_setenv()` (disponible seulement
  sous mod_php) → fatal sous Apache fpm-fcgi (en PHP 8 le `@` ne masque pas l'`Error`) ; gardé par
  `function_exists()`. Backup AJAX servi **sans extension `.json`** (avalée par le « smart static »
  nginx/Plesk → 404). Suppression/téléchargement de sauvegarde (le placeholder `{url_title}` n'accepte pas
  le `.zip`). Garde treeview « Not initialized » + `padding-bottom` invalide.
- **Marketplace injoignable** : URL par défaut passée en **non-www** (`https://neofrag-reborn.xyz/marketplace`).
- **Upload** : `uploaded_file($files, …, $var)` traitait `$var = 0` (1er fichier d'un envoi multiple) comme
  falsy → `basename(array)` → 404 ; corrigé (`$var !== NULL`).

### Documentation
- Wiki dev/utilisateur enrichi (`form()`/`form2()`, checker de widget, dépannage installation, workflow
  rôle, marketplace injoignable…) + note de déploiement **nginx/Plesk** (`.json` en static → 404).

---

## [1.0.0] — 2026-06-06 · socle Reborn (base Alpha 0.2.4)

Premier cycle du fork : modernisation du socle, durcissement sécurité et large vague de
fonctionnalités.

### Ajouté

**Plateforme & outillage**
- Runner de migrations versionnées + commande `baseline`, tests PHPUnit pilotes, bootstrap tolérant.
- **Installeur web** (assistant 4 étapes : Prérequis → Base de données → Administrateur → Terminé) :
  modèle **« tout bundlé »** — tous les modules, widgets et thèmes livrés sont installés et activés
  automatiquement à l'étape « Base de données » (page d'accueil garantie non vide).
- **Marketplace distant** (catalogue + archives servis depuis neofrag-reborn.xyz) : sert **après**
  l'installation — **détection des mises à jour** des addons installés (+ migrations de schéma par-addon)
  et **ajout d'addons tiers** en un clic depuis l'admin. Sécurité : HTTPS strict, vérification **SHA-256**,
  **anti-zip-slip** (chemins/`..`/symlinks), origine fixe (anti-SSRF), tailles/timeout bornés.
- Outils de packaging : `package-addons` (catalogue du marketplace) / `build-release` (paquets FTP).
- CLI de **maintenance** : purge de la corbeille et des comptes jamais confirmés (cron externe).

**Thèmes & interface**
- Thème communautaire **Nebula** + thèmes **Forge**, **Blockcraft**, **Granite** ; pont de tokens `--nf-*` pour la cohérence.
- **Rework complet du panel admin** (nouvelle direction artistique, sidebar, palette de commandes, clair/sombre).
- Sélecteurs de **thème** et de **langue** en pied de page (visiteurs inclus).

**Contenu**
- News & articles : image à la une, **publication programmée** (parution à l'heure réelle via endpoint cron gardé par token : notifications/webhooks/gamification émis au bon moment, plus à l'enregistrement), compteur de vues, actions en masse, recherche/filtre/pagination, page article dédiée.
- **Publication programmée** aussi sur pages, galeries et événements (date de parution distincte de la date de tenue pour les événements).
- Wiki : documentation on-site (sommaire en cartes, markdown fiable), recherche & pagination, **diff visuel entre révisions** (comparaison ligne à ligne, moteur LCS maison).
- Médias & galeries : éditeur de métadonnées, recherche/filtre ; recherche/filtre/pagination aussi sur downloads, links, faq, surveys, guestbook.
- Flux **RSS 2.0** (news + articles), boutons de **partage** social, **SEO** (meta description, canonical, Open Graph, Twitter Card, meta par page).

**Communauté & engagement**
- Centre de **notifications** in-site (cloche + non-lus) avec **abonnements** (suivre contenu/catégorie) et triggers (commentaires, forum, @mention, MP, invitations d'événement).
- **Réactions** « j'aime » polymorphes (news/articles/commentaires/forum), **révisions** de contenu (historique + restauration), widget « derniers commentaires ».
- **Corbeille** générique (soft-delete + restauration) sur news, articles, galeries, commentaires, **messages forum**.

**Gaming**
- Événements : CRUD complet des adversaires, compte à rebours live, notification d'invitation.
- Forum : zone **VIP**, image par catégorie ; **menu builder** (constructeur de menus hiérarchiques) intégré au widget navigation.

**Monétisation & gamification**
- Karma/réputation → **points** (barème configurable) → **boutique** (paiement en points) → statut **VIP**.
- **Stripe** (recharge de points + packs VIP), régie **publicitaire** (option sans-pub VIP), dons.

**Carrefours de contribution inter-modules**
- Statistiques agrégées (19 modules), **mur d'activité** cross-module sur le profil, **tableau de bord** « à traiter », et **blocs de page** : injection d'un bloc de module dans une page statique via le shortcode `[block:clé]` (news/articles/downloads).

**Webhooks & audit**
- **Webhooks** sortants signés HMAC (lib + admin CRUD). **Journal d'audit** : installation/désinstallation d'addon, activation thème/module, **sauvegarde de réglages** (`settings.saved`).

**Administration des comptes**
- Édition et suppression d'utilisateurs côté admin, **export des membres CSV/JSON (RGPD)**, réglages de modération côté espace membre.

### Modifié
- Versionnage normalisé en **SemVer pur** dans les dépendances d'addons (retrait des libellés « Alpha »).
- Packaging : `pages` reclassé dans le **core** ; news/forum/gallery désinstallables.
- Éditeur riche fonctionnel (sanitisation au lieu d'échappement).

### Corrigé
- 10 bugs fonctionnels relevés à l'audit ; déduplication des compteurs de vues (anti-gonflage).
- Publication programmée masquée aussi en accès URL direct ; câblages morts réparés (boutons de période des stats, toggles de templates email, réseaux sociaux, routes « Ajouter »).
- Thèmes : lisibilité & contrastes WCAG, login soudé, alignements.
- **Responsive** (tous supports, téléphone→TV) : tiroir mobile + contenu pleine largeur de l'admin, profil membre, userbar compacte des 4 thèmes communautaires.
- `strict_types` : TypeErrors corrigés (timetostr, helpers) ; `(int)` sur `row(FALSE)` d'un COUNT/SUM.

### Sécurité
- Sanitisation HTML serveur anti-XSS stocké (HTMLPurifier), validation d'upload par **magic bytes**, **HSTS**.
- **CSRF** durci (`hash_equals`, IP client secure-by-default), checks de permission **RBAC** manquants ajoutés.
- `unserialize` legacy bornés via `allowed_classes`.
- **Empreinte de session** (anti-détournement) : déconnexion si le user-agent diffère fortement de celui d'origine (tolérant, membres connectés). Endpoint de parution cron gardé par token (`hash_equals`).
