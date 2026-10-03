<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries;

use NF\NeoFrag\Library;

class Error extends Library
{
	public function __invoke()
	{
		// Une ancienne adresse : avant de répondre 404, le site cherche une redirection —
		// une page renommée, l'adresse d'un ancien site. Une page qui existe n'arrive jamais ici.
		if (($cible = nf_redirection()) !== NULL)
		{
			NeoFrag()->url->redirect_http($cible, 301);
			exit;
		}

		throw NeoFrag()->___load('', 'exception', [function(){
			header('HTTP/1.0 404 Not Found');
			// Le titre de l'onglet le dit aussi, plutôt que le nom du module qui n'a rien trouvé.
			$this->output->data->set('module', 'title', (string) $this->lang('Page introuvable'));
			return $this->view('errors/unfound');
		}]);
	}

	public function __call($name, $args)
	{
		if ($name == 'throw')
		{
			throw NeoFrag()->___load('', 'exception', $args);
		}

		return parent::__call($name, $args);
	}

	/**
	 * Une erreur interne (500) : la page a planté. Le visiteur lit qu'un problème est survenu, avec la
	 * référence que porte aussi la ligne du journal — et non plus « Page introuvable », qui lui faisait
	 * croire à une mauvaise adresse (relevé le 2026-10-02).
	 */
	public function interne(string $reference)
	{
		throw NeoFrag()->___load('', 'exception', [function() use ($reference){
			header('HTTP/1.0 500 Internal Server Error');
			header('X-NF-Reference: '.$reference);   // lue par NF.ajax, qui la montre dans son message
			$this->output->data->set('module', 'title', (string) $this->lang('Une erreur est survenue'));
			return $this->view('errors/internal', ['reference' => $reference]);
		}]);
	}

	public function unauthorized()
	{
		throw NeoFrag()->___load('', 'exception', [function(){
			header('HTTP/1.0 403 Forbidden');
			$this->output->data->set('module', 'title', (string) $this->lang('Accès non autorisé'));
			return $this->view('errors/unauthorized');
		}]);
	}

	public function unconnected()
	{
		if (!$this->user())
		{
			throw NeoFrag()->___load('', 'exception', [function(){
				header('HTTP/1.0 401 Unauthorized');
				$this->session->append('modals', 'ajax/user/auth');
				redirect();
			}]);
		}
	}
}
