<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\Modules\Events\Lib\Recurrence;

/**
 * Tests unitaires PURS du calcul des dates d'une série récurrente (locator-free). Pin le contrat
 * consommé par modules/events/controllers/admin.php::add() pour matérialiser les occurrences.
 */
final class EventRecurrenceTest extends TestCase
{
	public function test_no_recurrence_yields_single_occurrence(): void
	{
		$dates = Recurrence::dates('2026-07-01 18:00:00', '', '', 5);
		$this->assertCount(1, $dates, 'fréquence vide → une seule occurrence quel que soit count');
		$this->assertSame('2026-07-01 18:00:00', $dates[0][0]);
		$this->assertSame('', $dates[0][1]);
	}

	public function test_weekly_steps_by_seven_days(): void
	{
		$dates = Recurrence::dates('2026-07-01 18:00:00', '', 'weekly', 3);
		$this->assertSame(['2026-07-01 18:00:00', '2026-07-08 18:00:00', '2026-07-15 18:00:00'], array_column($dates, 0));
	}

	public function test_daily_steps_by_one_day(): void
	{
		$dates = Recurrence::dates('2026-07-01 09:30:00', '', 'daily', 2);
		$this->assertSame(['2026-07-01 09:30:00', '2026-07-02 09:30:00'], array_column($dates, 0));
	}

	public function test_monthly_steps_by_one_month(): void
	{
		$dates = Recurrence::dates('2026-01-15 12:00:00', '', 'monthly', 3);
		$this->assertSame(['2026-01-15 12:00:00', '2026-02-15 12:00:00', '2026-03-15 12:00:00'], array_column($dates, 0));
	}

	public function test_duration_is_preserved_per_occurrence(): void
	{
		// Durée = 2 h ; chaque occurrence garde la même durée, décalée d'une semaine.
		$dates = Recurrence::dates('2026-07-01 18:00:00', '2026-07-01 20:00:00', 'weekly', 2);
		$this->assertSame(['2026-07-01 20:00:00', '2026-07-08 20:00:00'], array_column($dates, 1));
	}

	public function test_empty_date_end_stays_empty(): void
	{
		$dates = Recurrence::dates('2026-07-01 18:00:00', '', 'weekly', 2);
		$this->assertSame(['', ''], array_column($dates, 1));
	}

	public function test_count_is_clamped(): void
	{
		$this->assertCount(Recurrence::MAX_OCCURRENCES, Recurrence::dates('2026-07-01 18:00:00', '', 'weekly', 999));
		$this->assertCount(1, Recurrence::dates('2026-07-01 18:00:00', '', 'weekly', 0));
	}

	public function test_invalid_date_yields_empty(): void
	{
		$this->assertSame([], Recurrence::dates('not-a-date', '', 'weekly', 3));
	}

	// ── Édition « toute la série » : ce qui se recopie, et ce qui ne DOIT pas ──────────────
	//
	// Une fois la série matérialisée par Recurrence, l'administration peut éditer les douze
	// occurrences d'un coup. Ce qu'elle recopie est une liste blanche, et l'absence des colonnes de
	// DATE y est la seule chose qui compte vraiment : les dates sont tout ce qui distingue une
	// séance de la suivante. Les propager ferait tenir la série entière le même soir, en perdant
	// définitivement les dates d'origine — un dégât qu'aucun retour arrière de l'écran ne rattrape.
	//
	// La liste est lue dans le FICHIER et non via la classe : le modèle étend un `Loadable` du
	// framework, qui ne s'instancie pas hors d'une requête. Lire la source suffit ici, puisque ce
	// qu'on éprouve est la déclaration elle-même.

	/** @return list<string> */
	private function series_fields(): array
	{
		$source = (string) file_get_contents(__DIR__ . '/../../modules/events/models/events.php');

		$this->assertMatchesRegularExpression(
			'/const SERIES_FIELDS = \[(.*?)\];/s',
			$source,
			'la liste blanche doit rester une constante inspectable'
		);

		preg_match('/const SERIES_FIELDS = \[(.*?)\];/s', $source, $m);
		preg_match_all("/'([a-z_]+)'/", $m[1], $champs);

		return $champs[1];
	}

	public function test_series_edit_never_propagates_a_date(): void
	{
		$champs = $this->series_fields();

		$this->assertNotEmpty($champs);

		foreach (['date', 'date_end', 'publish_date', 'reminder_sent_at'] as $interdit)
		{
			$this->assertNotContains($interdit, $champs,
				"« $interdit » ne doit JAMAIS être recopié sur toute la série : les dates distinguent les occurrences");
		}
	}

	public function test_series_edit_propagates_what_makes_an_event_the_same(): void
	{
		$champs = $this->series_fields();

		// Ce qui décrit l'événement, par opposition à ce qui situe l'occurrence dans le temps.
		foreach (['title', 'type_id', 'description', 'location', 'published'] as $attendu)
		{
			$this->assertContains($attendu, $champs);
		}
	}

	public function test_series_edit_uses_the_shared_list(): void
	{
		$source = (string) file_get_contents(__DIR__ . '/../../modules/events/models/events.php');

		// Une liste recopiée à la main dans edit_series() pourrait diverger de la constante sans que
		// rien ne le signale — et c'est la constante que les tests ci-dessus inspectent.
		$this->assertStringContainsString(
			'array_flip(self::SERIES_FIELDS)',
			$source,
			'edit_series() doit filtrer par la constante, pas par une liste à elle'
		);
	}
}
