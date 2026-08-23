<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Loadables\Controllers;

use NF\NeoFrag\Loadables\Controllers;

abstract class Module_Checker extends Module
{
	private $_extension_allowed;

	public function __construct($caller)
	{
		parent::__construct($caller);

		$this->_extension_allowed = !$this->url->extension;
	}

	public function extension($extension)
	{
		if ($this->url->extension != $extension)
		{
			$this->error();
		}
		else
		{
			$this->_extension_allowed = TRUE;
		}

		return $this;
	}

	public function valid()
	{
		return $this->_extension_allowed;
	}

	/**
	 * Trie une collection (array d'items) selon ?sort / ?order, validés contre une allowlist
	 * (anti-injection : seules les clés de $cols sont acceptées). À appeler dans un checker de liste
	 * AVANT la pagination (sinon le tri ne porte que sur la page courante).
	 *
	 * @param array  $items       Collection à trier (array d'arrays).
	 * @param array  $cols        ['cle_ui' => 'champ'] ou ['cle_ui' => callable($item):mixed].
	 * @param string $default_key Clé de tri par défaut.
	 * @param string $default_dir 'asc' | 'desc'.
	 * @return array [items triés, ['key' => clé appliquée, 'dir' => 'asc'|'desc']]
	 */
	protected function sort_items(array $items, array $cols, $default_key, $default_dir = 'asc')
	{
		$key = (isset($_GET['sort']) && isset($cols[(string)$_GET['sort']])) ? (string)$_GET['sort'] : $default_key;
		$dir = strtolower((string)($_GET['order'] ?? $default_dir)) === 'desc' ? 'desc' : 'asc';

		if (isset($cols[$key]))
		{
			$field   = $cols[$key];
			// Un extracteur EST une Closure : NE PAS utiliser is_callable() sur une string de champ,
			// car un nom comme 'date' est aussi une fonction PHP native (is_callable('date') === TRUE)
			// → on appellerait date($item) au lieu de lire $item['date'].
			$closure = $field instanceof \Closure;
			$sign    = $dir === 'desc' ? -1 : 1;

			usort($items, function($a, $b) use ($field, $closure, $sign)
			{
				$va = $closure ? $field($a) : ($a[$field] ?? NULL);
				$vb = $closure ? $field($b) : ($b[$field] ?? NULL);
				$cmp = (is_numeric($va) && is_numeric($vb)) ? ($va <=> $vb) : strcasecmp((string)$va, (string)$vb);
				return $sign * $cmp;
			});
		}

		return [$items, ['key' => $key, 'dir' => $dir]];
	}
}
