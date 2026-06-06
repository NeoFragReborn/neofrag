<?php
/**
 * https://neofr.ag
 * Module Gamification — socle karma / points / VIP.
 *
 * - KARMA (Phase 1) : réputation dérivée et cachée (nf_karma) depuis réactions reçues
 *   + contenu publié + ancienneté.
 * - POINTS (Phase 2) : monnaie virtuelle (nf_user_points + nf_points_log) gagnée par
 *   l'activité (barème customisable en admin), dépensable (shop/VIP à venir).
 *
 * API publique (via $this->module('gamification')->...) :
 *   get($uid) / badge($uid) / recompute($uid)           — karma
 *   get_points($uid) / earn($uid,$action) / spend_points($uid,$amount,$reason)
 *   add_points($uid,$amount,$type,$reason)              — crédit/débit brut
 *
 * Barème lu depuis la config (nf_settings) avec valeurs par défaut → réglable dans
 * l'admin du module (gam_karma_*, gam_pt_*, gam_cap_*).
 */

namespace NF\Modules\Gamification;

use NF\NeoFrag\Addons\Module;

class Gamification extends Module
{
	/* Poids du karma : clé interne => [clé config, défaut]. */
	const KARMA_WEIGHTS = [
		'reaction'  => ['gam_karma_reaction',  5],
		'content'   => ['gam_karma_content',   1],
		'seniority' => ['gam_karma_seniority', 2],
	];

	/* Barème des points : action => [clé config gain, clé config plafond/jour, gain défaut, plafond défaut]. */
	const POINT_RULES = [
		'comment'           => ['gam_pt_comment',           'gam_cap_comment',           5,  50],
		'forum_message'     => ['gam_pt_forum_message',     'gam_cap_forum_message',     3,  60],
		'forum_topic'       => ['gam_pt_forum_topic',       'gam_cap_forum_topic',       10, 30],
		'reaction_received' => ['gam_pt_reaction_received', 'gam_cap_reaction_received', 2,  0],
		'reaction_given'    => ['gam_pt_reaction_given',    'gam_cap_reaction_given',    1,  20],
		'news'              => ['gam_pt_news',              'gam_cap_news',              20, 0],
		'login'             => ['gam_pt_login',             'gam_cap_login',             5,  5],
	];

	/* Résolution du propriétaire d'un contenu (aligné sur reactions::ALLOWED_TYPES). */
	const OWNER_MAP = [
		'comment'       => ['nf_comment',         'id'],
		'forum-message' => ['nf_forum_messages',  'message_id'],
		'article'       => ['nf_articles',        'article_id'],
		'news'          => ['nf_news',            'news_id'],
	];

	/* Sources « réactions reçues » : [content_type, table propriétaire, clé primaire]. */
	const REACTION_SOURCES = [
		['comment',       'nf_comment',        'id'],
		['forum-message', 'nf_forum_messages', 'message_id'],
		['article',       'nf_articles',       'article_id'],
		['news',          'nf_news',           'news_id'],
	];

	/* Sources « contenu publié » : [table, colonne auteur]. */
	const CONTENT_SOURCES = [
		['nf_comment',        'user_id'],
		['nf_forum_messages', 'user_id'],
		['nf_news',           'user_id'],
		['nf_articles',       'user_id'],
	];

	/* Paliers de réputation (du plus bas au plus haut). */
	const TIERS = [
		['min' => 0,    'name' => 'Novice',  'color' => '#8a91a0', 'icon' => 'far fa-circle'],
		['min' => 50,   'name' => 'Bronze',  'color' => '#cd7f32', 'icon' => 'fas fa-medal'],
		['min' => 150,  'name' => 'Argent',  'color' => '#9aa3ad', 'icon' => 'fas fa-medal'],
		['min' => 400,  'name' => 'Or',      'color' => '#d4af37', 'icon' => 'fas fa-medal'],
		['min' => 1000, 'name' => 'Platine', 'color' => '#4fd0c0', 'icon' => 'fas fa-trophy'],
		['min' => 2500, 'name' => 'Diamant', 'color' => '#5ab0ff', 'icon' => 'fas fa-gem'],
	];

	protected function __info()
	{
		return [
			'title'       => $this->lang('Gamification'),
			'description' => $this->lang('Karma, points et VIP. Réputation et monnaie virtuelle dérivées de l\'activité (barème réglable).'),
			'icon'        => 'fas fa-star',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'admin'       => TRUE,
			'depends'     => ['neofrag' => '1.0.0'],
			'routes'      => [
				'admin' => 'index'
			]
		];
	}

	public function __init()
	{
		// Bonus de présence quotidien (lazy, plafonné 1/jour) : crédité au 1er chargement
		// du module sur le front pour un membre connecté. Pas en admin/ajax.
		if (!$this->url->admin && !$this->url->ajax && $this->user())
		{
			$this->daily_presence();
		}
	}

	/** Lecture d'une valeur de config entière avec défaut. */
	private function cfg($key, $default)
	{
		$v = $this->config->{$key};

		return ($v === NULL || $v === '') ? (int)$default : (int)$v;
	}

