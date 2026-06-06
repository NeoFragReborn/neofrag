<?php
/**
 * https://neofr.ag
 * Audit log — trace les actions sensibles (login, changement mdp, 2FA, suppression compte, modifs admin).
 */

namespace NF\NeoFrag\Libraries;

use NF\NeoFrag\Library;

class Audit_Log extends Library
{
	/**
	 * Enregistre une action.
	 *
	 * @param string $action Identifiant de l'action (ex: "login.success", "user.password_changed", "totp.enabled")
	 * @param array $opts Options : user_id, username, target_type, target_id, details (string|array), success (bool, défaut TRUE)
	 */
	public function log($action, array $opts = [])
	{
		$user = NeoFrag()->user();

		$details = $opts['details'] ?? NULL;
		if (is_array($details))
		{
			$details = json_encode($details, JSON_UNESCAPED_UNICODE);
		}

		$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : NULL;

		NeoFrag()->db->insert('nf_audit_log', [
			'user_id'     => $opts['user_id']     ?? ($user ? $user->id : NULL),
			'username'    => $opts['username']    ?? ($user ? $user->username : NULL),
			'action'      => $action,
			'target_type' => $opts['target_type'] ?? NULL,
			'target_id'   => isset($opts['target_id']) ? (string)$opts['target_id'] : NULL,
			'details'     => $details,
			'ip_address'  => Rate_Limit::client_ip(),
			'user_agent'  => $user_agent,
			'success'     => isset($opts['success']) ? (int)(bool)$opts['success'] : 1
		]);
	}

	/**
	 * Cleanup logs anciens (à appeler depuis un cron).
	 */
	public function gc($keep_days = 365)
	{
		NeoFrag()->db->execute('DELETE FROM nf_audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL '.(int)$keep_days.' DAY)');
	}
}
