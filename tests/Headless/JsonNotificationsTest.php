<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Une réponse JSON emporte les notifications en attente, que sa donnée soit un tableau ou un objet (2026-10-07).
 *
 * Le Monitoring relisait son cache par `json_decode()` sans son second argument : un objet. La bibliothèque JSON y
 * posait les notifications en attente comme dans un tableau, et la réponse plantait (« Cannot use object of type
 * stdClass as array », neofrag/libraries/json.php) — dès qu'on allumait le débogage depuis le Monitoring, qui notifie.
 * check-mise-en-page l'a trouvé dans le journal en parcourant l'administration.
 *
 * L'amorçage headless n'ouvre pas de session : la bibliothèque est éprouvée avec une session simulée, qui tient une
 * notification en attente et note qu'on l'a vidée.
 */
final class JsonNotificationsTest extends HeadlessTestCase
{
	/** La session simulée de la dernière bibliothèque fabriquée. */
	private object $simulee;

	/** La bibliothèque, branchée sur une session simulée qui tient `$attente`. */
	private function json(array $attente): \NF\NeoFrag\Libraries\Json
	{
		$session = new class($attente) {
			public bool $videe = FALSE;

			public function __construct(public array $attente)
			{
			}

			public function destroy(string $nom): void
			{
				$this->videe = $nom === 'notifications';
			}
		};

		$this->simulee = $session;

		return new class(\NeoFrag(), $session) extends \NF\NeoFrag\Libraries\Json {
			public function __construct($caller, public object $session)
			{
				parent::__construct($caller);
			}

			public function session(string $nom): mixed
			{
				return $nom === 'notifications' ? ($this->session->attente ?: NULL) : NULL;
			}
		};
	}

	public function test_un_objet_emporte_les_notifications_en_attente(): void
	{
		$json    = $this->json([['message' => 'Débogage allumé', 'type' => 'success']]);
		$reponse = json_decode((string) $json((object) ['storage' => ['total' => 3]]), TRUE);

		$this->assertIsArray($reponse, 'La réponse doit être du JSON valide.');
		$this->assertSame(3, $reponse['storage']['total'] ?? NULL);
		$this->assertSame('Débogage allumé', $reponse['notify'][0]['message'] ?? NULL);
		$this->assertTrue($this->simulee->videe, 'Les notifications servies se retirent de la session.');
	}

	public function test_un_tableau_aussi(): void
	{
		$json    = $this->json([['message' => 'Enregistré', 'type' => 'success']]);
		$reponse = json_decode((string) $json(['ok' => TRUE]), TRUE);

		$this->assertTrue($reponse['ok'] ?? NULL);
		$this->assertSame('Enregistré', $reponse['notify'][0]['message'] ?? NULL);
	}
}
