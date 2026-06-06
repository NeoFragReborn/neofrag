<?php
/**
 * https://neofr.ag
 * Adaptateur de session NeoFrag pour SocialConnect (anciennement bundled dans lib/SocialConnect/Provider/Session/NeoFrag.php).
 * Migré dans le namespace NF lors de la composerification.
 */

namespace NF\NeoFrag\Libraries;

class Social_Connect_Session implements \SocialConnect\Provider\Session\SessionInterface
{
	private $_session;

	public function __construct($session)
	{
		$this->_session = $session;
	}

	public function get($key)
	{
		return unserialize(serialize(call_user_func_array($this->_session, ['SocialConnect', $key])), ['allowed_classes' => false]);
	}

	public function set($key, $value)
	{
		$this->_session->set('SocialConnect', $key, $value);
	}

	public function delete($key)
	{
		$this->_session->destroy('SocialConnect', $key);
	}
}
