<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Comments\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index($page = '')
	{
		return [
			$this->collection('comment')
				 ->filters(
					$this->form2()
						 ->rule($this->form_text('content')
									 ->title('Contenu')
									 ->filter('_.content LIKE')
						 )
				 )
				 ->paginate($page)
		];
	}
}
