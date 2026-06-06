<?php
/**
 * https://neofr.ag
 * NeoFrag — Module emails — Admin checker (R2.0, 2026-05-06)
 */

namespace NF\Modules\Emails\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index()
	{
		if (!$this->access('emails', 'manage_email_templates'))
		{
			$this->error->unauthorized();
			return;
		}

		$current_lang = (isset($this->config->lang) && method_exists($this->config->lang, 'info'))
			? $this->config->lang->info()->name
			: 'fr';

		return [$this->model()->get_templates($current_lang)];
	}

	public function _edit($id, $url_title = NULL)
	{
		if (!$this->access('emails', 'manage_email_templates'))
		{
			$this->error->unauthorized();
			return;
		}

		$template = $this->model()->get_template($id);
		if (!$template)
		{
			$this->error->not_found();
			return;
		}

		return [$template];
	}

	public function _preview($id, $url_title = NULL)
	{
		if (!$this->access('emails', 'manage_email_templates'))
		{
			$this->error->unauthorized();
			return;
		}

		$template = $this->model()->get_template($id);
		if (!$template)
		{
			$this->error->not_found();
			return;
		}

		return [$template];
	}

	public function _test($id, $url_title = NULL)
	{
		if (!$this->access('emails', 'manage_email_templates'))
		{
			$this->error->unauthorized();
			return;
		}

		$template = $this->model()->get_template($id);
		if (!$template)
		{
			$this->error->not_found();
			return;
		}

		return [$template];
	}

	public function _toggle($id, $url_title = NULL)
	{
		if (!$this->access('emails', 'manage_email_templates'))
		{
			$this->error->unauthorized();
			return;
		}

		$template = $this->model()->get_template($id);
		if (!$template)
		{
			$this->error->not_found();
			return;
		}

		return [$template];
	}
}
