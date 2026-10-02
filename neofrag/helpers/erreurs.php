<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * Les erreurs que voit le visiteur, et leur trace pour l'administrateur.
 *
 * Jusqu'au 2026-10-02, une page qui plantait s'affichait « Page introuvable » (le visiteur croyait
 * à une mauvaise adresse), une erreur fatale laissait une page blanche, et la base injoignable
 * répondait en anglais brut — l'administrateur, lui, devait aller lire `logs/php.log` par SSH ou FTP.
 *
 * Chaque erreur reçoit maintenant une RÉFÉRENCE : le visiteur la lit sur la page, le journal la porte
 * sur la ligne de l'erreur. « J'ai eu une erreur, référence 3F9A1C07 » mène l'administrateur droit à
 * la bonne ligne, dans Système → Monitoring → Journal.
 */

/** La référence de l'erreur de cette requête : une seule, même si plusieurs choses échouent. */
function nf_reference_erreur(): string
{
	static $reference = NULL;

	return $reference ??= strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Écrit une erreur au journal, avec la requête et la référence, et rend la référence.
 *
 * La référence va EN FIN de ligne : le journal regroupe les lignes par message, et une même erreur
 * vue par cent visiteurs doit rester une seule entrée (le regroupement retire « (réf. …) »).
 */
function nf_journaliser_erreur(string $etiquette, string $message, string $origine = ''): string
{
	$reference = nf_reference_erreur();
	$requete   = trim((string) ($_SERVER['REQUEST_METHOD'] ?? '').' '.(string) ($_SERVER['REQUEST_URI'] ?? ''));

	error_log('['.$etiquette.'] '.($requete !== '' ? $requete.' : ' : '').$message.($origine !== '' ? ' — '.$origine : '').' (réf. '.$reference.')');

	return $reference;
}

/**
 * Le mode débogage se MONTRE-t-il à cette visite ?
 *
 * Il s'allume dans `config/neofrag.php` (`NEOFRAG_DEBUG_BAR`). Ce qu'il AFFICHE — la barre en bas de
 * page (requêtes, données de la visite, session), une erreur en brut au lieu de la page d'erreur, le
 * diagnostic d'une adresse refusée — ne se montre plus qu'à un administrateur connecté : allumé, il
 * s'affichait à tous les visiteurs (relevé le 2026-10-02). Il peut ainsi rester allumé un moment sur un
 * site en service, le temps d'un diagnostic. Ce qu'il COLLECTE ne change pas.
 */
function nf_debogage_visible(): bool
{
	return nf_debogage_actif() && nf_administrateur_connecte();
}

/**
 * Le relevé des traductions se MONTRE-t-il à cette visite ? Allumé, chaque texte traduit porte le drapeau
 * de sa langue — un texte sans drapeau n'est pas passé par la traduction. Il ne se montre qu'aux
 * administrateurs connectés : il s'affichait à tous les visiteurs. Les traductions manquantes, elles,
 * se relèvent pour toutes les visites (`nf_traductions_actives()`).
 */
function nf_traductions_visibles(): bool
{
	return nf_traductions_actives() && nf_administrateur_connecte();
}

/** Un administrateur est-il connecté à cette visite ? Dans le doute — trop tôt, session illisible —, non. */
function nf_administrateur_connecte(): bool
{
	try
	{
		// Lu directement, comme le fait `Access` : `isset(NeoFrag()->user)` répond toujours non, le cœur
		// se chargeant à la demande.
		return !empty(NeoFrag()->user->admin);
	}
	catch (\Throwable $e)
	{
		return FALSE;
	}
}

/*
 * Les outils de diagnostic. Chacun s'allume à demeure dans `config/neofrag.php`, ou depuis le Monitoring
 * pour une heure — sans avoir à modifier ce fichier par FTP (2026-10-02) :
 *
 *   debogage     NEOFRAG_DEBUG_BAR   la barre en bas de page, le détail des erreurs (administrateurs)
 *   trace        NEOFRAG_LOGS        chaque page servie, ses requêtes et ses en-têtes, dans logs/neofrag.log
 *   traductions  NEOFRAG_LOGS_I18N   les traductions manquantes (nf_log_i18n), le drapeau des textes traduits
 */
const NF_DIAGNOSTICS = ['debogage' => 'NEOFRAG_DEBUG_BAR', 'trace' => 'NEOFRAG_LOGS', 'traductions' => 'NEOFRAG_LOGS_I18N'];

/** Le mode débogage est-il allumé ? Ce qu'il affiche ne se montre qu'à un administrateur (`nf_debogage_visible()`). */
function nf_debogage_actif(): bool
{
	return nf_diagnostic_actif('debogage');
}

/** La trace détaillée des pages (`logs/neofrag.log`) est-elle allumée ? */
function nf_trace_active(): bool
{
	return nf_diagnostic_actif('trace');
}

/** Le relevé des traductions est-il allumé ? */
function nf_traductions_actives(): bool
{
	return nf_diagnostic_actif('traductions');
}

/** L'outil `$outil` est-il allumé, à demeure (configuration) ou pour une heure (Monitoring) ? */
function nf_diagnostic_actif(string $outil): bool
{
	static $monitoring = [];

	return nf_diagnostic_a_demeure($outil) || ($monitoring[$outil] ??= nf_diagnostic_jusqua($outil) > time());
}

/** L'outil `$outil` est-il allumé dans `config/neofrag.php` ? Il ne s'éteint alors que là. */
function nf_diagnostic_a_demeure(string $outil): bool
{
	$constante = NF_DIAGNOSTICS[$outil] ?? '';

	return $constante !== '' && defined($constante) && constant($constante);
}

/** Le fichier où le Monitoring note jusqu'à quand il a allumé chaque outil. */
function nf_diagnostic_fichier(): string
{
	return (defined('NEOFRAG_CMS') ? NEOFRAG_CMS : '.').'/cache/diagnostic.json';
}

/** @return array<string, int> */
function nf_diagnostic_etat(): array
{
	$etat = is_file($fichier = nf_diagnostic_fichier()) ? json_decode((string) @file_get_contents($fichier), TRUE) : NULL;

	return is_array($etat) ? array_map('intval', array_intersect_key($etat, NF_DIAGNOSTICS)) : [];
}

/** L'heure (horodatage) jusqu'à laquelle le Monitoring a allumé `$outil` ; 0 s'il ne l'a pas fait. */
function nf_diagnostic_jusqua(string $outil): int
{
	return nf_diagnostic_etat()[$outil] ?? 0;
}

/** Allume `$outil` jusqu'à `$jusqua` (horodatage), ou l'éteint (0). Rend FALSE si le fichier ne s'écrit pas. */
function nf_diagnostic_regler(string $outil, int $jusqua): bool
{
	$fichier = nf_diagnostic_fichier();
	$etat    = array_filter(nf_diagnostic_etat(), static fn (int $fin): bool => $fin > time());

	if ($jusqua > 0)
	{
		$etat[$outil] = $jusqua;
	}
	else
	{
		unset($etat[$outil]);
	}

	if (!$etat)
	{
		return !is_file($fichier) || @unlink($fichier);
	}

	if (!is_dir($dossier = dirname($fichier)))
	{
		@mkdir($dossier, 0775, TRUE);
	}

	return @file_put_contents($fichier, (string) json_encode($etat)) !== FALSE;
}

/** Un chemin du produit, sans la racine de l'installation : le journal se lit plus court, et ne dit pas où vit le site. */
function nf_chemin_relatif(string $chemin): string
{
	return defined('NEOFRAG_CMS') ? str_replace([NEOFRAG_CMS.'/', NEOFRAG_CMS.'\\'], '', $chemin) : $chemin;
}

/**
 * Le filet du démarrage : une exception que rien n'a rattrapée, ou une erreur fatale, ne laisse plus
 * une page blanche. Posé dès que les aides sont chargées, avant le reste du site.
 */
function nf_filet_erreurs(): void
{
	if (PHP_SAPI === 'cli')
	{
		return;   // les outils en ligne de commande gardent le comportement de PHP
	}

	set_exception_handler(static function (\Throwable $e): void {
		$reference = nf_journaliser_erreur('fatal', 'exception non rattrapée '.get_class($e).' : '.$e->getMessage(), nf_chemin_relatif($e->getFile()).':'.$e->getLine());

		error_log(nf_chemin_relatif($e->getTraceAsString()));

		// En débogage, pour un administrateur connecté : le détail de l'erreur, sur la page même.
		$detail = nf_debogage_visible() ? get_class($e).' : '.$e->getMessage()."\n".nf_chemin_relatif($e->getFile()).':'.$e->getLine()."\n\n".nf_chemin_relatif($e->getTraceAsString()) : '';

		nf_page_erreur_autonome(500, $reference, $detail);
	});

	register_shutdown_function(static function (): void {
		$erreur = error_get_last();

		if ($erreur === NULL || !($erreur['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)))
		{
			return;
		}

		// PHP a déjà écrit l'erreur fatale au journal ; on y ajoute la référence montrée au visiteur.
		$reference = nf_journaliser_erreur('fatal', 'la page s’est arrêtée sur une erreur fatale', nf_chemin_relatif((string) $erreur['file']).':'.(int) $erreur['line']);

		nf_page_erreur_autonome(500, $reference);
	});
}

/**
 * La langue de la page autonome : celle de l'adresse (`/en/…`), sinon celle que le navigateur
 * préfère, sinon le français.
 */
function nf_langue_autonome(): string
{
	$langues = ['fr', 'en', 'de', 'es', 'it', 'pt'];
	$base    = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
	$chemin  = substr((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), strlen($base));

	if (preg_match('#^/([a-z]{2})(/|$)#', $chemin, $m) && in_array($m[1], $langues, TRUE))
	{
		return $m[1];
	}

	foreach (explode(',', (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '')) as $preference)
	{
		if (in_array($code = strtolower(substr(trim($preference), 0, 2)), $langues, TRUE))
		{
			return $code;
		}
	}

	return 'fr';
}

/**
 * La page d'une erreur qui empêche le site de s'afficher : une erreur fatale, une exception au
 * démarrage, la base injoignable. Elle ne demande RIEN au site — ni base, ni thème, ni addon de
 * langue : ses textes se lisent directement dans les fichiers de traduction du cœur
 * (`neofrag/langs/<langue>.php`, sous la même clé que `lang()`), et `check-langs` les vérifie.
 */
function nf_page_erreur_autonome(int $code, string $reference = '', string $detail = ''): void
{
	while (ob_get_level() > 0)
	{
		ob_end_clean();
	}

	$langue = nf_langue_autonome();
	$traductions = [];

	if ($langue !== 'fr' && is_file($fichier = dirname(__DIR__).'/langs/'.$langue.'.php')
		&& preg_match_all("/'([0-9a-f]{8})'\\s*=>\\s*'((?:[^'\\\\]|\\\\.)*)'/", (string) file_get_contents($fichier), $paires, PREG_SET_ORDER))
	{
		foreach ($paires as [, $cle, $valeur])
		{
			$traductions[$cle] = str_replace(["\\'", '\\\\'], ["'", '\\'], $valeur);
		}
	}

	$lang = static fn (string $texte): string => $traductions[sprintf('%08x', crc32($texte))] ?? $texte;

	$titre   = $code === 503 ? $lang('Le site est momentanément indisponible') : $lang('Une erreur est survenue');
	$texte   = $code === 503
		? $lang('Il ne parvient pas à joindre sa base de données. Réessayez dans quelques instants.')
		: $lang('Le site a rencontré un problème et n’a pas pu afficher cette page. L’erreur est notée ; si elle se reproduit, signalez-la à l’administrateur du site avec cette référence :');
	$accueil = $lang('Retour à l’accueil');

	if (PHP_SAPI === 'cli')
	{
		fwrite(STDERR, $titre.' — '.$texte.($reference !== '' ? ' '.$reference : '')."\n");

		return;
	}

	if (!headers_sent())
	{
		// Une ligne de statut, pas `http_response_code()` : celle-ci est ignorée — avec un avertissement
		// au journal — quand le site a déjà posé la sienne par `header('HTTP/1.0 …')`.
		header((string) ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1').' '.$code.($code === 503 ? ' Service Unavailable' : ' Internal Server Error'), TRUE, $code);
		header('Content-Type: text/html; charset=UTF-8');
		header('Cache-Control: no-store');

		if ($reference !== '')
		{
			header('X-NF-Reference: '.$reference);
		}

		if ($code === 503)
		{
			header('Retry-After: 60');
		}
	}

	$e    = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
	$base = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/').'/';

	echo '<!DOCTYPE html><html lang="'.$langue.'"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><title>'.$e($titre).'</title>'
		.'<style>:root{color-scheme:light dark;--fond:#f5f7fa;--carte:#fff;--texte:#0f172a;--doux:#64748b;--accent:#0f766e}'
		.'@media (prefers-color-scheme:dark){:root{--fond:#070b12;--carte:#0f1623;--texte:#e2e8f0;--doux:#94a3b8;--accent:#2dd4bf}}'
		.'body{margin:0;min-height:100vh;display:grid;place-items:center;background:var(--fond);color:var(--texte);font:16px/1.6 system-ui,-apple-system,"Segoe UI",sans-serif;padding:16px;box-sizing:border-box}'
		.'main{max-width:34rem;background:var(--carte);border-radius:14px;padding:2.5rem 2rem;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,.08)}'
		.'.code{font-size:3rem;font-weight:700;color:var(--doux);margin:0}h1{font-size:1.4rem;margin:.25rem 0 1rem}p{color:var(--doux);margin:0 0 1.25rem}'
		.'.ref{display:inline-block;font:600 1.1rem ui-monospace,Consolas,monospace;letter-spacing:.08em;padding:.35rem .8rem;border-radius:8px;border:1px dashed var(--doux);color:var(--texte);margin-bottom:1.5rem}'
		.'a{display:inline-block;background:var(--accent);color:#fff;text-decoration:none;font-weight:600;padding:.6rem 1.2rem;border-radius:8px}'
		.'pre{text-align:left;font:12px/1.5 ui-monospace,Consolas,monospace;white-space:pre-wrap;overflow-wrap:anywhere;max-height:22rem;overflow:auto;background:var(--fond);border-radius:8px;padding:.8rem;margin:1.5rem 0 0}</style></head><body><main>'
		.'<p class="code">'.$code.'</p><h1>'.$e($titre).'</h1><p>'.$e($texte).'</p>'
		.($reference !== '' ? '<div class="ref">'.$e($reference).'</div><br>' : '')
		.'<a href="'.$e($base).'">'.$e($accueil).'</a>'
		.($detail !== '' ? '<pre>'.$e($detail).'</pre>' : '')
		.'</main></body></html>';
}
