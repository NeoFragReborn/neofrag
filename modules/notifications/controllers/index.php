<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * L'ancienne adresse de la liste des notifications du membre : la page vit dans l'espace membre depuis le chantier A
 * (étape A4), sous user/notifications, avec ses préférences. Un lien gardé ailleurs (un favori, un courriel) y mène.
 */

namespace NF\Modules\Notifications\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index($page = '')
	{
		redirect('user/notifications'.((string) $page !== '' ? '/'.$page : ''));
	}
}
