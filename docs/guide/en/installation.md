# Installation

NeoFrag Reborn installs on ordinary web hosting, **shared hosting included**: no Docker, no Composer and no
shell access are needed — the package ships with its dependencies.

## Requirements

- **PHP 8.2 to 8.5** with the `mysqli`, `mbstring`, `openssl`, `curl`, `gd`, `zip`, `intl`, `fileinfo` and
  `iconv` extensions, and a PHP that can hash passwords with **Argon2** (most can). They are enabled in your
  host's control panel, often under “PHP version” or “Extensions”. The installer checks every one of them and
  refuses to continue if one is missing.
- **MySQL 5.7, 8.0 or 8.4**, or **MariaDB 10.5, 10.6, 10.11, 11.4 or 11.8**, with the **InnoDB** engine and
  the **utf8mb4** character set. These are the versions every release of the CMS is tested against before it
  ships, with PHP 8.2 and 8.5: full installation, the whole test suite, and each installation profile.
- **Apache** with `mod_rewrite` (an `.htaccess` file is included); or **nginx** (`nginx.conf`); or **Caddy**
  (`Caddyfile`).
- An **empty** database and its credentials.
- A real **domain name** pointing to the server: the installer derives the site's contact address from it
  (`noreply@your-domain`) — installed by IP address, a site would have no valid sender address.

## The installer

The installer runs in **five steps**: *Requirements* → *Site profile* → *Database* → *Administrator* →
*Done*.

1. **Upload** the NeoFrag Reborn files to your web root: the contents of the `neofrag-reborn/` folder of the
   `neofrag-reborn-public-<version>.zip` package (by FTP, or by unzipping it on the server). The package is
   downloaded from the project's [releases page](https://github.com/NeoFragReborn/neofrag/releases/latest).
2. Open your domain: the installer starts and lists the **requirements** with the value it found (PHP
   version, extensions present).
3. Choose the **site profile**:

   | Profile | What it installs |
   |---|---|
   | **Full** | the core and every module in the package |
   | **Gaming / eSport** | the core and the gaming identity: forum, teams, games, events, recruitment, honours… |
   | **Community** | the core, news, forum, gallery, events, collectives and the API (for the Discord bot and integrations) |
   | **Association / club** | the core, news, forum, gallery, events, calendar, collectives, donations, membership fees (payments), newsletter, wiki and FAQ |
   | **Core only** | accounts, permissions, pages, settings — nothing more |

   Modules can still be unticked one by one; the modules a module needs are added automatically, and the
   final screen says so. The form works without JavaScript too.
4. Fill in the **database connection**: the installer imports the schema, installs the chosen modules,
   widgets and themes, and sets up the default layouts.
5. Create the **administrator account**. That's it.

Modules you did not install can be added later from **Administration → Themes & Addons**, and those that
are not in the package from the [marketplace](marketplace.md).

## Command-line installation

For a **scriptable** deployment (server, provisioning), `install/cli.php` does what the installer does,
without a browser — requirements included: it refuses to continue if one is missing. It installs the
**Full** profile.

```bash
# Administrator password through an environment variable (hidden from the process list):
export NF_ADMIN_PASS='my-strong-password'
php install/cli.php \
  --db-host=localhost --db-name=neofrag --db-user=neofrag --db-pass=secret \
  --admin-user=admin --admin-email=admin@site.tld --admin-pass-env=NF_ADMIN_PASS \
  --site-name="My community" --site-url=https://site.tld --yes
```

Without arguments, it switches to **interactive mode** (masked password). Options: `--db-port`,
`--create-db` (creates the database), `--dry-run` (validates the configuration and tests the connection
without writing anything), `--force` (reinstalls), `--no-lock` (does not set the lock),
`--lang=fr|en|de|es|it|pt` (the language of its messages), `--help`.

## Securing access after installation

At the end, the installer sets a **lock** (`install/db.txt`): visiting `/install/` again shows nothing
usable. As a precaution, **delete or rename the `install/` folder** — it is no longer needed. Enable
**HTTPS** if your host has not. Under nginx or Caddy, start from the examples at the root (`nginx.conf`,
`Caddyfile`): they already deny access to sensitive folders and files, like the `.htaccess` file does under
Apache.

## First settings

1. **Administration → Settings**: site name, description, favicon, **contact address** (check that it is
   valid and that a mail server can send — otherwise the contact form and registration e-mails never
   arrive; an SMTP server is set in *Settings → E-mail*, and *E-mails → Send test* proves it works), home
   page.
2. **Administration → Themes & Addons**: your theme, your modules.
3. **Administration → Live Editor**: build your pages (place your widgets in the regions).
4. **Administration → Users / Permissions**: create your roles and set access.
5. **Scheduled tasks**: scheduled posts, newsletters and event reminders go out through the site's **cron**
   (Monitoring gives its address and key). Add a task every five minutes at your host; without it, nothing
   scheduled is sent.

The next steps, in order: [Getting started](premiers-pas.md).

## Troubleshooting

- **“Unable to connect to the database”**: check the host (often `localhost`, sometimes a dedicated
  address on shared hosting), the port (`3306`), the database name — **it must exist and be empty** — and
  the credentials.
- **“Missing PHP extension”**: enable the reported extension from your host's panel; on the command line,
  `php -m`.
- **`config/` folder not writable**: the installer must write `db.php` and the secrets there. Give write
  access to `config/`, `cache/`, `logs/`, `upload/`, `backups/`.
- **Installation interrupted**: MySQL cannot undo a half-played `CREATE TABLE` — **create a fresh empty
  database** before trying again.
- **404 addresses or broken AJAX under nginx or Plesk**: `.htaccess` is only read by Apache; see the
  “nginx / Plesk” note in the [deployment guide](../../deploy-ftp.md).
- **No e-mail is sent**: look at *System → Monitoring → Error log* (an `[email] échec envoi …` line); the
  contact address must be valid and a transport must exist (an SMTP server configured, or a local mail
  agent).
- **“An error occurred”, with a reference**: the page crashed. The details are in the error log; the
  reference finds the right line.

## Development

The code is on GitHub: the CMS in [NeoFragReborn/neofrag](https://github.com/NeoFragReborn/neofrag), the
optional addons in [NeoFragReborn/extensions](https://github.com/NeoFragReborn/extensions) —
`tools/assembler.php` places them in the CMS tree. To develop or test, any machine with PHP 8.2+ and MariaDB
or MySQL will do; a Docker stack (`docker-compose.yml`) is provided. Details (database, migrations, tests,
checks, deployment): [`docs/development.md`](../../development.md).
