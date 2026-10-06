<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\Modules\Monitoring\Controllers\Index as Monitoring;

/**
 * La démo vit au présent (2026-10-06). Rejoué tel quel des semaines après avoir été écrit, `install/demo.sql` montrait
 * des « prochains » rendez-vous et matchs tous passés, et la frise de la saison se vidait : après chaque remise à zéro,
 * les dates des tables que l'instantané vide et remplit EN ENTIER avancent du temps écoulé depuis le jour qu'il porte
 * en tête. Les tables qu'il ne vide qu'en partie (les membres, que le compte réel partage) ne bougent jamais : décalées,
 * elles glisseraient un peu plus à chaque remise.
 */
final class DemoAuPresentTest extends TestCase
{
	private const INSTANTANE = "-- nf-demo-present: 2026-09-16\n"
		."DELETE FROM `nf_user` WHERE `id` NOT IN (1, 271);\n"
		."INSERT INTO `nf_user` (`id`) VALUES (271);\n"
		."DELETE FROM `nf_calendar_events`;\n"
		."INSERT INTO `nf_calendar_events` (`id`) VALUES (1);\n"
		."DELETE FROM `nf_news`;\n"
		."DELETE FROM `nf_forum`;\n";

	private const COLONNES = [
		'nf_user'            => ['registration_date', 'last_activity_date'],
		'nf_calendar_events' => ['start_at', 'end_at', 'created_at'],
		'nf_news'            => ['date'],
		'nf_forum'           => [],
	];

	public function test_seules_les_tables_videes_en_entier_avancent(): void
	{
		$sql = Monitoring::demo_au_present(self::INSTANTANE, self::COLONNES, 20);

		self::assertStringContainsString("UPDATE `nf_calendar_events` SET `start_at` = IF(`start_at` < '1000-01-02', `start_at`, `start_at` + INTERVAL 20 DAY), `end_at` = IF(", $sql);
		self::assertStringContainsString("`created_at` + INTERVAL 20 DAY) ORDER BY `start_at` DESC;", $sql);
		self::assertStringContainsString("UPDATE `nf_news` SET `date` = IF(`date` < '1000-01-02', `date`, `date` + INTERVAL 20 DAY) ORDER BY `date` DESC;", $sql);
		self::assertStringNotContainsString('nf_user', $sql, "les membres ne se vident qu'en partie : ils ne bougent pas");
		self::assertStringNotContainsString('nf_forum', $sql, 'une table sans date ne reçoit rien');
		self::assertStringStartsWith("START TRANSACTION;\n", $sql);
		self::assertStringEndsWith("\nCOMMIT;", $sql);
	}

	public function test_rien_ne_bouge_le_jour_meme_ni_avant(): void
	{
		self::assertSame('', Monitoring::demo_au_present(self::INSTANTANE, self::COLONNES, 0));
		self::assertSame('', Monitoring::demo_au_present(self::INSTANTANE, self::COLONNES, -3));
	}

	public function test_l_instantane_publie_porte_son_jour(): void
	{
		$demo = (string) file_get_contents(__DIR__.'/../../install/demo.sql');

		self::assertMatchesRegularExpression('/^-- nf-demo-present: \d{4}-\d{2}-\d{2}$/m', $demo);
		// Ce qu'il vide en entier, il le vide sous la forme que la remise à zéro reconnaît.
		self::assertGreaterThan(20, preg_match_all('/^DELETE FROM `nf_[a-z0-9_]+`;$/m', $demo));
		self::assertMatchesRegularExpression('/^DELETE FROM `nf_calendar_events`;$/m', $demo);
	}
}
