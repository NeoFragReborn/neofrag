<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Modèle Newsletter — envoi programmé via file d'attente batchée.
 *
 * Cycle de vie d'une campagne : scheduled → sending → sent (ou cancelled).
 *   - compose      : crée la campagne 'scheduled' (scheduled_at = maintenant ou futur).
 *   - process_due  : appelé par le cron. Les campagnes échues passent 'sending' et leur file
 *                    (snapshot des abonnés CONFIRMÉS à cet instant → désinscriptions respectées)
 *                    est construite, puis jusqu'à $limit envois sont traités par passage. Quand
 *                    la file d'une campagne est vide, elle passe 'sent' (stats sent/failed figées).
 *
 * L'envoi ne boucle plus dans la requête HTTP (cf. ancien _compose) : il est borné par passage,
 * reprenable et résistant au timeout sur grosse liste.
 */

namespace NF\Modules\Newsletter\Models;

use NF\NeoFrag\Loadables\Model;

class Newsletter extends Model
{
	/**
	 * Le frein des demandes d'inscription, sur le modèle du livre d'or : [essais, fenêtre, blocage],
	 * en secondes. Par adresse IP, pour qu'une même source ne fasse pas envoyer des e-mails de
	 * confirmation en masse à des adresses de son choix ; par adresse e-mail, pour qu'une même boîte
	 * ne soit pas visée en boucle (2026-10-04).
	 */
	const FREIN = [
		'ip'      => [5, 3600, 3600],
		'adresse' => [3, 86400, 86400],
	];

	/**
	 * Les clés du frein (`Rate_Limit`) d'une demande : l'adresse IP, et l'adresse e-mail quand elle en
	 * est une — hachée, la table du frein n'a pas à garder d'adresses en clair.
	 *
	 * @return array<string, string>  type (clé de FREIN) => clé du frein
	 */
	public static function cles_du_frein(string $ip, ?string $email): array
	{
		$cles = ['ip' => 'newsletter:ip:'.$ip];

		if ($email !== NULL && $email !== '')
		{
			$cles['adresse'] = 'newsletter:adresse:'.hash('sha256', $email);
		}

		return $cles;
	}

