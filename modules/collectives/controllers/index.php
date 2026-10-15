<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * Les pages d'un collectif, pour ses membres. Le chef et les administrateurs du collectif y gèrent ses membres :
 * ajouter, changer un rang, retirer — chacun ceux qui sont placés sous lui (Models\Collectives::peut_toucher()).
 *
 * couplage(notifications): facultatif — `Module::__load` rend NULL sans le module, et rien n'est envoyé.
 */

namespace NF\Modules\Collectives\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index($collectifs)
	{
		$this->title($this->lang('Mes collectifs'))->icon('fas fa-people-group');

		return $this->view('index', ['collectifs' => $collectifs, 'rangs' => $this->_libelles_des_rangs()]);
	}

	public function _collective($collectif, $rang, $gestionnaire)
	{
		$modele = $this->_modele();
		$id     = (int) $collectif['collective_id'];
		$moi    = (int) $this->user->id;

		$this	->title($collectif['name'])
				->icon('fas fa-people-group')
				->breadcrumb($this->lang('Mes collectifs'), 'collectives');

		$salon = NULL;

		if (!empty($collectif['discord_channel_id']) && ($guilde = (string) $this->config->nf_discord_guild_id) !== '')
		{
			$salon = 'https://discord.com/channels/'.rawurlencode($guilde).'/'.rawurlencode((string) $collectif['discord_channel_id']);
		}

		$membres = $modele->membres($id);

		foreach ($membres as &$m)
		{
			$m['touchable'] = $modele->peut_toucher($id, $moi, (int) $m['user_id'], $gestionnaire);
		}

		unset($m);

		return $this->view('collective', [
			'collectif' => $collectif,
			'slug'      => url_title($collectif['name']),
			'rang'      => $rang,
			'gere'      => $modele->gere($id, $moi, $gestionnaire),
			'donnables' => $modele->rangs_donnables($id, $moi, $gestionnaire),
			'rangs'     => $this->_libelles_des_rangs(),
			'membres'   => $membres,
			'forum'     => $modele->forum_de($collectif),
			'salon'     => $salon,
			'csrf'      => $this->csrf_token(),
		]);
	}

	/** Ajouter un membre, par son pseudo : le chef et les administrateurs du collectif. */
	public function _add_member($collectif, $rang, $gestionnaire)
	{
		$modele = $this->_modele();
		$id     = (int) $collectif['collective_id'];
		$retour = 'collectives/'.$id.'/'.url_title($collectif['name']);

		$this->exiger_post();
		$this->check_csrf($retour);

		$voulu = (string) ($_POST['rank'] ?? 'membre');

		if (!$modele->gere($id, (int) $this->user->id, $gestionnaire) || !in_array($voulu, $modele->rangs_donnables($id, (int) $this->user->id, $gestionnaire), TRUE))
		{
			notify($this->lang('Vous ne pouvez pas ajouter de membre à ce collectif.'), 'danger');
			redirect($retour);
		}

		$membre = $modele->membre_par_pseudo(nf_texte_brut($_POST['username'] ?? ''));

		if ($membre === NULL)
		{
			notify($this->lang('Aucun membre ne porte ce pseudo.'), 'danger');
		}
		else if (!$modele->ajouter($id, $membre, $voulu, (int) $this->user->id))
		{
			notify($this->lang('Ce membre fait déjà partie du collectif.'), 'warning');
		}
		else
		{
			if (($notifications = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['notifications'])) instanceof \NF\Modules\Notifications\Notifications)
			{
				$notifications->push($membre, 'collectives_added', (string) $this->lang('Vous faites maintenant partie du collectif « %s »', nf_texte_brut($collectif['name'])), $retour, (int) $this->user->id);
			}

			notify($this->lang('Membre ajouté.'));
		}

		redirect($retour);
	}

	public function _rank($collectif, $rang, $gestionnaire, $user_id)
	{
		$modele = $this->_modele();
		$id     = (int) $collectif['collective_id'];
		$retour = 'collectives/'.$id.'/'.url_title($collectif['name']);

		$this->exiger_post();
		$this->check_csrf($retour);

		$voulu = (string) ($_POST['rank'] ?? '');

		if (!$modele->peut_toucher($id, (int) $this->user->id, (int) $user_id, $gestionnaire) || !in_array($voulu, $modele->rangs_donnables($id, (int) $this->user->id, $gestionnaire), TRUE))
		{
			notify($this->lang('Vous ne pouvez pas changer le rang de ce membre.'), 'danger');
			redirect($retour);
		}

		$modele->changer_rang($id, (int) $user_id, $voulu);
		notify($this->lang('Rang changé.'));
		redirect($retour);
	}

	public function _remove($collectif, $rang, $gestionnaire, $user_id)
	{
		$modele = $this->_modele();
		$id     = (int) $collectif['collective_id'];
		$retour = 'collectives/'.$id.'/'.url_title($collectif['name']);

		$this->check_csrf($retour);

		if (!$modele->peut_toucher($id, (int) $this->user->id, (int) $user_id, $gestionnaire))
		{
			notify($this->lang('Vous ne pouvez pas retirer ce membre.'), 'danger');
			redirect($retour);
		}

		$modele->retirer($id, (int) $user_id);
		notify($this->lang('Membre retiré du collectif.'));
		redirect($retour);
	}

	/** Quitter le collectif — sauf son chef, qui doit d'abord être remplacé par un administrateur du site. */
	public function _leave($collectif, $rang, $gestionnaire)
	{
		$id     = (int) $collectif['collective_id'];
		$retour = 'collectives/'.$id.'/'.url_title($collectif['name']);

		$this->check_csrf($retour);

		if ($rang === NULL || $rang === 'chef')
		{
			notify($this->lang('Le chef ne quitte pas son collectif : un administrateur du site nomme d’abord son successeur.'), 'warning');
			redirect($retour);
		}

		$this->_modele()->retirer($id, (int) $this->user->id);
		notify($this->lang('Vous avez quitté le collectif.'));
		redirect('collectives');
	}

	/** @return array<string, string> */
	private function _libelles_des_rangs(): array
	{
		return [
			'chef'       => (string) $this->lang('Chef'),
			'admin'      => (string) $this->lang('Administrateur'),
			'moderateur' => (string) $this->lang('Modérateur'),
			'membre'     => (string) $this->lang('Membre'),
		];
	}

	private function _modele(): \NF\Modules\Collectives\Models\Collectives
	{
		$modele = $this->model();

		if (!$modele instanceof \NF\Modules\Collectives\Models\Collectives)
		{
			throw new \LogicException('modèle des collectifs introuvable');
		}

		return $modele;
	}
}
