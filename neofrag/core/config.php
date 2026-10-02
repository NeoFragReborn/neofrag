<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Core;

use NF\NeoFrag\Core;

/**
 * Settings exposés via __get() (depuis nf_settings). Annotations pour l'IDE + PHPStan.
 * @property mixed $nf_analytics
 * @property mixed $nf_captcha_private_key
 * @property mixed $nf_captcha_public_key
 * @property mixed $nf_contact
 * @property mixed $nf_cookie_expire
 * @property mixed $nf_font
 * @property mixed $nf_session_history_days
 * @property mixed $nf_cookie_name
 * @property mixed $nf_copyright
 * @property mixed $nf_cron_key
 * @property mixed $nf_email_transport
 * @property mixed $nf_smtp_host
 * @property mixed $nf_smtp_port
 * @property mixed $nf_smtp_username
 * @property mixed $nf_smtp_password
 * @property mixed $nf_smtp_secure
 * @property mixed $nf_default_page
 * @property mixed $nf_default_theme
 * @property mixed $nf_description
 * @property mixed $nf_favicon
 * @property mixed $nf_http_authentication
 * @property mixed $nf_http_authentication_name
 * @property mixed $nf_humans_txt
 * @property mixed $nf_maintenance
 * @property mixed $nf_maintenance_background
 * @property mixed $nf_maintenance_background_color
 * @property mixed $nf_maintenance_background_position
 * @property mixed $nf_maintenance_background_repeat
 * @property mixed $nf_maintenance_content
 * @property mixed $nf_maintenance_logo
 * @property mixed $nf_maintenance_opening
 * @property mixed $nf_maintenance_text_color
 * @property mixed $nf_maintenance_title
 * @property mixed $nf_moderation_auto_escalation
 * @property mixed $nf_moderation_default_ban_temp_duration_seconds
 * @property mixed $nf_moderation_default_mute_duration_seconds
 * @property mixed $nf_moderation_enabled
 * @property mixed $nf_moderation_preserve_content_snapshot
 * @property mixed $nf_moderation_report_flag_threshold_per_day
 * @property mixed $nf_moderation_report_rate_limit_per_hour
 * @property mixed $nf_moderation_require_approval_ban_perm
 * @property mixed $nf_moderation_require_approval_ban_temp
 * @property mixed $nf_moderation_snapshot_attachments_enabled
 * @property mixed $nf_moderation_snapshot_max_size_mb
 * @property mixed $nf_moderation_warning_threshold_ban
 * @property mixed $nf_moderation_warning_threshold_mute
 * @property mixed $nf_moderation_warning_window_days
 * @property mixed $nf_monitoring_last_check
 * @property mixed $nf_name
 * @property mixed $nf_registration_charte
 * @property mixed $nf_registration_status
 * @property mixed $nf_robots_txt
 * @property mixed $nf_social_behance
 * @property mixed $nf_social_deviantart
 * @property mixed $nf_social_dribble
 * @property mixed $nf_social_facebook
 * @property mixed $nf_social_flickr
 * @property mixed $nf_social_github
 * @property mixed $nf_social_google
 * @property mixed $nf_social_instagram
 * @property mixed $nf_social_steam
 * @property mixed $nf_social_twitch
 * @property mixed $nf_social_twitter
 * @property mixed $nf_social_youtube
 * @property mixed $nf_team_biographie
 * @property mixed $nf_team_creation
 * @property mixed $nf_team_logo
 * @property mixed $nf_team_name
 * @property mixed $nf_team_type
 * @property mixed $nf_theme_color
 * @property mixed $nf_update_callback
 * @property mixed $nf_version_css
 * @property mixed $nf_welcome
 * @property mixed $nf_welcome_content
 * @property mixed $nf_welcome_title
 * @property mixed $nf_welcome_user_id
 *
 * Réglages du cœur lus par les modules (marketplace, dons, réseaux sociaux, supervision,
 * traduction, version) :
 * @property mixed $nf_donations_paypal_email
 * @property mixed $nf_marketplace_url
 * @property mixed $nf_monitoring_check_url
 * @property mixed $nf_registration_validation
 * @property mixed $nf_social_bluesky
 * @property mixed $nf_social_discord
 * @property mixed $nf_social_linkedin
 * @property mixed $nf_social_mastodon
 * @property mixed $nf_social_threads
 * @property mixed $nf_social_tiktok
 * @property mixed $nf_theme_epoch
 * @property mixed $nf_translate_api
 * @property mixed $nf_pwa
 * @property mixed $nf_version
 * @property mixed $nf_migrations_version
 * @property mixed $articles_per_page
 * @property mixed $articles_liste
 * @property mixed $articles_fiche
 *
 * Deux valeurs que Config pose lui-même (voir `_const`), pas des réglages : la langue courante
 * et la liste des langues installées. `mixed` et non `Language` : cet addon résout `date()`,
 * `time` ou `datetime` par méthode magique, et le typer ferait surgir ces appels comme inconnus.
 * @property mixed $lang
 * @property mixed $langs
 */
