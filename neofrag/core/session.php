<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Core;

use NF\NeoFrag\Core;

/**
 * Les données de session passent par __call vers leur tableau (NF\NeoFrag\Libraries\Array_) : l'analyse
 * statique ne voyait pas ses méthodes.
 *
 * @method mixed destroy(mixed ...$args)
 */
class Session extends Core
{
	protected $_session;
	protected $_data;

	public function __construct($config = [])
	{
		/*
			TODO 0.2
			 - history_back
			 - session_history => user logged
			 - asset / ajax
		*/

		/*
		 * L'API (`api/…`) s'authentifie par clé, à chaque requête : elle n'a pas de
		 * session. Sans cette exception, chaque appel en créait une, avec son cookie — un bot à
		 * 120 requêtes par minute aurait laissé quelque 170 000 sessions par jour (mesuré le 2026-10-01).
		 */
		$api = (bool) preg_match('_^([a-z]{2}/)?api/_', (string) $this->url->request);

		if ($this->url->cli || is_crawler() || $api || (isset($config['avoid']) && is_a($config['avoid'], 'closure') && $config['avoid']()))
		{
			$this->_session = $this->model2('session');
			$this->_data    = $this->array;
		}
		else
		{
			if ($this->config->nf_cookie_expire)
			{
				$expiration_date = $this->date()->sub($this->config->nf_cookie_expire);

				$this->db	->where('remember', FALSE)
							->where('last_activity <', $expiration_date->sql())
							->delete('nf_session');

				/**
				 * Historique des connexions : purge au-delà de la durée de conservation.
				 *
				 * `nf_session_history` conserve IP, nom d'hôte, référent, agent et mode
				 * d'authentification à chaque connexion. Rien ne l'effaçait : ni le cœur, ni aucun
				 * module. Une table de données personnelles qui ne se vide jamais est une dette de
				 * conformité, même quand elle ne pèse que cinq lignes — ce qui était le cas en
				 * production le 2026-09-20.
				 *
				 * Défaut : 395 jours, soit treize mois. C'est la durée couramment retenue pour des
				 * journaux de connexion, et elle laisse une comparaison d'une année sur l'autre.
				 * `0` désactive la purge, pour l'exploitant que son propre cadre oblige à garder plus.
				 *
				 * Posée ici, dans le même test que la purge des sessions, pour deux raisons : le
				 * produit n'a pas de tâche planifiée obligatoire, et un site sans visite n'a rien à
				 * purger de toute façon.
				 */
				if ($retention = (int) $this->config->nf_session_history_days)
				{
					$this->db	->where('date <', $this->date()->sub($retention.' days')->sql())
								->delete('nf_session_history');
				}
			}

			$cookie_name = $this->config->nf_cookie_name;

			if ($this->url->https)
			{
				$cookie_name .= '_https';
			}

			$this->_session = $this->model2('session', isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : NULL);

			$this->_data = $this->_session->data->__extends($this);

			if ($this->_session())
			{
				// Anti-détournement de session : si le user-agent diffère fortement de celui
				// enregistré à la création (cookie probablement rejoué depuis un autre appareil),
				// on déconnecte le membre. Tolérant aux mises à jour mineures du navigateur.
				if (($logged = $this->_session->user) && $logged() && $this->_fingerprint_changed())
				{
					$this->_session->set('user', NULL)->update();
				}

				if (isset($expiration_date) && $this->_session->last_activity->timestamp() < $expiration_date->timestamp())
				{
					$this->_renew_id();
				}

				$this->_session->set('last_activity', NeoFrag()->date())->update();
			}
			else
			{
				$this->_renew_id();

				// X-Real-IP est un header CLIENT (forgeable hors reverse-proxy de confiance) :
				// validé comme IP, sinon repli sur REMOTE_ADDR — il est stocké puis affiché
				// dans l'historique de sessions admin (XSS stocké si brut).
				$real_ip = isset($_SERVER['HTTP_X_REAL_IP']) ? filter_var($_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP) : FALSE;

				$this->set('session', [
					'date'       => NeoFrag()->date(),
					'ip_address' => $ip_address = $real_ip !== FALSE ? $real_ip : $_SERVER['REMOTE_ADDR'],
					'host_name'  => utf8_string(gethostbyaddr($ip_address)),
					'referer'    => isset($_SERVER['HTTP_REFERER'])                 ? utf8_htmlentities($_SERVER['HTTP_REFERER'])    : '',
					'user_agent' => isset($_SERVER['HTTP_USER_AGENT'])              ? utf8_htmlentities($_SERVER['HTTP_USER_AGENT']) : ''
				]);
			}

			statistics('nf_sessions_max_simultaneous', $this->db->select('COUNT(DISTINCT IFNULL(user_id, id))')->from('nf_session')->where('last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)')->row(), function($a, $b){ return $a > $b; });

			$this->on('output_loaded', function(){
				$this->_session->set('data', $this->_data)->update();
			});
		}

		NeoFrag()->user = $user = $this->_session->user;

		if ($user())
		{
			$user->set('last_activity_date', NeoFrag()->date())->update();

			// Le fuseau horaire choisi dans son profil, pour l'affichage des dates (cf. nf_fuseau(),
			// helpers/time.php). Ici et non dans le module user : son __init() ne s'exécute que sur
			// ses propres pages, et le choix du membre restait sans effet partout ailleurs.
			if (($profil = $user->profile()) && $profil() && (string) $profil->timezone !== '')
			{
				nf_fuseau_membre((string) $profil->timezone, TRUE);
			}
		}

		$this	->trigger('session_init', $this)
				->debug->bar('session', function(){
					return $this->_data->__toArray();
				});
	}

