<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

function is_asset($extension = NULL): bool
{
	return !in_array($extension ?: extension($_SERVER['REQUEST_URI']), ['', 'php', 'json', 'txt', 'xml']);
}

function icon($icon): string
{
	if (preg_match('/^(fa[bsrld] fa-.+)/', $icon, $match))
	{
		return '<i class="icon '.$match[0].' fa-fw"></i>';
	}
	else if (preg_match('/^pe-7s-.+/', $icon, $match))
	{
		return '<i class="icon '.$match[0].'"></i>';
	}

	return '<i class="icon">'.$icon.'</i>';
}

function path($file, $file_type = '', $caller = NULL): string
{
	if (is_valid_url($file))
	{
		return $file;
	}

	if (!in_array($file_type, ['images', 'css', 'js', 'fonts']))
	{
		return url($file_type.'/'.$file);
	}

	if (!$caller)
	{
		$caller = Neofrag()->output->theme() ?: Neofrag();
	}

	if ($path = $caller->__path('assets', $file_type.'/'.$file))
	{
		return url($path);
	}
	else if (check_file($file))
	{
		return url($file);
	}

	return url($file_type.'/'.$file);
}

/**
 * Version de cache d'un asset = mtime du fichier résolu (overrides inclus). Lie le ?v= au fichier :
 * toute modification (édition/upload) invalide automatiquement le cache du navigateur, sans bump manuel
 * de nf_version_css. Retourne 0 si le fichier n'est pas localisable (URL externe, introuvable) → l'appelant
 * retombe alors sur nf_version_css.
 */
function asset_version($file, $file_type = '', $caller = NULL): int
{
	if (is_valid_url($file) || !in_array($file_type, ['images', 'css', 'js', 'fonts']))
	{
		return 0;
	}

	if (!$caller)
	{
		$caller = Neofrag()->output->theme() ?: Neofrag();
	}

	if (($rel = $caller->__path('assets', $file_type.'/'.$file)) && is_file($abs = NEOFRAG_CMS.'/'.$rel))
	{
		return (int) @filemtime($abs);
	}

	return 0;
}

/**
 * Le `?v=` d'une feuille de style ou d'un script : la date du fichier (asset_version()) ET le compteur des réglages
 * (`nf_version_css`). Une feuille de thème est un gabarit PHP qui lit ses réglages — couleurs, images,
 * braises… — : changer un réglage doit changer son adresse, sinon le navigateur garde l'ancienne feuille. Les pages
 * de réglages des thèmes font avancer `nf_version_css` à chaque enregistrement ; depuis que le `?v=` suivait la
 * seule date du fichier, ce compteur n'était plus lu (trouvé le 2026-10-06 en éprouvant un réglage de Forge :
 * un réglage changé ne se voyait pas, pas plus qu'une nouvelle couleur d'accent sur un autre thème).
 */
function nf_version_asset(string $file, string $file_type, $caller = NULL): string
{
	$date     = asset_version($file, $file_type, $caller);
	$reglages = (int) NeoFrag()->config->nf_version_css;

	if ($date && $reglages)
	{
		return $date.'-'.$reglages;
	}

	return $date || $reglages ? (string) ($date ?: $reglages) : '';
}

function image($file, $caller = NULL): string
{
	return path($file, 'images', $caller);
}

/**
 * L'adresse du favicon du site : celui téléversé dans les réglages, sinon celui du cœur.
 *
 * La résolution était écrite deux fois — dans le gabarit principal et, depuis que `/favicon.ico` est
 * servi à la racine, dans le contrôleur des réglages. Deux copies d'une même règle finissent par
 * diverger ; et le modèle générique ne déclarant pas `path()`, chaque copie faisait trébucher
 * l'analyse statique. Une seule fonction, typée, employée par les deux.
 */
function favicon_url(): string
{
	$config = NeoFrag()->config;

	if ($config->nf_favicon)
	{
		$fichier = NeoFrag()->model2('file', $config->nf_favicon);

		if (method_exists($fichier, 'path') && ($chemin = $fichier->path()))
		{
			return (string) $chemin;
		}
	}

	return image('favicon.png');
}

function css($file, $caller = NULL): string
{
	return path($file, 'css', $caller);
}

function js($file, $caller = NULL): string
{
	return path($file, 'js', $caller);
}
