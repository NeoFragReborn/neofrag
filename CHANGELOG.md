# Changelog

Tous les changements notables de **NeoFrag Reborn** sont consignés ici.

Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) ;
les numéros de version s'inspirent du [versionnage sémantique](https://semver.org/lang/fr/) ; une version qui ne change
que le dernier chiffre (1.2.x) peut toutefois apporter des nouveautés et des retraits.

NeoFrag Reborn est la continuité communautaire de **NeoFrag** (base Alpha 0.2.4), créé à l'origine par
Michaël BILCOT et Jérémy VALENTIN — projet open source sous licence LGPLv3.

---

## [1.2.49] — 2026-10-10

Un gros lot : une adresse au mauvais titre mène à la bonne, le relevé des pages introuvables, l'éditeur de texte dans
la langue de la page, le module Événements utilisable sans les jeux — pour une association ou un club —, les images
des tickets venues de Discord, et une longue liste de corrections trouvées en relisant tout le journal des versions,
dont la fiche d'un match qui n'affichait plus son match. Le bot Discord passe en **version 0.2.7** (voir son propre
journal des versions).

### Ajouté

- **Les images d'un ticket du Bugtracker** : une capture jointe sur Discord à un ticket ou à une idée, à son
  ouverture ou dans une réponse, s'affiche dans le ticket du site — avec le bot 0.2.7, qui la confie au site comme il
  le fait déjà pour le forum. Le texte des tickets reste du texte brut : seules les images gardées par le site s'y
  affichent ; l'adresse d'une image hébergée ailleurs reste du texte.
- **Le relevé des pages introuvables**, dans *Monitoring → Diagnostic* : chaque adresse que le site n'a pas
  trouvée — sans la langue ni les paramètres —, combien de visiteurs et de robots l'ont demandée, la dernière fois
  qu'on l'a demandée et la dernière page d'où l'on venait. Un bouton « Rediriger » ouvre le formulaire des
  redirections, l'adresse déjà remplie ; les sondes des robots qui cherchent une faille sont rangées à part. Ni
  adresse IP, ni navigateur ; une adresse qu'on ne demande plus depuis 90 jours s'oublie.
- **Les images abandonnées de l'éditeur**, dans *Monitoring → Diagnostic* : une image envoyée dans un texte puis
  retirée, ou celle d'un brouillon jamais enregistré, restait sur le disque. La page les retrouve — aucun texte du
  site ne les affiche plus, ni contenu, ni message, ni réglage, ni révision, ni corbeille, et elles ont plus de
  30 jours —, les montre, et efface celles qu'on coche.
- **L'éditeur de texte parle la langue de la page** : ses menus, boutons et fenêtres étaient en anglais partout ; ils
  sont désormais en français, allemand, espagnol, italien ou portugais selon la langue de la page.
- **Un GIF animé reste animé** dans l'éditeur : il devenait une image fixe. Il est réécrit sans rien garder d'autre
  que ses images, leur minutage et sa boucle. Un GIF de plus de 2 000 px de côté ou de plus de 1 000 images devient,
  comme avant, une image fixe.
- **Les événements sans les jeux** : le module Événements ne demande plus les modules Jeux et Équipes. Une
  association, un club l'installent seul — invitations, réponses, récurrence, rappels ; les matchs, leurs adversaires
  et leurs scores viennent avec les modules Jeux et Équipes. Il entre dans les profils « Communauté » et
  « Association / club » de l'installation.

### Corrigé

- **Une adresse au mauvais titre redirige vers la bonne** (redirection permanente, 301) : le titre d'un contenu a
  changé, le lien vient d'une autre langue ou porte une faute. Les billets, catégories, auteurs et séries du Blog, le
  Palmarès, les tickets du Bugtracker, les événements du Calendrier, les petites annonces, les sondages et les
  recettes répondaient à n'importe quel titre — des doublons pour les moteurs de recherche ; les actualités, les
  événements, les offres de recrutement, les équipes, les albums et images de la Galerie, les forums et sujets, les
  profils, les groupes et les partenaires répondaient « introuvable » à l'ancienne adresse d'un titre changé. La page
  d'une catégorie du blog porte son titre, et non plus le texte de l'adresse ; la page d'un type ou d'une équipe
  d'événements qui n'existe pas répond « introuvable ».
- **Une image envoyée dans l'éditeur ne déborde plus** de la page sur les pages qui ne la bornaient pas (onglet
  « À propos » d'un profil, événements, partenaires, albums, offres de recrutement, équipes) ni sur un téléphone :
  elle prend au plus la largeur de la colonne.
- Une taille de moins de 1 000 octets s'écrit « 685 o », et non plus « 685,00 o ».
- **Forge**, de jour : les textes discrets (dates, auteurs, lieux, « Inscrit le… ») sont un peu plus foncés — ils
  restaient juste sous le seuil de lisibilité.
- **Forge et Extend** : les fins traits qui choisissent l'image du diaporama se touchent facilement du doigt au
  téléphone (ils ne faisaient que 3 px de haut) ; rien ne change à l'œil.
- **Chronique** : le nombre de notifications non lues, sur l'icône du compte, se lit nettement mieux, de jour comme
  de nuit.
- **Discord** : quand un sujet ou un message du forum, un ticket ou un commentaire de ticket est supprimé, la
  trace qui le reliait à son fil Discord s'efface d'elle-même une semaine plus tard ; elle restait en base pour
  toujours.
- **Plus rien de collé au bord** : la liste des IP bannies et son formulaire d'ajout (la phrase d'aide et le bouton
  touchaient les bords de leur carte), le champ de recherche de la liste des événements, la pagination d'un tableau
  dans Granite, la pagination et le bouton « Suivre » dans Nebula, un pseudo long dans la liste des membres. Au
  téléphone, les tableaux du diaporama et des permissions défilent dans leur carte au lieu d'en déborder.
- **Plus de boutons collés** : « Inviter », « Quitter »… dans une conversation, au téléphone ; « Retour » et « Poster
  le sujet » sur le forum ; les deux boutons de la recherche du forum dans l'administration.
- **Forge** : au téléphone, le menu de l'espace membre réapparaît — il passait sous la carte de la page ; sur
  ordinateur, il reste en vue quand on fait défiler. Le bouton vert « Voir ma candidature » redevient lisible.
- **La fiche d'un match** montre de nouveau son match — l'équipe, l'adversaire, le score, le jeu et les manches,
  qu'elle n'affichait plus. L'équipe, le score et l'adversaire tiennent sur une ligne, comme chaque manche, au
  téléphone comme à l'ordinateur.
- **Recrutement** : la page « Ma candidature » annonce de nouveau le bon poste et la bonne équipe — elle affichait
  le nom de l'icône à la place du poste. **Forum** : l'édition d'un forum, dans l'administration, montre de nouveau sa
  description et sa catégorie — elle montrait le titre à la place, et l'enregistrer pouvait ranger le forum ailleurs.
- **Modération** : une sanction s'efface d'elle-même trois ans après sa fin (échue ou levée ; un avertissement, trois
  ans après avoir été donné) ; une sanction sans fin (un ban définitif, par exemple) reste tant qu'elle court. Elles
  se gardaient sans limite.
- Le bouton de signalement (le drapeau « Signaler ce contenu ») se touche facilement du doigt au téléphone ; dans la
  liste *Templates emails* de l'administration, les variables du sujet d'un e-mail (`{{date}}`) se distinguent du
  texte.
- **Newsletter** : la cible « Groupe » et la liste des campagnes nomment chaque groupe dans la langue de
  l'administration ; elles le prenaient dans une langue au hasard (« Criador de addons », « Mitwirkender »).
- **Des libellés justes** : sur le profil d'un membre, le bouton « Contacter » s'affichait « Contact » ; le thème
  Extend écrit « À la une » et « Découvrir » dans la langue de la page (ils restaient en français) ; le bouton
  « Grille » du Blog et de la page des extensions ne se traduit plus « programme » en anglais, allemand, espagnol et
  italien ; le type de ticket « Demande de feature » devient « Demande de fonctionnalité ». En anglais, le bouton de
  connexion dit « Log in », le menu « Log out », et « Voir mon profil » se dit partout « View my profile ». Dans
  le Monitoring, la carte « Stockage » écrivait « Sauvegarde », « Libre » et « Total » en français dans toutes les
  langues ; le réglage des événements dit « Nombre d'événements par page ».
- **Pulse, de nuit** : le nombre de notifications non lues, sur l'icône du compte, se lit — un chiffre sombre sur la
  brique claire ; blanc, il n'avait que 3,9:1.
- **Le widget Dons** montre l'initiale d'un donateur dont le nom commence par une lettre accentuée ; il affichait
  « & ».
- **Réinitialiser son mot de passe** refuse, comme l'inscription et le changement, un mot de passe identique au
  pseudo : la réinitialisation ne le comparait pas.
- Le bot Discord passe en **version 0.2.7** (0.2.6 puis 0.2.7) : il confie au site les images jointes aux tickets
  (voir plus haut), et son journal ne note plus les reconnexions ordinaires à Discord, seulement une coupure qui dure
  plus d'une minute (voir son propre journal des versions).

## [1.2.48] — 2026-10-09

Un gros lot : un audit de sécurité (accès, injections, comptes, fichiers), la modération qui fonctionne enfin — ses
sanctions s'appliquent à toutes les écritures des membres —, une nouvelle adresse e-mail confirmée avant de compter,
l'effacement des comptes inactifs, et la connexion par Discord, GitHub ou Google réparée. Le bot Discord passe en
**version 0.2.5** (voir son propre journal des versions). **Sur un site en HTTPS, chacun se reconnecte une fois après
la mise à jour** : le cookie de session change de nom.

### Sécurité

- **La recherche du forum, ouverte aux visiteurs, laissait injecter du SQL** par un mot écrit entre guillemets ; la
  recherche de la messagerie avait le même défaut. Ce mot est maintenant nettoyé comme les autres, et échappé par la
  base de données elle-même.
- **Un envoi venu d'un autre site est refusé** (POST, PUT, DELETE…), d'après ce que dit le navigateur
  (`Sec-Fetch-Site`, à défaut `Origin`) : une page piégée ne peut plus faire agir un membre connecté à son insu. Un
  client sans navigateur — le bot, un service de paiement — n'est pas concerné.
- **Vingt actions qui modifiaient quelque chose par un simple lien** demandent désormais le jeton de la session, ou
  n'acceptent plus que POST : suivre un sujet, le mettre en annonce, tout marquer comme lu, quitter, archiver,
  supprimer ou restaurer une conversation, réagir, marquer ses notifications comme lues, donner sa disponibilité à
  un événement, ouvrir ou fermer le site et les inscriptions, lancer une sauvegarde ou une mise à jour, supprimer un
  champ de recrutement… Une déconnexion par un lien sans jeton se confirme par un bouton.
- **En HTTPS, pour un site installé à la racine de son domaine, le cookie de session porte le préfixe `__Host-`** :
  un sous-domaine (une démonstration, un webmail) ne le reçoit plus et ne peut plus en imposer un. Un site installé
  dans un sous-dossier garde un cookie sans ce préfixe, qui change seulement de nom.
- **Les comptes** :
  - le lien « mot de passe oublié » ouvrait aussi la validation d'une inscription, qui connecte sans rien demander
    pendant deux jours : chaque lien ne vaut plus que pour son usage ;
  - changer de mot de passe fait tomber une nouvelle adresse en attente et les liens « mot de passe oublié » ;
  - les erreurs de connexion se comptent par compte — quelle que soit la façon de l'écrire — et par réseau : une
    adresse IPv6 compte pour son réseau /64, dans toutes les limites d'essais du site ;
  - activer la double authentification ou lier un compte Discord, GitHub ou Google redemande le mot de passe ; cinq
    erreurs bloquent cette confirmation un quart d'heure ;
  - « mot de passe oublié » répond la même chose qu'une adresse soit inscrite ou non, et l'inscription ne dit plus
    qu'une adresse est déjà prise que cinq fois par heure et par réseau ;
  - l'historique des connexions note l'adresse réelle de la connexion, et non plus un en-tête que le navigateur peut
    choisir.
- **Douze fuites d'accès fermées** : l'historique et les révisions d'une page du wiki en brouillon ; les catégories
  du forum réservées au VIP, par la recherche et les profils ; les groupes cachés ; l'image d'un album fermé ; les
  matchs d'un type réservé ; la présence que des participants avaient cachée ; les commentaires et réactions d'un
  contenu invisible ; le signalement d'une conversation dont on ne fait pas partie.
- **Deux anciennes adresses de la messagerie rendaient les messages de n'importe quelle conversation**, même privée,
  à qui avait le droit de lire — visiteurs compris : retirées. Poster une image dans la galerie demandait le seul
  droit de voir l'album.
- **Les pièces jointes du forum et des conversations sont servies par le site**, à qui peut lire le sujet ou la
  conversation, et plus par le serveur web à quiconque avait leur adresse. Une image ou un PDF s'affichent, le reste
  se télécharge ; une pièce jointe qu'un navigateur exécuterait est refusée à l'envoi. Sur Apache, le fichier
  `upload/.htaccess` livré s'en charge ; sur nginx ou Caddy, reportez dans la configuration du serveur la règle qui
  refuse `upload/forum/` et `upload/talks/` (voir `nginx.conf` et `Caddyfile` livrés).
- **Ce que le serveur va chercher lui-même** (relais d'images, flux RSS, webhooks) se limite aux adresses publiques au
  sens strict, sur les ports 80 et 443.
- **Les clés Stripe ne se relisent plus en clair** dans l'administration : enregistrées chiffrées, jamais
  réaffichées.
- **Démonstration** : les adresses IP, noms d'hôte et sites de provenance des visiteurs ne s'y montrent plus ; une
  publicité, un lien, un partenaire qui mène hors du site s'affiche sur une page qui dit où il mène, au lieu d'y
  rediriger.
- **Un lien `javascript:` ne peut plus s'afficher** à la place d'une adresse : la page d'où arrive une session
  (administration), les liens d'un match (site de l'adversaire, retransmission, article). Le lien qu'un signalement
  donne vers le contenu signalé ne peut mener qu'à une page du site.

### Ajouté

- **Une nouvelle adresse e-mail attend sa confirmation** : le lien part à la nouvelle adresse (valable deux jours),
  l'ancienne est prévenue, et « Mon compte » propose de renvoyer le lien ou d'annuler. Une faute de frappe coupait
  jusqu'ici le membre de tout ce que le site lui envoie.
- **Les comptes inactifs s'effacent** : sans visite depuis trois ans — réglable dans *Paramètres*, `0` pour jamais —,
  un e-mail prévient le membre un mois avant, une visite annule tout. Jamais un administrateur.
- **Sanctionner un membre sans attendre un signalement**, depuis son historique de modération, que sa fiche
  d'administration propose.
- **Le bouton de signalement** (le drapeau « Signaler ce contenu ») sur le livre d'or, les images de la galerie, les
  petites annonces, les tickets et leurs commentaires.
- **« Mes sanctions »** dans l'espace membre, pour qui en a eu une dans l'année : sa nature, son motif, sa période.
- **La médiation** : depuis un signalement, le modérateur propose une conversation privée entre lui, le membre signalé
  et celui qui l'a signalé. Le membre signalé y verra qui l'a signalé : elle ne s'ouvre donc qu'avec l'accord de ce
  dernier, à qui une page explique ce qu'il accepte ; s'il refuse, son signalement reste anonyme et suit son cours.

### Corrigé

- **Les sanctions de modération s'appliquent à toutes les écritures des membres** : forum, messagerie,
  commentaires, livre d'or, Bugtracker, recrutement, petites annonces, galerie, profil, images envoyées dans
  l'éditeur de texte. Seuls le muet du forum et de la messagerie étaient vérifiés, et après l'envoi. Le formulaire
  laisse place à un avis qui dit ce que la sanction interdit, jusqu'à quand, et pourquoi ; la restriction des liens
  (« Restriction : liens ») refuse un texte qui en porte un, sans le perdre.
- **Aucune sanction ne se prononçait depuis l'écran de modération** : une erreur interne l'empêchait, si bien que
  l'escalade automatique ne s'est jamais déclenchée. Revus dans la foulée : les réglages (cases qui ne se décochaient
  pas, seuil du signaleur suspect), les portées, les durées (un bannissement temporaire sans durée devenait
  définitif), la validation par un modérateur plus haut placé, le panneau des modérateurs, les signalements en
  double, les pages 2 et suivantes, la liste d'IP. Un modérateur ne peut plus lever une sanction qui le vise, ni
  celle d'un membre de rang égal ou supérieur au sien, ni celle prononcée par un modérateur plus haut placé.
- **Le shadow ban fonctionne** : ce qu'écrit un membre qui en fait l'objet ne se montre qu'à lui et aux
  modérateurs, et rien ne se diffuse — ni notification, ni Discord, ni abonnés.
- **Un rôle donné en plus — Modérateur — retirait les droits d'un membre** : un modérateur perdait le forum et la
  messagerie. Un rôle ajoute des droits, il n'en retire plus.
- **Les e-mails partent dans la langue de celui qui les reçoit** — sanctions, réponses du forum, messagerie —, et
  plus dans celle du membre qui les a déclenchés.
- **La connexion par Discord, GitHub ou Google tombait en erreur** dès qu'un service était configuré (la
  bibliothèque OAuth avait changé), et envoyait au service une adresse de retour qu'il refusait. Un retour refusé dit
  maintenant pourquoi au lieu d'une page d'erreur, et l'avatar Discord est repris.
- **Livre d'or, administration** : approuver, rejeter ou supprimer un message menait à une page introuvable.
- **L'erreur d'un champ de formulaire s'écrit sous le champ**, au téléphone comme à l'ordinateur : elle ne se lisait
  que dans une bulle au survol.
- **La barre du haut de l'administration reste en haut** au défilement ; au téléphone, seuls le menu et le fil
  d'Ariane restent collés.
- **Trente-quatre fautes de traduction** dans douze fichiers de langue (« There are 1 user », des confirmations de
  suppression qui affichaient `< br / >`, des pluriels anglais…).
- L'adresse de site que déclarent 48 addons du cœur est celle du projet, et non plus celle du NeoFrag d'origine. La
  page *Templates emails* de l'administration ne liste plus les modèles d'un module non installé. Une erreur dans
  certains formulaires (livre d'or trop sollicité, suppression du compte, double authentification, mot de passe,
  création d'un membre dans l'administration) ne mène plus à une page introuvable.
- **Démonstration** : un membre ordinaire n'ouvrait aucun sujet, ne répondait nulle part, ne postulait à rien.
- **L'export « Mes données » contient les signalements faits par le membre** : ils manquaient à l'archive.

### Modifié

- **Un widget lié à un module en dépend désormais** (21 widgets) : sans ce module, ou s'il est désactivé, le widget
  ne s'affiche plus et l'éditeur en direct ne le propose plus ; « Ajouter » refuse une archive de widget sans son
  module.
- **La documentation du produit ne se livre plus** dans le wiki d'un site neuf, qui arrive vide ; elle vit sur le site
  du projet. Une installation neuve ne reçoit plus les réglages de thèmes qu'elle n'a pas.

### Retiré

- Les anciennes adresses `ajax/talks` de la messagerie, et son ancien signalement d'un message, qui ne faisait
  qu'écrire au journal d'audit, avec sa page d'administration « Signalements de messages » : un message se signale
  désormais par la modération.

## [1.2.47] — 2026-10-09

La barre d'objets de Blockcraft, au téléphone.

### Corrigé

- **La barre d'objets de Blockcraft occupe toute la largeur du téléphone** : ses cases se partagent l'écran à parts
  égales et chaque nom tient dans la sienne — sur deux lignes pour « Mon espace », coupé au trait d'union pour un mot
  allemand ou portugais trop long (« Neuig-keiten »). Elle gardait la même largeur quel que soit l'écran (sept cases
  de 47 px) : un vide à droite, des cases étroites et « Nouvelles » qui débordait de la sienne. Le cadre blanc de la
  case choisie ne touche plus son nom.

## [1.2.46] — 2026-10-08

Les bandeaux du haut de page ne recouvrent plus rien, la cloche arrive dans Nebula, et la page *Thèmes & Addons*
réinstalle de nouveau un thème.

### Corrigé

- **Ce qui colle en haut de l'écran se range sous les bandeaux du haut de page** — celui de la démonstration, de la
  maintenance et de l'aperçu des droits. Dès qu'on faisait défiler, la moitié de l'en-tête d'Extend, de Chronique, de
  Pulse et de Nebula passait sous le bandeau de la démonstration ; le bas du rail de Forge et de la barre latérale de
  l'administration sortait de l'écran ; au téléphone, le blason et le compte de Forge étaient cachés. Le gabarit
  principal empile désormais les bandeaux et publie leur hauteur ; les thèmes, l'administration et l'éditeur en direct
  s'y calent, et les notifications à l'écran s'affichent dessous.
- **Les colonnes collées des modules se calent sous l'en-tête du thème**, quel qu'il soit : le menu de l'espace membre
  (à 16 px du haut, il se glissait sous un en-tête collé de 60 à 70 px), le sommaire du wiki, le sommaire et l'encart
  d'un article. Une colonne plus haute que la place qu'il lui reste ne colle plus : son bas restait hors de l'écran
  jusqu'au bout de la page — le menu de l'espace membre d'Extend, sur un écran de portable.
- **Un lien vers une ancre s'arrête sous l'en-tête collé** — un message du forum, une section du wiki ou d'un article —
  au lieu de poser son titre dessous : chaque thème fixait ce décalage à la main, sans compter les bandeaux.
- **« Réinstaller par défaut » et « Supprimer » un thème aboutissent depuis la page « Thèmes & Addons »**, et l'ordre
  des langues et des authentificateurs s'enregistre : la confirmation était refusée, « jeton de sécurité invalide ».
- **Le bandeau de la démonstration se lit** : son texte, blanc sur vert (2,4:1 de contraste), passe en sombre.
- **Au téléphone, les boutons d'un en-tête de l'administration passent sous le titre** : sur la page des articles,
  « Catégories », « Séries » et « Nouvel article » recouvraient « 4 publiés », et le dernier sortait de l'écran.
- **Un site ouvert sous un autre nom que celui de sa configuration** (avec ou sans `www.`, par exemple) affiche les
  images écrites avec l'adresse configurée : la politique de sécurité du navigateur les bloquait, car elles venaient
  pour lui d'un autre site ; elles passent maintenant par le relais d'images du site.
