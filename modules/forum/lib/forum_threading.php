<?php
declare(strict_types=1);

namespace NF\Modules\Forum\Lib;

/**
 * Logique PURE du threading forum (aucune dépendance au service locator). Extraite du modèle
 * (modules/forum/models/forum.php) où le calcul de profondeur était dupliqué (get_messages +
 * get_messages_with_threading). Le modèle fetch les messages en DB puis délègue ici le calcul.
 */
final class Forum_Threading
{
	/**
	 * Affecte une profondeur (`depth`) à chaque message pour le rendu imbriqué.
	 * Les messages DOIVENT être ordonnés par message_id croissant (un parent précède sa réponse) :
	 * depth(msg) = depth(parent) + 1 si le parent a déjà été vu, sinon 0 (racine ou parent absent).
	 *
	 * @param array<int,array<string,mixed>> $messages chaque entrée a 'message_id' et 'parent_id'
	 * @return array<int,array<string,mixed>> les mêmes messages, enrichis de 'depth'
	 */
	public static function assign_depths(array $messages): array
	{
		$depths = [];

		foreach ($messages as &$m)
		{
			$pid        = (int) $m['parent_id'];
			$m['depth'] = ($pid && isset($depths[$pid])) ? $depths[$pid] + 1 : 0;

			$depths[(int) $m['message_id']] = $m['depth'];
		}
		unset($m);

		return $messages;
	}

	/**
	 * Un message à la profondeur $parent_depth peut-il recevoir une réponse ?
	 * Règle de _validate_parent() : refus si la profondeur atteint le maximum (>=) → autorisé ssi < max.
	 */
	public static function parent_depth_allows_reply(int $parent_depth, int $max_depth): bool
	{
		return $parent_depth < $max_depth;
	}
}
