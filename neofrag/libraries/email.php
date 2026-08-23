<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries;

use NF\NeoFrag\Library;

class Email extends Library
{
	protected $_key;
	protected $_from;
	protected $_reply_to;
	protected $_to = [];
	protected $_cc = [];
	protected $_bcc = [];
	protected $_subject;
	protected $_view;
	protected $_data;
	protected $_footer;
	protected $_attachments = [];
	protected $_config = [];
	protected $_error;
	protected $_transport = '';

	public function __construct($caller, $config = [])
	{
		parent::__construct($caller);

		$this->_config = array_merge_recursive([
			'smtp' => []
		], $config);

		unset($this->_config['footer']);

		if (isset($config['footer']) && is_a($config['footer'], 'closure'))
		{
			$this->_footer = $config['footer'];
		}
		else
		{
			$this->_footer = function(){
				return $this->config->nf_description.' | <a href="'.url('//').'">'.$this->config->nf_name.'</a>';
			};
		}

		// SMTP saisi dans l'admin (nf_settings) : prioritaire sur config/email.php quand renseigné →
		// l'utilisateur configure son SMTP depuis le panel, sans toucher aux fichiers, et ça survit aux
		// redéploiements. Si non renseigné (host vide) : on garde config/email.php puis l'auto-détection.
		if ($host = (string) $this->config->nf_smtp_host)
		{
			$this->_config['smtp'] = [
				'host'     => $host,
				'port'     => (int) $this->config->nf_smtp_port,
				'username' => (string) $this->config->nf_smtp_username,
				'password' => $this->crypt->decrypt_secret((string) $this->config->nf_smtp_password),
				'secure'   => (string) $this->config->nf_smtp_secure,
			];
		}
	}

	/** Vrai si un serveur SMTP est configuré (host renseigné). Sinon NeoFrag utilise la fonction mail() de PHP. */
	public function has_smtp()
	{
		return !empty($this->_config['smtp']['host']);
	}

	/** Dernière erreur d'envoi (PHPMailer ErrorInfo) — pour l'afficher/diagnostiquer après un send() à FALSE. */
	public function last_error()
	{
		return $this->_error;
	}

	/** Transport effectivement utilisé au dernier envoi (mail() / relais local 127.0.0.1:25 / SMTP configuré) — diagnostic. */
	public function last_transport()
	{
		return $this->_transport;
	}

	public function __sleep()
	{
		return ['_key', '_from', '_reply_to', '_to', '_cc', '_bcc', '_subject'];
	}

	public function key(&$key = NULL)
	{
		if ($key === NULL)
		{
			$key = unique_id($this->db()->select('key')->from('nf_emailing_email_recipient')->get());
		}

		$this->_key = $key;

		return $this;
	}

	public function from($from, $name = '')
	{
		$this->_from = [$from];

		if ((string)$name !== '')
		{
			$this->_from[] = $name;
		}

		return $this;
	}

	public function to($to)
	{
		$this->_to[] = strtolower($to);

		return $this;
	}

	public function reply_to($to, $name = '')
	{
		$this->_reply_to = [strtolower($to)];

		if ((string)$name !== '')
		{
			$this->_reply_to[] = utf8_html_entity_decode($name);
		}

		return $this;
	}

	public function cc($to)
	{
		$this->_cc[] = strtolower($to);

		return $this;
	}

	public function bcc($to)
	{
		$this->_bcc[] = strtolower($to);

		return $this;
	}

	public function subject($subject)
	{
		$this->_subject = $subject;

		return $this;
	}

	public function message()
	{
		$args = func_get_args();

		$this->_data = array_pop($args);
		$this->_view = array_shift($args) ?: 'default';

		return $this;
	}

	/**
	 * R2.0 — Charge un template depuis nf_email_templates par sa clé fonctionnelle, render les placeholders,
	 * set subject + body. Pattern remplaçant les `->subject(...)->message('default', ['content' => ...])` hardcodés.
	 *
	 * Usage : $this->email->template('user.registration', ['username' => 'bob', 'validation_url' => $url], 'fr')
	 *                    ->to($email)->send();
	 *
	 * - Si $lang null : utilise la langue courante du site (fallback fr automatique côté model).
	 * - Auto-injecte 'site_name' si pas fourni (depuis nf_name).
	 * - Si la clé est introuvable ou désactivée : retourne $this sans rien set (le send() échouera proprement).
	 *
	 * @param string $key          Clé du template (ex: 'user.registration')
	 * @param array  $placeholders [name => value]
	 * @param string $lang         Code langue (null = courante)
	 */
	public function template($key, array $placeholders = [], $lang = NULL)
	{
		$emails_module = NeoFrag()->module('emails');

		if (!$emails_module || !$emails_module->is_enabled())
		{
			// Module emails pas installé / désactivé → fallback silencieux : le caller pourra utiliser ->subject()/->message()
			$this->_error = 'Le module Emails est désactivé.';
			return $this;
		}

		$model = $emails_module->model();

		$template = $model->get_by_key($key);
		if (!$template)
		{
			$this->_error = 'Template email introuvable : '.$key;
			return $this;
		}

		if ($lang === NULL)
		{
			$lang = (isset(NeoFrag()->config->lang) && method_exists(NeoFrag()->config->lang, 'info'))
				? NeoFrag()->config->lang->info()->name
				: 'fr';
		}

		$translation = $model->get_translation($template['template_id'], $lang);
		if (!$translation)
		{
			$this->_error = 'Traduction du template « '.$key.' » introuvable pour la langue « '.$lang.' ».';
			return $this;
		}

		// Auto-injection site_name si absent
		if (!isset($placeholders['site_name']))
		{
			$placeholders['site_name'] = (string)NeoFrag()->config->nf_name;
		}

		$this->_subject = $model::render_placeholders($translation['subject'], $placeholders);

		$rendered_body = $model::render_placeholders($translation['body'], $placeholders);

		// On utilise le pipeline 'default' qui injecte $body dans emails/main.tpl.php
		$this->_view = 'default';
		$this->_data = ['content' => $rendered_body];

		return $this;
	}

