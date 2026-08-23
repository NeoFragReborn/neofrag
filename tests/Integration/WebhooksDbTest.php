<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Tests d'intégration du module Webhooks : sélection des abonnements à notifier
 * (filtre SQL `enabled = 1` + appartenance à la liste CSV d'événements faite en PHP)
 * et signature HMAC du corps émis. On mime VERBATIM le SQL et la logique de
 * trigger()/_dispatch() (modules/webhooks/webhooks.php) — un test qui exécute une
 * requête que le modèle n'utilise pas réellement ne vaut rien.
 *
 * DDL : install/schema.sql:562-572 — nf_webhooks(id, title, url, secret, events TEXT,
 * enabled tinyint(1), created_at). La colonne de gating est `enabled` (pas `active`).
 * `events` est une CSV dans une colonne TEXT (pas de table de jointure, pas de JSON).
 * Webhooks globaux : aucune colonne user_id/FK.
 */
final class WebhooksDbTest extends IntegrationTestCase
{
	/* ------------------------------------------------- Sélection des abonnés */

	public function test_webhook_subscription_selects_only_enabled(): void
	{
		// Trois webhooks : actif + événement visé, actif + autre événement, désactivé.
		$this->exec("INSERT INTO nf_webhooks (title, url, secret, events, enabled) VALUES ('on-match', 'https://a.example/h', 's1', 'news.published', 1)");
		$on_match = (int) self::$pdo->insert_id;
		$this->exec("INSERT INTO nf_webhooks (title, url, secret, events, enabled) VALUES ('on-other', 'https://b.example/h', 's2', 'comment.created', 1)");
		$on_other = (int) self::$pdo->insert_id;
		$this->exec("INSERT INTO nf_webhooks (title, url, secret, events, enabled) VALUES ('off', 'https://c.example/h', 's3', 'news.published', 0)");
		$off = (int) self::$pdo->insert_id;

		// Requête exacte de trigger() (webhooks.php:67) :
		//   select('url','secret','events')->from('nf_webhooks')->where('enabled', 1)->get()
		$res = $this->exec(
			"SELECT url, secret, events FROM nf_webhooks WHERE enabled = 1 AND id IN (?, ?, ?)",
			[$on_match, $on_other, $off]
		)->get_result();

		$urls = [];
		while ($row = $res->fetch_assoc())
		{
			$urls[] = $row['url'];
		}
		sort($urls);

		$this->assertSame(['https://a.example/h', 'https://b.example/h'], $urls, 'Seuls les webhooks enabled=1 sont candidats ; le désactivé est exclu.');
	}

	/* -------------------------------------------- Filtre CSV des événements */

	public function test_webhook_event_csv_membership_filter(): void
	{
		// CSV avec espaces (test du trim), une liste d'autres événements, et le wildcard.
		$this->exec("INSERT INTO nf_webhooks (title, url, secret, events, enabled) VALUES ('match', 'https://m.example/h', '', 'news.published, comment.created', 1)");
		$id_match = (int) self::$pdo->insert_id;
		$this->exec("INSERT INTO nf_webhooks (title, url, secret, events, enabled) VALUES ('miss', 'https://x.example/h', '', 'forum.topic, user.registered', 1)");
		$id_miss = (int) self::$pdo->insert_id;
		$this->exec("INSERT INTO nf_webhooks (title, url, secret, events, enabled) VALUES ('wild', 'https://w.example/h', '', '*', 1)");
		$id_wild = (int) self::$pdo->insert_id;

		// On récupère les vraies CSV depuis la base, puis on reproduit la logique
		// PHP de trigger() (webhooks.php:87-89) sur la valeur réelle.
		$rows = [];
		$res = $this->exec("SELECT id, events FROM nf_webhooks WHERE enabled = 1 AND id IN (?, ?, ?)", [$id_match, $id_miss, $id_wild])->get_result();
		while ($row = $res->fetch_assoc())
		{
			$rows[(int) $row['id']] = (string) $row['events'];
		}

		$event = 'news.published';

		$matches = static function (string $csv) use ($event): bool {
			// Logique exacte de trigger() (webhooks.php:87-89).
			$events = array_filter(array_map('trim', explode(',', $csv)));

			return in_array('*', $events, TRUE) || in_array($event, $events, TRUE);
		};

		$this->assertTrue($matches($rows[$id_match]), 'CSV contenant l\'événement (après trim) → match.');
		$this->assertFalse($matches($rows[$id_miss]), 'CSV listant uniquement d\'autres événements → pas de match.');
		$this->assertTrue($matches($rows[$id_wild]), 'CSV wildcard "*" → match de n\'importe quel événement.');
	}

	/* ------------------------------------------------- Signature HMAC (pur PHP) */

	public function test_webhook_hmac_signature_sensitivity(): void
	{
		// Corps canonique exact de trigger() (webhooks.php:79-83).
		$event   = 'news.published';
		$payload = ['id' => 42, 'title' => 'Bonjour /monde & café'];
		$ts      = 1700000000;
		$secret  = 'super-secret';

		$body = json_encode([
			'event'     => $event,
			'data'      => $payload,
			'timestamp' => $ts
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		// En-tête exact de _dispatch() (webhooks.php:120).
		$sig = 'sha256='.hash_hmac('sha256', $body, $secret);

		// On teste la SENSIBILITÉ de la signature au secret et au corps (un mirror de hash_hmac ne peut pas
		// tester son propre déterminisme — c'est une garantie du langage, pas un invariant du module).

		// (1) Changer le secret change la signature.
		$this->assertNotSame($sig, 'sha256='.hash_hmac('sha256', $body, 'autre-secret'), 'Un secret différent produit une signature différente.');

		// (2) Changer le corps change la signature.
		$other_body = json_encode([
			'event'     => $event,
			'data'      => ['id' => 43],
			'timestamp' => $ts
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$this->assertNotSame($sig, 'sha256='.hash_hmac('sha256', $other_body, $secret), 'Un corps différent produit une signature différente.');
	}
}
