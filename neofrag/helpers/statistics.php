<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

// Comptabilise une vue de contenu une seule fois par session (jamais pour les crawlers), pour éviter
// le gonflage artificiel des compteurs sur refresh / clics répétés d'un même visiteur. Retourne TRUE
// si la vue doit être incrémentée (1re fois cette session pour ce contenu), FALSE sinon.
function count_view($type, $id): bool
{
	if (is_crawler())
	{
		return FALSE;
	}

	$session = NeoFrag()->session;

	if (!$session)
	{
		return FALSE;
	}

	$key = $type.':'.(int)$id;

	if ($session('views', $key))
	{
		return FALSE;
	}

	$session->set('views', $key, TRUE);

	return TRUE;
}

function statistics($name, $value = NULL, $callback = NULL)
{
	static $statistics;

	if ($statistics === NULL)
	{
		foreach (NeoFrag()->db->from('nf_statistics')->get() as $stat)
		{
			$statistics[$stat['name']] = $stat['value'];
		}
	}

	if (func_num_args() > 1)
	{
		if (isset($statistics[$name]))
		{
			if ($callback === NULL || call_user_func($callback, $value, $statistics[$name]))
			{
				NeoFrag()->db	->where('name', $name)
										->update('nf_statistics', [
											'value' => $value
										]);
			}
		}
		else
		{
			NeoFrag()->db->insert('nf_statistics', [
				'name'  => $name,
				'value' => $value
			]);
		}
	}
	else
	{
		return $statistics[$name];
	}
}
