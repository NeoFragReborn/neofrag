<?php
declare(strict_types=1);
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

	/* Plus de registre en dur : proprietaire, sources de reactions et sources de contenu viennent
	   des descripteurs declares par les modules (cf. Module::content_types(), 2026-09-15). Ce module
	   ne nomme donc plus nf_news, nf_articles, nf_forum_messages ni nf_comment. La meme connaissance
	   etait auparavant recopiee ici en TROIS exemplaires, plus deux fois dans Notifications. */

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
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['gaming'],
			'requires'    => [],
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

	/**
	 * Lecture d'une valeur de config entière avec défaut.
	 *
	 * Publique parce que la page d'administration en a besoin AUSSI, et que la dupliquer a coûté
	 * cher : son contrôleur avait réécrit le test en oubliant `=== FALSE`, si bien que les dix-sept
	 * champs du barème s'affichaient VIDES et qu'un simple « Enregistrer » aurait écrit 0 partout.
	 */
	public function cfg($key, $default)
	{
		$v = $this->config->{$key};

		// Config::__get renvoie FALSE pour un setting absent (parent::__get) : le traiter comme
		// « non défini » sinon (int)false = 0 écraserait silencieusement TOUS les barèmes par défaut
		// (karma + points) tant qu'un admin ne les a pas saisis.
		return ($v === NULL || $v === '' || $v === FALSE) ? (int)$default : (int)$v;
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
		// Tous les types reactables declares, alias compris (le content_type stocke peut
		// employer l'une ou l'autre orthographe). Table absente = module non installe : on saute
		// au lieu de fataliser — ce que l'ancienne boucle en dur ne faisait pas.
		foreach (self::content_types() as $type => $desc)
		{
			if (empty($desc['reactable']) || empty($desc['table']) || !$this->db->table_exists($desc['table']))
			{
				continue;
			}

			$table = $desc['table'];
			$pk    = $desc['pk'];

			$received += (int)$this->db	->select('COUNT(*)')
										->from('nf_reactions r')
										->join($table.' t', 't.'.$pk.' = r.content_id')
										->where('r.content_type', $type)
										->where('t.user_id', $user_id)
										->row();
		}

		$content = 0;
		// Dedoublonnage par table : un alias designe le meme contenu, il ne doit pas compter deux fois.
		$tables = [];
		foreach (self::content_types() as $desc)
		{
			if (!empty($desc['table']) && !empty($desc['reactable']))
			{
				$tables[$desc['table']] = $desc['author'];
			}
		}

		foreach ($tables as $table => $col)
		{
			if (!$this->db->table_exists($table))
			{
				continue;
			}

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

		return '<span class="nf-karma-badge" title="'.$score.' karma" style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;font-size:11.5px;font-weight:600;color:'.couleur_lisible_sur($tier['color']).';background:'.$tier['color'].';">'
			.icon($tier['icon']).' '.htmlspecialchars((string) ($tier['name']))
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

		// Écrit par la base, en une requête : la ligne était lue puis réécrite, et deux crédits
		// simultanés — un gain de forum pendant un achat de points Stripe — n'en gardaient qu'un
		// (2026-10-04). La première écriture crée la ligne, les suivantes l'augmentent ; le solde ne
		// descend jamais sous zéro, comme avant. Que des entiers dans la requête.
		$credit = max(0, $amount);
		$debit  = max(0, -$amount);

		$this->db->execute('INSERT INTO nf_user_points (user_id, total, earned, spent) VALUES ('.$user_id.', '.$credit.', '.$credit.', '.$debit.')'
			.' ON DUPLICATE KEY UPDATE total = GREATEST(0, total + ('.$amount.')), earned = earned + '.$credit.', spent = spent + '.$debit);

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

	/**
	 * Dépense de points (refusée si solde insuffisant). @return bool
	 *
	 * Le solde est vérifié et débité par la MÊME requête (`… WHERE total >= montant`) : il était lu,
	 * puis débité, et deux dépenses simultanées passaient toutes les deux sur un solde qui n'en
	 * couvrait qu'une (2026-10-04). Dans une transaction, la ligne du membre reste verrouillée
	 * jusqu'à sa fin (cf. la boutique).
	 */
	public function spend_points($user_id, $amount, $reason = '')
	{
		$user_id = (int)$user_id;
		$amount  = (int)$amount;

		if (!$user_id || $amount <= 0)
		{
			return FALSE;
		}

		$debite = $this->db	->where('user_id', $user_id)
							->where('total >=', $amount)
							->update('nf_user_points', 'total = total - '.$amount.', spent = spent + '.$amount);

		if (!$debite)
		{
			return FALSE;
		}

		$this->db->insert('nf_points_log', [
			'user_id' => $user_id,
			'amount'  => -$amount,
			'type'    => 'spend',
			'reason'  => mb_substr((string)$reason, 0, 255)
		]);

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

		// Écrit par la base, en une requête : l'échéance était lue puis réécrite, et deux octrois
		// simultanés — un pack VIP payé pendant un achat VIP à la boutique — n'en gardaient qu'un
		// (2026-10-04). Un VIP en cours est prolongé depuis son échéance, un VIP échu repart de
		// maintenant ; les heures restent celles de PHP, comme partout où `nf_vip` est lue.
		$maintenant = date('Y-m-d H:i:s');
		$echeance   = date('Y-m-d H:i:s', time() + $days * 86400);
		$origine    = $this->db->escape_string(mb_substr((string)$source, 0, 50));

		$this->db->execute('INSERT INTO nf_vip (user_id, expires_at, source) VALUES ('.$user_id.', \''.$echeance.'\', \''.$origine.'\')'
			.' ON DUPLICATE KEY UPDATE expires_at = IF(expires_at > \''.$maintenant.'\', expires_at + INTERVAL '.$days.' DAY, \''.$echeance.'\'), source = \''.$origine.'\'');
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
		$types = self::content_types();

		if (empty($types[$type]['table']) || empty($types[$type]['reactable']))
		{
			return NULL;
		}

		$table = $types[$type]['table'];
		$pk    = $types[$type]['pk'];

		if (!$this->db->table_exists($table))
		{
			return NULL;
		}

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