	/* ------------------------------------------------------------------ Karma */

	/** Score karma courant. Recalcule si absent du cache ou périmé (> 24 h). */
	public function get($user_id)
	{
		$user_id = (int)$user_id;

		if (!$user_id)
		{
			return 0;
		}

		$row = $this->db	->select('score', 'UNIX_TIMESTAMP(updated_at) AS ts')
							->from('nf_karma')
							->where('user_id', $user_id)
							->row(FALSE);

		if (!$row || (time() - (int)$row['ts']) > 86400)
		{
			return $this->recompute($user_id);
		}

		return (int)$row['score'];
	}

	/** Recalcule et persiste le karma d'un membre depuis les sources. @return int score */
	public function recompute($user_id)
	{
		$user_id = (int)$user_id;

		if (!$user_id)
		{
			return 0;
		}

		$received = 0;
		foreach (self::REACTION_SOURCES as list($type, $table, $pk))
		{
			$received += (int)$this->db	->select('COUNT(*)')
										->from('nf_reactions r')
										->join($table.' t', 't.'.$pk.' = r.content_id')
										->where('r.content_type', $type)
										->where('t.user_id', $user_id)
										->row();
		}

		$content = 0;
		foreach (self::CONTENT_SOURCES as list($table, $col))
		{
			$content += (int)$this->db	->select('COUNT(*)')
										->from($table)
										->where($col, $user_id)
										->row();
		}

		$months = 0;
		if ($reg = $this->db->select('registration_date')->from('nf_user')->where('id', $user_id)->row())
		{
			$ts     = is_numeric($reg) ? (int)$reg : strtotime((string)$reg);
			$months = $ts ? min(24, (int)floor((time() - $ts) / (30 * 86400))) : 0;
		}

		$score = $received * $this->cfg(self::KARMA_WEIGHTS['reaction'][0],  self::KARMA_WEIGHTS['reaction'][1])
			   + $content  * $this->cfg(self::KARMA_WEIGHTS['content'][0],   self::KARMA_WEIGHTS['content'][1])
			   + $months   * $this->cfg(self::KARMA_WEIGHTS['seniority'][0], self::KARMA_WEIGHTS['seniority'][1]);

		$data = [
			'score'              => $score,
			'reactions_received' => $received,
			'content_count'      => $content
		];

		if ($this->db->from('nf_karma')->where('user_id', $user_id)->empty())
		{
			$this->db->insert('nf_karma', ['user_id' => $user_id] + $data);
		}
		else
		{
			$this->db->where('user_id', $user_id)->update('nf_karma', $data);
		}

		return $score;
	}

	/** Palier correspondant à un score. */
	public function tier($score)
	{
		$tier = self::TIERS[0];

		foreach (self::TIERS as $t)
		{
			if ($score >= $t['min'])
			{
				$tier = $t;
			}
		}

		return $tier;
	}

	/** Badge HTML (palier + score en tooltip). Réutilisable (profil, forum…). */
	public function badge($user_id)
	{
		$score = $this->get($user_id);
		$tier  = $this->tier($score);

		return '<span class="nf-karma-badge" title="'.$score.' karma" style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;font-size:11.5px;font-weight:600;color:#fff;background:'.$tier['color'].';">'
			.icon($tier['icon']).' '.htmlspecialchars($tier['name'])
			.'</span>';
	}

	/* ----------------------------------------------------------------- Points */

	/** Solde de points d'un membre. */
	public function get_points($user_id)
	{
		$user_id = (int)$user_id;

		return $user_id ? (int)$this->db->select('total')->from('nf_user_points')->where('user_id', $user_id)->row() : 0;
	}

	/** Crédit/débit brut. @return int nouveau solde */
	public function add_points($user_id, $amount, $type, $reason = '')
	{
		$user_id = (int)$user_id;
		$amount  = (int)$amount;

		if (!$user_id || !$amount)
		{
			return $this->get_points($user_id);
		}

		if ($this->db->from('nf_user_points')->where('user_id', $user_id)->empty())
		{
			$this->db->insert('nf_user_points', [
				'user_id' => $user_id,
				'total'   => max(0, $amount),
				'earned'  => $amount > 0 ? $amount : 0,
				'spent'   => $amount < 0 ? -$amount : 0
			]);
		}
		else
		{
			$row = $this->db->select('total', 'earned', 'spent')->from('nf_user_points')->where('user_id', $user_id)->row(FALSE);

			$this->db->where('user_id', $user_id)->update('nf_user_points', [
				'total'  => max(0, (int)$row['total'] + $amount),
				'earned' => (int)$row['earned'] + ($amount > 0 ? $amount : 0),
				'spent'  => (int)$row['spent']  + ($amount < 0 ? -$amount : 0)
			]);
		}

		$this->db->insert('nf_points_log', [
			'user_id' => $user_id,
			'amount'  => $amount,
			'type'    => mb_substr((string)$type, 0, 50),
			'reason'  => mb_substr((string)$reason, 0, 255)
		]);

		return $this->get_points($user_id);
	}

