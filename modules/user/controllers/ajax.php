<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\User\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\NeoFrag\Libraries\Editeur_Images;
use NF\NeoFrag\Libraries\Rate_Limit;

class Ajax extends Controller_Module
{
	public function _member($user)
	{
		return $user->view('profile');
	}

	/**
	 * `ajax/user/editeur-image` — une image collée ou glissée dans l'éditeur riche, envoyée par
	 * js/editeur-images.js. Rend `{"location": "/upload/editeur/AAAA/MM/<nom>.<ext>"}`, ou `{"error": "…"}`
	 * avec le statut du refus. Les règles — qui, quoi, combien, comment c'est écrit — sont dans
	 * Editeur_Images ; ici, seulement leur ordre et les effets (débit, disque, registre des fichiers).
	 *
	 * Ici et non dans un module de contenu : l'éditeur sert partout (forum, commentaires, administration,
	 * messagerie), et le module user est toujours présent.
	 */
	public function editeur_image()
	{
		if (strtolower((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'post')
		{
			header('Allow: POST');
			$this->_editeur_reponse(405, ['error' => Editeur_Images::message('absent')]);
		}

		$jeton = $_POST['_'] ?? NULL;

		// L'ordre compte : un visiteur n'apprend rien du jeton, et l'on ne crée pas de jeton pour lui.
		$refus = Editeur_Images::refus_requete((bool) $this->user(), nf_demo(), $this->user() && is_string($jeton) && hash_equals(nf_jeton_csrf(), $jeton));

		if ($refus !== NULL)
		{
			$this->_editeur_reponse(Editeur_Images::statut($refus), ['error' => Editeur_Images::message($refus)]);
		}

		// Le débit, par membre : chaque tentative compte, réussie ou non.
		$limiteur = new Rate_Limit($this);
		$cles     = [];

		foreach (Editeur_Images::DEBITS as $nom => [$nombre, $fenetre])
		{
			$cle      = 'editeur_image:'.$nom.':'.(int) $this->user->id;
			$controle = $limiteur->check($cle);

			if (!$controle['allowed'])
			{
				header('Retry-After: '.max(1, (int) $controle['retry_after']));
				$this->_editeur_reponse(429, ['error' => Editeur_Images::message('debit')]);
			}

			$cles[$cle] = [$nombre, $fenetre];
		}

		foreach ($cles as $cle => [$nombre, $fenetre])
		{
			$limiteur->hit($cle, $nombre, $fenetre, $fenetre);
		}

		$fichier = $_FILES['file'] ?? NULL;

		if (!is_array($fichier) || !is_string($fichier['tmp_name'] ?? NULL) || !is_int($fichier['error'] ?? NULL))
		{
			$this->_editeur_reponse(400, ['error' => Editeur_Images::message('absent')]);
		}

		// Un chemin temporaire qui ne vient pas d'un envoi HTTP n'est jamais lu.
		$temporaire = $fichier['error'] === UPLOAD_ERR_OK && is_uploaded_file($fichier['tmp_name']) ? $fichier['tmp_name'] : '';
		$controle   = Editeur_Images::controler($temporaire, $fichier['error']);

		if (is_string($controle))
		{
			$this->_editeur_reponse(Editeur_Images::statut($controle), ['error' => Editeur_Images::message($controle)]);
		}

		$relatif = Editeur_Images::chemin($controle['type'], time());
		$absolu  = NEOFRAG_CMS.'/'.$relatif;

		dir_create(dirname($absolu));

		if (!is_dir(dirname($absolu)) || !is_writable(dirname($absolu)))
		{
			nf_journaliser_erreur('editeur', 'dossier des images de l\'éditeur non inscriptible : '.dirname($relatif), nf_chemin_relatif(__FILE__).':'.__LINE__);
			$this->_editeur_reponse(500, ['error' => Editeur_Images::message('ecriture')]);
		}

		if (!Editeur_Images::reencoder($temporaire, $controle['type'], $absolu))
		{
			// Rien n'est écrit quand le ré-encodage échoue : une image que GD ne sait pas relire n'est pas gardée.
			$this->_editeur_reponse(422, ['error' => Editeur_Images::message('illisible')]);
		}

		// Le registre des fichiers : qui a envoyé quoi, et quand (nf_file : membre, nom d'origine, chemin, date).
		\NF\NeoFrag\Models\File::add($relatif, Editeur_Images::nom((string) ($fichier['name'] ?? ''), $controle['type']));

		$this->_editeur_reponse(200, ['location' => $this->url->base.$relatif]);
	}

	/**
	 * `ajax/user/consentement` — la trace d'un choix fait dans le bandeau ou la fenêtre « Gérer mes cookies »
	 * (js/consentement.js) : la preuve du consentement (RGPD, art. 7.1). Rien n'est lu dans la requête que le
	 * cookie du choix lui-même : une page d'un autre site ne peut donc rien faire enregistrer de faux (le
	 * cookie est SameSite=Lax, il ne part pas avec un envoi venu d'ailleurs). Ni adresse IP ni navigateur :
	 * le jeton tiré au hasard, les services acceptés, l'empreinte de ce qui était proposé, la date, et le
	 * membre s'il est connecté — son archive « Mes données » le lui rend. Six mois de choix, donc treize
	 * mois de traces au plus (le ménage, Session).
	 *
	 * Le débit est compté par visiteur — sur une empreinte de son adresse, jamais l'adresse elle-même.
	 */
	public function consentement()
	{
		if (strtolower((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'post' || !($choix = nf_consentement_lire(nf_consentement_valeur())) || $choix['jeton'] === '')
		{
			http_response_code(400);
			exit;
		}

		$limiteur = new Rate_Limit($this);
		$cle      = 'consentement:'.substr(hash_hmac('sha256', (string) Rate_Limit::client_ip(), $this->crypt->derive('consentement')), 0, 24);

		if (!$limiteur->check($cle)['allowed'] || $limiteur->hit($cle, 30, 3600, 3600)['locked'])
		{
			http_response_code(429);
			exit;
		}

		$this->db->insert('nf_cookie_consent', [
			'consent_token' => $choix['jeton'],
			'user_id'       => $this->user() ? (int) $this->user->id : NULL,
			'services'      => implode(',', $choix['services']),
			'empreinte'     => $choix['empreinte'],
		]);

		http_response_code(204);
		exit;
	}

	/**
	 * `ajax/user/relais/<signature>/<adresse>` — une image d'un autre site, que le site va chercher lui-même
	 * pour que le navigateur du visiteur n'ait pas à le faire (helpers/relais.php). L'adresse, en hexadécimal,
	 * doit porter la signature du site : seul ce que le site a lui-même affiché passe ici. L'image est gardée
	 * dans upload/relais/, d'où le serveur web la sert les fois suivantes.
	 */
	public function _relais($signature, $hexa)
	{
		$adresse = strlen((string) $hexa) % 2 === 0 && ctype_xdigit((string) $hexa) ? (string) hex2bin((string) $hexa) : '';
		$cle     = $this->crypt->derive('relais');

		if ($adresse === '' || !hash_equals(nf_relais_signature($adresse, $cle), (string) $signature) || nf_relais_distante($adresse, nf_relais_hote()) === NULL)
		{
			http_response_code(404);
			exit;
		}

		$donnees = nf_fetch_public_url($adresse, 6, NF_RELAIS_OCTETS, 'NeoFrag-Reborn/'.NEOFRAG_VERSION.' (relais d\'images ; +'.site_origin().')');
		$info    = $donnees !== NULL ? @getimagesizefromstring($donnees) : FALSE;

		if (!$info || !isset(NF_RELAIS_FORMATS[$info[2]]))
		{
			http_response_code(404);
			exit;
		}

		$dossier = NEOFRAG_CMS.'/'.NF_RELAIS_DOSSIER;
		$nom     = nf_relais_empreinte($adresse);

		if (is_dir($dossier) || @mkdir($dossier, 0775, TRUE))
		{
			// Une autre extension d'une version précédente de la même image s'en va : une seule à la fois.
			foreach (NF_RELAIS_FORMATS as $extension)
			{
				@unlink($dossier.'/'.$nom.'.'.$extension);
			}

			$provisoire = $dossier.'/.'.$nom.'.'.bin2hex(random_bytes(4));

			if (@file_put_contents($provisoire, $donnees) !== FALSE)
			{
				@rename($provisoire, $dossier.'/'.$nom.'.'.NF_RELAIS_FORMATS[$info[2]]);
			}

			// Le ménage, de temps en temps. Une image encore affichée est reprise chaque semaine au plus (dix minutes
			// pour un aperçu de direct) : un fichier de plus de trente jours n'est plus affiché nulle part.
			if (random_int(1, 100) === 1)
			{
				foreach (glob($dossier.'/*') ?: [] as $ancien)
				{
					if (filemtime($ancien) < time() - 2592000)
					{
						@unlink($ancien);
					}
				}
			}
		}

		$this->_image($donnees, $info['mime'], 600);
	}

	/**
	 * `ajax/user/tuile/<zoom>/<x>/<y>` — une tuile de la carte des lieux (js/places.js), que le site va chercher
	 * lui-même chez OpenStreetMap : le navigateur du visiteur ne parle qu'au site. Gardée sept jours, comme le
	 * demandent les règles d'usage des tuiles d'OpenStreetMap (operations.osmfoundation.org/policies/tiles),
	 * qui exigent aussi un agent qui nomme l'application. L'hôte est fixe et les trois nombres vérifiés :
	 * personne ne fait télécharger au serveur autre chose qu'une tuile.
	 */
	public function _tuile($zoom, $x, $y)
	{
		$zoom = (int) $zoom;
		$x    = (int) $x;
		$y    = (int) $y;

		if ($zoom < 0 || $zoom > 19 || $x < 0 || $y < 0 || $x >= 2 ** $zoom || $y >= 2 ** $zoom)
		{
			http_response_code(404);
			exit;
		}

		$fichier = NEOFRAG_CMS.'/'.NF_RELAIS_DOSSIER.'/tuile-'.$zoom.'-'.$x.'-'.$y.'.png';

		if (is_file($fichier) && filemtime($fichier) >= time() - 604800 && ($donnees = @file_get_contents($fichier)) !== FALSE)
		{
			$this->_image($donnees, 'image/png', 604800);
		}

		$donnees = nf_fetch_public_url('https://tile.openstreetmap.org/'.$zoom.'/'.$x.'/'.$y.'.png', 6, 1048576, 'NeoFrag-Reborn/'.NEOFRAG_VERSION.' (carte des lieux ; +'.site_origin().')');
		$info    = $donnees !== NULL ? @getimagesizefromstring($donnees) : FALSE;

		if (!$info || $info[2] !== IMAGETYPE_PNG)
		{
			http_response_code(404);
			exit;
		}

		if (is_dir(dirname($fichier)) || @mkdir(dirname($fichier), 0775, TRUE))
		{
			$provisoire = $fichier.'.'.bin2hex(random_bytes(4));

			if (@file_put_contents($provisoire, $donnees) !== FALSE)
			{
				@rename($provisoire, $fichier);
			}
		}

		$this->_image($donnees, 'image/png', 604800);
	}

	/** Une image, puis la fin de la requête. */
	private function _image(string $donnees, string $type, int $duree): never
	{
		header('Content-Type: '.$type);
		header('Content-Length: '.strlen($donnees));
		header('Cache-Control: public, max-age='.$duree);
		header('X-Content-Type-Options: nosniff');

		exit($donnees);
	}

	/** La réponse JSON de l'envoi d'image, puis la fin de la requête. */
	private function _editeur_reponse(int $statut, array $corps): never
	{
		http_response_code($statut);
		header('Content-Type: application/json; charset=utf-8');

		exit((string) json_encode($corps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	public function auth()
	{
		$authenticators = NeoFrag()	->model2('addon')
									->get('authenticator')
									->filter('is_setup')
									->sort(function($a, $b){
										return $a->settings()->order - $b->settings()->order;
									});

		if (!$authenticators->empty())
		{
			return $this->modal('Connexion rapide', 'fas fa-user-circle')
						->large()
						->body($this->view('authenticators', [
							'authenticators' => $authenticators
						]))
						->button($this	->button()
										->title($this->lang('Mot de passe oublié ?'))
										->color('link')
										->modal_ajax('ajax/user/lost-password')
						)
						->button_if($this->config->nf_registration_status, $this->button()
																				->title($this->lang('Créer un compte'))
																				->color('secondary')
																				->modal_ajax('ajax/user/register')
						)
						->button($this	->button()
										->title('Se connecter')
										->color('primary')
										->modal_ajax('ajax/user/login')
						);
		}
		else
		{
			return $this->login();
		}
	}

	public function login()
	{
		$pending = $this->session('totp', 'pending_user_id');
		$expires = $this->session('totp', 'pending_expires');

		if ($pending && $expires && $expires >= time())
		{
			return $this->form2('login_totp')
						->modal('Validation 2FA', 'fas fa-shield-alt');
		}

		return $this->form2('login')
					->modal('Se connecter', 'fas fa-sign-in-alt')
					->button_prepend_if($this->config->nf_registration_status, $this->button()
																					->title($this->lang('Créer un compte'))
																					->color('secondary')
																					->modal_ajax('ajax/user/register')
					)
					->button_prepend($this	->button()
											->title($this->lang('Mot de passe oublié ?'))
											->color('link')
											->modal_ajax('ajax/user/lost-password')
					);
	}

	public function register()
	{
		return $this->form2(trim(strip_tags($this->config->traduit('nf_registration_charte'))) !== '' ? 'username password_required email charte' : 'username password_required email', $this->model2('user'))
					->compact()
					->captcha()
					->success(function($user, $form){
						// R2.0 — Rate limit anti-bot/spam : 3 inscriptions par IP / 30 min
						$rateLimit = new \NF\NeoFrag\Libraries\Rate_Limit($this);
						$ip        = \NF\NeoFrag\Libraries\Rate_Limit::client_ip();
						$rl_key    = 'register:ip:'.$ip;
						$rl_check  = $rateLimit->check($rl_key);
						if (!$rl_check['allowed'])
						{
							$form->error($this->lang('Trop d\'inscriptions récentes depuis cette IP. Réessaye dans %d minute(s).', ceil($rl_check['retry_after'] / 60)));
							return;
						}
						$rateLimit->hit($rl_key, 3, 1800, 1800);

						$user->set_password($user->password)->create();

						if ($wh = $this->module('webhooks'))
						{
							$wh->trigger('user.registered', [
								'user_id'  => (int)$user->id,
								'username' => $user->username
							]);
						}

						// Le message de bienvenue, par la messagerie (le même pour l'inscription par un compte externe).
						if (($module_user = $this->module('user')) instanceof \NF\Modules\User\User)
						{
							$module_user->bienvenue((int) $user->id, (string) $user->username);

							// La validation par e-mail : le compte attend que son adresse soit prouvée, sans connexion
							// d'ici là. Le lien partait AVANT que le compte existe, et menait à une page absente.
							if ($module_user->a_valider($user))
							{
								$envoye = $module_user->envoyer_validation($user);

								notify($envoye
									? $this->lang('Votre compte est créé : ouvrez le lien envoyé à %s pour l\'activer.', nf_texte($user->email))
									: $this->lang('Votre compte est créé, mais l\'e-mail de validation n\'a pas pu partir : connectez-vous un peu plus tard pour en recevoir un nouveau.'),
									$envoye ? 'success' : 'warning');

								refresh();
							}
						}

						notify($this->lang('Votre compte à bien été créé, bienvenue !'));

						$this->session->login($user);

						refresh();
					})
					->modal($this->lang('Créer un compte'), 'fas fa-sign-in-alt fa-rotate-90')
					->cancel();
	}

	public function lost_password()
	{
		return $this->form2()
					->compact()
					->rule($this->form_email('email')
								->title($this->lang('Adresse email'))
								->required()
					)
					->success(function($data, $form){
						// R2.0 — Rate limit anti-énumération comptes : 5 demandes par IP / heure, 3 par email / heure
						$rateLimit  = new \NF\NeoFrag\Libraries\Rate_Limit($this);
						$ip         = \NF\NeoFrag\Libraries\Rate_Limit::client_ip();
						$ip_key     = 'lost_password:ip:'.$ip;
						$mail_key   = 'lost_password:email:'.strtolower($data['email']);
						$ip_check   = $rateLimit->check($ip_key);
						$mail_check = $rateLimit->check($mail_key);
						if (!$ip_check['allowed'] || !$mail_check['allowed'])
						{
							$retry = max($ip_check['retry_after'], $mail_check['retry_after']);
							$form->error($this->lang('Trop de demandes récentes. Réessaye dans %d minute(s).', ceil($retry / 60)));
							return;
						}
						$rateLimit->hit($ip_key,   5, 3600, 3600);
						$rateLimit->hit($mail_key, 3, 3600, 3600);

						$user = $this->db	->collection('user')
											->where('deleted', FALSE)
											->where('email', $data['email'])
											->row();

						if (!$user())
						{
							$form->error($this->lang('Addresse email introuvable'));
						}
						else
						{
							$sent = $this	->anti_flood()
											->email
											->template('user.lost_password', [
												'username'  => $user->username,
												'reset_url' => absolute_url('user/lost-password/'.$user->token())
											])
											->to($data['email'])
											->send();

							if ($sent)
							{
								notify($this->lang('Message envoyé'));
								$this->modal->dispose();
							}
							else
							{
								$form->error($this->lang('Une erreur s\'est produite lors de l\'envoi du message'));
							}
						}
					})
					->modal($this->lang('Récupération de mot de passe'), 'fas fa-unlock-alt')
					->cancel();
	}

	public function _lost_password($token)
	{
		return $this->form2('password_required')
					->compact()
					->success(function($data) use ($token){
						$user = $token->delete()->user;

						$user	->set_password($data['password'])
								->update();

						notify($this->lang('Nouveau mot de passe enregistré'));

						// Mêmes gardes que la voie mot de passe : le reset prouve le
						// contrôle de l'email, pas le second facteur ni la levée d'un ban.
						if ($this->moderation->is_banned((int)$user->id, 'global'))
						{
							$msg = $this->moderation->block_message_for_user((int)$user->id, 'global');
							notify($msg ?: $this->lang('Ce compte est banni.'), 'danger');
						}
						else if ($user->totp_enabled)
						{
							$this->session->set('totp', 'pending_user_id', $user->id);
							$this->session->set('totp', 'pending_remember', 0);
							$this->session->set('totp', 'pending_expires', time() + 300);
							$this->session->append('modals', 'ajax/user/login');
						}
						else
						{
							$this->session->login($user);
						}

						refresh();
					})
					->modal($this->lang('Réinitialisation de mot de passe'), 'fas fa-unlock-alt')
					->cancel();
	}
}
