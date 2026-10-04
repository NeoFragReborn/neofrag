<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Core;

use NF\NeoFrag\Core;

class Debug extends Core
{
	/** Au-delà, le journal bascule en `.1` et repart. 64 Mio : de quoi lire une séance, pas un mois. */
	const JOURNAL_MAX = 67108864;


	protected $_logs = [];
	private $_timeline = [];

	public function __construct($config = [])
	{
		ini_set('display_errors', FALSE);

		$this('Start');

		set_error_handler(function($errno, $errstr, $errfile, $errline){
			// `error_reporting() & $errno` et non `!== 0` : depuis PHP 8, `@` ne ramène plus le niveau à zéro.
			// Le relevé notait donc les alertes volontairement étouffées — « propriété absente » sur les
			// thèmes, « REDIRECT_CONTEXT » hors Apache —, des milliers de lignes que le journal PHP ignore.
			if (error_reporting() & $errno)
			{
				if (nf_debogage_actif() || nf_trace_active())
				{
					if (in_array($errno, [E_USER_ERROR, E_RECOVERABLE_ERROR]))
					{
						$error = 'error';
					}
					else if (in_array($errno, [E_USER_WARNING, E_WARNING]))
					{
						$error = 'warning';
					}
					else if (in_array($errno, [E_USER_NOTICE, E_NOTICE]))
					{
						$error = 'notice';
					}
					else if (in_array($errno, [E_DEPRECATED]))
					{
						$error = 'deprecated';
					}
					else if ($errno == E_STRICT)
					{
						$error = 'strict';
					}

					$this->_logs[] = [[], $errstr, $error, relative_path($errfile), $errline, date_create(), memory_get_usage()];
				}
			}

			/*
			 * Et l'erreur continue vers le journal PHP, TOUJOURS.
			 *
			 * La version précédente la gardait pour la barre de débogage et `logs/neofrag.log` dès que
			 * NEOFRAG_LOGS ou NEOFRAG_DEBUG_BAR étaient actifs : `logs/php.log` ne recevait alors plus
			 * AUCUNE alerte PHP. C'est la configuration d'un site d'essai — si bien que `check-journal` et
			 * `check-liens`, qui lisent `php.log`, n'y voyaient rien, pendant que la production (réglages
			 * éteints) les écrivait. Le 2026-09-22, le widget du forum y a perdu des mois.
			 *
			 * Rendre FALSE laisse PHP appliquer son traitement ordinaire, qui respecte `@` : les
			 * sondages volontairement muets du chargeur (« Unfound libraries ») restent hors du journal,
			 * exactement comme en production.
			 */
			return FALSE;
		});

		if (nf_trace_active())
		{
			$this->on('output_rendered', function(){
				$cols = $lines = [];

				foreach ($this->_logs as list($args, $message, $type, $file, $line, $date, $memory))
				{
					array_unshift($args, $date->format('Y-m-d H:i:s.u'), sprintf('%.3f', ($memory - NEOFRAG_MEMORY) / 1024 / 1024).'Mb', strtoupper($type));

					foreach ($args as $i => $value)
					{
						$n = strlen($value);
						$cols[$i] = isset($cols[$i]) ? max($n, $cols[$i]) : $n;
					}

					if ($file)
					{
						$message .= ' '.$file.' '.$line;
					}

					$lines[] = [$args, $message];
				}

				dir_create('logs');

				nf_log_rotate('logs/neofrag.log', self::JOURNAL_MAX);

				if ($f = fopen('logs/neofrag.log', 'a'))
				{
					while (!flock($f, LOCK_EX));

					// La page tracée, en tête de son bloc : la lecture du Monitoring (Trace des pages) en fait
					// le titre de chaque page ; sans elle, rien ne disait à quelle adresse le bloc répondait.
					fwrite($f, '» '.($_SERVER['REQUEST_METHOD'] ?? 'CLI').' '.($_SERVER['REQUEST_URI'] ?? '')."\n");

					foreach ($lines as list($args, $message))
					{
						foreach ($cols as $i => $size)
						{
							$args[$i] = isset($args[$i]) ? sprintf('%'.$size.'.s', $args[$i]) : str_repeat(' ', $size);
						}

						fwrite($f, implode('    ', $args).' '.$message."\n");
					}

					fwrite($f, str_repeat('=', 350)."\n");

					flock($f, LOCK_UN);

					fclose($f);
				}
			});
		}
	}

	public function __invoke($message)
	{
		$memory = memory_get_usage();

		$args = func_get_args();
		$message = array_pop($args);

		$this->_logs[] = [$args, $message, 'info', '', 0, $this->date(), $memory];
	}

	static function debug_to_console($data) {
		echo "<script>console.log('Debug Objects: " . json_encode($data) . "' );</script>";
	}

	public function timeline()
	{
		if (!func_num_args())
		{
			$output = '<table class="table table-striped">
						<tbody>';

			usort($this->_timeline, static fn ($a, $b): int => $a[1] <=> $b[1]);

			// Les bornes de la chronologie : le premier début et la dernière fin. Elles se lisaient sur une
			// propriété `__debug` que ces entrées n'ont pas — la barre, rallumée le 2026-10-02, ne
			// s'affichait plus (variables indéfinies, puis division par zéro).
			$min   = $this->_timeline ? min(array_column($this->_timeline, 1)) : 0;
			$max   = $this->_timeline ? max(array_column($this->_timeline, 2)) : 0;
			$total = ($max - $min) ?: 1;

			foreach ($this->_timeline as $time)
			{
				$class = 'float-start';

				if (preg_match('/class="(.*?)"/', $time[0], $match))
				{
					$class .= ' '.$match[1];
				}

				$output .= '	<tr>
									<td>'.$time[0].'</td>
									<td>
										<div class="float-start" style="height: 25px; width: '.str_replace(',', '.', (string) floor(($time[1] - $this->_timeline[0][1]) * 100 / $total)).'%;"></div>
										<div class="'.$class.'" style="height: 25px; display: block; padding: 0; width: '.str_replace(',', '.', (string) max(1, floor(($time[2] - $time[1]) * 100 / $total))).'%;"></div>
									</td>
								</tr>';
			}

			$output .= '	</tbody>
						</table>';

			return $output;
		}
		else
		{
			$this->_timeline[] = func_get_args();
			return $this;
		}
	}

