<?php
declare(strict_types=1);

/**
 * outil — le socle que chaque outil de `tools/` charge en première ligne.
 *
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Soixante outils portaient chacun leur garde HTTP, leur lecture de `$argv`, leur façon d'écrire
 * un verdict et leur code de sortie — soixante variantes, dont trois gardes manquantes trouvées le
 * 2026-09-21 (`table-map.php` publiait la carte des tables à qui la demandait). Ce fichier est
 * l'unique endroit où ces conventions sont écrites ; un outil les obtient en le chargeant.
 *
 * Ce qu'il fixe
 * -------------
 *   - la GARDE : rien de `tools/` ne s'exécute hors ligne de commande (la première instruction) ;
 *   - les CODES DE SORTIE : 0 le contrôle a mesuré et n'a rien à reprocher · 1 il a mesuré et
 *     refuse · 2 il n'a PAS pu juger (prérequis absent, port occupé, page muette). La distinction
 *     entre 1 et 2 compte : un contrôle aveugle qui se dirait vert est pire qu'un contrôle absent ;
 *   - le VERDICT : la dernière ligne écrite est `<outil> OK : …`, `<outil> ÉCHEC : …` ou
 *     `<outil> REFUS : …` — c'est elle que `check-all` affiche ;
 *   - les OPTIONS : `--nom=valeur`, `--drapeau`, et `--` pour les arguments positionnels ;
 *   - les PORTS : chaque outil qui sert le site a son port par défaut, tous distincts (cf. NF_PORTS),
 *     surchargeable par `--port=` puis par la variable `NF_PORT`.
 *
 * Usage
 * -----
 *   require __DIR__.'/lib/outil.php';        // en tête de chaque tools/*.php
 *   $o = nf_options(['max' => 300, 'verbeux' => FALSE, 'depart' => []]);
 *   …
 *   nf_ok('aucun lien mort sur 300 pages');   // ou nf_echec(…) / nf_refus(…)
 */

// La garde, avant tout. Servi par un serveur web, un outil d'administration abîmerait le site.
if (PHP_SAPI !== 'cli')
{
    http_response_code(404);
    exit;
}

/** La racine du dépôt (le parent de `tools/`). */
const NF_RACINE = __DIR__.'/../..';

const NF_OK    = 0;
const NF_ECHEC = 1;
const NF_REFUS = 2;

/**
 * Un port par outil, tous distincts.
 *
 * Deux outils qui partagent un port par défaut ne peuvent pas tourner en même temps, et le
 * second interroge le serveur du premier sans le savoir : c'est arrivé (`capture` et `journal`
 * étaient tous deux sur 8096). Un outil absent de cette table demande son port explicitement.
 */
const NF_PORTS = [
    'capture'               => 8091,
    'check-admin-back'      => 8092,
    'check-liens'           => 8093,
    'check-contraste'       => 8094,
    'check-demo-ecriture'   => 8095,
    'check-important'       => 8096,
    'check-install-profiles'=> 8097,
    'check-journal'         => 8098,
    'check-js'              => 8099,
    'check-js-console'      => 8100,
    'check-responsive'      => 8101,
    'check-restauration'    => 8102,
    'check-widget-contract' => 8103,
    'init-dispositions'     => 8104,
    'check-smoke'           => 8105,
    'check-langues-contenu' => 8106,
    'check-service-worker'  => 8107,
    'check-parcours'        => 8108,
    'capturer-apercus'      => 8109,
    // check-mise-en-page sert DEUX sites : l'installation (8110) et, avec --vierge, une installation
    // neuve montée pour l'occasion (8111). Les deux ports lui sont réservés.
    'check-mise-en-page'    => 8110,
    'check-mise-en-page-vierge' => 8111,
    'check-mise-a-jour'     => 8112,
    'check-seo'             => 8113,
];

/** Le nom de l'outil qui s'exécute, tel qu'il apparaît dans ses verdicts : `check-liens`. */
function nf_outil(): string
{
    return basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? 'outil'), '.php');
}

/** La racine du dépôt, résolue. */
function nf_racine(): string
{
    static $racine = NULL;

    return $racine ??= (string) realpath(NF_RACINE);
}

/**
 * Lit la ligne de commande contre un schéma de défauts.
 *
 * Le TYPE de chaque option est celui de sa valeur par défaut : un booléen se donne par `--nom`,
 * un entier ou une chaîne par `--nom=valeur`, un tableau par `--nom=valeur` répété. Ce qui suit
 * `--`, ou ce qui ne commence pas par `--`, est rendu dans `reste`, dans l'ordre. Une option
 * inconnue est un REFUS : une faute de frappe ne doit jamais se lire comme « option ignorée ».
 *
 * @param  array<string, mixed> $schema  nom => valeur par défaut
 * @return array{0: array<string, mixed>, 1: list<string>}  [options, reste]
 */
