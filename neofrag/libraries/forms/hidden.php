<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries\Forms;

use NF\NeoFrag\Library;

class Hidden extends Library
{
	protected $_name;
	protected $_value;

	/**
	 * La valeur est FACULTATIVE.
	 *
	 * Un champ caché dont le contenu est posé plus tard par du JavaScript s'écrit naturellement
	 * `form_hidden('comment_id')`. Ces champs passent par une magic method : ni l'analyse statique
	 * ni les tests ne voyaient l'appel, et l'argument manquant levait une TypeError EN PLEIN RENDU.
	 * Le routeur l'attrapait et rendait la page d'erreur : toute page portant des commentaires —
	 * actualités, articles, événements, galerie — était vide pour un membre CONNECTÉ, et normale
	 * pour un visiteur anonyme, qui ne voit pas le formulaire de réponse. Signalé sous
	 * la forme « vide sauf en navigation privée ».
	 */
	public function __invoke($name, $value = '')
	{
		$this->_name  = $name;
		$this->_value = $value;
		return $this;
	}

	public function __toString()
	{
		return $this->html('input', TRUE)
					->attr('type',  'hidden')
					->attr('name',  $this->_name)
					->attr('value', $this->_value)
					->__toString();
	}
}
