<?php
declare(strict_types=1);

/**
 * https://neofr.ag
 *
 * Encode/décode l'arbre de disposition du live editor (Array_ > Row > Col > Widget) en JSON,
 * en remplacement de serialize()/unserialize(). Objectif sécurité : supprimer la surface
 * d'object-injection (zone.php désérialisait des OBJETS via une allowlist de 5 classes) et
 * obtenir un stockage lisible/diffable/portable entre versions PHP.
 *
 * `decode()` est RÉTROCOMPATIBLE : il lit le nouveau format JSON OU une disposition encore
 * PHP-serialized (legacy, allowlist bornée) → la migration des lignes existantes peut donc se
 * faire indépendamment, sans fenêtre de casse.
 *
 * État capturé (= exactement ce que __sleep persistait) : Row->_style, Col->_size,
 * Widget->{_widget,_style,_size}. Vérifié lossless sur les dispositions réelles.
 */

namespace NF\NeoFrag\Libraries;

use NF\NeoFrag\Library;
use NF\NeoFrag\Displayables\Row;
use NF\NeoFrag\Displayables\Col;
use NF\NeoFrag\Displayables\Widget;

class Disposition extends Library
{
	/** Classes autorisées pour relire une disposition encore PHP-serialized (legacy, transitoire). */
	const LEGACY_CLASSES = [
		Array_::class,
		\NF\NeoFrag\Displayables\Zone::class,
		Row::class,
		Col::class,
		Widget::class,
	];

	/** Arbre de disposition → JSON. */
	public function encode($disposition): string
	{
		return (string) json_encode($this->to_array($disposition), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	/** JSON (nouveau) OU PHP-serialized (legacy) → arbre Array_ > Row > Col > Widget. */
	public function decode($value)
	{
		if (!is_string($value) || ($value = trim($value)) === '')
		{
			return $this->array();
		}

		// Nouveau format JSON.
		if ($value[0] === '[' || $value[0] === '{')
		{
			$rows = json_decode($value, TRUE);

			return is_array($rows) ? $this->from_array($rows) : $this->array();
		}

		// Legacy PHP-serialized (allowlist bornée — disparaît après migration des lignes).
		$tree = @unserialize($value, ['allowed_classes' => self::LEGACY_CLASSES]);

		return $tree instanceof Array_ ? $tree : $this->array();
	}

	/** Arbre → tableau plat (JSON-able). */
	public function to_array($disposition): array
	{
		$rows = [];

		if (!is_iterable($disposition))
		{
			return $rows;
		}

		foreach ($disposition as $row)
		{
			if (!($row instanceof Row))
			{
				continue;
			}

			$cols = [];

			foreach ($row as $col)
			{
				if (!($col instanceof Col))
				{
					continue;
				}

				$widgets = [];

				foreach ($col as $widget)
				{
					if ($widget instanceof Widget)
					{
						$widgets[] = [
							'id'    => (int) $widget->widget_id(),
							'style' => $widget->style() ?: NULL,
							'size'  => $widget->size() ?: NULL,
						];
					}
				}

				$cols[] = [
					'size'    => $col->size() ?: NULL,
					'widgets' => $widgets,
				];
			}

			$rows[] = [
				'style' => $row->style() ?: NULL,
				'cols'  => $cols,
			];
		}

		return $rows;
	}

	/** Tableau plat → arbre Array_ > Row > Col > Widget (mêmes factories que le live editor). */
	public function from_array($rows)
	{
		$disposition = $this->array();

		foreach (is_array($rows) ? $rows : [] as $row_data)
		{
			$row = $this->row();

			if (!empty($row_data['style']))
			{
				$row->style($row_data['style']);
			}

			foreach (!empty($row_data['cols']) && is_array($row_data['cols']) ? $row_data['cols'] : [] as $col_data)
			{
				$col = $this->col();

				if (!empty($col_data['size']))
				{
					$col->size($col_data['size']);
				}

				foreach (!empty($col_data['widgets']) && is_array($col_data['widgets']) ? $col_data['widgets'] : [] as $widget_data)
				{
					if (!empty($widget_data['id']))
					{
						$widget = $this->widget((int) $widget_data['id']);

						if (!empty($widget_data['style']))
						{
							$widget->style($widget_data['style']);
						}

						if (!empty($widget_data['size']))
						{
							$widget->size($widget_data['size']);
						}

						$col->append($widget);
					}
				}

				$row->append($col);
			}

			$disposition->append($row);
		}

		return $disposition;
	}
}