function nf_options(array $schema, ?array $arguments = NULL): array
{
    $arguments ??= array_slice($_SERVER['argv'] ?? [], 1);
    $options    = $schema;
    $reste      = [];

    while ($arguments)
    {
        $argument = array_shift($arguments);

        if ($argument === '--')
        {
            $reste = array_merge($reste, $arguments);
            break;
        }

        if (!str_starts_with($argument, '--'))
        {
            $reste[] = $argument;
            continue;
        }

        [$nom, $valeur] = array_pad(explode('=', substr($argument, 2), 2), 2, NULL);

        if (!array_key_exists($nom, $schema))
        {
            nf_refus(sprintf("option inconnue « --%s » — voir l'en-tête de tools/%s.php", $nom, nf_outil()));
        }

        $defaut = $schema[$nom];

        if (is_bool($defaut))
        {
            $options[$nom] = TRUE;
        }
        elseif (is_array($defaut))
        {
            $options[$nom][] = (string) $valeur;
        }
        elseif (is_int($defaut))
        {
            if ($valeur === NULL || !preg_match('/^-?\d+$/', $valeur))
            {
                nf_refus(sprintf('« --%s » attend un entier', $nom));
            }

            $options[$nom] = (int) $valeur;
        }
        else
        {
            $options[$nom] = (string) $valeur;
        }
    }

    return [$options, $reste];
}

/**
 * Le port sur lequel cet outil sert le site : `--port=` déjà lu par nf_options(), sinon `NF_PORT`,
 * sinon le port réservé à l'outil dans NF_PORTS.
 */
function nf_port(int $demande = 0): int
{
    if ($demande > 0)
    {
        return $demande;
    }

    if (($env = getenv('NF_PORT')) !== FALSE && ctype_digit($env))
    {
        return (int) $env;
    }

    return NF_PORTS[nf_outil()] ?? nf_refus('cet outil n\'a pas de port réservé dans tools/lib/outil.php : passer --port=');
}

/** Un chemin dans le dossier temporaire, préfixé du nom de l'outil : `/tmp/nf-check-liens-routeur.php`. */
function nf_temp(string $suffixe): string
{
    return sys_get_temp_dir().'/nf-'.nf_outil().'-'.$suffixe;
}

/** Écrit sur la sortie d'erreur, sans sortir. */
function nf_avertir(string $message): void
{
    fwrite(STDERR, rtrim($message)."\n");
}

/** Le verdict OK : dernière ligne, code 0. */
function nf_ok(string $message): never
{
    echo nf_outil().' OK : '.rtrim($message)."\n";
    exit(NF_OK);
}

/** Le verdict ÉCHEC : le contrôle a mesuré, et refuse. Dernière ligne sur STDERR, code 1. */
function nf_echec(string $message): never
{
    fwrite(STDERR, nf_outil().' ÉCHEC : '.rtrim($message)."\n");
    exit(NF_ECHEC);
}

/** Le REFUS de juger : prérequis absent, port occupé, mesure impossible. Code 2, jamais 1. */
function nf_refus(string $message): never
{
    fwrite(STDERR, nf_outil().' REFUS : '.rtrim($message)."\n");
    exit(NF_REFUS);
}

/**
 * Un fichier PHP du dépôt lu SANS ses commentaires, les sauts de ligne conservés.
 *
 * Trois contrôles se sont déclenchés sur leur propre documentation avant d'adopter ce filtre :
 * un exemple cité en commentaire n'est pas un appel. On passe par le tokeniseur, jamais par une
 * expression régulière — une URL contient `//`. Les CHAÎNES restent : c'est dedans que vivent
 * les noms de classes et de tables qu'on cherche.
 */
function nf_sans_commentaires(string $source): string
{
    if (!str_contains($source, '<?'))
    {
        return $source;
    }

    $sortie = '';

    foreach (@token_get_all($source) ?: [] as $jeton)
    {
        if (is_array($jeton) && in_array($jeton[0], [T_COMMENT, T_DOC_COMMENT], TRUE))
        {
            $sortie .= str_repeat("\n", substr_count($jeton[1], "\n"));
            continue;
        }

        $sortie .= is_array($jeton) ? $jeton[1] : $jeton;
    }

    return $sortie;
}
