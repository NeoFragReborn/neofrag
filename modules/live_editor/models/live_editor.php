<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Live_Editor\Models;

use NF\NeoFrag\Loadables\Model;

class Live_Editor extends Model
{
	public function get_disposition($disposition_id, &$theme, &$page, &$zone)
	{
		$disposition = $this->db	->select('disposition', 'theme', 'page', 'zone')
									->from('nf_dispositions')
									->where('disposition_id', $disposition_id)
									->row();

		$theme = $disposition['theme'];
		$page  = $disposition['page'];
		$zone  = $disposition['zone'];

		return $this->disposition->decode($disposition['disposition']);
	}

	public function set_disposition($disposition_id, $disposition)
	{
		$this->db	->where('disposition_id', $disposition_id)
					->update('nf_dispositions', [
						'disposition' => $this->disposition->encode($disposition)
					]);
	}

	public function delete_widgets($disposition)
	{
		$widgets = [];

		$disposition->each($f = function($a) use (&$f, &$widgets){
			if (is_a($a, 'NF\NeoFrag\Displayables\Widget'))
			{
				$widgets[] = $a->widget_id();
			}
			else if ($a)
			{
				$a->each($f);
			}
		});

		if ($widgets)
		{
			$this->db	->where('widget_id', $widgets)
						->delete('nf_widgets');
		}
	}

	public function check_widget($widget_id)
	{
		// Renvoie la ligne nf_widgets telle quelle (settings = JSON brut stocké). On ne re-sérialise
		// plus le champ : c'était un reliquat de l'ancien format dont la sortie n'était jamais lue
		// (widget_settings l'ignore ; widget_update le réencode via get_settings) → code mort retiré,
		// plus aucun serialize() runtime des settings widget.
		return $this->db->from('nf_widgets')->where('widget_id', $widget_id)->row() ?: FALSE;
	}

	/**
	 * Widgets installés : titres, types, et icônes.
	 *
	 * $icones est le troisième paramètre plutôt qu'une refonte de $widgets : les deux checkers
	 * n'utilisent que les CLÉS de $widgets (`isset($widgets[$nom])`) et la vue n'en lit que le
	 * titre — en changer la forme les casserait sans rien apporter.
	 *
	 * L'icône vient de la déclaration de l'addon, avec repli sur celle du module générique : un
	 * widget sans icône doit rendre un pictogramme neutre, jamais une case vide. Les noms déclarés
	 * sont vérifiés contre le FontAwesome embarqué par tools/check-addon-declarations.php (règle 7).
	 */
	public function get_widgets(&$widgets, &$types, &$icones = NULL)
	{
		$icones = is_array($icones) ? $icones : [];

		foreach (NeoFrag()->model2('addon')->get('widget') as $widget)
		{
			$info = $widget->info();

			$widgets[$name = $info->name] = $info->title;
			$icones[$name]                = !empty($info->icon) ? $info->icon : 'fas fa-puzzle-piece';

			if (!empty($info->types))
			{
				$types[$name] = $info->types;
				array_natsort($types[$name]);
			}
		}

		array_natsort($widgets);
	}
}