	/** Gain de points pour une action du barème, plafonné par jour (anti-farm). @return int crédité */
	public function earn($user_id, $action, $reason = '')
	{
		$user_id = (int)$user_id;

		if (!$user_id || !isset(self::POINT_RULES[$action]))
		{
			return 0;
		}

		list($amount_key, $cap_key, $def_amount, $def_cap) = self::POINT_RULES[$action];

		$amount = $this->cfg($amount_key, $def_amount);

		if ($amount <= 0)
		{
			return 0;
		}

		$cap = $this->cfg($cap_key, $def_cap);

		if ($cap > 0)
		{
			$today = (int)$this->db	->select('COALESCE(SUM(amount), 0)')
									->from('nf_points_log')
									->where('user_id', $user_id)
									->where('type', $action)
									->where('amount >', 0)
									->where('created_at >=', date('Y-m-d').' 00:00:00')
									->row();

			$amount = min($amount, $cap - $today);

			if ($amount <= 0)
			{
				return 0;
			}
		}

		$this->add_points($user_id, $amount, $action, $reason);

		return $amount;
	}

	/** Dépense de points (refusée si solde insuffisant). @return bool */
	public function spend_points($user_id, $amount, $reason = '')
	{
		$user_id = (int)$user_id;
		$amount  = (int)$amount;

		if ($amount <= 0 || $this->get_points($user_id) < $amount)
		{
			return FALSE;
		}

		$this->add_points($user_id, -$amount, 'spend', $reason);

		return TRUE;
	}

	/** Bonus de présence quotidien du membre courant (plafonné 1/jour via le barème). */
	public function daily_presence()
	{
		if ($this->user())
		{
			$this->earn((int)$this->user->id, 'login', $this->lang('Connexion quotidienne'));
		}
	}

	/* -------------------------------------------------------------------- VIP */

	/** Octroie/prolonge le statut VIP de N jours (cumule si déjà VIP non expiré). */
	public function grant_vip($user_id, $days, $source = '')
	{
		$user_id = (int)$user_id;
		$days    = (int)$days;

		if (!$user_id || $days <= 0)
		{
			return;
		}

		$exists  = !$this->db->from('nf_vip')->where('user_id', $user_id)->empty();
		$current = $exists ? $this->db->select('expires_at')->from('nf_vip')->where('user_id', $user_id)->row() : NULL;
		$base    = ($current && strtotime((string)$current) > time()) ? strtotime((string)$current) : time();
		$expires = date('Y-m-d H:i:s', $base + $days * 86400);

		if ($exists)
		{
			$this->db->where('user_id', $user_id)->update('nf_vip', ['expires_at' => $expires, 'source' => mb_substr((string)$source, 0, 50)]);
		}
		else
		{
			$this->db->insert('nf_vip', ['user_id' => $user_id, 'expires_at' => $expires, 'source' => mb_substr((string)$source, 0, 50)]);
		}
	}

	/** Date d'expiration VIP si actif, sinon NULL. */
	public function vip_expires($user_id)
	{
		$user_id = (int)$user_id;

		if (!$user_id)
		{
			return NULL;
		}

		$exp = $this->db->select('expires_at')->from('nf_vip')->where('user_id', $user_id)->row();

		return ($exp && strtotime((string)$exp) > time()) ? (string)$exp : NULL;
	}

	/** Le membre est-il VIP (non expiré) ? */
	public function is_vip($user_id)
	{
		return $this->vip_expires($user_id) !== NULL;
	}

	/** Jours restants de VIP (0 si non VIP). */
	public function vip_days_left($user_id)
	{
		$exp = $this->vip_expires($user_id);

		return $exp ? max(0, (int)ceil((strtotime($exp) - time()) / 86400)) : 0;
	}

	/** Badge VIP HTML (vide si non VIP). */
	public function vip_badge($user_id)
	{
		if (!$this->is_vip($user_id))
		{
			return '';
		}

		return '<span class="nf-vip-badge" title="VIP" style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;font-size:11.5px;font-weight:700;color:#3a2a00;background:linear-gradient(135deg,#ffd75e,#e0a32e);">'
			.icon('fas fa-crown').' VIP</span>';
	}

	/* ------------------------------------------------------------- Déclencheurs */

	/** Propriétaire d'un contenu (via OWNER_MAP). @return int|null */
	public function content_owner($type, $id)
	{
		if (!isset(self::OWNER_MAP[$type]))
		{
			return NULL;
		}

		list($table, $pk) = self::OWNER_MAP[$type];

		$owner = $this->db	->select('user_id')
							->from($table)
							->where($pk, (int)$id)
							->row();

		return $owner ? (int)$owner : NULL;
	}

	/** Toggle d'une réaction : recalcule le karma du propriétaire du contenu. */
	public function on_reaction($type, $id)
	{
		if ($owner = $this->content_owner($type, $id))
		{
			$this->recompute($owner);
		}
	}
}
