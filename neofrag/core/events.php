<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * Event dispatcher core.
 * Permet aux modules de fire des events et à d'autres modules de s'abonner.
 *
 * Usage côté émetteur :
 *   $this->events->fire('forum.post.created', $message_id, $topic_id, $user_id);
 *
 * Usage côté listener (typiquement dans le __construct d'un module ou dans
 * un fichier de bootstrap chargé tôt) :
 *   $this->events->on('forum.post.created', function($message_id, $topic_id, $user_id) {
 *       // ... handler
 *   });
 *
 * Différence avec NF\NeoFrag\Core::trigger/on : ces methods sont protected et
 * one-shot (unset après fire). Events est public et persistent — un listener
 * reste actif pour tous les fires de la requête.
 */

namespace NF\NeoFrag\Core;

use NF\NeoFrag\Core;

class Events extends Core
{
	static protected $_listeners = [];

	public function on($event, $callback)
	{
		static::$_listeners[$event][] = $callback;
		return $this;
	}

	public function off($event)
	{
		unset(static::$_listeners[$event]);
		return $this;
	}

	/**
	 * Les événements qui PUBLIENT un contenu. Quand son auteur est sous shadow ban, ils ne se diffusent pas : ni courriel
	 * ni cloche, ni recopie sur Discord, ni annonce au salon public, ni points — le contenu n'existe que pour lui et les
	 * modérateurs (audit du 2026-10-09).
	 */
	const PUBLICATIONS = ['forum.topic.created', 'forum.post.created', 'forum.post.edited', 'talks.message.created', 'bugtracker.ticket.created', 'bugtracker.comment.created', 'bugtracker.comment.edited'];

	public function fire($event, ...$args)
	{
		if (in_array($event, self::PUBLICATIONS, TRUE) && is_array($args[0] ?? NULL) && !empty($args[0]['user_id']) && NeoFrag()->moderation->est_masque((int) $args[0]['user_id']))
		{
			return [];
		}

		$results = [];

		if (isset(static::$_listeners[$event]))
		{
			foreach (static::$_listeners[$event] as $callback)
			{
				try
				{
					$results[] = call_user_func_array($callback, $args);
				}
				catch (\Throwable $e)
				{
					if (nf_debogage_actif() || nf_trace_active())
					{
						$this->debug('EVENTS', 'Listener for "'.$event.'" threw: '.$e->getMessage());
					}
				}
			}
		}

		return $results;
	}

	public function listeners($event = NULL)
	{
		if ($event === NULL)
		{
			return static::$_listeners;
		}

		return static::$_listeners[$event] ?? [];
	}

	public function has($event)
	{
		return !empty(static::$_listeners[$event]);
	}
}
