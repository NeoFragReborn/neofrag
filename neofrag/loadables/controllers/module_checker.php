<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Loadables\Controllers;

use NF\NeoFrag\Loadables\Controllers;

abstract class Module_Checker extends Module
{
	private $_extension_allowed;

	/** Le checker a-t-il déclaré l'extension qu'il sert (`extension('xml')`…) ? */
	private bool $_extension_declaree = FALSE;

	public function __construct($caller)
	{
		parent::__construct($caller);

		$this->_extension_allowed = !$this->url->extension;
	}

	public function extension($extension)
	{
		$this->_extension_declaree = TRUE;

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
	 * Ce checker refuse-t-il pour une raison ORDINAIRE ?
	 *
	 * Un refus de checker part aux journaux avec son motif : c'est ce qui rend diagnosticable une
	 * requête rejetée, qui sinon ne serait qu'un `404` nu. Mais certains « non » ne sont pas des
	 * anomalies — celui d'un routeur de repli, dont tout le rôle est de dire « aucune page à cette
	 * adresse ». Chaque visiteur qui se trompe et chaque robot qui sonde écrivaient alors une ligne
	 * d'erreur dans le journal de production.
	 *
	 * Un checker qui rend TRUE ici garde son diagnostic à l'écran en mode débogage, et ne pollue
	 * plus le journal. À ne rendre TRUE que si le refus signifie **cette adresse n'existe pas** —
	 * jamais pour masquer un rejet qu'on devrait expliquer.
	 */
	public function refus_ordinaire(): bool
	{
		return FALSE;
	}

	/**
	 * L'adresse porte une extension alors que cette page n'en sert AUCUNE : c'est une adresse qui
	 * n'existe pas, un 404 ordinaire. Ce sont les robots qui sondent (`newsletter/.env`), ou qui tapent
	 * `/index.php` : le journal du site officiel en portait une ligne d'anomalie à chaque passage
	 * (relevé le 2026-10-04). Une page qui sert une extension (`sitemap.xml`) et en reçoit une autre
	 * reste, elle, une anomalie journalisée : c'est peut-être un lien faux du produit.
	 */
	public function extension_jamais_servie(): bool
	{
		return !$this->_extension_declaree && (bool) $this->url->extension;
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
