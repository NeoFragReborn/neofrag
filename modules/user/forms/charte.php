<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

$this	->rule($this->form_checkbox('charte')
					->data([
						'on' => $this->lang('En vous inscrivant, vous acceptez notre <a %s>charte d\'inscription</a>', 'href="#collapseCharte" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="collapseCharte"').'
								<div class="collapse" id="collapseCharte">
									<div class="card card-body mt-2">'.bbcode($this->config->nf_registration_charte).'</div>
								</div>'
					])
					->required()
		);
