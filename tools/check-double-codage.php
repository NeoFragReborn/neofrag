<?php
declare(strict_types=1);

/**
 * check-double-codage — un texte se pose dans une page par nf_texte(), qui décode puis échappe : jamais codé deux fois.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi ce contrôle existe
 * ---------------------------
 * Le site range ses textes codés pour le web : le formulaire enregistre « é » sous la forme `&eacute;`
 * et « — » sous la forme `&mdash;`, l'API code les balises. D'autres textes arrivent bruts : le SQL
 * livré, un service extérieur, une traduction. Une page qui passait un texte rangé par
 * `htmlspecialchars()` le codait une seconde fois — `&amp;eacute;` — et le visiteur lisait
 * « &eacute; » au lieu de « é ».
 *
 * Le mainteneur l'a vu le 2026-10-05 dans le bloc « Sur le forum » de la vitrine. L'inventaire qui a suivi a
 * trouvé 1 080 appels de cette forme dans 213 fichiers : titres de conversation, Boutique, Dons, nom du
 * site dans les thèmes, pseudos, libellés, flux RSS. Corriger les vues une à une n'aurait rien
 * empêché : la suivante aurait repris la forme qui s'écrit naturellement.
 *
 * La règle tient en une fonction du cœur, `nf_texte()` (neofrag/helpers/string.php) : décoder, puis
 * échapper une seule fois. Le résultat est juste, que le texte soit rangé codé ou brut.
 *
 * Ce que le contrôle vérifie
 * --------------------------
 * Dans le code de produit, il refuse :
 *   - tout appel à `htmlspecialchars()` ou `htmlentities()` ;
 *   - un appel à `utf8_htmlentities()` sur une ligne qui produit du HTML (`echo`, gabarit, chaîne qui
 *     porte une balise ou un attribut). Ailleurs, `utf8_htmlentities()` est le codage à
 *     l'ENREGISTREMENT — celui du formulaire —, qui reste juste.
 *
 * Seule exception : ce qui doit se lire tel quel, entités comprises — une ligne du journal, du JSON ou
 * du HTML posé dans un attribut (décoder ce HTML réveillerait les balises qu'il cite). Elle s'annote
 * d'un commentaire `codage: <raison>` sur la ligne de l'appel ou sur celle qui la précède.
 *
 * L'analyse passe par le tokeniseur PHP : un commentaire ou une chaîne qui CITE la fonction ne compte
 * pas.
 *
 * Usage
 * -----
 *   php tools/check-double-codage.php             liste les appels fautifs, code 1 s'il y en a
 *   php tools/check-double-codage.php --epreuve   ne joue QUE l'épreuve à l'envers
 *
 * L'épreuve à l'envers tourne de toute façon avant chaque analyse : un défaut planté doit être
 * refusé, une écriture correcte doit passer en silence — sans quoi le contrôle refuse de conclure.
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o] = nf_options(['epreuve' => FALSE]);

/** Les fonctions qui échappent sans décoder ; la troisième seulement quand la ligne produit du HTML. */
const TOUJOURS   = ['htmlspecialchars', 'htmlentities'];
const A_LA_SORTIE = ['utf8_htmlentities'];

/**
 * Les appels fautifs d'une source PHP.
 *
 * @return list<array{int, string}> [ligne, fonction]
 */
function analyser_source(string $src): array
{
    $tokens   = token_get_all($src);
    $annotees = [];
    $sorties  = [];

    // Premier passage : les lignes annotées, et celles qui produisent du HTML.
    foreach ($tokens as $t)
    {
        if (!is_array($t))
        {
            continue;
        }

        [$id, $texte, $ligne] = $t;
        $fin = $ligne + substr_count($texte, "\n");

        if (in_array($id, [T_COMMENT, T_DOC_COMMENT], TRUE) && preg_match('/\bcodage\s*:/u', $texte))
        {
            for ($l = $ligne; $l <= $fin; $l++)
            {
                $annotees[$l] = TRUE;
            }
        }

        if (in_array($id, [T_ECHO, T_OPEN_TAG_WITH_ECHO, T_PRINT], TRUE)
            || ($id === T_INLINE_HTML && trim($texte) !== '')
            || (in_array($id, [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], TRUE) && preg_match('/<\/?[a-z!]|="/i', $texte)))
        {
            for ($l = $ligne; $l <= $fin; $l++)
            {
                $sorties[$l] = TRUE;
            }
        }
    }

    $fautes = [];

    foreach ($tokens as $i => $t)
    {
        // `\htmlspecialchars` est un seul jeton depuis PHP 8 (T_NAME_FULLY_QUALIFIED).
        if (!is_array($t) || !in_array($t[0], [T_STRING, T_NAME_FULLY_QUALIFIED], TRUE))
        {
            continue;
        }

        $nom = strtolower(ltrim($t[1], '\\'));

        if (!in_array($nom, array_merge(TOUJOURS, A_LA_SORTIE), TRUE) || !appel_de_fonction($tokens, $i))
        {
            continue;
        }

        $ligne = $t[2];

        if (isset($annotees[$ligne]) || isset($annotees[$ligne - 1]))
        {
            continue;
        }

        if (in_array($nom, TOUJOURS, TRUE) || isset($sorties[$ligne]))
        {
            $fautes[] = [$ligne, $nom];
        }
    }

    return $fautes;
}

