<?php
/**
 * https://neofr.ag
 *
 * Endpoint de parution (cron externe). NeoFrag n'a pas d'ordonnanceur : un cron externe appelle
 * périodiquement /monitoring/cron?key=<nf_cron_key> pour faire paraître à l'heure réelle le contenu
 * programmé (news/articles arrivés à échéance → event + webhook + gamification + notifications).
 *
 *   (crontab) * /5 * * * * curl -fsS "https://<site>/monitoring/cron?key=<nf_cron_key>" >/dev/null
 *
 * Gardé par un token secret (nf_cron_key, généré à l'installation) comparé en temps constant.
 * Idempotent : un contenu n'est annoncé qu'une fois (cf. nf_news.announced_at).
 */

namespace NF\Modules\Monitoring\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function cron()
	{
		$expected = (string)$this->config->nf_cron_key;
		$key      = (string)($_GET['key'] ?? '');

		if ($expected === '' || !hash_equals($expected, $key))
		{
			$this->_respond(403, "403 Forbidden\n");
		}

		// Site de démo : recharge l'instantané install/demo.sql (anti-casse — restaure contenu +
		// membres démo + config d'affichage, sans toucher l'admin ni les secrets).
		//   (crontab) 0 * * * * curl -fsS "https://<demo>/monitoring/cron?key=<clé>&demo=1" >/dev/null
		if (!empty($_GET['demo']))
		{
			if (!nf_demo())
			{
				$this->_respond(403, "Reset démo indisponible (NEOFRAG_DEMO non actif).\n");
			}

			$file = NEOFRAG_CMS.'/install/demo.sql';

			if (!is_file($file) || ($sql = file_get_contents($file)) === FALSE)
			{
				$this->_respond(500, "install/demo.sql introuvable.\n");
			}

			$result = $this->db->import($sql);

			$this->_respond($result === TRUE ? 200 : 500, $result === TRUE ? "OK demo reset\n" : "ERREUR demo reset: ".$result."\n");
		}

		$report = [];

		foreach (['news', 'articles'] as $name)
		{
			if ($module = $this->module($name))
			{
				$report[] = $name.': '.(int)$module->model()->publish_scheduled();
			}
		}

		$this->_respond(200, "OK\n".implode("\n", $report)."\n");
	}

	private function _respond($status, $body)
	{
		if (!headers_sent())
		{
			http_response_code($status);
			header('Content-Type: text/plain; charset=utf-8');
		}

		exit($body);
	}
}
