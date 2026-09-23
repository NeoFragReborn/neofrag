<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Corbeille — vue centralisée du contenu soft-deleted (deleted_at), avec restauration
 * et purge (suppression définitive), par type/module. Chaque module declare ses types via trash_types().
 */

namespace NF\Modules\Trash;

use NF\NeoFrag\Addons\Module;

class Trash extends Module
{
	/**
	 * Types restaurables — COLLECTES AUPRES DES MODULES (inversion du 2026-09-15).
	 *
	 * Avant, ce module tenait en dur la table, la cle primaire et les methodes de restauration de
	 * news/articles/gallery/comments/forum. Un module du coeur connaissait donc nommement des modules
	 * optionnels : impossible d'alleger le paquet sans que la corbeille ne les reclame.
	 *
	 * Desormais chaque module declare `trash_types()` et la corbeille ne connait plus personne. Un
	 * module absent ne declare rien, il disparait simplement de la liste. Meme idiome que `groups()`
	 * dans neofrag/core/groups.php.
	 */
	public function types()
	{
		static $types = NULL;

		if ($types !== NULL)
		{
			return $types;
		}

		$types = [];

		// NeoFrag()->model2() et non $this->model2() : dans un module, le raccourci $this->model2()
		// se resout sur le module lui-meme (il chercherait nf_trash_addon). Idiome des modules :
		// cf. admin/controllers/admin.php, access/controllers/admin_checker.php, live_editor.
		foreach (NeoFrag()->model2('addon')->get('module') as $module)
		{
			if (!method_exists($module, 'trash_types'))
			{
				continue;
			}

			foreach ($module->trash_types() as $key => $cfg)
			{
				// Le module declarant est toujours le proprietaire : plus de nom a recopier.
				$cfg['module'] = $module->info()->name;
				$types[$key]   = $cfg;
			}
		}

		return $types;
	}

	protected function __info()
	{
		return [
			'title'       => $this->lang('Corbeille'),
			'description' => $this->lang('Restaurer ou purger définitivement le contenu supprimé.'),
			'icon'        => 'fas fa-trash-restore',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '1.0.0'],
			'routes'      => [
				'admin{pages}' => 'index',
			],
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => $this->lang('Corbeille'),
						'icon'   => 'fas fa-trash-restore',
						'access' => [
							'restore' => ['title' => $this->lang('Restaurer'), 'icon' => 'fas fa-trash-restore', 'admin' => TRUE],
							'purge'   => ['title' => $this->lang('Purger'),     'icon' => 'fas fa-times',          'admin' => TRUE],
						],
					],
				],
			],
		];
	}
}
