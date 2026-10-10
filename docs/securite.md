# La sécurité de NeoFrag Reborn

Ce que fait le CMS pour se protéger, ses limites connues, et ce qu'il attend de l'hébergement. Pour
**signaler une faille**, jamais d'issue publique : la [politique de sécurité de
l'organisation](https://github.com/NeoFragReborn/.github/blob/main/SECURITY.md) dit comment le faire en
privé, et quelles versions reçoivent des correctifs.

## Ce que fait le CMS

- **Injection** : les données passent par le constructeur de requêtes (requêtes paramétrées) ;
  `sanitize_html` (HTMLPurifier) nettoie tout HTML enregistré, contre le XSS stocké.
- **Jetons** : identifiant de session, jetons CSRF et liens de réinitialisation ou de validation tirés
  d'un générateur cryptographique (`random_bytes`). Un lien envoyé par e-mail sert une fois, ne vaut que
  pour son usage (le lien « mot de passe oublié » n'ouvre pas la validation d'une inscription) et expire :
  une heure pour choisir un nouveau mot de passe, deux jours pour valider une inscription ou confirmer une
  nouvelle adresse.
- **CSRF** : un jeton par formulaire, et par lien d'action — de l'administration comme du site (suivre un
  sujet, quitter une conversation, se déconnecter…) —, comparé en temps constant (`hash_equals`). Un envoi
  (POST, PUT, DELETE…) venu d'un autre site est refusé avant toute route, d'après ce que dit le navigateur
  (`Sec-Fetch-Site`, à défaut `Origin`) ; les actions que le JavaScript du site appelle n'acceptent que
  POST. Une déconnexion sans jeton se confirme par un bouton.
- **Authentification** : double authentification (TOTP) et bannissement appliqués sur tous les chemins
  de connexion (mot de passe, OAuth, réinitialisation) ; mots de passe hachés en **Argon2id** — un
  ancien hachage hérité de NeoFrag est converti à la connexion suivante.
- **Essais** : les erreurs de connexion se comptent par compte — quelle que soit la façon de l'écrire,
  pseudo ou adresse, majuscules ou accents — et par réseau : une adresse IPv6 compte pour son réseau /64.
  Le mot de passe est redemandé avant d'activer ou de couper la double authentification, de lier un
  service (Discord, GitHub, Google), de changer d'adresse ou de mot de passe et de supprimer son compte ;
  cinq erreurs bloquent cette confirmation un quart d'heure.
- **Mot de passe changé** : les autres sessions sont fermées, et les liens en attente tombent — une
  nouvelle adresse pas encore confirmée, un lien « mot de passe oublié ».
- **Comptes devinables** : « mot de passe oublié » répond la même chose qu'une adresse ait un compte ou
  non ; l'inscription ne dit qu'une adresse est déjà prise que cinq fois par heure et par réseau.
- **Envois de fichiers** : le type réel est lu dans le contenu (*magic bytes*, extension PHP `fileinfo`)
  **et** une liste d'extensions est refusée d'office (scripts, exécutables, HTML, SVG) ; l'extension
  écrite sur le disque est contrôlée à part, car un fichier peut être à la fois une image et un script.
  Une image collée ou glissée dans l'éditeur riche est réservée aux membres connectés (jeton de session,
  débit borné), refusée si elle porte du code, bornée en poids et en dimensions avant tout décodage, puis
  **ré-encodée** : seuls ses pixels sont écrits, sous un nom aléatoire. Les pièces jointes du forum et des
  conversations ne sont pas servies par le serveur web (`upload/forum/`, `upload/talks/` refusés) : le site les
  donne lui-même, à qui peut lire le sujet ou la conversation, une image ou un PDF à l'affichage, tout autre
  type au téléchargement, sous une politique de contenu qui n'autorise aucun script.
