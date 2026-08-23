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
}
