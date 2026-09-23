<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\NeoFrag\Traits;

/**
 * Back-end commun aux contenus « publiables programmés » (news, articles) : parution effective
 * (announce), passage cron (publish_scheduled) et incrément des vues. Extrait de la duplication
 * historique entre les deux modules — un seul endroit à corriger (le bug de date 2038 avait dû être
 * corrigé deux fois). La PRÉSENTATION reste propre à chaque module (news = feed court votable ;
 * articles = blog long avec sommaire) : ce trait ne touche QUE la logique de parution.
 *
 * Le modèle qui l'utilise fournit sa config via publishable_config().
 */
trait Publishable_Content
{
	/**
	 * @return array{table:string, id:string, lang_table:string, type:string, url_prefix:string, notif_message:string}
	 *   - table         : table principale (ex. 'nf_news')
	 *   - id            : colonne clé primaire (ex. 'news_id')
	 *   - lang_table    : table de traduction (ex. 'nf_news_lang')
	 *   - type          : type logique pour events/gamification/notifications (ex. 'news')
	 *   - url_prefix    : segment d'URL public (ex. 'news' / 'articles')
	 *   - notif_message : gabarit de notification (%s = titre), résolu via lang() du module
	 */
	abstract protected function publishable_config(): array;

	public function increment_views($id)
	{
		$c = $this->publishable_config();
		$this->db->execute('UPDATE '.$c['table'].' SET views = views + 1 WHERE '.$c['id'].' = '.(int)$id);
	}

	/**
	 * Parution effective d'un contenu : émet event + webhook + gamification + notifications UNE SEULE
	 * FOIS, au moment réel de parution. Idempotent via announced_at. Appelée à l'enregistrement
	 * (parution immédiate) ET par l'endpoint de parution (cron) pour le contenu programmé échu.
	 *
	 * @return bool TRUE si l'annonce vient d'être émise.
	 */
	public function announce($id)
	{
		$c  = $this->publishable_config();
		$id = (int)$id;

		$row = $this->db	->select('t.user_id', 't.category_id', 't.date', 't.published', 't.announced_at', 'tl.title')
							->from($c['table'].' t')
							->join($c['lang_table'].' tl', 't.'.$c['id'].' = tl.'.$c['id'])
							->where('t.'.$c['id'], $id)
							->where('tl.lang', $this->config->lang->info()->name)
							->where('t.deleted_at', NULL)
							->row();

		if (!$row || $row['published'] != '1' || !empty($row['announced_at']) || strtotime($row['date']) > time())
		{
			return FALSE;
		}

		// Marque AVANT d'émettre : empêche toute double émission (ré-entrance / passages cron concurrents).
		$this->db->where($c['id'], $id)->update($c['table'], ['announced_at' => date('Y-m-d H:i:s')]);

		$title = (string)$row['title'];
		$url   = $c['url_prefix'].'/'.$id.'/'.url_title($title);
		$owner = (int)$row['user_id'];

		$this->events->fire($c['type'].'.published', [$c['id'] => $id, 'title' => $title]);

		if ($gam = $this->module('gamification'))
		{
			// Barème 'news' = « news / article publié » (clé partagée, cf. admin gamification).
			if ($gam_owner = $gam->content_owner($c['type'], $id))
			{
				$gam->earn($gam_owner, 'news');
				$gam->recompute($gam_owner);
			}
		}

		if ($wh = $this->module('webhooks'))
		{
			$wh->trigger($c['type'].'.published', [$c['id'] => $id, 'title' => $title, 'url' => url($url)]);
		}

		if ($notifications = $this->module('notifications'))
		{
			foreach ($notifications->subscribers($c['type'].'-category', (int)$row['category_id'], $owner) as $uid)
			{
				$notifications->push($uid, $c['type'], $this->lang($c['notif_message'], $title), $url, $owner);
			}
		}

		return TRUE;
	}

	/** Parution des contenus programmés arrivés à échéance (appelée par l'endpoint cron). @return int annoncés */
	public function publish_scheduled()
	{
		$c     = $this->publishable_config();
		$count = 0;

		foreach ($this->db	->select($c['id'])
							->from($c['table'])
							->where('published', '1')
							->where('announced_at', NULL)
							->where('date <=', date('Y-m-d H:i:s'))
							->where('deleted_at', NULL)
							->get() as $id)
		{
			if ($this->announce($id))
			{
				$count++;
			}
		}

		return $count;
	}
}
