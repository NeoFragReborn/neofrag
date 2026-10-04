<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use NF\Modules\Surveys\Surveys;

/**
 * Quand les résultats d'un sondage se voient : la règle du réglage « Afficher les résultats ».
 *
 * La page du sondage les montrait à quiconque avait voté ou dès la fermeture, quel que soit le
 * réglage, et le widget les montrait toujours (2026-10-04). `Surveys::resultats_visibles()` est
 * désormais la seule règle, pour la page comme pour le widget — fonction pure, épreuve pure.
 */
final class SondagesResultatsTest extends TestCase
{
	/** @return array<string, array{string, bool, bool, bool, bool}> réglage, a voté, fermé, gestionnaire, visible */
	public static function cas(): array
	{
		return [
			'toujours, avant tout vote'                => ['always',     FALSE, FALSE, FALSE, TRUE],
			'après le vote : pas encore voté'          => ['after_vote', FALSE, FALSE, FALSE, FALSE],
			'après le vote : a voté'                   => ['after_vote', TRUE,  FALSE, FALSE, TRUE],
			'après le vote : fermé sans avoir voté'    => ['after_vote', FALSE, TRUE,  FALSE, TRUE],
			'à la fermeture : a voté, encore ouvert'   => ['closed',     TRUE,  FALSE, FALSE, FALSE],
			'à la fermeture : fermé'                   => ['closed',     FALSE, TRUE,  FALSE, TRUE],
			'jamais : a voté'                          => ['never',      TRUE,  FALSE, FALSE, FALSE],
			'jamais : fermé'                           => ['never',      TRUE,  TRUE,  FALSE, FALSE],
			'jamais : le gestionnaire les voit'        => ['never',      FALSE, FALSE, TRUE,  TRUE],
			'à la fermeture : le gestionnaire les voit'=> ['closed',     FALSE, FALSE, TRUE,  TRUE],
			'réglage inconnu : le défaut, après vote'  => ['autre',      FALSE, FALSE, FALSE, FALSE],
			'réglage inconnu, a voté'                  => ['autre',      TRUE,  FALSE, FALSE, TRUE],
		];
	}

	#[DataProvider('cas')]
	public function test_la_regle(string $reglage, bool $a_vote, bool $ferme, bool $gestionnaire, bool $visible): void
	{
		self::assertSame($visible, Surveys::resultats_visibles($reglage, $a_vote, $ferme, $gestionnaire));
	}

	public function test_la_page_et_le_widget_suivent_la_meme_regle(): void
	{
		$racine = __DIR__.'/../..';
		$page   = (string) file_get_contents($racine.'/modules/surveys/controllers/index.php');
		$widget = (string) file_get_contents($racine.'/widgets/surveys/controllers/index.php');

		self::assertStringContainsString('Surveys::resultats_visibles(', $page);
		self::assertStringContainsString('Surveys::resultats_visibles(', $widget);
		self::assertStringContainsString("'show_results'", $widget, 'le widget lit le réglage du sondage');
		self::assertStringNotContainsString("\$survey['show_results'] === 'always'", $page, 'plus de règle recopiée à la main');
	}

	/** La liste des sondages : le total des votes est un résultat, il suit la règle (« Jamais » le montrait). */
	public function test_la_liste_cache_le_total_quand_les_resultats_sont_caches(): void
	{
		$racine  = __DIR__.'/../..';
		$page    = (string) file_get_contents($racine.'/modules/surveys/controllers/index.php');
		$checker = (string) file_get_contents($racine.'/modules/surveys/controllers/checker.php');

		self::assertSame(1, preg_match('/public function index\(\$surveys\)(.*?)\n\t\}/s', $page, $liste));
		self::assertStringContainsString('Surveys::sondages_votes()', $liste[1]);
		self::assertStringContainsString('Surveys::resultats_visibles(', $liste[1]);
		self::assertMatchesRegularExpression("/\\\$visible \? '<small class=\"text-muted\">'\.\\\$this->lang\('%d vote\|%d votes'/", $liste[1]);
		self::assertStringContainsString("'s.show_results'", $checker, 'la liste lit le réglage de chaque sondage');
	}
}
