<?php
declare(strict_types=1);

namespace NF\Modules\Wiki\Lib;

/**
 * Les ancres des titres d'une page du wiki — PURES, sans le service locator : le module les pose à
 * l'affichage, et tools/lib/wiki.php s'en sert pour que les renvois des guides (`admin.md#…`) mènent
 * au titre visé une fois dans le wiki.
 *
 * Avant m19, seuls les titres de niveau 2 portaient une ancre, et les guides écrivent les leurs comme
 * GitHub (`tables--table2`) : `admin#le-référencement` ou `framework#tables--table2` ne menaient
 * nulle part dans le wiki, dans aucune langue.
 */
final class Wiki_Ancres
{
	/**
	 * L'ancre d'un titre : son texte en minuscules, des tirets à la place du reste. Une version du
	 * journal (« [1.2.14] — 2026-10-02 ») a l'ancre de son seul numéro, « 1-2-14 » : la fenêtre de mise
	 * à jour y mène sans connaître la date de publication.
	 */
	public static function ancre(string $titre): string
	{
		if (preg_match('/^\[(\d+(?:\.\d+)+)\]/', $titre, $version))
		{
			return str_replace('.', '-', $version[1]);
		}

		return trim((string) preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower($titre)), '-') ?: 'section';
	}

	/** Une ancre sur chaque titre de niveau 2 à 4 qui n'en a pas : les cartes de l'accueil et les renvois des guides y mènent. */
	public static function poser(string $html): string
	{
		return (string) preg_replace_callback('#<h([234])>(.*?)</h\1>#is', static fn (array $m): string => '<h'.$m[1].' id="'.nf_texte(self::ancre(self::texte($m[2]))).'">'.$m[2].'</h'.$m[1].'>', $html);
	}

	/**
	 * Les ancres que poser() mettra dans ce HTML.
	 *
	 * @return list<string>
	 */
	public static function ids(string $html): array
	{
		return preg_match_all('#<h([234])>(.*?)</h\1>#is', $html, $m) ? array_map(static fn (string $t): string => self::ancre(self::texte($t)), $m[2]) : [];
	}

	/** Le texte d'un titre HTML, tel que le lit le visiteur. */
	public static function texte(string $html): string
	{
		return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
	}
}
