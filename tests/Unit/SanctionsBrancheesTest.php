<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use NF\NeoFrag\Libraries\Moderation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Les sanctions de modération branchées sur les écritures des membres (m03, 2026-10-09).
 *
 * Jusque-là, la carte des sanctions (`modules/moderation/sanction_mapping.php`) n'était consultée par aucun formulaire :
 * une restriction des liens, de l'envoi de fichiers ou des commentaires, un bannissement de la galerie ou du profil
 * étaient prononcés et restaient sans effet. Ces épreuves tiennent deux choses que rien d'autre ne voit :
 *
 * - le motif des liens refusés (`Moderation::MOTIF_LIEN`) : ce qu'il attrape, et ce qu'il laisse passer ;
 * - la carte elle-même : chaque contrôle qu'elle nomme existe, chaque portée est une portée de `nf_sanctions`, et
 *   chaque `is_blocked_for(…, 'module.action')` du code nomme une entrée de la carte — une permission absente rend
 *   NULL, c'est-à-dire « rien ne bloque », en silence.
 *
 * Les messages et les sanctions réelles (un membre, une base) se mesurent sur l'atelier par de vraies requêtes :
 * `$this->lang()` ne vit pas hors d'une requête (cf. tests/Headless/EventReminderTest.php).
 */
final class SanctionsBrancheesTest extends TestCase
{
	private const CARTE = __DIR__.'/../../modules/moderation/sanction_mapping.php';

	/** Les contrôles que `Moderation::is_blocked_for()` sait évaluer. */
	private const CONTROLES = ['is_muted', 'is_banned', 'is_banned_global', 'can_upload_files', 'can_post_links', 'can_change_avatar', 'can_change_signature', 'can_comment'];

	/** Les portées de la colonne `nf_sanctions.scope`. */
	private const PORTEES = ['global', 'forum', 'talks', 'comments', 'wiki', 'gallery', 'guestbook', 'profile', 'bugtracker', 'recruits'];

	/** @return list<array{string}> */
	public static function textesAvecLien(): array
	{
		return [
			['https://exemple.fr'],
			['voir http://exemple.fr/page'],
			['ftp://serveur.exemple'],
			['www.exemple.fr'],
			['(www.exemple.fr)'],
			['WWW.EXEMPLE.FR'],
			['<a href="/ailleurs">ici</a>'],
			['[url=https://exemple.fr]ici[/url]'],
		];
	}

	/** @return list<array{string}> */
	public static function textesSansLien(): array
	{
		return [
			['un texte sans aucun lien'],
			['le fichier notes.www.txt'],
			['mon-www.site'],
			['https sans deux-points'],
			['<abbr title="href">x</abbr>'],
		];
	}

	#[DataProvider('textesAvecLien')]
	public function testLeMotifAttrapeUnLien(string $texte): void
	{
		$this->assertSame(1, preg_match(Moderation::MOTIF_LIEN, $texte), $texte);
	}

	#[DataProvider('textesSansLien')]
	public function testLeMotifLaissePasserUnTexteSansLien(string $texte): void
	{
		$this->assertSame(0, preg_match(Moderation::MOTIF_LIEN, $texte), $texte);
	}

	public function testLaCarteNeNommeQueDesControlesEtDesPorteesConnus(): void
	{
		$carte = require self::CARTE;

		$this->assertIsArray($carte);
		$this->assertNotEmpty($carte);

		foreach ($carte as $permission => $controles)
		{
			$this->assertMatchesRegularExpression('/^[a-z_]+\.[a-z_]+$/', $permission);
			$this->assertIsArray($controles, $permission);

			foreach ($controles as $controle => $argument)
			{
				$this->assertContains($controle, self::CONTROLES, $permission.' : contrôle inconnu « '.$controle.' »');

				if (in_array($controle, ['is_muted', 'is_banned'], TRUE))
				{
					$this->assertContains($argument, self::PORTEES, $permission.' : portée inconnue « '.$argument.' »');
				}
				else
				{
					$this->assertTrue($argument, $permission.' : « '.$controle.' » attend TRUE');
				}
			}
		}
	}

	public function testChaqueControleDuCodeNommeUneEntreeDeLaCarte(): void
	{
		$carte    = require self::CARTE;
		$racine   = dirname(__DIR__, 2);
		$appels   = [];
		$fichiers = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($racine, \FilesystemIterator::SKIP_DOTS));

		foreach ($fichiers as $fichier)
		{
			$chemin = str_replace('\\', '/', $fichier->getPathname());

			if (!str_ends_with($chemin, '.php') || preg_match('~/(vendor|node_modules|dist|tests|cache|\.git)/~', substr($chemin, strlen($racine))))
			{
				continue;
			}

			preg_match_all("/is_blocked_for\\([^;]*?'([a-z_]+\\.[a-z_]+)'/", (string) file_get_contents($chemin), $trouves);

			foreach ($trouves[1] as $permission)
			{
				$appels[$permission][] = substr($chemin, strlen($racine) + 1);
			}
		}

		$this->assertGreaterThanOrEqual(15, count($appels), 'les écritures des membres consultent la carte');

		foreach ($appels as $permission => $ou)
		{
			$this->assertArrayHasKey($permission, $carte, '« '.$permission.' » ('.implode(', ', array_unique($ou)).') n’est pas dans la carte : rien ne le bloquerait');
		}
	}
}
