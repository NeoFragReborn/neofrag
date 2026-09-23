<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

use NF\NeoFrag\Addons\Module;

/**
 * Descripteurs de contenu — la collecte auprès des modules (inversion du 2026-09-15).
 *
 * La même connaissance (« quel type de contenu vit dans quelle table, sous quelle clé ») était
 * recopiée SIX fois dans des modules du cœur : Notifications (OWNER_MAP, SUB_TYPES, content_url),
 * Gamification (OWNER_MAP, REACTION_SOURCES, CONTENT_SOURCES), Reactions::ALLOWED_TYPES et
 * Revisions::ALLOWED_TYPES. Le cœur nommait donc en dur les tables de news, articles et forum, ce
 * qui interdisait d'alléger le paquet.
 *
 * Ce test épingle le contrat : les modules déclarent, le cœur collecte, et aucun consommateur ne
 * nomme plus personne.
 */
final class ContentTypesTest extends HeadlessTestCase
{
	/** Les six consommateurs ne doivent plus contenir la moindre table d'un autre module. */
	public function test_aucun_consommateur_ne_nomme_de_table_etrangere(): void
	{
		$etrangeres = ['nf_news', 'nf_articles', 'nf_forum_messages', 'nf_forum_topics', 'nf_comment'];

		foreach (['notifications', 'gamification', 'reactions', 'revisions'] as $module)
		{
			$source = file_get_contents(NEOFRAG_CMS."/modules/$module/$module.php");

			foreach ($etrangeres as $table)
			{
				$this->assertStringNotContainsString(
					"'".$table."'", $source,
					"Le module « $module » ne doit plus nommer la table $table : elle appartient à un autre module."
				);
			}
		}
	}

	public function test_les_types_attendus_sont_declares(): void
	{
		$types = Module::content_types();

		foreach (['news' => 'news', 'articles' => 'articles', 'article' => 'articles',
		          'forum-message' => 'forum', 'comment' => 'comments'] as $type => $module)
		{
			$this->assertArrayHasKey($type, $types, "Le type « $type » doit être déclaré.");
			$this->assertSame($module, $types[$type]['module'], "« $type » doit être déclaré par $module.");
			$this->assertNotSame('', (string) $types[$type]['table'], "« $type » doit dire dans quelle table il vit.");
			$this->assertNotSame('', (string) $types[$type]['pk'],    "« $type » doit dire quelle est sa clé.");
		}
	}

	/** Un alias désigne le MÊME contenu : mêmes table et clé, sinon le karma compterait deux fois. */
	public function test_un_alias_partage_le_descripteur(): void
	{
		$types = Module::content_types();

		$this->assertSame($types['articles']['table'], $types['article']['table']);
		$this->assertSame($types['articles']['pk'],    $types['article']['pk']);
	}

	public function test_les_drapeaux_pilotent_bien_les_consommateurs(): void
	{
		$types = Module::content_types();

		// Réactions : exactement les types déclarés reactable.
		foreach (['comment', 'forum-message', 'article', 'news'] as $t)
		{
			$this->assertTrue(!empty($types[$t]['reactable']), "« $t » doit rester reactable.");
			$this->assertTrue(\NF\Modules\Reactions\Reactions::is_allowed($t), "Reactions doit accepter « $t ».");
		}

		// Révisions : news et articles seulement.
		$this->assertTrue(\NF\Modules\Revisions\Revisions::is_allowed('news'));
		$this->assertTrue(\NF\Modules\Revisions\Revisions::is_allowed('article'));
		$this->assertFalse(\NF\Modules\Revisions\Revisions::is_allowed('comment'),
			'Un commentaire ne porte pas d\'historique de révisions.');

		// Abonnements : les quatre cibles historiques de SUB_TYPES, et rien de plus.
		foreach (['news', 'article', 'news-category', 'article-category'] as $t)
		{
			$this->assertTrue(\NF\Modules\Notifications\Notifications::is_subscribable($t),
				"« $t » doit rester une cible d'abonnement.");
		}
		$this->assertFalse(\NF\Modules\Notifications\Notifications::is_subscribable('forum-message'));
		$this->assertFalse(\NF\Modules\Notifications\Notifications::is_subscribable('n-importe-quoi'));
	}
}