- **Sessions** : empreinte de l'agent (contre le détournement d'un cookie), `unserialize` borné par
  `allowed_classes`. En HTTPS, le cookie de session porte le préfixe `__Host-` : le navigateur ne l'accepte
  que posé par le site lui-même, pour tout le site, et ne l'envoie à aucun sous-domaine. L'adresse IP notée
  (historique des connexions, limites d'essais, liste de bannis) est
  celle de la connexion, jamais un en-tête que le navigateur choisit ; derrière un relais qui la
  transmet, on le déclare (`NEOFRAG_TRUSTED_PROXY` dans `config/neofrag.php`).
- **Adresses dans les e-mails** : construites sur l'origine du site fixée à l'installation
  (`config/url.php`), jamais sur l'en-tête `Host` de la requête.
- **Ce que le serveur va chercher lui-même** (relais d'images, flux RSS, webhooks) : des adresses publiques
  au sens strict — ni boucle locale, ni plage privée, réservée, partagée des opérateurs (100.64.0.0/10) ou de
  test, ni le serveur du site —, sur les ports 80 et 443, l'adresse vérifiée étant celle qu'on contacte, sans
  suivre de redirection.
- **Secrets de l'administration** : mot de passe SMTP, clé du bot Discord, clés Stripe — enregistrés
  chiffrés, jamais réaffichés (un champ laissé vide garde la valeur).
- **Fichiers sensibles** : `config/`, `backups/` (sauvegardes SQL et secrets), `logs/`, `install/`,
  `tools/`, `tests/` et `docs/` ne sont pas servis (`.htaccess` pour Apache, `nginx.conf`, `Caddyfile`
  — à reproduire si l'hébergeur sert par nginx sans lire `.htaccess`, cf.
  [deploy-ftp.md](deploy-ftp.md)).
- **En-têtes** : une politique de sécurité du contenu stricte, servie par le CMS, avec un nonce par
  requête (`script-src 'self' 'nonce-…'`, aucun script depuis un CDN ; l'origine de Google Analytics n'y
  entre que si un identifiant est configuré, et son script ne se charge qu'avec le consentement du
  visiteur). Les configurations de serveur livrées ajoutent `X-Frame-Options`, `X-Content-Type-Options`,
  `Referrer-Policy`, `Permissions-Policy`, et HSTS en HTTPS.
- **Navigateur** : aucun jQuery ; `check-js-sources` refuse tout appel qui reviendrait, et
  `check-js-console` ouvre chaque page d'administration dans un vrai navigateur et refuse toute erreur
  ou violation de la politique de contenu.
- **Marketplace et mise à jour du cœur** : origine validée par liste blanche (HTTPS, port 443, hôte
  autorisé, sans identifiants dans l'adresse) sur tous les chemins ; un manifeste qui ne porte qu'un nom
  de fichier ; empreinte **SHA-256** vérifiée avant toute écriture ; archive contrôlée entrée par entrée
  (aucune sortie de dossier, aucun lien symbolique) ; tailles et délais bornés ; sauvegarde SQL et
  fichiers avant d'écrire, **retour arrière automatique** si une étape échoue.
- **Abus** : limitation de débit par clé (par exemple trois messages de contact par adresse IP et par
  heure), journal d'audit des actions sensibles, mode démonstration qui verrouille l'écriture. Sur une
  démonstration, aucun e-mail ne part, et une publicité, un lien, un téléchargement, un partenaire ou un
  forum-lien qui mène hors du site s'affiche sur une page qui dit où il mène, au lieu d'y rediriger : tout
  visiteur y est administrateur, le domaine du projet ne doit pas servir à tromper.
- **Données personnelles** : l'historique des connexions (adresse IP, agent, date) est purgé au-delà de
  treize mois — durée réglable dans *Paramètres*, `0` pour ne jamais purger. Un compte sans visite depuis
  trois ans est effacé — un e-mail prévient son membre un mois avant, une visite annule tout ; durée
  réglable dans *Paramètres* (« Comptes inactifs »), `0` pour ne jamais effacer ; jamais un administrateur.

## Limites connues

- Le temps de réponse de « mot de passe oublié » peut encore trahir qu'un e-mail est parti, donc que
  l'adresse a un compte : la réponse elle-même est identique, son délai ne l'est pas toujours.
- La politique de contenu garde `'unsafe-inline'` sur **`style-src`** (styles en ligne de Bootstrap 5
  et de TinyMCE), et accepte toute origine `https:` pour les images, les polices et les connexions
  (avatars distants, polices Google, widgets Steam et Twitch). Les images acceptent aussi `blob:` :
  l'aperçu d'une image collée dans l'éditeur, le temps de son envoi — une telle adresse ne se fabrique
  que depuis la page elle-même.

## Ce que le CMS attend de l'hébergement

Garder PHP et les dépendances à jour ; servir en **HTTPS** ; ne jamais versionner ni partager les
secrets de `config/`, écrits à l'installation (`db.php`, `crypt.php`, `password.php`, `url.php`,
`webmaster.php`, `email.php`) ; configurer un envoi d'e-mail fiable (serveur SMTP dans *Paramètres →
E-mail*, ou agent de messagerie local) avec une **adresse de contact valide** — sans quoi le formulaire
de contact et les e-mails d'inscription échouent en silence pour le visiteur ; installer la tâche
planifiée (*Monitoring* en donne la ligne) pour les publications programmées et les rappels.
