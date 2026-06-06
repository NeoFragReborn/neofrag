# Déploiement sur hébergement mutualisé (FTP)

NeoFrag Reborn s'installe sur n'importe quel hébergement **Apache + PHP 8.2+ + MySQL/MariaDB**
(type cPanel/Plesk), sans Docker ni Composer ni accès shell. Les paquets prêts à l'emploi sont
générés par `tools/build-release.php` dans `dist/` :

| Paquet | Pour |
|--------|------|
| `neofrag-reborn-<v>.zip` | le **site principal** (vitrine) |
| `neofrag-reborn-demo-<v>.zip` | le **site de démo** (admin lecture seule + auto-reset) |

Les deux embarquent `vendor/` (aucun `composer install` requis) et le `.htaccess` (réécriture d'URL).

## Prérequis hébergement

- PHP **≥ 8.2** avec extensions : `mysqli`, `mbstring`, `gd`, `zip`, `curl`, `intl`.
- Une base **MySQL/MariaDB** (créée depuis le panel, ou créée par l'installeur si les droits le permettent).
- Apache avec **`mod_rewrite`** et `AllowOverride All` (le `.htaccess` route vers `index.php`). Sur la
  plupart des mutualisés c'est actif par défaut ; sinon demander au support.

## Site principal — étapes

1. **Décompresser** `neofrag-reborn-<v>.zip` et **uploader** le contenu du dossier `neofrag-reborn/`
   à la racine web du domaine (ex. `public_html/` ou le docroot du sous-domaine).
2. **Droits d'écriture** (chmod 755 dossiers / 644 fichiers, puis rendre **inscriptibles** par PHP) :
   `config/`, `cache/`, `logs/`, `upload/`, `backups/`. (Ces dossiers sont créés au besoin ; le seul
   indispensable en écriture au départ est `config/`.)
3. Visiter **`https://<domaine>/install/`** → l'assistant en 4 étapes :
   prérequis → base de données → compte admin → fin. Il génère `config/db.php` + les secrets
   (`crypt.php`, `password.php`), importe le schéma + le seed, applique les migrations, pose le verrou
   `install/db.txt`.
4. Terminé : le site répond sur `https://<domaine>/` avec le thème **vitrine** par défaut.
5. **(optionnel) Parution programmée** : ajouter un cron 5 min qui appelle l'endpoint de publication.
   **Copie l'URL exacte affichée dans Admin → Monitoring** (elle inclut le bon préfixe de langue) ;
   `-L` suit la redirection de langue si tu omets le préfixe :
   ```
   */5 * * * * curl -fsSL "https://<domaine>/monitoring/cron?key=<nf_cron_key>" >/dev/null 2>&1
   ```

## Site de démo — étapes

Identique au site principal, avec en plus le **mode démo** (déjà activé : `config/neofrag.php`
contient `NEOFRAG_DEMO=TRUE`) et les **données de démo**.

1. Uploader le contenu de `neofrag-reborn-demo-<v>.zip` sur le **sous-domaine** (ex. `demo.<domaine>`).
2. Droits d'écriture (idem).
3. Visiter **`https://demo.<domaine>/install/`** → assistant (DB + compte admin). L'installeur **charge
   automatiquement `install/demo.sql`** à la fin : le site démarre **directement** sur le thème **nebula**,
   peuplé (membres + contenu de tous les modules), thème/widget **vitrine** retiré, bandeau démo + compte
   `demo`/`demo` actifs. Ton compte admin (créé à l'install) est préservé. **Aucune étape manuelle.**
4. **Cron de reset** (recommandé : toutes les heures) — restaure l'état de démo, nettoie ce que les
   visiteurs ont posté. **Clé + URL exacte dans Admin → Monitoring** ; `-L` suit la redirection de
   langue (le préfixe `/fr/` ci-dessous dépend de la langue par défaut du site) :
   ```
   0 * * * * curl -fsSL "https://demo.<domaine>/fr/monitoring/cron?key=<nf_cron_key>&demo=1" >/dev/null 2>&1
   ```

### Comportement du mode démo

- L'**administration est navigable mais en lecture seule** : toute écriture de configuration/installation
  est bloquée (toast « Action désactivée sur le site de démonstration. »). L'admin du site (créé à
  l'install) reste maître ; les secrets ne sont jamais touchés par le reset.
- Le **front reste interactif** : inscription, forum, commentaires, réactions… Tout cela est **éphémère**
  (nettoyé au prochain reset).
- Un **bandeau** « Démo — réinitialisée régulièrement · connexion : demo / demo » s'affiche partout.
  Compte de démonstration prêt à l'emploi : **`demo` / `demo`**.

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
- **Mises à jour** : remplacer les fichiers (hors `config/`, `upload/`, `backups/`) par la nouvelle version,
  puis visiter le site (les migrations en attente s'appliquent). Sauvegarder d'abord (Admin → Monitoring).
- **Sécurité** : durcir la CSP du `.htaccess` (retirer `unsafe-inline`/`unsafe-eval`) une fois le rendu validé.
