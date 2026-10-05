<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Les préférences de notifications (chantier A, étape A4) ne règlent que les types qu'un module DÉCLARE par sa
 * méthode types_de_notification() : un type envoyé sans être déclaré arrive au membre sans qu'il puisse le couper.
 *
 * La première liste en a manqué deux, trouvés en recoupant les envois (2026-10-05) : les billets s'envoient sous
 * `article` (et non `articles`), et les petites annonces envoient aussi `classifieds-contact`. Cette épreuve lit
 * le code : chaque type qu'un appel à push(), push_unique() ou push_to_content_owner() nomme en clair, et celui
 * des contenus publiables (`notif_message`), doit être déclaré.
 */
final class TypesDeNotificationTest extends TestCase
{
	private const RACINE = __DIR__.'/../..';

	/** @return array<string, string> les types déclarés, et le module qui les déclare */
	private function declares(): array
	{
		$types = [];

		foreach (glob(self::RACINE.'/modules/*/*.php') ?: [] as $fichier)
		{
			$code = (string) file_get_contents($fichier);

			if (($debut = strpos($code, 'function types_de_notification')) === FALSE)
			{
				continue;
			}

			$corps = substr($code, $debut, (int) strpos($code, '}', $debut) - $debut);

			preg_match_all("/'type'\s*=>\s*'([^']+)'/", $corps, $m);

			foreach ($m[1] as $type)
			{
				$types[$type] = basename(dirname($fichier));
			}
		}

		return $types;
	}

	/** @return array<string, string> les types envoyés, et l'un des fichiers qui les envoie */
	private function envoyes(): array
	{
		$types    = [];
		$fichiers = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::RACINE.'/modules', \FilesystemIterator::SKIP_DOTS));

		foreach ($fichiers as $fichier)
		{
			if ($fichier->getExtension() !== 'php')
			{
				continue;
			}

			$code   = (string) file_get_contents($fichier->getPathname());
			$relatif = substr(str_replace('\\', '/', $fichier->getPathname()), strlen(self::RACINE) + 1);

			// push($destinataire, 'type', …) et push_unique($destinataire, 'type', …), sur une ou plusieurs lignes.
			preg_match_all("/->push(?:_unique)?\(\s*(?:[^,()]|\([^()]*\))+,\s*'([^']+)'/s", $code, $m);

			// push_to_content_owner($contenu, $id, 'type', …).
			preg_match_all("/->push_to_content_owner\(\s*[^,]+,\s*[^,]+,\s*'([^']+)'/s", $code, $n);

			// Les contenus publiables : leur `type` est celui de la notification (Publishable_Content::publish_due()).
			preg_match_all("/'type'\s*=>\s*'([^']+)'(?=[^\]]*'notif_message')/s", $code, $p);

			foreach (array_merge($m[1], $n[1], $p[1]) as $type)
			{
				$types[$type] ??= $relatif;
			}
		}

		return $types;
	}

	public function test_chaque_type_envoye_est_declare_aux_preferences(): void
	{
		$declares = $this->declares();
		$envoyes  = $this->envoyes();

		self::assertNotEmpty($envoyes, 'la lecture des envois ne trouve plus rien : le motif ne correspond plus au code');

		foreach (['talks_message', 'forum_reply', 'comment', 'news', 'article', 'classifieds-contact'] as $connu)
		{
			self::assertArrayHasKey($connu, $envoyes, 'l’envoi « '.$connu.' » n’est plus reconnu par la lecture du code');
		}

		foreach ($envoyes as $type => $fichier)
		{
			self::assertArrayHasKey($type, $declares, 'le type « '.$type.' » ('.$fichier.') n’est déclaré par aucun types_de_notification() : le membre ne peut pas le couper');
		}
	}

	public function test_un_type_n_est_declare_qu_une_fois(): void
	{
		$vus = [];

		foreach (glob(self::RACINE.'/modules/*/*.php') ?: [] as $fichier)
		{
			$code = (string) file_get_contents($fichier);

			if (($debut = strpos($code, 'function types_de_notification')) === FALSE)
			{
				continue;
			}

			preg_match_all("/'type'\s*=>\s*'([^']+)'/", substr($code, $debut, (int) strpos($code, '}', $debut) - $debut), $m);

			foreach ($m[1] as $type)
			{
				self::assertArrayNotHasKey($type, $vus, 'le type « '.$type.' » est déclaré deux fois ('.($vus[$type] ?? '').' et '.basename(dirname($fichier)).')');
				$vus[$type] = basename(dirname($fichier));
			}
		}

		self::assertNotEmpty($vus);
	}
}
