<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : la page de la FAQ, datée de sa dernière question
 * modifiée. Une seule adresse : les questions sont des ancres de cette page (`#faq-q-12`), que les
 * moteurs ramènent à elle — le plan d'avant en annonçait une par question, en doublon.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Faq\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		return [['adresse' => 'faq', 'date' => $this->db->select('MAX(updated_at)')->from('nf_faq_questions')->where('published', '1')->row()]];
	}
}
