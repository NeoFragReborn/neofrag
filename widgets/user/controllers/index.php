<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\User\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($config = [])
	{
		if ($this->user())
		{
			$this->css('user');

			// R1.9 — En preview, le footer affiche "Quitter le preview" au lieu de "Se déconnecter"
			// (sinon l'admin réel se déco par accident)
			$preview_active = method_exists($this->access, 'get_preview_target') && $this->access->get_preview_target() !== NULL;
			$footer = $preview_active
				? '<a href="'.url('admin/access/preview/exit').'" class="text-warning"><strong>'.icon('fas fa-eye-slash').' '.$this->lang('Quitter le preview').'</strong></a>'
				: '<a href="'.url('user/logout').'">'.icon('fas fa-times').' '.$this->lang('Se déconnecter').'</a>';

			return $this->panel()
						->heading($this->lang('Espace membre'))
						->body($this->view('logged', [
							'username' => $this->user->username
						]), FALSE)
						->footer($footer);
		}
		else
		{
			if ($authenticators = NeoFrag()->model2('addon')->get('authenticator')->filter('is_enabled')->__toArray())
			{
				$this	->css('auth')
						->css('auth_mini');
			}

			return $this->module('user')
						->form2('login')
						->button_prepend_if($this->config->nf_registration_status, $this->button()
																						->title($this->lang('Créer un compte'))
																						->color('secondary')
																						->modal_ajax('ajax/user/register')
						)
						->button_prepend($this	->button()
												->title($this->lang('Mot de passe oublié ?'))
												->color('link')
												->modal_ajax('ajax/user/lost-password')
						)
						->panel()
						->title($this->lang('Espace membre').($authenticators ? '<span class="nf-login-auth">'.implode($authenticators).'</span>' : ''));
		}
	}

	public function index_mini($config = [])
	{
		return $this->view('index_mini', $config);
	}

	public function messages_inbox($config = [])
	{
		if (!$this->user())
		{
			return;
		}

		// R1.9 — En preview, on ne liste pas les MP : ce serait celles de l'admin (incohérent avec "tu vois comme bob")
		// et exposer un compteur d'unread bizarre. Le widget est donc masqué côté preview.
		if (method_exists($this->access, 'get_preview_target') && $this->access->get_preview_target() !== NULL)
		{
			return;
		}

		// Migration MP → Talks unifié : on liste les talks privés de type direct/group
		$conversations = $this->module('talks')->model()->get_my_conversations($this->user->id);

		// Filtre uniquement direct + group (pas les salons publics dans le widget MP)
		$private = array_values(array_filter($conversations, function($c){
			return in_array($c['type'], ['direct', 'group'], TRUE);
		}));

		// Limite 5 dans le widget
		$private = array_slice($private, 0, 5);

		// Adapte au format attendu par la view existante (messages_inbox.tpl.php)
		$messages = array_map(function($c){
			return [
				'message_id' => 'talk-'.(int)$c['talk_id'],
				'title'      => $c['name'],
				'unread'     => !empty($c['unread_count']),
				'date'       => $c['last_message_date'] ? strtotime($c['last_message_date']) : strtotime($c['updated_at'] ?? 'now'),
				'user_id'    => (int)($c['creator_id'] ?? 0),
				'username'   => $c['name'],
				'_talk_url'  => 'talks/'.(int)$c['talk_id'].'/'.url_title($c['name'])
			];
		}, $private);

		return $this->panel()
					->heading($this->lang('Messagerie'), 'fas fa-envelope')
					->body($this->view('messages_inbox', ['messages' => $messages]), FALSE)
					->footer('<a class="btn btn-secondary" href="'.url('talks?type=private').'">'.icon('fas fa-inbox').' '.$this->lang('Boîte de réception').'</a> <a class="btn btn-primary" href="'.url('talks/new?type=direct').'">'.icon('fas fa-edit').' '.$this->lang('Rédiger').'</a>');
	}
}
