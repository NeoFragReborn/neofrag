# Déploiement sur hébergement mutualisé (FTP)

NeoFrag Reborn s'installe sur n'importe quel hébergement **Apache + PHP 8.2+ + MySQL/MariaDB**
(type cPanel/Plesk), sans Docker ni Composer ni accès shell. Chaque version publie ses paquets prêts
à l'emploi :

| Paquet | Pour |
|--------|------|
| `neofrag-reborn-public-<v>.zip` | **ton site** — la distribution à installer |
| `neofrag-reborn-demo-<v>.zip` | un **site de démonstration** (actions sensibles verrouillées, remis à zéro par un cron) |
| `neofrag-reborn-update-<v>.zip` | la **mise à jour**, que le bouton de l'administration télécharge seul — pas à décompresser à la main |

Les deux premiers embarquent `vendor/` (aucun `composer install` requis) et le `.htaccess` (réécriture
d'URL).

## Prérequis hébergement

- PHP **8.2 à 8.5**, avec les extensions que liste le [guide d'installation](guide/installation.md#prérequis).
- Une base **MySQL/MariaDB** (créée depuis le panel, ou créée par l'installeur si les droits le permettent).
- Apache avec **`mod_rewrite`** et `AllowOverride All` (le `.htaccess` route vers `index.php`). Sur la
  plupart des mutualisés c'est actif par défaut ; sinon demander au support.
- **Hébergement nginx / Plesk** : le `.htaccess` n'est lu QUE par Apache → sous nginx il faut router les
  URLs vers `index.php` (sinon les appels AJAX en `.json` renvoient 404 : backup qui tourne sans fin,
  arbre d'intégrité du Monitoring vide…). Voir la note **« nginx / Plesk »** dans la section Notes.

## Installer un site — étapes

1. **Décompresser** `neofrag-reborn-public-<v>.zip` et **uploader** le contenu du dossier `neofrag-reborn/`
   à la racine web du domaine (ex. `public_html/` ou le docroot du sous-domaine).
2. **Droits d'écriture** (chmod 755 dossiers / 644 fichiers, puis rendre **inscriptibles** par PHP) :
   `config/`, `cache/`, `logs/`, `upload/`, `backups/`. (Ces dossiers sont créés au besoin ; le seul
   indispensable en écriture au départ est `config/`.)
3. Visiter **`https://<domaine>/install/`** → l'assistant en **cinq étapes** : prérequis (valeurs constatées) →
   **profil du site** (*Complet*, *Gaming / eSport*, *Communauté*, *Association / club*, *Cœur seul* ; modules décochables un par un,
   dépendances ajoutées d'office) → base de données → compte administrateur → fin. Il génère `config/db.php`
   + les secrets (`crypt.php`, `password.php`) + `config/url.php` (origine canonique du site, figée pour les
   liens d'e-mail), importe le schéma + le seed, installe les addons choisis, baseline leurs migrations, pose
   le verrou `install/db.txt`. **Installe par le nom de domaine, pas par l'IP** : l'adresse de contact du site
   en est dérivée (`noreply@<domaine>`), et une IP donnerait une adresse invalide.
4. Terminé : le site répond sur `https://<domaine>/` avec le thème **Nebula** par défaut. Passer par
   *Paramètres* pour vérifier l'adresse de contact et régler l'envoi d'e-mail (SMTP, ou agent local).
5. **(optionnel) Parution programmée** : ajouter un cron 5 min qui appelle l'endpoint de publication.
   **Copie l'URL exacte affichée dans Admin → Monitoring** (elle inclut le bon préfixe de langue) ;
   `-L` suit la redirection de langue si tu omets le préfixe :
   ```
   */5 * * * * curl -fsSL "https://<domaine>/monitoring/cron?key=<nf_cron_key>" >/dev/null 2>&1
   ```

## Site de démo — étapes

Identique à l'installation d'un site, avec en plus le **mode démo** (déjà activé : `config/neofrag.php`
contient `NEOFRAG_DEMO=TRUE`) et les **données de démo**.

1. Uploader le contenu de `neofrag-reborn-demo-<v>.zip` sur le **sous-domaine** (ex. `demo.<domaine>`).
2. Droits d'écriture (idem).
3. Visiter **`https://demo.<domaine>/install/`** → assistant (DB + compte admin). L'installeur **charge
   automatiquement `install/demo.sql`** à la fin : le site démarre **directement** sur le thème **nebula**,
   peuplé (membres + contenu de tous les modules), bandeau démo + compte `demo`/`demo` actifs. Ton compte admin (créé à l'install) est préservé. **Aucune étape manuelle.**
4. **Cron de reset** (recommandé : toutes les heures) — restaure l'état de démo, nettoie ce que les
   visiteurs ont posté, et **ramène le contenu au présent** : `install/demo.sql` porte en tête le jour où il a
   été écrit (`-- nf-demo-present: AAAA-MM-JJ`), et chaque remise à zéro fait avancer les dates du contenu du
   temps écoulé depuis — les prochains rendez-vous et matchs restent à venir, l'agenda et la frise de la saison
   ne se vident pas (les membres, eux, gardent leurs dates). **Clé + URL exacte dans Admin → Monitoring** ; `-L` suit la redirection de
   langue (le préfixe `/fr/` ci-dessous dépend de la langue par défaut du site) :
   ```
   0 * * * * curl -fsSL "https://demo.<domaine>/fr/monitoring/cron?key=<nf_cron_key>&demo=1" >/dev/null 2>&1
   ```

### Comportement du mode démo

- L'**administration est navigable**, mais les modules qui touchent à la configuration du site —
  réglages, accès, fichiers, Monitoring, clés d'API, paiements, Discord, éditeur en direct… — sont
  **verrouillés** : aucun envoi de formulaire, aucune action par lien (toast « Action désactivée sur le
  site de démonstration. »), et des **exemples fictifs** à la place des fichiers, journaux et réglages
  sensibles. Le **contenu** (actualités, forum, événements…) reste modifiable. L'envoi de fichiers est
  refusé et la suppression d'une image ne supprime rien. L'admin du site (créé à l'install) reste
  maître ; les secrets ne sont jamais touchés par le reset.
- Le **front reste interactif** : inscription, forum, commentaires, réactions… Tout cela est **éphémère**
  (nettoyé au prochain reset).
- Un **bandeau** « Démo — réinitialisée régulièrement · connexion : demo / demo » s'affiche partout.
  Compte de démonstration prêt à l'emploi : **`demo` / `demo`**.

## Mettre à jour un site déjà en ligne

**Par le bouton** (le plus simple) : quand une version sort, l'administration affiche « Mise à jour
disponible » sous le logo et dans *Système → Monitoring*. Le bouton sauvegarde le site, télécharge le
paquet de mise à jour, vérifie son empreinte, puis remplace le code.

**Par FTP**, si le bouton ne peut pas écrire sur l'hébergement :

1. **Sauvegarde** le site (*Système → Monitoring*, ou renomme l'ancien dossier).
2. **Décompresse** `neofrag-reborn-public-<v>.zip` et uploade le contenu de `neofrag-reborn/` par-dessus
   le site (écrase le code).
3. `config/{db,crypt,password,email}.php` ne sont pas dans le paquet → ils sont **conservés**, et un
   redéploiement n'écrase pas le SMTP configuré.
4. `cache/`, `logs/` et `upload/` doivent rester **inscriptibles**.
5. **Visite le site** : à la première page vue avec un code neuf, les migrations qu'il apporte
   s'appliquent seules, une fois. Un échec n'empêche pas la page de s'afficher : il est écrit dans le
   journal des erreurs, et la tentative suivante a lieu dix minutes plus tard.

**Vérification post-déploiement :**

- [ ] « Mot de passe oublié » → 200 et email reçu.
- [ ] Maintenance activée puis reconnexion → login OK.
- [ ] Une erreur quelconque → notification **rouge** (pas verte).
- [ ] Une page front + une page admin dans chaque thème actif.

> **SMTP** : sans hôte SMTP valide (Admin → Réglages → Email), l'envoi retombe sur `mail()` de PHP,
> souvent bloqué en mutualisé. Teste un vrai « mot de passe oublié » après configuration.

## Notes

- **HTTPS** : activer le certificat (Let's Encrypt). Le `.htaccess` pose HSTS quand HTTPS est actif. NeoFrag
  détecte HTTPS derrière un reverse-proxy/SSL terminé (Plesk/cPanel) via `X-Forwarded-Proto` — pas de
  configuration à faire.
- **Dossiers inscriptibles** : `config/` (install) ET surtout **`cache/`** doivent être inscriptibles par
  PHP — le CSS des thèmes est compilé dans `cache/` ; sans droits, le rendu est dégradé. Plus `logs/`,
  `upload/`, `backups/`.
- **Emails** : l'installeur crée `config/email.php` avec un **hôte SMTP vide** → NeoFrag envoie via la
  fonction `mail()` de PHP (marche d'office en mutualisé). Pour un vrai SMTP (meilleure délivrabilité),
  éditer `config/email.php` (host/port/secure/username/password ; exemples Brevo/Resend en commentaire).
- **Si l'install échoue à mi-chemin** : MySQL n'a pas de transaction sur les DDL (`CREATE TABLE`…) — en cas
  d'erreur d'import, **vider/supprimer la base puis recréer une base vierge** et relancer `/install/`
  (ne pas réessayer sur une base à moitié créée).
- **Sécurité** : CSP stricte déjà active par défaut — servie par `index.php` (nonce par requête, plus
  d'`unsafe-inline`/`unsafe-eval` ni de `https:` générique sur le `script-src`), en-têtes de sécurité dans
  `.htaccess`. Rien à durcir à la main.

## nginx / Plesk (important)

Le `.htaccess` (réécriture vers `index.php`) n'est lu **que par Apache**. Beaucoup d'hébergeurs servent
via **nginx** — en particulier **Plesk** avec son option « Traitement intelligent des fichiers statiques »
(*Smart static files processing*), qui sert les extensions comme **`.json`** directement comme des
fichiers. Or NeoFrag appelle ses endpoints AJAX en `.json` (ex. `…/admin/ajax/monitoring/backup.json`,
`…/admin/ajax/monitoring.json`). Sous nginx ils sont alors servis en statique → **404**, ce qui casse
**toute l'AJAX `.json`** : la sauvegarde « tourne sans fin » (le JS reçoit un 404 sans le voir), l'arbre
d'intégrité du Monitoring reste vide, etc.

**Symptôme typique** (console F12) : `GET …/admin/ajax/…json → 404` + `bootstrap-treeview: Not initialized`.

Deux corrections (au choix) :

1. **Plesk (le plus simple)** : *Sites web & Domaines* → ton domaine → **Paramètres Apache & nginx** →
   **décocher « Traitement intelligent des fichiers statiques »** → *Appliquer*. nginx repasse alors toutes
   les requêtes à Apache, qui applique le `.htaccess`.
2. **OU directive nginx** (hébergement nginx pur, ou si tu gardes le smart static) — dans
   *Directives nginx supplémentaires* :
   ```nginx
   location ~ \.json$ { try_files $uri /index.php?$args; }
   ```
   (sert un vrai fichier `.json` s'il existe, sinon route vers `index.php` — équivalent du `.htaccess`).

### Dossiers sensibles sous nginx (⚠ sécurité)

Le `.htaccess` interdit aussi l'accès direct aux dossiers et fichiers sensibles (les archives de
sauvegarde contiennent le **dump SQL + les secrets de `config/`**). Sous **nginx sans `.htaccess`**, ils
seraient servis en statique → **fuite de secrets**. Si tu n'as pas décoché le smart static (option 1),
ajoute dans les *Directives nginx supplémentaires* :

```nginx
location ~ ^/(backups|cache|config|logs|install|tools|tests|docs)/ { deny all; }
location ~* \.(sql|lock|scssc|map|dist|ini|sh|neon|md)$ { deny all; }
location ~ ^/(package\.json|package-lock\.json|eslint\.config\.js|playwright\.config\.js|Caddyfile|nginx\.conf)$ { deny all; }
```

Ce sont les règles de l'exemple complet que livre le paquet, `nginx.conf` (à la racine), à adapter :
domaine, dossier du site, PHP-FPM. `check-htaccess` vérifie que ces lignes refusent la même liste.
