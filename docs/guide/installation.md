# Installation

NeoFrag Reborn s'installe sur un hébergement web classique, **mutualisé compris** : ni Docker, ni
Composer, ni accès shell ne sont nécessaires — le paquet embarque ses dépendances.

## Prérequis

- **PHP 8.2+** avec les extensions `mysqli`, `gd`, `intl`, `mbstring`, `zip`, `curl`.
- **MySQL 5.7+** ou **MariaDB 10.5+**.
- **Apache** avec `mod_rewrite` (un `.htaccess` est livré) ; ou **nginx** (`nginx.conf`) ; ou **Caddy**
  (`Caddyfile`).
- Une base de données **vide** et ses identifiants.
- Un vrai **nom de domaine** pointant sur le serveur : l'assistant en déduit l'adresse de contact du site
  (`noreply@ton-domaine`) — installé par son adresse IP, un site n'aurait pas d'adresse d'expéditeur
  valide.

## L'assistant d'installation

L'assistant se déroule en **cinq étapes** : *Prérequis* → *Profil du site* → *Base de données* →
*Administrateur* → *Terminé*.

1. **Téléverse** les fichiers de NeoFrag Reborn à la racine web : le contenu du dossier `neofrag-reborn/`
   du paquet `neofrag-reborn-public-<version>.zip` (par FTP, ou en le décompressant sur le serveur).
2. Ouvre ton domaine : l'assistant se lance et affiche les **prérequis** avec la valeur constatée
   (version de PHP, extensions présentes).
3. Choisis le **profil du site** :

   | Profil | Ce qu'il installe |
   |---|---|
   | **Complet** | le cœur et tous les modules du paquet |
   | **Gaming / eSport** | le cœur et l'identité gaming : forum, équipes, jeux, événements, recrutement, palmarès… |
   | **Communauté** | le cœur, les actualités, le forum, la galerie |
   | **Association / club** | le cœur, les actualités, le forum, la galerie, le calendrier, les dons, la newsletter, le wiki et la FAQ |
   | **Cœur seul** | comptes, permissions, pages, paramètres — rien de plus |

   Les modules restent décochables un par un ; ceux qu'un module réclame sont ajoutés automatiquement,
   et l'écran final le dit. Sans JavaScript, le formulaire fonctionne quand même.
4. Renseigne la **connexion à la base de données** : l'assistant importe le schéma, installe les modules,
   widgets et thèmes choisis, et pose les dispositions par défaut.
5. Crée le **compte administrateur**. C'est fini.

Les modules non installés s'ajoutent plus tard depuis **Administration → Thèmes & Addons**, et ceux
qui ne sont pas dans le paquet depuis le [marketplace](marketplace.md).

## Installation en ligne de commande

Pour un déploiement **scriptable** (serveur, provisioning), `install/cli.php` fait la même chose que
l'assistant, sans navigateur. Il installe le profil **Complet**.

```bash
# Mot de passe administrateur via variable d'environnement (invisible dans la liste des processus) :
export NF_ADMIN_PASS='mon-mot-de-passe-fort'
php install/cli.php \
  --db-host=localhost --db-name=neofrag --db-user=neofrag --db-pass=secret \
  --admin-user=admin --admin-email=admin@site.tld --admin-pass-env=NF_ADMIN_PASS \
  --site-name="Ma communauté" --site-url=https://site.tld --yes
```

Sans arguments, il passe en **mode interactif** (mot de passe masqué). Options : `--db-port`,
`--create-db` (crée la base), `--dry-run` (valide la configuration et teste la connexion sans rien
écrire), `--force` (réinstalle), `--no-lock` (ne pose pas le verrou), `--help`.

## Sécuriser l'accès après l'installation

À la fin, l'assistant pose un **verrou** (`install/db.txt`) : revisiter `/install/` n'affiche plus rien
d'exploitable. Par précaution, **supprime ou renomme le dossier `install/`** — il n'est plus nécessaire.
Active **HTTPS** si l'hébergeur ne l'a pas fait. Sous Caddy, le `Caddyfile` livré refuse déjà l'accès à
`config/`, `logs/`, `install/`, `tools/`, `tests/`, `docs/` et aux fichiers sensibles.

## Premiers réglages

1. **Administration → Paramètres** : nom du site, description, favicon, **adresse de contact** (vérifie
   qu'elle est valide et qu'un serveur de messagerie peut envoyer — sans cela, le formulaire de contact
   et les e-mails d'inscription n'aboutissent pas ; un serveur SMTP se règle dans *Paramètres → E-mail*,
   et *E-mails → Envoi de test* le prouve), page d'accueil.
2. **Administration → Thèmes & Addons** : ton thème, tes modules.
3. **Administration → Éditeur en direct** : compose tes pages (place tes widgets dans les régions).
4. **Administration → Utilisateurs / Permissions** : crée tes rôles et règle les accès.
5. **Tâches planifiées** : les publications programmées, les newsletters et les rappels d'événements
   partent par le **cron** du site (Monitoring en donne l'adresse et la clé). Ajoute une tâche toutes
   les cinq minutes chez ton hébergeur ; sans elle, rien de programmé ne part.

## Dépannage

- **« Connexion à la base impossible »** : vérifie l'hôte (souvent `localhost`, parfois une adresse
  dédiée sur les mutualisés), le port (`3306`), le nom de la base — **elle doit exister et être vide** —
  et les identifiants.
- **« Extension PHP manquante »** : active l'extension signalée depuis le panel de l'hébergeur ; en ligne
  de commande, `php -m`.
- **Dossier `config/` non inscriptible** : l'assistant doit y écrire `db.php` et les secrets. Donne les
  droits d'écriture à `config/`, `cache/`, `logs/`, `upload/`, `backups/`.
- **Installation interrompue** : MySQL ne sait pas annuler un `CREATE TABLE` à moitié joué — **recrée
  une base vierge** avant de relancer.
- **Adresses en 404 ou AJAX cassés sous nginx ou Plesk** : le `.htaccess` n'est lu que par Apache ; voir la
  note « nginx / Plesk » du [guide de déploiement](../deploy-ftp.md).
- **Aucun e-mail ne part** : regarde *Système → Monitoring → Journal des erreurs* (une ligne
  `[email] échec envoi …`) ; l'adresse de contact doit être valide et un transport doit exister (serveur
  SMTP configuré, ou agent de messagerie local).
- **« Une erreur est survenue », avec une référence** : la page a planté. Le détail est dans le journal
  des erreurs ; la référence y retrouve la bonne ligne.

## Développement

Pour développer ou tester, toute machine avec PHP 8.2+ et MariaDB convient ; une pile Docker
(`docker-compose.yml`) est fournie. Détails (base, migrations, tests, contrôles, déploiement) :
[`docs/development.md`](../development.md).