	public function attachment($file, $name = '')
	{
		if (is_a($file, 'NF\NeoFrag\Models\File'))
		{
			if ($name === '')
			{
				$name = utf8_html_entity_decode($file->name);
			}

			$file = $file->path;
		}

		if ($name === '')
		{
			$name = basename($file);
		}

		$this->_attachments[] = [$file, $name];

		return $this;
	}

	public function send()
	{
		if (!$this->_to || !$this->_subject || !$this->_view)
		{
			$missing = [];
			if (!$this->_to)      $missing[] = 'destinataire';
			if (!$this->_subject) $missing[] = 'sujet';
			if (!$this->_view)    $missing[] = 'corps';
			$this->_error = $this->_error ?: 'Email incomplet ('.implode(', ', $missing).' manquant).';

			return FALSE;
		}

		$debug = [];

		$PHPMailer = new \PHPMailer\PHPMailer\PHPMailer;

		$PHPMailer->SMTPDebug   = 2; // capture la conversation SMTP (journalisée seulement en cas d'échec)
		$PHPMailer->Debugoutput = function($message) use (&$debug){
			$debug[] = $message;
		};

		if (!empty($this->_config['smtp']['host']))
		{
			$PHPMailer->isSMTP();

			$PHPMailer->Host = $this->_config['smtp']['host'];

			if ($PHPMailer->SMTPAuth = $this->_config['smtp']['username'] && $this->_config['smtp']['password'])
			{
				$PHPMailer->Username = $this->_config['smtp']['username'];
				$PHPMailer->Password = $this->_config['smtp']['password'];
			}

			if ($this->_config['smtp']['secure'])
			{
				$PHPMailer->SMTPSecure = $this->_config['smtp']['secure'];
			}

			if ($this->_config['smtp']['port'])
			{
				$PHPMailer->Port = $this->_config['smtp']['port'];
			}
		}

		if ($this->_reply_to)
		{
			call_user_func_array([$PHPMailer, 'AddReplyTo'], $this->_reply_to);
		}

		$PHPMailer->setFrom(strtolower($this->_from && array_key_exists(0, $this->_from) ? $this->_from[0] : $this->config->nf_contact),
							utf8_html_entity_decode($this->_from && array_key_exists(1, $this->_from) ? $this->_from[1] : $this->config->nf_name),
							!ini_get('sendmail_from')
		);


		if ($this->_key)
		{
			$PHPMailer->MessageID = '<'.$this->_key.'@'.preg_replace('/^.+?@/', '', $PHPMailer->From).'>';
		}

		$PHPMailer->XMailer  = ' ';
		$PHPMailer->Encoding = 'quoted-printable';
		$PHPMailer->CharSet  = 'UTF-8';
		$PHPMailer->Subject  = utf8_html_entity_decode($this->_subject);
		$PHPMailer->isHTML(TRUE);

		foreach (array_unique($this->_to) as $to)
		{
			$PHPMailer->addAddress($to);
		}

		foreach (array_unique($this->_cc) as $to)
		{
			$PHPMailer->AddCC($to);
		}

		foreach (array_unique($this->_bcc) as $to)
		{
			$PHPMailer->AddBCC($to);
		}

		foreach ($this->_attachments as $attachment)
		{
			$PHPMailer->addAttachment(...$attachment);
		}

		$this->output->email(function() use ($PHPMailer){
			$data = array_merge([
				'header'  => '',
				'content' => '',
				'footer'  => ''
			], is_a($this->_data, 'closure') ? call_user_func($this->_data) : $this->_data);

			$data['footer'] .= call_user_func($this->_footer);

			$PHPMailer->Body = (string)$this->view('emails/main', [
				'body' => $this->view('emails/'.$this->_view, $data)
			]);

			$PHPMailer->AltBody = trim(strip_tags($PHPMailer->Body));
		});

		if (!empty($this->_config['smtp']['host']))
		{
			// SMTP explicitement configuré (admin ou config/email.php) : on l'utilise en priorité.
			$this->_transport = 'SMTP '.$this->_config['smtp']['host'];
			$sent = $PHPMailer->send();

			if (!$sent)
			{
				// SMTP configuré mais en échec (identifiants erronés, relais injoignable…) : ne pas bloquer
				// TOUT l'envoi du site — on retombe sur l'auto-détection (relais local / mail()).
				if (method_exists($PHPMailer, 'smtpClose'))
				{
					$PHPMailer->smtpClose();
				}
				$sent = $this->_send_auto($PHPMailer);
			}
		}
		else
		{
			// Aucun SMTP configuré : envoi « clé en main » — on détecte automatiquement le transport qui
			// marche sur cet hébergement (relais SMTP local puis mail()), sans service externe.
			$sent = $this->_send_auto($PHPMailer);
		}

		if (!$sent)
		{
			// La vraie cause de l'échec (From rejeté, mail() KO, SMTP refusé…) est dans ErrorInfo.
			// On la mémorise (last_error()), on la journalise, puis on déverse la conversation SMTP.
			// On garde un message déjà posé par l'auto-détection (ex. aucun transport disponible).
			$this->_error = $this->_error ?: $PHPMailer->ErrorInfo;

			error_log('[email] échec envoi'.($this->has_smtp() ? ' (SMTP '.$this->_config['smtp']['host'].')' : ' (mail() PHP)').' : '.$PHPMailer->ErrorInfo);

			foreach ($debug as $message)
			{
				trigger_error(utf8_string($message, is_windows() ? 'CP1252' : ''), E_USER_WARNING);
			}
		}

		return $sent;
	}

