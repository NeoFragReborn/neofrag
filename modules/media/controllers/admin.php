<?php
namespace NF\Modules\Media\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Media\Media;

class Admin extends Controller_Module
{
	public function index($medias, $count, $total_size, $filters)
	{
		$this->title($this->lang('Bibliothèque média'))->icon('fas fa-photo-video');

		// Stat cards
		$stats = '<div class="nf-stats-grid">'
			.'<div class="nf-stat-card"><div class="nf-stat-label"><i class="fas fa-file"></i> '.$this->lang('Fichiers').'</div><div class="nf-stat-value">'.number_format((int)$count, 0, ',', ' ').'</div></div>'
			.'<div class="nf-stat-card"><div class="nf-stat-label"><i class="fas fa-database"></i> '.$this->lang('Stockage utilisé').'</div><div class="nf-stat-value">'.Media::format_size($total_size).'</div></div>'
			.'</div>';

		// Header
		$header_left  = '<span><i class="fas fa-photo-video"></i> '.$this->lang('Bibliothèque').' <small class="text-muted" style="font-weight:400;font-size:12px;margin-left:8px;">'.(int)$count.' '.$this->lang('fichier|fichiers', (int)$count).'</small></span>';
		$header_right = '<span><a class="btn btn-primary btn-sm" href="'.url('admin/media/upload').'"><i class="fas fa-upload"></i> '.$this->lang('Uploader des fichiers').'</a></span>';

		// Barre recherche / filtre (GET). Préservée à travers la pagination par get_pagination().
		$toolbar = '';
		if ($count > 0 || !empty($filters['active']))
		{
			$form_action  = url($this->module->pagination->get_url());
			$type_options = ['' => $this->lang('Tous les types'), 'image' => $this->lang('Images'), 'video' => $this->lang('Vidéos'), 'audio' => $this->lang('Audio'), 'pdf' => $this->lang('PDF')];

			$toolbar  = '<form method="get" action="'.$form_action.'" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;padding:12px 16px;border-bottom:1px solid var(--nf-border);">';
			$toolbar .= '<input type="text" name="q" value="'.htmlspecialchars($filters['q']).'" class="form-control form-control-sm" placeholder="'.htmlspecialchars($this->lang('Rechercher par nom ou titre…'), ENT_QUOTES).'" style="max-width:280px;">';
			$toolbar .= '<select name="type" class="form-control form-control-sm" style="width:auto;">';
			foreach ($type_options as $val => $label)
			{
				$toolbar .= '<option value="'.$val.'"'.($filters['type'] === $val ? ' selected' : '').'>'.htmlspecialchars($label).'</option>';
			}
			$toolbar .= '</select>';
			$toolbar .= '<button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> '.$this->lang('Filtrer').'</button>';
			if (!empty($filters['active']))
			{
				$toolbar .= '<a href="'.$form_action.'" class="btn btn-sm btn-light"><i class="fas fa-times"></i> '.$this->lang('Réinitialiser').'</a>';
				$toolbar .= '<span class="text-muted" style="font-size:12px;margin-left:auto;">'.$this->lang('%d résultat|%d résultats', (int)$filters['matched'], (int)$filters['matched']).(!empty($filters['capped']) ? '+' : '').'</span>';
			}
			$toolbar .= '</form>';
		}

		if (empty($medias)) {
			$body = !empty($filters['active'])
				? '<div class="nf-empty"><i class="fas fa-search"></i>'.$this->lang('Aucun fichier ne correspond à ces critères.').'</div>'
				: '<div class="nf-empty"><i class="fas fa-photo-video"></i>'.$this->lang('Aucun fichier. Clique sur <strong>Uploader des fichiers</strong> pour commencer.').'</div>';
		} else {
			$body = '<div style="padding:16px;"><div class="row">';
			foreach ($medias as $m) {
				$url_file = url('upload/media/'.$m['filename']);
				$is_img = Media::is_image($m['mime_type']);

				$body .= '<div class="col-6 col-md-3 col-lg-2 mb-3">';
				$body .= '<div class="card" style="margin-bottom:0;">';

				if ($is_img) {
					$body .= '<a href="'.$url_file.'" target="_blank"><img src="'.$url_file.'" style="width:100%;height:110px;object-fit:cover;display:block;border-bottom:1px solid var(--nf-border);" loading="lazy"></a>';
				} else {
					$icon = strpos($m['mime_type'], 'pdf') !== FALSE ? 'far fa-file-pdf'
						: (strpos($m['mime_type'], 'video') !== FALSE ? 'far fa-file-video'
						: (strpos($m['mime_type'], 'audio') !== FALSE ? 'far fa-file-audio'
						: 'far fa-file'));
					$body .= '<div style="padding:30px 0;text-align:center;background:var(--nf-surface-2);border-bottom:1px solid var(--nf-border);"><i class="'.$icon.'" style="font-size:36px;color:var(--nf-text-muted);"></i></div>';
				}

				$body .= '<div style="padding:10px;">';
				$body .= '<div style="font-size:12.5px;font-weight:500;color:var(--nf-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="'.htmlspecialchars($m['original_name']).'">'.htmlspecialchars($m['original_name']).'</div>';
				if (!empty($m['title'])) $body .= '<div style="font-size:11.5px;color:var(--nf-text);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:1px;" title="'.htmlspecialchars($m['title']).'"><i class="fas fa-tag" style="font-size:9px;opacity:.6;"></i> '.htmlspecialchars($m['title']).'</div>';
				$body .= '<div style="font-size:11px;color:var(--nf-text-muted);font-feature-settings:\'tnum\';margin-top:2px;">'.Media::format_size($m['size_bytes']);
				if ($m['width'] && $m['height']) $body .= ' · '.(int)$m['width'].'×'.(int)$m['height'];
				$body .= '</div>';
				$body .= '<input class="form-control form-control-sm" type="text" readonly value="'.$url_file.'" onclick="this.select()" style="margin-top:6px;font-size:11px;">';
				$body .= '<a class="btn btn-sm btn-outline-secondary btn-block" href="'.url('admin/media/edit/'.$m['id']).'" style="margin-top:6px;"><i class="far fa-edit"></i> '.$this->lang('Éditer').'</a>';
				$body .= '<a class="btn btn-sm btn-outline-danger btn-block" href="'.url('admin/media/delete/'.$m['id']).'" data-confirm="'.htmlspecialchars($this->lang('Supprimer ?'), ENT_QUOTES).'" style="margin-top:6px;"><i class="far fa-trash-alt"></i> '.$this->lang('Supprimer').'</a>';
				$body .= '</div>';
				$body .= '</div></div>';
			}
			$body .= '</div></div>';
		}

		$pagination = (string)$this->module->pagination->get_pagination();
		if ($pagination !== '')
		{
			$pagination = '<div style="padding:12px 16px;border-top:1px solid var(--nf-border);text-align:center;">'.$pagination.'</div>';
		}

		return $stats.'<div class="card"><div class="card-header">'.$header_left.$header_right.'</div>'.$toolbar.$body.$pagination.'</div>';
	}

