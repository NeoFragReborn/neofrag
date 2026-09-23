<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Talks\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		return [];
	}

	public function _new()
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		return [];
	}

	public function _view($talk_id, $title, $page = '')
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		return [$talk_id, $title, $page];
	}

	public function _invite($talk_id, $title)
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		return [$talk_id, $title];
	}

	public function _leave($talk_id, $title)
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		return [$talk_id, $title];
	}

	public function _archive($talk_id, $title)
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		return [$talk_id, $title];
	}

	public function _unarchive($talk_id, $title)
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		return [$talk_id, $title];
	}

	public function _report($talk_id, $title, $message_id)
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		return [$talk_id, $title, $message_id];
	}
}
