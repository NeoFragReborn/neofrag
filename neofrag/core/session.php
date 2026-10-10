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

				// Les sessions « Se souvenir de moi » ne s'effaçaient jamais. Leur cookie expire au bout d'un an :
				// au-delà d'un an sans activité, la ligne ne servirait plus à personne (2026-10-08).
				$this->db	->where('remember', TRUE)
							->where('last_activity <', $this->date()->sub('1 year')->sql())
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

			// Le ménage du jour : une fois par jour, à la première visite.
			if (($jour = date('Y-m-d')) !== (string) $this->config->nf_menage_jour)
			{
				$this->config('nf_menage_jour', $jour);
				$this->_menage_du_jour();
			}

			$cookie_name = $this->nom_du_cookie();

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

				// L'adresse du client : celle de la connexion, ou celle qu'un relais de confiance transmet
				// (Rate_Limit::client_ip()). X-Real-IP était lu tel quel : n'importe quel navigateur l'envoie, et
				// l'historique des connexions montrait l'adresse de son choix (audit du 2026-10-09).
				$this->set('session', [
					'date'       => NeoFrag()->date(),
					'ip_address' => $ip_address = (string) \NF\NeoFrag\Libraries\Rate_Limit::client_ip(),
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
		$cookie_name = $this->nom_du_cookie();

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
					// Jusqu'à la fermeture du navigateur ; un an pour « Se souvenir de moi » (2026-10-08). Le
					// cookie vivait un an pour tout le monde, visiteurs compris, alors que le site oubliait la
					// session au bout de `nf_cookie_expire` sans activité : il durait onze mois de trop.
					setcookie($cookie_name, $this->_session->id, $this->_attributs_du_cookie($this->_session->remember ? strtotime('+1 year') : 0));

					return;
				}
			}
		}

		// Dix tentatives infructueuses : la session est inécrivable. On l'abandonne franchement —
		// cookie effacé, modèle vide — plutôt que de boucler. La requête en cours se termine sans
		// session, et la suivante en ouvre une neuve. Un refus explicite vaut mieux qu'une page qui
		// ne répond jamais.
		trigger_error('Session inécrivable après dix tentatives : session abandonnée.', E_USER_WARNING);

		setcookie($cookie_name, '', $this->_attributs_du_cookie(1));

		$this->_session = $this->model2('session');
		$this->_data    = $this->_session->data->__extends($this);
	}

	/**
	 * Les durées de conservation (2026-10-08). Ces tables gardaient tout, pour toujours : le journal d'audit
	 * (adresse IP et navigateur de chaque action d'administration et de chaque connexion), les compteurs
	 * anti-abus (une adresse IP dans leur clé), la file d'envoi des lettres d'information (une adresse
	 * électronique par destinataire et par lettre), les inscriptions jamais confirmées, l'adresse IP de qui
	 * signale un contenu et la copie du contenu signalé. Le RGPD veut une durée limitée à ce que la finalité
	 * exige (art. 5.1.e) ; la politique de confidentialité du site les annonce, elles doivent donc être vraies.
	 *
	 *   - journal d'audit : un an ;
	 *   - compteurs anti-abus : un jour après la dernière tentative, une fois le blocage levé ;
	 *   - traces du consentement : treize mois (un choix vaut six mois ; la trace le survit, pour la preuve) ;
	 *   - file d'envoi des lettres : quatre-vingt-dix jours après l'envoi ;
	 *   - inscription à la lettre jamais confirmée : trente jours ;
	 *   - nouvelle adresse e-mail jamais confirmée : deux jours, la vie de son lien (User::ADRESSE_DUREE) ;
	 *   - signalement traité depuis un an : l'adresse IP de qui l'a fait, la copie du contenu et des
	 *     pièces jointes s'en vont ; le signalement, lui, reste (qui a décidé quoi).
	 *   - lien Discord d'un élément supprimé : sept jours après la suppression.
	 *   - sanction de modération : trois ans après sa fin (échue, levée ; un avertissement, après avoir été donné).
	 *
	 * Une table absente (son module n'est pas installé) est passée en silence.
	 */
	private function _menage_du_jour(): void
	{
		$an = $this->date()->sub('1 year')->sql();

		$purges = [
			fn() => $this->db->where('created_at <', $an)->delete('nf_audit_log'),
			fn() => $this->db	->where('first_attempt_at <', $this->date()->sub('1 day')->sql())
								->where('(locked_until IS NULL OR locked_until < NOW())')
								->delete('nf_rate_limit'),
			fn() => $this->db->where('created_at <', $this->date()->sub('395 days')->sql())->delete('nf_cookie_consent'),
			fn() => $this->db	->where('status <>', 'pending')
								->where('created_at <', $this->date()->sub('90 days')->sql())
								->delete('nf_newsletter_queue'),
			fn() => $this->db	->where('confirmed', 0)
								->where('created_at <', $this->date()->sub('30 days')->sql())
								->delete('nf_newsletter_subscribers'),
			fn() => $this->db->where('created_at <', $this->date()->sub('2 days')->sql())->delete('nf_user_email_change'),
			// Le relevé des pages introuvables (nf_noter_introuvable()) : une adresse qu'on ne demande plus depuis 90 jours
			// s'oublie, et pas plus de 2 000 en tout — un robot qui en essaie des milliers ne remplit pas la base.
			fn() => $this->db->where('derniere_fois <', $this->date()->sub('90 days')->sql())->delete('nf_pages_introuvables'),
			function() {
				$limite = $this->db->select('derniere_fois')->from('nf_pages_introuvables')->order_by('derniere_fois DESC')->limit(1999, 1)->get();

				if ($limite)
				{
					$this->db->where('derniere_fois <', (string) current($limite))->delete('nf_pages_introuvables');
				}
			},
			// Les sanctions de modération (ligne 0.51, 2026-10-10) : effacées trois ans après leur fin — échue, levée, donnée
			// pour un avertissement, jamais approuvée pour une sanction qui attendait l'accord d'un supérieur. Une sanction
			// permanente qui court reste. Trois ans suffisent à juger une récidive : l'escalade ne regarde que trente jours.
			function() {
				$avant = $this->db->escape_string($this->date()->sub('3 years')->sql());

				$this->db->execute("DELETE FROM nf_sanctions WHERE (revoked_at IS NOT NULL AND revoked_at < '{$avant}')"
					." OR (revoked_at IS NULL AND expires_at IS NOT NULL AND expires_at < '{$avant}')"
					." OR (type = 'warning' AND expires_at IS NULL AND created_at < '{$avant}')"
					." OR (requires_approval = 1 AND approved_at IS NULL AND revoked_at IS NULL AND created_at < '{$avant}')");
			},
			// Les liens Discord d'un élément supprimé (m21, 2026-10-10) : un ticket, un sujet, un message ou un commentaire
			// effacé laissait son lien. Le lien qui a perdu son élément est noté ; il part sept jours plus tard — le bot, qui le
			// lit pour effacer le fil sur Discord, a eu le temps de le faire. Un élément restauré entre-temps garde le sien.
			function() {
				// La colonne vient avec la migration du module (2026_10_10_liens_orphelins) : rien avant elle.
				if (!$this->db->table_exists('nf_discord_links') || !array_key_exists('orphelin_depuis', (array) $this->db->table_columns('nf_discord_links')))
				{
					return;
				}

				foreach (['ticket' => ['nf_bug_tickets', 'id'], 'comment' => ['nf_bug_comments', 'id'], 'topic' => ['nf_forum_topics', 'topic_id'], 'message' => ['nf_forum_messages', 'message_id']] as $type => [$table, $cle])
				{
					if ($this->db->table_exists($table))
					{
						$this->db->execute("UPDATE nf_discord_links l LEFT JOIN `{$table}` e ON e.`{$cle}` = l.site_id SET l.orphelin_depuis = IF(e.`{$cle}` IS NULL, COALESCE(l.orphelin_depuis, NOW()), NULL) WHERE l.type = '{$type}'");
					}
				}

				$this->db->where('orphelin_depuis <', $this->date()->sub('7 days')->sql())->delete('nf_discord_links');
			},
			// Les comptes restés sans visite : prévenus un mois avant, puis effacés (User::menage_des_comptes_inactifs()). En FIN
			// de requête : les e-mails se rédigent dans la langue du membre, et la langue du site n'est pas encore chargée ici.
			// Sous PHP-FPM, la réponse part d'abord : le visiteur n'attend pas les envois.
			fn() => register_shutdown_function(function () {
				if (function_exists('fastcgi_finish_request'))
				{
					fastcgi_finish_request();
				}

				try
				{
					if (($membres = $this->module('user')) instanceof \NF\Modules\User\User)
					{
						$membres->menage_des_comptes_inactifs();
					}
				}
				catch (\Throwable $e)
				{
					error_log('[ménage du jour] '.$e->getMessage().' — '.$e->getFile().':'.$e->getLine());
				}
			}),
			function() use ($an) {
				$anciens = array_map('intval', $this->db	->select('id')
															->from('nf_reports')
															->where('status <>', 'pending')
															->where('handled_at <', $an)
															->where('(reporter_ip <> \'\' OR content_snapshot IS NOT NULL)')
															->get());

				foreach ($anciens as $id)
				{
					foreach (glob(NEOFRAG_CMS.'/backups/moderation/reports/'.$id.'/*') ?: [] as $fichier)
					{
						@unlink($fichier);
					}

					@rmdir(NEOFRAG_CMS.'/backups/moderation/reports/'.$id);

					$this->db->where('report_id', $id)->delete('nf_reports_attachments_snapshot');
					$this->db->where('id', $id)->update('nf_reports', ['reporter_ip' => '', 'content_snapshot' => NULL]);
				}
			},
		];

		foreach ($purges as $purge)
		{
			try
			{
				$purge();
			}
			// Une purge qui échoue n'arrête pas les autres, mais se dit au journal : elle échouait sans un mot.
			catch (\Throwable $e)
			{
				error_log('[ménage du jour] '.$e->getMessage().' — '.$e->getFile().':'.$e->getLine());
			}
		}
	}

	/**
	 * Le nom du cookie de session. En HTTPS, pour un site à la racine de son domaine : `__Host-` devant (audit du
	 * 2026-10-09). Le navigateur n'accepte un tel cookie que posé par CE site, en HTTPS, pour tout le site, et ne l'envoie
	 * qu'à lui : un sous-domaine (une démo, un webmail) ne peut ni le recevoir ni en poser un à sa place pour imposer une
	 * session. Ailleurs — en HTTP, ou pour un site dans un sous-dossier, que ce préfixe n'admet pas —, le nom d'avant.
	 */
	public function nom_du_cookie(): string
	{
		$nom = (string) $this->config->nf_cookie_name;

		if (!$this->url->https)
		{
			return $nom;
		}

		return $this->url->base === '/' ? '__Host-'.$nom : $nom.'_https';
	}

	/** Les attributs du cookie de session ; un cookie `__Host-` n'a jamais de domaine (le navigateur le refuserait). */
	private function _attributs_du_cookie(int $expire): array
	{
		return [
			'expires'  => $expire,
			'path'     => $this->url->base,
			'domain'   => str_starts_with($this->nom_du_cookie(), '__Host-') ? '' : $this->url->domain,
			'secure'   => (bool) $this->url->https,
			'httponly' => TRUE,
			'samesite' => 'Lax'
		];
	}

	public function login($user, $remember = NULL)
	{
		$this->_session	->set('user', $user)
						->set_if($remember !== NULL, 'remember', $remember);

		// Anti-fixation de session : nouvel ID à l'élévation de privilège (le commit du nouvel
		// ID persiste aussi l'utilisateur qu'on vient de poser).
		$this->_renew_id();

		// La même adresse qu'à la création de session : jamais un en-tête que le navigateur choisit.
		$this	->model2('session_history')
				->set('user',       $user)
				->set('ip_address', $ip_address = (string) \NF\NeoFrag\Libraries\Rate_Limit::client_ip())
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
