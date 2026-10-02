<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries;

use NF\NeoFrag\Libraries\Captcha\Altcha;
use NF\NeoFrag\Libraries\Captcha\Fournisseur;
use NF\NeoFrag\Libraries\Captcha\Hcaptcha;
use NF\NeoFrag\Libraries\Captcha\Recaptcha;
use NF\NeoFrag\Libraries\Captcha\Turnstile;
use NF\NeoFrag\Library;

/**
 * Le captcha du site : la façade des fournisseurs (2026-10-02).
 *
 * Les deux systèmes de formulaires passent par elle — `form()->add_captcha()` (recrutement) et
 * `form2()->captcha()` (contact, inscription). Le fournisseur vient du réglage `nf_captcha_provider` :
 * ALTCHA par défaut, sans clé ni service tiers ; Turnstile, hCaptcha ou reCAPTCHA v2 avec leurs clés
 * (`nf_captcha_public_key`, `nf_captcha_private_key`, la seconde chiffrée au repos).
 */
class Captcha extends Library
{
	/** Les fournisseurs, par valeur du réglage. */
	public const FOURNISSEURS = [
		'altcha'    => Altcha::class,
		'turnstile' => Turnstile::class,
		'hcaptcha'  => Hcaptcha::class,
		'recaptcha' => Recaptcha::class,
	];

	/** La valeur du réglage qui éteint le captcha — déconseillée. */
	public const AUCUN = 'aucun';

	private ?Fournisseur $_fournisseur = NULL;
	private bool $_resolu = FALSE;

	/**
	 * Le fournisseur actif d'après les réglages. Sans réglage — un site d'avant ce changement —, reCAPTCHA
	 * si ses deux clés existent, sinon ALTCHA. Un fournisseur qui exige des clés absentes retombe sur
	 * ALTCHA : un captcha mal réglé ne doit pas laisser un formulaire sans protection. Pure : la CSP
	 * d'index.php s'en sert avant que la page existe.
	 *
	 * @return string la valeur du fournisseur, '' si le captcha est éteint
	 */
	public static function cle_active(?string $reglage, ?string $public, ?string $prive): string
	{
		$reglage = (string) $reglage;
		$cles    = (string) $public !== '' && (string) $prive !== '';

		if ($reglage === self::AUCUN)
		{
			return '';
		}

		if ($reglage === '')
		{
			return $cles ? Recaptcha::cle() : Altcha::cle();
		}

		if (!isset(self::FOURNISSEURS[$reglage]) || (self::FOURNISSEURS[$reglage]::cles_requises() && !$cles))
		{
			return Altcha::cle();
		}

		return $reglage;
	}

	/**
	 * Les origines que la politique de sécurité ouvre pour ce fournisseur.
	 *
	 * @return array{script?: list<string>, frame?: list<string>, style?: list<string>}
	 */
	public static function csp(string $cle): array
	{
		return isset(self::FOURNISSEURS[$cle]) ? self::FOURNISSEURS[$cle]::csp() : [];
	}

	/** Le fournisseur actif, ou NULL si le captcha est éteint. */
	public function fournisseur(): ?Fournisseur
	{
		if ($this->_resolu)
		{
			return $this->_fournisseur;
		}

		$this->_resolu = TRUE;
		$public        = (string) $this->config->nf_captcha_public_key;
		$prive         = (string) $this->crypt->decrypt_secret((string) $this->config->nf_captcha_private_key);
		$cle           = self::cle_active((string) $this->config->nf_captcha_provider, $public, $prive);

		if ($cle === Altcha::cle())
		{
			$this->_fournisseur = new Altcha($this->crypt->derive('captcha-altcha'), $this->_adresse_worker(), $this->_textes(), \Closure::fromCallable([$this, '_deja_vu']));
		}
		else if ($cle !== '')
		{
			$classe             = self::FOURNISSEURS[$cle];
			$this->_fournisseur = new $classe($public, $prive);
		}

		return $this->_fournisseur;
	}

	public function is_ok(): bool
	{
		return $this->fournisseur() !== NULL;
	}

	/** Le champ POST qui porte la réponse du visiteur. */
	public function champ(): string
	{
		return $this->fournisseur()?->champ() ?? '';
	}

