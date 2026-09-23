<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Le contrat entre l'assistant d'ajout de widget et le balisage qu'il pilote.
 *
 * L'assistant vit dans `live-editor.js` (fonction `nfLeWizard`) et son comportement est éprouvé
 * dans un vrai navigateur — `tests/Browser/live-editor-wizard.test.html`. Mais cette épreuve
 * travaille sur une **maquette réduite**, parce qu'un navigateur ne sait pas rendre un gabarit PHP.
 * Rien ne garantirait donc que la VRAIE vue porte encore les crochets dont l'assistant dépend : on
 * pourrait renommer `data-role="widgets"` dans le gabarit, voir l'épreuve de navigateur rester
 * verte, et casser l'ajout de widget en production.
 *
 * Ce test ferme cet écart. Il ne juge pas l'apparence : il vérifie que chaque sélecteur utilisé par
 * l'assistant existe dans le gabarit, et que les champs envoyés au serveur n'ont pas changé de nom
 * — c'est le contrat avec le contrôleur et les deux checkers.
 */
final class LiveEditorWizardMarkupTest extends TestCase
{
	private static function racine(): string
	{
		return dirname(__DIR__, 2);
	}

	private static function gabarit(): string
	{
		return (string) file_get_contents(self::racine() . '/modules/live_editor/views/widget.tpl.php');
	}

	private static function assistant(): string
	{
		return (string) file_get_contents(self::racine() . '/modules/live_editor/js/live-editor.js');
	}

	public function test_les_champs_envoyes_au_serveur_gardent_leur_nom(): void
	{
		$vue = self::gabarit();

		// Le contrôleur lit $widget/$type/$title et les checkers font un post_check dessus : renommer
		// l'un d'eux ferait échouer le checker, qui répondrait 404 — indiscernable d'une mauvaise route.
		foreach (['widget', 'type', 'title'] as $champ)
		{
			$this->assertMatchesRegularExpression(
				'/name="' . $champ . '"/',
				$vue,
				"Le champ « $champ » doit rester dans le formulaire : c'est le contrat avec le serveur."
			);
		}

		$this->assertStringContainsString('id="live-editor-settings-form"', $vue,
			'Le formulaire garde son identifiant : la sérialisation et le bouton Valider s\'appuient dessus.');
	}

	public function test_le_gabarit_porte_les_crochets_de_l_assistant(): void
	{
		$vue = self::gabarit();

		$crochets = [
			'id="live-editor-settings-widget"' => 'le champ caché qui porte le widget choisi',
			'id="live-editor-settings-type"'   => 'le champ caché qui porte le type choisi',
			'id="live-editor-settings-title"'  => 'le champ de titre',
			'id="live-editor-settings"'        => 'le conteneur des réglages du widget',
			'data-role="widgets"'              => 'la liste des cartes de widgets',
			'data-role="types"'                => 'la liste des cartes de types',
			'data-role="filtre"'               => 'le champ de filtre',
			'data-role="vide"'                 => 'le message « aucun résultat »',
			'nf-le-wiz-step-n'                 => 'le numéro d\'étape, renuméroté à l\'affichage',
			'data-recherche='                  => 'le texte sur lequel le filtre travaille',
		];

		foreach ($crochets as $crochet => $role)
		{
			$this->assertStringContainsString($crochet, $vue, "Crochet manquant ($role) : « $crochet ».");
		}
	}

	public function test_les_quatre_etapes_et_leurs_panneaux_existent(): void
	{
		$vue = self::gabarit();

		/* Les boutons d'étape sont RENDUS PAR UNE BOUCLE : `data-step="widget"` n'apparaît donc pas
		   littéralement dans le gabarit, seulement l'attribut avec une interpolation PHP. On vérifie
		   l'attribut d'un côté, et les quatre clés de la boucle de l'autre. */
		$this->assertStringContainsString('data-step="', $vue,
			"Les boutons d'étape doivent porter data-step : c'est ce que lit l'assistant.");

		foreach (['widget', 'type', 'title', 'settings'] as $etape)
		{
			$this->assertMatchesRegularExpression('/\'' . $etape . '\'\s*=>/', $vue,
				"L'étape « $etape » doit figurer dans la liste des étapes du gabarit.");

			// Les panneaux, eux, sont écrits en clair.
			$this->assertStringContainsString('data-panel="' . $etape . '"', $vue,
				"L'étape « $etape » doit avoir son panneau.");
		}
	}

	public function test_les_cartes_portent_ce_que_l_assistant_lit(): void
	{
		$vue = self::gabarit();

		// Une carte de widget porte sa valeur ; une carte de type porte EN PLUS le widget auquel
		// elle appartient, puisque toutes sont rendues d'un coup puis filtrées à l'affichage.
		$this->assertMatchesRegularExpression('/class="nf-le-wiz-card"[^>]*role="option"/s', $vue,
			'Les cartes de widgets sont des options de listbox.');

		$this->assertStringContainsString('data-widget=', $vue,
			'Une carte de type doit dire à quel widget elle appartient.');

		$this->assertGreaterThanOrEqual(2, substr_count($vue, 'data-value='),
			'Les deux listes de cartes portent une valeur.');
	}

	public function test_la_modale_fournit_les_deux_boutons_de_navigation(): void
	{
		$modale = (string) file_get_contents(self::racine() . '/modules/live_editor/views/index.tpl.php');

		foreach (['wiz-prev', 'wiz-next'] as $action)
		{
			$this->assertStringContainsString('data-action="' . $action . '"', $modale,
				"La modale de réglages doit fournir le bouton « $action » à l'assistant.");
		}
	}

	/**
	 * L'assistant doit rester indépendant de la modale : c'est ce qui le rend éprouvable dans un
	 * navigateur sans monter l'application. S'il se remettait à interroger `modal`, l'épreuve de
	 * navigateur cesserait de pouvoir l'instancier — et on ne s'en apercevrait qu'à ce moment-là.
	 */
	public function test_l_assistant_ne_connait_ni_la_modale_ni_le_serveur(): void
	{
		$js    = self::assistant();
		$debut = strpos($js, 'window.nfLeWizard = function(form, options){');

		$this->assertNotFalse($debut, 'La fonction nfLeWizard doit exister et être exposée.');

		$fin  = strpos($js, "\nvar modal_settings = function", $debut);
		$this->assertNotFalse($fin, 'modal_settings doit suivre nfLeWizard.');

		$corps = substr($js, $debut, $fin - $debut);

		$this->assertStringNotContainsString('modal.', $corps,
			'nfLeWizard ne doit pas interroger la modale : la navigation lui est passée en paramètre.');
		$this->assertStringNotContainsString('NF.ajax', $corps,
			'nfLeWizard ne doit pas parler au serveur : le chargement des réglages est un rappel.');
	}
}
