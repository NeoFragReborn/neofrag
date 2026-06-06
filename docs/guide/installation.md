# Installation

NeoFrag Reborn s'installe sur un hébergement web classique, **mutualisé compris**.

## Prérequis

- **PHP 8.3+** avec les extensions `mysqli`, `gd`, `intl`, `mbstring`, `zip`, `curl`.
- **MySQL 5.7+** ou **MariaDB 10.5+**.
- **Apache** avec `mod_rewrite` (un `nginx.conf` est fourni en alternative).
- Une base de données vide + ses identifiants (panel de l'hébergeur).

## Mise en ligne

1. **Téléverse** les fichiers de NeoFrag Reborn à la racine web (FTP ou Git).
2. Visite ton domaine : l'**assistant d'installation** se lance automatiquement.
3. Renseigne la **connexion base de données**, puis crée le **compte administrateur**.
4. L'assistant importe le schéma, applique les migrations et finalise.
5. Supprime/repli l'accès à l'installeur si l'hébergeur ne le fait pas.

À l'issue, tu disposes d'un site fonctionnel avec le **cœur communautaire** (forum,
actualités, commentaires, membres, galerie, contact). Ajoute le reste depuis le
[marketplace](marketplace.md).

## Premiers réglages

1. **Admin → Paramètres** : nom du site, description, favicon, page d'accueil.
2. **Admin → Thèmes & Addons** : choisis ton thème (Nebula pour une communauté) et
   installe les modules voulus.
3. **Admin → Live Editor** : compose tes pages (place tes widgets dans les zones).
4. **Admin → Utilisateurs / Permissions** : crée tes rôles et règle les accès.

## Développement local

Pour développer ou tester, une stack **Docker** est fournie (Apache + PHP 8.3, MariaDB,
phpMyAdmin, Mailpit) :

```bash
docker compose up -d        # http://localhost:8080
```

Détails (bootstrap d'une base, migrations, tests, déploiement) :
[`docs/development.md`](../development.md).
