<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

function notify($message, $type = 'success'): void
{
	NeoFrag()->session->append('notifications', [
		'message' => (string)$message,
		'type'    => is_color($type) ? $type : 'success'
	]);
}

function notifications(): void
{
	if ($notifications = NeoFrag()->session('notifications'))
	{
		foreach ($notifications as $notification)
		{
			// json_encode, et non addcslashes : un message qui finissait par une barre oblique, qui
			// contenait un saut de ligne ou `</script>` cassait le script de la page — et un message
			// peut porter un nom saisi par un membre. Les drapeaux HEX gardent la chaîne inerte dans
			// un <script> en ligne.
			NeoFrag()->js_load('notify('.json_encode($notification['message'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE).', '.json_encode($notification['type']).');');
		}

		NeoFrag()->session->destroy('notifications');
	}
}
