<?php
declare(strict_types=1);
/**
 * check-addon-name — aucune lecture `->name` sur un addon chargé : elle rend FALSE en silence, le nom est dans `info()->name`.
 *
 * Famille : statique
 *
 * Pourquoi ce contrôle existe
 * ---------------------------
 * Deux objets portent le même mot « addon » :
 *   - la LIGNE de la table `nf_addon`, où `->name` est une colonne et vaut bien le nom ;
 *   - l'ADDON CHARGÉ, rendu par `model2('addon')->get('module')` et consorts, où le nom vit dans
 *     `info()->name`. `->name` y passe par le service locator, ne trouve rien, et rend **FALSE** —
 *     sans avertissement, sans trace dans le journal.
 *
 * Trois endroits s'y étaient fait prendre, trouvés le 2026-09-20 en écrivant le carrefour `cron` du
 * lecteur de flux, dont la première version rapportait « : 0 refreshed » au lieu de « rss: … » :
 *
 *   - `modules/statistics/models/statistics.php` : toutes les clés valaient « -comments »,
 *     « -articles »… au lieu de « comments-comments ». Invisible, parce que le formulaire et le
 *     filtre lisent les mêmes clés — mais deux modules exposant une statistique du même nom
 *     s'écrasaient l'un l'autre en silence ;
 *   - `widgets/navigation/views/admin.tpl.php` : l'exclusion de `live_editor` et `pages` de la liste
 *     des liens ne s'appliquait jamais ;
 *   - `modules/addons/controllers/admin_ajax.php` : la liste des modules installés se réduisait à
 *     une entrée vide, donc TOUTE dépendance déclarée était annoncée comme manquante.
 *
 * Ce que le contrôle vérifie
 * --------------------------
 * Dans chaque `foreach` dont la source est un `get('module'|'widget'|'theme'|…)` d'addons chargés, il
 * refuse une lecture `$variable->name` sur la variable de boucle. La bonne écriture est
 * `$variable->info()->name`.
 *
 * C'est un contrôle STATIQUE : il lit le code, il n'exécute rien. Il ne voit donc pas une variable
 * passée à une fonction puis lue ailleurs — mais il attrape la forme qui s'écrit naturellement, et
 * qui est celle des trois cas trouvés.
 *
 * Usage
 * -----
 *   php tools/check-addon-name.php            liste les lectures fautives, code 1 s'il y en a
 *   php tools/check-addon-name.php --detail   dit aussi combien de boucles ont été examinées
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o]    = nf_options(['detail' => FALSE]);
$racine = nf_racine();
$detail = $o['detail'];

/** Les dossiers où du code de produit vit ; `vendor` et les archives n'y sont pas. */
const DOSSIERS = NF_DOSSIERS_PRODUIT;

/** Ce qui désigne une collection d'addons CHARGÉS (et non des lignes de table). */
const SOURCES = [
    "model2('addon')->get(",
    'model2("addon")->get(',
];

/**
 * Le code d'un fichier, ligne par ligne, COMMENTAIRES ET CHAÎNES RETIRÉS.
 *
 * L'analyse passe par `token_get_all()` plutôt que par une expression régulière sur le texte : la
 * première version de ce contrôle signalait les commentaires qui CITENT `$module->name` pour
 * expliquer le défaut. Un garde-fou qui crie à tort finit désactivé.
 *
 * @return array<int, string> numéro de ligne (à partir de 1) => code de la ligne
 */
