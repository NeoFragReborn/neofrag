<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * Chaque nombre ne se compte que si son module est installé et activé ($present), et seulement dans ce que le visiteur
 * peut voir — les mêmes règles que les pages de chaque module :
 * couplage(forum): les discussions et les messages, dans les forums que le visiteur peut lire (Forum::forums_lisibles()).
 * couplage(news): les actualités parues, ni brouillon ni programmées ni à la corbeille.
 * couplage(calendar): les rendez-vous publiés, à venir.
 * couplage(gallery): les photos des albums parus que le visiteur a le droit de voir (`gallery.gallery_see`).
 */

namespace NF\Widgets\Chiffres\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	/** Les nombres que le widget sait compter, dans l'ordre où l'administration les propose. */
	public const NOMBRES = ['membres', 'discussions', 'messages', 'actualites', 'rendez_vous', 'photos'];

	/** Ceux qu'il montre quand rien n'est réglé. */
	public const PAR_DEFAUT = ['membres', 'discussions', 'rendez_vous'];

	public function index($settings = [])
	{
		$this->css('chiffres');

		$choisis = array_values(array_intersect(self::NOMBRES, (array) ($settings['nombres'] ?? self::PAR_DEFAUT)));
		$nombres = [];

		foreach ($choisis ?: self::PAR_DEFAUT as $nom)
		{
			if (($valeur = $this->_compter($nom)) !== NULL)
			{
				$nombres[] = ['nom' => $nom, 'valeur' => $valeur, 'libelle' => $this->_libelle($nom, $valeur)];
			}
		}

		$body = $this->view('index', ['nombres' => $nombres]);

		if (($settings['display_panel'] ?? 'oui') === 'non')
		{
			return $body;
		}

		return $this->panel()
					->heading($this->lang('Le site en chiffres'), 'fas fa-chart-simple')
					->body($body);
	}

	/** Un nombre écrit dans la langue du site : « 1 214 » en français, « 1,214 » en anglais. */
	public static function nombre(int $valeur): string
	{
		$config = NeoFrag()->config;
		$langue = isset($config->lang) && is_object($config->lang) ? (string) $config->lang->info()->name : 'fr';
		$locale = ['fr' => 'fr_FR', 'en' => 'en_GB', 'de' => 'de_DE', 'es' => 'es_ES', 'it' => 'it_IT', 'pt' => 'pt_PT'][$langue] ?? $langue;

		return class_exists('NumberFormatter') ? (string) (new \NumberFormatter($locale, \NumberFormatter::DECIMAL))->format($valeur) : (string) $valeur;
	}

	/** Le nombre, ou NULL quand son module manque (le nombre ne paraît pas). */
	private function _compter(string $nom): ?int
	{
		$present = static fn (string $module): bool => ($addon = @NeoFrag()->module($module)) && $addon->is_enabled();
		$stockage = nf_fuseau_stockage();
		$maintenant = new \DateTimeImmutable('now', $stockage);

		switch ($nom)
		{
			case 'membres':
				return (int) $this->db->select('COUNT(*)')->from('nf_user')->where('deleted', '0')->row();

			case 'discussions':
			case 'messages':
				if (!$present('forum') || !($forum = NeoFrag()->module('forum')) || !(($modele = $forum->model('forum')) instanceof \NF\Modules\Forum\Models\Forum))
				{
					return NULL;
				}

				if (!($forums = $modele->forums_lisibles()))
				{
					return 0;
				}

				return $nom === 'discussions'
					? (int) $this->db	->select('COUNT(*)')
										->from('nf_forum_topics t')
										->join('nf_forum_messages m', 'm.message_id = t.message_id', 'INNER')
										->where('t.forum_id', $forums)
										->where('m.deleted_at', NULL)
										->row()
					: (int) $this->db	->select('COUNT(*)')
										->from('nf_forum_messages m')
										->join('nf_forum_topics t', 't.topic_id = m.topic_id', 'INNER')
										->where('t.forum_id', $forums)
										->where('m.deleted_at', NULL)
										->row();

			case 'actualites':
				if (!$present('news'))
				{
					return NULL;
				}

				return (int) $this->db	->select('COUNT(*)')
										->from('nf_news n')
										->where('n.deleted_at', NULL)
										->where('n.published', TRUE)
										->where('n.date <=', $maintenant->format('Y-m-d H:i:s'))
										->row();

			case 'rendez_vous':
				if (!$present('calendar'))
				{
					return NULL;
				}

				return (int) $this->db	->select('COUNT(*)')
										->from('nf_calendar_events')
										->where('published', '1')
										->where('start_at >=', $maintenant->setTimezone(nf_fuseau())->setTime(0, 0)->setTimezone($stockage)->format('Y-m-d H:i:s'))
										->row();

			case 'photos':
				if (!$present('gallery'))
				{
					return NULL;
				}

				$albums = [];

				foreach ($this->db	->select('g.gallery_id')
									->from('nf_gallery g')
									->where('g.deleted_at', NULL)
									->where('g.published', TRUE)
									->where('g.date <=', $maintenant->format('Y-m-d H:i:s'))
									->get() as $album)
				{
					if ($this->access('gallery', 'gallery_see', (int) $album))
					{
						$albums[] = (int) $album;
					}
				}

				return $albums ? (int) $this->db->select('COUNT(*)')->from('nf_gallery_images')->where('gallery_id', $albums)->row() : 0;
		}

		return NULL;
	}

	private function _libelle(string $nom, int $valeur): string
	{
		switch ($nom)
		{
			case 'membres':     return (string) $this->lang('membre|membres', $valeur);
			case 'discussions': return (string) $this->lang('discussion|discussions', $valeur);
			case 'messages':    return (string) $this->lang('message|messages', $valeur);
			case 'actualites':  return (string) $this->lang('actualité|actualités', $valeur);
			case 'rendez_vous': return (string) $this->lang('rendez-vous à venir|rendez-vous à venir', $valeur);
			case 'photos':      return (string) $this->lang('photo|photos', $valeur);
		}

		return '';
	}
}
