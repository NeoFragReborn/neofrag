<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries\Forms;

/**
 * Le captcha d'un formulaire `form2()` — contact, inscription. Le fournisseur et sa vérification sont
 * ceux de la façade (NF\NeoFrag\Libraries\Captcha) ; ce champ ne fait que les placer.
 *
 * Un membre connecté n'en voit pas. Un visiteur qui l'a réussi mais qu'une autre erreur oblige à renvoyer
 * le formulaire en est dispensé pour ce seul renvoi (session) ; l'envoi d'après le redemande.
 */
class Captcha extends Labelable
{
	protected $_color;
	protected $_compact;
	protected $_session;

	public function __invoke($name = '')
	{
		if (!$this->captcha->is_ok())
		{
			return;
		}

		$this->__id();

		$this->_check[] = function($post){
			if ($this->user())
			{
				return FALSE;
			}

			// La dispense ne sert qu'une fois (2026-10-02). Elle durait jusqu'à la fin de la session : un
			// robot qui résolvait un seul défi envoyait ensuite ce formulaire sans limite.
			if ($this->session('captcha', $this->__id()))
			{
				$this->session->destroy('captcha', $this->__id());
				return FALSE;
			}

			if ($this->captcha->is_valid((array) $post))
			{
				$this->session->set('captcha', $this->__id(), TRUE);
				$this->_session = TRUE;
				return FALSE;
			}

			$this->_errors[] = $this->lang('Veuillez valider la vérification anti-robot');

			return FALSE;
		};

		$this->_template[] = function(&$input){
			if (!$this->user() && !$this->_session)
			{
				$input = $this->captcha->element((string) $this->_color, $this->_compact ? 'compact' : '');

				// Les champs disent leur erreur dans une bulle sur l'icône de leur libellé. Le captcha n'a
				// pas de libellé : il ne restait qu'une icône seule, sans bulle sur un écran tactile. Son
				// erreur s'écrit donc en clair sous lui (sauf en affichage compact, qui le fait déjà).
				if ($this->_errors && !($this->_form && ($this->_form->display() & \NF\NeoFrag\Libraries\Form2::FORM_COMPACT)))
				{
					$input .= $this	->html('div')
									->attr('class', 'nf-captcha-error text-danger mt-1')
									->attr('role', 'alert')
									->content($this->label(implode('<br />', $this->_errors), 'fas fa-exclamation-triangle'));
				}
			}

			return FALSE;
		};

		return parent::__invoke($this->captcha->champ());
	}

	/**
	 * Le formulaire a abouti (appelé par Form2) : la dispense, faite pour un renvoi après une autre erreur,
	 * n'a plus lieu d'être, et le formulaire réaffiché redemande la vérification.
	 */
	public function abouti()
	{
		if ($this->_session)
		{
			$this->session->destroy('captcha', $this->__id());
			$this->_session = FALSE;
		}
	}

	public function dark()
	{
		$this->_color = 'dark';
		return $this;
	}

	public function compact()
	{
		$this->_compact = TRUE;
		return $this;
	}
}
