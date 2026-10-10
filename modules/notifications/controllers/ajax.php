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

	public function _subscribe($type, $id)
	{
		$this->exiger_post();

		header('Content-Type: application/json');
		$following = $this->module->toggle_subscription($type, $id);
		echo json_encode(['ok' => TRUE, 'following' => $following]);
		exit;
	}
}