	public function _upload()
	{
		$this->title($this->lang('Uploader des fichiers'))->icon('fas fa-upload')->breadcrumb();

		$message = '';
		$uploaded = [];
		$errors = [];

		if (!empty($_FILES['files']) && is_array($_FILES['files']['name']))
		{
			$files = $_FILES['files'];
			$count = count($files['name']);

			for ($i = 0; $i < $count; $i++)
			{
				if ($files['error'][$i] !== UPLOAD_ERR_OK)
				{
					if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
					$errors[] = $this->lang('%s : erreur upload (code %d)', $files['name'][$i], $files['error'][$i]);
					continue;
				}

				$tmp = $files['tmp_name'][$i];
				$orig_name = $files['name'][$i];
				$size = $files['size'][$i];

				if ($size > Media::MAX_SIZE)
				{
					$errors[] = $this->lang('%s : trop volumineux (%s > %s)', $orig_name, Media::format_size($size), Media::format_size(Media::MAX_SIZE));
					continue;
				}

				$mime = detect_mime_type($tmp);
				if (!in_array($mime, Media::ALLOWED_MIMES, TRUE))
				{
					$errors[] = $this->lang('%s : type non autorisé (%s)', $orig_name, $mime);
					continue;
				}

				// Génère nom unique
				$ext = pathinfo($orig_name, PATHINFO_EXTENSION);
				$ext = preg_replace('/[^a-z0-9]/i', '', $ext);
				$filename = bin2hex(random_bytes(8)).'-'.preg_replace('/[^a-z0-9._-]/i', '_', pathinfo($orig_name, PATHINFO_FILENAME));
				$filename = substr($filename, 0, 100).'.'.$ext;

				$dest = 'upload/media/'.$filename;
				if (!@move_uploaded_file($tmp, $dest))
				{
					$errors[] = $this->lang('%s : impossible d\'écrire dans upload/media/. Vérifie les permissions.', $orig_name);
					continue;
				}

				$width = NULL; $height = NULL;
				if (Media::is_image($mime) && ($img = @getimagesize($dest)))
				{
					$width = $img[0]; $height = $img[1];
				}

				NeoFrag()->db->insert('nf_media', [
					'filename'      => $filename,
					'original_name' => $orig_name,
					'mime_type'     => $mime,
					'size_bytes'    => (int)$size,
					'width'         => $width,
					'height'        => $height,
					'user_id'       => $this->user->id
				]);
				$uploaded[] = $orig_name;
			}

			if (!empty($uploaded))
			{
				$nb = count($uploaded);
				notify($this->lang('%d fichier uploadé.|%d fichiers uploadés.', $nb, $nb));
			}

			if (!empty($errors))
			{
				$message = '<div class="alert alert-warning"><strong>'.$this->lang('Erreurs :').'</strong><ul class="mb-0">';
				foreach ($errors as $e)
				{
					$message .= '<li>'.htmlspecialchars($e).'</li>';
				}
				$message .= '</ul></div>';
			}

			if (empty($errors))
			{
				redirect('admin/media');
			}
		}

		$body = $message;
		$body .= '<form method="post" enctype="multipart/form-data" action="'.url('admin/media/upload').'">';
		$body .= '<div class="form-group">';
		$body .= '<label>'.$this->lang('Sélectionne un ou plusieurs fichiers').'</label>';
		$body .= '<input type="file" name="files[]" class="form-control-file" multiple required>';
		$body .= '<small class="form-text text-muted">';
		$body .= $this->lang('Taille max : %s par fichier. Types autorisés : images (JPG, PNG, GIF, WebP, SVG), PDF, vidéos (MP4, WebM), audio (MP3, OGG), ZIP, TXT, MD, JSON.', Media::format_size(Media::MAX_SIZE));
		$body .= '</small>';
		$body .= '</div>';
		$body .= '<button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> '.$this->lang('Uploader').'</button>';
		$body .= ' <a class="btn btn-secondary" href="'.url('admin/media').'">'.$this->lang('Annuler').'</a>';
		$body .= '</form>';

		return $this->admin_back('admin/media', $this->lang('Médias')).$this->admin_card('fas fa-upload', $this->lang('Upload'), $body);
	}

