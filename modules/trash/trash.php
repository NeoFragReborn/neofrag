<?php
/**
 * https://neofr.ag
 * Module Corbeille — vue centralisée du contenu soft-deleted (deleted_at), avec restauration
 * et purge (suppression définitive), par type/module. Étendre via la table TYPES.
 */

namespace NF\Modules\Trash;

use NF\NeoFrag\Addons\Module;

class Trash extends Module
{
	// Registre des types pris en charge. Pour étendre (forum, galerie…) : ajouter une entrée
	// + la colonne deleted_at sur la table + le filtre WHERE deleted_at IS NULL côté module.
	const TYPES = [
		'news' => [
			'module'  => 'news',     'label' => 'Actualité', 'table' => 'nf_news',
			'pk'      => 'news_id',   'lang'  => 'nf_news_lang', 'title' => 'title',
			'restore' => 'restore_news', 'purge' => 'purge_news', 'url' => 'news/%d/%s',
		],
		'article' => [
			'module'  => 'articles', 'label' => 'Article', 'table' => 'nf_articles',
			'pk'      => 'article_id', 'lang' => 'nf_articles_lang', 'title' => 'title',
			'restore' => 'restore_article', 'purge' => 'purge_article', 'url' => 'articles/%d/%s',
		],
		'gallery' => [
			'module'  => 'gallery', 'label' => 'Galerie', 'table' => 'nf_gallery',
			'pk'      => 'gallery_id', 'lang' => 'nf_gallery_lang', 'title' => 'title',
			'restore' => 'restore_gallery', 'purge' => 'purge_gallery', 'url' => 'gallery/%d/%s',
		],
		// Type sans table de langue : le « titre » est extrait de la colonne content.
		'comment' => [
			'module'  => 'comments', 'label' => 'Commentaire', 'table' => 'nf_comment',
			'pk'      => 'id', 'content' => 'content',
			'restore' => 'restore_comment', 'purge' => 'purge_comment',
		],
		'forum' => [
			'module'  => 'forum', 'label' => 'Message forum', 'table' => 'nf_forum_messages',
			'pk'      => 'message_id', 'content' => 'message',
			'restore' => 'restore_message', 'purge' => 'hard_delete_message',
		],
	];

	protected function __info()
	{
		return [
			'title'       => $this->lang('Corbeille'),
			'description' => $this->lang('Restaurer ou purger définitivement le contenu supprimé.'),
			'icon'        => 'fas fa-trash-restore',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '1.0.0'],
			'routes'      => [
				'admin{pages}' => 'index',
			],
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => $this->lang('Corbeille'),
						'icon'   => 'fas fa-trash-restore',
						'access' => [
							'restore' => ['title' => $this->lang('Restaurer'), 'icon' => 'fas fa-trash-restore', 'admin' => TRUE],
							'purge'   => ['title' => $this->lang('Purger'),     'icon' => 'fas fa-times',          'admin' => TRUE],
						],
					],
				],
			],
		];
	}
}
