<?php
declare(strict_types=1);
require_once __DIR__.'/outil.php';

/**
 * demo — ce que l'instantané de la démonstration ne porte jamais.
 *
 * Diffusion : publique
 *
 * `install/demo.sql` est versionné, part dans le dépôt public et dans le paquet de la démo, et se
 * rejoue sur la démonstration tous les quarts d'heure : un secret qui y entre est PUBLIÉ. `dump-demo`
 * le produit en appliquant ce fichier ; `check-demo-lock` confronte ce fichier au code ET au fichier
 * produit. La liste vit ici, une fois, pour que les deux lisent la même.
 *
 * Elle était écrite de mémoire dans `dump-demo`, et ne protégeait que des fantômes : le 2026-10-02,
 * huit noms qu'aucun code n'emploie (`nf_recaptcha_secret`, `nf_stripe_secret`…) pendant que la vraie
 * clé du captcha passait ; le 2026-10-03, un neuvième (`nf_smtp_user`, pour `nf_smtp_username`), la
 * clé du service de traduction et celles du widget Twitch, que personne n'avait listées.
 */

/**
 * Les réglages (`nf_settings`) que l'instantané n'emporte pas. Chaque nom existe dans le code —
 * `check-demo-lock` le vérifie. Un secret, ou un réglage propre à l'installation.
 */
const NF_DEMO_REGLAGES_EXCLUS = [
    'nf_cron_key'               => "la clé du cron : sans elle, la remise à zéro s'auto-détruit",
    'nf_monitoring_check_url'   => 'origine des mises à jour du cœur, propre à chaque site',
    'nf_migrations_version'     => "l'état de l'installation : figé, chaque remise à zéro le ramènerait au jour de l'instantané",
    'nf_smtp_host'              => "le serveur d'envoi du site",
    'nf_smtp_username'          => "le compte d'envoi",
    'nf_smtp_password'          => "le mot de passe d'envoi, chiffré",
    'nf_email_smtp'             => "le serveur d'envoi de l'ancien installeur (alpha)",
    'nf_email_password'         => "le mot de passe d'envoi de l'ancien installeur (alpha)",
    'nf_captcha_private_key'    => 'la clé secrète du captcha, chiffrée',
    'pay_stripe_secret'         => 'la clé secrète de Stripe',
    'pay_stripe_webhook_secret' => 'le secret des notifications de Stripe',
    'nf_discord_token'          => 'la clé du bot Discord, chiffrée — une clé reste une clé',
    'nf_translate_api'          => "la clé du service de traduction de NeoFrag d'origine",
    'nf_seo_indexnow_cle'       => "la clé IndexNow, propre à chaque site : un moteur la lit à la racine de celui qui envoie",
    'nf_seo_indexnow_api'       => "le service IndexNow remplacé pour une épreuve : jamais d'un site à l'autre",
];

/**
 * Un nom qui DÉSIGNE un secret. Sur un réglage, il oblige à choisir : exclu, ou public avec sa raison
 * (NF_DEMO_REGLAGES_PUBLICS). Dans les réglages d'un widget (`nf_widgets.settings`, en JSON), la
 * valeur est vidée à la prise de l'instantané — `client_secret` et `api_key` du widget Twitch,
 * `query_user` et `query_pass` du widget TeamSpeak, le compte qui interroge le serveur vocal.
 */
const NF_DEMO_MOTIF_SECRET = '/secret|passw|(^|_)pass($|_)|query_user|token|private|api_?key|apikey|(^|_)api$|(^|_)key$|(^|_)cle($|_)|webhook|smtp/i';

/** Les réglages dont le nom ressemble à un secret sans en être un : l'instantané les emporte. */
const NF_DEMO_REGLAGES_PUBLICS = [
    'nf_captcha_public_key' => 'la clé publique du captcha : elle est écrite dans chaque page qui affiche le captcha',
    'nf_smtp_port'          => "un numéro de port : rien qu'un attaquant n'apprenne en interrogeant le serveur",
    'nf_smtp_secure'        => 'le chiffrement de la connexion (tls, ssl) : un choix, pas un secret',
];

/**
 * Les réglages d'un widget, débarrassés de leurs secrets : toute clé dont le nom répond à
 * NF_DEMO_MOTIF_SECRET garde sa place, vide. Des réglages qui ne sont pas du JSON objet sont rendus tels quels.
 */
function nf_demo_reglages_widget(?string $json): ?string
{
    if ($json === NULL || $json === '' || !is_array($reglages = json_decode($json, TRUE)))
    {
        return $json;
    }

    $nettoyer = static function (array $tableau) use (&$nettoyer): array
    {
        foreach ($tableau as $cle => $valeur)
        {
            if (is_array($valeur))
            {
                $tableau[$cle] = $nettoyer($valeur);
            }
            elseif (is_string($cle) && preg_match(NF_DEMO_MOTIF_SECRET, $cle) && $valeur !== '' && $valeur !== NULL)
            {
                $tableau[$cle] = '';
            }
        }

        return $tableau;
    };

    $propres = $nettoyer($reglages);

    return $propres === $reglages ? $json : (string) json_encode($propres, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
