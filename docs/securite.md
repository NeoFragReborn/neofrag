# La sécurité de NeoFrag Reborn

Ce que fait le CMS pour se protéger, ses limites connues, et ce qu'il attend de l'hébergement. Pour
**signaler une faille**, jamais d'issue publique : la [politique de sécurité de
l'organisation](https://github.com/NeoFragReborn/.github/blob/main/SECURITY.md) dit comment le faire en
privé, et quelles versions reçoivent des correctifs.

## Ce que fait le CMS

- **Injection** : les données passent par le constructeur de requêtes (requêtes paramétrées) ;
  `sanitize_html` (HTMLPurifier) nettoie tout HTML enregistré, contre le XSS stocké.
- **Jetons** : identifiant de session, jetons CSRF et liens de réinitialisation ou de validation tirés
  d'un générateur cryptographique (`random_bytes`) ; ces liens servent une fois et expirent (1 h).
- **CSRF** : un jeton par formulaire, et par lien d'action de l'administration, comparé en temps
  constant (`hash_equals`).
- **Authentification** : double authentification (TOTP) et bannissement appliqués sur tous les chemins
  de connexion (mot de passe, OAuth, réinitialisation) ; mots de passe hachés en **Argon2id** — un
  ancien hachage hérité de NeoFrag est converti à la connexion suivante.
- **Envois de fichiers** : le type réel est lu dans le contenu (*magic bytes*, extension PHP `fileinfo`)
  **et** une liste d'extensions est refusée d'office (scripts, exécutables, HTML, SVG) ; l'extension
  écrite sur le disque est contrôlée à part, car un fichier peut être à la fois une image et un script.
- **Sessions** : empreinte de l'agent (contre le détournement d'un cookie), `unserialize` borné par
  `allowed_classes`, adresse IP entrante validée (`FILTER_VALIDATE_IP`).
- **Adresses dans les e-mails** : construites sur l'origine du site fixée à l'installation
  (`config/url.php`), jamais sur l'en-tête `Host` de la requête.
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
  heure), journal d'audit des actions sensibles, mode démonstration qui verrouille l'écriture.
- **Données personnelles** : l'historique des connexions (adresse IP, agent, date) est purgé au-delà de
  treize mois — durée réglable dans *Paramètres*, `0` pour ne jamais purger.

## Limites connues

- La politique de contenu garde `'unsafe-inline'` sur **`style-src`** (styles en ligne de Bootstrap 5
  et de TinyMCE), et accepte toute origine `https:` pour les images, les polices et les connexions
  (avatars distants, polices Google, widgets Steam et Twitch).

## Ce que le CMS attend de l'hébergement

Garder PHP et les dépendances à jour ; servir en **HTTPS** ; ne jamais versionner ni partager les
secrets de `config/`, écrits à l'installation (`db.php`, `crypt.php`, `password.php`, `url.php`,
`webmaster.php`, `email.php`) ; configurer un envoi d'e-mail fiable (serveur SMTP dans *Paramètres →
E-mail*, ou agent de messagerie local) avec une **adresse de contact valide** — sans quoi le formulaire
de contact et les e-mails d'inscription échouent en silence pour le visiteur ; installer la tâche
planifiée (*Monitoring* en donne la ligne) pour les publications programmées et les rappels.