	// Détection automatique du transport sur un hébergement NON configuré : essaie d'abord les relais SMTP
	// LOCAUX de l'hébergeur (Postfix/Exim — présents sur la quasi-totalité des mutualisés), puis PHP mail()
	// en dernier (mail() renvoie souvent TRUE sans réellement livrer). Aucun service externe. Mémorise le
	// transport qui marche (nf_email_transport) ; le cache reste un INDICE — on retombe sur la liste complète
	// s'il a cessé de marcher, et on mémorise l'échec total (TTL) pour ne pas re-tester en boucle.
	private function _send_auto($PHPMailer)
	{
		$defaults = ['127.0.0.1:25', 'localhost:25', '127.0.0.1:587', 'mail'];
		$cached   = (string) $this->config->nf_email_transport;

		// Échec total mémorisé récemment (sentinelle 'none:<ts>') : ne pas re-dérouler la détection lente à
		// chaque envoi tant que rien ne marche (TTL court pour re-tenter ensuite).
		if (strpos($cached, 'none:') === 0)
		{
			if (time() - (int) substr($cached, 5) < 600)
			{
				$this->_error = 'Aucun transport email disponible sur cet hébergement (détection récente en échec).';
				return FALSE;
			}
			$cached = '';
		}

		// Le transport mémorisé est essayé en premier, mais s'il a cessé de marcher on enchaîne sur les
		// autres candidats dans le MÊME appel (sinon l'email courant serait perdu).
		$candidates = $cached !== '' ? array_merge([$cached], array_values(array_diff($defaults, [$cached]))) : $defaults;

		foreach ($candidates as $transport)
		{
			if ($transport === 'mail')
			{
				if (!function_exists('mail') || preg_match('/(^|,)\s*mail\s*(,|$)/i', (string) ini_get('disable_functions')))
				{
					continue; // mail() désactivé par l'hébergeur
				}
				$PHPMailer->isMail();
			}
			else
			{
				list($host, $port) = explode(':', $transport);
				$PHPMailer->isSMTP();
				$PHPMailer->Host        = $host;
				$PHPMailer->Port        = (int) $port;
				$PHPMailer->SMTPAuth    = FALSE;
				$PHPMailer->SMTPAutoTLS = FALSE;
				$PHPMailer->SMTPSecure  = '';
				$PHPMailer->Timeout     = 5; // ne pas bloquer si le relais est absent
			}

			$this->_transport = $transport === 'mail' ? 'mail()' : 'SMTP local '.$transport;

			if (@$PHPMailer->send())
			{
				$this->_remember($transport);
				return TRUE;
			}

			if (method_exists($PHPMailer, 'smtpClose'))
			{
				$PHPMailer->smtpClose();
			}
		}

		$this->_remember('none:'.time()); // mémorise l'échec total (TTL) → pas de re-détection lente immédiate
		return FALSE;
	}

	/** Mémorise le transport auto-détecté via Config (UPSERT nf_settings + maj du cache mémoire $_const →
	 *  les envois suivants de la MÊME requête sont directs, sans re-dérouler la détection). */
	private function _remember($transport)
	{
		if ((string) $this->config->nf_email_transport !== (string) $transport)
		{
			$this->config('nf_email_transport', $transport, 'string');
		}
	}
}
