<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Notifications — centre de notifications in-site (cloche + non-lus).
 * Alimenté par appels directs depuis les modules émetteurs :
 *   $this->module('notifications')->push_to_content_owner('news', $id, 'comment', $title, $url, $actor_id);
 * Rendu dans la navbar via bell() (thème).
 */

namespace NF\Modules\Notifications;

use NF\NeoFrag\Addons\Module;

class Notifications extends Module
{
	// Plus de registre en dur ici : le proprietaire d'un contenu, ses abonnements et son URL
	// viennent des descripteurs declares par les modules eux-memes (cf. Module::content_types(),
	// inversion du 2026-09-15). Ce module ne nomme donc plus news, articles ni forum.

	protected function __info()
	{
		return [
			'title'       => $this->lang('Notifications'),
			'description' => $this->lang('Centre de notifications in-site (commentaires, réactions…).'),
			'icon'        => 'far fa-bell',
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
				'{pages}'                          => 'index',
				'ajax/read/{id}'                   => '_read',
				'ajax/read-all'                    => '_read_all',
				'ajax/subscribe/{url_title}/{id}'  => '_subscribe',
			]
		];
	}

	/**
	 * Les TYPES de notifications que le site envoie (chantier A, étape A4) : chaque module installé déclare les
	 * siens par une méthode types_de_notification() de sa classe — `type` (celui de `nf_notifications.type`),
	 * `titre` (ce que le membre lit dans ses préférences), `email` (TRUE si le module sait aussi l'envoyer par
	 * e-mail) et `ordre`. C'est la liste que montrent les préférences.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function types(): array
	{
		if ($this->_types !== NULL)
		{
			return $this->_types;
		}

		$types = [];

		foreach (NeoFrag()->model2('addon')->get('module') as $module)
		{
			if ($module instanceof Module && method_exists($module, 'types_de_notification'))
			{
				foreach ((array) $module->types_de_notification() as $type)
				{
					if (is_array($type) && !empty($type['type']) && !empty($type['titre']))
					{
						$types[(string) $type['type']] = $type + ['email' => FALSE, 'ordre' => 50];
					}
				}
			}
		}

		usort($types, fn ($a, $b) => (int) $a['ordre'] <=> (int) $b['ordre']);

		return $this->_types = $types;
	}

	/** @var list<array<string, mixed>>|null les types déjà rassemblés */
	private ?array $_types = NULL;

	/**
	 * Le membre veut-il recevoir ce type de notification, sur ce canal (`site` : la cloche, `email`) ? Sans réglage
	 * de sa part, oui : il reçoit tout, comme avant les préférences.
	 */
	public function veut(int $user_id, string $type, string $canal = 'site'): bool
	{
		static $preferences = [];

		if (!isset($preferences[$user_id]))
		{
			$preferences[$user_id] = [];

			if ($user_id && $this->db->table_exists('nf_notifications_preferences'))
			{
				foreach ((array) $this->db->select('type', 'site', 'email')->from('nf_notifications_preferences')->where('user_id', $user_id)->get() as $ligne)
				{
					$preferences[$user_id][(string) $ligne['type']] = ['site' => (bool) $ligne['site'], 'email' => (bool) $ligne['email']];
				}
			}
		}

		return $preferences[$user_id][$type][$canal] ?? TRUE;
	}

	/** Crée une notification. Ignore l'auto-notification (acteur == destinataire). @return int|null */
	public function push($user_id, $type, $title, $url = '', $actor_id = NULL)
	{
		$user_id  = (int)$user_id;
		$actor_id = $actor_id !== NULL ? (int)$actor_id : NULL;

		if (!$user_id || ($actor_id !== NULL && $actor_id === $user_id))
		{
			return NULL;
		}

		// Ce que le membre a choisi de ne plus recevoir sur le site (préférences, chantier A, étape A4).
		if (!$this->veut($user_id, (string) $type, 'site'))
		{
			return NULL;
		}

		return $this->db->insert('nf_notifications', [
			'user_id'  => $user_id,
			'actor_id' => $actor_id,
			'type'     => mb_substr((string)$type, 0, 50),
			'title'    => mb_substr((string)$title, 0, 255),
			'url'      => mb_substr((string)$url, 0, 255)
		]);
	}

	/** Comme push() mais ignore si une notif non-lue identique (user, type, url) existe déjà (anti-spam). */
	public function push_unique($user_id, $type, $title, $url = '', $actor_id = NULL)
	{
		$user_id = (int)$user_id;

		if ($user_id && !$this->db	->from('nf_notifications')
									->where('user_id', $user_id)
									->where('type', (string)$type)
									->where('url', (string)$url)
									->where('is_read', 0)
									->empty())
		{
			return NULL;
		}

		return $this->push($user_id, $type, $title, $url, $actor_id);
	}

	/** Notifie le propriétaire d'un contenu (propriétaire + URL résolus via OWNER_MAP / content_url). */
	public function push_to_content_owner($content, $content_id, $type, $title, $actor_id = NULL)
	{
		$types = self::content_types();

		if (!isset($types[$content]) || empty($types[$content]['table']))
		{
			return NULL;
		}

		$table = $types[$content]['table'];
		$pk     = $types[$content]['pk'];

		// Module non installe : sa table n'existe pas — rien a notifier, pas de fatale.
		if (!$this->db->table_exists($table))
		{
			return NULL;
		}

		$owner = $this->db	->select('user_id')
							->from($table)
							->where($pk, (int)$content_id)
							->row();

		// push_unique : 1 seule notif non-lue par (destinataire, type, contenu) — anti-spam.
		return $owner ? $this->push_unique((int)$owner, $type, $title, $this->content_url($content, $content_id), $actor_id) : NULL;
	}

	/**
	 * URL publique d'un contenu, pour le lien de la notification.
	 *
	 * Ne construit plus rien elle-meme : elle delegue au module qui declare le contenu
	 * (cf. Module::content_url_of()). Ce module portait auparavant la logique d'URL de news,
	 * articles, forum et commentaires — il les nommait donc tous en dur.
	 *
	 * Conserve comme point d'entree public : `comments` et le widget `latest_comments` l'appellent.
	 */
	public function content_url($content, $content_id)
	{
		return self::content_url_of($content, (int) $content_id);
	}

	// --- Abonnements -------------------------------------------------------

	public static function is_subscribable($type)
	{
		$types = self::content_types();

		return !empty($types[$type]['subscribable']);
	}

	public function is_subscribed($type, $id, $user_id = NULL)
	{
		$user_id = $user_id !== NULL ? (int)$user_id : ($this->user() ? (int)$this->user->id : 0);

		if (!$user_id)
		{
			return FALSE;
		}

		return !$this->db	->from('nf_subscriptions')
							->where('user_id', $user_id)
							->where('content_type', $type)
							->where('content_id', (int)$id)
							->empty();
	}

	/** Bascule l'abonnement du user courant. @return bool nouvel état (TRUE = abonné). */
	public function toggle_subscription($type, $id)
	{
		if (!$this->user() || !self::is_subscribable($type))
		{
			return FALSE;
		}

		$user_id = (int)$this->user->id;
		$id      = (int)$id;

		if ($this->is_subscribed($type, $id, $user_id))
		{
			$this->db	->where('user_id', $user_id)
						->where('content_type', $type)
						->where('content_id', $id)
						->delete('nf_subscriptions');

			return FALSE;
		}

		$this->db->insert('nf_subscriptions', [
			'user_id'      => $user_id,
			'content_type' => $type,
			'content_id'   => $id
		]);

		return TRUE;
	}

	/** IDs des abonnés à une cible (optionnellement hors un user). */
	public function subscribers($type, $id, $exclude_user_id = NULL)
	{
		$this->db	->select('user_id')
					->from('nf_subscriptions')
					->where('content_type', $type)
					->where('content_id', (int)$id);

		if ($exclude_user_id !== NULL)
		{
			$this->db->where('user_id !=', (int)$exclude_user_id);
		}

		return array_map('intval', $this->db->get());
	}

	/** Bouton « Suivre / Suivi » (toggle AJAX). À appeler depuis une vue. */
	public function follow_button($type, $id)
	{
		if (!$this->user() || !self::is_subscribable($type))
		{
			return '';
		}

		$this->css('notifications')->js('notifications');

		$subscribed = $this->is_subscribed($type, $id);
		// `lang()` rend un objet de traduction differee. On le fige ici, parce que ces deux
		// valeurs partent ensuite dans `htmlspecialchars((string) ())`, qui n'accepte que des chaines.
		$follow     = (string) $this->lang('Suivre');
		$following  = (string) $this->lang('Suivi');

		return '<button type="button" class="btn btn-sm nf-follow-btn'.($subscribed ? ' following btn-secondary' : ' btn-outline-secondary').'"'
			.' data-follow-toggle data-follow-type="'.nf_texte($type).'" data-follow-id="'.(int)$id.'"'
			.' data-label-follow="'.nf_texte($follow).'" data-label-following="'.nf_texte($following).'">'
			.'<i class="'.($subscribed ? 'fas' : 'far').' fa-bell"></i> <span class="nf-follow-label">'.($subscribed ? $following : $follow).'</span>'
			.'</button>';
	}

	public function unread_count($user_id = NULL)
	{
		$user_id = $user_id !== NULL ? (int)$user_id : ($this->user() ? (int)$this->user->id : 0);

		if (!$user_id)
		{
			return 0;
		}

		return (int)$this->db	->select('COUNT(*)')
								->from('nf_notifications')
								->where('user_id', $user_id)
								->where('is_read', 0)
								->row();
	}

	/** Notifications récentes du user courant (avec pseudo de l'acteur). */
	public function recent($limit = 10)
	{
		if (!$this->user())
		{
			return [];
		}

		return $this->db	->select('n.id', 'n.type', 'n.title', 'n.url', 'n.is_read', 'n.created_at', 'a.username AS actor')
							->from('nf_notifications n')
							->join('nf_user a', 'a.id = n.actor_id', 'LEFT')
							->where('n.user_id', (int)$this->user->id)
							->order_by('n.id DESC')
							->limit((int)$limit)
							->get(FALSE);
	}

	public function mark_read($id)
	{
		if (!$this->user())
		{
			return;
		}

		$this->db	->where('id', (int)$id)
					->where('user_id', (int)$this->user->id)
					->update('nf_notifications', ['is_read' => 1]);
	}

	public function mark_all_read()
	{
		if (!$this->user())
		{
			return;
		}

		$this->db	->where('user_id', (int)$this->user->id)
					->where('is_read', 0)
					->update('nf_notifications', ['is_read' => 1]);
	}

	/** Rend l'item <li> de cloche pour la navbar (compteur + dropdown des récentes). */
	public function bell()
	{
		if (!$this->user())
		{
			return '';
		}

		$this->css('notifications')->js('notifications');

		$count = $this->unread_count();
		$items = $this->recent(8);

		$list = '';
		if (!$items)
		{
			$list = '<span class="dropdown-item-text text-muted">'.$this->lang('Aucune notification').'</span>';
		}
		else
		{
			foreach ($items as $n)
			{
				$list .= '<a class="dropdown-item nf-notif-item'.(empty($n['is_read']) ? ' unread' : '').'" href="'.url($n['url'] ?: 'user/notifications').'" data-notif-id="'.(int)$n['id'].'">'
					.'<span class="nf-notif-title">'.nf_texte($n['title']).'</span>'
					.'<small class="text-muted d-block">'.nf_texte($n['created_at']).'</small>'
					.'</a>';
			}
		}

		$badge = $count > 0 ? '<span class="badge text-bg-danger nf-notif-badge">'.($count > 99 ? '99+' : $count).'</span>' : '';

		return '<li class="nav-item dropdown nf-notif">'
			.'<a class="nav-link" href="#" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" title="'.nf_texte($this->lang('Notifications')).'">'
			.icon('far fa-bell').$badge
			.'</a>'
			.'<div class="dropdown-menu dropdown-menu-end nf-notif-menu">'
			.'<div class="dropdown-header d-flex justify-content-between align-items-center">'
			.'<span>'.$this->lang('Notifications').'</span>'
			.($count > 0 ? '<a href="#" data-notif-read-all class="small">'.$this->lang('Tout marquer comme lu').'</a>' : '')
			.'</div>'
			.'<div class="dropdown-divider"></div>'
			.$list
			.'<div class="dropdown-divider"></div>'
			.'<a class="dropdown-item text-center small" href="'.url('user/notifications').'">'.$this->lang('Voir tout').'</a>'
			.'</div>'
			.'</li>';
	}

	/**
	 * Les notifications dans le menu de l'espace membre (User::menu_espace(), chantier A), avec le nombre de
	 * non lues. La page n'était dans aucun menu : on n'y arrivait que par la cloche.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function espace_membre($user): array
	{
		return [['url' => 'user/notifications', 'titre' => (string) $this->lang('Notifications'), 'icone' => 'far fa-bell', 'badge' => (int) $this->unread_count((int) $user->id), 'ordre' => 20]];
	}
}
