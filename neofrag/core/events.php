<?php
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

	public function fire($event, ...$args)
	{
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
					if (NEOFRAG_DEBUG_BAR || NEOFRAG_LOGS)
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
