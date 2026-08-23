<?php
declare(strict_types=1);

namespace NF\Modules\Forum\Lib;

/**
 * Règles PURES de validation des pièces jointes forum (aucune dépendance au service locator).
 * Le modèle lit la config et le contrôleur applique la décision ; la logique (défauts, parsing
 * CSV, allow-list MIME, borne de taille) vit ici, directement unit-testable.
 */
final class Forum_Attachment_Rules
{
	public const DEFAULT_MIMES   = 'image/jpeg,image/png,image/gif,image/webp,application/pdf,text/plain,application/zip';
	public const DEFAULT_SIZE_KB = 5120; // 5 Mo

	/**
	 * Parse la liste blanche de MIME depuis la valeur de config (CSV), défaut si absente/vide.
	 * @return string[]
	 */
	public static function parse_mimes(?string $configured): array
	{
		$value = ($configured !== null && $configured !== '') ? $configured : self::DEFAULT_MIMES;

		return array_filter(array_map('trim', explode(',', $value)));
	}

	/** Taille max en octets depuis les Ko de config (défaut 5120 Ko si absent ; 0 reste 0). */
	public static function max_bytes(?int $configured_kb): int
	{
		return ($configured_kb ?? self::DEFAULT_SIZE_KB) * 1024;
	}

	/** @param string[] $allowed */
	public static function is_allowed_mime(string $mime, array $allowed): bool
	{
		return in_array($mime, $allowed, TRUE);
	}

	public static function is_within_size(int $size, int $max): bool
	{
		return $size <= $max;
	}
}
