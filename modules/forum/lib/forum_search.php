<?php
declare(strict_types=1);

namespace NF\Modules\Forum\Lib;

/**
 * Logique PURE de la recherche full-text du forum (aucune dépendance au service locator).
 * Extraite du modèle (modules/forum/models/forum.php::_to_boolean_query/_quote) pour être
 * unit-testable directement : le modèle garde ses requêtes DB et délègue ici la construction
 * de la requête booléenne MySQL et l'échappement.
 */
final class Forum_Search
{
	/**
	 * Convertit une saisie utilisateur en requête MATCH ... AGAINST (...) IN BOOLEAN MODE.
	 * "foo bar" → "+foo* +bar*" (AND + stemming) ; garde les "phrases entre guillemets" ;
	 * "-mot" → exclusion (nettoyée) ; un token < 3 caractères est ignoré.
	 */
	public static function to_boolean_query(string $query): string
	{
		$tokens = preg_split('/\s+/', $query);
		$out    = [];

		foreach ($tokens as $tok)
		{
			$tok = trim($tok);
			if ($tok === '')
			{
				continue;
			}

			if ($tok[0] === '"' && substr($tok, -1) === '"')
			{
				// Le mot entre guillemets, nettoyé comme les autres : il partait tel quel dans la requête SQL, et un
				// visiteur en sortait (antislash + apostrophe), puis lisait la base (audit du 2026-10-09).
				if (($phrase = preg_replace('/[^\p{L}\p{N}_]/u', '', substr($tok, 1, -1))) !== '')
				{
					$out[] = '"'.$phrase.'"';
				}
			}
			else if ($tok[0] === '-' && strlen($tok) > 1)
			{
				$out[] = '-'.preg_replace('/[^\p{L}\p{N}_]/u', '', substr($tok, 1));
			}
			else
			{
				$clean = preg_replace('/[^\p{L}\p{N}_]/u', '', $tok);
				if (strlen($clean) >= 3)
				{
					$out[] = '+'.$clean.'*';
				}
			}
		}

		return implode(' ', $out);
	}

	/**
	 * Échappe une valeur pour une chaîne SQL, sans connexion : l'antislash doublé, puis l'apostrophe. Le modèle emploie
	 * l'échappement de la base elle-même (`Db::escape_string()`), qui connaît son jeu de caractères et son sql_mode ;
	 * celle-ci reste pour la logique pure et ses tests. Elle ne doublait que l'apostrophe : « \' » refermait la chaîne.
	 */
	public static function quote(string $s): string
	{
		return "'".str_replace(['\\', "'"], ['\\\\', "''"], $s)."'";
	}
}
