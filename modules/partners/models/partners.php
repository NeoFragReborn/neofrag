<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Partners\Models;

use NF\NeoFrag\Loadables\Model;

class Partners extends Model
{
	public function get_partners()
	{
		return $this->db->select('p.partner_id', 'p.name', 'p.logo_light', 'p.logo_dark', 'p.website', 'p.facebook', 'p.twitter', 'p.count', 'p.code', 'p.order', 'pl.title', 'pl.description')
						->from('nf_partners p')
						->join_lang('nf_partners_lang pl', 'partner_id', 'p.partner_id')
						->order_by('p.order', 'p.partner_id')
						->get();
	}

	public function check_partner($partner_id, $name)
	{
		return $this->db->select('p.partner_id', 'p.name', 'p.logo_light', 'p.logo_dark', 'p.website', 'p.facebook', 'p.twitter', 'p.count', 'p.code', 'pl.title', 'pl.description')
						->from('nf_partners p')
						->join_lang('nf_partners_lang pl', 'partner_id', 'p.partner_id')
						->where('p.partner_id', $partner_id)
						->where('p.name', $name)
						->row();
	}

	public function add_partner($title, $logo_light, $logo_dark, $description, $website, $facebook, $twitter, $code)
	{
		$partner_id = $this->db->insert('nf_partners', [
			'name'       => url_title($title),
			'logo_light' => $logo_light,
			'logo_dark'  => $logo_dark,
			'website'    => $website,
			'facebook'   => $facebook,
			'twitter'    => $twitter,
			'code'       => $code
		]);

		$this->db->insert('nf_partners_lang', [
			'partner_id'  => $partner_id,
			'lang'        => $this->config->lang->info()->name,
			'title'       => $title,
			'description' => $description
		]);
	}

	public function edit_partner($partner_id, $title, $logo_light, $logo_dark, $description, $website, $facebook, $twitter, $code)
	{
		$this->db	->where('partner_id', $partner_id)
					->update('nf_partners', [
						'name'       => url_title($title),
						'logo_light' => $logo_light,
						'logo_dark'  => $logo_dark,
						'website'    => $website,
						'facebook'   => $facebook,
						'twitter'    => $twitter,
						'code'       => $code
					]);

		$this->db	->where('partner_id', $partner_id)
					->where('lang', $this->config->lang->info()->name)
					->update('nf_partners_lang', [
						'title'       => $title,
						'description' => $description
					]);
	}

	public function delete_partner($partner_id)
	{
		foreach ($this->db->select('logo_light', 'logo_dark')->from('nf_partners')->where('partner_id', $partner_id)->row() as $file_id)
		{
			NeoFrag()->model2('file', $file_id)->delete();
		}

		$this->db	->where('partner_id', $partner_id)
					->delete('nf_partners');
	}
}