	public function bar($type = '', $data = NULL)
	{
		if (nf_debogage_actif())
		{
			static $debug_bar = [];

			if ($type && $data)
			{
				$debug_bar[$type] = $data;
				return $this;
			}
			else if (!nf_debogage_visible())
			{
				return '';   // la barre ne se montre qu'à un administrateur connecté
			}
			else
			{
				$table = function($data) use (&$table){
					if (is_array($data) || is_object($data))
					{
						$output = '<table class="table table-striped">';
						
						$data = (array)$data;
						ksort($data);
						
						foreach ($data as $key => $value)
						{
							if(!is_object($value)) {
							$output .= '	<tr>
												<td style="width: 200px;"><b>'.$key.'</b></td>
												<td>'.$table($value).'</td>
											</tr>';
							}
						}
						
						$output .= '</table>';

						return $output;
					}
					else if (is_bool($data))
					{
						return $data ? '<i class="fas fa-check text-success" title="TRUE"></i>' : '<i class="fas fa-times text-danger" title="FALSE"></i>';
					}
					else if ($data === NULL)
					{
						return '<em>NULL</em>';
					}
					else
					{
						// (string) : un nombre (temps, compteur, port du serveur) faisait tomber toute la
						// barre en mode strict — elle ne s'affichait plus du tout, et personne ne le voyait
						// tant qu'elle restait éteinte (relevé le 2026-10-02 par le journal des erreurs).
						return utf8_htmlentities(str_replace(["\n", "\r"], '', (string) $data));
					}
				};

				$this	->bar('console', function(&$label){
							
						  $result = '<table class="table table-striped">';

							$warning = $error = $notice = $deprecated = $strict = 0;

							foreach ($this->_logs as $i => list($prefix, $text, $type, $file, $line, $date))
							{
								if ($type == "info")
								{
									$class_type = $type;
									$type = '<span class="badge text-bg-success">Info</span>';
								}
								else if ($type == "warning")
								{
									$class_type = $type;
									$type = '<span class="badge text-bg-warning">Warning</span>';
									$warning++;
								}
								else if ($type == "error")
								{
									$class_type = $type;
									$type = '<span class="badge text-bg-danger">Error</span>';
									$error++;
								}
								else if ($type == "notice")
								{
									$class_type = $type;
									$type = '<span class="badge text-bg-info">Notice</span>';
									$notice++;
								}
								else if ($type == "deprecated")
								{
									$class_type = $type;
									$type = '<span class="badge text-bg-warning">Deprecated</span>';
									$deprecated++;
								}
								else if ($type == "strict")
								{
									$class_type = $type;
									$type = '<span class="badge text-bg-secondary">Strict</span>';
									$strict++;
								}

								if($class_type == "info") {
									$result .= '	<tr class="row-'.$class_type.'" style="display: none;">';
								}else{
									$result .= '	<tr class="row-'.$class_type.'">';
								}
								
								$result .= '		<td><b>'.($i + 1).'</b><div class="float-end">'.$type.'</div></td>
													<td>'.utf8_htmlentities($text).'</td>
													<td class="text-end">'.$file.' <code>'.$line.'</code></td>
												</tr>';	
							
							}

							$result .= '</table>';

							if ($error)
							{
								$label = '<span class="badge text-bg-danger">'.$error.'</span>';
							}
							else if ($warning)
							{
								$label = '<span class="badge text-bg-warning">'.$warning.'</span>';
							}
							else if ($strict)
							{
								$label = '<span class="badge text-bg-secondary">'.$strict.'</span>';
							}
							else if ($notice)
							{
								$label = '<span class="badge text-bg-info">'.$notice.'</span>';
							}
							else if ($deprecated)
							{
								$label = '<span class="badge text-bg-warning">'.$deprecated.'</span>';
							}

							return $result;
						})
						->bar('loader', function(){
							return '';
						})
						->bar('timeline', function(){
							return $this->timeline();
						})
						->bar('server', function(){
							return $_SERVER;
						});

				$this	->css('fonts/open-sans')
						->css('debug-bar')
						->js('debug-bar');

				$tabs = [
					'console'  => ['Console',  'fas fa-terminal'],
					'database' => ['Database', 'fas fa-database'],
					'loader'   => ['Loader',   'fas fa-puzzle-piece'],
					'timeline' => ['Timeline', 'far fa-clock'],
					'request'  => ['Request',  'far fa-hand-pointer'],
					'output'   => ['Result',   'fas fa-share'],
					'settings' => ['Settings', 'fas fa-cogs'],
					'session'  => ['Session',  'fas fa-flag'],
					'server'   => ['Server',   'fas fa-server']
				];

				array_walk($tabs, function(&$a, $name) use ($debug_bar, &$table){
					$label = NULL;

					if (is_array($result = $debug_bar[$name]($label)))
					{
						$result = $table($result);
					}

					$a[] = $result;

					if ($label)
					{
						$a[] = $label;
					}
				});

				return $this->view('debug/bar', [
					'tabs'   => $tabs,
					'active' => ($tab = $this->session('debug', 'tab')) && isset($tabs[$tab]) ? $tab : NULL
				]);
			}
		}
	}
}
