<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Comments;

use NF\NeoFrag\Addons\Module;

class Comments extends Module
{

	/** Descripteurs de contenu — cf. Module::content_types(). */
	public function declare_content_types()
	{
		return [
			'comment' => [
				'table' => 'nf_comment', 'pk' => 'id', 'author' => 'user_id',
				'reactable' => TRUE,
			],
		];
	}

	/** Un commentaire se montre s'il n'est pas supprimé et que le contenu commenté se montre. */
	public function contenu_visible(string $type, int $id): bool
	{
		$commentaire = $type === 'comment' ? $this->db->select('module', 'module_id')->from('nf_comment')->where('id', $id)->where('deleted_at IS NULL')->row() : NULL;

		return is_array($commentaire) && $commentaire && \NF\NeoFrag\Addons\Module::content_visible_of((string) $commentaire['module'], (int) $commentaire['module_id']);
	}

	/** URL d'un commentaire = celle du contenu commente, ancree sur le fil. */
	public function content_url($type, $id)
	{
		if ($type !== 'comment')
		{
			return '';
		}

		$comment = $this->db->select('module', 'module_id')->from('nf_comment')->where('id', (int) $id)->row(FALSE);

		if (!$comment)
		{
			return '';
		}

		// Delegation au module commente, via le resolveur partage : `comments` n'a pas a
		// connaitre news, articles ni forum.
		$url = self::content_url_of($comment['module'], (int) $comment['module_id']);

		return $url ? $url.'#comments' : '';
	}

	/** Corbeille : type sans table de langue — le « titre » vient de la colonne content. */
	public function trash_types()
	{
		return [
			'comment' => [
				'label'   => 'Commentaire', 'table' => 'nf_comment',
				'pk'      => 'id', 'content' => 'content',
				'restore' => 'restore_comment', 'purge' => 'purge_comment',
			],
		];
	}
	protected function __info()
	{
		return [
			'title'       => $this->lang('Commentaires'),
			'description' => $this->lang('Système de commentaires réutilisable par les modules (news, articles, etc.).'),
			'icon'        => 'far fa-comments',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'admin'       => TRUE,
			'routes'      => [
				'admin/{pages}' => 'index',
				// Les commentaires d'UN contenu précis. La colonne « Commentaires » des listes
				// d'administration pointait déjà ici — `admin()` plus bas construit l'adresse —
				// mais la route n'existait pas : chaque compteur menait à un 404. Constaté par
				// tools/check-liens.php sur neuf pages (actualités et événements).
				'admin/{url_title}/{id}'         => '_module',
				'admin/{url_title}/{id}/{pages}' => '_module'
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

		if ($this->user() && ($bloque = $this->moderation->is_blocked_for((int) $this->user->id, 'comments.write')))
		{
			// Une sanction qui interdit de commenter — restriction, muet ou bannissement des commentaires, bannissement du
			// site — : pas de formulaire, l'avis dit pourquoi. Aucune ne s'appliquait aux commentaires (2026-10-09).
			$new = $this->moderation->avis($bloque, 'mb-3');
		}
		else if ($this->user())
		{
			// Loader explicite : $this->module() depuis une classe Module mis-résout le type d'addon
			// (forward_static_call → get_called_class = la classe appelante). On force le static = Module.
			$notifications = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['notifications']);
			$gamification  = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['gamification']);
			$webhooks      = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['webhooks']);

			$new = $this->view('new', [
				'form' => $this	->form2()
								->compact()
								->rule($this->form_hidden('comment_id')) // cible d'une réponse (posée par comments.js)
								->rule($this->form_textarea('comment')
											->rows(4)
											->required()
											->editor()
								)
								->success(function($data, $form) use ($module, $module_id, $notifications, $gamification, $webhooks){
									// Un lien, quand une restriction les interdit : refusé, le texte reste à corriger.
									if ($refus = $this->moderation->lien_refuse((int) $this->user->id, $data['comment']))
									{
										$form->error($refus['message']);
										return;
									}

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

									$comment = $this	->model2('comment')
														->set('module',    $module)
														->set('module_id', $module_id)
														->set('content',   $data['comment']);

									// Réponse : rattache le nouveau commentaire à un commentaire de PREMIER niveau
									// (profondeur limitée à 1) du MÊME contenu, encore vivant. Validé en base pour
									// ne jamais faire confiance au comment_id posté.
									$parent_id = (int) ($data['comment_id'] ?? 0);
									if ($parent_id)
									{
										$parent = $this->db	->select('parent_id', 'module', 'module_id', 'deleted_at')
															->from('nf_comment')
															->where('id', $parent_id)
															->row();

										if ($parent && $parent['parent_id'] === NULL && $parent['deleted_at'] === NULL
											&& (string) $parent['module'] === (string) $module
											&& (int) $parent['module_id'] === (int) $module_id)
										{
											$comment->set('parent', $parent_id);
										}
									}

									$comment->create();

									// Sous shadow ban : le commentaire existe pour son auteur, rien ne l'annonce (audit du 2026-10-09).
									if ($this->moderation->est_masque((int) $this->user->id))
									{
										notify($this->lang('Commentaire envoyé'));
										refresh();
									}

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
								->submit($this->lang('Envoyer'))
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

			// Les commentaires d'un membre sous shadow ban ne se montrent qu'à lui et aux modérateurs (audit du 2026-10-09).
			if ($sans_masques = $this->moderation->condition_sans_masques('_.user_id'))
			{
				$comments[$module][$module_id]->where($sans_masques);
			}
		}

		return $comments[$module][$module_id];
	}

	/**
	 * Les notifications que ce module envoie, pour les préférences de chaque membre (Notifications::types(),
	 * chantier A, étape A4).
	 *
	 * @return list<array<string, mixed>>
	 */
	public function types_de_notification(): array
	{
		return [
			['type' => 'comment', 'titre' => (string) $this->lang('Un commentaire sur ce que j’ai publié ou ce que je suis'), 'ordre' => 30],
		];
	}
}