- **Pour les contributeurs** : l'outil `check-mise-en-page` ne signale plus de faux chevauchements dans le contenu
  d'un bloc replié (`<details>`), que Chrome garde dans la page sans l'afficher — 48 fausses alertes sur la page
  *Journal des erreurs* du Monitoring —, ni dans les messages d'erreur que cette page affiche volontairement.

### Ajouté

- **La cloche des notifications dans Nebula**, comme dans les autres thèmes : l'espace membre l'annonce « en haut de
  chaque page », et Nebula n'en avait pas.
- **Pour les auteurs de thèmes** : l'en-tête collé d'un thème porte `data-nf-entete`, et tout ce qui colle en haut de
  l'écran se cale sur `--nf-haut` (et une colonne sur `--nf-entete`) ; un nouveau contrôle, `check-colles`, le vérifie
  — lancé sur la 1.2.45, il y relevait vingt règles CSS fautives (*Créer un thème*).

### Retiré

- **Des règles de style inutilisées dans la feuille de Nebula** (une barre, des boutons et un pied hérités d'un autre
  thème) : aucun gabarit de Nebula ne les employait.

## [1.2.45] — 2026-10-08

La cloche des notifications, revue dans tous les thèmes qui l'affichent, et la connexion à double authentification
d'un seul geste.

### Corrigé

- **Avec la double authentification, le code se demande aussitôt après le mot de passe** : la fenêtre de connexion
  laisse place à celle du code, sans recharger la page. Elle se fermait, et il fallait recliquer sur « Se connecter »
  pour voir apparaître le code ; depuis le formulaire de l'espace membre, la fenêtre du code s'ouvre au rechargement.
- **La pastille de la cloche se pose dans son coin**, dans tous les thèmes qui affichent la cloche : la feuille du
  module, chargée après celle du thème, la replaçait en ligne, et dans Extend et Forge elle tombait au milieu du
  bouton, sur la cloche.
- **La cloche reste au centre de son bouton** quand une pastille s'y pose : une icône qui n'était plus seule dans son
  lien prenait une marge à droite, et glissait de trois pixels.
- **« Notifications » et « Tout marquer comme lu » ne se touchent plus** en tête de la liste : elle garde sa largeur
  quand le thème rétrécit ses menus, et les deux textes gardent un écart, quitte à passer à la ligne.
- **Les dates s'affichent dans le format de la langue et le fuseau du visiteur** dans la liste de la cloche,
  l'historique des révisions et l'infobulle d'un signalement, qui montraient l'horodatage brut de la base.

## [1.2.44] — 2026-10-08

Un correctif de la 1.2.43.

### Corrigé

- **Le filtre des services tiers fonctionne sous PHP-FPM et sous Apache.** Il tourne à la fin de la requête, quand le
  dossier courant n'est plus celui du site : il ne trouvait plus la clé du relais des images, et la page partait sans
  être filtrée. Les images d'autres sites, que la politique de sécurité refuse désormais, ne s'affichaient pas (les
  avatars du widget Discord), et une vidéo intégrée se serait chargée sans son avis. Le défaut ne se voyait pas avec
  le serveur intégré de PHP, qui ne change pas de dossier en fin de requête.
- **Le journal des erreurs PHP garde aussi celles de la fin de la requête**, erreurs fatales comprises : son chemin
  était relatif, et se résolvait alors depuis la racine du disque (`/`).

## [1.2.43] — 2026-10-08

La confidentialité : plus aucun visiteur envoyé chez un tiers sans son accord.

### Ajouté

- **Un vrai choix sur les services d'autres sociétés.** Le bandeau de cookies ne réglait que Google Analytics, pendant
  que les vidéos, le widget Discord et les captchas se chargeaient quoi que le visiteur réponde. La fenêtre **« Gérer
  mes cookies »**, ouverte depuis le pied de chaque page, montre les cookies du site lui-même, puis chaque service, à
  accepter ou à refuser un par un. À la place d'une vidéo YouTube, Twitch, Vimeo ou Dailymotion, d'un morceau Spotify ou
  SoundCloud, ou du widget Discord, un avis dit qui recevrait l'adresse IP du visiteur, avec « Afficher » et « Toujours
  autoriser ». Le bandeau ne s'ouvre que si le site a vraiment quelque chose à demander d'emblée (la mesure d'audience,
  un captcha confié à un tiers) ; « Tout accepter » et « Tout refuser » y ont le même poids. Le choix est gardé six
  mois, et sa trace, sans adresse IP, treize mois.
- **Paramètres → Confidentialité** : les pages de mentions légales et de politique de confidentialité, choisies parmi
  les pages publiées. Le pied de chaque thème les relie, à côté de « Gérer mes cookies ».

### Modifié

- **Les polices sont servies par le site** : les thèmes et le réglage « Police du site » les prenaient chez Google
  Fonts, et le navigateur de chaque visiteur envoyait son adresse IP à Google, sur chaque page.
- **Les images d'autres sites sont servies par le site** — un avatar Discord, Steam ou Twitch, un GIF de la messagerie,
  une image collée dans un article, le forum ou une annonce, une bannière de publicité : le site les récupère lui-même
  et les garde sept jours, et le navigateur ne contacte plus leur hébergeur. Les tuiles de la carte des lieux aussi.
- **La politique de sécurité n'accepte plus que le site lui-même** pour les images et les échanges des scripts (Google
  Analytics excepté, s'il est réglé). Elle laisse en revanche s'afficher les lecteurs des services connus (YouTube,
  Twitch, Vimeo, Dailymotion, Spotify, SoundCloud, Discord), qu'elle bloquait : la vidéo d'un article ne s'affichait
  pas. Un script d'un autre site collé dans un widget « Code HTML » ou une publicité ne s'exécute plus : seuls passent
  ceux de Google Analytics et du captcha choisi.
- **Tant que le visiteur n'a pas accepté un captcha confié à un tiers, ALTCHA le remplace**, servi par le site :
  personne n'a à céder ses données à Google, à hCaptcha ou à Cloudflare pour écrire au site ou s'inscrire.
- **Plus de pixel de suivi dans la lettre d'information**, ni de taux d'ouverture : un traceur que l'inscription ne
  demandait pas (recommandation de la CNIL du 12 mars 2026 sur les pixels de suivi dans les courriels).
- **Des durées de conservation** : le journal d'audit un an, les compteurs anti-abus un jour, les inscriptions à la
  lettre jamais confirmées trente jours, sa file d'envoi quatre-vingt-dix jours ; pour un signalement traité depuis un
  an, l'adresse IP de son auteur et la copie du contenu s'en vont. Le cookie de connexion s'arrête à la fermeture du
  navigateur, sauf avec « Se souvenir de moi » (un an), qui n'est plus coché d'avance.
- **Plus de cartes Google dans les textes** : une carte Google Maps intégrée à un texte n'est plus acceptée. La carte du
  produit est celle du module « Carte des lieux », sur OpenStreetMap.
- Le lien « Propulsé par NeoFrag Reborn » des thèmes, le mot magique `{neofrag}` du copyright et les liens du pied de
  l'administration mènent au site du projet ; ils menaient au site du NeoFrag d'origine.

### Corrigé

- **Plus aucune adresse IP envoyée à neofr.ag** : le drapeau du pays posé devant les adresses des sessions est retiré ;
  pour l'obtenir, le navigateur de l'administrateur envoyait toutes ces adresses au site du NeoFrag d'origine.
- **La suppression d'un compte par l'administrateur efface vraiment** le profil, les comptes liés, l'historique des
  connexions et les notifications, comme celle que le membre demande lui-même ; elle ne faisait que fermer le compte.
- **L'archive « Mes données » se télécharge enfin** : trois de ses listes (sujets du forum, commentaires, messages des
  discussions) lisaient des colonnes qui n'existent pas, et l'archive échouait.
- Les infobulles de la liste des sessions et de la galerie s'affichent : elles portaient un attribut de Bootstrap 4, que
  Bootstrap 5 ignore, et restaient vides.
- L'adresse de l'avatar du widget Steam était écrite telle quelle dans la page ; elle est désormais échappée, comme le
  reste du widget.
- L'infobulle « Ouvrir la chaîne » et le bouton « Fermer » du lecteur du widget Twitch, restés en français, se
  traduisent.

## [1.2.42] — 2026-10-07

Un thème refait : **Extend**, le site comme le lanceur d'un jeu en ligne.

### Ajouté

- **Extend 2.0.0 « Lanceur »** — une mise en page neuve, choisie parmi trois maquettes. Une barre
  d'onglets en haut, en capitales serrées, l'onglet ouvert allumé d'un trait bleu ; sur l'accueil, une **grande
  vitrine** (le diaporama, son titre en grand et son bouton « Découvrir » ; sans diaporama, le nom du site sur l'image
  du thème), puis les prochains rendez-vous en cartes, les actualités en vignettes, les derniers résultats ; ailleurs,
  le titre de la page sur cette image. À droite, sur toutes les pages, un **panneau toujours ouvert** : qui est en
  ligne, avec les avatars, puis le salon de discussion (et, sur les pages du forum, ses chiffres). En bas, une **barre
  d'état** : la place des widgets « Serveur de jeu », « Serveur TeamSpeak 3 » et « Serveur Discord », la langue, puis
  « Propulsé par NeoFrag Reborn », le copyright et les liens légaux. Le forum en bibliothèque, l'espace membre en fiche
  de joueur (bannière, avatar cerclé, palier de la gamification dans la barre).
  Au téléphone, les onglets passent en bas de l'écran, avec « En ligne », qui ouvre le panneau en tiroir, et « Plus »
  au-delà de quatre rubriques. Bleu acier sur fond marine, nuit par défaut, le jour au choix ; titres Saira Condensed,
  texte Albert Sans. Le logo, l'image de la vitrine, le fond et les couleurs se règlent. D'après le thème de
  Chewbaka, dont il garde le nom et la licence.
- **« Qui est en ligne ? (liste) »**, un nouvel affichage du widget Membres : les trois nombres (administrateurs,
  membres, visiteurs), puis les présents eux-mêmes avec leur avatar et leur dernière activité.
- **« Un salon public : ses derniers messages »**, un nouvel affichage du widget Discussions : les quatre derniers
  messages d'un salon ouvert à tous les membres, et le lien pour y écrire. Un visiteur ne lit pas les messages : il est
  invité à se connecter, comme dans la messagerie elle-même ; le salon du staff ne s'affiche jamais.

### Corrigé

- **Les billets liés d'un article du blog ne débordent plus au téléphone** : la case d'un billet sans image prenait sa
  largeur de la hauteur de sa rangée, et dépassait de l'écran de quelques pixels (à 414 px, dans tous les thèmes).
- **Blockcraft, revu sur la démonstration publiée** : au téléphone, le bandeau des pages montre le paysage entier
  (recadré, il n'en montrait que des troncs), sans l'astre, sur lequel passait un long titre ; la démonstration règle
  une adresse de serveur d'exemple (`play.example.org`), pour qu'on voie le bouton « Copier l'adresse ».

## [1.2.41] — 2026-10-07

Un thème refait : **Blockcraft**, le site d'un serveur de jeu de blocs.

### Ajouté

- **Blockcraft 2.0.0 « Spawn + Inventaire »** — une mise en page neuve, choisie parmi quatre maquettes.
  En tête, un ciel au-dessus d'un paysage en blocs (collines, arbres, roche), le soleil et des nuages qui passent, la
  lune et les étoiles la nuit ; sur l'accueil, le nom du serveur en grand et **son adresse, à copier d'un
  clic** (réglage « Adresse du serveur »), et le Discord s'il est renseigné. La navigation est une **barre d'objets** :
  chaque rubrique dans sa case, avec l'objet qui la représente (le livre pour les nouvelles, la carte pour le forum…) ;
  au téléphone, elle se colle en bas de l'écran, à portée de pouce. Des blocs à coins carrés et ombres franches, le
  **forum en coffres** (un couvercle en planches par catégorie, chaque forum dans sa case, une case enchantée qui
  scintille quand il y a du neuf), l'**espace membre en écran du personnage**, un pied en roche. Titres Jersey 10,
  texte Rubik, chiffres VT323 ; jour « prairie », nuit « ciel étoilé ». Tous les pixels sont dessinés pour le thème.
  De jour, les textes du ciel sont sombres : blancs, ils ne se seraient lus que par leur ombre.

### Corrigé

- **La colonne de Pulse, de Chronique et de Blockcraft ne déforme plus les widgets qu'on y pose** : sa mise en page
  atteignait aussi l'intérieur des widgets, et « Qui est en ligne » y écartait ses nombres de leurs libellés.
- **L'onglet choisi du profil d'un membre se lit dans Pulse, Chronique et Granite** : ces thèmes en changeaient le
  texte sans en changer le fond, que le module peint de la couleur d'accent — texte sombre sur l'accent.
- **L'icône d'un groupe ne touche plus son nom** (par exemple « Équipe principale », dans le profil et l'espace
  membre) : le socle commun des thèmes ne laissait aucun espace entre l'icône et le nom — dans les six thèmes qui le
  chargent.
- **Les commentaires d'un événement gardent leur icône** dans Pulse, Chronique et Granite : ces thèmes ôtaient l'icône de
  tout lien posé au pied d'une carte, et le lien ne disait plus que « 0 → ». Seul le pied des widgets (« Voir le
  calendrier → ») perd désormais son icône.
- **La pastille choisie de la messagerie se lit** (« Toutes », « Archives »…) dans Pulse et Chronique : le socle commun
  donnait à son texte la couleur des liens et à son icône celle de l'accent, sur un fond d'accent (1,1:1).
- **Granite** : les boutons posés au pied d'une carte (« Poster une image », « Voir ma candidature », la pagination des
  sessions) prenaient le rouge des liens sur l'encre (2:1) ; « Lever cette sanction » n'avait que 3,7:1 ; la lettrine
  de la une descendait, au téléphone, sur la ligne « Par … le … » qui la suit ; les petits boutons font 24 px de haut.
- **Pulse, de nuit** : le compteur du menu de l'espace membre se lit : blanc sur la brique de nuit, plus claire, il
  n'avait que 3,9:1 ; il garde la brique du jour.
- **Le menu de l'espace membre, au téléphone, se cale sur le début d'un onglet** : centré au pixel près, il laissait
  l'onglet précédent amputé du début de son nom (« …fications »).
- **Allumer le « Mode débogage » depuis le Monitoring ne fait plus planter sa page** : la réponse plantait dès qu'une
  notification attendait (« Cannot use object of type stdClass as array »).
- **Les guides comptent les thèmes du paquet** : « Concepts » en annonçait quatre (Chronique et Pulse manquaient),
  « Créer un thème » nommait quatre thèmes sur le socle commun au lieu de six.

### Modifié

- **`check-mise-en-page` ne crie plus à tort** : un texte de la bannière des cookies ou d'une barre collée en bas de
  l'écran passe au-dessus d'un graphique à dessein (seul un même calque fait conflit) ; la partie cachée d'un texte —
  le code d'un bloc qui défile, une description tronquée — ne « chevauche » plus la colonne voisine ; l'onglet d'une
  bande qui défile n'est plus « perdu au bord gauche ». Des faux défauts en moins, et les vrais sortent toujours
  (éprouvé sur une page piégée, avec l'ancienne sonde et la nouvelle).

## [1.2.40] — 2026-10-07

Le référencement revu après les alertes de la Search Console du site officiel : Google n'indexait pas les sujets du
forum ni les pages du wiki, et se voyait proposer des pages vides. Et le message de bienvenue, qui ne partait plus.

### Corrigé

- **Les contenus sans langue ne se présentent plus comme des doublons** : un sujet du forum, une page du wiki, un
  ticket, un événement, une annonce, une recette, un sondage, une offre de recrutement, une distinction, une campagne de
  dons — rédigés une fois — répondaient sous chacune des langues du site, seuls les menus traduits, et chaque adresse se
  disait canonique. Google en retenait une autre et ne les indexait pas (« Page en double : Google n'a pas choisi la
  même URL canonique que l'utilisateur » : sur le site officiel, en six langues, 59 contenus annoncés six fois, 354 des
  580 adresses du plan du site).
  Leur canonique est désormais dans la langue première du site, la seule annoncée en `hreflang` et au plan du site. De
  même pour les pages faites de ces contenus : la FAQ, le glossaire, les citations, les liens, les téléchargements, la
  boutique et la liste des événements.
- **Les pages vides ne sont plus proposées aux moteurs** : la webradio sans flux ni émission, le livre d'or sans
  message et la page des dons sans campagne figuraient au plan du site, dans chaque langue — des « soft 404 » pour
  Google. Elles n'y sont plus tant qu'elles sont vides, et se déclarent `noindex`.
- **`/fr/forum/` redirige pour de bon vers `/fr/forum`** : la redirection d'une adresse finie par une barre oblique,
  ou qui en doublait une, était temporaire (302) — et la première perdait ce qui suivait le `?` ; elle est permanente
  (301), et garde la fin de l'adresse.
- **Le message de bienvenue part de nouveau** : le module Membres ne trouvait pas la messagerie, et le nouvel inscrit ne
  recevait rien. Un module qui en chargeait un autre (`$this->module(…)`) obtenait toujours « rien » — le même défaut
  privait les webhooks d'un commentaire du titre et de l'adresse du contenu commenté.
- **Les sauvegardes de mise à jour ne s'entassent plus** : le site garde toujours les cinq plus récentes et retire les
  autres passé trente jours, mais des mises à jour rapprochées les laissaient toutes passer — le site officiel en
  portait quarante, 663 Mo. Jamais plus de dix ne restent désormais, quel que soit leur âge.

### Modifié

- **`check-seo` compare les langues entre elles** : il échantillonne le même chemin dans chaque plan du site et signale
  un même texte servi sous plusieurs langues, chacune canonique. Il déclarait le site officiel juste ; il y trouve
  désormais ces doublons. Un module peut déclarer ses contenus sans langue : une entrée de son plan du site marquée
  `'sans_langue' => TRUE` ne figure qu'au plan de la langue première, et `nf_seo_sans_langue()` place la canonique de
  la page (guide « Créer un module »).

## [1.2.39] — 2026-10-06

Pulse revu sur la démonstration publiée : le forum au téléphone ne colle plus ses boutons, dans tous les
thèmes ; et un billet du blog qui débordait encore de l'écran au téléphone.

### Corrigé

- **Le forum au téléphone ne colle plus ses boutons** : la barre d'un sujet (Retour, Répondre et les outils de
  modération) et celle d'un forum gardent un écart entre leurs boutons et entre leurs rangs ; les boutons d'un message
  passent au-dessus de sa date, qui ne se coupe plus sur trois lignes ; le titre d'un sujet ne touche plus le bouton
  « Suivre ». Dans tous les thèmes.
