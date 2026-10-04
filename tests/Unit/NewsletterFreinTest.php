<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\Modules\Newsletter\Models\Newsletter;

/**
 * Le frein des inscriptions à la newsletter. Sans lui, n'importe qui pouvait faire envoyer par le
 * site des e-mails de confirmation en masse, à des adresses de son choix (2026-10-04). Il suit le
 * modèle du livre d'or : `Rate_Limit`, par adresse IP et, ici, par adresse e-mail.
 */
final class NewsletterFreinTest extends TestCase
{
	public function test_les_cles_par_ip_et_par_adresse(): void
	{
		$cles = Newsletter::cles_du_frein('203.0.113.7', 'membre@exemple.fr');

		self::assertSame('newsletter:ip:203.0.113.7', $cles['ip']);
		self::assertSame('newsletter:adresse:'.hash('sha256', 'membre@exemple.fr'), $cles['adresse']);
		self::assertStringNotContainsString('membre@exemple.fr', implode(' ', $cles), 'aucune adresse en clair dans la table du frein');
		self::assertLessThanOrEqual(191, strlen($cles['adresse']), 'la colonne rate_key fait 191 caractères');
	}

	public function test_une_saisie_qui_n_est_pas_une_adresse_ne_freine_que_l_ip(): void
	{
		self::assertSame(['ip'], array_keys(Newsletter::cles_du_frein('203.0.113.7', NULL)));
		self::assertSame(['ip'], array_keys(Newsletter::cles_du_frein('203.0.113.7', '')));
	}

	public function test_chaque_cle_a_ses_seuils(): void
	{
		foreach (['ip', 'adresse'] as $type)
		{
			self::assertArrayHasKey($type, Newsletter::FREIN);
			[$essais, $fenetre, $blocage] = Newsletter::FREIN[$type];
			self::assertGreaterThan(0, $essais);
			self::assertGreaterThan(0, $fenetre);
			self::assertGreaterThan(0, $blocage);
		}
	}

	/** Le frein est consulté AVANT l'inscription — donc avant tout envoi —, pour la page comme pour le widget. */
	public function test_le_frein_passe_avant_l_inscription(): void
	{
		$controleur = (string) file_get_contents(__DIR__.'/../../modules/newsletter/controllers/index.php');

		self::assertSame(1, preg_match('/private function _inscrire\(.*?\n\t\}/s', $controleur, $corps));
		self::assertLessThan(strpos($corps[0], '->inscrire('), strpos($corps[0], '$frein->check('));
		self::assertLessThan(strpos($corps[0], '->inscrire('), strpos($corps[0], '$frein->hit('));
		self::assertSame(2, substr_count($controleur, '$this->_inscrire('), 'la page et le widget passent par lui');
	}
}
