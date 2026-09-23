<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

/*
 * Traduction des alignements en classes Bootstrap 5.
 *
 * POURQUOI CE FICHIER EXISTE
 * --------------------------
 * Le produit tourne sous Bootstrap 5, qui a RENOMMÉ les utilitaires directionnels : `float-right`
 * est devenu `float-end`, `text-left` est devenu `text-start`. Une classe renommée ne provoque
 * aucune erreur — elle n'est simplement définie nulle part, et l'élément garde sa mise en page par
 * défaut. Le bouton censé se caler à droite reste à gauche, et rien, nulle part, ne le signale.
 *
 * Le passage à Bootstrap 5 avait réécrit les vues, mais il n'avait pas pu voir ces quatre
 * endroits-ci : les bibliothèques du cœur ne posent pas la classe dans un `class="…"`, elles la
 * FABRIQUENT par concaténation (`'float-'.$align`). Aucune recherche sur le nom de la classe ne
 * pouvait les trouver, et c'est pour cela qu'ils ont survécu.
 *
 * Étaient concernés, sur les sept thèmes :
 *
 *   - `neofrag/libraries/button.php`   — tout pied de panneau et de modale ;
 *   - `neofrag/libraries/table.php`    — l'alignement des cellules de la première bibliothèque ;
 *   - `neofrag/libraries/table_col.php`— celui de la seconde, dont les colonnes d'actions.
 *
 * Passer par une fonction unique plutôt que par un `str_replace` à chaque appel a un intérêt :
 * le jour où Bootstrap renommera encore, il y aura un seul endroit à changer, et `tools/check-
 * classes-bs4.php` sait que ce qui sort d'ici est déjà à jour.
 */

/**
 * Le nom Bootstrap 5 d'un alignement horizontal.
 *
 * Accepte aussi bien les noms d'intention (`left`, `right`, `center`) que les noms Bootstrap 4
 * (`float-right`…) ou Bootstrap 5 (`text-end`…) : les réglages enregistrés en base par les widgets
 * contiennent les trois formes selon leur époque.
 *
 * @param string $align    `left`, `right`, `center`, ou une classe déjà formée
 * @param string $prefixe  `float` ou `text`
 * @return string          la classe Bootstrap 5, ou '' si l'alignement est vide ou inconnu
 */
function nf_bs_align(string $align, string $prefixe = 'text'): string
{
	$align = trim($align);

	if ($align === '')
	{
		return '';
	}

	// Une classe déjà formée : on ne garde que son alignement pour le retraduire sous le préfixe
	// demandé. `float-end` passé à un contexte `text` doit donner `text-end`, pas `text-float-end`.
	$align = preg_replace('/^(?:float|text)-/', '', $align) ?? $align;

	$noms = [
		'left'   => 'start',
		'start'  => 'start',
		'right'  => 'end',
		'end'    => 'end',
		'center' => 'center',
	];

	if (!isset($noms[$align]))
	{
		return '';
	}

	// `float-center` n'a jamais existé, dans aucune version de Bootstrap : un pied de panneau
	// demandé « centré » se retrouvait donc dans un conteneur sans la moindre règle, c'est-à-dire
	// aligné à gauche. `text-center` est ce que l'appelant voulait dire.
	if ($prefixe === 'float' && $noms[$align] === 'center')
	{
		return 'text-center';
	}

	return $prefixe.'-'.$noms[$align];
}
