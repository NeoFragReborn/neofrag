<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Webhooks\Lib;

/**
 * Le format de Discord, pour un webhook qui pointe vers un salon Discord.
 *
 * Le module envoyait à toutes les adresses le même corps générique, `{event, data, timestamp}`.
 * Discord le refuse : un webhook Discord exige `content` ou `embeds`, et répond « Cannot send an
 * empty message » à tout le reste. Le module annonçait pourtant Discord dans sa description
 * (relevé le 2026-09-23). Une adresse de webhook Discord reçoit désormais un message
 * lisible : un `embed` par événement, avec un titre, un lien ABSOLU, un texte et le nom du site.
 *
 * La classe ne dépend de rien d'autre que PHP : les textes lui arrivent déjà traduits, ce qui la
 * rend testable sans monter le site (tests/Unit/WebhooksDiscordTest.php).
 */
final class Discord
{
	/** Les limites de Discord : au-delà, le message entier est refusé. */
	public const TITRE_MAX       = 256;
	public const DESCRIPTION_MAX = 4096;
	public const NOM_MAX         = 80;

	/** Une couleur par événement, pour qu'on reconnaisse l'annonce d'un coup d'œil dans le salon. */
	public const COULEURS = [
		'news.published'    => 0x2DD4BF,
		'article.published' => 0x38BDF8,
		'user.registered'   => 0x22C55E,
		'comment.created'   => 0xA78BFA,
		'forum.topic'       => 0xF59E0B,
		'stream.live'       => 0x9146FF,
		'webhook.test'      => 0x94A3B8,
	];

	/**
	 * L'adresse est-elle un webhook Discord ? `discord.com` et ses variantes (`discordapp.com`, `ptb.`,
	 * `canary.`), en HTTPS, sous `/api/webhooks/`.
	 */
	public static function est_adresse(string $url): bool
	{
		$p = parse_url($url);

		return is_array($p)
			&& strtolower($p['scheme'] ?? '') === 'https'
			&& (bool) preg_match('/^(?:(?:ptb|canary)\.)?discord(?:app)?\.com$/i', (string) ($p['host'] ?? ''))
			&& str_starts_with((string) ($p['path'] ?? ''), '/api/webhooks/');
	}

	/**
	 * Le corps à envoyer à Discord.
	 *
	 * @param array{titre: string, description?: string, url?: string, image?: string} $message
	 *        des textes déjà traduits ; `url` peut être relative au site (`/fr/news/…`)
	 * @param string $site    le nom du site, signé en pied de message et comme nom d'expéditeur
	 * @param string $origine `https://site.tld`, pour rendre absolues les adresses relatives
	 * @return array<string, mixed>
	 */
	public static function corps(string $evenement, array $message, string $site, string $origine): array
	{
		$embed = array_filter([
			'title'       => self::couper($message['titre'], self::TITRE_MAX),
			'description' => self::couper((string) ($message['description'] ?? ''), self::DESCRIPTION_MAX),
			'url'         => self::absolue((string) ($message['url'] ?? ''), $origine),
			'color'       => self::COULEURS[$evenement] ?? self::COULEURS['webhook.test'],
			'footer'      => $site !== '' ? ['text' => self::couper($site, 2048)] : NULL,
			'timestamp'   => gmdate('Y-m-d\TH:i:s\Z'),
		], static fn ($valeur): bool => $valeur !== '' && $valeur !== NULL);

		if (($image = self::absolue((string) ($message['image'] ?? ''), $origine)) !== '')
		{
			$embed['image'] = ['url' => $image];
		}

		return array_filter([
			'username' => $site !== '' ? self::couper($site, self::NOM_MAX) : NULL,
			'embeds'   => [$embed],
		], static fn ($valeur): bool => $valeur !== NULL);
	}

	/** Une adresse relative au site devient absolue ; une adresse qui n'est ni l'un ni l'autre est écartée. */
	public static function absolue(string $url, string $origine): string
	{
		if ($url === '')
		{
			return '';
		}

		if (preg_match('#^https?://#i', $url))
		{
			return $url;
		}

		if (str_starts_with($url, '/') && preg_match('#^https?://[^/]+$#i', rtrim($origine, '/')))
		{
			return rtrim($origine, '/').$url;
		}

		return '';
	}

	private static function couper(string $texte, int $max): string
	{
		$texte = trim(html_entity_decode(strip_tags($texte), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

		return mb_strlen($texte) > $max ? rtrim(mb_substr($texte, 0, $max - 1)).'…' : $texte;
	}
}
