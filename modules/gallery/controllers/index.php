<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Gallery\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index($category_id = NULL, $name = NULL, $breadcrumb = NULL)
	{
		$this->breadcrumb_if($breadcrumb, $breadcrumb);

		$gallery = $this->model()->get_gallery($category_id);

		if ($category_id && $name)
		{
			$category = $this->model()->check_category($category_id, $name);
		}

		foreach ($gallery as $key => $galerie)
		{
			if (!$this->access('gallery', 'gallery_see', $galerie['gallery_id']))
			{
				unset($gallery[$key]);
			}
		}

		return $this->row()
					->append(
						$this	->col()
								->append(
									$this	->widget('navigation')
											->output('vertical', [
												'links' => $this->array()
																->append([
																	'title' => $this->lang('Tous les albums'),
																	'url'   => 'gallery'
																])
																->exec(function($array){
																	foreach ($this->model()->get_categories() as $category)
																	{
																		$array->append([
																			'title' => ($category['icon_id'] ? '<img src="'.NeoFrag()->model2('file', $category['icon_id'])->path().'" class="img-icon me-2" alt="" />' : '').$category['title'],
																			'url'   => 'gallery/'.$category['category_id'].'/'.$category['name']
																		]);
																	}
																})
																->__toArray()
											])
											->title($this->lang('Galeries'), 'far fa-image')
								)
								->size('col-md-4 col-lg-3')
					)
					->append(
						$this	->col()
								->append(
									$this->view('gallery', [
										'category_id' => $category_id,
										'category'    => isset($category) ? $category : NULL,
										'gallery'     => $gallery
									])
								)
								->size('col-md-8 col-lg-9')
					);
	}

	public function _category($category_id, $name, $title)
	{
		return $this->index($category_id, $name, $title);
	}

	public function _gallery($gallery_id, $category_id, $image_id, $name, $published, $title, $description, $category_name, $category_title, $image, $category_icon, $images)
	{
		$this	->breadcrumb($category_title, 'gallery/'.$category_id.'/'.url_title($category_name))
				->breadcrumb($title);

		return $this->row(
			$this->col(
				$this	->panel()
						->body($this->view('album', [
							'image_id'       => $image_id,
							'title'          => $title,
							'description'    => $description,
							'category_id'    => $category_id,
							'category_name'  => $category_name,
							'category_title' => $category_title,
							'image'          => $image,
							'count'          => count($this->model()->get_images($gallery_id))
						]), FALSE)
						->footer_if($this->access('gallery', 'gallery_post', $gallery_id), $this->button($this->lang('Poster une image'), 'fas fa-plus', 'primary d-block w-100')->modal_ajax('ajax/gallery/post/'.$gallery_id.'/'.url_title($name)))
						->size('col-12 col-lg-4')
			),
			$this->col(
				$this->view('images', [
					'images'     => $images,
					'pagination' => $this->module->pagination->get_pagination()
				])
			)->size('col-12 col-lg-8')
		);
	}

	public function _image($image_id, $original_file_id, $gallery_id, $title, $description, $gallery_name, $gallery_title)
	{
		$album = 'gallery/album/'.$gallery_id.'/'.$gallery_name;

		$this	->title($title)
				->breadcrumb($gallery_title, $album)
				->breadcrumb($title);

		return $this->array()
					->append($this->row($this->col(
						$this	->panel()
								->heading($title, 'far fa-image')
								->body($this->view('image', [
									'original_file_id' => $original_file_id
								]).($description ? '<p class="mt-3 mb-0">'.bbcode($description).'</p>' : '')
								// Signaler une image (2026-10-09 : la galerie n'avait pas de bouton). Une image ne garde pas son auteur.
								.(($moderation = $this->module('moderation')) instanceof \NF\Modules\Moderation\Moderation ? '<div class="text-end">'.$moderation->report_button('gallery_image', (int) $image_id, url('gallery/image/'.$image_id.'/'.url_title($title))).'</div>' : ''))
								->footer('<a href="'.url($album).'">'.icon('fas fa-arrow-left').' '.$this->lang('Retour à l\'album %s', $gallery_title).'</a>', 'left')
					)))
					->append_if(($comments = $this->module('comments')) && $comments->is_enabled(), function() use (&$comments, $image_id){
						return $this->row($this->col($comments('gallery', $image_id)));
					});
	}
}
