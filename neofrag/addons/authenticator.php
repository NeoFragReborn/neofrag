<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Addons;

use NF\NeoFrag\Loadables\Addon;

abstract class Authenticator extends Addon
{
	static public function __class($name)
	{
		return 'Addons\authenticator_'.$name.'\authenticator_'.$name;
	}

	static public function url()
	{
		// site_origin() : le redirect_uri OAuth ne doit pas dériver d'un Host forgeable.
		return site_origin().NeoFrag()->url->base.'user/auth';
	}

	protected function __info()
	{
		return [];
	}

	public $_keys = ['id', 'secret'];

	abstract public function data(&$params = []);

	public function is_setup()
	{
		$settings = $this->__settings->{$this->url->production() ? 'prod' : 'dev'};

		foreach ($this->_keys as $key)
		{
			if (empty($settings->$key))
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	public function config()
	{
		$settings = $this->__settings->{$this->url->production() ? 'prod' : 'dev'};

		return [
			'applicationId'     => $settings->id,
			'applicationSecret' => $settings->secret
		];
	}

	public function __toString()
	{
		$button = $this	->button()
						->tooltip($this->info()->title)
						->icon($this->info()->icon)
						->style('background-color', $this->info()->color)
						->url('user/auth/'.url_title($this->info()->name));

		return '<div class="btn-auth">'.$button.'</div>';
	}

	public function _params()
	{
		return [
			'callback' => $this->adresse_de_retour()
		];
	}

	/**
	 * L'adresse où le service renvoie le membre : celle que l'écran de réglage demande de déclarer chez lui
	 * (`callback`), et celle que la connexion lui envoie. Elle ne portait pas le nom du service à l'envoi
	 * (`user/auth`) : le service refusait une adresse non déclarée, et son retour serait tombé sur la liste
	 * « Mes comptes liés » sans être lu (2026-10-09).
	 */
	public function adresse_de_retour(): string
	{
		return static::url().'/'.url_title($this->info()->name);
	}
}
