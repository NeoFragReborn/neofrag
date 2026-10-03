<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : les événements publiés et parus — une date de
 * publication à venir les tient encore cachés —, dont un VISITEUR peut lire le type
 * (`events.access_events_type`, que l'installation n'accorde pas d'office). Monolingue.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Events\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		$adresses = [];
		$types    = [];

		foreach ($this->db->select('event_id', 'type_id', 'title', 'date', 'publish_date')->from('nf_events')->where('published', '1')->order_by('date DESC')->get() as $evenement)
		{
			if (!empty($evenement['publish_date']) && strtotime((string) $evenement['publish_date']) > time())
			{
				continue;
			}

			$type = (int) $evenement['type_id'];

			if (!($types[$type] ??= (bool) $this->access('events', 'access_events_type', $type, 'visitors')))
			{
				continue;
			}

			$adresses[] = ['adresse' => 'events/'.$evenement['event_id'].'/'.url_title($evenement['title']), 'date' => $evenement['publish_date'] ?: NULL];
		}

		array_unshift($adresses, ['adresse' => 'events']);

		return $adresses;
	}
}
