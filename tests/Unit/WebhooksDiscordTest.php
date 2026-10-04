<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use NF\Modules\Webhooks\Lib\Discord;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../modules/webhooks/lib/discord.php';

/**
 * Le format de Discord pour le module Webhooks.
 *
 * Le module envoyait à toutes les adresses un corps générique que Discord refuse (« Cannot send an
 * empty message ») : il exige `content` ou `embeds`. Ces épreuves figent ce qu'un salon Discord
 * doit recevoir, et surtout ce qui ferait refuser le message entier — un champ vide, un titre trop
 * long, une adresse relative.
 */
final class WebhooksDiscordTest extends TestCase
{
	public function testLesAdressesDeDiscordSontReconnues(): void
	{
		foreach ([
			'https://discord.com/api/webhooks/123/abc',
			'https://discordapp.com/api/webhooks/123/abc',
			'https://ptb.discord.com/api/webhooks/123/abc',
			'https://canary.discord.com/api/webhooks/123/abc',
		] as $url)
		{
			$this->assertTrue(Discord::est_adresse($url), $url);
		}
	}

	public function testLesAutresAdressesGardentLeFormatGenerique(): void
	{
		foreach ([
			'https://hooks.zapier.com/hooks/catch/1/abc',
			'http://discord.com/api/webhooks/123/abc',        // pas en HTTPS
			'https://discord.com/channels/123/456',            // pas un webhook
			'https://discord.com.pirate.tld/api/webhooks/1/a', // hôte qui imite
			'https://evil-discord.com/api/webhooks/1/a',
		] as $url)
		{
			$this->assertFalse(Discord::est_adresse($url), $url);
		}
	}

	public function testUnMessageDeDiscordPorteUnEmbedComplet(): void
	{
		$corps = Discord::corps('news.published', ['titre' => 'Nouvelle actualité : Bienvenue', 'url' => '/fr/news/1/bienvenue'], 'Mon clan', 'https://clan.tld');

		$this->assertSame('Mon clan', $corps['username']);
		$this->assertCount(1, $corps['embeds']);

		$embed = $corps['embeds'][0];
		$this->assertSame('Nouvelle actualité : Bienvenue', $embed['title']);
		$this->assertSame('https://clan.tld/fr/news/1/bienvenue', $embed['url'], 'Discord exige une adresse absolue');
		$this->assertSame(Discord::COULEURS['news.published'], $embed['color']);
		$this->assertSame(['text' => 'Mon clan'], $embed['footer']);
		$this->assertArrayNotHasKey('description', $embed, 'un champ vide fait refuser le message');
	}

	public function testUneAdresseImpossibleARendreAbsolueEstEcartee(): void
	{
		$embed = Discord::corps('forum.topic', ['titre' => 'Sujet', 'url' => '/fr/forum/topic/1/x'], '', '')['embeds'][0];

		$this->assertArrayNotHasKey('url', $embed);
		$this->assertSame('', Discord::absolue('javascript:alert(1)', 'https://clan.tld'));
	}

	public function testLesTextesTropLongsSontCoupesEtLeHtmlRetire(): void
	{
		$embed = Discord::corps('comment.created', [
			'titre'       => str_repeat('é', 300),
			'description' => '<b>Gras</b> &amp; <i>italique</i>',
		], 'Clan', 'https://clan.tld')['embeds'][0];

		$this->assertSame(Discord::TITRE_MAX, mb_strlen($embed['title']));
		$this->assertStringEndsWith('…', $embed['title']);
		$this->assertSame('Gras & italique', $embed['description']);
	}

	public function testLeDirectMontreSaMiniature(): void
	{
		$embed = Discord::corps('stream.live', [
			'titre' => 'Alex est en direct',
			'url'   => 'https://www.twitch.tv/alex',
			'image' => 'https://static-cdn.jtvnw.net/previews/alex.jpg',
		], 'Clan', 'https://clan.tld')['embeds'][0];

		$this->assertSame(['url' => 'https://static-cdn.jtvnw.net/previews/alex.jpg'], $embed['image']);
		$this->assertSame(Discord::COULEURS['stream.live'], $embed['color']);
	}
}
