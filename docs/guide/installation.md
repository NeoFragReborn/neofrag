# Installation

NeoFrag Reborn s'installe sur un hébergement web classique, **mutualisé compris**.

## Prérequis

- **PHP 8.2+** avec les extensions `mysqli`, `gd`, `intl`, `mbstring`, `zip`, `curl`.
- **MySQL 5.7+** ou **MariaDB 10.5+**.
- **Apache** avec `mod_rewrite` (un `nginx.conf` est fourni en alternative).
- Une base de données vide + ses identifiants (panel de l'hébergeur).

## Mise en ligne

L'assistant d'installation se déroule en **4 étapes** : Prérequis → Base de données →
Administrateur → Terminé.

1. **Téléverse** les fichiers de NeoFrag Reborn à la racine web (FTP ou Git).
2. Visite ton domaine : l'**assistant d'installation** se lance automatiquement et vérifie les prérequis.
3. Renseigne la **connexion base de données** (une base vide) — l'assistant importe le schéma, les migrations
   **et installe tous les modules, widgets et thèmes** livrés dans le paquet (modèle « tout bundlé »).
4. Crée le **compte administrateur**. C'est fini — supprime/replie l'accès à l'installeur si l'hébergeur ne le fait pas.

## Modules : tout est déjà là

NeoFrag Reborn s'installe **complet** : tous les modules (actualités, forum, galerie, équipes, événements,
wiki, boutique…), widgets et thèmes du paquet sont installés et activés d'emblée — il n'y a **pas de choix
de profil** à l'installation (modèle WordPress).

Tu **actives/désactives** ensuite chaque addon depuis **Admin → Thèmes & Addons**. Les **mises à jour** des
addons (et l'ajout d'addons tiers) se font depuis le [marketplace](marketplace.md), avec intégrité vérifiée
par empreinte SHA-256.

> La page d'accueil rend toujours quelque chose (jamais d'écran vide) : les actualités par défaut.

## Premiers réglages

1. **Admin → Paramètres** : nom du site, description, favicon, page d'accueil.
2. **Admin → Thèmes & Addons** : choisis ton thème (Nebula pour une communauté) et
   **active ou désactive** les modules selon tes besoins — tout est déjà installé (modèle « tout bundlé »).
3. **Admin → Live Editor** : compose tes pages (place tes widgets dans les zones).
4. **Admin → Utilisateurs / Permissions** : crée tes rôles et règle les accès.

## Sécuriser l'accès après l'installation

À la fin, l'assistant pose un **verrou** (`install/db.txt`) : revisiter `/install/` n'affiche plus rien
d'exploitable. Par précaution, **supprime (ou renomme) le dossier `install/`** de ton serveur — il n'est
plus nécessaire au fonctionnement du site. Pense aussi à activer **HTTPS** (Let's Encrypt) si l'hébergeur
ne l'a pas fait.

## Dépannage

- **« Connexion à la base impossible »** : vérifie l'hôte (souvent `localhost`, parfois une adresse dédiée
  sur les mutualisés), le port (`3306`), le nom de la base — **elle doit exister et être vide** — et les
  identifiants (panel de l'hébergeur).
- **« Extension PHP manquante »** (étape Prérequis) : active l'extension signalée (`mysqli`, `gd`, `intl`,
  `mbstring`, `zip`, `curl`) depuis le panel de l'hébergeur, ou demande au support. En ligne de commande : `php -m`.
- **Dossier `config/` non inscriptible** : l'assistant doit y écrire `db.php` + les secrets. Donne les droits
  d'écriture à `config/` (et à `cache/`, `logs/`, `upload/`, `backups/`).
- **Installation interrompue en cours de route** : MySQL ne sait pas annuler un `CREATE TABLE` à moitié joué
  → **vide ou recrée une base vierge** avant de relancer `/install/` (ne réessaie pas sur une base déjà entamée).
- **URLs en 404 / AJAX cassés sous nginx ou Plesk** : le `.htaccess` n'est lu que par Apache. Voir la note
  « nginx / Plesk » du guide de déploiement.

## Développement local

Pour développer ou tester, une stack **Docker** est fournie (Apache + PHP 8.3, MariaDB,
phpMyAdmin, Mailpit) :

```bash
docker compose up -d        # http://localhost:8080
```

Détails (bootstrap d'une base, migrations, tests, déploiement) :
[`docs/development.md`](../development.md).
