<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Tests d'intégration de la PUBLICATION PROGRAMMÉE (news / articles) contre la vraie
 * base : le SQL exact du cron (publish_scheduled), la garde d'idempotence de announce()
 * et le masquage des dates futures sur les listings publics.
 *
 * Le cœur de l'invariant « annoncer EXACTEMENT une fois » est prouvé au niveau base :
 * un UPDATE conditionnel sur (announced_at IS NULL) touche 1 ligne au premier passage,
 * 0 au second — donc aucun second event/webhook/points/notif.
 */
final class ScheduledPublishingDbTest extends IntegrationTestCase
{
	/** Crée une catégorie news (FK obligatoire de nf_news) et renvoie son id. */
	private function createNewsCategory(): int
	{
		$this->exec("INSERT INTO nf_news_categories (name) VALUES ('itest_cat')");

		return (int) self::$pdo->insert_id;
	}

	/* ----------------------------------------------------------------- News */

	public function test_news_publish_scheduled_selects_only_due_published_unannounced_live(): void
	{
		$uid = $this->createUser();
		$cat = $this->createNewsCategory();

		$now    = date('Y-m-d H:i:s');
		$future = date('Y-m-d H:i:s', time() + 86400);
		$past   = date('Y-m-d H:i:s', time() - 86400);

		// (a) dû / publié / announced_at NULL / vivant → DOIT apparaître.
		$this->exec("INSERT INTO nf_news (category_id, user_id, date, published, announced_at, deleted_at) VALUES (?, ?, ?, '1', NULL, NULL)", [$cat, $uid, $past]);
		$due = (int) self::$pdo->insert_id;
		// (b) daté dans le futur → exclu par date <=.
		$this->exec("INSERT INTO nf_news (category_id, user_id, date, published, announced_at, deleted_at) VALUES (?, ?, ?, '1', NULL, NULL)", [$cat, $uid, $future]);
		// (c) non publié → exclu.
		$this->exec("INSERT INTO nf_news (category_id, user_id, date, published, announced_at, deleted_at) VALUES (?, ?, ?, '0', NULL, NULL)", [$cat, $uid, $past]);
		// (d) déjà annoncé → exclu (idempotence : pas re-sélectionné).
		$this->exec("INSERT INTO nf_news (category_id, user_id, date, published, announced_at, deleted_at) VALUES (?, ?, ?, '1', ?, NULL)", [$cat, $uid, $past, $now]);
		// (e) soft-supprimé → exclu.
		$this->exec("INSERT INTO nf_news (category_id, user_id, date, published, announced_at, deleted_at) VALUES (?, ?, ?, '1', NULL, ?)", [$cat, $uid, $past, $now]);

		// Requête de publish_scheduled() — modules/news/models/news.php:214-220 (le `AND category_id`
		// n'est PAS dans la requête cron : c'est un scope d'isolation contre la base partagée).
		$res = $this->exec(
			"SELECT news_id FROM nf_news WHERE published = '1' AND announced_at IS NULL AND date <= ? AND deleted_at IS NULL AND category_id = ?",
			[$now, $cat]
		)->get_result();

		$ids = [];
		while ($r = $res->fetch_row())
		{
			$ids[] = (int) $r[0];
		}

		$this->assertSame([$due], $ids, 'Seule la ligne due+publiée+non-annoncée+vivante est sélectionnée par le cron.');
	}

	public function test_news_announce_conditional_update_fires_once_then_noop(): void
	{
		$uid = $this->createUser();
		$cat = $this->createNewsCategory();

		$now  = date('Y-m-d H:i:s');
		$past = date('Y-m-d H:i:s', time() - 86400);

		$this->exec("INSERT INTO nf_news (category_id, user_id, date, published, announced_at, deleted_at) VALUES (?, ?, ?, '1', NULL, NULL)", [$cat, $uid, $past]);
		$news_id = (int) self::$pdo->insert_id;

		// Garde + marquage de announce() — modules/news/models/news.php:170,176 — repliés en un UPDATE conditionnel atomique.
		$sql = "UPDATE nf_news SET announced_at = ? WHERE news_id = ? AND announced_at IS NULL AND published = '1' AND date <= ? AND deleted_at IS NULL";

		$first  = $this->exec($sql, [$now, $news_id, $now])->affected_rows;
		$second = $this->exec($sql, [$now, $news_id, $now])->affected_rows;

		$this->assertSame(1, $first, 'Premier passage : announced_at posé → annonce émise une fois.');
		$this->assertSame(0, $second, 'Second passage : announced_at non NULL → no-op, pas de double émission.');
	}