	/**
	 * L'élément à afficher, ses scripts demandés à la page. $theme et $taille passent aux widgets qui les
	 * comprennent (Turnstile, hCaptcha, reCAPTCHA).
	 */
	public function element(string $theme = '', string $taille = ''): string
	{
		if (!($fournisseur = $this->fournisseur()))
		{
			return '';
		}

		$langue = (string) $this->config->lang->info()->name;

		foreach ($fournisseur->scripts($langue) as $script)
		{
			NeoFrag()->js($script);
		}

		[$balise, $attributs] = $fournisseur->element($langue);

		$html = NeoFrag()->html($balise);

		foreach (array_filter(['data-theme' => $theme, 'data-size' => $taille]) + $attributs as $nom => $valeur)
		{
			$html->attr($nom, $valeur);
		}

		return (string) $html;
	}

	/** L'ancien nom, gardé pour `form()` et les addons qui l'appellent. */
	public function display(): string
	{
		return $this->element();
	}

	/** La réponse du visiteur est-elle valide ? Captcha éteint : rien à vérifier. */
	public function is_valid(?array $post = NULL): bool
	{
		if (!($fournisseur = $this->fournisseur()))
		{
			return TRUE;
		}

		$post    ??= post();
		$reponse = $post[$fournisseur->champ()] ?? '';

		return $fournisseur->verifier(is_string($reponse) ? $reponse : '', Rate_Limit::client_ip(), function(string $url, array $champs): ?array {
			$resultat = $this->network($url)->post(http_build_query($champs));

			return is_object($resultat) || is_array($resultat) ? json_decode((string) json_encode($resultat), TRUE) : NULL;
		});
	}

	/** L'adresse du calcul d'ALTCHA, versionnée comme les autres scripts. */
	private function _adresse_worker(): string
	{
		$adresse = path('altcha/pbkdf2.js', 'js');

		if ($version = asset_version('altcha/pbkdf2.js', 'js'))
		{
			$adresse .= '?v='.$version;
		}

		return $adresse;
	}

	/**
	 * Les textes du widget ALTCHA, traduits par le site plutôt que par ses fichiers de langue : il reste
	 * dans les six langues du produit, et `check-langs` les tient.
	 *
	 * @return array<string, string>
	 */
	private function _textes(): array
	{
		$nf = NeoFrag();

		return [
			'label'                => (string) $nf->lang('Je ne suis pas un robot'),
			'verify'               => (string) $nf->lang('Vérifier'),
			'verifying'            => (string) $nf->lang('Vérification en cours…'),
			'verified'             => (string) $nf->lang('Vérifié'),
			'verificationRequired' => (string) $nf->lang('Vérification requise'),
			'waitAlert'            => (string) $nf->lang('Vérification en cours, veuillez patienter.'),
			'error'                => (string) $nf->lang('La vérification a échoué. Réessayez plus tard.'),
			'expired'              => (string) $nf->lang('La vérification a expiré. Réessayez.'),
			'loading'              => (string) $nf->lang('Chargement…'),
			'reload'               => (string) $nf->lang('Recharger'),
			'cancel'               => (string) $nf->lang('Annuler'),
		];
	}

	/**
	 * Une solution ALTCHA déjà présentée ? Sinon, la retient jusqu'à l'expiration de son défi. Un fichier
	 * par empreinte dans cache/captcha, créé d'un geste (`x`) : deux envois simultanés de la même solution
	 * ne passent pas tous les deux. Un cache qu'on ne peut pas écrire ne bloque pas les visiteurs — la
	 * solution reste vérifiée et signée ; seul le rejeu cesse d'être refusé, et le journal le dit. Le ménage
	 * des empreintes expirées se fait au passage.
	 */
	private function _deja_vu(string $empreinte, int $jusqua): bool
	{
		$dossier = NEOFRAG_CMS.'/cache/captcha';
		$chemin  = $dossier.'/'.$empreinte;

		if (!preg_match('/^[a-f0-9]{64}$/', $empreinte))
		{
			return TRUE;
		}

		if (!is_dir($dossier))
		{
			@mkdir($dossier, 0775, TRUE);
		}

		if (!($fichier = @fopen($chemin, 'x')))
		{
			if (is_file($chemin))
			{
				return TRUE;
			}

			error_log('[captcha] '.$dossier." ne peut pas être écrit : le rejeu d'une solution ALTCHA n'est plus refusé");

			return FALSE;
		}

		fwrite($fichier, (string) $jusqua);
		fclose($fichier);

		if (random_int(1, 50) === 1)
		{
			foreach (glob($dossier.'/*') ?: [] as $ancien)
			{
				if ((int) @file_get_contents($ancien) < time())
				{
					@unlink($ancien);
				}
			}
		}

		return FALSE;
	}
}
