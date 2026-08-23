<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Comments;

use NF\NeoFrag\Addons\Module;

class Comments extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Commentaires'),
			'description' => 'Système de commentaires réutilisable par les modules (news, articles, etc.).',
			'icon'        => 'far fa-comments',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'admin'       => TRUE,
			'routes'      => [
				'admin/{pages}' => 'index'
			]
		];
	}

	public function __invoke($module, $module_id = 0)
	{
		if (is_a($module, 'NF\NeoFrag\Loadables\Model2'))
		{
			$module_id = $module->id;
			$module    = $module->__table;
		}

		if ($this->user())
		{
			// Loader explicite : $this->module() depuis une classe Module mis-résout le type d'addon
			// (forward_static_call → get_called_class = la classe appelante). On force le static = Module.
			$notifications = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['notifications']);
			$gamification  = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['gamification']);
			$webhooks      = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['webhooks']);

			$new = $this->view('new', [
				'form' => $this	->form2()
								->compact()
								->rule($this->form_textarea('comment')
											->rows(4)
											->required()
											->editor()
								)
								->success(function($data, $form) use ($module, $module_id, $notifications, $gamification, $webhooks){
									// R2.0 — Rate limit anti-spam : 8 commentaires par user / 5 min
									$rateLimit = new \NF\NeoFrag\Libraries\Rate_Limit($this);
									$rl_key    = 'comment:user:'.(int)$this->user->id;
									$rl_check  = $rateLimit->check($rl_key);
									if (!$rl_check['allowed'])
									{
										$form->error($this->lang('Trop de commentaires récents. Réessaye dans %d minute(s).', ceil($rl_check['retry_after'] / 60)));
										return;
									}
									$rateLimit->hit($rl_key, 8, 300, 600);

									$this	->model2('comment')
											->set('module',    $module)
											->set('module_id', $module_id)
											->set('content',   $data['comment'])
											->create();

									if ($webhooks)
									{
										$webhooks->trigger('comment.created', [
											'module'    => $module,
											'module_id' => (int)$module_id,
											'user_id'   => (int)$this->user->id,
											'username'  => $this->user->username
										]);
									}

									if ($gamification)
									{
										$gamification->earn((int)$this->user->id, 'comment');
										$gamification->recompute((int)$this->user->id);
									}

									if ($notifications)
									{
										$actor = (int)$this->user->id;

										// Propriétaire du contenu.
										$notifications->push_to_content_owner($module, $module_id, 'comment', $this->lang('%s a commenté votre publication', $this->user->username), $actor);

										// Abonnés à la discussion (push_unique dédup → le propriétaire déjà notifié est ignoré).
										$sub_type = $module === 'articles' ? 'article' : $module;
										$url      = $notifications->content_url($module, $module_id);

										foreach ($notifications->subscribers($sub_type, $module_id, $actor) as $uid)
										{
											$notifications->push_unique($uid, 'comment', $this->lang('Nouveau commentaire sur un contenu que vous suivez'), $url, $actor);
										}
									}

									notify($this->lang('Commentaire envoyé'));

									refresh();
								})
								->submit('Envoyer')
			]);
		}
		else
		{
			$new = '<div class="alert alert-danger" role="alert">'.icon('fas fa-ban').' '.$this->lang('Vous devez être identifié pour pouvoir poster un commentaire').'</div>';
		}

		return $this->css('comments')
					->js('comments')
					->panel()
					->style('comments')
					->heading()
					->heading($this->label($module, $module_id)->align('right'))
					->body('<a name="comments"></a>'.$new.$this->_comments($module, $module_id)->view('comment'));
	}

	public function admin($module, $module_id)
	{
		return '<a href="'.url('admin/comments/'.url_title($module).'/'.$module_id).'">'.$this->count($module, $module_id).'</a>';
	}

	public function link($module, $module_id, $url)
	{
		return '<a href="'.url($url.'#comments').'">'.$this->label($module, $module_id).'</a>';
	}

	public function count($module, $module_id)
	{
		// Hors corbeille : un commentaire soft-deleted ne compte pas (la ligne reste
		// chargée pour le threading mais n'est pas comptabilisée).
		return $this->model()->count_live($module, $module_id);
	}

	public function label($module, $module_id)
	{
		return parent::label($this->no_translate($this->count($module, $module_id)), $this->info()->icon);
	}

	public function delete($module, $module_id)
	{
		// Suppression réelle (le contenu parent est purgé → ses commentaires aussi).
		return $this->model()->delete_all($module, $module_id);
	}

	protected function _comments($module, $module_id)
	{
		static $comments = [];

		if (!isset($comments[$module][$module_id]))
		{
			$comments[$module][$module_id] = $this	->collection('comment')
													->where('module', $module)
													->where('module_id', $module_id)
													->order_by('IFNULL(parent_id, id) DESC');
		}

		return $comments[$module][$module_id];
	}
}
