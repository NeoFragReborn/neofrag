<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Tools\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Api extends Controller_Module
{
	public function scss($action = 'reload')
	{
		$list_scss_files = function(){
			$files = [];

			dir_scan('.', function($file) use (&$files){
				if (preg_match('#\.scss$#', $file, $match))
				{
					$files[] = $file;
				}
			});

			return $files;
		};

		$cleanup_dir = function($dir) use (&$cleanup_dir){
			if (!is_dir($dir))
			{
				return;
			}

			foreach (scandir($dir) as $entry)
			{
				if ($entry === '.' || $entry === '..')
				{
					continue;
				}

				$path = $dir.'/'.$entry;

				if (is_dir($path))
				{
					$cleanup_dir($path);
				}
				else
				{
					unlink($path);
				}
			}

			rmdir($dir);
		};

		// Pré-processe tous les .scss du projet (PHP inline → SCSS pur) dans un dossier mirror temporaire.
		// Nécessaire car scssphp 1.x n'a plus l'équivalent de preprocessingFunction() de la 0.x,
		// et certains thèmes peuvent injecter du PHP dans les .scss pour les couleurs admin-customizables.
		$build_mirror = function($files){
			$mirror = sys_get_temp_dir().'/neofrag-scss-mirror-'.uniqid();
			mkdir($mirror, 0777, TRUE);

			foreach ($files as $file)
			{
				$dst = $mirror.'/'.$file;
				$dstDir = dirname($dst);

				if (!is_dir($dstDir))
				{
					mkdir($dstDir, 0777, TRUE);
				}

				ob_start();
				include $file;
				file_put_contents($dst, ob_get_clean());
			}

			return $mirror;
		};

		$compile = function($files) use ($build_mirror, $cleanup_dir){
			$results = [];
			$mirror  = $build_mirror($files);

			try
			{
				foreach ($files as $file)
				{
					if (preg_match('#/sass/((?!_)[a-z0-9_.-]+)\.scss$#', $file, $match))
					{
						$path      = preg_replace('#/sass/[^/]*?\.scss#', '', $file);
						$css       = $path.'/'.$match[1].'.css';
						$entryFile = $mirror.'/'.$file;

						try
						{
							$scss = new \ScssPhp\ScssPhp\Compiler();
							$scss->setOutputStyle(\ScssPhp\ScssPhp\OutputStyle::COMPRESSED);
							$scss->setSourceMap(\ScssPhp\ScssPhp\Compiler::SOURCE_MAP_FILE);
							$scss->setImportPaths($mirror.'/'.$path.'/sass');
							$scss->setSourceMapOptions([
								'sourceMapWriteTo' => $css.'.map',
								'sourceMapURL'     => $match[1].'.css.map',
								'sourceRoot'       => '/'
							]);

							$md5    = md5_file($css);
							$result = $scss->compileString(file_get_contents($entryFile), $entryFile);

							file_put_contents($css, $result->getCss());

							if ($map = $result->getSourceMap())
							{
								file_put_contents($css.'.map', $map);
							}

							if ($md5 != md5_file($css))
							{
								$results[] = $css;
							}
						}
						catch (\Exception $e)
						{
							echo "Error $file\n\t--> ".$e->getMessage()."\n";
						}
					}
				}
			}
			finally
			{
				$cleanup_dir($mirror);
			}

			return $results;
		};

		if ($action == 'reload')
		{
			foreach ($compile($list_scss_files()) as $file)
			{
				echo $file."\n";
			}

			$this->config('nf_version_css', time());

			return 'OK';
		}
		else if ($action == 'watch')
		{
			echo "Watching...\n";

			$files = [];
			$first = TRUE;

			while (TRUE)
			{
				$need_update = [];

				foreach ($scan = $list_scss_files() as $file)
				{
					if (!array_key_exists($file, $files))
					{
						$files[$file] = filemtime($file);
						$need_update['Added'][] = $file;
					}
					else if ($files[$file] != ($time = filemtime($file)))
					{
						$files[$file] = $time;
						$need_update['Updated'][] = $file;
					}
				}

				foreach (array_diff(array_keys($files), $scan) as $file)
				{
					$need_update['Removed'][] = $file;
					unset($files[$file]);
				}

				if ($need_update)
				{
					foreach ($updated = $compile($scan) as $file)
					{
						$need_update['-->'][] = $file;
					}

					if (!$first && isset($need_update['-->']))
					{
						foreach ($need_update as $type => $f)
						{
							echo "$type\n".implode("\n", array_map(function($a){
								return "\t".$a;
							}, $f))."\n";
						}
					}

					$first = FALSE;
				}

				usleep(200000);
			}
		}
	}
}
