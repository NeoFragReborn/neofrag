# `config/` — ce que l'installateur écrit ici

Ce dossier porte les **secrets du site**. Aucun de ses fichiers `.php` n'est versionné, et c'est
délibéré : une fois le site installé, ils contiennent le mot de passe de la base, la clé de
chiffrement et le sel des mots de passe. S'ils étaient suivis par git, un `git add -A` distrait les
enverrait sur le dépôt — et un secret poussé ne se retire qu'en réécrivant l'historique.

Ce qui **est** versionné, ce sont les gabarits `*.php.dist` à côté : ils montrent la forme de chaque
fichier sans en porter la moindre valeur réelle.

## Tu n'as rien à faire ici

`install/index.php` crée ce dossier et **écrit tous ces fichiers lui-même**, avec des secrets tirés
au hasard à chaque installation. Un clone neuf n'a pas de `config/`, et c'est normal : il apparaît
au premier passage de l'installateur. Les gabarits sont là pour **lire**, pas pour être copiés.

| Fichier | Ce qu'il porte | Écrit par |
|---|---|---|
| `db.php` | hôte, compte, mot de passe et nom de la base | l'installateur, à l'étape « base de données » |
| `crypt.php` | la clé de chiffrement du site (99 octets aléatoires) | l'installateur, **régénérée à chaque installation** |
| `password.php` | le sel des mots de passe (48 octets aléatoires) | idem |
| `email.php` | la configuration SMTP | l'installateur, **une seule fois** — un redéploiement ne réécrase pas un SMTP déjà réglé |
| `neofrag.php` | les interrupteurs de débogage et le mode démonstration | l'installateur |
| `url.php` | l'origine canonique du site, figée à l'installation | l'installateur |

## Les deux fichiers à connaître

**`crypt.php` et `password.php` ne se recopient jamais d'un site à l'autre.** Deux sites qui
partageraient la même clé partageraient aussi la capacité de déchiffrer les secrets de l'autre, et
le même sel rendrait les empreintes de mots de passe comparables entre eux. L'installateur les tire
au hasard précisément pour que cela n'arrive pas. Si tu déplaces un site, tu copies ces fichiers
**avec sa base** — jamais vers un site différent.

**`url.php` se modifie à la main** quand le site change de domaine. C'est le seul de la liste dans
ce cas : toutes les URL absolues du produit — liens de réinitialisation de mot de passe, retours
OAuth, retours de paiement — sont construites dessus, et jamais sur le `Host` de la requête, qui
est forgeable.

## Protection

`.htaccess` interdit tout accès HTTP à ce dossier sur Apache, et le `Caddyfile` de production
refuse `/config/*` de son côté. Cette double garde est délibérée : sur un hébergement mutualisé où
`AllowOverride None` est imposé, le `.htaccess` est ignoré.
