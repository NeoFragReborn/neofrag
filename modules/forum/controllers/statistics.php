<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Forum\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'topics' => [
				'title' => $this->lang('Nouveaux sujets'),
				'data'  => function(){
					$this->db	->from('nf_forum_topics t')
								->join('nf_forum_messages m', 'm.message_id = t.message_id', 'INNER');

					return 'm.date';
				}
			],
			'replies' => [
				'title' => $this->lang('Nouvelles réponses'),
				'data'  => function(){
					$this->db	->from('nf_forum_messages m')
								->join('nf_forum_topics t', 'm.topic_id = t.topic_id', 'INNER')
								->where('m.message_id <> t.message_id');

					return 'm.date';
				}
			]
		];
	}
}