	public function _edit($m)
	{
		$this	->title($this->lang('Bibliothèque média'))->icon('fas fa-photo-video')
				->subtitle($this->lang('Éditer : %s', $m['original_name']))
				->form()
				->add_rules([
					'title'       => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => (string)$m['title'], 'description' => $this->lang('Texte alternatif et légende affichés dans la galerie publique.')],
					'description' => ['label' => $this->lang('Description'), 'type' => 'textarea', 'value' => (string)$m['description']]
				])
				->add_back('admin/media')
				->add_submit($this->lang('Enregistrer'));

		if ($this->form()->is_valid($post))
		{
			NeoFrag()->db	->where('id', (int)$m['id'])
							->update('nf_media', [
								'title'       => $post['title'],
								'description' => $post['description']
							]);

			notify($this->lang('Métadonnées enregistrées.'));
			redirect_back('admin/media');
		}

		$url_file = url('upload/media/'.$m['filename']);
		if (Media::is_image($m['mime_type']))
		{
			$preview = '<a href="'.$url_file.'" target="_blank"><img src="'.$url_file.'" style="max-width:100%;max-height:240px;border:1px solid var(--nf-border);border-radius:4px;display:block;margin-bottom:16px;"></a>';
		}
		else
		{
			$preview = '<div style="margin-bottom:16px;"><a href="'.$url_file.'" target="_blank"><i class="far fa-file"></i> '.htmlspecialchars($m['original_name']).'</a></div>';
		}
		$meta = '<p class="text-muted" style="font-size:12px;">'.Media::format_size($m['size_bytes']);
		if ($m['width'] && $m['height']) $meta .= ' · '.(int)$m['width'].'×'.(int)$m['height'];
		$meta .= ' · '.htmlspecialchars($m['mime_type']).'</p>';

		return $this->panel()
					->heading($this->lang('Éditer : %s', $m['original_name']), 'fas fa-edit')
					->body($preview.$meta.$this->form()->display())
					->size('col-12');
	}

	public function _delete($m)
	{
		$path = 'upload/media/'.$m['filename'];
		if (file_exists($path)) @unlink($path);
		NeoFrag()->db->where('id', $m['id'])->delete('nf_media');
		notify($this->lang('Fichier supprimé.'));
		redirect('admin/media');
	}
}
