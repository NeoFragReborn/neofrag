<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * tools/lib/cascade.php — qui gagne entre la feuille d'un thème et celle d'un addon.
 *
 * La feuille d'un widget est servie APRÈS celle du thème : à égalité de spécificité, l'addon gagne
 * et la déclaration du thème est morte (2026-10-13, Pulse et quatre widgets). `check-cascade`
 * refuse ces déclarations ; ce test fige SURTOUT ce qu'il doit laisser passer. Une forme ajoutée
 * à un contrôle s'éprouve sur ce qu'elle ne doit pas attraper (leçon 12) : la première version de
 * celui-ci comptait 49 déclarations, dont 26 à tort — une règle `:hover` d'addon n'écrase pas la
 * déclaration au repos, et `[href$="/logout"]` ne vise qu'un lien sur dix.
 *
 * Il tourne dans son propre processus, comme ToolsSqlTest : la bibliothèque des outils définit
 * `nf_refus()`, comme le CMS.
 */
#[RunTestsInSeparateProcesses]
final class ToolsCascadeTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../tools/lib/cascade.php';
    }

    // ── Ce que le contrôle doit attraper ────────────────────────────────────

    public function test_le_meme_selecteur_de_part_et_dautre_gagne_a_laddon(): void
    {
        $this->assertTrue(nf_cascade_couvre('.nf-chiffre-valeur', '.nf-chiffre-valeur'));
    }

    public function test_le_nom_de_lelement_ne_sauve_pas_le_theme(): void
    {
        // `span.x` nomme l'élément que le module écrit lui-même : toutes les instances sont des span.
        $this->assertTrue(nf_cascade_couvre('span.monitoring-icon-status', '.monitoring-icon-status'));
    }

    public function test_le_meme_etat_des_deux_cotes_gagne_a_laddon(): void
    {
        $this->assertTrue(nf_cascade_couvre('.nf-salon-rejoindre:hover', '.nf-salon-rejoindre:hover'));
    }

    public function test_un_pseudo_element_compte_comme_un_etat_partage(): void
    {
        $this->assertTrue(nf_cascade_couvre('.nf-en-ligne-avatar::after', '.nf-en-ligne-avatar::after'));
    }

    // ── Ce que le contrôle doit LAISSER ─────────────────────────────────────

    public function test_un_etat_de_laddon_nefface_pas_la_declaration_au_repos(): void
    {
        // Le piège qui faisait crier 26 fois à tort : `.fc-event:hover` ne dit rien du repos.
        $this->assertFalse(nf_cascade_couvre('.fc-event:hover', '.fc .fc-event'));
    }

    public function test_un_attribut_de_laddon_ne_vise_quune_partie_des_elements(): void
    {
        $this->assertFalse(nf_cascade_couvre(
            '.nf-espace-lien[href$="/logout"]:hover',
            '.nf-espace-menu .nf-espace-lien:hover'
        ));
    }

    public function test_un_addon_plus_classe_que_le_theme_vise_autre_chose(): void
    {
        $this->assertFalse(nf_cascade_couvre('.nf-membre-onglet.is-actif', '.nf-membre-onglet'));
    }

    public function test_un_theme_plus_specifique_gagne(): void
    {
        $this->assertFalse(nf_cascade_couvre('.nf-chiffre-valeur', 'body .nf-chiffre-valeur'));
        $this->assertFalse(nf_cascade_couvre('.nf-chiffre-valeur', '.pl-page .nf-chiffre-valeur'));
    }

    public function test_un_bloc_de_contexte_suffit_a_proteger_le_theme(): void
    {
        // C'est la réparation : un ancêtre ajoute une classe, et le thème passe devant. Avec les
        // restrictions ci-dessus, aucune règle d'addon à un seul bloc ne peut rattraper cela — une
        // règle qui monterait sa spécificité le ferait par une classe, un état ou un attribut de
        // plus, donc en visant moins d'éléments.
        $this->assertFalse(nf_cascade_couvre('.nf-chiffre.grand', '.ex-page > .nf-chiffre.grand'));
        $this->assertFalse(nf_cascade_couvre('.nf-chiffre.grand:hover', '.ex-page .nf-chiffre.grand'));
    }

    public function test_important_du_theme_gagne_toujours(): void
    {
        $this->assertFalse(nf_cascade_couvre('.nf-chiffre-valeur', '.nf-chiffre-valeur', TRUE));
    }

    public function test_un_addon_a_plusieurs_blocs_depend_du_contexte_et_nest_pas_jugé(): void
    {
        $this->assertFalse(nf_cascade_couvre('.panel .nf-chiffre-valeur', '.nf-chiffre-valeur'));
    }

    public function test_un_addon_sans_classe_nest_pas_jugé(): void
    {
        $this->assertFalse(nf_cascade_couvre('h3', '.nf-chiffre h3'));
    }

    // ── La spécificité ──────────────────────────────────────────────────────

    public function test_la_specificite_compte_classes_attributs_et_pseudo_classes(): void
    {
        $this->assertSame([0, 1, 0], nf_cascade_specificite('.a'));
        $this->assertSame([0, 1, 1], nf_cascade_specificite('span.a'));
        $this->assertSame([0, 2, 0], nf_cascade_specificite('.a:hover'));
        $this->assertSame([0, 2, 0], nf_cascade_specificite('.a[href]'));
        $this->assertSame([1, 0, 0], nf_cascade_specificite('#a'));
        $this->assertSame([0, 1, 1], nf_cascade_specificite('.a::after'));
    }

    public function test_le_contenu_de_not_compte_mais_pas_son_enveloppe(): void
    {
        $this->assertSame([0, 2, 0], nf_cascade_specificite('.a:not(.b)'));
    }

    // ── Ce qu'une déclaration pose vraiment ─────────────────────────────────

    public function test_une_forme_courte_pose_son_contenu(): void
    {
        // Le piège de la leçon 13 : `font:` pose aussi `line-height`.
        $this->assertTrue(nf_cascade_conflit('font', 'line-height'));
        $this->assertTrue(nf_cascade_conflit('background', 'background-color'));
        $this->assertTrue(nf_cascade_conflit('border', 'border-left-color'));
        $this->assertTrue(nf_cascade_conflit('margin', 'margin-top'));
        $this->assertTrue(nf_cascade_conflit('gap', 'row-gap'));
        $this->assertTrue(nf_cascade_conflit('font', 'font'));   // deux formes courtes se croisent par leur contenu
    }

    public function test_une_forme_courte_ne_pose_pas_ce_quelle_ne_touche_pas(): void
    {
        // `border: 1px solid red` ne pose PAS `border-radius` : les ranger ensemble ferait crier à tort.
        $this->assertFalse(nf_cascade_conflit('border', 'border-radius'));
        $this->assertFalse(nf_cascade_conflit('border', 'border-collapse'));
        $this->assertFalse(nf_cascade_conflit('color', 'background-color'));
        $this->assertFalse(nf_cascade_conflit('overflow-x', 'overflow-y'));
    }

    public function test_un_prefixe_proprietaire_ne_change_rien(): void
    {
        $this->assertTrue(nf_cascade_conflit('-webkit-transition', 'transition-delay'));
    }

    // ── La lecture d'une feuille ────────────────────────────────────────────

    public function test_une_regle_dans_une_couche_est_marquee_comme_telle(): void
    {
        $regles = nf_cascade_regles("@layer nf-repli {\n.beat-slow { animation: beat 1s; }\n}\n.autre { color: red; }\n");

        $this->assertCount(2, $regles);
        $this->assertTrue($regles[0]['couche']);
        $this->assertFalse($regles[1]['couche']);
    }

    public function test_une_regle_dans_un_media_garde_son_selecteur(): void
    {
        $regles = nf_cascade_regles("@media (max-width: 600px) {\n  .a, .b { color: red; }\n}\n");

        $this->assertSame(['.a', '.b'], array_column($regles, 'selecteur'));
        $this->assertSame(['color', 'color'], array_column($regles, 'propriete'));
    }

    public function test_un_gabarit_php_se_lit_comme_du_css(): void
    {
        // Les feuilles de thème sont des gabarits : ce qu'un bloc PHP interpole ne doit pas tout casser.
        $regles = nf_cascade_regles(".a { color: <?= \$x ?>; }\n.b { margin: 0; }\n");

        $this->assertSame(['.a', '.b'], array_column($regles, 'selecteur'));
    }

    public function test_important_est_releve(): void
    {
        $regles = nf_cascade_regles('.a { color: red !important; }');

        $this->assertTrue($regles[0]['important']);
    }

    public function test_un_commentaire_ne_coupe_pas_la_lecture(): void
    {
        $regles = nf_cascade_regles("/* une note { avec une accolade } */\n.a { color: red; }\n");

        $this->assertSame(['.a'], array_column($regles, 'selecteur'));
    }
}