class Config extends Core
{
	protected $_const = [];

	public function __construct()
	{
		$settings = [];

		foreach ($this->db->select('site', 'lang', 'name', 'value', 'type')->from('nf_settings')->get() as $setting)
		{
			if ($setting['type'] == 'array')
			{
				$value = unserialize(utf8_html_entity_decode($setting['value']), ['allowed_classes' => false]);
			}
			else if ($setting['type'] == 'list')
			{
				$value = explode('|', $setting['value']);
			}
			else if ($setting['type'] == 'bool')
			{
				$value = (bool)$setting['value'];
			}
			else if ($setting['type'] == 'int')
			{
				$value = (int)$setting['value'];
			}
			else
			{
				$value = $setting['value'];
			}

			$settings[$setting['site']][$setting['lang']][$setting['name']] = $value;
		}

		$load = function($site = '', $lang = '') use (&$settings){
			$this->_const['lang'] = $lang;
			$this->_const['site'] = $site;

			if ($lang)
			{
				$lang = $lang->info()->name;
			}

			if (!empty($settings[$site][$lang]))
			{
				foreach ($settings[$site][$lang] as $name => $value)
				{
					$this->_const[$name] = $value;
				}
			}
		};

		$load();

		if ($this->url->subdomain)
		{
			$load($this->url->subdomain);
		}

		$this->on('session_init', function($session) use (&$load){
			$n = 0;
			$langs = [];

			foreach ($this->model2('addon')->get('language') as $lang)
			{
				if ($lang->is_enabled())
				{
					$n++;
					$langs[$lang->info()->name] = $lang;
				}
			}

			$main_lang = NULL;

			if ($n > 1)
			{
				uasort($langs, function($a, $b){
					// L'ordre d'affichage d'une langue est un ENTIER, et `strnatcmp()` attend deux
					// chaines : hors mode strict, PHP convertissait en silence. Le cast ne change
					// pas le classement — c'est le tri naturel qui est voulu ici, pour que 2 passe
					// avant 10.
					return strnatcmp((string) $a->settings()->order, (string) $b->settings()->order);
				});

				$this->trigger('config_langs_listed', $langs, $main_lang);

				if (!$main_lang)
				{
					if (($this->user() && ($addon = $this->user->language->addon()) && isset($langs[$name = $addon->info()->name])) || isset($langs[$name = $session('language')]))
					{
						$main_lang = $langs[$name];
					}
					else if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) && preg_match_all('/([a-zA-Z-]+)(?:;q=([0-9.]+))?,?/', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches, PREG_SET_ORDER))
					{
						$accepted = [];

						foreach ($matches as $match)
						{
							$accepted[$match[1]] = isset($match[2]) ? (float)$match[2] : 1;
						}

						arsort($accepted);

						foreach ($accepted as $name => $q)
						{
							if (isset($langs[$name]))
							{
								$main_lang = $langs[$name];
								break;
							}
						}
					}
				}
			}

			if (!$main_lang)
			{
				$main_lang = reset($langs);
			}

			$load('', $main_lang);

			$this->_const['langs'] = [];

			if ($n > 1)
			{
				unset($langs[$this->_const['lang']->info()->name]);
				array_unshift($langs, $main_lang);
				$this->_const['langs'] = array_values($langs);

				if (nf_debogage_actif() || nf_trace_active())
				{
					$this->debug('LANGS', implode(' / ', array_map(function($a){
						return strtoupper($a->info()->name);
					}, $langs)));
				}
			}

			$this->trigger('config_lang_selected');

			setlocale(LC_ALL, $main_lang->locale());

			$this->trigger('config_init');
		});

		$this->debug->bar('settings', function(){
			return $this->_const;
		});
	}

	public function __get($name)
	{
		if (array_key_exists($name, $this->_const))
		{
			return $this->_const[$name];
		}

		return parent::__get($name);
	}

	public function __set($name, $value)
	{
		$this->_const[$name] = $value;
	}

	public function __call($name, $args)
	{
		if ($name == 'unset')
		{
			if (array_key_exists($args[0], $this->_const))
			{
				unset($this->_const[$args[0]]);

				NeoFrag()->db	->where('name', $args[0])
								->delete('nf_settings');
			}

			return $this;
		}

		return parent::__call($name, $args);
	}


	public function __isset($name)
	{
		return array_key_exists($name, $this->_const);
	}

	public function __invoke($name, $value, $type = NULL)
	{
		if (array_key_exists($name, $this->_const))
		{
			NeoFrag()->db	->where('name', $name)
							->update('nf_settings', [
								'value' => $value
							]);

			if ($type)
			{
				NeoFrag()->db	->where('name', $name)
								->update('nf_settings', [
									'type' => $type
								]);
			}
		}
		else
		{
			NeoFrag()->db->insert('nf_settings', [
				'name'  => $name,
				'value' => $value,
				'type'  => $type ?: 'string'
			]);
		}

		$this->_const[$name] = $value;

		return $this;
	}
}
