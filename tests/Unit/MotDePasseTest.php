<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use NF\Modules\User\User;

/**
 * La règle d'un NOUVEAU mot de passe (ligne 0.33, chantier A, 2026-10-05) : ni l'inscription, ni le changement,
 * ni la réinitialisation n'en avaient — « a » passait. User::mot_de_passe_refuse() dit « court », « facile » ou
 * rien ; les formulaires password_required et new_password en font un message.
 */
final class MotDePasseTest extends TestCase
{
	public function test_trop_court(): void
	{
		$this->assertSame('court', User::mot_de_passe_refuse('a'));
		$this->assertSame('court', User::mot_de_passe_refuse(str_repeat('x', User::MOT_DE_PASSE_MIN - 1)));
	}

	public function test_longueur_comptee_en_caracteres_et_non_en_octets(): void
	{
		// Neuf caractères accentués font dix-huit octets : toujours trop court.
		$this->assertSame('court', User::mot_de_passe_refuse('éàüçèêëïô'));
	}

	public function test_trop_facile(): void
	{
		$this->assertSame('facile', User::mot_de_passe_refuse('aaaaaaaaaaaa'), 'moins de trois caractères différents');
		$this->assertSame('facile', User::mot_de_passe_refuse('ababababab'), 'deux caractères seulement');
		$this->assertSame('facile', User::mot_de_passe_refuse('AzertyUIOP'), 'les plus courants, sans égard à la casse');
		$this->assertSame('facile', User::mot_de_passe_refuse('NovaStrike99', 'novastrike99'), 'le pseudo lui-même');
	}

	public function test_un_bon_mot_de_passe_passe(): void
	{
		$this->assertNull(User::mot_de_passe_refuse('cheval-agrafe-batterie'));
		$this->assertNull(User::mot_de_passe_refuse('Epreuve-2026!x', 'epreuve-externe'));
	}
}
