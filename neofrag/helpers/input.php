<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

function post($var = NULL)
{
	if ($var === NULL)
	{
		return $_POST;
	}

	if (isset($_POST[$var]))
	{
		return $_POST[$var];
	}

	return NULL;
}

/**
 * Carnet des motifs de refus de la requête courante.
 *
 * Un checker qui refuse fait rendre un `404` NU, indiscernable d'une mauvaise adresse. Le serveur
 * sait pourtant exactement ce qui cloche — quel champ POST manque, par exemple — et ne le disait à
 * personne : c'est ce silence qui a coûté une heure sur les dix widgets impossibles à ajouter dans
 * le Live Editor. Les motifs déposés ici sont relus par Output au moment de rendre l'erreur, qui
 * les journalise toujours et les renvoie au client en mode debug.
 *
 * @param string|null $raison Motif à déposer ; NULL pour relire le carnet.
 * @return array|null         Les motifs déposés quand on relit.
 */
function nf_refus($raison = NULL)
{
	static $motifs = [];

	if ($raison === NULL)
	{
		return $motifs;
	}

	$motifs[] = (string) $raison;

	return NULL;
}

function post_check($args, $post = NULL)
{
	if (is_array($args))
	{
		if ($post === NULL)
		{
			$post = post();
		}
		else if (!is_array($post))
		{
			$post = post($post);
		}
	}
	else
	{
		$args = func_get_args();
		$post = post();
	}

	$data = [];

	foreach ($args as $var)
	{
		// Un nom suffixe de « ? » designe un champ FACULTATIF : son absence ne refuse plus la
		// requete, la cle est seulement rendue a NULL. Sans cela post_check est tout-ou-rien, et un
		// champ legitimement absent — le « settings » d'un widget qui n'a pas de formulaire de
		// reglages — fait echouer le checker, qui repond alors 404 : indiscernable d'une mauvaise
		// route. C'est ce defaut qui rendait 10 widgets impossibles a ajouter dans le Live Editor.
		if (is_string($var) && substr($var, -1) === '?')
		{
			$var = substr($var, 0, -1);
			$data[$var] = $post[$var] ?? NULL;
			continue;
		}

		if (isset($post[$var]))
		{
			$data[$var] = $post[$var];
		}
		else
		{
			// Dire LEQUEL manque, et ce qui est arrivé à la place. Sans cela, le refus remonte en
			// 404 nu et l'enquête recommence de zéro à chaque fois.
			nf_refus(sprintf(
				'champ POST « %s » absent (reçus : %s)',
				$var,
				$post ? implode(', ', array_keys($post)) : 'aucun'
			));

			return FALSE;
		}
	}

	return $data;
}