function lignes_de_code(string $chemin): array
{
    $source = (string) file_get_contents($chemin);
    $lignes = [];

    foreach (token_get_all($source) as $token)
    {
        if (is_array($token))
        {
            [$id, $texte, $ligne] = $token;

            // Ce qui n'est pas du code : le HTML du gabarit et les commentaires. Les CHAÎNES restent —
            // c'en sont qui portent `'addon'` et `'module'`, c'est-à-dire ce qui permet de reconnaître
            // la boucle. Les retirer rendait le contrôle aveugle : zéro boucle vue, verdict vert.
            if (in_array($id, [T_INLINE_HTML, T_COMMENT, T_DOC_COMMENT], TRUE))
            {
                // On garde les sauts de ligne pour ne pas décaler la numérotation.
                $texte = str_repeat("\n", substr_count($texte, "\n"));
            }
        }
        else
        {
            [$texte, $ligne] = [$token, NULL];
        }

        if ($ligne === NULL)
        {
            // Un token « caractère » n'emporte pas son numéro : on le colle à la dernière ligne vue.
            $ligne = $lignes ? array_key_last($lignes) : 1;
            $lignes[$ligne] = ($lignes[$ligne] ?? '').$texte;
            continue;
        }

        foreach (explode("\n", $texte) as $decalage => $morceau)
        {
            $n = $ligne + $decalage;
            $lignes[$n] = ($lignes[$n] ?? '').$morceau;
        }
    }

    ksort($lignes);

    return $lignes;
}

/**
 * Les fichiers PHP et gabarits d'un dossier.
 *
 * @return list<string>
 */
function sources(string $dossier): array
{
    return array_values(nf_fichiers([nf_relatif($dossier)], ['php'], [], FALSE));
}

$fautes  = [];
$boucles = 0;

foreach (DOSSIERS as $dossier)
{
    foreach (sources($racine.'/'.$dossier) as $fichier)
    {
        $code   = lignes_de_code($fichier);
        $brut   = file($fichier, FILE_IGNORE_NEW_LINES) ?: [];
        $rel    = str_replace($racine.DIRECTORY_SEPARATOR, '', str_replace('/', DIRECTORY_SEPARATOR, $fichier));

        // Variable de boucle => ligne où la boucle commence. Une variable réutilisée par une boucle
        // ultérieure écrase la précédente, ce qui est le comportement voulu.
        $variables = [];

        foreach ($code as $numero => $ligne)
        {
            foreach (SOURCES as $source)
            {
                if (strpos($ligne, $source) !== FALSE && preg_match('/\bas\s+(\$\w+)\s*\)/', $ligne, $m))
                {
                    $variables[$m[1]] = $numero;
                    $boucles++;
                    break;
                }
            }

            foreach ($variables as $variable => $depuis)
            {
                // `->name(` serait un appel de méthode, pas la lecture de propriété qu'on traque.
                $motif = '/'.preg_quote($variable, '/').'->name\b(?!\s*\()/';

                if (preg_match($motif, $ligne))
                {
                    // Une ligne qui porte déjà la bonne écriture n'est pas fautive.
                    if (preg_match('/'.preg_quote($variable, '/').'->info\(\)->name\b/', $ligne))
                    {
                        continue;
                    }

                    $fautes[] = [$rel, $numero, trim($brut[$numero - 1] ?? $ligne), $variable, $depuis];
                }
            }
        }
    }
}

if ($detail)
{
    printf("  %d boucle(s) sur des addons chargés examinée(s).\n\n", $boucles);
}

if (!$fautes)
{
    nf_ok(sprintf('aucune lecture `->name` sur un addon chargé (%d boucle(s) examinée(s))', $boucles));
}

echo "LECTURES `->name` SUR UN ADDON CHARGÉ — elles rendent FALSE, en silence :\n\n";

foreach ($fautes as [$rel, $ligne, $texte, $variable, $depuis])
{
    printf("  %s:%d\n", $rel, $ligne);
    printf("      %s  (boucle ouverte L%d)\n", mb_strimwidth($texte, 0, 96, '…'), $depuis);
    printf("      écrire : %s->info()->name\n\n", $variable);
}

echo "Le nom d'un addon chargé est dans `info()`. `->name` n'est une colonne que sur la LIGNE de la\n";
echo "table `nf_addon` — celle que rend `collection('addon')->get()`, où l'écriture reste correcte.\n";

nf_echec(count($fautes).' lecture(s) `->name` sur un addon chargé');