/** Le jeton désigne-t-il l'appel d'une fonction globale — ni méthode, ni définition, ni constante ? */
function appel_de_fonction(array $tokens, int $i): bool
{
    $ignores = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];

    for ($k = $i + 1; isset($tokens[$k]) && is_array($tokens[$k]) && in_array($tokens[$k][0], $ignores, TRUE); $k++);

    if (($tokens[$k] ?? NULL) !== '(')
    {
        return FALSE;
    }

    for ($k = $i - 1; $k >= 0 && is_array($tokens[$k]) && in_array($tokens[$k][0], array_merge($ignores, [T_NS_SEPARATOR]), TRUE); $k--);

    return !(isset($tokens[$k]) && is_array($tokens[$k])
        && in_array($tokens[$k][0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW], TRUE));
}

/**
 * L'épreuve à l'envers : des défauts plantés, des écritures correctes.
 *
 * @return list<string> les échecs de l'épreuve
 */
function epreuve(): array
{
    $echecs = [];

    $defauts = [
        'gabarit'                         => "<h3><?php echo htmlspecialchars(\$t['title']) ?></h3>",
        'contrôleur'                      => "<?php \$html .= '<td>'.htmlspecialchars((string) \$c['name']).'</td>';",
        'attribut'                        => "<a title=\"<?= htmlspecialchars(\$a['nom'], ENT_QUOTES) ?>\">",
        'htmlentities'                    => "<?php \$s = htmlentities(\$x);",
        'espace de noms'                  => "<?php namespace A; echo \\htmlspecialchars(\$x);",
        'utf8_htmlentities à l\'affichage' => "<?php echo '<b>'.utf8_htmlentities(\$n).'</b>';",
        'annotation trop loin'            => "<?php // codage: brut\n\necho htmlspecialchars(\$x);",
    ];

    $propres = [
        'nf_texte'                        => "<h3><?php echo nf_texte(\$t['title']) ?></h3>",
        'annoté sur la ligne'             => "<?php echo '<i data-x=\"'.htmlspecialchars(\$json).'\">'; // codage: du JSON dans un attribut",
        'annoté la ligne d\'avant'        => "<?php\n// codage: la ligne du journal, telle quelle\necho htmlspecialchars(\$l);",
        'codage à l\'enregistrement'      => "<?php \$v = utf8_htmlentities(trim(\$v));",
        'commentaire qui cite'            => "<?php // htmlspecialchars(\$x) codait deux fois\n\$a = 1;",
        'chaîne qui cite'                 => "<?php \$s = 'htmlspecialchars(';",
        'méthode du même nom'             => "<?php \$o->htmlspecialchars(\$x);",
    ];

    foreach ($defauts as $nom => $src)
    {
        if (!analyser_source($src))
        {
            $echecs[] = "défaut NON vu : $nom";
        }
    }

    foreach ($propres as $nom => $src)
    {
        if (analyser_source($src))
        {
            $echecs[] = "faux positif : $nom";
        }
    }

    return $echecs;
}

$echecs = epreuve();

if ($o['epreuve'])
{
    foreach ($echecs as $echec)
    {
        echo "  $echec\n";
    }

    $echecs ? nf_echec('épreuve à l\'envers : le contrôle est aveugle ou trop bavard') : nf_ok('épreuve à l\'envers : les défauts plantés sont refusés, les écritures correctes passent');
}

if ($echecs)
{
    // Un contrôle qui ne voit plus ce qu'il vise ne doit pas se dire vert.
    nf_refus('épreuve à l\'envers ratée — '.implode(' ; ', $echecs));
}

$fautes   = [];
$fichiers = 0;

foreach (nf_fichiers(NF_DOSSIERS_PRODUIT, ['php']) as $rel => $chemin)
{
    $fichiers++;

    foreach (analyser_source((string) file_get_contents($chemin)) as [$ligne, $fonction])
    {
        $fautes[] = [$rel.':'.$ligne, $fonction];
    }
}

if (!$fautes)
{
    nf_ok(sprintf('tout texte posé dans une page passe par nf_texte() (%d fichier(s), épreuve à l\'envers passée)', $fichiers));
}

echo "TEXTES CODÉS DEUX FOIS — un texte rangé codé (`&eacute;`) s'afficherait « &eacute; » :\n\n";

foreach ($fautes as [$lieu, $fonction])
{
    echo "  $lieu  $fonction()\n";
}

echo "\nÉcrire nf_texte(…), qui décode puis échappe une seule fois. Ce qui doit se lire tel quel (une ligne\n";
echo "du journal, du JSON ou du HTML posé dans un attribut) s'annote « codage: <raison> » sur la ligne.\n";

nf_echec(count($fautes).' appel(s) qui codent un texte sans le décoder');