	/**
	 * Empreinte de session : le user-agent courant a-t-il trop changé depuis la création ?
	 * On se limite au user-agent (l'IP est trop volatile — wifi/4G, IP dynamiques — et
	 * déconnecterait des membres légitimes). Tolérant : une mise à jour de navigateur ne
	 * change que le numéro de version (faible distance), un autre appareil change tout.
	 */
	private function _fingerprint_changed()
	{
		$stored  = (string)$this('session', 'user_agent');
		$current = isset($_SERVER['HTTP_USER_AGENT']) ? utf8_htmlentities($_SERVER['HTTP_USER_AGENT']) : '';

		// Empreinte ou UA absent (vieille session, client exotique) : on ne peut pas conclure.
		if ($stored === '' || $current === '' || $stored === $current)
		{
			return FALSE;
		}

		// levenshtein() est plafonné à 255 caractères (renvoie -1 au-delà).
		return levenshtein(substr($stored, 0, 255), substr($current, 0, 255)) > 25;
	}

	public function __invoke()
	{
		return call_user_func_array([$this->_data, 'get'], func_get_args());
	}

	public function __get($name)
	{
		if (isset($this->_session->$name))
		{
			return $this->_session->$name;
		}

		return parent::__get($name);
	}

	public function __call($name, $args)
	{
		if ($this->_data && !isset($this->$name))
		{
			return call_user_func_array([$this->_data, $name], $args);
		}
		else
		{
			return parent::__call($name, $args);
		}
	}

