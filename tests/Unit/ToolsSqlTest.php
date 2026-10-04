<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * tools/lib/sql.php — lire les lignes d'un fichier SQL livré, sans base.
 *
 * Fige le défaut du 2026-10-04 : nf_sql_tuples() s'arrêtait au premier commentaire glissé entre deux
 * tuples. 59 des 157 réglages de install/seed.sql échappaient ainsi à qui la lisait — d'abord au
 * contrôle des clés en double, puis au relevé des couplages par la base livrée.
 *
 * Il tourne dans son propre processus, comme ToolsPaquetTest : la bibliothèque des outils définit
 * `nf_refus()`, comme le CMS.
 */
#[RunTestsInSeparateProcesses]
final class ToolsSqlTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../tools/lib/sql.php';
    }

    public function test_un_commentaire_entre_deux_tuples_ne_coupe_pas_la_lecture(): void
    {
        $sql = "INSERT INTO `nf_settings` (`name`, `value`) VALUES\n"
             . "('a', '1'),\n"
             . "-- une note sur le suivant,\n"
             . "-- sur deux lignes\n"
             . "('b', '2'),\n"
             . "('c', 'x -- pas un commentaire');\n";

        $lu = nf_sql_tuples($sql, 'nf_settings');

        $this->assertSame(['name', 'value'], $lu['colonnes']);
        $this->assertSame([['a', '1'], ['b', '2'], ['c', 'x -- pas un commentaire']], array_column($lu['tuples'], 'valeurs'));
    }

    public function test_un_tuple_garde_sa_position_apres_un_commentaire(): void
    {
        $sql   = "INSERT INTO `nf_t` (`x`) VALUES\n-- une note\n('y');\n";
        $tuple = nf_sql_tuples($sql, 'nf_t')['tuples'][0];

        $this->assertSame("('y')", substr($sql, $tuple['debut'], $tuple['fin'] - $tuple['debut']));
    }
}