- **Un billet du blog ne déborde plus au téléphone** : un bout de code trop long (un chemin de fichier, par exemple) se
  coupe, et un tableau qui reste trop large défile dans sa colonne (le billet de la 1.2.0 dépassait de l'écran).

## [1.2.38] — 2026-10-06

Un thème neuf : **Pulse**, la maison commune des associations, des clubs et des communautés, avec trois nouveautés qui
servent à tous les thèmes — le site en chiffres, le prochain rendez-vous et les dernières photos.

### Ajouté

- **Le thème Pulse.** Une barre claire qui reste en haut, avec les rubriques en pastilles, le compte et l'appel
  « Adhérer » toujours en vue (un bouton « Menu » au téléphone) ; l'accueil est une mosaïque de dalles — le nom du site
  et sa devise sur l'image d'accueil, le prochain rendez-vous, le site en chiffres, les actualités, l'agenda, le sondage,
  les discussions, les documents, les photos, les partenaires —, que l'éditeur en direct recompose dalle par dalle
  (claire, soleil ou ardoise). Le forum se lit en cartes, avec ses statistiques et son activité à côté ; le pied, sombre,
  porte le plan du site et les réseaux. Jour clair, nuit « ardoise » au choix du visiteur ; titres Bricolage Grotesque,
  texte Manrope. Réglages : couleurs, image d'accueil, logo, et l'adresse du bouton « Adhérer » (une page d'adhésion, un
  formulaire d'une autre plateforme, ou l'inscription au site).
- **Le widget « Le site en chiffres »** : jusqu'à quatre nombres, choisis parmi les membres, les discussions, les
  messages du forum, les actualités, les rendez-vous à venir et les photos — en ne comptant que ce que le visiteur a le
  droit de voir (les forums réservés, les albums d'un groupe restent hors du compte). Les nombres défilent jusqu'à leur
  valeur quand la dalle paraît dans Pulse, sauf si le visiteur a demandé moins d'animations.
- **Le calendrier affiche aussi « Le prochain rendez-vous »** : sa date en grand, son titre, dans combien de jours, à
  quelle heure (ou « toute la journée ») et où, le début de sa description, et un bouton pour l'ouvrir.
- **La galerie affiche aussi « Les dernières photos »**, en grille — des seuls albums que le visiteur peut voir.

### Corrigé

- **Le style « Titre coloré » de Chronique et Granite** : le titre du panneau était de la couleur de son fond (sarcelle
  sur sarcelle, rouge sur rouge), donc invisible. Il est maintenant en couleur, sur le papier, comme ces thèmes le
  prévoyaient — et l'aperçu du style, dans l'éditeur en direct, le montre ainsi.
- **Le style « Widget coloré » de Granite** : le texte, les libellés d'un formulaire et le bouton d'un widget posé dans
  ce style pouvaient devenir blancs sur le papier (ou encre sur encre, la nuit). Ils gardent les couleurs du
  journal. Le contrôle de contraste sait maintenant mesurer un widget dans chaque thème et chaque style de panneau.
- **Le fil d'Ariane au-dessus d'une actualité** : chaque thème le prévoyait, il n'y paraissait jamais — la règle qui
  le place visait une adresse qui n'existe pas. Il s'affiche maintenant sur les articles et la liste des actualités,
  dans tous les thèmes.
- **Deux encarts côte à côte restent alignés** : sous une actualité, « Autres actualités de l'auteur » descendait de 14
  à 22 px à côté de « À propos de l'auteur » (de même dans la fiche d'un événement et la messagerie).
- **Chronique : un article garde ses marges** dans sa carte ; le titre et le texte touchaient la bordure.
- **Des textes posés sur un dégradé se lisent** : le bouton « Rejoindre le serveur » du widget Discord, le sigle du
  blason de Forge, le titre et la devise de la bannière de Blockcraft, le jour. Le contrôle de contraste mesure
  maintenant le texte sur un dégradé, et en membre connecté (l'espace membre compris).
- **Un billet du Blog à un seul intertitre ne s'affiche plus écrasé** : sans sommaire, la page lui gardait sa colonne,
  le texte s'y tassait sur 200 px et l'encart (auteur, partage) prenait sa place — les notes des versions 1.2.32, 1.2.33
  et 1.2.37 sur le site officiel. Merci à Blober de l'avoir signalé.
- **`admin.php` et `ajax.php` répondent « page introuvable »** au lieu d'une erreur du serveur (et de deux lignes au
  journal à chaque robot qui sonde ces adresses).
- **La frise de la saison** : « Dans 5 jours » ne se coupe plus en deux lignes au téléphone.

## [1.2.37] — 2026-10-06

Chronique revu sur la démonstration telle qu'on la voit — en visiteur et connecté, de jour, de nuit, au téléphone —, et une démonstration qui vit au présent.

### Corrigé

- **L'espace membre de Chronique.** Dans son panneau, « Mot de passe oublié ? » se posait sur « Se connecter »
  dès que la colonne les mettait l'un sous l'autre ; connecté, les icônes du menu restaient gris foncé sur l'ardoise et
  « Se déconnecter » tombait dans un rectangle blanc (noir la nuit). Ce sont maintenant des liens blancs, un seul bouton
  plein, et un écart dans les deux sens — avec « Créer un compte » aussi, quand les inscriptions sont ouvertes.
- **Chronique au téléphone garde la connexion dans l'en-tête**, en icône : le mot « Connexion » disparaissait, et il
  fallait descendre au bas de la page pour se connecter.
- **Les boutons du panneau de connexion gardent leur écart quand ils passent à la ligne**, dans les cinq thèmes qui
  partagent le socle commun (Blockcraft, Chronique, Extend, Forge, Granite).
- **Extend : les icônes des champs du panneau coloré se voient** (le pseudo et le mot de passe) : elles étaient blanches
  sur une case claire.
- **La liste des membres, de nuit** : un réseau social qu'un membre n'a pas renseigné devenait une pastille grise collée à
  la suivante. C'est une icône grisée, sans fond, et les boutons des réseaux renseignés ont un vrai écart — sur une ligne
  dans une carte étroite.
- **La démonstration vit au présent.** Remise à zéro tous les quarts d'heure, elle remontrait le contenu du jour où son
  instantané a été écrit : des « prochains » rendez-vous et matchs passés depuis des semaines, un agenda vide, une frise de
  la saison qui se serait vidée d'elle-même. Chaque remise à zéro fait maintenant avancer les dates du contenu du temps
  écoulé (les écarts entre elles ne changent pas) ; les membres gardent les leurs.

## [1.2.36] — 2026-10-06

Un thème neuf : **Chronique**, le carnet de la saison des associations et des clubs, avec deux nouveautés qui servent à
tous les thèmes — la frise de la saison et la semaine du calendrier.

### Ajouté

- **Le thème Chronique.** Le site comme le carnet de la saison : un en-tête discret qui reste en haut, avec un fin trait
  qui suit la lecture, un bouton « Sommaire » qui ouvre tout le site en plein écran (chaque rubrique numérotée) et
  l'appel « Adhérer » pour le visiteur ; l'accueil s'ouvre sur une grande phrase — la saison, le nom du site, sa devise —
  et la semaine en cours ; puis la frise raconte la saison mois par mois, à côté d'une colonne qui reste en place
  (l'espace membre, les documents, les partenaires). Le forum se lit en chapitres numérotés, l'espace membre comme un
  carnet, et le pied ferme le livre. Jour « papier », nuit « à la lampe » ; titres Fraunces, texte Work Sans, dates IBM
  Plex Mono. Réglages : couleurs, image d'ouverture, logo, et l'adresse du bouton « Adhérer » (une page d'adhésion, un
  formulaire d'une autre plateforme, ou l'inscription au site).
- **Le widget « Frise de la saison »** : ce que le site vit, mois par mois, sur une ligne de temps — les rendez-vous à
  venir du calendrier (leur point bat), les actualités, les discussions du forum et les albums photo, chacun si son
  module est installé, et seulement ce que le visiteur a le droit de lire (les forums réservés, les albums d'un groupe
  restent cachés). Le nombre d'entrées et les mois passés se règlent.
- **Le calendrier affiche aussi « La semaine »** : les sept jours de la semaine en cours, ceux qui portent un événement
  marqués, aujourd'hui entouré, et le prochain rendez-vous — dans le fuseau horaire de celui qui regarde.

### Corrigé

- **En mode jour, les composants de Bootstrap prennent les couleurs du thème** : un accordéon de la FAQ, une case à
  cocher, un tableau ou un menu déroulant restaient blancs, et leur texte gris foncé, sur le papier de Granite. Vérifié
  élément par élément sur Forge, Granite, Blockcraft et Extend : seules leurs couleurs changent, et le mode nuit reste
  tel quel.
- **« Mode jour » et « Mode nuit » se traduisent** : l'infobulle et le nom, lu par les lecteurs d'écran, du bouton de
  bascule restaient en français sur un site dans une autre langue (Forge, Granite, Blockcraft, Extend). Le contrôle des
  textes écrits en dur connaît maintenant les mots « jour » et « nuit ».
- **La page de personnalisation d'un thème ne journalise plus d'avertissement** quand ses réglages n'existent pas encore
  (un thème enregistré sans que son installation ait créé ses réglages) : la position d'une image prend sa valeur par
  défaut.

## [1.2.35] — 2026-10-06

Le deuxième thème refait de fond en comble : **Granite 2.0.0 « Gazette »**, le journal des associations et des clubs.

### Modifié

- **Granite 2.0.0 « Gazette » : une nouvelle mise en page, pas un habillage.** Le site devient le journal du club : la date
  du jour et le compte sur une ligne en tête, le nom du site imprimé en très grand (le logo au-dessus, s'il y en a un), un
  double filet, puis les rubriques en petites capitales entre deux filets. Sur l'accueil, une ligne « En bref » fait
  défiler les derniers sujets du forum ; elle s'arrête au survol, au clavier et sur son bouton pause, et reste immobile si
  le visiteur a demandé moins d'animations. La une ouvre sur l'actualité principale en grand, avec sa capitale ornée, puis
  les suivantes en colonnes séparées de filets ; la colonne de droite porte l'agenda, le sondage et qui est en ligne. Les
  blocs deviennent des encadrés sans boîte, titrés en petites capitales sur un filet ; l'article se lit en corps de
  journal ; le forum prend l'allure d'un courrier des lecteurs ; l'espace membre, celle d'une carte de membre frappée de
  son premier groupe en tampon ; le pied devient un « ours » (les partenaires, puis qui publie). Une fine barre de lecture
  suit la page. Le jour est un papier, la nuit une page « à l'encre », au choix du visiteur ; titres en Playfair Display,
  texte en Source Serif. Au téléphone, les rubriques défilent de côté et tout passe en une colonne.
- **Les zones de Granite se nomment d'après leur place** : « Rubriques », « En bref », « Contenu », « Après le contenu »,
  « Pied de page ».
- **Les réglages de Granite** : l'image de bannière devient l'image du titre, et la couleur du titre pose, si on la
  change, un bandeau derrière le nom, comme la manchette d'un quotidien ; le logo sert enfin (au-dessus du nom) ; un
  réglage « Lettrines » remplace celui de la barre du haut fixe, qui n'a plus d'objet.

### Corrigé

- **Plus de bleu Bootstrap au milieu des couleurs d'un thème.** La barre d'un sondage, la case cochée et le bouton radio
  choisi (jusque dans l'administration), la page active, l'entrée pressée d'un menu, la flèche d'un accordéon, la lueur
  du focus clavier et les états d'un bouton principal gardaient le bleu par défaut de Bootstrap. Ils prennent la couleur
  d'accent du thème, dans tous les thèmes ; un nouveau contrôle, `check-bleu-bootstrap`, cherche ce bleu dans les pages
  servies, au repos comme au focus, de jour comme de nuit.
- **Les rubriques posées par Forge et Granite se traduisent** : « Matchs », « Nous rejoindre », « À la une », « Agenda »
  et « Documents » restaient en français sur un site dans une autre langue. Le contrôle des traductions vérifie
  désormais chaque intitulé de menu qu'un thème pose.
- **L'éditeur en direct montre le site avec ses propres polices** : il imposait la sienne au contenu des zones qu'il
  encadre.
- **Le contrôle du contraste mesure aussi le texte posé sur un grain** (un papier, une trame) : il le prenait pour une
  image et renonçait à le mesurer.

## [1.2.34] — 2026-10-06

Le premier thème refait de fond en comble : **Forge 2.0.0 « Coulée »**, pour les clans compétitifs.

### Modifié

- **Forge 2.0.0 « Coulée » : une nouvelle mise en page, pas un habillage.** La navigation quitte le haut de la page pour
  un rail d'acier sur le côté, avec le logo du site en tête et le compte en bas (avatar, notifications, mode jour, menu de
  l'espace membre) ; au téléphone, le logo et le compte passent en barre du haut, la navigation en barre d'onglets en bas
  de l'écran, et au-delà de cinq entrées les dernières se rangent dans « Plus ». L'accueil s'ouvre sur un foyer de lave où
  montent des braises : le diaporama, au titre coulé dans le métal, et la plaque des derniers résultats. Dessous, un
  tableau de bord : les actualités (la première à la une) et, à côté, les prochains matchs, le palmarès et qui est en
  ligne. Les blocs sont des plaques aux coins coupés dont le filet rougeoie au survol ; le forum range chaque forum dans
  un tiroir d'acier avec une jauge de chaleur pour son activité ; l'espace membre et le profil public prennent la plaque
  d'identité et les onglets en biseau ; le pied est riveté et porte les partenaires. Le mode jour éclaircit le contenu,
  le rail et le foyer restent d'acier et de feu. Tout mouvement s'arrête si le visiteur a demandé moins d'animations.
  Les blocs des matchs, du palmarès et des partenaires ne se posent que si leurs modules sont installés.
- **Les zones de Forge se nomment d'après leur place** : « Rail de navigation », « Haut de page », « Contenu », « Après
  le contenu », « Pied de page ».
- **Les réglages de Forge** : le logo sert enfin (le blason du rail ; sans logo, les initiales du site) ; l'image de
  bannière devient l'image du foyer ; un réglage « Braises du foyer » (aucune, douces, vives) remplace celui de la barre
  du haut fixe, qui n'a plus d'objet.
- **Le palmarès** (widget) affiche la place du podium (« 1er », « 2e », « 3e ») et le nom de la compétition sur toute la
  largeur disponible, avec dessous le lieu et la plateforme ; la ligne commençait par la plateforme et coupait le nom à
  vingt caractères.
- **Les images du diaporama de la démo ne portent plus leur titre** : le diaporama l'écrit déjà par-dessus, et il
  s'affichait en double, en travers de la légende au téléphone.

### Corrigé

- **Un réglage de thème se voit tout de suite** (couleur d'accent, images de fond et de bannière…) : l'adresse de la feuille de
  style ne suivait plus que la date du fichier, et le navigateur gardait l'ancienne feuille après un changement.
- **La page de personnalisation des thèmes** Forge, Granite, Blockcraft et Extend a pour titre le nom du thème, au lieu
  de « Dashboard », un mot anglais affiché sur tous ses onglets.
- **En anglais, le palmarès n'écrit plus « 2th » ni « 3th »** : la place du podium a ses propres mots, les autres places
  s'écrivent « #4 ».
- **Réinstaller un thème n'écrit plus d'avertissement au journal** en mode débogage (un avertissement « Deprecated » de
  PHP 8.2).

## [1.2.33] — 2026-10-06

Une version de correction : la CI publique du dépôt `neofrag`, tombée à la 1.2.32 pour une raison d'outillage, est
réparée. Rien ne change pour les sites.

### Corrigé

- **La vérification automatique (CI) du dépôt `neofrag` passe de nouveau.** Depuis la 1.2.32, l'étape qui cherche la
  version correspondante du dépôt `extensions` pouvait échouer : elle cessait de lire la liste des versions dès qu'elle
  avait trouvé la bonne, et `git`, interrompu en pleine écriture, tombait en erreur maintenant que la liste est longue.
  Elle lit désormais la liste entière ; même correction dans la CI d'`extensions`.
- **La vérification des liens des documents ne lit plus ceux des bibliothèques embarquées** (la licence de TinyMCE) :
  ils ne se corrigent pas chez nous, et gnu.org fermait la connexion à leur vieille adresse.

## [1.2.32] — 2026-10-06

Le chantier des thèmes commence : Forge, Granite, Blockcraft et Extend partagent désormais un socle commun. Rien ne
change à l'écran ; c'est la base sur laquelle chacun recevra son identité.

### Modifié

- **Les thèmes Forge, Granite, Blockcraft et Extend partagent un socle commun** (`css/nf-socle-themes.css`) : 153
  des règles qu'ils avaient en commun n'y sont plus écrites qu'une fois, dans le cœur ; une cinquantaine d'autres
  restent dans chaque thème, parce que leur place dans la feuille compte. Rien ne change à l'écran — vérifié élément
  par élément et pixel par pixel, sur les quatre thèmes, de jour et de nuit, à l'ordinateur et au téléphone. C'est la
  première étape de leur refonte : chacun aura bientôt son allure propre. Les quatre thèmes passent en 1.1.0 et
  demandent le cœur 1.2.32.

## [1.2.31] — 2026-10-06

L'espace membre, dernières étapes : la sécurité du compte — les appareils connectés se déconnectent, les sanctions
d'avatar et de signature s'appliquent enfin — et les notifications, rangées dans l'espace membre avec le choix de ce
que chacun reçoit. Le chantier de l'espace membre est terminé.

### Ajouté

- **« Sécurité » montre les appareils où le compte est ouvert, et permet de les déconnecter** : chaque session
  avec son navigateur, son système, son adresse IP et sa dernière activité, « Cet appareil » pour celle d'où l'on
  regarde ; « Déconnecter » pour un autre, ou « Déconnecter tous les autres appareils ». « Sécurité » ne montrait
  jusqu'ici que l'historique des connexions, et une session ouverte ailleurs ne se fermait pas. Sur un site de
  démonstration, dont le compte est partagé, aucune session ne peut être fermée.
- **Chaque membre choisit les notifications qu'il reçoit**, dans « Préférences de notifications » : une case par
  sorte de notification (un message privé, une réponse dans un sujet suivi, une mention, un commentaire, une
  réaction, un rappel d'événement…) pour la recevoir sur le site, et une case « Par e-mail » pour celles que le site
  envoie aussi par e-mail (messages privés, réponses et mentions du forum). Sans réglage, il reçoit tout, comme
  avant. Pour les auteurs de modules : un module déclare les siennes par `types_de_notification()` (voir le guide
  « Créer un module »).
- **« Mes notifications » rejoint l'espace membre**, à l'entrée « Notifications » de son menu, vingt par page : la
  page vivait à part, à l'adresse `/notifications`, et n'en montrait que les cinquante dernières (désormais jusqu'aux
  cinq cents dernières). L'ancienne adresse mène à la nouvelle.

### Corrigé

- **Les sanctions d'avatar et de signature s'appliquent.** Un modérateur pouvait les prononcer, mais le profil ne
  les consultait pas : le membre sanctionné changeait son avatar et sa signature comme avant. Il voit maintenant
  ce que la sanction lui interdit, son motif et jusqu'à quand.
- **Huit modèles de données du cœur lisent toujours leur propre table** (sessions, historique des connexions,
  addons, traductions, journaux…) : chargés depuis un module, ils cherchaient une table qui n'existe pas. Rien ne
  le montrait encore à l'écran ; la nouvelle liste des appareils l'aurait montré. C'est le même défaut que celui du
  favicon et des fichiers supprimés, corrigé en 1.2.29.
- **Une mention coupée au forum ne fait plus perdre la réponse.** Un membre mentionné dans un sujet qu'il suit ne
  recevait rien quand la mention lui était coupée — l'e-mail de mention coupé par le site (« Notifier les mentions
  @user par email ») ou la mention coupée dans ses préférences. Il reçoit désormais la réponse. Quand la mention lui
  parvient, elle tient lieu de réponse, pour ne pas l'avertir deux fois.

## [1.2.30] — 2026-10-05

L'espace membre, deuxième étape : le profil public, et ce que chacun choisit d'y montrer. Et une correction qui
compte : un membre connecté lit et écrit de nouveau dans le forum.

### Ajouté

- **Le profil public d'un membre devient une vraie page.** Sa couverture en bannière (téléversée, elle ne
  s'affichait nulle part), son avatar qui la chevauche, son pseudo, son rang, ses groupes et sa présence ; pour un
  membre connecté, « Contacter » (si la messagerie est installée) et le drapeau « Signaler ce contenu », ou
  « Modifier mon profil » sur le sien ; puis des onglets, chacun à son adresse : À propos (citation, identité, liens,
  champs publics du site, et ses chiffres), Activité, et ceux que les modules installés apportent — Forum, Blog,
  Équipes, Petites annonces. Un module sans rien à montrer de ce membre n'ajoute pas d'onglet vide.
- **Chaque membre choisit ce que son profil montre**, dans « Confidentialité et données » : ses points, son
  karma et ses jours de VIP lui sont réservés tant qu'il ne les montre pas (ils étaient visibles de tous,
  visiteurs compris) ; son âge et sa présence en ligne restent montrés tant qu'il ne les cache pas. Son rang reste
  public. Le choix vaut partout : la fiche qui s'ouvre au survol d'un pseudo, la pastille de l'avatar, l'effectif
  des équipes, les widgets « Qui est en ligne ? » et « Activité du forum », la recherche de membres, les
  anniversaires de l'horloge. Sur son propre profil, le membre voit tout, un cadenas sur ce qu'il est seul à voir.

### Corrigé

- **Un membre connecté lit et écrit de nouveau dans le forum**, et voit les galeries, les pages, les types
  d'événements et les dossiers ouverts aux visiteurs. Depuis la refonte des droits, le rôle « Membre » n'avait
  que ce qu'on lui donnait expressément : un membre voyait « Aucun forum » là où un visiteur lisait tout, et
  n'écrivait nulle part — seuls les administrateurs, qui passent outre les droits, ne voyaient rien d'anormal. Les
  règles d'origine reviennent : ce qu'un visiteur peut, un membre le peut ; ce qui n'est refusé qu'aux visiteurs
  reste permis aux membres. Une migration les rétablit sur chaque site, sans toucher une règle déjà posée pour les
  membres.
- **Les rangs de réputation (Novice, Bronze, Argent…) se traduisent** : ils s'affichaient en français dans
  toutes les langues. Et la bulle d'un rang ne dit plus le score de karma.
- **Sur un site de démonstration, le widget « Activité du forum » ne montre plus le compte administrateur créé à
  l'installation** parmi les membres en ligne ; « Qui est en ligne ? » l'écartait déjà.
- **Un site web saisi sans « https:// » dans un profil mène bien à ce site** : il devenait un lien vers une page du
  site lui-même.
- **Une mise à jour n'écrase plus le catalogue du site qui sert le marketplace.** Le paquet de mise à jour emportait
  un vieux catalogue, fait à la 1.2.22 : posé sur ce site, il ne correspondait plus aux archives, et chaque
  installation d'un addon était refusée jusqu'à ce que le catalogue soit refait. Un site ordinaire lit le catalogue
  en ligne et n'en était pas gêné.
- **Dans « Mon espace », le groupe du membre s'aligne à gauche sous son pseudo** : il était centré au milieu de
  l'en-tête.

## [1.2.29] — 2026-10-05

L'espace membre, première étape de sa refonte : un seul cadre, un seul menu, des réglages rangés là où on les
cherche. Et le marketplace ne propose plus à un site un addon fait pour une version plus récente de NeoFrag.

### Ajouté

- **Un nouveau mot de passe compte au moins 10 caractères.** Il est refusé s'il reprend le pseudo, s'il n'emploie
  qu'un ou deux caractères différents (« aaaaaaaaaa », « ababababab ») ou s'il figure parmi les plus courants
  (« azertyuiop », « 1234567890 »…). La règle vaut à l'inscription, au changement et à la réinitialisation (à la
  réinitialisation, le pseudo n'est pas comparé). Les mots de passe existants restent valables.

### Modifié

- **L'espace membre a un seul cadre et un seul menu.** Il changeait de forme d'une page à l'autre — un menu à
  gauche sous la carte du profil, une barre « Menu » repliée en haut, rien du tout — et une même page portait
  trois noms. Les pages du compte ont désormais le même menu, au même endroit : une colonne à gauche sur
  ordinateur, une bande d'onglets qui défile au téléphone, la page courante marquée. Le menu mène aussi aux pages
  des modules : la messagerie et les notifications avec leurs non-lus, les abonnements du forum (qu'aucun lien
  n'atteignait), la modération. La barre du haut des thèmes Blockcraft, Extend, Forge et Granite et le widget
  « Espace membre » reprennent ce menu, avec les mêmes mots.
- **Les réglages sont rangés là où on les cherche.** « Mon compte » réunit l'identifiant, l'adresse, le mot de
  passe, la langue (qui ne se choisissait que par le sélecteur du site) et le fuseau horaire (qui était au
  milieu du profil public) ; « Sécurité » garde la double authentification et l'historique des connexions ;
  une page « Confidentialité et données » accueille l'export de ses données et la suppression du compte,
  rangés jusqu'ici sous « Sécurité (2FA) ».
- **« Mon espace » s'ouvre sur un en-tête compact** — avatar, pseudo, groupes, « Voir mon profil » et
  « Modifier mon profil » — au lieu de la grande carte du profil, qui repoussait le menu d'un écran entier au
  téléphone.

### Corrigé

- **Le marketplace ne propose plus à un site un addon fait pour une version plus récente de NeoFrag.** Seule
  la mise à jour d'un addon vérifiait la version de NeoFrag qu'il demande, pas son installation : un site resté en
  arrière pouvait installer un addon qui appelait des fonctions absentes de sa version, et tomber en erreur. Le
  marketplace officiel envoie désormais à chaque site le catalogue fait pour sa version (un site plus ancien que
  tous les catalogues gardés reçoit le plus ancien). La fenêtre du marketplace écarte en plus un addon trop récent
  et dit de mettre le site à jour d'abord. Le bouton « Mises à jour » annonce une nouvelle version de NeoFrag
  d'après la vérification des mises à jour du Monitoring, et plus seulement d'après le catalogue.
- **Le favicon choisi par l'administrateur sert aussi d'icône au site sur un téléphone, et un fichier supprimé
  quitte vraiment le disque.** Une erreur interne empêchait le site de retrouver ces fichiers : le favicon manquait
  quand on ajoute le site à l'écran d'accueil d'un téléphone (avec un avertissement au journal à chaque fois), et le
  fichier d'une pièce jointe supprimée du forum ou de la messagerie restait sur le disque et dans la base, comme
  l'avatar et la couverture d'un membre qui efface son compte.

## [1.2.28] — 2026-10-05

Une version de correction : l'espace membre d'un compte inscrit par Discord, GitHub ou Google, l'export et la
suppression des données personnelles, et le site de démonstration.

### Corrigé

- **Un membre inscrit par Discord, GitHub ou Google n'est plus enfermé dans son compte.** Sans mot de passe,
  il ne pouvait ni changer d'identifiant ou d'adresse, ni se créer un mot de passe, ni couper sa double
  authentification, ni supprimer son compte : chaque formulaire demandait le mot de passe actuel. Il
  confirme désormais son identité en repassant par le service relié ; la confirmation vaut dix minutes, et
  se connecter par ce service en est une.
- **L'export « Mes données » fonctionne, et il contient plus.** Il tombait en erreur : le membre téléchargeait
  une page d'erreur au lieu de ses données. L'archive contient aussi, désormais, le profil (nom, date de
  naissance, lieu, signature, liens), les comptes liés, l'historique des connexions, les notifications et ce que
  les modules du site gardent à son nom, en clair et sans aucun secret, pas même l'identifiant de ses sessions.
  Les sujets du forum, les commentaires et les messages des discussions n'y sont entrés qu'avec la 1.2.43.
- **Supprimer son compte efface ce que la page promettait.** Le pseudo est anonymisé sur les messages, le
  profil vidé ; les comptes liés, l'historique des connexions et les notifications sont effacés, et un
  compte Discord, GitHub ou Google lié redevient libre pour une nouvelle inscription. Le message « Ton compte
  a été supprimé » s'affiche enfin.
- **Sur un site de démonstration, le compte partagé ne se modifie plus** : un visiteur pouvait changer son mot de
  passe, activer sa double authentification ou le supprimer, et le fermer aux autres jusqu'à la remise à
  zéro.
- **« Connexion » se traduit au sens « se connecter »** (Log in, Anmelden, Iniciar sesión, Accedi, Entrar) :
  les cinq autres langues disaient « liaison réseau ». Le bouton de l'administration Discord qui relie le
  bot devient « Connecter le bot ».

## [1.2.27] — 2026-10-05

Le bot Discord passe en **version 0.2.4** : les images passent entre le forum et Discord dans les deux sens
(voir son propre journal des versions). La 0.2.3 continue de fonctionner.

### Ajouté

- **Une image envoyée sur Discord s'affiche dans le forum** (avec le bot Discord 0.2.4) : le bot la garde
  sur le site, contrôlée comme une image collée dans l'éditeur, au lieu d'un simple lien vers Discord.
  L'API gagne pour cela l'adresse `POST /api/v1/forum/images`.
- **La validation de l'inscription par e-mail** (*Paramètres → Gestion des inscriptions*, encart
  « Validation par e-mail », éteinte par défaut) : le nouveau membre reçoit un lien, valable deux jours. Il ne peut
  pas se connecter avant de l'avoir ouvert ; s'il essaie, un nouveau lien lui est envoyé. Cette fonction, héritée de
  NeoFrag, était restée inachevée et n'avait aucun réglage dans l'administration.

### Corrigé

- **Les textes accentués ne s'affichent plus codés.** Un titre, un pseudo, un libellé ou le nom du site qui
  contenait « é » ou « — » pouvait apparaître sous la forme `&eacute;` ou `&mdash;` : dans les
  conversations, la Boutique, les Dons, le sommaire d'un billet, les flux RSS, l'administration… Ces textes
  s'affichent désormais tous de la même façon, et deux vérifications automatiques empêchent la faute de revenir
  (l'une relit le code, l'autre les pages du site). Les suggestions de mention et de recherche, l'objet des
  courriels, les données lues par les moteurs de recherche (nom du site, titre et auteur d'un billet) et les
  webhooks reçoivent aussi le texte en clair.
- **L'initiale d'un avatar** montrait « & » pour un nom commençant par une lettre accentuée
  (administration, page d'une campagne de dons).
- **Une inscription par Discord, GitHub ou Google fait accepter le règlement**, quand le site en a un :
  un écran le montre, et le compte n'est créé qu'une fois la case cochée — comme par le formulaire
  d'inscription, que ces comptes contournaient.
- **Le menu ne mène plus vers un module absent** : le lien d'un module non installé ou éteint (Forum, Galerie,
  Actualités…) restait dans le menu — un clic, une page introuvable. Il disparaît, et revient quand le module est
  rallumé. Le lien d'une page personnalisée publiée reste ; celui d'une page non publiée disparaît aussi.
- **Sur un site sans le module Événements, la page des équipes** n'écrit plus d'avertissement au journal des
  erreurs.
- **Le bouton ☰ de l'administration fonctionne sur grand écran** : il y était affiché sans rien faire ; il
  replie maintenant le menu latéral, et le rouvre, en gardant le choix. Sur un téléphone, il ouvre le menu
  comme avant.
- **L'avatar de l'auteur d'un message du forum est plus grand** : 80 px (36 px sur téléphone), au lieu des 40 px
  que tous les thèmes lui donnaient.
- **Une image dans une signature, ou dans la description d'un album de la galerie, s'affiche** : la page
  montrait le code HTML (`<p><img …></p>`) au lieu de l'image. Les signatures et descriptions déjà enregistrées
  s'affichent sans être ressaisies.
- **La version texte des courriels est lisible** : les paragraphes y étaient collés, les accents codés, et
  un lien se retrouvait suivi du mot d'après — inutilisable dans une messagerie qui n'affiche que le texte.
- **Un formulaire trafiqué ne fait plus tomber la page en erreur** : une case à cocher envoyée sous une forme
  qu'aucun navigateur n'envoie est ignorée, comme une case non cochée.
- **Sur un site de démonstration, le compte administrateur créé à l'installation reste caché au survol et dans
  l'historique de modération**, comme sur sa page : la fiche affichée au survol montrait son nom, ses groupes et
  ses dates de passage, et l'historique de modération son nom.

## [1.2.26] — 2026-10-04

Le bot Discord passe en **version 0.2.3** : il n'écrit plus d'avertissement de discord.js à chaque réponse
privée (voir son propre journal des versions). La 0.2.2 continue de fonctionner.

### Ajouté

- **Le règlement et le message de bienvenue se traduisent langue par langue** :
  *Paramètres → Gestion des inscriptions* a un onglet par langue du site, et chaque visiteur lit le règlement — et
  reçoit le message — dans la langue de la page. Une langue qui n'a pas encore son texte montre le texte commun :
  un site qui n'en avait qu'un le garde pour toutes ses langues, rien ne change tant qu'on ne traduit pas.
- **Le journal des versions existe en anglais** (`CHANGELOG.en.md`, le français reste la référence).
- **Chaque addon a maintenant sa vignette, au même format (960 × 600)** : les modules API et Discord n'en avaient
  pas, les quatre thèmes du catalogue gardaient une ancienne image plus petite, et trois widgets du cœur n'en avaient
  aucune dans *Thèmes & addons*. Quelques-unes montrent encore un addon vide ou non configuré. Un nouvel addon ne
  peut plus être publié sans la sienne.

### Modifié

- **Les descriptions du catalogue disent ce que fait chaque addon** : trente d'entre elles tenaient en
  quelques mots ou se présentaient seulement comme « gaming ». Elles décrivent maintenant ce que l'addon fait
  vraiment, et à qui il sert, dans les six langues ; cinq disaient même une chose fausse (le widget
  Équipes n'affiche pas les membres, le widget Téléchargements montre les plus téléchargés…).
- **Le module Événements s'appelle « Événements »**, et non plus « Événements gaming » : aucun titre du
  catalogue ne porte plus le mot « gaming ».
- **Le module Paiements demande le module Gamification**, qui crédite les points et les jours VIP achetés :
  sans lui, un paiement était encaissé sans rien créditer. À l'installation du site, choisir Paiements ajoute
  Gamification ; depuis le marketplace, il faut l'installer d'abord. S'il manque ou se désactive ensuite, la vente
  se ferme et l'administration le signale ; un paiement reçu n'est pas marqué traité, et Stripe le renverra.

### Corrigé

- **Une image collée (Ctrl+V) ou glissée dans l'éditeur de texte est enregistrée** — réponse du forum,
  commentaire, page de l'administration, salon staff de la messagerie : elle s'affichait cassée, puis
  disparaissait quand on publiait. Elle est maintenant envoyée au site et reste dans le message. C'est réservé aux
  membres connectés : une image JPEG, PNG, GIF ou WebP de 5 Mo au plus. Le site l'enregistre à neuf, ce qui retire
  les informations cachées d'une photo (le lieu où elle a été prise, par exemple). Au-delà de 2 000 pixels de
  côté, l'image est réduite ; au-delà de 8 192 pixels de côté ou d'environ 12,6 millions de pixels, elle est
  refusée. Un GIF animé devient une image fixe. Sur le site de démonstration, l'envoi d'images reste fermé.
- **Le message de bienvenue s'affiche proprement** : écrit dans l'éditeur riche, il arrivait dans la
  messagerie avec ses balises visibles (`<h3>`, `<p>`…). Il y est mis en texte — titres, listes à puces ou
  numérotées, adresses devenues des liens —, et son titre ne montre plus `&eacute;` à la place d'un accent.
- **Le message de bienvenue part aussi quand on s'inscrit avec Discord, GitHub ou Google** : seule
  l'inscription par le formulaire l'envoyait.
- **Le QR code de la double authentification s'affiche** : l'écran d'activation (*Sécurité du compte →
  Activer le 2FA*) montrait le code de l'image dans une case de saisie au lieu de l'image à scanner — les
  formulaires ne connaissaient pas ce genre de champ et le prenaient pour un texte.
- **Les logos du widget Partenaires mènent au site du partenaire** et comptent la visite : ils menaient à
  une page introuvable depuis la 1.0.0, et le compteur « Visites » ne bougeait plus. La page Partenaires
  passe aussi par la visite comptée.
- **Les boutons « Payer » (Paiements) et « Acheter » (Boutique) fonctionnent** : ils appelaient une adresse
  introuvable.
- **S'inscrire à la newsletter depuis le widget fonctionne** : l'adresse tapée était perdue en route. Le lien
  de confirmation de l'e-mail était relatif — inutilisable dans un logiciel de messagerie —, comme le lien de
  désinscription des campagnes : tous deux sont des adresses complètes. Et si l'e-mail ne part pas,
  l'inscription est annulée et le visiteur est prévenu, au lieu de lire « envoyé ».
- **Les liens des e-mails du forum et de la messagerie** (mention, abonnement, discussion) sont des adresses
  complètes : relatifs, ils ne menaient nulle part.
- **Les sondages respectent le réglage « Afficher les résultats »** (après le vote, à la clôture, jamais) —
  sur leur page comme dans leur widget, qui les montrait toujours. Les gestionnaires les voient toujours,
  avec une mention.
- **Les widgets suivent les règles de leur module** : le widget Galeries ne montre plus les albums brouillons,
  programmés, à la corbeille ou réservés à un groupe ; le widget Forum, plus l'extrait des catégories réservées
  aux membres VIP ni de lignes vides pour les messages supprimés ; le widget et le calendrier des Événements,
  plus un événement programmé avant son heure ; le widget Recrutement suit « Masquer les offres
  indisponibles ».
- **Un lien vers une page du site, inséré dans l'éditeur, garde son adresse complète** : l'éditeur le
  raccourcissait en un chemin (`../../…`) qui ne marchait que depuis la page d'édition — affiché ailleurs, il ne
  menait nulle part.
- **La feuille de route** (`ROADMAP.md`) dit que le code est publié depuis le 4 octobre 2026.

### Sécurité

- **Deux anciennes adresses techniques de la messagerie affichaient les messages sans les protéger** : un message
  contenant du code aurait pu s'y exécuter dans le navigateur. Plus aucune page ne les utilisait, et il fallait déjà
  avoir accès à la conversation ; elles affichent désormais les messages comme la conversation elle-même.
- **Les boutons Facebook et Twitter de la page Partenaires ne suivent plus un lien `javascript:`** saisi dans
  l'administration : ces adresses sont filtrées et échappées, comme l'était déjà le site du partenaire.
- **Le bouton « Acheter » de la Boutique et le bouton « Payer » vérifient désormais que la demande vient bien du
  site** : une page piégée ne peut plus faire acheter un objet à un membre connecté sans qu'il le sache.
- **Un achat ne peut plus être compté deux fois** : le débit des points, le stock et la possession se
  vérifient et s'écrivent dans une seule transaction — deux achats simultanés ne vendent plus deux fois le
  dernier objet, ni ne débitent deux fois un solde. Les gains de points et les jours VIP s'ajoutent eux aussi
  d'un seul coup : deux gains au même instant ne s'écrasent plus. Un objet à 0 point s'obtient désormais
  sans débit (il était refusé, « points insuffisants »).
- **L'inscription à la newsletter est freinée** : 5 demandes par heure et par adresse IP, 3 par jour et par adresse
  e-mail — de quoi empêcher l'envoi en masse d'e-mails de confirmation à des adresses choisies.
- **Un sondage dont les résultats sont cachés ne montre plus son total de votes** dans la liste.

## [1.2.25] — 2026-10-04

Le bot Discord passe en **version 0.2.2** : les messages du forum qu'il relaie perdent toutes leurs balises
HTML, même imbriquées (voir son propre journal des versions). La 0.2.1 continue de fonctionner.

### Corrigé

- **La fenêtre du marketplace propose aussi les widgets qu'aucun module n'apporte** (À propos, Serveur de jeu, Lecteur
  de flux, Effet saisonnier, Groupe Steam, Serveur TeamSpeak 3, Statut live) : elle ne montrait que les
  modules et les thèmes, et ces sept widgets ne s'installaient que par « Ajouter », archive en main. Un
  widget déjà apporté par un module (celui du Forum, par exemple) n'y figure pas en double.
- **Le guide d'administration nomme le bouton qui enregistre une nouvelle adresse du site** par son libellé,
  *Utiliser*, suivi de l'adresse — et non plus par une fausse adresse `https://…` qu'un lecteur prenait
  pour un lien.

### Sécurité

- **Les workflows de vérification du dépôt ne reçoivent qu'un jeton en lecture** (`permissions: contents:
  read`) : ils n'ont rien à écrire, et une étape compromise ne pourrait ainsi rien modifier. Relevé par
  l'analyse de code de GitHub à l'ouverture des dépôts.
- **Le widget Vidéo n'accepte, pour un élément de sa liste de lecture, qu'une adresse `http(s)` ou un
  chemin du site** : une adresse `javascript:` ou `data:` est ignorée au clic. Le lecteur n'exécutait
  rien, mais une telle adresse n'avait rien à y faire. Relevé par la même analyse.

### Ajouté

- **Trois épreuves de plus dans le workflow `installation.yml`** : `check-extensions` installe chacun des
  addons du marketplace publié sur un site qui n'a que le cœur, par la fenêtre du marketplace, et par « Ajouter »
  ce qu'elle ne propose pas ; `check-prerequis-absents` retire tour à tour chaque extension PHP exigée, et vérifie
  que l'assistant et l'installeur en ligne de commande disent laquelle manque ; le paquet publié est installé
  par l'assistant chez un hébergeur mutualisé simulé (Apache sans fonctions qui lancent un programme,
  `open_basedir`, 128 Mo). Et les liens de tous les documents sont vérifiés (lychee).

## [1.2.24] — 2026-10-04

**NeoFrag Reborn s'ouvre sur GitHub.** Le code du CMS ([NeoFragReborn/neofrag](https://github.com/NeoFragReborn/neofrag)), les addons
à la carte ([NeoFragReborn/extensions](https://github.com/NeoFragReborn/extensions)) et le bot Discord
([NeoFragReborn/bot-discord](https://github.com/NeoFragReborn/bot-discord)) y sont publiés avec l'historique de leurs
versions — depuis la 1.0.0 pour le CMS et les addons, depuis la 0.1.0 pour le bot : le code de chaque version, ses
notes, et les paquets à partir de la 1.2.23.

### Modifié

- **Les bases de données annoncées sont celles qui sont éprouvées** : chaque version s'installe et passe
  toute sa suite de tests, avec PHP 8.2 et 8.5, sur MySQL 5.7, 8.0 et 8.4 et sur MariaDB 10.5, 10.6,
  10.11, 11.4 et 11.8 — le guide d'installation annonce ces versions-là, et non plus « 5.7+ » et « 10.5+ ».
- **Les guides disent où trouver le projet** : le paquet d'installation et l'archive du bot sur leur page
  des versions, les addons à la carte et leur catalogue sur celle d'`extensions`, le code pour qui veut
  contribuer. Dans le wiki, les renvois vers un document du dépôt (déploiement, architecture, outils)
  mènent à ce document sur GitHub, au lieu de n'en garder que le titre.

### Corrigé

- **Les réglages des modules s'ouvrent de nouveau** : la fenêtre « Configuration » du Forum, du
  Recrutement, des Actualités, de la Galerie, des Événements, du Calendrier et des Articles répondait
  « erreur 500 » à chaque ouverture, sur tous les sites, depuis la 1.2.0 : le champ « nombre » refusait un
  nombre entier (son pas, et la valeur relue de la base) depuis le passage du code au typage strict.
- **Équipes : l'option « Afficher les matchs réalisés » se décoche de nouveau.** L'enregistrement des
  réglages stockait le mot « Array » au lieu d'un oui ou d'un non, toujours lu comme « oui ».
- **Les réglages d'un authentificateur jamais configuré** (Google, Discord, GitHub) s'ouvrent sans écrire
  huit alertes au journal.
- **Le tableau de bord et les statistiques d'un site sans Articles, Bugtracker ou Forum** n'écrivent plus
  d'alertes au journal à chaque ouverture : les cartes de ces modules n'apparaissent que s'ils sont
  installés, et aucune table absente n'est plus interrogée.
- **L'assistant d'installation relit la configuration qu'il vient d'écrire** : sur un hébergement dont le
  cache de PHP ne revérifie pas les fichiers, une deuxième tentative à l'étape « Base de données » lisait
  encore l'ancienne `config/db.php`.
- **Un site déployé depuis git et installé par l'assistant web fonctionne** : l'assistant n'écrivait pas
  `config/neofrag.php` (le paquet le porte, un clone n'en a que le modèle), et chaque page répondait
  « erreur 500 » sans une ligne au journal.
- **L'exemple `nginx.conf` démarre** : la règle de TinyMCE, sans guillemets, faisait refuser toute la
  configuration à nginx (« missing closing parenthesis »). Ses redirections ne portent plus le paramètre
  interne `request_url`.
- **Les fichiers envoyés se servent sous Apache avec PHP en module** : la garde de `upload/` employait une
  directive interdite dans un `.htaccess`, et tout ce dossier — avatars, galerie, médiathèque — répondait
  « erreur 500 ».
- **Sous Caddy, les fichiers envoyés qui ne sont pas des images** (un PDF de la médiathèque) se servent :
  l'exemple ne servait que les images.
- **`/index.php` mène à l'accueil** (redirection permanente) au lieu d'une page introuvable ; et une
  adresse à extension qu'aucune page ne sert (`/newsletter/.env`, que tentent les robots) répond 404 sans
  écrire d'anomalie au journal.
- **`tools/check-all.php` sous PHP 8.2** : chaque contrôle y comptait pour un échec, même réussi — le code
  de sortie était relu une fois de trop, ce que PHP ne permet qu'à partir de la 8.3. Pour qui contribue
  sous PHP 8.2.

### Sécurité

- **L'assistant d'installation ne se rouvre plus sur un site installé dont la base ne répond pas.** Si le
  verrou `install/db.txt` avait disparu, une panne passagère de la base rouvrait l'assistant, dont l'étape
  « Base de données » réécrit `config/db.php` : un visiteur pouvait alors rediriger le site vers sa propre
  base. Le site affiche désormais « momentanément indisponible ». Et l'étape « Administrateur » refuse de
  créer un compte dès que le site en a un.
- **Apache n'exécute plus que l'`index.php` de la racine** : la règle de réécriture laissait s'exécuter
  directement tout fichier `index.php` d'un sous-dossier (plus de quatre-vingts contrôleurs internes).

### Ajouté

- **Quatre contrôles de plus** : `check-assistant` joue l'assistant d'installation de bout en bout, comme
  un visiteur, pour chaque profil (écrans, refus, compte créé, connexion avec le mot de passe saisi,
  journal muet) ; `check-reglages` ouvre l'écran de réglages de chaque addon, l'enregistre tel quel et le
  rouvre ; `check-serveur-web` éprouve Apache, nginx et Caddy, avec les configurations livrées, en posant
  des sondes (dossiers et fichiers interdits, scripts qui ne doivent pas s'exécuter, réécriture, en-têtes
  de sécurité) ; `check-nouveau-venu` joue tels quels le README et le guide du contributeur sur une
  machine vierge. Les workflows `installation.yml` et `nouveau-venu.yml` les rejouent chaque semaine.
- **Les commandes du README fonctionnent sur une machine neuve** : son bloc « Développer » s'arrêtait sur une
  suite de tests qui échouait faute de base de test ; il renvoie maintenant au guide du contributeur, qui crée
  cette base (`php tools/prepare-test-db.php`).

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
  *Monitoring* et le tableau de bord de l'administration montrent la même liste. Il faut PHP 8.2 ou plus
  récent ; le produit est éprouvé jusqu'à PHP 8.5.
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
  produit ; Extend garde la licence de son auteur (CC BY-NC-SA). La licence de chaque addon sous LGPL
  renvoie à son texte officiel.
- **HSTS** : l'en-tête que pose le `.htaccess` ne s'étend plus aux sous-domaines et ne déclare plus le
  site candidat à la liste de préchargement des navigateurs (`includeSubDomains` et `preload` retirés) :
  posés d'office, ils engageaient pour un an tous les sous-domaines de qui installait le CMS.

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
- **Le bilan du référencement** reconnaît une propriété Google Search Console vérifiée par le DNS du
  domaine (au lieu de la dire « non déclarée »), et conseille d'y soumettre le plan qui réunit toutes les
  langues, `/sitemap.xml`, et non celui de la seule langue affichée. Quand Google est vérifié, il ne dit
  plus Bing « non déclaré » : Bing Webmaster Tools peut importer un site depuis Google Search Console
  sans rien poser sur le site, et le bilan ne peut pas le voir.
- **`humans.txt`, `robots.txt` et la clé IndexNow** : une adresse absente — un `humans.txt` vide, une
  clé inconnue — répond un simple « introuvable », sans écrire une erreur au journal du site à chaque
  robot qui la demande.

---

## [1.2.22] — 2026-10-03

### Ajouté

- **Le référencement d'un contenu** : un bouton « Référencement » dans la carte d'édition d'une
  actualité, d'un billet du Blog, d'une page ou d'une page du wiki donne, pour chaque langue, le titre et
  la description que montrent les moteurs et les aperçus de partage. Laissés vides, ils restent
  automatiques.
- **Paramètres → Référencement → Bilan du référencement** : ce que voit un moteur de recherche, mesuré
  sur le site — les pages du plan par module, les textes de chaque langue, l'image de partage, Google et
  Bing, `robots.txt`, la maintenance —, avec pour chaque point le lien vers ce qui le corrige.
- **Les redirections** : une ancienne adresse mène à la nouvelle (301) au lieu de répondre « Page
  introuvable », avec le nombre de visites qu'elle reçoit encore. Une page ou une page du wiki renommée
  laisse la sienne d'elle-même ; on en ajoute à la main, pour l'adresse d'un ancien site par exemple.
- **Prévenir les moteurs (IndexNow)** : allumé dans Paramètres → Référencement, le site signale dans les
  minutes qui suivent chaque page qui paraît, change ou disparaît, à Bing, Yandex, Seznam, Naver, Yep et
  Amazon. Google n'y participe pas : pour lui, le plan du site reste la voie. Les pages de tout module
  présent dans le plan du site sont signalées, sans rien à ajouter ; la tâche planifiée du site est
  nécessaire.

### Corrigé

- **Modération** : dans l'administration, un modérateur sans le droit « conversations privées » pouvait
  ouvrir le signalement d'un message privé, et cet accès n'était pas inscrit au journal d'audit. Celui
  qui signale un message privé voit enfin l'avertissement qui lui est destiné.
- **Messagerie** : le message envoyé au salon du staff depuis l'administration répondait toujours
  « Aucun salon staff configuré ».
- Plus d'avertissement PHP au journal quand le signalement, le message ou le membre concerné a été
  supprimé.

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
- **Chaque page du Blog** donnait aux moteurs pour adresse de référence `/articles/…`, qui n'est qu'une
  redirection ; l'accueil donnait `/index`. Les liens entre langues sont complets, avec la version par
  défaut (`x-default`).
- **Le titre de l'accueil** ne répète plus le nom du site (« NeoFrag Reborn | NeoFrag Reborn »).
- **Google Analytics** : le réglage n'acceptait que l'ancien format `UA-…`, que Google a arrêté en
  2023 ; il accepte les identifiants actuels, `G-…`.
- **La langue du navigateur** : un visiteur dont le navigateur n'annonce que `de-DE` arrive en allemand,
  et non dans la langue par défaut du site.
- **Le sélecteur de langue** et le bandeau « Ce contenu n'existe pas en … », depuis le Blog, menaient à
  son ancienne adresse.

### Sécurité

- **Les adresses complètes** de l'en-tête des pages (canonique, langues, partage) sont construites sur
  l'adresse du site, et non plus sur l'en-tête `Host` de la requête, que n'importe qui peut forger.

## [1.2.20] — 2026-10-03

### Ajouté

- **Un profil d'installation « Association / club »** : actualités, forum, galeries, calendrier, dons,
  newsletter, wiki et FAQ, sans l'attirail esport. Il s'ajoute à *Complet*, *Gaming / eSport*,
  *Communauté* et *Cœur seul*, dans les six langues de l'assistant.

### Corrigé

- **L'export des membres au format JSON** (RGPD, article 15) écrit désormais sa mention « Export des
  données membres » : elle sortait vide.
- **Blog** : sur la page d'un auteur, « Voir son profil » menait à une page introuvable.

### Sécurité

- **L'instantané de la démonstration n'emporte plus aucun secret.** Le fichier qui remet la démo à zéro,
  livré avec le paquet de démonstration, aurait recopié l'identifiant d'envoi des e-mails, la clé du
  service de traduction automatique du NeoFrag d'origine et les clés des widgets Twitch et TeamSpeak
  d'un site où elles auraient été saisies ; il portait encore la ligne, vide, de la clé secrète du
  captcha. Aucune clé n'avait fui. Un contrôle vérifie désormais la liste contre le code, et le fichier
  livré lui-même.

### Documentation

- NeoFrag Reborn se présente comme **le CMS libre des communautés, du gaming aux associations** ; le
  guide des concepts indique quel profil choisir pour le site d'une association ou d'un club.

## [1.2.19] — 2026-10-02

### Ajouté

- **Un captcha moderne, actif dès l'installation : ALTCHA.** Il est hébergé par le site lui-même —
  sans compte, sans clé, sans cookie ni service tiers. Le visiteur voit une case qui se coche d'elle-même
  pendant qu'il remplit le formulaire : son navigateur fait un petit calcul, que le serveur vérifie. Il
  protège le formulaire de contact, l'inscription et le recrutement. À la mise à jour, un site sans clés
  reCAPTCHA passe sur ALTCHA ; un site qui en avait garde reCAPTCHA. ALTCHA rend l'envoi en masse coûteux
  pour un robot ; contre un attaquant obstiné, les fournisseurs ci-dessous, qui ajoutent leurs propres
  contrôles, sont plus solides.
- **Au choix, Cloudflare Turnstile, hCaptcha ou Google reCAPTCHA v2**, dans
  *Paramètres → Sécurité anti-bots*, avec le lien vers la console de chacun. La clé secrète est chiffrée
  et n'est jamais réaffichée. Sans ses deux clés, un fournisseur est remplacé par ALTCHA plutôt que de
  laisser le formulaire ouvert.
- Le captcha et ses messages existent dans les six langues.

### Corrigé

- **reCAPTCHA** : la clé secrète partait dans l'adresse de vérification ; elle part désormais dans le
  corps de la requête, comme Google le demande. Le lien vers sa console, périmé, est remplacé, et
  l'adresse du visiteur transmise suit la règle du reste du site, qui tient compte d'un mandataire
  déclaré de confiance.
- Quand la vérification anti-robot n'est pas validée, le message s'écrit en clair sous le captcha, au
  lieu d'une icône seule dont la bulle n'apparaît pas sur un écran tactile.

### Sécurité

- **Réussir le captcha une fois ne dispense plus de le refaire.** Depuis le NeoFrag d'origine, un
  captcha réussi dispensait de le repasser pour tous les envois suivants du même formulaire, jusqu'à la
  fin de la session : un robot qui résolvait un seul défi pouvait ensuite envoyer sans limite. La
  dispense ne vaut plus que pour un seul renvoi, après une autre erreur dans le formulaire.
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
- **Les guides du développeur** décrivent comment ne mettre en lien une adresse saisie qu'une fois
  vérifiée (`nf_url_sure()`), le verrou du site de démonstration, les listes découpées en pages, les
  compteurs et les dates ; les chiffres du README et des guides suivent le code (62 modules, 40 widgets).

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
  langue de la page, et non plus celle du navigateur (« 2 oct. 2025 » sur une page en français, et non
  « Oct 2, 2025 »). Au-delà de trois séries, des lignes seules plutôt que des aires qui se recouvrent.
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
  l'enregistrement. En anglais, les dates courtes s'écrivent désormais jour/mois/année et sur 24 heures,
  comme dans les autres langues (« 02/10/2026 14:05 ») ; un test vérifie, dans les six langues, qu'une
  date affichée se relit.
- **Plus de fausses alertes « fichier corrompu » avant une mise à jour.** Le Monitoring comparait les
  fichiers du site à la liste de contrôle de la dernière version publiée, même quand le site n'était
  pas encore à son niveau : tout ce que la nouvelle version change y paraissait corrompu (102 alertes
  sur un site juste avant son passage en 1.2.13). La vérification attend maintenant que le site
  soit à jour, et le dit.
- **Statistiques** : en allemand, le graphique ne se chargeait jamais ; les boutons « 7 jours »,
  « 30 jours », « 90 jours » et « 1 an » ne faisaient rien, dans aucune langue.
- **La date de modification d'une page du wiki** donnait l'heure de la dernière visite : le compteur de
  vues réécrivait la date de modification. Même défaut sur les petites annonces.
- **Dans la fenêtre de suppression du gestionnaire de fichiers**, « Annuler » et « Supprimer » étaient
  deux corbeilles rouges identiques. Les textes du module avaient perdu leurs accents (« Dossier cree
  avec succes », « Element deplace ») et comptaient en « élément(s) ».
- **Le tableau de bord et le journal d'audit** montrent ce qui s'est passé (« Mode débogage allumé »,
  « Paramètres enregistrés ») et non plus des identifiants techniques (`monitoring.debogage.allume`).
- **La page Commentaires de l'administration** affichait du code à la place de la date de chaque
  commentaire.
- **Un titre accentué** (« journ&amp;eacute;e ») ne s'affiche plus en code dans l'en-tête des cartes
  de l'administration.
- **En mode sombre**, les textes colorés de l'administration (alertes, pastilles, messages de réussite
  en vert) restaient sombres sur fond sombre, presque illisibles.
- **Les dates de la modération** (sanctions, signalements, historique d'un membre), **de la corbeille,
  des sauvegardes, des notifications et du journal du bot Discord** s'affichent dans la langue et à
  l'heure du visiteur (« 21/09/2026 22:54 »), et non plus telles qu'en base (« 2026-09-21 20:54:11 »).
- **« Connexions de membres »**, dans les statistiques, s'écrivait « Connections de membres ».
- **Les dates suivent la langue du visiteur** : le wiki, le Bugtracker, les petites annonces, le livre
  d'or, la newsletter, les conversations archivées, le gestionnaire de fichiers et le widget des
  événements les écrivaient en dur — telles qu'en base (« 2026-09-20 22:54 ») ou à la française même
  en allemand (« 02.10.2026 » attendu).

## [1.2.13] — 2026-10-02

### Ajouté

- **Le journal des erreurs, dans l'administration** (*Système → Monitoring → Journal des erreurs*). Plus
  besoin du FTP ni d'un accès au serveur pour savoir ce qui a échoué : les erreurs du site, regroupées,
  de la plus récente à la plus ancienne, classées par gravité, à filtrer par période ou par mot. Le
  chemin de l'installation, les mots de passe, les clés et les jetons sont masqués à l'écran, les adresses
  e-mail et IP en partie ; le fichier se télécharge et se vide (l'ancien est gardé à côté). Le Monitoring
  signale les erreurs des dernières 24 heures, et un dossier `logs/` où le site ne peut plus écrire.
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

- **Les heures ne sont plus décalées.** Le site les affichait à l'heure du serveur — l'heure
  universelle — pour tout le monde : deux heures de retard pour un visiteur en France l'été. Le fuseau
  du profil membre, lui, ne s'appliquait que sur les pages du profil, et décalait les dates que ce
  membre faisait enregistrer. Le calendrier, le wiki, le Bugtracker, les conversations archivées et le
  gestionnaire de fichiers écrivaient leurs heures sans conversion ; l'émission « en direct » de la
  webradio se lisait à l'heure du serveur.
- **Le calendrier** : le début et la fin d'un événement se choisissent dans un sélecteur de date, et
  non plus dans un champ texte au format « YYYY-MM-DD HH:MM:SS » ; un titre accentué ne s'affiche plus
  « journ&amp;eacute;e » ; la description mise en forme ne montre plus ses balises ; l'export agenda
  reçoit un texte propre.
- **Les listes avec recherche** disent « Aucun résultat » dans la langue du site, et non « No results
  found ».
- **L'éditeur en direct** propose désormais de composer les pages du Forum, des Galeries, des Équipes,
  du Contact et du Palmarès : son menu les omettait, faute de reconnaître leur page d'accueil.
- **Une page qui plante dit « Une erreur est survenue » (500)**, avec sa référence, au lieu de « Page
  introuvable », qui faisait croire à une mauvaise adresse.
- **Plus de page blanche.** Une erreur fatale, ou une erreur survenue en dehors d'une page, affiche une
  page d'erreur dans la langue du visiteur. La base de données injoignable aussi (503), au lieu d'un
  message en anglais brut, et la panne est enfin notée au journal.
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
- Deux erreurs internes ne laissent plus de trace au journal : un formulaire envoyé sans un de ses
  champs, et une requête à la base de données qui ne renvoie rien à lire — celle-ci faisait tomber la
  page.
- **La barre de débogage s'affiche de nouveau** : une valeur numérique la faisait planter depuis que le
  code vérifie strictement ses types, et sa chronologie était fausse.
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

Le bot Discord passe en **version 0.2.0**. Pour le mettre à jour : remplacez son dossier par celui de la
nouvelle archive en gardant le fichier `.env`, lancez `npm ci --omit=dev`, puis redémarrez-le. Créez ensuite
une nouvelle clé d'accès dans *Administration → Discord* — la page le demande — et remplacez l'ancienne dans
`.env` : sans elle, le bot n'a pas les droits du Bugtracker. La mise en place du serveur demande aussi la
permission Discord « Gérer les salons » : réinvitez un bot invité avec la 1.2.11 par le bouton
« Inviter le bot », ou donnez cette permission à son rôle dans les réglages du serveur.

### Ajouté

- **Bot Discord : des fonctionnalités qui s'allument une à une.** *Discord → Fonctionnalités* liste ce
  que le bot sait faire (il le déclare lui-même). Chacune s'allume, s'éteint et se règle depuis
  l'administration ; le changement s'applique dans la minute, sans redémarrer le bot. Le bouton
  **Resynchroniser** remet tout d'accord, et le forum rattrape au démarrage ce qui s'est écrit sur Discord
  pendant une absence du bot.
- **Bot Discord : il rattrape le site à son retour.** Ce qui s'écrit sur le site pendant que le bot est
  éteint part sur Discord quand il redémarre, dans la limite des 30 jours d'événements que garde le site.
  Il repartait jusqu'ici du dernier événement, et ce qui s'était écrit entre-temps n'arrivait jamais sur
  Discord.
- **Bot Discord : la mise en place du serveur.** Depuis l'administration, le bot crée sur Discord une
  catégorie, un salon Forum par forum choisi (ses préfixes en étiquettes) et un rôle par groupe choisi, pose
  les correspondances lui-même, et reprend au lieu de dédoubler ce qui existe déjà. Un aperçu précède,
  et la dernière mise en place s'annule.
- **Bot Discord : `/forum`.** `/forum account link` donne un lien à usage unique qui relie son compte Discord
  à son compte du site ; on peut au passage reprendre à son nom ce qu'on avait publié depuis Discord.
  `/forum account unlink` le délie. Sans compte relié, `/forum visibility` choisit comment on paraît sur le
  forum : son pseudo Discord, un nom anonyme, ou un pseudo choisi, changeable tous les sept jours — les
  messages déjà publiés suivent.
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
  rôles reliés à un groupe du site, ceux tenus par Discord et ceux placés au niveau du bot ou de qui les
  donne, ou plus haut, sont refusés. La page *Discord → Rôles temporaires* les liste, avec « Retirer maintenant ».
- **API** : deux nouveaux droits, « Lire le Bugtracker » (`bugtracker:read`) et « Écrire dans le Bugtracker »
  (`bugtracker:write`). Ils permettent de lire les tickets (`GET bugtracker/tickets`, les seuls ouverts avec
  `open=1`) et leurs commentaires, d'ouvrir un ticket au nom d'un membre, de le commenter au nom d'un membre ou
  d'un compte Discord, et de modifier ou supprimer ces commentaires. Le préfixe d'un sujet du forum se change
  aussi par l'API (`PATCH forum/topics/{id}`). Le bot reçoit les adresses `discord/*` de ses nouvelles
  fonctionnalités : rôles temporaires (`discord/timed-roles`), mise en place du serveur, liaison des comptes.

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
- **Bot Discord : supprimer sur Discord la copie d'un message du site n'efface plus l'original.** Quand le
  bot n'avait plus ce message en mémoire (après un redémarrage), supprimer sur Discord la copie d'un message
  écrit sur le site par un membre ayant lié son compte effaçait aussi le message du site. Dans ce cas, le bot
  n'efface plus sur le site que les messages venus de Discord.
- **Le wiki** : sur l'accueil du wiki, une page de premier niveau sans sous-pages s'affichait comme une
  rubrique vide ; elle montre maintenant ses premières sections (ou le début de son texte) et un lien pour la
  lire, et la barre latérale en fait un lien.
- **Les pages introuvables et interdites ne sont plus des pages vides.** Sur tous les thèmes publics, une
  adresse qui ne mène à rien (404) ou une page réservée (403) n'affichait que l'en-tête et le pied de
  page, sans un mot — défaut hérité de NeoFrag. Elles disent maintenant ce qui se passe, dans les six
  langues, avec un bouton pour revenir à l'accueil (au tableau de bord en administration), et l'onglet
  du navigateur porte « Page introuvable » ou « Accès non autorisé » au lieu du nom du module.

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
  d'emblée, quand les inscriptions sont ouvertes. Il recevait jusqu'ici « Compte Discord inconnu ».

### Corrigé

- **Lier son compte Discord connectait au compte d'un autre membre** quand ce Discord était déjà lié à
  ce dernier : la session basculait sur lui. La liaison est désormais refusée, avec un message clair, et
  un même compte externe ne peut plus être lié à deux membres.

## [1.2.9] — 2026-10-01

### Corrigé

- **Blog : le sommaire d'un billet restait titré « Sommaire » dans les autres langues**, et laissait un
  avertissement dans le journal du site. Un message de l'administration des membres
  (« Groupes du membre édités ») avait le même défaut.
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
- **Désactiver la double authentification** tombait en erreur une fois la désactivation faite, et laissait
  les anciens codes de secours en base.
- **Blog : une catégorie vide ne pouvait pas être supprimée** — le Blog la croyait toujours occupée —, et
  sa suppression n'était pas protégée contre les liens piégés. Elle demande maintenant confirmation.

## [1.2.7] — 2026-10-01

### Corrigé

- **Forum : un forum-lien affiche son icône.** L'icône choisie dans son formulaire d'administration remplace
  le globe qu'il affichait toujours.

## [1.2.6] — 2026-10-01

### Ajouté

- **Forum : la réponse « solution ».** L'auteur d'un sujet (ou un modérateur) marque la réponse qui
  le résout. Elle est mise en avant sous la question, signalée dans le fil, et le sujet s'affiche
  « Résolu » dans la liste. Une solution supprimée ou déplacée ne laisse pas le sujet résolu.
- **Forum : les préfixes de sujet** (« Question », « Tutoriel », « Important »…), créés par
  l'administrateur avec leur couleur, et traduisibles dans chaque langue active. Le membre en choisit un en
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
- **Bugtracker : la liste se filtre par type** (« Bug », « Demande de feature », « Question », « Autre »),
  et « Nouveau ticket » garde le type choisi. Un lien vers la liste filtrée (par exemple `/bugtracker?type=bug`)
  montre les tickets déjà signalés de ce type avant d'en ouvrir un nouveau.
- **Bugtracker : « Déjà signalé ? »** Pendant qu'on écrit le titre d'un nouveau ticket, les tickets
  ouverts qui lui ressemblent s'affichent dessous, pour commenter l'existant plutôt qu'en ouvrir un
  second. Un ticket peut aussi être marqué **doublon** d'un autre : sa page renvoie vers l'original.

## [1.2.4] — 2026-10-01

### Ajouté

- **Forum : des catégories et des forums traduisibles.** Chacun garde son titre par défaut et peut
  recevoir, dans son formulaire d'administration, un titre par langue active (et, pour un forum, une
  description). Le visiteur voit la traduction de sa langue, sinon le titre par défaut. Une adresse reste
  valable avec le titre par défaut comme avec chaque traduction.

### Corrigé

- **Une migration déjà en place ne bloque plus la mise à jour.** Appliquée à la main sans être
  enregistrée, elle échouait (« colonne déjà présente ») et aurait annulé la mise à jour entière. Les
  migrations s'appliquent désormais instruction par instruction, en ignorant seulement ce qui est déjà
  fait.
- **Les modules livrés avec le cœur reçoivent enfin leurs changements de base de données.** Seule la
  mise à jour d'un addon par le marketplace les appliquait : un module comme le forum ou le
  calendrier recevait son code neuf par la mise à jour du cœur, jamais sa base. Ils s'appliquent
  désormais avec ceux du cœur, par le bouton comme après un dépôt FTP.
- **Articles** : les six droits du module (ajouter, modifier, supprimer, et leurs équivalents pour les
  catégories) n'étaient vérifiés nulle part. Ils le sont, actions groupées comprises.
- **Un contenu programmé n'apparaît plus avant sa date**, ni un contenu mis à la corbeille : le widget
  « Articles récents », le plan du site envoyé aux moteurs, la recherche des actualités, l'activité
  d'un membre et le bloc « Derniers articles » les montraient. Le widget Tags des actualités ne mêle
  plus les langues, et celui des catégories ne compte plus les brouillons.

## [1.2.3] — 2026-10-01

### Corrigé

- **La mise à jour se met enfin à jour elle-même.** Son code — avec celui de la sauvegarde, de la
  restauration et du marketplace — vivait dans le dossier `install/`, que la mise à jour ne
  réécrivait jamais : un site gardait celui du jour de son installation, et ses corrections ne
  l'atteignaient pas. Il vit désormais dans le cœur (`neofrag/installer.php`), réécrit à chaque
  version, et le Monitoring en vérifie l'intégrité. Le reste d'`install/` suit aussi les versions ;
  seul le verrou `install/db.txt` reste propre au site. Sur un site existant, `install/` se met à
  jour à partir de la mise à jour suivant celle-ci.
- **Un site qui supprime son dossier `install/` après l'installation**, comme on le conseille
  souvent, garde sa mise à jour et son marketplace ; leurs messages restent alors en français.

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
- **Une mise à jour applique enfin les changements de la base de données du cœur.** Jusqu'ici, seule
  l'installation les appliquait : un site mis à jour gardait sa base ancienne sous un code neuf. Par le
  bouton, ils s'appliquent pendant la mise à jour, qui est annulée s'ils échouent. Par FTP, ils
  s'appliquent à la première page servie par le nouveau code. (Ceux des modules suivent depuis la 1.2.4.)
- **Connexion par Discord** : l'adresse d'un avatar animé (102 caractères) dépassait la place que la base lui
  réservait (100), et la connexion échouait ; cette place passe à 255 pour tous les comptes liés. Un membre
  Discord sans avatar recevait une image cassée.

### Modifié

- **L'envoi des e-mails passe à PHPMailer 7** (7.1.1). Sa seule rupture concerne les classes qui en
  héritent, et NeoFrag n'en a pas. Éprouvé par un envoi réel avec les réglages d'un site,
  en SMTP comme par `mail()`.

## [1.2.1] — 2026-10-01

### Sécurité

- **Deux failles corrigées dans la bibliothèque Markdown** (`league/commonmark` 2.10.3) : un déni de
  service par des tableaux construits pour ralentir le serveur (gravité élevée, GHSA-3q6v-r5mr-hxv8), et un
  contournement du filtre qui retire le HTML interdit (gravité moyenne, GHSA-97jj-33gv-5xf9). Rendues
  publiques le 21 septembre 2026. La bibliothèque de nettoyage du HTML (`ezyang/htmlpurifier` 4.19.1) passe
  aussi à sa dernière version.

### Corrigé

- **Le Monitoring ne déclare plus « manquants » des fichiers présents.** Depuis la publication de la 1.2.0,
  il comparait le site à la liste officielle des fichiers de la version, mais n'écartait les sources Sass
  (fichiers de style avant compilation) que du côté du site : quatre fichiers déclarés manquants à tort, et
  un état de santé « Le navire coule ! ».

### Modifié

- **Une mise à jour du cœur s'inscrit au journal d'audit** : qui l'a lancée, quand, de quelle version vers
  quelle version, combien de fichiers ont été écrits et combien d'anciens fichiers retirés. Elle s'écrivait
  jusqu'ici dans le journal d'erreurs, où elle passait pour une anomalie.

## [1.2.0] — 2026-09-23

### Ajouté

- **La mise à jour du cœur se fait en un clic** (2026-09-23). À partir de cette version, **Administration
  → Monitoring** signale les nouvelles versions de NeoFrag Reborn et les installe : sauvegarde du site,
  vérification de l'empreinte du paquet, application, mise à jour de la base, et retour à la sauvegarde
  si quelque chose échoue. C'est la première version publiée par ce chemin ; il a été éprouvé de bout en
  bout sur un site neuf avant la publication. Un site en 1.1.0 n'a pas encore ce bouton : il passe à la
  1.2.0 à la main, en remplaçant ses fichiers. Pour les mainteneurs : `tools/build-release.php` produit les
  trois fichiers à publier ensemble (`neofrag-reborn-update-<v>.zip`, `version.json`, `checksum.json`) ; la
  procédure est dans `docs/guide/marketplace.md`.

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
  - **Le marketplace** montre le nom et la description de chaque addon dans la langue du site.
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

- **Le navigateur des visiteurs peut garder les fichiers du site, si on le demande** (2026-09-22). Le
  manifeste rendait déjà le site installable ; le site peut désormais garder ses images, ses styles et ses
  scripts dans le navigateur des visiteurs. **Éteint par défaut**, à allumer dans **Administration →
  Paramètres → Préférences générales** (« Application installable »).

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
  réglages — et non déposé en fichier, qui serait faux partout ailleurs.

- **Les dossiers sensibles sont fermés sous Apache aussi** (2026-09-21). Les `.htaccess` du paquet
  protégeaient **moins** que la configuration Caddy fournie : sous Apache, l'installateur restait joignable
  une fois le site installé (seule sa propre vérification l'empêchait de s'exécuter), et sur un site
  installé depuis le dépôt git, `docs/`, `tools/` et `tests/` l'étaient aussi — la documentation se lisait
  en texte. `cache/`, `config/`, `install/`, `tools/`, `tests/` et `docs/` portent désormais chacun leur
  garde, la racine refuse aussi les fichiers `.neon` et `.md`, et `tools/check-htaccess.php` relit la liste
  à chaque passage, avec la raison de chaque ligne.

- **Une adresse inconnue n'écrit plus d'erreur dans le journal du site** (2026-09-21). Le module `pages`,
  qui reçoit en dernier recours les adresses que rien d'autre ne reconnaît, écrivait une ligne d'**erreur**
  pour chacune — chaque robot qui sonde le site en ajoutait. Pour les développeurs d'addons : un contrôleur
  peut désormais déclarer un refus ordinaire (`Module_Checker::refus_ordinaire()`) ; le diagnostic reste
  affiché à l'écran en mode débogage. Le contrôle `check-journal` refuse désormais toute ligne que le
  produit écrit lui-même dans le journal des erreurs.

- **Deux contrôles font respecter les règles du dépôt** (2026-09-21), pour les contributeurs.
  `tools/check-tools.php` vérifie que chaque outil suit les règles communes : en-tête descriptif, appui sur
  la bibliothèque partagée plutôt que du code recopié, verdict et codes de sortie normalisés, port réservé,
  catalogue à jour. `tools/check-docs.php` vérifie la documentation : chiffres exacts, liens et ancres
  valides, aucun document orphelin, aucun outil cité qui n'existe plus, aucun passage recopié d'un document
  à l'autre, documents qui restent lisibles. Les deux tournent en intégration continue.

- **Huit nouveaux addons, tous facultatifs** (2026-09-20). Une mise à jour ne les installe pas : sur un
  site existant, ils s'ajoutent depuis **Administration → Thèmes & addons**, et se retirent de même. Une
  installation neuve les inclut avec le profil *Complet*, proposé par défaut, mais pas avec les autres.

  | Addon | Ce qu'il fait |
  |---|---|
  | **Effet saisonnier** (widget) | Neige, confettis ou feuilles sur tout l'écran, pendant une plage de dates. Les dates s'écrivent `MM-JJ` **sans année** — la saison revient toute seule — et peuvent enjamber le Nouvel An (`du 12-15 au 01-06`). Hors saison, la page ne reçoit **rien du tout** : ni image, ni script. Le réglage du système qui demande moins d'animations (`prefers-reduced-motion`) est respecté, et l'animation se met en pause quand l'onglet passe à l'arrière-plan. |
  | **Lecteur de flux** (widget) | Les derniers articles d'un flux RSS ou Atom extérieur. **Une page n'attend presque jamais un site tiers** : elle lit un cache, et sert même un contenu périmé plutôt que de faire patienter ; le rafraîchissement a lieu dans la tâche planifiée. Seul le tout premier affichage, cache vide, attend le flux, trois secondes au plus. Un flux tombé est mis de côté dix minutes au lieu d'être retenté à chaque visite. |
  | **Citations** (module) | Un recueil classé, avec auteur et source. |
  | **Recettes** (module) | Ingrédients et étapes saisis une par ligne, durées et nombre de parts, balisage `schema.org/Recipe` que lisent les moteurs de recherche. |
  | **Dictionnaire** (module) | Un lexique rangé par lettre. « Éclaireur » se range sous E, « Æther » sous A, « 1v1 » sous #. La lettre est calculée, jamais saisie. |
  | **Carte des lieux** (module) | Des lieux sur une carte OpenStreetMap, avec adresse, description et lien. La bibliothèque de carte est **hébergée par le site** ; seules les images du fond de carte viennent d'OpenStreetMap. **La page reste utile sans JavaScript** — la liste, les adresses et les liens y sont. |
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
  installation allégée finissait sur une erreur serveur (500) : des modules du cœur interrogeaient les
  tables de modules facultatifs sans vérifier qu'elles existaient. Ces vérifications existent désormais et
  sont contrôlées à chaque exécution de la CI.

- **Chaque module, widget et thème livré déclare son rôle** : dans son `__info()`, il dit s'il appartient
  au cœur, dans quels profils d'installation il entre, et de quels modules il a besoin. L'appartenance au
  cœur était jusqu'ici un effet de bord de la présence dans le paquet — c'est ainsi qu'`emojis`, `files`
  et `webhooks` s'y étaient retrouvés sans qu'aucune décision ne soit prise.

- **Trois nouveaux garde-fous en intégration continue** :

  | Contrôle | Ce qu'il refuse |
  |---|---|
  | `check-addon-declarations.php` | un addon muet, une dépendance inexistante, **un addon du cœur qui dépend d'un optionnel**, un addon non distribuable publié au catalogue, une déclaration en désaccord avec `seed.sql` |
  | `check-addon-coupling.php` | un couplage **fatal** (table, classe) ni déclaré ni annoté ; un cycle dur. Lu dans le code au **tokenizer PHP**, pas à l'expression régulière — donc aucun faux positif sur les commentaires |
  | `check-install-profiles.php` | un profil qui ne démarre pas. Il **installe pour de vrai** sur une base jetable, sert le site et échoue au moindre 5xx — y compris sur les routes des modules absents, qui doivent rendre un 404 propre |

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

  Désormais, une occurrence de série porte un bouton **« Supprimer toute la série »**, dont la
  confirmation annonce combien d'occurrences partiront, et le formulaire d'édition propose
  **« Appliquer à toutes les occurrences de la série »**. Le titre, le type, les descriptions, le lieu, l'image
  et la publication sont recopiés sur chacune ; **les dates ne le sont jamais** — ce sont elles qui
  distinguent une séance de la suivante.

- **Retour arrière d'une mise à jour, et restauration d'une sauvegarde.** Le CMS prenait déjà une
  sauvegarde complète — fichiers et base — juste avant chaque mise à jour du cœur. Il ne savait pas
  s'en resservir : le filet était tendu, personne ne savait tomber dedans.

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

- **Répondre à un commentaire.** Le bouton « Répondre », désactivé dans le code d'origine, fonctionne : la
  réponse s'affiche en retrait sous le commentaire visé. On ne répond qu'à un commentaire de premier niveau
  (un seul niveau de réponses), du même contenu et encore en ligne : le serveur le vérifie.
- **Pour les créateurs de thèmes : des zones appelées par leur nom** (idée reprise de HiddenCMS). Une vue
  appelle désormais une zone par son nom — `$this->output->region('content')` — plutôt que par son numéro
  — `zone(2)`. Le thème déclare la correspondance entre noms et zones dans son `__info()` (`regions`).
  L'ancien appel `zone()` fonctionne toujours, et un thème sans cette déclaration ne change pas. Les cinq
  thèmes publics (Nebula, Forge, Granite, Blockcraft, Extend) emploient les noms.
- **Installation en ligne de commande** (`install/cli.php`, idée reprise de HiddenCMS) : une alternative à
  l'assistant web, pratique pour installer un serveur de façon reproductible ou automatisée. Ses options
  couvrent la base de données (`--db-*`, `--create-db`), le compte administrateur (`--admin-*`), le nom et
  l'adresse du site (`--site-name`, `--site-url`), la langue (`--lang`), les données de démonstration
  (`--demo`) et le déroulement (`--force`, `--yes`, `--dry-run`, `--no-lock`). Sans options, il pose les
  questions une à une et masque la saisie du mot de passe ; `--admin-pass-env` lit le mot de passe dans une
  variable d'environnement, pour qu'il n'apparaisse pas dans la liste des processus. Il emploie le même code
  que l'assistant web et installe tous les addons livrés, comme le profil *Complet*.

- **Un lanceur pour toute la batterie** : `php tools/check-all.php`. Les contrôles n'avaient
  aucun point d'entrée commun — la CI les appelle un par un, et en local chacun refaisait une
  boucle à la main, jamais la même. Le lanceur découvre les contrôles présents (un contrôle neuf
  entre sans qu'on y touche), joue `composer audit` en tête, borne chaque contrôle en durée, et
  énonce à la fin **ce qu'il n'a pas joué** : les épreuves en navigateur (`--navigateur`) et les
  contrôles à cible explicite, dont `check-restauration` qui abîme volontairement le site.

- **Chaque addon montre à quoi il ressemble** (2026-09-22). La page « Thèmes & addons » de
  l'administration et le marketplace affichent une vignette : une capture d'écran réelle du module, du
  widget, du thème ou de la langue tel qu'il tourne sur la démonstration. Les 61 addons de la place de
  marché en ont tous une, en 960×600 pour les modules et les widgets. Pour y arriver, six modules et deux
  widgets ont été installés sur la démonstration avec du contenu d'exemple — un dictionnaire, des
  citations, des recettes, une carte de lieux et une grille de webradio —, et les widgets qui n'affichent
  rien sans réglage ont été photographiés avec des réglages d'exemple. Ce qui n'a rien à montrer garde son
  icône, comme les connecteurs Discord, GitHub et Google, dont l'icône est le logo. La démonstration porte
  aussi enfin l'identité de sa communauté — nom, type, date de création et présentation — qui était
  restée vide.

- **Dix addons ont enfin une description** (2026-09-22) : les six langues et les quatre addons de connexion
  externe (le socle et ses connecteurs Discord, GitHub et Google). Elle s'affiche dans la langue du site.

- **Des champs de profil définis par l'administrateur** (2026-09-20). Le profil d'un membre n'avait que des
  champs fixes. **Administration → Utilisateurs → Champs de profil** permet d'en ajouter — pseudo en jeu,
  plateforme, rang… — parmi huit types de champ. Les membres les remplissent dans le panneau
  « Informations complémentaires » de leur profil. Un champ est privé par défaut ; rendu public, il
  s'affiche sur la fiche du membre. Les valeurs entrent dans l'export des données personnelles du membre et
  s'effacent avec son compte.

- **La police du site se choisit dans l'administration** (2026-09-20). **Administration → Paramètres →
  Préférences générales → Police du site** propose douze polices, qui remplacent celle de tous les thèmes.
  Par défaut, « Police du thème » garde la police de chaque thème et ne fait appel à aucun service
  extérieur ; les douze polices au choix sont servies par Google Fonts.

- **L'historique des connexions ne se garde plus indéfiniment** (2026-09-20). Il conserve l'adresse IP, le
  navigateur et la date de chaque connexion, et rien ne l'effaçait. Il est désormais purgé au-delà de
  395 jours (treize mois), durée réglable dans **Administration → Paramètres → Préférences générales** ;
  0 désactive la purge.

- **Rappel des événements suivis du calendrier** (2026-09-20). Un membre qui suit un événement du calendrier
  reçoit un rappel avant son début, comme les participants d'un événement du module Événements. Le délai se
  règle dans la configuration du calendrier (« Rappel avant un événement suivi », 24 heures par défaut,
  0 pour désactiver).

- **Le paquet de mise à jour dit ce qu'il protège et ce qu'il retire** (2026-09-20). Il porte un fichier
  `nf-manifest.json` : les dossiers du site qu'une mise à jour n'écrit jamais (`config/`, `install/`,
  `upload/`, `logs/`, `backups/`, `cache/`), et les fichiers que la version retire. Jusqu'ici, la mise à
  jour déduisait d'elle-même ce qu'il fallait effacer ; un paquet sans ce fichier reste accepté.

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

- **Le JavaScript est relu par un linter** (2026-09-22). Soixante et onze fichiers du projet, écrits sur des
  années, que rien ne vérifiait au-delà de la syntaxe et de l'absence de jQuery. ESLint voit ce que la syntaxe ne dit
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

- **Les outils de `tools/` refondus sur une bibliothèque commune** (2026-09-21). Une soixantaine d'outils
  écrits chacun à sa manière recopiaient la même plomberie : lancer un serveur PHP, ouvrir une session
  d'administrateur, lancer Chrome, lire `config/db.php`. Une leçon apprise dans l'un ne se propageait pas
  aux autres. `tools/lib/` porte désormais ce socle — connexion, session, serveur, navigateur, parcours du
  dépôt, SQL, options, verdicts — et chaque outil tient dans sa logique propre. Il en reste cinquante-deux,
  sans perte de fonction : `check-js-syntax` et `check-js-jquery` forment `check-js-sources` ;
  `check-docs-counts` et `check-docs-liens` forment `check-docs` ; `check-lang-args` rejoint `check-langs` ;
  `seed-wiki-docs` et `dump-wiki` forment `wiki-docs` ; `smoke-test` devient `check-smoke`, comme tout contrôle ;
  `addons-manifest` et `table-map`, qui sont des données, vivent dans la bibliothèque. Chaque outil a
  désormais son port réservé (plusieurs en partageaient un), trois codes de sortie qui distinguent « rien à
  reprocher », « refusé » et « n'a pas pu juger », et un en-tête dont le catalogue de `tools/README.md` est
  engendré. `bs5-codemod`, outil de la migration Bootstrap 4 → 5 de juin, est retiré : `check-classes-bs4`
  couvre tout ce qu'il vérifiait, points de rupture des utilitaires directionnels compris.

- **`declare(strict_types=1)` sur tout le périmètre utile** (2026-09-21) : **1 365 fichiers** contre
  78 la veille. Le chantier avançait par petits lots depuis juin ; il est
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
  près de 500 emplacements dans 86 fichiers.

  La vague a aussi imposé onze conversions explicites, dont trois sur des chemins empruntés à chaque
  page — l'ordre d'affichage des langues et des groupes, lu au démarrage de chaque session, et le calcul du
  jeton qui protège tous les formulaires contre les requêtes forgées (CSRF) : sans elles, un site à deux
  langues actives aurait rendu une erreur 500 sur toutes ses pages.

- **Le journal dit désormais OÙ, pas seulement quoi** (2026-09-21). Quand un module lève une
  exception, son rendu est abandonné et la page s'affiche amputée — en silence. La ligne de journal
  donnait le message sans jamais nommer un fichier. Elle nomme maintenant le premier emplacement du code
  du produit dans la pile d'appels. Retrouver l'origine des erreurs ci-dessus est passé d'une enquête à une
  lecture.

- **Le compilateur SCSS passe en 2.1** (2026-09-20). Sur PHP 8.5, chaque compilation des styles écrivait
  **dix dépréciations** dans le journal ; il n'y en a plus aucune. La 2.x minifie un peu plus (`.25rem`
  au lieu de `0.25rem`, `#ccc` au lieu de `#CCCCCC`), ce qui change trois feuilles — celles des modules
  addons et palmarès, et celle de la page de maintenance. L'équivalence n'a pas été déduite du
  texte : les deux jeux de CSS ont été donnés à lire au moteur de Chrome, qui y voit exactement les
  mêmes règles, propriétés et valeurs calculées.

- **La documentation a été relue en entier contre le code** (2026-09-17). Les guides d'installation, de
  concepts et de développement (créer un module, un widget, un thème, le framework) dataient de juin et
  décrivaient un CMS d'avant : une installation sans choix de profil, un thème qui chargeait jQuery, des
  grilles Bootstrap 4. Ils disent désormais ce que fait le produit — profils d'installation, déclarations
  d'addons, régions nommées, front sans jQuery sous politique de sécurité stricte, réglages de widget avec
  valeurs de repli, vocabulaire de couleurs partagé. Le README,
  le guide de contribution, la politique de sécurité et la référence technique sont alignés de même.

- **Ce qu'un addon autorise se lit dans sa déclaration**, plus dans des listes de noms en dur. Trois
  d'entre elles décidaient du sort des addons, et les trois étaient fausses : celle des widgets
  protégeait sept noms qui ne sont pas des widgets ; celle des thèmes protégeait un thème `default`
  inexistant **en laissant `nebula`, le seul thème public du cœur, supprimable** dès qu'il était inactif ;
  celle des modules dupliquait, en désaccord, ce que `__info()` disait déjà. Vérifié addon par addon
  contre l'ancienne logique : aucun changement de désactivabilité, aucun changement d'état actif, et
  **39 addons deviennent non supprimables** — ce qui est l'objet du correctif.

- **La Corbeille ne connaît plus les autres modules.** Elle tenait en dur la liste des tables, clés
  primaires et méthodes de restauration de `news`, `articles`, `gallery`, `comments` et `forum` : elle
  ne pouvait donc pas appartenir au cœur sans tirer avec elle quatre modules optionnels — `comments`,
  lui, est du cœur. Chaque module
  déclare maintenant ses types restaurables ; le cœur collecte. Même inversion pour les descripteurs de
  contenu (réactions, abonnements, révisions).

- **La liste des addons du cœur et des addons facultatifs n'est plus tenue à la main.**
  `tools/lib/addons-manifest.php`, table écrite à la main et figée en juin, dérive désormais des
  déclarations. Les deux avaient déjà divergé : `emojis` y figurait comme cœur alors qu'il se déclare à la
  carte — il était donc décochable à l'installation mais absent du catalogue, donc **impossible à
  réinstaller**. Catalogue : 52 → **53 addons** ce jour-là (61 à la sortie de la 1.2.0).

- **Installateur repris sur la forme** : mise en page à deux colonnes, jetons de style repris de `nebula`
  (marine et sarcelle, accent `#2dd4bf`), dégradé sorti de derrière le texte, logo posé dans une pastille.

### Corrigé

- **La sauvegarde du Monitoring fonctionne de nouveau** (2026-09-23). Depuis le 21 septembre, elle
  s'arrêtait dès la première ligne de la base : le bouton « Lancer la sauvegarde », et la mise à jour du cœur qui
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
  deux accordéons (FAQ, liens de la navigation) restés à la structure de Bootstrap 4 (celui de la FAQ
  s'ouvrait, mais sans l'apparence ni la flèche du composant), des champs qui s'enveloppaient dans un
  groupe vide, la flèche des bulles d'aide restée blanche sous une bulle sombre, et deux règles du widget
  des partenaires. `check-classes-bs4` lit désormais aussi la STRUCTURE des composants et les styles
  écrits dans les vues.

- **Les pages d'erreur qui n'en étaient pas** (2026-09-23). Dans la modération, la gestion des
  rôles, des modèles d'e-mails, des membres et du diaporama, une adresse vers un élément disparu
  devait répondre « page introuvable » : elle appelait une fonction qui n'existe pas, écrivait un
  avertissement au journal, et la page de modération s'affichait vide. Trouvé en apprenant à
  l'analyse statique les fonctions « magiques » du framework — ce qui a allégé de 455 occurrences sa
  liste d'exceptions.

- **Le bouton des e-mails envoyés aux membres est lisible** (2026-09-23) : validation du compte, mot
  de passe perdu et les autres modèles avaient un bouton blanc sur turquoise clair (contraste 2,3:1). La
  correction vaut pour les modèles livrés et pour ceux déjà installés, sauf ceux qu'un administrateur a
  personnalisés.

- **Dernières finitions d'affichage** (2026-09-23) : liens du wiki dans la teinte lisible du thème ; boutons
  « succès » des thèmes sombres ; statut d'une candidature, dont la boîte n'avait aucun fond (classes d'un
  ancien thème d'administration) ; réseaux sociaux des cartes de membres ; titres coupés dans
  l'administration ; un visiteur qui suit le lien de participation à un événement reçoit « Accès non
  autorisé » au lieu d'une page introuvable.

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
  lieu de ne lui laisser que 200 px de large, et les boutons d'un message ne recouvrent plus sa date.

- **Les pluriels traduits** (2026-09-23). 47 textes avaient perdu leur forme plurielle en
  traduction : un site en anglais affichait « 3 topic », « 5 image », « 2 year ». Le compteur des
  catégories du forum, écrit en français dans toutes les langues, est traduit.

- **La documentation du site est à jour** (2026-09-23). Les dix pages du wiki livrées avec le produit
  et celles de la démonstration avaient une semaine de retard sur les guides ; « Créer un widget »
  enseignait encore une classe de Bootstrap 4. Un contrôle les compare désormais aux guides.

- **Le formulaire de réponse du forum tient sur un téléphone** (2026-09-23) : la liste des types de
  pièces jointes autorisés, écrite sans espace, formait un seul mot trop large pour l'écran.

- **Tout le site passé au crible, dans chaque thème, chaque mode et à chaque largeur** (2026-09-23).
  Un contrôle automatique rend désormais chaque page d'administration dans son thème et chaque page
  publique dans chaque thème public, en clair et en sombre, connecté en administrateur et en visiteur, à
  huit largeurs de 360 à 2560 px, avec et sans contenu.
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
  listes de l'administration à deux colonnes — catégories des actualités, de la galerie… —, le
  second bouton passait à la ligne, décalé sous le premier. Ils restent
  désormais côte à côte.

- **La carte « Stockage » de la supervision s'affiche correctement** (2026-09-22). La jauge se dessinait
  en cercle entier au lieu du demi-cercle prévu, avec un nombre brut au centre, et la valeur et le
  pourcentage, placés en haut, chevauchaient le trait ; ils sont désormais dans le creux de la jauge. Le
  titre « Informations serveur » de la même page passe sur deux lignes quand la colonne est étroite, au
  lieu d'être coupé net. Dans le groupe « Envoi d'email », la ligne « Transport email » affichait
  `[object Object]` à la place de la méthode employée.

- **Un widget posé sans réglages s'affiche avec ses valeurs par défaut** (2026-09-22). Quand un
  thème pose un widget à l'installation, ou qu'une ancienne disposition le restaure, il arrive
  sans réglages : il écrivait alors des alertes dans le journal à chaque page affichée, et le
  menu de navigation disparaissait purement et simplement. Il reçoit désormais les valeurs que
  son formulaire aurait enregistrées par défaut. Les widgets déjà réglés ne changent pas.

- **La page des événements s'affiche de nouveau** (2026-09-22). Elle rendait une page d'erreur
  « introuvable » dès qu'elle avait des événements à répartir sur plusieurs pages — sous un
  titre parfaitement normal, ce qui la rendait difficile à remarquer. Le réglage du nombre
  d'événements par page était enregistré comme du texte, et le code, rendu plus strict la
  veille, le refusait. Toutes les listes paginées acceptent désormais ce réglage sous les deux
  formes.

- **Les votes des sondages sont enregistrés** (2026-09-22). Le votant lisait « Merci pour ton
  vote ! », et le vote n'était jamais compté : aucune option n'était reconnue comme appartenant
  au sondage. Un vote qui ne vise aucune option du sondage est désormais refusé en le disant,
  au lieu d'être salué.

- **Le widget « Forum » affiche enfin des sujets et des messages** (2026-09-22). Ses deux types,
  « Derniers sujets » et « Derniers messages », ne retenaient aucune catégorie de forum, et restaient donc
  vides sur tous les sites, quel que soit le nombre de messages.

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

- **Les autres bulles d'aide des formulaires s'affichent au survol** (2026-09-22). La correction de la
  veille ne couvrait qu'une partie des formulaires : l'icône (i) posée à côté des champs des autres écrans
  de l'administration, et les info-bulles des étiquettes, ne montraient toujours rien — même défaut
  d'attribut, dans les deux bibliothèques qui les fabriquent.

- **Les libellés accentués du menu sont traduits** (2026-09-22). Un lien de menu enregistré
  depuis l'administration — « Actualités », « Équipes » — est stocké sous forme codée, et sa
  traduction n'était plus trouvée : il restait en français dans les cinq autres langues, avec
  une alerte au journal à chaque page affichée.

- **Les archives du marketplace sont reproductibles** (2026-09-22). Elles emportaient les
  cartes de source `.map`, que le compilateur de styles régénère sur chaque installation et que
  le dépôt ignore : deux archives du même addon, bâties sur deux machines, différaient sans que
  le code ait bougé. Les archives n'emportent plus ces fichiers.

- **Le widget « Palmarès » s'installe enfin par dépôt d'archive** (2026-09-22). Il était le seul
  des 61 addons distribuables à ne pas déclarer sa dépendance au cœur ; l'installeur, qui l'exige
  pour reconnaître un addon, passait son chemin SANS RIEN DIRE — ni message, ni trace.

- **La page de maintenance n'est plus blanche** (2026-09-22). Les deux champs « Titre » et
  « Contenu » sont livrés VIDES, et la page ne montrait donc RIEN d'autre que le nom du site —
  alors que l'aperçu de l'administration promettait un titre et un texte. Un titre et un
  message par défaut, traduits dans les six langues, comblent le vide ; dès que les champs
  sont remplis, ce sont eux qui s'affichent.

- **Les boutons à contour ont retrouvé leur cadre** (2026-09-22). Les thèmes ne redéfinissaient que la
  variante « primaire » du bouton à contour, et leur propre règle effaçait la bordure des autres : les six
  autres variantes employées par le produit — 90 emplois dans le code — ressemblaient à de simples liens.
  Le plus visible était « Ouvrir le site », dans le bandeau de maintenance, qui n'avait de cadre qu'au
  survol.

- **Le code technique n'est plus rose dans l'administration** (2026-09-22). Le thème ne fixait
  que la police des extraits `<code>`, qui gardaient donc le rose de Bootstrap — une couleur
  étrangère à toutes les palettes du produit. Très visible sur la liste des rôles.

- **Le bouton « Fermer » des fenêtres ferme enfin la fenêtre** (2026-09-22). Dans TOUTES les
  fenêtres du produit, le bouton « Fermer » (ou « Annuler ») du bas ne faisait rien : il fallait
  la croix en haut à droite. La cause : Bootstrap 5 a renommé l'attribut qui commande la
  fermeture, et le cœur posait encore l'ancien nom ; un navigateur ignore un attribut inconnu
  **sans rien dire**. Le bouton est aussi devenu un vrai bouton — il était rendu en `<span>`,
  donc inaccessible au clavier. Deux gardes l'empêchent de revenir : le contrôle du code refuse
  l'ancien nom, et un parcours de navigateur, lancé à la main, CLIQUE sur le bouton.

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
  adresse technique qui répond toujours en JSON. Le serveur distingue désormais l'appel de la fenêtre
  « Choisir ma langue », qui attend du JSON, d'un envoi de formulaire ordinaire, qui attend d'être
  emmené sur la page. Trouvé en suivant le geste dans un vrai navigateur, ce qu'aucun contrôle ne
  faisait jusque-là.

- **Un contenu rédigé dans une seule langue ne renvoie plus les autres sur une page d'erreur**
  (2026-09-21). Une actualité écrite en français proposait quand même les cinq autres langues dans son
  sélecteur, et les cinq rendaient 404 : mesuré sur la démonstration, **60 adresses mortes sur 72**.
  Le geste le plus naturel d'un visiteur étranger tombait sur une erreur. La version qui existe est
  désormais servie, avec un bandeau qui le dit dans la langue du visiteur et un lien vers l'original.
  Cela vaut pour les actualités, les articles et les albums (et leurs catégories), ainsi que les équipes.

  Deux précautions indissociables, sans quoi le remède coûterait plus que le mal : la page n'annonce
  plus aux moteurs de recherche que les langues qui **existent vraiment**, et elle désigne l'original
  comme adresse de référence — sinon six adresses seraient indexées pour un seul texte.
  **En administration, aucun repli** : une version vide doit se voir vide, c'est ce qu'on vient
  remplir. `tools/check-langues-contenu.php` tient les trois propriétés, en intégration continue.

- **L'intégration continue est de nouveau verte** (2026-09-21) ; sur la branche principale, elle était
  rouge depuis le 10 juin. Les causes tenaient presque toutes aux outils de test et à la configuration de
  la CI, pas au produit. L'exception : sous le serveur intégré de PHP 8.3, le site pouvait mal calculer
  son adresse de base et rediriger feuilles de style et scripts ; il ne la déduit plus que d'un
  `SCRIPT_NAME` qui se termine par `index.php`. Une suite de tests qui ne peut pas tourner échoue
  désormais au lieu de se sauter en silence, et la plupart des tâches de la CI ont une durée maximale.

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
  | **Les bulles d'aide d'une partie des formulaires** étaient vides | cœur — `neofrag/libraries/form.php` |
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
    — qu'aucune feuille servie ne définit. Le message s'affichait bien, mais rien ne désignait le champ
    concerné. Ils sont remplacés par un marqueur maison, défini une fois.
  - **Le pied de page** portait `<div class="float-right">` : « Propulsé par NeoFrag Reborn » tombait à la
    ligne, à gauche. La valeur vient de loin — une migration de l'Alpha 0.2.2 l'avait passée de
    `pull-right` à `float-right` au moment de Bootstrap 4, et le pas suivant n'a jamais été fait. Une migration
    (`2026_09_20_bootstrap5_float`) corrige les sites existants ; le seed livre désormais `float-end`.
  - Et les dernières classes mortes des vues : la barre de débogage sur téléphone, la gouttière des
    listes d'événements, les étiquettes de l'éditeur de menu, les deux barres du vote de recrutement
    — qui sortaient de la **même couleur**, le graphique ne distinguant plus les avis favorables des
    défavorables.

- **Le corps d'un article pouvait être effacé à l'affichage** (2026-09-21). La construction du
  sommaire remplaçait le texte par le résultat d'un traitement qui peut abandonner sur un article long :
  la page répondait alors normalement, avec un article **vide**, sans rien dans les journaux.

- **Un widget placé en haut ou en bas de page disparaissait.** Le thème *Nebula*
  proposait les emplacements « Header » et « Footer » dans l'éditeur de mise en page, mais ne les
  affichait pas. Le widget était bien enregistré, et rien n'apparaissait — sans message. Les quatre
  autres thèmes publics n'étaient pas touchés.

- **Le widget de navigation proposait deux modules internes** (`live_editor` et `pages`) qu'il aurait dû
  masquer. Le même défaut faisait perdre leur nom de module aux statistiques de l'administration, sans
  effet visible tant que deux modules n'emploient pas le même nom de statistique.

- **Le journal de débogage grossissait sans limite** quand il était activé. Il est désormais borné :
  au-delà de 64 Mo, il repart en conservant la génération précédente.

- **Sur un site en anglais, « Non » restait en français** au lieu de « No ».

- **Cinq pages d'administration n'offraient aucun retour** (2026-09-20) : la fiche d'un membre, le
  journal d'audit, l'ajout d'un groupe, la liste des sessions et les fichiers du monitoring. Le fil
  d'Ariane ne rend le nom du module cliquable que si le module déclare avoir une page d'accueil
  d'administration ; `user` et `monitoring` déclaraient le contraire alors qu'ils en ont une. Une fois
  arrivé sur ces pages, il fallait passer par le menu latéral ou le bouton du navigateur.

- **Les moteurs de recherche recevaient du JSON à la place du plan du site.** Demandé à la racine,
  sans préfixe de langue — la seule façon dont un robot les demande —, `/sitemap.xml` répondait
  `{"redirect":"/fr/sitemap.xml"}` avec un code 200, et `robots.txt` comme `humans.txt` faisaient de
  même. La redirection qui ajoute le préfixe de langue répondait en JSON dès que l'adresse finissait
  par `.txt`, `.xml` ou `.json`. Ces fichiers n'ayant pas de version par langue, ils ne passent
  plus par cette redirection : ils sont servis directement, avec leur vrai type de contenu.

- **`/favicon.ico` répondait 404.** Les navigateurs demandent cette adresse quoi qu'annonce la page ;
  elle mène désormais au favicon configuré dans les réglages, ou à celui du CMS.

- **Activer la double authentification plantait.** Au moment d'enregistrer les codes de récupération,
  une erreur interrompait la page : le compte se retrouvait marqué « protégé » sans aucun code de
  secours. Trouvé par un test écrit en durcissant le typage de cette bibliothèque ; corrigé, et le test
  reste.

- **Une limitation « une seule tentative » ne bloquait jamais.** Le seuil n'était vérifié qu'à partir
  de la deuxième tentative. Sans effet sur les seuils livrés, mais la limite de signalements de la
  modération, réglable jusqu'à 1, ne bloquait alors jamais. Le seuil vaut désormais dès la première.

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
  tous les thèmes. Si vous créez un thème, vous pouvez écrire
  `if ($zone = $this->output->region('banner'))` (ou `zone(n)`) : la condition dit désormais la vérité.

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
  lui seul : l'adresse où chercher les nouvelles versions n'était réglée nulle part, si bien que le bouton
  n'apparaissait jamais ; un interrupteur global, à poser dans la configuration, bloquait l'opération ; le
  téléchargement visait les versions du NeoFrag d'origine, qui auraient écrasé NeoFrag Reborn ; le paquet
  rangeait ses fichiers dans un sous-dossier, si bien qu'une mise à jour n'aurait **rien remplacé, sans la
  moindre erreur** ; et l'extraction de l'archive passait par une interface de PHP dépréciée depuis
  PHP 8.0.

  L'interrupteur est remplacé par quatre garanties : la source des versions doit figurer dans une **liste
  d'adresses autorisées** (la même que pour le marketplace) ; le fichier de version ne donne qu'un **nom de
  fichier**, jamais une adresse ; l'empreinte **SHA-256** du paquet est vérifiée **avant** de toucher au
  moindre fichier ; chaque fichier de l'archive est contrôlé (aucun chemin qui sort du site, aucun lien
  symbolique). Les dossiers `config/` et `install/` ne sont jamais réécrits s'ils existent déjà.

- **Le catalogue du marketplace était injoignable depuis n'importe quel site.** Le serveur qui le
  distribue renvoyait `{"redirect":"/fr/…"}` **avec un code 200** au lieu du fichier statique — un corps
  parfaitement valide en JSON, et parfaitement faux, donc un échec silencieux qui faisait retomber chaque
  site sur son catalogue local. La cause était la configuration de ce serveur, corrigée : rien à faire sur
  les sites. Côté code, la mise à jour du cœur vérifie désormais la forme de ses manifestes, et plus
  seulement qu'ils se lisent comme du JSON.

- **Le bouton « Supprimer » d'un thème**, dans « Thèmes & addons », ne protégeait que le thème
  d'administration, par son nom écrit en dur. Il suit maintenant la déclaration du thème, et protège donc
  aussi Nebula.

- **Couplages du cœur vers l'optionnel.** Sept modules interrogeaient les tables de modules optionnels :
  cinq du cœur (`admin`, `moderation`, `settings`, `statistics`, `user`), plus `games` et `teams`. Deux
  requêtes n'étaient pas protégées : dans `teams`, la garde d'existence de `recruits` était placée
  **après** la requête qu'elle devait protéger, et dans `games`, supprimer un jeu interrogeait la table des
  équipes sans vérifier qu'elle existe. Les autres étaient gardés mais muets : ils portent désormais une
  annotation vérifiée par la CI. Le seul cycle dur du paquet (`games` ↔ `teams`) est rompu.

- **Live Editor : les modifications s'affichent sans recharger la page.** Supprimer un widget le laissait à
  l'écran, ajouter une ligne ne la montrait pas — jusqu'au rechargement, alors que le serveur avait bien
  enregistré. Le script attendait une réponse d'un autre format que celle du serveur ; le défaut datait du
  retrait de jQuery.
- **Live Editor : le glisser-déposer des widgets fonctionne de nouveau.** Quand le mode Colonnes était
  actif, un widget ajouté restait collé en bas de sa colonne, impossible à remonter au-dessus du contenu de
  la page. Les lignes et les colonnes n'étaient pas touchées.
- **Live Editor : les widgets sans réglages s'ajoutent de nouveau.** Dix widgets qui n'ont pas d'écran de
  réglages (`breadcrumb`, `copyright`, `downloads`, `forum`, `module`, `news`, `newsletter`, `slider`,
  `surveys`, `teams`) ne pouvaient pas être ajoutés : l'ajout échouait sur une erreur 404, sans message
  utile.
- **Une erreur du serveur n'est plus affichée comme un contenu.** Quand une action faite sans recharger la
  page recevait une erreur du serveur (une page 404, par exemple), certains écrans inséraient cette page
  d'erreur au milieu de l'interface. L'erreur est désormais traitée comme un échec.
- **Live Editor : le formulaire de réglages correspond toujours au widget choisi.** En changeant vite de
  widget ou de type, la réponse la plus lente pouvait afficher le formulaire d'un autre widget. La demande
  dépassée est désormais annulée, et le formulaire n'est plus rechargé (ni la saisie en cours perdue) quand
  la sélection n'a pas changé.
- **Le port de la base de données est pris en compte.** Le CMS se connectait toujours au port 3306, quel
  que soit le port saisi dans l'assistant d'installation : impossible de l'installer ou de le faire tourner
  sur un serveur MySQL ou MariaDB qui écoute ailleurs. Le port est désormais enregistré dans
  `config/db.php` (seulement s'il diffère de 3306) et employé à chaque connexion.
- **Un thème activé n'est plus vide.** Activer Forge, Granite, Blockcraft ou Extend changeait le thème sans
  poser sa mise en page par défaut, qui ne s'appliquait qu'avec le bouton « Réinstaller par défaut » : le
  site s'affichait sans aucun widget. L'activation pose désormais la mise en page par défaut du thème s'il
  n'en a pas encore, sans jamais écraser une mise en page personnalisée.
- **Changer le thème par défaut s'applique aussi aux visiteurs qui en avaient choisi un.** Le sélecteur de
  thème du pied de page retenait le choix d'un visiteur pendant un an, et ce choix l'emportait sur le thème
  par défaut : quand l'administrateur changeait de thème, les visiteurs, et lui-même, restaient sur
  l'ancien, sans moyen d'en sortir depuis un thème sans sélecteur. Un choix fait avant le dernier
  changement du thème par défaut est désormais oublié ; un choix fait après reste respecté.
- **Le navigateur ne garde plus de copie périmée des pages.** Faute d'instruction, il pouvait réafficher
  une ancienne version d'une page : un changement de thème, par exemple, n'apparaissait qu'après un
  rechargement forcé (Ctrl+F5). Les pages et réponses du site interdisent désormais cette mise en cache
  (`Cache-Control: no-store`) ; les images, styles et scripts, servis directement par le serveur web, ne
  sont pas concernés.

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
  avait aucune et retombait sur la règle générale (`default-src 'self'`). Elle autorise désormais les
  médias du site lui-même et ceux intégrés à la page (`'self' data:`) ; l'adresse d'un flux de webradio
  n'y est ajoutée **que si un flux est configuré** : un site sans webradio n'autorise aucune origine
  extérieure de plus.

- **Onze avis de sécurité fermés sur les dépendances** (2026-09-20) : `league/commonmark` passe de
  **2.8.2 à 2.10.1** (dix avis, dont huit graves) et `phpseclib` de **3.0.52 à 3.0.57** (un avis moyen).

  Neuf des dix avis de commonmark sont des **dénis de service par Markdown fabriqué** — analyse en temps
  quadratique, titres dont les ancres entrent en collision, notes de bas de page dupliquées, blocs d'attributs
  adjacents, sortie XML profondément imbriquée — et le dixième est une **faille XSS** : le filtre des
  attributs `on*` se contournait avec un saut de page U+000C. La bibliothèque rend le Markdown du wiki,
  des articles et de la FAQ, écrit par des membres. Plusieurs de ces avis visent des extensions que le
  produit n'active pas (notes de bas de page, attributs, ancres de titres, sortie XML) ; la mise à jour les
  ferme tous.

  Le rendu ne change pas, et ce n'est pas une impression : **douze cas représentatifs** — titres,
  tableau, liste de tâches, bloc de code, HTML injecté, lien `javascript:`, citation, texte barré,
  lien automatique, listes imbriquées, image, entités et note de bas de page, imbrication profonde —
  rendus par les deux versions avec la configuration réelle du produit donnent le même HTML
  **octet pour octet** (1 568 octets, même empreinte).

  À noter : Composer **refuse désormais d'installer** la 2.8.2, précisément à cause de ces dix
  avis. Il a fallu désactiver la politique dans un dossier jetable pour mener la comparaison.

## [1.1.0] — 2026-08-23

### Sécurité
- **Revue de sécurité de tout le code**, et correction des failles graves qu'elle a relevées :
  - **Sauvegardes** : les archives de sauvegarde (copie de la base et du dossier `config/`, qui contient les mots de
    passe) ne sont plus téléchargeables par une simple adresse web, sous Apache comme sous nginx ; seule
    l'administration (Monitoring) les remet. Leur nom contient désormais une partie aléatoire, impossible à deviner. Le
    dossier des journaux (`logs/`) est protégé de la même façon.
  - **Jetons secrets** : identifiants de session, jetons des formulaires et liens envoyés par e-mail (mot de passe
    oublié, validation du compte) sont tirés d'un générateur aléatoire sûr ; ils étaient prévisibles. Ces liens expirent
    au bout d'une heure, et une nouvelle demande annule la précédente.
  - **Connexion** : la double authentification (2FA) et le bannissement sont vérifiés quelle que soit la façon de se
    connecter — mot de passe, compte Discord, Google ou GitHub, lien « mot de passe oublié » —, et plus seulement avec
    le mot de passe.
  - **Liens dans les e-mails** : l'adresse du site utilisée dans les liens envoyés par e-mail, au retour d'une connexion
    par Discord, Google ou GitHub et au retour d'un paiement Stripe est celle enregistrée à l'installation
    (`config/url.php`), et non plus celle que la requête annonce : un attaquant ne peut plus faire envoyer un lien qui
    mène chez lui.
  - **Pages d'administration** : un nom de pièce jointe piégé (dans un signalement de modération) ou une fausse adresse
    IP (en-tête `X-Real-IP`, dans l'historique des sessions) pouvaient y injecter du code ; ils sont vérifiés et
    neutralisés. Le message d'accueil d'un serveur de jeu, fourni par un service extérieur, est nettoyé avant affichage.
  - **Actions d'administration** : supprimer, activer ou désactiver, fermer, approuver, restaurer, purger… exigent
    désormais un jeton de sécurité dans les 23 modules qui n'en avaient pas. C'étaient de simples liens ou des
    formulaires sans jeton : un lien piégé, cliqué par un administrateur connecté, suffisait à déclencher l'action.
  - **Outils** : les scripts du dossier `tools/` ne s'exécutent qu'en ligne de commande, jamais depuis une adresse web ;
    à chaque modification, l'intégration continue vérifie qu'aucune dépendance PHP n'a de faille connue
    (`composer audit`).
  - **Boutons rendus muets par la politique de sécurité (CSP)** : la CSP stricte du site bloque les actions écrites dans
    le HTML (`onclick="…"`), si bien que ces boutons ne faisaient rien : bandeau des cookies, nombre de lignes des
    tableaux d'administration, confirmations de la corbeille et des révisions, nom du fichier joint dans la messagerie,
    actions groupées des mentions du forum, sélection d'un champ au clic, bouton de la fenêtre de suppression. Ils sont
    rebranchés autrement et fonctionnent aussi dans le contenu chargé sans recharger la page. Le choix du nombre de
    lignes appelait en outre jQuery, pourtant retiré.
- **Politique de sécurité du contenu (CSP) stricte** : servie par le site avec un jeton propre à chaque page, elle
  refuse les scripts écrits dans la page sans ce jeton (`unsafe-inline`), le code construit à la volée (`unsafe-eval`)
  et les objets intégrés (`object-src 'none'`) ; les scripts ne viennent plus que du site et d'une courte liste
  d'adresses, TinyMCE et CodeMirror étant désormais hébergés par le site.
- **Session renouvelée à la connexion** : l'identifiant de session change quand on se connecte ; un identifiant imposé
  avant la connexion ne donne plus accès à la session ouverte.
- **Secrets chiffrés en base** : le mot de passe SMTP et la clé de la double authentification (2FA) sont chiffrés
  (AES-256-GCM) ; une copie de la base ne les livre plus en clair. Une valeur existante est chiffrée au prochain
  enregistrement.
- **Webhooks** : un webhook ne peut plus viser le réseau interne du serveur (adresses privées, locales ou réservées
  refusées, redirections non suivies).

### Ajouté
- **Fichiers CSS et JavaScript toujours à jour** : chaque fichier est appelé avec sa date de modification (`?v=…`), si
  bien que le navigateur le recharge dès qu'il change — y compris un fichier remplacé par un thème ou envoyé à la
  main —, sans avoir à augmenter `nf_version_css` (qui reste utilisé quand le fichier est introuvable).
- **Marketplace** : la fenêtre « Mises à jour » de la page *Thèmes & Addons* signale qu'une nouvelle version de
  NeoFrag Reborn est disponible ; la mise à jour du cœur elle-même reste manuelle.
- **Mises à jour des addons** : la même fenêtre compare les addons installés au catalogue du marketplace et installe les
  nouvelles versions, avec leurs migrations de base de données.
- **Installeur** : nouvelle présentation — logo NeoFrag Reborn, bandeau « Fork non officiel de NeoFrag », crédit à
  Michaël BILCOT et Jérémy VALENTIN (licence LGPLv3) en pied de page, polices Inter et Space Grotesk, couleurs
  turquoise.
- **Fichiers** : un gestionnaire de fichiers (arborescence de dossiers, envoi, droits de lecture par fichier ou par
  dossier), porté de HiddenCMS, de HiddenBlob (LGPLv3). Livré avec le cœur.
- **Émojis personnalisés** (module Emojis, livré avec le cœur) : l'administration ajoute des images nommées, que `:nom:`
  affiche dans les messages et signatures du forum, les actualités, les pages, les événements et le recrutement, entre
  autres.
- **Newsletter** : envoi programmé à la date choisie, par petits lots traités par la tâche planifiée du site (cron) au
  lieu de tout envoyer d'un coup ; suivi des ouvertures (taux d'ouverture par campagne, mesuré par une image
  invisible) ; modèles d'e-mail réutilisables ; choix des destinataires : tous les abonnés, les membres seulement ou un
  groupe.
- **Événements** : un événement peut se répéter chaque jour, chaque semaine ou chaque mois, pour le nombre de fois
  choisi — chaque date devient un événement à part entière. Les participants, sauf ceux qui ont décliné, reçoivent un
  rappel sur le site (cloche des notifications) avant l'événement, 24 heures avant par défaut ; les rappels passent par
  la tâche planifiée du site (cron).
- **Réactions par émoji** : six réactions au choix (👍❤️😂😮😢😡) au lieu d'un seul cœur, comme sur Discord ou Facebook, avec
  le compte de chacune ; les anciens « j'aime » deviennent des ❤️.
- **Widget « Statut live »** (l'ancien widget Twitch) : suit plusieurs chaînes à la fois, sur Twitch et sur YouTube
  (identifiants Twitch et clé d'API YouTube à fournir), avec jeu, spectateurs, titre, miniature et lecteur intégré.
- **Pages composées** : sous son texte, une page peut afficher des blocs d'autres modules (dernières actualités,
  actualités d'une catégorie, derniers articles, téléchargements populaires), placés dans l'ordre voulu et réglés un à
  un depuis la modification de la page. Le code court accepte aussi des réglages, par exemple
  `[block:news.latest count=2]`.
- **Thème Extend**, de Chewbaka, porté sur NeoFrag Reborn (Bootstrap 5, modes jour et nuit), sous licence
  CC BY-NC-SA 4.0 ; disponible dans le marketplace.
- **Recherche instantanée** : le widget de recherche propose des résultats dès la deuxième lettre (actualités, forum,
  pages).
- **Tri des listes d'administration** : dix listes (actualités, articles, petites annonces, téléchargements, FAQ, livre
  d'or, liens, médias, sondages, wiki) se trient selon le critère choisi.
- **Avatars animés** : un GIF animé envoyé comme avatar reste animé — toujours si le serveur dispose d'Imagick, sinon
  tant qu'il ne dépasse pas 250 × 250 pixels.
- **Permissions** : la grille des permissions s'ouvre dans une fenêtre, sans quitter la page.
- **Gestionnaire de fichiers du webmaster** (Monitoring, bouton « Gérer les fichiers ») : parcourir les fichiers du
  site, les modifier dans un éditeur (CodeMirror), en créer, en renommer ou en supprimer. Les dossiers `config/`,
  `logs/` et `backups/` restent hors d'atteinte ; chaque enregistrement garde une copie `.nfbak` et entre au journal
  d'audit. Toute écriture exige le **mot de passe webmaster**, distinct de celui du compte et gardé hors de la base
  (défini à l'installation ou dans Monitoring, carte « Sécurité webmaster ») ; une fois défini, il est aussi redemandé
  pour supprimer ou purger des sauvegardes et pour changer la clé de la tâche planifiée.
- **Notes de version tirées d'une seule source** : `tools/changelog-section.php` extrait une version de ce journal
  (`CHANGELOG.md`), en Markdown ou en HTML ; les notes des releases GitHub en sont tirées, et leurs textes ne peuvent
  plus diverger.

### Modifié
- **Installeur « tout compris »** : l'assistant passe à quatre étapes (Prérequis → Base de
  données → Administrateur → Terminé). Tous les modules, widgets et thèmes livrés sont installés et activés à l'étape
  « Base de données », et la page d'accueil affiche les actualités dès la fin de l'installation ; l'étape « Modules » et
  ses profils disparaissent.
- **Bootstrap 4.6.2 → 5.3.8, et jQuery n'est plus chargé** : le JavaScript du site s'appuie sur un petit outil maison
  (`window.NF`). Neuf extensions jQuery ou Bootstrap 4 sont remplacées (notifications → toasts de Bootstrap 5,
  selectize → Tom Select, sélecteur de date → flatpickr, FullCalendar 3 → 6, sélecteurs de couleur et d'icône,
  arborescence et jauges → code maison, barres de défilement → défilement natif), et jQuery UI par SortableJS. Quatre
  scripts oubliés appelaient encore jQuery : ils sont réécrits en 1.2.0.
- **Mode sombre** : tableaux, cartes, menus déroulants et éditeur de texte prennent les couleurs sombres du thème au
  lieu de rester blancs, dans tous les thèmes.
- **Réglages des widgets et disposition des pages** : enregistrés en JSON au lieu du format de sérialisation de PHP, ce
  qui ferme une voie d'injection de code ; les anciens réglages restent lus.
- **Graphiques des statistiques** : Chart.js (licence MIT) remplace Highstock, une bibliothèque commerciale qui
  interdisait de redistribuer librement le CMS.

### Corrigé
- **MySQL 8** : ouvrir un sujet ou répondre sur le forum, scinder ou fusionner des sujets, créer une conversation dans
  la messagerie, réordonner les images du Slider… échouaient par une erreur fatale sur MySQL 8, qui refusait la façon
  dont le site ouvrait ses transactions. Elles passent désormais par les fonctions prévues de mysqli.
- **Émojis** : un émoji — ou tout caractère codé sur 4 octets — dans un texte provoquait une erreur de base de données.
  La connexion et toutes les tables passent en `utf8mb4` (interclassement `utf8mb4_unicode_ci`, reconnu par MySQL 8
  comme par MariaDB 10 ; les interclassements propres à MariaDB 11, qui faisaient échouer l'installation ailleurs,
  disparaissent).
- **`where('col', [])`** générait une condition vide (→ `DELETE`/`UPDATE` sur toute la table) : produit
  désormais `1 = 0` (ensemble vide).
- **Dates au-delà de 2038** : l'envoi programmé d'une newsletter, la publication programmée d'une actualité, d'un
  article, d'une galerie ou d'une page, et les dates d'un événement ne pouvaient pas dépasser le 19 janvier 2038 :
  au-delà, MariaDB refusait l'enregistrement, sans aucun message. Ces dates vont désormais jusqu'en l'an 9999 ; les
  installations existantes sont mises à jour par migration.
- **`/user/login` et `/user/registration` répondaient « page introuvable »** : les boutons « Connexion » et
  « Inscription » des thèmes y mènent quand JavaScript est désactivé ou que le lien s'ouvre dans un nouvel onglet. Ces
  adresses ouvrent désormais la fenêtre de connexion ou d'inscription.
- **Nebula** : le bouton « Inscription » restait affiché quand les inscriptions étaient fermées, et menait à une page
  introuvable. Il est masqué dans ce cas.
- **Widget Discord** : il donne la vraie cause d'un échec (widget désactivé sur le serveur, identifiant introuvable,
  réseau) au lieu d'un message général, montre le vrai logo du serveur (lu dans l'invitation publique) et compte juste
  les membres en ligne — la liste fournie par Discord s'arrête à 100, ce qui faussait le compte des grands serveurs.
- **Widget TeamSpeak, affichage en arbre** : il plantait sous PHP 8 et retombait sur la carte simple. L'arbre des canaux
  et des clients est désormais dessiné par le widget lui-même, avec des icônes Font Awesome (plus besoin de pack
  d'icônes) ; une erreur réseau affiche un message général, sans révéler l'adresse ni le port du serveur.
- **Widgets Discord, Twitch et Serveur de jeu** : ils affichaient « inaccessible » même quand le service répondait (la
  réponse était décodée deux fois). Les requêtes du site envoient aussi un identifiant de navigateur (User-Agent) : sans
  lui, les services protégés par Cloudflare répondaient par un refus (erreur 403).
- **Sauvegardes et Monitoring** : créer une sauvegarde plantait sur les serveurs en PHP-FPM, et échouait sous nginx ou
  Plesk, qui répondaient « page introuvable » aux adresses en `.json` ; supprimer ou télécharger une sauvegarde menait à
  une page introuvable ; l'arborescence du Monitoring affichait une erreur au premier rafraîchissement. Tout est
  corrigé.
- **Marketplace injoignable** : URL par défaut passée en **non-www** (`https://neofrag-reborn.xyz/marketplace`).
- **Envoi de plusieurs fichiers** : le premier fichier d'un envoi multiple faisait échouer l'envoi
  (« page introuvable ») — c'est ce qui bloquait l'envoi dans le module Fichiers. Pour les développeurs :
  `uploaded_file()` prenait l'indice `0` pour une absence d'indice.
- **Webhooks** : l'inscription d'un membre (`user.registered`), un nouveau commentaire (`comment.created`) et un nouveau
  sujet du forum (`forum.topic`) déclenchent enfin leur webhook ; ces événements étaient proposés mais jamais émis.

### Documentation
- **Documentation** (guides du wiki) : pour les développeurs, les formulaires (`form()` et `form2()`) et la validation
  des réglages d'un widget ; pour les administrateurs, le dépannage de l'installation, un exemple de création de rôle et
  que faire quand le marketplace est injoignable. Une note explique comment régler nginx ou Plesk, qui répondaient
  « page introuvable » aux adresses en `.json`.

---

## [1.0.0] — 2026-06-06 · socle Reborn (base Alpha 0.2.4)

Premier cycle du fork : modernisation du socle, durcissement de la sécurité et large vague de
fonctionnalités.

### Ajouté

**Plateforme & outillage**
- **Migrations de base de données** numérotées (`tools/migrate.php`, avec une commande `baseline` pour reprendre une
  base existante) et premiers tests automatisés (PHPUnit).
- **Nouvel installeur web**, qui remplace celui de NeoFrag d'origine : un assistant en cinq étapes (Prérequis → Base de
  données → Modules → Administrateur → Terminé). L'étape « Modules » propose trois profils de site (Communauté,
  Gaming / eSport, Site simple) et peut ajouter des addons depuis le marketplace.
- **Marketplace distant** (catalogue et archives servis par neofrag-reborn.xyz) : l'installeur (étape « Modules ») et
  l'administration (bouton « Marketplace » de la page *Thèmes & Addons*) y ajoutent en un clic les addons du catalogue
  officiel qui ne sont pas livrés avec le site. Sécurité : HTTPS obligatoire, empreinte SHA-256 vérifiée, archive
  contrôlée entrée par entrée (aucun chemin qui sorte du dossier de l'addon, aucun lien symbolique), adresse du
  catalogue fixe, tailles et délais bornés.
- **Outils de fabrication des paquets** : `package-addons` prépare le catalogue du marketplace, `build-release` les
  archives à déposer par FTP.
- **Script de maintenance** (`tools/maintenance.php`, à lancer par une tâche planifiée) : vide la corbeille de ce qui y
  dort depuis plus de 30 jours (réglable) et supprime les comptes jamais confirmés au bout de 7 jours, quand la
  validation des inscriptions est activée — jamais un administrateur.

**Thèmes & interface**
- **Quatre thèmes créés pour NeoFrag Reborn** : Nebula, pensé pour les sites de communauté, Forge, Blockcraft et
  Granite. Des variables de couleur communes (`--nf-*`) habillent modules et widgets aux couleurs de chaque thème.
- **Refonte complète de l'administration** : nouvelle présentation, barre latérale, palette de commandes (Ctrl+K), modes
  clair et sombre.
- Sélecteurs de **thème** et de **langue** en pied de page (visiteurs inclus).
- **Menus** : constructeur de menus à plusieurs niveaux, utilisable dans le widget Navigation (option « Menu géré »).

**Contenu**
- **Actualités et articles** : publication programmée — le contenu paraît à l'heure choisie, et les notifications,
  webhooks et points ne partent qu'à ce moment-là, plus à l'enregistrement (adresse à appeler par une tâche planifiée,
  protégée par une clé) ; actions groupées ; recherche, filtres et pagination dans l'administration ; une page dédiée
  pour lire une actualité. Les articles reçoivent une image à la une, comme les actualités, et le compteur de vues des
  actualités, qui n'augmentait jamais, compte enfin.
- **Publication programmée** aussi sur pages, galeries et événements (date de parution distincte de la date de tenue pour les événements).
- **Wiki** : sert de documentation sur le site (sommaire en cartes, rendu Markdown fiable) ; recherche et pagination ;
  comparaison visuelle de deux révisions d'une page, ligne à ligne.
- **Médias** : titre et description modifiables pour chaque fichier, recherche, filtre par type et pagination.
  Recherche, filtres et pagination aussi dans l'administration des Téléchargements, de l'Annuaire de liens, de la FAQ,
  des Sondages et du Livre d'or.
- Flux **RSS 2.0** (news + articles), boutons de **partage** social, **SEO** (meta description, canonical, Open Graph, Twitter Card, meta par page).

**Communauté & engagement**
- **Centre de notifications** sur le site (cloche et nombre de non-lues), avec **abonnements** (suivre un contenu ou une
  catégorie). Elles signalent les commentaires, les réponses du forum, les mentions @pseudo, les messages privés et les
  invitations à un événement.
- **Réactions** « j'aime » sur les actualités, articles, commentaires et messages du forum ; **révisions** des contenus
  (historique et restauration) ; widget « Derniers commentaires ».
- **Corbeille** commune : actualités, articles, galeries, commentaires et messages du forum supprimés y passent, et
  peuvent en être restaurés.
- **Forum** : catégorie réservée aux membres VIP, image par catégorie.

**Gaming**
- **Événements** : gestion complète des adversaires (liste, modification, suppression ; NeoFrag d'origine permettait
  seulement d'en ajouter depuis un match), compte à rebours en direct, notification d'invitation.

**Monétisation & gamification**
- Karma/réputation → **points** (barème configurable) → **boutique** (paiement en points) → statut **VIP**.
- **Stripe** (achat de points et de formules VIP), **régie publicitaire** (masquée pour les membres VIP), **dons**
  (module et widget portés du « Donation v3 » de HiddenBlob, d'après majiid ; LGPLv3).

**Liens entre modules**
- **Statistiques** étendues à 19 modules (NeoFrag d'origine en couvrait 3 : commentaires, forum, membres) ; **mur
  d'activité** sur le profil, qui rassemble l'activité d'un membre dans tous les modules ; tableau de bord
  **« À traiter »** dans l'administration ; **blocs de page** : le code court `[block:…]` insère dans une page un bloc
  d'un module (actualités, articles, téléchargements).

**Webhooks & audit**
- **Webhooks** sortants signés (HMAC), gérés depuis l'administration. Le **journal d'audit** enregistre aussi
  l'installation et la désinstallation d'un addon, l'activation d'un thème ou d'un module et l'enregistrement des
  réglages.

**Administration des comptes**
- **Membres** : export des membres en CSV ou JSON (RGPD) ; les modérateurs qui en ont le droit règlent la modération
  depuis leur espace membre. La modification et la suppression d'un membre depuis l'administration, déjà présentes dans
  NeoFrag d'origine, sont rebranchées sur la nouvelle liste des membres.

### Modifié
- Versionnage normalisé en **SemVer pur** dans les dépendances d'addons (retrait des libellés « Alpha »).
- **Pages** fait désormais partie du cœur ; **Actualités**, **Forum** et **Galeries** peuvent être désinstallés.
- **Éditeur de texte riche** : il fonctionne enfin — le HTML qu'il produit est nettoyé au lieu d'être affiché comme du
  texte.

### Retiré
- Les deux thèmes de NeoFrag d'origine, « Thème par défaut » (`default`) et Azuro, remplacés par les thèmes de
  NeoFrag Reborn.
- L'éditeur BBCode de NeoFrag d'origine (WysiBB) et la conversion du BBCode : l'éditeur de texte riche le remplace, et
  un texte écrit en BBCode s'affiche désormais tel quel.

### Corrigé
- **Dix défauts corrigés**, dont : les événements sans participant ni match n'apparaissaient pas ; modifier ou supprimer
  un mode de jeu échouait ; supprimer une offre de recrutement échouait ; le lien vers un partenaire menait à une page
  introuvable (il mène à son site) ; l'auteur d'un signalement n'était jamais montré, même aux modérateurs autorisés ;
  les widgets Galeries et Forum vérifiaient mal leurs réglages ou les droits. Les compteurs de vues ne comptent plus
  qu'une fois par visite et ignorent les robots.
- Un contenu programmé n'est plus visible par son adresse directe avant l'heure. Des boutons sans effet fonctionnent :
  périodes des statistiques, activation des modèles d'e-mail, réseaux sociaux (les six s'affichent), boutons « Ajouter »
  des actualités, pages et galeries.
- **Thèmes** : contrastes des boutons relevés au niveau WCAG AA, champs de connexion soudés à leur icône, alignements
  corrigés.
- **Affichage sur petits et grands écrans** (vérifié de 390 à 2 560 pixels de large) : dans l'administration, menu
  latéral en tiroir et contenu en pleine largeur sur téléphone ; profil membre ; barre du compte compacte dans Nebula,
  Forge, Blockcraft et Granite.
- Les pages Membres, Statistiques et Live Editor de l'administration affichaient une erreur 500 ; des compteurs
  restaient bloqués à 1 (pastille de modération, abonnés de la newsletter, statistiques des médias), et une catégorie
  vide de Téléchargements, de FAQ ou de liens ne pouvait pas être supprimée. (Pour les développeurs : erreurs de typage
  strict dans `timetostr()` et les helpers ; `(int)` sur `row(FALSE)` d'un `COUNT`/`SUM` valait toujours 1.)

### Sécurité
- Le HTML envoyé par les membres est nettoyé côté serveur (HTMLPurifier) contre l'injection de code ; un fichier envoyé
  est vérifié d'après son contenu réel, pas seulement son extension ; HSTS activé (le navigateur ne revient plus en
  HTTP).
- **Jetons anti-CSRF** comparés en temps constant. **Adresse IP** : la limitation de débit et la liste des IP bannies ne
  croient plus les en-têtes falsifiables (`X-Forwarded-For`…), sauf proxy de confiance déclaré. **Droits** : contrôles
  de permission ajoutés là où ils manquaient (Livre d'or, Jeux, Galeries, Événements, Statistiques).
- Les anciens scripts de mise à jour ne peuvent plus recréer n'importe quel objet PHP en relisant des réglages
  (`unserialize` limité par `allowed_classes`).
- **Empreinte de session** contre le vol de session : un membre connecté est déconnecté si son navigateur (user-agent)
  change nettement en cours de session — une simple mise à jour du navigateur ne suffit pas. L'adresse de parution
  programmée est protégée par une clé comparée en temps constant.