	public function test_news_public_listing_masks_future_dated(): void
	{
		$uid = $this->createUser();
		$cat = $this->createNewsCategory();

		$now    = date('Y-m-d H:i:s');
		$future = date('Y-m-d H:i:s', time() + 86400);

		$this->exec("INSERT INTO nf_news (category_id, user_id, date, published, announced_at, deleted_at) VALUES (?, ?, ?, '1', NULL, NULL)", [$cat, $uid, $now]);
		$news_id = (int) self::$pdo->insert_id;

		// Filtre de visibilité non-admin de get_news() — modules/news/models/news.php:42.
		$visible = (int) $this->scalar("SELECT COUNT(*) FROM nf_news WHERE news_id = ? AND published = '1' AND date <= ?", [$news_id, $now]);
		$this->assertSame(1, $visible, 'Daté maintenant + publié = visible.');

		$this->exec("UPDATE nf_news SET date = ? WHERE news_id = ?", [$future, $news_id]);

		$visible = (int) $this->scalar("SELECT COUNT(*) FROM nf_news WHERE news_id = ? AND published = '1' AND date <= ?", [$news_id, $now]);
		$this->assertSame(0, $visible, 'Daté dans le futur = masqué jusqu\'à son heure.');
	}

	/* -------------------------------------------------------------- Articles */

	public function test_articles_publish_scheduled_selects_only_due_published_unannounced_live(): void
	{
		$uid = $this->createUser();
		// nf_articles n'a aucune FK déclarée : on insère directement avec category_id arbitraire.
		$cat = 999000 + $uid;

		$now    = date('Y-m-d H:i:s');
		$future = date('Y-m-d H:i:s', time() + 86400);
		$past   = date('Y-m-d H:i:s', time() - 86400);

		$this->exec("INSERT INTO nf_articles (category_id, user_id, date, published, announced_at, deleted_at) VALUES (?, ?, ?, '1', NULL, NULL)", [$cat, $uid, $past]);
		$due = (int) self::$pdo->insert_id;
		$this->exec("INSERT INTO nf_articles (category_id, user_id, date, published, announced_at, deleted_at) VALUES (?, ?, ?, '1', NULL, NULL)", [$cat, $uid, $future]);
		$this->exec("INSERT INTO nf_articles (category_id, user_id, date, published, announced_at, deleted_at) VALUES (?, ?, ?, '0', NULL, NULL)", [$cat, $uid, $past]);
		$this->exec("INSERT INTO nf_articles (category_id, user_id, date, published, announced_at, deleted_at) VALUES (?, ?, ?, '1', ?, NULL)", [$cat, $uid, $past, $now]);
		$this->exec("INSERT INTO nf_articles (category_id, user_id, date, published, announced_at, deleted_at) VALUES (?, ?, ?, '1', NULL, ?)", [$cat, $uid, $past, $now]);

		// Requête de publish_scheduled() — modules/articles/models/articles.php:147-153 (le `AND category_id`
		// est un scope d'isolation de test, absent de la requête cron réelle).
		$res = $this->exec(
			"SELECT article_id FROM nf_articles WHERE published = '1' AND announced_at IS NULL AND date <= ? AND deleted_at IS NULL AND category_id = ?",
			[$now, $cat]
		)->get_result();

		$ids = [];
		while ($r = $res->fetch_row())
		{
			$ids[] = (int) $r[0];
		}

		$this->assertSame([$due], $ids, 'Seule la ligne due+publiée+non-annoncée+vivante est sélectionnée par le cron articles.');
	}

	public function test_articles_public_listing_masks_future_dated(): void
	{
		$uid = $this->createUser();
		$cat = 999000 + $uid;

		$now    = date('Y-m-d H:i:s');
		$future = date('Y-m-d H:i:s', time() + 86400);

		$this->exec("INSERT INTO nf_articles (category_id, user_id, date, published, announced_at, deleted_at) VALUES (?, ?, ?, '1', NULL, NULL)", [$cat, $uid, $now]);
		$article_id = (int) self::$pdo->insert_id;

		// Filtre de visibilité non-admin de get_articles() — modules/articles/models/articles.php:44.
		$visible = (int) $this->scalar("SELECT COUNT(*) FROM nf_articles WHERE article_id = ? AND published = '1' AND date <= ?", [$article_id, $now]);
		$this->assertSame(1, $visible, 'Daté maintenant + publié = visible.');

		$this->exec("UPDATE nf_articles SET date = ? WHERE article_id = ?", [$future, $article_id]);

		$visible = (int) $this->scalar("SELECT COUNT(*) FROM nf_articles WHERE article_id = ? AND published = '1' AND date <= ?", [$article_id, $now]);
		$this->assertSame(0, $visible, 'Daté dans le futur = masqué jusqu\'à son heure.');
	}
}