	/** Une adresse saisie, nettoyée (minuscules, sans blancs autour) — NULL si ce n'en est pas une. */
	public static function adresse($saisie): ?string
	{
		$email = strtolower(trim(is_string($saisie) ? $saisie : ''));

		return ($email !== '' && strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) !== FALSE) ? $email : NULL;
	}

	/**
	 * Inscrit une adresse, en attente de sa confirmation (double opt-in). Une seule voie pour la page
	 * `newsletter` et pour le widget, qui ne disaient pas la même chose.
	 *
	 * @return array{statut: string, email: string, jeton: string}  statut : `invalide` (pas une adresse),
	 *   `inscrit` (déjà confirmée), `en_attente` (déjà demandée, pas confirmée), `nouveau` (ligne créée)
	 */
	public function inscrire($saisie, $user_id = NULL): array
	{
		if (($email = self::adresse($saisie)) === NULL)
		{
			return ['statut' => 'invalide', 'email' => '', 'jeton' => ''];
		}

		$existant = $this->db	->select('id', 'confirmed')
								->from('nf_newsletter_subscribers')
								->where('email', $email)
								->row();

		if (!empty($existant))
		{
			return ['statut' => $existant['confirmed'] ? 'inscrit' : 'en_attente', 'email' => $email, 'jeton' => ''];
		}

		$jeton = bin2hex(random_bytes(32));

		$this->db->insert('nf_newsletter_subscribers', [
			'email'   => $email,
			'token'   => $jeton,
			'user_id' => $user_id ? (int) $user_id : NULL
		]);

		return ['statut' => 'nouveau', 'email' => $email, 'jeton' => $jeton];
	}

	/** Retire une inscription que le courriel de confirmation n'a pas atteinte : l'adresse pourra réessayer. */
	public function annuler_inscription(string $jeton): void
	{
		$this->db	->where('token', $jeton)
					->where('confirmed', 0)
					->delete('nf_newsletter_subscribers');
	}

	/** Abonnés confirmés (= destinataires potentiels d'une campagne). */
	public function confirmed_count()
	{
		return (int)$this->db->from('nf_newsletter_subscribers')->where('confirmed', 1)->count();
	}

	/**
	 * Programme une campagne. $scheduled_at NULL = envoi immédiat (au prochain passage du cron / send_now).
	 * $segment : 'all' (tous les confirmés) | 'members' (confirmés rattachés à un compte) | 'group' (+ $group_id).
	 */
	public function schedule($subject, $content, $user_id, $scheduled_at = NULL, $segment = 'all', $group_id = NULL)
	{
		$segment  = in_array($segment, ['all', 'members', 'group'], TRUE) ? $segment : 'all';
		$group_id = ($segment === 'group' && $group_id) ? (int)$group_id : NULL;

		return (int)$this->db->insert('nf_newsletter_campaigns', [
			'subject'          => $subject,
			'content'          => $content,
			'user_id'          => (int)$user_id,
			'status'           => 'scheduled',
			'scheduled_at'     => $scheduled_at ?: date('Y-m-d H:i:s'),
			'segment'          => $segment,
			'segment_group_id' => $group_id,
			'recipients_total' => $this->segment_count($segment, $group_id)
		]);
	}

	/** Nombre d'abonnés confirmés dans un segment (cf. schedule()). */
	public function segment_count($segment, $group_id = NULL)
	{
		return (int)$this->_apply_segment($this->db->from('nf_newsletter_subscribers s')->where('s.confirmed', 1), $segment, $group_id)->count();
	}

	// Applique le filtre de segment à une requête déjà sur `nf_newsletter_subscribers s` (confirmés).
	protected function _apply_segment($db, $segment, $group_id)
	{
		if ($segment === 'members')
		{
			$db->where('s.user_id IS NOT NULL');
		}
		else if ($segment === 'group' && $group_id)
		{
			$db->join('nf_users_groups ug', 'ug.user_id = s.user_id', 'INNER')->where('ug.group_id', (int)$group_id);
		}

		return $db;
	}

	/**
	 * Traite les campagnes échues. À appeler depuis l'endpoint cron (et inline par « Envoyer maintenant »).
	 * @param  int  $limit  nombre maximum d'emails envoyés par passage (borne anti-timeout).
	 * @return array{campaigns:int,sent:int,failed:int}
	 */
	public function process_due($limit = 100)
	{
		$now    = date('Y-m-d H:i:s');
		$result = ['campaigns' => 0, 'sent' => 0, 'failed' => 0];

		// 1) Campagnes échues : scheduled → sending + construction de la file (atomique : seul le
		//    1er passage qui réussit la transition construit la file, pas de doublon).
		foreach ($this->db	->select('id')
							->from('nf_newsletter_campaigns')
							->where('status', 'scheduled')
							->where('scheduled_at <=', $now)
							->get() as $campaign_id)
		{
			$claimed = (int)$this->db	->where('id', (int)$campaign_id)
										->where('status', 'scheduled')
										->update('nf_newsletter_campaigns', ['status' => 'sending']);

			if ($claimed > 0)
			{
				$this->_build_queue((int)$campaign_id);
			}
		}

		// 2) Envoi borné des destinataires en attente, toutes campagnes 'sending' confondues.
		foreach ($this->db	->select('q.id', 'q.email', 'q.token', 'q.track_token', 'c.subject', 'c.content')
							->from('nf_newsletter_queue q')
							->join('nf_newsletter_campaigns c', 'q.campaign_id = c.id', 'INNER')
							->where('q.status', 'pending')
							->where('c.status', 'sending')
							->order_by('q.id ASC')
							->limit($limit)
							->get(FALSE) as $row)
		{
			$ok = $this->_send_one($row);

			$this->db	->where('id', (int)$row['id'])
						->update('nf_newsletter_queue', [
							'status'  => $ok ? 'sent' : 'failed',
							'sent_at' => $ok ? $now : NULL
						]);

			$ok ? $result['sent']++ : $result['failed']++;
		}

		// 3) Finalisation : une campagne 'sending' sans destinataire en attente passe 'sent'.
		foreach ($this->db	->select('id')
							->from('nf_newsletter_campaigns')
							->where('status', 'sending')
							->get() as $campaign_id)
		{
			$pending = (int)$this->db	->from('nf_newsletter_queue')
										->where('campaign_id', (int)$campaign_id)
										->where('status', 'pending')
										->count();

			if ($pending > 0)
			{
				continue;
			}

			$sent   = (int)$this->db->from('nf_newsletter_queue')->where('campaign_id', (int)$campaign_id)->where('status', 'sent')->count();
			$failed = (int)$this->db->from('nf_newsletter_queue')->where('campaign_id', (int)$campaign_id)->where('status', 'failed')->count();

			$this->db	->where('id', (int)$campaign_id)
						->update('nf_newsletter_campaigns', [
							'status'    => 'sent',
							'sent_to'   => $sent,
							'failed_to' => $failed,
							'sent_at'   => $now
						]);

			$result['campaigns']++;
		}

		return $result;
	}

	/** « Envoyer maintenant » : rend la campagne échue puis traite un 1er lot inline (feedback admin immédiat). */
	public function send_now($campaign_id)
	{
		$this->db	->where('id', (int)$campaign_id)
					->where('status', 'scheduled')
					->update('nf_newsletter_campaigns', ['scheduled_at' => date('Y-m-d H:i:s')]);

		return $this->process_due();
	}

	/** Annule une campagne tant qu'elle n'a pas commencé à partir (status 'scheduled' uniquement). */
	public function cancel($campaign_id)
	{
		return (int)$this->db	->where('id', (int)$campaign_id)
								->where('status', 'scheduled')
								->update('nf_newsletter_campaigns', ['status' => 'cancelled']) > 0;
	}

	// Snapshot des abonnés confirmés dans la file de la campagne (au moment où elle démarre).
	protected function _build_queue($campaign_id)
	{
		$total = 0;

		$c       = $this->db->select('segment', 'segment_group_id')->from('nf_newsletter_campaigns')->where('id', $campaign_id)->row(FALSE);
		$segment = $c['segment'] ?? 'all';
		$group   = $c['segment_group_id'] ?? NULL;

		foreach ($this->_apply_segment($this->db->select('s.email', 's.token')->from('nf_newsletter_subscribers s')->where('s.confirmed', 1), $segment, $group)->get(FALSE) as $sub)
		{
			$this->db->insert('nf_newsletter_queue', [
				'campaign_id' => $campaign_id,
				'email'       => $sub['email'],
				'token'       => $sub['token'],
				'track_token' => bin2hex(random_bytes(16))
			]);

			$total++;
		}

		$this->db->where('id', $campaign_id)->update('nf_newsletter_campaigns', ['recipients_total' => $total]);

		return $total;
	}

	// Envoi d'un destinataire (lien de désinscription + pixel de suivi d'ouverture). @return bool succès SMTP.
	protected function _send_one($row)
	{
		// Adresses ABSOLUES : relatives, le lien de désinscription et le pixel de suivi se résolvaient
		// contre le domaine du client de messagerie — se désinscrire était impossible depuis le courriel.
		$unsub   = absolute_url('newsletter/unsubscribe/'.$row['token']);
		$pixel   = !empty($row['track_token']) ? '<img src="'.absolute_url('newsletter/track/'.$row['track_token']).'" width="1" height="1" alt="" style="display:none">' : '';
		$content = $row['content']
			.'<hr><p style="font-size:0.85em;color:#888;text-align:center">'
			.$this->lang('Tu reçois ce mail car tu es inscrit à la newsletter de %s.', nf_texte($this->config->nf_name)).' '
			.'<a href="'.$unsub.'">'.$this->lang('Se désinscrire').'</a></p>'
			.$pixel;

		return (bool)$this->email
			->to($row['email'])
			->subject($row['subject'])
			->message(function() use ($content){
				return ['content' => $content];
			})
			->send();
	}

	/**
	 * Enregistre l'ouverture d'un email (pixel de suivi). Pose opened_at + incrémente opened_to de la
	 * campagne, une seule fois par destinataire (claim atomique). @return bool true si NOUVELLE ouverture.
	 */
	public function record_open($track_token)
	{
		$row = $this->db	->select('id', 'campaign_id')
							->from('nf_newsletter_queue')
							->where('track_token', (string) $track_token)
							->where('opened_at', NULL)
							->row(FALSE);

		if (!$row)
		{
			return FALSE;
		}

		$claimed = (int) $this->db	->where('id', (int) $row['id'])
									->where('opened_at', NULL)
									->update('nf_newsletter_queue', ['opened_at' => date('Y-m-d H:i:s')]);

		if ($claimed < 1)
		{
			return FALSE;
		}

		$this->db->where('id', (int) $row['campaign_id'])->update('nf_newsletter_campaigns', 'opened_to = opened_to + 1');

		return TRUE;
	}
}
