<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries\Forms;

class Number extends Text
{
	protected $_type = 'number';
	protected $_step = 1;

	public function __invoke($name)
	{
		parent::__invoke($name);

		// (string) : le pas et la valeur arrivent souvent en ENTIER — un réglage enregistré se relit typé —, et
		// sous `strict_types` str_replace() refuse un entier. Depuis la vague du 2026-09-21, tout écran dont
		// un champ nombre avait une valeur enregistrée répondait 500 (réglages de Recrutement et du Forum,
		// signalés sur le site officiel le 2026-10-04).
		array_splice($this->_template, 1, 0, function(&$input){
			$input->attr('step', str_replace(',', '.', (string) $this->_step));
		});

		$this->_check[] = function($post, &$data){
			if (isset($post[$this->_name]) && $post[$this->_name] !== '' && $post[$this->_name] != (float)$post[$this->_name])
			{
				$this->_errors[] = 'Nombre invalide';
			}
		};

		return $this;
	}

	public function value($value, $erase = FALSE)
	{
		return parent::value($value === NULL ? NULL : str_replace(',', '.', (string) $value), $erase);
	}

	public function step($step)
	{
		$this->_step = $step;
		return $this;
	}
}
