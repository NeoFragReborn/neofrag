<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * nf_formulaire() (tools/lib/serveur.php) relit un formulaire tel qu'un navigateur l'enverrait : les outils l'emploient
 * pour réenvoyer un écran de réglages sans rien y changer. Une liste dont l'option choisie a une valeur vide (« Police
 * du thème ») était relue comme sa première option non vide : réenvoyé, le formulaire changeait la police de la vitrine
 * (2026-10-15). Dans son propre processus : la bibliothèque des outils définit nf_refus(), comme le CMS.
 */
#[RunTestsInSeparateProcesses]
final class ToolsFormulaireTest extends TestCase
{
	protected function setUp(): void
	{
		require_once __DIR__.'/../../tools/lib/outil.php';
		require_once __DIR__.'/../../tools/lib/serveur.php';
	}

	public function test_un_formulaire_se_relit_comme_le_navigateur_l_enverrait(): void
	{
		$html = '<form method="post">'
			.'<input type="text" name="f[nom]" value="Mon &amp; site">'
			.'<input type="checkbox" name="f[pwa][]" value="on">'
			.'<input type="checkbox" name="f[push][]" value="on" checked="checked">'
			.'<select name="f[police]"><option value="" selected="selected">Police du thème</option><option value="Inter">Inter</option></select>'
			.'<select name="f[fuseau]"><option value="UTC">UTC</option><option value="Europe/Paris" selected>Paris</option></select>'
			.'<select name="f[langue]"><option value="fr">Français</option><option value="en">English</option></select>'
			.'<textarea name="f[texte]">Deux&#10;lignes</textarea>'
			.'<input type="submit" value="Valider">'
			.'</form>';

		$this->assertSame([
			'f[nom]'     => 'Mon & site',
			'f[push][]'  => 'on',
			'f[texte]'   => "Deux\nlignes",
			'f[police]'  => '',
			'f[fuseau]'  => 'Europe/Paris',
			'f[langue]'  => 'fr',
		], nf_formulaire($html, '[nom]'));
	}
}
