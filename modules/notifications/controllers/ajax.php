<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Endpoints AJAX : marquer une notification / toutes comme lues.
 */

namespace NF\Modules\Notifications\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Ajax extends Controller_Module
{
	public function _read($id)
	{
		// Les trois en POST seulement (notifications.js ; audit du 2026-10-09).
		$this->exiger_post();

		header('Content-Type: application/json');
		$this->module->mark_read($id);
		echo json_encode(['ok' => TRUE, 'count' => $this->module->unread_count()]);
		exit;
	}

	public function _read_all()
	{
		$this->exiger_post();

		header('Content-Type: application/json');
		$this->module->mark_all_read();
		echo json_encode(['ok' => TRUE, 'count' => 0]);
		exit;
	}

	/**
	 * Le navigateur du membre vient de s'abonner (modules/notifications/js/push.js) : il envoie l'abonnement tel que
	 * `PushSubscription.toJSON()` le donne — l'adresse et ses deux clés.
	 */
	public function _push_abonner()
	{
		$this->exiger_post();

		header('Content-Type: application/json');

		$abonnement = json_decode((string) file_get_contents('php://input'), TRUE);
		$ok         = is_array($abonnement) && $this->module->abonner((int) $this->user->id,
			(string) ($abonnement['endpoint'] ?? ''), (string) ($abonnement['keys']['p256dh'] ?? ''), (string) ($abonnement['keys']['auth'] ?? ''),
			(string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));

		echo json_encode(['ok' => $ok, 'appareils' => count($this->module->abonnements((int) $this->user->id))]);
		exit;
	}

	public function _push_desabonner()
	{
		$this->exiger_post();

		header('Content-Type: application/json');

		$abonnement = json_decode((string) file_get_contents('php://input'), TRUE);
		$this->module->desabonner((int) $this->user->id, (string) ($abonnement['endpoint'] ?? ''));

		echo json_encode(['ok' => TRUE, 'appareils' => count($this->module->abonnements((int) $this->user->id))]);
		exit;
	}

	/** « M'envoyer un essai » : un message à chacun des navigateurs du membre, tout de suite. */
	public function _push_essai()
	{
		$this->exiger_post();

		header('Content-Type: application/json');

		$codes = [];

		if ($this->module->push_actif())
		{
			foreach ($this->module->abonnements((int) $this->user->id) as $abonnement)
			{
				$codes[] = $this->module->envoyer($abonnement, $this->module->message('essai', (string) $this->lang('Les notifications arrivent bien sur cet appareil.'), 'user/notifications/preferences'));
			}
		}

		// Un abonnement que le service de push ne connaît plus (404, 410) vient d'être oublié : la page le dit, et le
		// navigateur se désabonne aussi.
		echo json_encode(['ok' => $codes !== [] && min($codes) >= 200 && max($codes) < 300, 'envois' => count($codes), 'codes' => $codes,
			'oublies' => count(array_intersect($codes, [404, 410]))]);
		exit;
	}

	public function _subscribe($type, $id)
	{
		$this->exiger_post();

		header('Content-Type: application/json');
		$following = $this->module->toggle_subscription($type, $id);
		echo json_encode(['ok' => TRUE, 'following' => $following]);
		exit;
	}
}
