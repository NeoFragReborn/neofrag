<?php
declare(strict_types=1);
namespace NF\Widgets\Calendar\Controllers;
use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($config = [])
	{
		return $this->upcoming($config);
	}

	public function upcoming($config = [])
	{
		$count = max(1, min(20, (int)($config['count'] ?? 5)));

		$events = NeoFrag()->db	->select('id', 'title', 'start_at', 'all_day', 'color')
								->from('nf_calendar_events')
								->where('published', '1')
								->where('start_at >=', date('Y-m-d 00:00:00'))
								->order_by('start_at ASC')
								->limit($count)
								->get();

		$body = '';
		if (empty($events))
		{
			$body = '<div class="text-center text-muted py-2"><small>'.$this->lang('Aucun événement à venir').'</small></div>';
		}
		else
		{
			$body = '<ul class="list-unstyled mb-0">';
			foreach ($events as $e)
			{
				$color = $e['color'] ? $e['color'] : '#03c1a2';
				$body .= '<li class="py-1 border-bottom" style="border-left:3px solid '.nf_texte($color).';padding-left:0.5rem">';
				$body .= '<a href="'.url('calendar/'.$e['id'].'/'.url_title($e['title'])).'"><i class="far fa-calendar me-1"></i>'.nf_texte($e['title']).'</a>';
				$body .= '<br><small class="text-muted">'.\NF\Modules\Calendar\Calendar::format_dt($e['start_at'], !empty($e['all_day'])).'</small>';
				$body .= '</li>';
			}
			$body .= '</ul>';
		}

		if (($config['display_panel'] ?? 'oui') === 'non')
		{
			return $body;
		}

		return $this->panel()
					->heading($this->lang('Prochains événements'), 'far fa-calendar')
					->body($body)
					->footer('<a href="'.url('calendar').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Voir le calendrier').'</a>', 'right');
	}

	/**
	 * La semaine en cours, du lundi au dimanche, dans le fuseau de celui qui regarde : chaque jour qui porte un
	 * événement est marqué, aujourd'hui entouré, et le prochain rendez-vous est dit en clair (2026-10-06, pour le
	 * thème Chronique ; il sert à tout thème).
	 */
	public function semaine($config = [])
	{
		$this->css('calendar');

		$stockage   = nf_fuseau_stockage();
		$maintenant = new \DateTimeImmutable('now', nf_fuseau());
		$lundi      = $maintenant->modify('monday this week')->setTime(0, 0);
		$dimanche   = $lundi->modify('+7 days');

		// Un jour de marge de chaque côté : un fuseau lointain fait tomber un événement la veille ou le lendemain.
		$evenements = NeoFrag()->db	->select('id', 'title', 'start_at', 'end_at', 'all_day')
									->from('nf_calendar_events')
									->where('published', '1')
									->where('start_at <', $dimanche->modify('+1 day')->setTimezone($stockage)->format('Y-m-d H:i:s'))
									->where('IFNULL(end_at, start_at) >=', $lundi->modify('-1 day')->setTimezone($stockage)->format('Y-m-d H:i:s'))
									->order_by('start_at ASC')
									->get();

		$marques = [];

		foreach ($evenements as $e)
		{
			$debut = self::moment((string) $e['start_at'], !empty($e['all_day']));
			$fin   = $e['end_at'] ? self::moment((string) $e['end_at'], !empty($e['all_day'])) : $debut;

			for ($jour = $debut->setTime(0, 0); $jour <= $fin && $jour < $dimanche; $jour = $jour->modify('+1 day'))
			{
				if ($jour >= $lundi)
				{
					$marques[$jour->format('Y-m-d')][] = $e;
				}
			}
		}

		$jours = [];

		for ($jour = $lundi; $jour < $dimanche; $jour = $jour->modify('+1 day'))
		{
			$cle     = $jour->format('Y-m-d');
			$jours[] = [
				'date'        => $cle,
				'numero'      => $jour->format('j'),
				'evenements'  => $marques[$cle] ?? [],
				'aujourdhui'  => $cle === $maintenant->format('Y-m-d')
			];
		}

		// Le prochain rendez-vous, cette semaine ou plus loin.
		$prochain = NeoFrag()->db	->select('id', 'title', 'start_at', 'all_day')
									->from('nf_calendar_events')
									->where('published', '1')
									->where('start_at >=', $maintenant->setTime(0, 0)->setTimezone($stockage)->format('Y-m-d H:i:s'))
									->order_by('start_at ASC')
									->limit(1)
									->row();

		$body = $this->view('semaine', [
			'jours'    => $jours,
			'prochain' => $prochain ?: NULL,
			'debut'    => $prochain ? self::moment((string) $prochain['start_at'], !empty($prochain['all_day'])) : NULL
		]);

		if (($config['display_panel'] ?? 'oui') === 'non')
		{
			return $body;
		}

		return $this->panel()
					->heading($this->lang('La semaine'), 'far fa-calendar')
					->body($body)
					->footer('<a href="'.url('calendar').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Voir le calendrier').'</a>', 'right');
	}

	/**
	 * Le prochain rendez-vous, mis en avant : sa date en grand (le jour de la semaine, le quantième, le mois), son titre,
	 * quand et où, le début de sa description, et le chemin vers lui — dans le fuseau de celui qui regarde (2026-10-06,
	 * pour le thème Pulse ; il sert à tout thème).
	 */
	public function prochain($config = [])
	{
		$this->css('calendar');

		$maintenant = new \DateTimeImmutable('now', nf_fuseau());
		$evenement  = NeoFrag()->db	->select('id', 'title', 'description', 'location', 'start_at', 'end_at', 'all_day')
									->from('nf_calendar_events')
									->where('published', '1')
									->where('start_at >=', $maintenant->setTime(0, 0)->setTimezone(nf_fuseau_stockage())->format('Y-m-d H:i:s'))
									->order_by('start_at ASC')
									->limit(1)
									->row();

		$debut = $evenement ? self::moment((string) $evenement['start_at'], !empty($evenement['all_day'])) : NULL;

		$body = $this->view('prochain', [
			'evenement' => $evenement ?: NULL,
			'debut'     => $debut,
			'fin'       => $evenement && $evenement['end_at'] ? self::moment((string) $evenement['end_at'], !empty($evenement['all_day'])) : NULL,
			'jours'     => $debut ? (int) $maintenant->setTime(0, 0)->diff($debut->setTime(0, 0))->days : NULL
		]);

		if (($config['display_panel'] ?? 'oui') === 'non')
		{
			return $body;
		}

		return $this->panel()
					->heading($this->lang('Le prochain rendez-vous'), 'far fa-calendar')
					->body($body)
					->footer('<a href="'.url('calendar').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Voir le calendrier').'</a>', 'right');
	}

	/**
	 * Un moment du calendrier, à montrer : une journée entière est une date de calendrier, sans fuseau
	 * (Calendar::format_dt()) ; un instant se lit dans le fuseau d'enregistrement, puis se montre dans celui de celui qui
	 * regarde.
	 */
	public static function moment(string $valeur, bool $journee): \DateTimeImmutable
	{
		return $journee
			? new \DateTimeImmutable(substr($valeur, 0, 10), nf_fuseau())
			: (new \DateTimeImmutable($valeur, nf_fuseau_stockage()))->setTimezone(nf_fuseau());
	}
}
