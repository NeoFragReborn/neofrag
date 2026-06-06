<?php
/**
 * https://neofr.ag
 * Module Réactions — "j'aime" générique sur n'importe quel contenu (content_type + content_id).
 * Réutilisable par d'autres modules via $this->module('reactions')->bar($type, $id).
 */

namespace NF\Modules\Reactions;

use NF\NeoFrag\Addons\Module;

class Reactions extends Module
{
	// Types de contenu autorisés (anti-injection : on ne réagit que sur des cibles connues).
	// Tokens en forme URL-safe (a-z0-9-) car routés via le placeholder {url_title}.
	const ALLOWED_TYPES = ['comment', 'forum-message', 'article', 'news'];

	protected function __info()
	{
		return [
			'title'       => $this->lang('Réactions'),
			'description' => $this->lang('Système de « j\'aime » réutilisable (commentaires, forum, articles…).'),
			'icon'        => 'far fa-heart',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'depends'     => ['neofrag' => '1.0.0'],
			'routes'      => [
				// {type} n'est pas un placeholder NeoFrag : on réutilise {url_title} ([a-z0-9-]+).
				'ajax/toggle/{url_title}/{id}' => '_toggle'
			]
		];
	}

	public static function is_allowed($type)
	{
		return in_array($type, self::ALLOWED_TYPES, TRUE);
	}

	/** Nombre de réactions sur un contenu. */
	public function count($content_type, $content_id)
	{
		return (int)NeoFrag()->db	->select('COUNT(*)')
									->from('nf_reactions')
									->where('content_type', $content_type)
									->where('content_id', (int)$content_id)
									->row();
	}

	/** L'utilisateur courant a-t-il réagi ? */
	public function reacted($content_type, $content_id)
	{
		return $this->user() && !NeoFrag()->db	->from('nf_reactions')
												->where('user_id', $this->user->id)
												->where('content_type', $content_type)
												->where('content_id', (int)$content_id)
												->empty();
	}

	/**
	 * Rend le bouton « j'aime » + compteur pour un contenu. À appeler depuis une vue :
	 *   echo $this->module('reactions')->bar('comment', $comment->id);
	 */
	public function bar($content_type, $content_id)
	{
		if (!self::is_allowed($content_type))
		{
			return '';
		}

		$content_id = (int)$content_id;
		$count      = $this->count($content_type, $content_id);
		$reacted    = $this->reacted($content_type, $content_id);
		$logged     = (bool)$this->user();

		$this->css('reactions')->js('reactions');

		$attrs = $logged
			? ' data-reaction-toggle data-reaction-type="'.htmlspecialchars($content_type).'" data-reaction-id="'.$content_id.'"'
			: ' disabled title="'.htmlspecialchars($this->lang('Connecte-toi pour aimer'), ENT_QUOTES).'"';

		return '<button type="button" class="nf-reaction-btn'.($reacted ? ' reacted' : '').'"'.$attrs.'>'
			.'<i class="'.($reacted ? 'fas' : 'far').' fa-heart"></i> '
			.'<span class="nf-reaction-count">'.$count.'</span>'
			.'</button>';
	}
}
