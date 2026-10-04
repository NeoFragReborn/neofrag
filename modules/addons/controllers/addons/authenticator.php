<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Addons\Controllers\Addons;

use NF\NeoFrag\Loadables\Controller;

class Authenticator extends Controller
{
	/**
	 * Libellés du type (pluriel, singulier), icône et couleur : affichés sur les cartes de la page des
	 * addons. Posés au constructeur, parce qu'une valeur par défaut de propriété ne peut pas appeler
	 * lang() — écrits en dur, ils restaient en français sur un site dans une autre langue.
	 */
	public $__label;

	public function __construct($caller)
	{
		parent::__construct($caller);

		$this->__label = [$this->lang('Authentificateurs'), $this->lang('Authentificateur'), 'fas fa-lock', 'info'];
	}

	public function __actions()
	{
		return $this->array
					->set('enable', [$this->lang('Activer'), 'fas fa-check', 'success', TRUE, function($addon){
						return !$addon->is_enabled();
					}])
					->set('disable', [$this->lang('Désactiver'), 'fas fa-times', 'muted', TRUE, function($addon){
						return $addon->is_enabled();
					}])
					->set('order', [$this->lang('Ordre'), 'fas fa-sort', 'info', TRUE])
					->set('settings', [$this->lang('Configuration'), 'fas fa-wrench', 'warning', TRUE]);
	}

	public function enable($addon)
	{
		$addon->__addon->set('data', $addon->__addon->data->set('enabled', TRUE))->update();

		notify($this->lang('<b>%s</b> activé', $addon->info()->title));

		refresh();
	}

	public function disable($addon)
	{
		$addon->__addon->set('data', $addon->__addon->data->set('enabled', FALSE))->update();

		notify($this->lang('<b>%s</b> désactivé', $addon->info()->title));

		refresh();
	}

	public function order()
	{
		$authenticators = [];

		foreach (NeoFrag()->collection('addon')->where('type_id', NeoFrag()->collection('addon_type')->where('name', 'authenticator')->row()->id)->get() as $authenticator)
		{
			if ($authenticator->data->get('enabled'))
			{
				$authenticators[$authenticator->id] = $authenticator;
			}
		}

		uasort($authenticators, function($a, $b){
			// L'ordre d'un authentificateur est un entier ; `strnatcmp()` attend des chaines.
			return strnatcmp((string) $a->data->get('order'), (string) $b->data->get('order'));
		});

		$authenticators = $this->array($authenticators);

		if (($post = post_check('id', 'position')) && (list($addon_id, $position) = array_values($post)))
		{
			foreach ($authenticators->move($addon_id, $position)->values() as $order => $addon)
			{
				$addon	->set('data', $addon->data->set('order', $order))
						->update();
			}

			return $this->output->json(['success' => 'refresh']);
		}

		return $this->modal('Authentificateurs', 'fas fa-sort')
					->body($this->table2($authenticators)
								->compact(function($a){
									return $this->button_sort($a->id, 'admin/addons/order/'.$a->url());
								})
								->col(function($a){
									return $this->label($a->addon()->info()->title, $a->addon()->info()->icon);
								})
					)
					->close();
	}

	public function settings($auth)
	{
		return $this->form2()
					->info('<div class="alert alert-primary">
								<h5 class="alert-heading">'.$this->label('Informations', 'fas fa-info-circle').'</h5>
								<dl>
									<dt>'.$this->lang('Enregistrez votre site via').'</dt>
										<dd><a href="'.$auth->info()->help.'" target="_blank">'.$auth->info()->help.'</a></dd>
									'.$this	->array($auth->_params())
											->each(function($a, $key){
												return '<dt>'.$key.'</dt><dd>'.$a.'</dd>';
											}).'
								</dl>
							</div>')
					->exec(function($form) use ($auth){
						foreach (['dev' => $this->lang('Développement'), 'prod' => $this->lang('Production')] as $type => $legend)
						{
							$form	->legend($legend)
									->exec(function($form) use ($type, $auth){
										foreach ($auth->_keys as $name)
										{
											// Un authentificateur jamais configuré n'a ni clés de développement ni
											// de production : l'écran les lisait sur un objet vide (huit alertes au
											// journal par ouverture — trouvé par check-reglages, 2026-10-04).
											$form->rule($this	->form_text($type.'_'.$name)
																->title($name)
																->value($auth->settings()->$type->$name ?? ''));
										}
									});
						}
					})
					->success(function($data) use ($auth){
						foreach (['dev', 'prod'] as $type)
						{
							foreach ($auth->_keys as $key)
							{
								$auth->__addon->data->set($type, $key, $data[$type.'_'.$key]);
							}
						}

						$auth->__addon->set('data', $auth->__addon->data)->update();

						notify($this->lang('Configuration de <b>%s</b> modifiée', $auth->info()->title));

						refresh();
					})
					->modal($auth->info()->title, 'fas fa-wrench')
					->cancel();
	}
}