	// Régénère l'ID de session (+ cookie). Appelé à la création/expiration et surtout à
	// l'élévation de privilège (login). Sur une session existante, l'UPDATE renomme l'ID en
	// place (WHERE = ancien ID, SET = nouveau) → l'ID fixé avant le login n'existe plus en
	// base : neutralise la fixation de session.
	//
	// La boucle de tirage est BORNÉE depuis le 2026-09-16, et c'est un correctif de fond.
	//
	// Elle s'écrivait `do { nouvel ID } while (!commit());` — sans limite. Elle était pensée pour
	// rattraper une collision d'identifiant, dont la probabilité est négligeable. Mais `commit()`
	// échoue aussi pour des raisons qu'un nouvel identifiant ne corrige PAS : si la ligne ne peut
	// pas être écrite, aucun tirage ne la fera passer, et la boucle tourne jusqu'à ce que PHP tue
	// la requête au bout de `max_execution_time`.
	//
	// Le cas réel, constaté sur le site de démonstration : une session « se souvenir de moi » dont
	// le compte a été supprimé. Elle échappe au nettoyage des sessions expirées (réservé aux
	// sessions sans `remember`), déclenche donc ce renouvellement, et sa clé étrangère vers un
	// utilisateur absent fait échouer l'écriture indéfiniment. Mesuré : **60 s sans réponse**, pour
	// 0,16 s avec une session saine. Le visiteur n'a aucun moyen de s'en sortir — vider le cookie
	// ou passer en navigation privée, et c'est tout.
	private function _renew_id()
	{
		$cookie_name = $this->config->nf_cookie_name;

		if ($this->url->https)
		{
			$cookie_name .= '_https';
		}

		// Deux passes. La première couvre la collision d'identifiant. Si elle échoue, la cause la
		// plus probable est la référence à un utilisateur qui n'existe plus : on la détache et on
		// retente, ce qui rend au visiteur une session anonyme utilisable plutôt qu'une page morte.
		foreach ([FALSE, TRUE] as $detacher_utilisateur)
		{
			if ($detacher_utilisateur)
			{
				$this->_session->set('user', NULL);
			}

			for ($essai = 0; $essai < 5; $essai++)
			{
				$this->_session->set('id', unique_id());

				if ($this->_session->commit())
				{
					setcookie($cookie_name, $this->_session->id, [
						'expires'  => strtotime('+1 year'),
						'path'     => $this->url->base,
						'domain'   => $this->url->domain,
						'secure'   => (bool)$this->url->https,
						'httponly' => TRUE,
						'samesite' => 'Lax'
					]);

					return;
				}
			}
		}

		// Dix tentatives infructueuses : la session est inécrivable. On l'abandonne franchement —
		// cookie effacé, modèle vide — plutôt que de boucler. La requête en cours se termine sans
		// session, et la suivante en ouvre une neuve. Un refus explicite vaut mieux qu'une page qui
		// ne répond jamais.
		trigger_error('Session inécrivable après dix tentatives : session abandonnée.', E_USER_WARNING);

		setcookie($cookie_name, '', [
			'expires'  => 1,
			'path'     => $this->url->base,
			'domain'   => $this->url->domain,
			'secure'   => (bool)$this->url->https,
			'httponly' => TRUE,
			'samesite' => 'Lax'
		]);

		$this->_session = $this->model2('session');
		$this->_data    = $this->_session->data->__extends($this);
	}

	public function login($user, $remember = NULL)
	{
		$this->_session	->set('user', $user)
						->set_if($remember !== NULL, 'remember', $remember);

		// Anti-fixation de session : nouvel ID à l'élévation de privilège (le commit du nouvel
		// ID persiste aussi l'utilisateur qu'on vient de poser).
		$this->_renew_id();

		// Même validation qu'à la création de session : X-Real-IP est forgeable.
		$real_ip = isset($_SERVER['HTTP_X_REAL_IP']) ? filter_var($_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP) : FALSE;

		$this	->model2('session_history')
				->set('user',       $user)
				->set('ip_address', $ip_address = $real_ip !== FALSE ? $real_ip : $_SERVER['REMOTE_ADDR'])
				->set('host_name',  utf8_string(gethostbyaddr($ip_address)))
				->set('referer',    (string)$this('session', 'referer'))
				->set('user_agent', isset($_SERVER['HTTP_USER_AGENT']) ? utf8_htmlentities($_SERVER['HTTP_USER_AGENT']) : '')
				->set('auth',       $this('session', 'auth'))
				->create();

		return $this;
	}

	public function logout()
	{
		$this->_session->set('user', NULL)->update();
		return $this;
	}

	public function current_sessions()
	{
		return NeoFrag()->collection('session')
						->where('_.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)');
	}
}
