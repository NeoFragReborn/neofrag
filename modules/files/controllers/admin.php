<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: HiddenCMS — porté sur NeoFrag Reborn
 */

namespace NF\Modules\Files\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	private function root()
	{
		$root = NEOFRAG_CMS.'/upload/files';
		dir_create($root);

		return str_replace('\\', '/', realpath($root));
	}

	private function normalize($path)
	{
		$path = trim(str_replace('\\', '/', (string)$path), '/');

		if ($path === '')
		{
			return '';
		}

		$parts = [];

		foreach (explode('/', $path) as $part)
		{
			$part = trim($part);

			if ($part === '' || $part === '.')
			{
				continue;
			}

			if ($part === '..')
			{
				return NULL;
			}

			$parts[] = $part;
		}

		return implode('/', $parts);
	}

	private function full_path($path)
	{
		$path = $this->normalize($path);

		if ($path === NULL)
		{
			return NULL;
		}

		$root = $this->root();
		$full = $root.($path !== '' ? '/'.$path : '');
		$check = file_exists($full) ? realpath($full) : realpath(dirname($full));

		if (!$check)
		{
			return NULL;
		}

		$check = str_replace('\\', '/', $check);

		if (stripos($check, $root) !== 0)
		{
			return NULL;
		}

		return $full;
	}

	private function clean_name($name)
	{
		$name = trim(str_replace(['/', '\\'], '', (string)$name));

		return in_array($name, ['', '.', '..'], TRUE) ? '' : $name;
	}

	private function selected_items()
	{
		$items = [];

		foreach ((array)@$_POST['paths'] as $path)
		{
			$path = (string)$path;

			if (strpos($path, 'file:') === 0)
			{
				$id = (int)substr($path, 5);

				if ($id > 0)
				{
					$items[] = ['type' => 'file', 'id' => $id];
				}
			}
			else if (strpos($path, 'dir:') === 0)
			{
				$path = $this->normalize(substr($path, 4));

				if ($path !== NULL && $path !== '')
				{
					$items[] = ['type' => 'dir', 'path' => $path];
				}
			}
		}

		return $items;
	}

	private function upload_dir($dir = '')
	{
		$dir = $this->normalize($dir);

		return trim('files'.($dir ? '/'.$dir : ''), '/');
	}

	private function normalize_db_path($path)
	{
		$path = trim(str_replace('\\', '/', (string)$path));

		if (strpos($path, './') === 0)
		{
			$path = substr($path, 2);
		}

		return trim($path, '/');
	}

	private function file_relative_path($file)
	{
		$path = $this->normalize_db_path($file['path']);
		$prefix = 'upload/files/';

		return strpos($path, $prefix) === 0 ? substr($path, strlen($prefix)) : NULL;
	}

	private function file_dir($file)
	{
		$path = $this->file_relative_path($file);

		if ($path === NULL || strpos($path, '/') === FALSE)
		{
			return '';
		}

		return dirname($path);
	}

	private function file_full_path($file)
	{
		$path = $this->normalize_db_path($file['path']);
		$full = str_replace('\\', '/', NEOFRAG_CMS.'/'.$path);
		$root = $this->root();
		$check = file_exists($full) ? realpath($full) : NULL;

		if (!$check)
		{
			return NULL;
		}

		$check = str_replace('\\', '/', $check);

		return stripos($check, $root) === 0 ? $full : NULL;
	}

	private function file_public_slug($file)
	{
		return pathinfo(basename($this->normalize_db_path($file['path'])), PATHINFO_FILENAME);
	}

	private function file_records()
	{
		return $this->db	->select('id', 'user_id', 'name', 'path', 'date')
						->from('nf_file')
						->order_by('name')
						->get(FALSE);
	}

	private function file_record($id)
	{
		return $this->db	->select('id', 'user_id', 'name', 'path', 'date')
						->from('nf_file')
						->where('id', (int)$id)
						->row(FALSE);
	}

	private function directory_label($dir)
	{
		return $dir === '' ? 'Racine' : $dir;
	}

	private function directory_id($dir, $create = TRUE)
	{
		$dir = $this->normalize($dir);

		if ($dir === NULL)
		{
			return NULL;
		}

		if (($directory_id = $this->db->select('directory_id')->from('nf_files_directories')->where('path', $dir)->row()))
		{
			return (int)$directory_id;
		}

		if (!$create)
		{
			return NULL;
		}

		$directory_id = $this->db->insert('nf_files_directories', [
			'path' => $dir
		]);

		$this->ensure_access('files', 'read_directory', $directory_id, 'directory');

		return (int)$directory_id;
	}

	// === Permissions par-contenu (ACL de lecture) — NeoFrag Reborn ============
	// nf_role_permissions (role_id, permission « files.<action> », scope_id, authorized). scope_id =
	// directory_id (nf_files_directories) pour read_directory, file_id (nf_file) pour read_file.

	private function has_access($action, $id)
	{
		return (bool) $this->db	->select('role_id')
								->from('nf_role_permissions')
								->where('permission', 'files.'.$action)
								->where('scope_id', (int)$id)
								->row();
	}

	private function ensure_access($module, $action, $id, $type)
	{
		// Seed les défauts (permissions()[$type]['init']) une seule fois, comme gallery->access->init().
		if (!$this->has_access($action, $id))
		{
			$this->access->init($module, $type, (int)$id);
		}

		return $this;
	}

	private function copy_access($source_action, $source_id, $target_action, $target_id)
	{
		$this->ensure_access('files', $source_action, $source_id, $source_action === 'read_directory' ? 'directory' : 'file');
		$this->ensure_access('files', $target_action, $target_id, $target_action === 'read_directory' ? 'directory' : 'file');

		// Héritage : un fichier/dossier reçoit les autorisations de son dossier parent (sinon un upload dans
		// un dossier restreint serait public par défaut). On remplace les droits de la cible par ceux de la source.
		$this->db	->where('permission', 'files.'.$target_action)
					->where('scope_id', (int)$target_id)
					->delete('nf_role_permissions');

		foreach ($this->db->select('role_id', 'authorized')->from('nf_role_permissions')->where('permission', 'files.'.$source_action)->where('scope_id', (int)$source_id)->get(FALSE) as $permission)
		{
			$this->db->insert('nf_role_permissions', [
				'role_id'    => $permission['role_id'],
				'permission' => 'files.'.$target_action,
				'scope_id'   => (int)$target_id,
				'authorized' => $permission['authorized']
			]);
		}

		$this->access->reload();

		return $this;
	}

	private function delete_access($action, $id)
	{
		$this->db	->where('permission', 'files.'.$action)
					->where('scope_id', (int)$id)
					->delete('nf_role_permissions');

		$this->access->reload();

		return $this;
	}

	private function current_dir()
	{
		$dir = $this->normalize(isset($_GET['dir']) ? $_GET['dir'] : '');
		$full = $dir !== NULL ? $this->full_path($dir) : NULL;

		if ($dir === NULL || !$full || !is_dir($full))
		{
			return '';
		}

		return $dir;
	}

	private function index_path($dir = '')
	{
		$dir = $this->normalize($dir);

		return 'admin/files'.($dir ? '?dir='.rawurlencode($dir) : '');
	}

	private function index_url($dir = '')
	{
		return url($this->index_path($dir));
	}

	private function unique_path($dir, $name)
	{
		$name = $this->clean_name($name);
		$path = $dir.($dir !== '' ? '/' : '').$name;
		$full = $this->full_path($path);

		if (!$full || !file_exists($full))
		{
			return [$path, $full];
		}

		$extension = pathinfo($name, PATHINFO_EXTENSION);
		$base = $extension ? substr($name, 0, -strlen($extension) - 1) : $name;
		$i = 2;

		do
		{
			$candidate = $base.'-'.$i.($extension ? '.'.$extension : '');
			$path = $dir.($dir !== '' ? '/' : '').$candidate;
			$full = $this->full_path($path);
			$i++;
		}
		while ($full && file_exists($full));

		return [$path, $full];
	}

	private function handle_post($dir)
	{
		if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !post('files_action'))
		{
			return;
		}

		$action = post('files_action');

		if ($action === 'upload')
		{
			if (!$this->is_authorized('add_files'))
			{
				$this->error->unauthorized();
			}

			$count = 0;

			foreach ((array)@$_FILES['files']['name'] as $i => $name)
			{
				if (!empty($_FILES['files']['error'][$i]) || empty($_FILES['files']['tmp_name'][$i]))
				{
					continue;
				}

				$name = $this->clean_name($name);

				if ($name === '')
				{
					continue;
				}

				if (($file = NeoFrag()->model2('file')->static_uploaded_file($_FILES['files'], $this->upload_dir($dir), NULL, $i)) && $file->id)
				{
					$this	->ensure_access('files', 'read_file', $file->id, 'file')
							->copy_access('read_directory', $this->directory_id($dir), 'read_file', $file->id);

					$count++;
				}
			}

			notify($count ? $this->lang('%d fichier ajouté|%d fichiers ajoutés', $count, $count) : $this->lang('Aucun fichier ajouté'), $count ? 'success' : 'warning');
			redirect($this->index_path($dir));
		}

		if ($action === 'mkdir')
		{
			if (!$this->is_authorized('add_files'))
			{
				$this->error->unauthorized();
			}

			$name = $this->clean_name(post('name'));
			$target_dir = $this->normalize(post('target_dir'));

			if ($target_dir === NULL || !is_dir($this->full_path($target_dir)) || $name === '')
			{
				notify($this->lang('Nom de dossier invalide'), 'danger');
				redirect($this->index_path($dir));
			}

			list($relative, $target) = $this->unique_path($target_dir, $name);
			dir_create($target);

			$this->copy_access('read_directory', $this->directory_id($target_dir), 'read_directory', $this->directory_id($relative));

			notify($this->lang('Dossier créé'));
			redirect($this->index_path($dir));
		}

		if ($action === 'delete')
		{
			if (!$this->is_authorized('delete_files'))
			{
				$this->error->unauthorized();
			}

			$path = $this->normalize(post('path'));
			$full = $path !== '' ? $this->full_path($path) : NULL;

			if (!$full || !file_exists($full))
			{
				notify($this->lang('Élément introuvable'), 'danger');
				redirect($this->index_path($dir));
			}

			is_dir($full) ? dir_remove($full) : unlink($full);
			notify($this->lang('Élément supprimé'));
			redirect($this->index_path($dir));
		}

		if ($action === 'delete_selected')
		{
			if (!$this->is_authorized('delete_files'))
			{
				$this->error->unauthorized();
			}

			$count = 0;

			foreach ($this->selected_items() as $item)
			{
				if ($item['type'] === 'file')
				{
					if (($file = NeoFrag()->model2('file', $item['id'])) && $file())
					{
						$file->delete();
						$this->delete_access('read_file', $item['id']);
						$count++;
					}
				}
				else if ($item['type'] === 'dir')
				{
					$full = $this->full_path($item['path']);

					if (!$full || !is_dir($full))
					{
						continue;
					}

					$prefix = $this->upload_dir($item['path']).'/';

					foreach ($this->file_records() as $file)
					{
						$path = $this->normalize_db_path($file['path']);

						if (strpos($path, 'upload/'.$prefix) === 0)
						{
							NeoFrag()->model2('file', $file['id'])->delete();
						}
					}

					foreach ($this->db->select('directory_id', 'path')->from('nf_files_directories')->get(FALSE) as $directory)
					{
						if ($directory['path'] === $item['path'] || strpos($directory['path'], $item['path'].'/') === 0)
						{
							$this->delete_access('read_directory', $directory['directory_id']);
							$this->db->where('directory_id', $directory['directory_id'])->delete('nf_files_directories');
						}
					}

					dir_remove($full);
					$count++;
				}
			}

			notify($count ? $this->lang('%d élément supprimé|%d éléments supprimés', $count, $count) : $this->lang('Aucun élément supprimé'), $count ? 'success' : 'warning');
			redirect($this->index_path($dir));
		}

		if ($action === 'move')
		{
			if (!$this->is_authorized('modify_files'))
			{
				$this->error->unauthorized();
			}

			$source = $this->normalize(post('path'));
			$target_dir = $this->normalize(post('target_dir'));
			$name = $this->clean_name(post('name'));
			$source_full = $source !== '' ? $this->full_path($source) : NULL;

			if (!$source_full || !file_exists($source_full) || $target_dir === NULL || !is_dir($this->full_path($target_dir)) || $name === '')
			{
				notify($this->lang('Déplacement impossible'), 'danger');
				redirect($this->index_path($dir));
			}

			$target = $target_dir.($target_dir !== '' ? '/' : '').$name;

			if (is_dir($source_full) && ($target === $source || strpos($target.'/', $source.'/') === 0))
			{
				notify($this->lang('Impossible de déplacer un dossier dans lui-même'), 'danger');
				redirect($this->index_path($dir));
			}

			$target_full = $this->full_path($target);

			if (!$target_full || file_exists($target_full))
			{
				notify($this->lang('Un élément existe déjà à cet emplacement'), 'danger');
				redirect($this->index_path($dir));
			}

			rename($source_full, $target_full);
			notify($this->lang('Élément déplacé'));
			redirect($this->index_path($target_dir));
		}

		if ($action === 'move_selected')
		{
			if (!$this->is_authorized('modify_files'))
			{
				$this->error->unauthorized();
			}

			$items = $this->selected_items();
			$target_dir = $this->normalize(post('target_dir'));

			if (empty($items) || $target_dir === NULL || !is_dir($this->full_path($target_dir)))
			{
				notify($this->lang('Déplacement impossible'), 'danger');
				redirect($this->index_path($dir));
			}

			$count = 0;
			$single_name = count($items) === 1 ? $this->clean_name(post('name')) : '';

			foreach ($items as $item)
			{
				if ($item['type'] === 'file')
				{
					$file = $this->file_record($item['id']);

					if (!$file || !($source_full = $this->file_full_path($file)))
					{
						continue;
					}

					$name = $single_name ?: $file['name'];
					$target = $this->upload_dir($target_dir).'/'.basename($this->normalize_db_path($file['path']));
					$target_full = str_replace('\\', '/', NEOFRAG_CMS.'/upload/'.$target);

					if (file_exists($target_full) && realpath($target_full) !== realpath($source_full))
					{
						continue;
					}

					dir_create(dirname($target_full));

					if ($target_full === $source_full || rename($source_full, $target_full))
					{
						NeoFrag()->model2('file', $file['id'])
							->set('name', $name)
							->set('path', 'upload/'.$target)
							->update();

						$this->copy_access('read_directory', $this->directory_id($target_dir), 'read_file', $file['id']);

						$count++;
					}
				}
				else if ($item['type'] === 'dir')
				{
					$source = $item['path'];
					$source_full = $this->full_path($source);

					if (!$source_full || !is_dir($source_full))
					{
						continue;
					}

					$name = $single_name ?: basename($source);
					$target = $target_dir.($target_dir !== '' ? '/' : '').$name;

					if ($target === $source || strpos($target.'/', $source.'/') === 0)
					{
						continue;
					}

					$target_full = $this->full_path($target);

					if (!$target_full || file_exists($target_full))
					{
						continue;
					}

					if (rename($source_full, $target_full))
					{
						$source_prefix = 'upload/'.$this->upload_dir($source).'/';
						$target_prefix = 'upload/'.$this->upload_dir($target).'/';
						$directory_prefix = $source.'/';

						foreach ($this->file_records() as $file)
						{
							$path = $this->normalize_db_path($file['path']);

							if (strpos($path, $source_prefix) === 0)
							{
								NeoFrag()->model2('file', $file['id'])
									->set('path', $target_prefix.substr($path, strlen($source_prefix)))
									->update();
							}
						}

						foreach ($this->db->select('directory_id', 'path')->from('nf_files_directories')->get(FALSE) as $directory)
						{
							if ($directory['path'] === $source || strpos($directory['path'], $directory_prefix) === 0)
							{
								$this->db	->where('directory_id', $directory['directory_id'])
											->update('nf_files_directories', [
												'path' => $target.substr($directory['path'], strlen($source))
											]);
							}
						}

						$count++;
					}
				}
			}

			notify($count ? $this->lang('%d élément déplacé|%d éléments déplacés', $count, $count) : $this->lang('Aucun élément déplacé'), $count ? 'success' : 'warning');
			redirect($this->index_path($target_dir));
		}
	}

	private function directories($base = '')
	{
		$base = $this->normalize($base);
		$full = $this->full_path($base);
		$dirs = ['' => '/'];

		if (!$full || !is_dir($full))
		{
			return $dirs;
		}

		$scan = function($dir, $relative) use (&$scan, &$dirs){
			foreach (scandir($dir) as $entry)
			{
				if (in_array($entry, ['.', '..'], TRUE) || !is_dir($dir.'/'.$entry))
				{
					continue;
				}

				$path = $relative.($relative !== '' ? '/' : '').$entry;
				$dirs[$path] = $path;
				$scan($dir.'/'.$entry, $path);
			}
		};

		$scan($this->root(), '');

		return $dirs;
	}

	private function immediate_directories($base = '')
	{
		$base = $this->normalize($base);
		$full = $this->full_path($base);
		$dirs = [];

		if (!$full || !is_dir($full))
		{
			return $dirs;
		}

		foreach (scandir($full) as $entry)
		{
			if (in_array($entry, ['.', '..'], TRUE) || !is_dir($full.'/'.$entry))
			{
				continue;
			}

			$path = $base.($base !== '' ? '/' : '').$entry;
			$dirs[$path] = $entry;
		}

		return $dirs;
	}

	private function render_tree_branch($base, $current)
	{
		$directories = $this->immediate_directories($base);

		if (empty($directories))
		{
			return '';
		}

		$html = '<ul>';

		foreach ($directories as $path => $title)
		{
			$html .= '<li class="files-tree-node">'
					.'<a class="'.($path === $current ? 'active' : '').'" href="'.$this->index_url($path).'">'
						.icon('far fa-folder').' '.utf8_htmlentities($title)
					.'</a>'
					.$this->render_tree_branch($path, $current)
				.'</li>';
		}

		return $html.'</ul>';
	}

	private function render_tree($current)
	{
		return '<div class="files-tree-heading">'.icon('far fa-folder-open').' '.$this->lang('Dossiers').'</div>'
			.'<ul class="files-tree">'
				.'<li class="files-tree-node files-tree-root">'
					.'<a class="'.($current === '' ? 'active' : '').'" href="'.$this->index_url().'">'
						.icon('far fa-folder').' '.$this->lang('Racine')
					.'</a>'
					.$this->render_tree_branch('', $current)
				.'</li>'
			.'</ul>';
	}

	private function render_breadcrumb($dir)
	{
		$html = '<nav class="files-breadcrumb">'.icon('fas fa-map-marker-alt').'<a href="'.$this->index_url().'">'.$this->lang('Racine').'</a>';
		$path = '';

		foreach (array_filter(explode('/', $dir)) as $part)
		{
			$path .= ($path !== '' ? '/' : '').$part;
			$html .= '<span>/</span><a href="'.$this->index_url($path).'">'.utf8_htmlentities($part).'</a>';
		}

		return $html.'<span class="files-breadcrumb-access">'.$this->button_access($this->directory_id($dir), 'directory', 'files', $this->lang('Permissions de lecture du dossier')).'</span></nav>';
	}

	private function directory_value($dir)
	{
		return $dir === '' ? '/' : $dir;
	}

	private function render_directory_suggestions()
	{
		$html = '<datalist id="files-directory-paths">';

		foreach ($this->directories() as $path => $label)
		{
			$html .= '<option value="'.utf8_htmlentities($this->directory_value($path)).'">';
		}

		return $html.'</datalist>';
	}

	private function render_path_input($selected = '', $name = 'target_dir')
	{
		return '<input class="form-control form-control-sm files-path-input" type="text" name="'.$name.'" list="files-directory-paths" value="'.utf8_htmlentities($this->directory_value($selected)).'" placeholder="'.$this->lang('Chemin du dossier').'">';
	}

	private function render_items($dir)
	{
		$full = $this->full_path($dir);
		$items = [];

		foreach (scandir($full) as $entry)
		{
			if (in_array($entry, ['.', '..'], TRUE))
			{
				continue;
			}

			$path = $dir.($dir !== '' ? '/' : '').$entry;
			$item_full = $this->full_path($path);

			if (!$item_full || !is_dir($item_full))
			{
				continue;
			}

			$items[] = [
				'name' => $entry,
				'path' => $path,
				'dir'  => TRUE,
				'size' => NULL,
				'date' => filemtime($item_full),
				'type' => 'dir',
				'id'   => $this->directory_id($path)
			];
		}

		foreach ($this->file_records() as $file)
		{
			if ($this->file_dir($file) !== $dir || !($full_path = $this->file_full_path($file)))
			{
				continue;
			}

			if (!$this->has_access('read_file', $file['id']))
			{
				$this->copy_access('read_directory', $this->directory_id($dir), 'read_file', $file['id']);
			}

			$items[] = [
				'name' => $file['name'],
				'path' => $this->file_relative_path($file),
				'dir'  => FALSE,
				'size' => filesize($full_path),
				'date' => strtotime($file['date']),
				'type' => 'file',
				'id'   => $file['id'],
				'slug' => $this->file_public_slug($file)
			];
		}

		usort($items, function($a, $b){
			if ($a['dir'] !== $b['dir'])
			{
				return $a['dir'] ? -1 : 1;
			}

			return strnatcasecmp((string) $a['name'], (string) $b['name']);
		});

		if (empty($items))
		{
			return '<div class="files-empty">'.icon('far fa-folder-open').'<span>'.$this->lang('Ce dossier est vide').'</span></div>';
		}

		$rows = '';

		foreach ($items as $item)
		{
			$name = utf8_htmlentities($item['name']);
			$icon = $item['dir'] ? 'far fa-folder' : 'far fa-file';
			$title = $item['dir'] ? '<a href="'.$this->index_url($item['path']).'">'.$name.'</a>' : '<a href="'.url('files/'.$item['slug']).'" target="_blank" rel="noopener">'.$name.'</a>';
			$size = $item['dir'] ? '-' : human_size($item['size']);
			$date = nf_date_heure($item['date']);
			$value = $item['type'] === 'file' ? 'file:'.$item['id'] : 'dir:'.$item['path'];
			$access = $item['type'] === 'file' ? $this->button_access($item['id'], 'file', 'files', $this->lang('Permissions de lecture')) : $this->button_access($item['id'], 'directory', 'files', $this->lang('Permissions de lecture'));

			$rows .= '<tr data-file-row data-path="'.utf8_htmlentities($value).'" data-name="'.$name.'">'
					.'<td class="files-select"><input type="checkbox" class="files-select-item" value="'.utf8_htmlentities($value).'" data-name="'.$name.'"></td>'
					.'<td class="files-name">'.icon($icon).' '.$title.'</td>'
					.'<td class="files-size">'.$size.'</td>'
					.'<td class="files-date">'.$date.'</td>'
					.'<td class="files-access">'.$access.'</td>'
				.'</tr>';
		}

		return '<div class="table-responsive">'
				.'<table class="table table-hover table-sm files-table mb-0">'
					.'<thead><tr>'
						.'<th class="files-select"><input type="checkbox" class="files-select-all"></th>'
						.'<th>'.$this->lang('Nom').'</th>'
						.'<th>'.$this->lang('Taille').'</th>'
						.'<th>'.$this->lang('Date').'</th>'
						.'<th class="text-end">'.$this->lang('Lecture').'</th>'
					.'</tr></thead>'
					.'<tbody>'.$rows.'</tbody>'
				.'</table>'
			.'</div>';
	}

	private function render_selection_actions()
	{
		if (!$this->is_authorized('modify_files') && !$this->is_authorized('delete_files'))
		{
			return '';
		}

		$buttons = '';

		if ($this->is_authorized('modify_files'))
		{
			$buttons .= '<button class="btn btn-sm btn-outline-secondary files-selection-action" type="button" data-bs-toggle="modal" data-bs-target="#files-move-modal" disabled>'
					.icon('fas fa-exchange-alt').' '.$this->lang('Déplacer')
				.'</button>';
		}

		if ($this->is_authorized('delete_files'))
		{
			$buttons .= '<button class="btn btn-sm btn-outline-danger files-selection-action" type="button" data-bs-toggle="modal" data-bs-target="#files-delete-modal" disabled>'
					.icon('far fa-trash-alt').' '.$this->lang('Supprimer')
				.'</button>';
		}

		return '<div class="files-selection-bar" data-files-selection>'
				.'<span class="files-selection-count">'.$this->lang('Aucun élément sélectionné').'</span>'
				.$buttons
			.'</div>';
	}

	private function render_toolbar($dir)
	{
		$upload = '';
		$mkdir = '';

		if ($this->is_authorized('add_files'))
		{
			$upload = '<button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#files-upload-modal">'
					.icon('fas fa-upload').' '.$this->lang('Ajouter des fichiers')
				.'</button>';

			$mkdir = '<button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#files-mkdir-modal">'
					.icon('fas fa-folder-plus').' '.$this->lang('Créer un dossier')
				.'</button>';
		}

		return '<div class="files-toolbar">'
				.'<div class="files-toolbar-main">'.$upload.$mkdir.'</div>'
				.$this->render_selection_actions()
			.'</div>';
	}

	private function render_mkdir_modal($dir)
	{
		if (!$this->is_authorized('add_files'))
		{
			return '';
		}

		return '<div class="modal fade files-mkdir-modal" id="files-mkdir-modal" tabindex="-1" role="dialog" aria-hidden="true">'
				.'<div class="modal-dialog" role="document">'
					.'<form class="modal-content files-mkdir-form" method="post" action="'.$this->index_url($dir).'">'
						.'<input type="hidden" name="files_action" value="mkdir">'
						.'<div class="modal-header">'
							.'<h5 class="modal-title">'.icon('fas fa-folder-plus').' '.$this->lang('Créer un dossier').'</h5>'
							.'<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="'.$this->lang('Fermer').'">'
							.'</button>'
						.'</div>'
						.'<div class="modal-body">'
							.'<div class="nf-field">'
								.'<label>'.$this->lang('Nom du dossier').'</label>'
								.'<input class="form-control" type="text" name="name" placeholder="'.$this->lang('Nouveau dossier').'" required>'
							.'</div>'
							.'<div class="nf-field mb-0">'
								.'<label>'.$this->lang('Chemin').'</label>'
								.$this->render_path_input($dir)
								.'<small class="form-text text-muted">'.$this->lang('Tapez pour afficher les dossiers existants').'</small>'
							.'</div>'
						.'</div>'
						.'<div class="modal-footer">'
							.'<button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">'.$this->lang('Annuler').'</button>'
							.'<button type="submit" class="btn btn-sm btn-primary">'.icon('fas fa-folder-plus').' '.$this->lang('Créer').'</button>'
						.'</div>'
					.'</form>'
				.'</div>'
			.'</div>';
	}

	private function render_upload_modal($dir)
	{
		if (!$this->is_authorized('add_files'))
		{
			return '';
		}

		return '<div class="modal fade files-upload-modal" id="files-upload-modal" tabindex="-1" role="dialog" aria-hidden="true">'
				.'<div class="modal-dialog modal-lg" role="document">'
					.'<form class="modal-content files-upload-form" method="post" action="'.$this->index_url($dir).'" enctype="multipart/form-data">'
						.'<input type="hidden" name="files_action" value="upload">'
						.'<div class="modal-header">'
							.'<h5 class="modal-title">'.icon('fas fa-upload').' '.$this->lang('Ajouter des fichiers').'</h5>'
							.'<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="'.$this->lang('Fermer').'">'
							.'</button>'
						.'</div>'
						.'<div class="modal-body">'
							.'<input class="files-upload-input" type="file" name="files[]" multiple>'
							.'<button class="files-upload-dropzone" type="button">'
								.'<span class="files-upload-dropzone-icon">'.icon('fas fa-cloud-upload-alt').'</span>'
								.'<strong>'.$this->lang('Glissez-déposez les fichiers ici').'</strong>'
								.'<small>'.$this->lang('ou cliquer pour choisir des fichiers').'</small>'
							.'</button>'
							.'<ul class="files-upload-list"></ul>'
						.'</div>'
						.'<div class="modal-footer">'
							.'<button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">'.$this->lang('Annuler').'</button>'
							.'<button type="submit" class="btn btn-sm btn-primary">'.icon('fas fa-upload').' '.$this->lang('Ajouter').'</button>'
						.'</div>'
					.'</form>'
				.'</div>'
			.'</div>';
	}

	private function render_move_modal($dir)
	{
		if (!$this->is_authorized('modify_files'))
		{
			return '';
		}

		return '<div class="modal fade files-move-modal" id="files-move-modal" tabindex="-1" role="dialog" aria-hidden="true">'
				.'<div class="modal-dialog" role="document">'
					.'<form class="modal-content files-selection-form" method="post" action="'.$this->index_url($dir).'">'
						.'<input type="hidden" name="files_action" value="move_selected">'
						.'<div class="files-selected-inputs"></div>'
						.'<div class="modal-header">'
							.'<h5 class="modal-title">'.icon('fas fa-exchange-alt').' '.$this->lang('Déplacer la sélection').'</h5>'
							.'<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="'.$this->lang('Fermer').'">'
							.'</button>'
						.'</div>'
						.'<div class="modal-body">'
							.'<p class="files-selected-summary text-muted"></p>'
							.'<div class="nf-field files-rename-field">'
								.'<label>'.$this->lang('Nom').'</label>'
								.'<input class="form-control" type="text" name="name">'
								.'<small class="form-text text-muted">'.$this->lang('Seulement quand un seul élément est sélectionné').'</small>'
							.'</div>'
							.'<div class="nf-field mb-0">'
								.'<label>'.$this->lang('Chemin de destination').'</label>'
								.$this->render_path_input($dir)
							.'</div>'
						.'</div>'
						.'<div class="modal-footer">'
							.'<button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">'.$this->lang('Annuler').'</button>'
							.'<button type="submit" class="btn btn-sm btn-primary">'.icon('fas fa-exchange-alt').' '.$this->lang('Déplacer').'</button>'
						.'</div>'
					.'</form>'
				.'</div>'
			.'</div>';
	}

	private function render_delete_modal($dir)
	{
		if (!$this->is_authorized('delete_files'))
		{
			return '';
		}

		return '<div class="modal fade files-delete-modal" id="files-delete-modal" tabindex="-1" role="dialog" aria-hidden="true">'
				.'<div class="modal-dialog" role="document">'
					.'<form class="modal-content files-selection-form" method="post" action="'.$this->index_url($dir).'">'
						.'<input type="hidden" name="files_action" value="delete_selected">'
						.'<div class="files-selected-inputs"></div>'
						.'<div class="modal-header">'
							.'<h5 class="modal-title">'.icon('far fa-trash-alt').' '.$this->lang('Supprimer la sélection').'</h5>'
							.'<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="'.$this->lang('Fermer').'">'
							.'</button>'
						.'</div>'
						.'<div class="modal-body">'
							.'<p class="files-selected-summary text-muted"></p>'
							.'<div class="alert alert-danger mb-0">'.$this->lang('Cette action est définitive.').'</div>'
						.'</div>'
						.'<div class="modal-footer">'
							.'<button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">'.$this->lang('Annuler').'</button>'
							.'<button type="submit" class="btn btn-sm btn-danger">'.icon('far fa-trash-alt').' '.$this->lang('Supprimer').'</button>'
						.'</div>'
					.'</form>'
				.'</div>'
			.'</div>';
	}

	public function index()
	{
		$this->title($this->lang('Fichiers'));
		$this->css('file_manager');
		$this->js('file_manager');

		$dir = $this->current_dir();
		$this->handle_post($dir);

		$content = '<div class="row files-manager">'
				.'<div class="col-md-3 files-sidebar"><div class="files-sidebar-card">'.$this->render_tree($dir).'</div></div>'
				.'<div class="col-md-9 files-content">'
					.$this->render_breadcrumb($dir)
					.$this->render_toolbar($dir)
					.$this->render_items($dir)
				.'</div>'
			.'</div>'
			.$this->render_directory_suggestions()
			.$this->render_upload_modal($dir)
			.$this->render_mkdir_modal($dir)
			.$this->render_move_modal($dir)
			.$this->render_delete_modal($dir);

		return $this->panel()
					->heading($this->lang('Gestionnaire de fichiers'), 'far fa-folder-open')
					->body($content, FALSE);
	}
}
