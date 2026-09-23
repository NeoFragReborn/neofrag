<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * La bibliothèque de formulaires (neofrag/libraries/form.php) pose une valeur enregistrée dans un
 * attribut `value`, une zone de texte ou une liste. Elle l'échappait avec `addcslashes(…, '"')`,
 * sans effet en HTML : la source d'une citation, `"><img src=x>`, sortait de l'attribut et injectait
 * une balise dans le formulaire d'édition de l'administration. Vu le 2026-09-23 par
 * check-mise-en-page, sous la forme d'une « image cassée ».
 *
 * Les valeurs qu'enregistre le formulaire lui-même sont encodées à l'entrée : leur rendu ne doit
 * PAS changer (aucun double encodage). Ce sont les deux moitiés du contrat.
 */
final class FormEchappementTest extends HeadlessTestCase
{
	/**
	 * Le boot headless ne câble pas de session ; le formulaire n'en attend que son jeton. Une session
	 * minimale, posée le temps du rendu, suffit.
	 */
	private function formulaire(array $regles): string
	{
		$nf = \NeoFrag();
		$nf->session = new class {
			private array $donnees = [];

			public function __invoke(...$cles)
			{
				return $this->donnees[$cles[0]] ?? NULL;
			}

			public function set(...$args): void
			{
				$valeur = array_pop($args);
				$this->donnees[$args[0]][$args[1] ?? 0] = $valeur;
			}
		};

		try
		{
			$form = new \NF\NeoFrag\Libraries\Form($nf);

			return (string) $form->add_rules($regles)->display();
		}
		finally
		{
			unset($nf->session);
		}
	}

	public function test_une_valeur_brute_ne_sort_pas_de_son_attribut(): void
	{
		$html = $this->formulaire([
			'source' => ['label' => 'Source', 'type' => 'text', 'value' => '"><img src=x>'],
		]);

		$this->assertStringNotContainsString('<img src=x>', $html);
		$this->assertStringContainsString('value="&quot;&gt;&lt;img src=x&gt;"', $html);
	}

	public function test_une_zone_de_texte_ne_se_ferme_pas_sur_sa_valeur(): void
	{
		$html = $this->formulaire([
			'quote' => ['label' => 'Citation', 'type' => 'textarea', 'value' => '</textarea><script>alert(1)</script>'],
		]);

		$this->assertStringNotContainsString('<script>alert(1)</script>', $html);
		$this->assertStringContainsString('&lt;/textarea&gt;&lt;script&gt;alert(1)&lt;/script&gt;</textarea>', $html);
	}

	public function test_une_valeur_deja_encodee_n_est_pas_encodee_deux_fois(): void
	{
		// Ce que `is_valid()` enregistre : la saisie passée par utf8_htmlentities().
		$html = $this->formulaire([
			'author' => ['label' => 'Auteur', 'type' => 'text', 'value' => utf8_htmlentities('Tom & "Jerry" <b>')],
		]);

		$this->assertStringContainsString('value="Tom &amp; &quot;Jerry&quot; &lt;b&gt;"', $html);
		$this->assertStringNotContainsString('&amp;amp;', $html);
	}

	public function test_les_valeurs_d_une_liste_restent_dans_leur_attribut(): void
	{
		$html = $this->formulaire([
			'choix' => ['label' => 'Choix', 'type' => 'select', 'values' => ['a"><b>x' => 'Libellé'], 'value' => 'a"><b>x'],
		]);

		$this->assertStringNotContainsString('<b>x', $html);
		$this->assertStringContainsString('<option value="a&quot;&gt;&lt;b&gt;x" selected="selected">', $html);
	}
}
