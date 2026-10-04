<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Réactions — "j'aime" générique sur n'importe quel contenu (content_type + content_id).
 * Réutilisable par d'autres modules via $this->module('reactions')->bar($type, $id).
 */

namespace NF\Modules\Reactions;

use NF\NeoFrag\Addons\Module;

class Reactions extends Module
{
	// Les types autorises viennent des descripteurs declares par les modules (Module::content_types(),
	// drapeau `reactable`) : c'est toujours une liste blanche — donc la garde anti-injection tient —
	// mais ce module ne nomme plus news, articles, forum ni comments. Les cles restent URL-safe
	// (a-z0-9-) car routees via le placeholder {url_title}.

	// Jeu de réactions (clé URL-safe → emoji), façon Discord/Facebook. Une réaction par
	// utilisateur et par contenu (le `reaction` de la ligne dit laquelle). 'love' = le cœur historique.
	const REACTIONS = [
		'like'  => '👍',
		'love'  => '❤️',
		'haha'  => '😂',
		'wow'   => '😮',
		'sad'   => '😢',
		'angry' => '😡',
	];

	protected function __info()
	{
		return [
			'title'       => $this->lang('Réactions'),
			'description' => $this->lang('Système de « j\'aime » réutilisable (commentaires, forum, articles…).'),
			'icon'        => 'far fa-heart',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
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
		$types = self::content_types();

		return !empty($types[$type]['reactable']);
	}

	/** Nombre TOTAL de réactions sur un contenu (tous emojis confondus). */
	public function count($content_type, $content_id)
	{
		return (int)NeoFrag()->db	->select('COUNT(*)')
									->from('nf_reactions')
									->where('content_type', $content_type)
									->where('content_id', (int)$content_id)
									->row();
	}

	/** Comptes par emoji : ['love' => 3, 'like' => 1, …] (seules les clés présentes). */
	public function counts($content_type, $content_id)
	{
		$out = [];

		foreach (NeoFrag()->db	->select('reaction', 'COUNT(*) AS n')
								->from('nf_reactions')
								->where('content_type', $content_type)
								->where('content_id', (int)$content_id)
								->group_by('reaction')
								->get() as $row)
		{
			if (isset(self::REACTIONS[$row['reaction']]))
			{
				$out[$row['reaction']] = (int)$row['n'];
			}
		}

		return $out;
	}

	/** Clé de la réaction de l'utilisateur courant sur ce contenu, ou NULL. */
	public function reacted($content_type, $content_id)
	{
		if (!$this->user())
		{
			return NULL;
		}

		$key = NeoFrag()->db	->select('reaction')
								->from('nf_reactions')
								->where('user_id', $this->user->id)
								->where('content_type', $content_type)
								->where('content_id', (int)$content_id)
								->row();

		return ($key && isset(self::REACTIONS[$key])) ? $key : NULL;
	}

	/** Résumé HTML des emojis présents (pill emoji + compte), trié par compte décroissant. */
	public static function summary_html(array $counts)
	{
		arsort($counts);
		$html = '';

		foreach ($counts as $key => $n)
		{
			if (isset(self::REACTIONS[$key]))
			{
				$html .= '<span class="nf-reaction-tally" data-reaction="'.$key.'">'.self::REACTIONS[$key].' '.(int)$n.'</span>';
			}
		}

		return $html;
	}

	/**
	 * Rend la barre de réactions (picker multi-emoji au survol + résumé). À appeler depuis une vue :
	 *   echo $this->module('reactions')->bar('comment', $comment->id);
	 */
	public function bar($content_type, $content_id)
	{
		if (!self::is_allowed($content_type))
		{
			return '';
		}

		$content_id = (int)$content_id;
		$counts     = $this->counts($content_type, $content_id);
		$mine       = $this->reacted($content_type, $content_id);
		$logged     = (bool)$this->user();

		$this->css('reactions')->js('reactions');

		$picker = '';
		foreach (self::REACTIONS as $key => $emoji)
		{
			$picker .= '<button type="button" class="nf-reaction-pick'.($mine === $key ? ' picked' : '').'" data-reaction="'.$key.'" aria-label="'.$key.'">'.$emoji.'</button>';
		}

		$main_emoji = ($mine !== NULL) ? self::REACTIONS[$mine] : '🙂';
		$attrs      = ' data-reaction-type="'.htmlspecialchars((string) ($content_type)).'" data-reaction-id="'.$content_id.'"';

		return '<span class="nf-reactions'.($mine !== NULL ? ' has-mine' : '').($logged ? '' : ' is-guest').'"'.$attrs
				.($logged ? '' : ' title="'.htmlspecialchars((string) ($this->lang('Connecte-toi pour aimer')), ENT_QUOTES).'"').'>'
			.'<span class="nf-reaction-control">'
				.'<button type="button" class="nf-reaction-main'.($mine !== NULL ? ' reacted' : '').'"'.($logged ? '' : ' disabled').'>'
					.'<span class="nf-reaction-emoji">'.$main_emoji.'</span>'
				.'</button>'
				.($logged ? '<span class="nf-reaction-picker">'.$picker.'</span>' : '')
			.'</span>'
			.'<span class="nf-reaction-summary">'.self::summary_html($counts).'</span>'
		.'</span>';
	}
}
