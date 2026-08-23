<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Helper Markdown — convertit du Markdown vers HTML via CommonMark.
 * Permet aux modules (articles, wiki, FAQ, etc.) d'accepter du Markdown comme format de contenu.
 */

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Convertit du Markdown CommonMark en HTML.
 *
 * @param string $markdown Le texte Markdown source
 * @param bool $github_flavored Active GFM (tables, task lists, autolinks, etc.) — défaut TRUE
 * @return string HTML rendu
 */
function markdown_to_html($markdown, $github_flavored = TRUE): string
{
	static $converter = NULL, $gfm_converter = NULL;

	if ($github_flavored)
	{
		if ($gfm_converter === NULL)
		{
			$gfm_converter = new GithubFlavoredMarkdownConverter([
				'html_input'         => 'escape',
				'allow_unsafe_links' => FALSE,
				'max_nesting_level'  => 20
			]);
		}

		return (string) $gfm_converter->convert((string) $markdown);
	}

	if ($converter === NULL)
	{
		$converter = new CommonMarkConverter([
			'html_input'         => 'escape',
			'allow_unsafe_links' => FALSE,
			'max_nesting_level'  => 20
		]);
	}

	return (string) $converter->convert((string) $markdown);
}

/**
 * Détecte si une chaîne ressemble à du Markdown plutôt que du HTML.
 * Heuristique simple : si elle contient des balises HTML ouvrantes structurelles, on suppose HTML.
 *
 * @param string $content
 * @return bool TRUE si Markdown probable
 */
function looks_like_markdown($content): bool
{
	if (preg_match('#<(p|div|h[1-6]|ul|ol|li|table|article|section|main)\b#i', $content))
	{
		return FALSE;
	}

	if (preg_match('/^(#{1,6}\s|\*\s|-\s|\d+\.\s|>\s|```|\[.+\]\(.+\))/m', $content))
	{
		return TRUE;
	}

	return FALSE;
}

/**
 * Rendu universel : si content ressemble à du Markdown, convertit. Sinon retourne tel quel.
 */
function render_content($content): string
{
	if (looks_like_markdown($content))
	{
		return markdown_to_html($content);
	}
	return $content;
}
