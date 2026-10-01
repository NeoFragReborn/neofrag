<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * couplage(forum): les tables du forum ne sont lues que par `_forums()`, après la garde
 * `module('forum')` ; sans le forum, l'adresse `forums` répond 404 « module_unavailable ».
 * couplage(discord): les adresses `discord/*` passent par `_modele_discord()`, qui répond 404
 * « module_unavailable » sans le module Discord.
 */

namespace NF\Modules\Api\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Api\Api;

class Index extends Controller_Module
{
	/*
	 * L'API REST, version 1 (2026-10-01). Un seul point d'entrée : il authentifie, limite
	 * le débit, aiguille selon l'adresse et la méthode, et répond TOUJOURS en JSON — y compris pour une
	 * adresse inconnue ou une clé refusée. Il n'y a ni session, ni thème, ni redirection de langue
	 * (cf. `Url`, qui laisse passer `api/`) : la réponse est écrite et la requête se termine ici.
	 *
	 * Forme des réponses : `{"data": …}` en cas de succès ; `{"error": {"code": "…", "message": "…"}}`
	 * sinon, avec le code HTTP qui convient. Les codes d'erreur sont stables, les messages lisibles.
	 */

	/** Échecs d'authentification permis par minute et par adresse IP, avant blocage. */
	private const ECHECS_PAR_MINUTE = 20;

	public function _v1(...$segments)
	{
		$segments = array_values(array_filter(array_map('strval', $segments), static fn (string $s): bool => $s !== ''));
		$methode  = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
		$ip       = \NF\NeoFrag\Libraries\Rate_Limit::client_ip();
		$limites  = $this->rate_limit;

		// Une adresse qui accumule les clés refusées est bloquée un temps : deviner une clé de
		// 160 bits est hors de portée, mais rien ne doit permettre de marteler l'API.
		if (!($etat = $limites->check('api:ip:'.$ip))['allowed'])
		{
			$this->_erreur(429, 'too_many_requests', $this->lang('Trop de requêtes. Réessayez plus tard.'), ['Retry-After' => (string) $etat['retry_after']]);
		}

		if (!($jeton = $this->_jeton()))
		{
			$limites->hit('api:ip:'.$ip, self::ECHECS_PAR_MINUTE, 60, 300);
			$this->_erreur(401, 'unauthorized', $this->lang('Clé d’accès absente, invalide ou révoquée.'), ['WWW-Authenticate' => 'Bearer realm="api"']);
		}

		// Le limiteur bloque dès que le compte atteint le seuil : seuil + 1 pour permettre PAR_MINUTE requêtes.
		$compte = $limites->hit('api:token:'.$jeton['token_id'], Api::PAR_MINUTE + 1, 60, 60);

		$this->_entetes = [
			'X-RateLimit-Limit'     => (string) Api::PAR_MINUTE,
			'X-RateLimit-Remaining' => (string) max(0, Api::PAR_MINUTE - (int) $compte['attempts']),
		];

		if ($compte['locked'])
		{
			$this->_erreur(429, 'too_many_requests', $this->lang('Trop de requêtes. Réessayez plus tard.'), ['Retry-After' => (string) $compte['retry_after']]);
		}

		$this->_modele()->marquer_usage($jeton['token_id'], $ip);
		$this->_jeton_courant = $jeton;
		Api::$cle_courante    = $jeton['token_id'];

		// L'aiguillage : [méthode, motif, droit requis, action]. Un motif est une suite de segments ;
		// `#` y désigne un nombre.
		$routes = [
			['GET', ['status'],                    NULL,           fn () => $this->_statut()],
			['GET', ['members', '#'],              'members:read', fn (array $p) => $this->_membre((int) $p[0])],
			['GET', ['members', 'discord', '#'],   'members:read', fn (array $p) => $this->_membre_discord((string) $p[0])],
			['GET', ['groups'],                    'members:read', fn () => $this->_groupes()],
			['GET', ['forums'],                    'forum:read',   fn () => $this->_forums()],
			['GET', ['forum', 'topics', '#'],      'forum:read',   fn (array $p) => $this->_sujet((int) $p[0])],
			['GET', ['forum', 'messages', '#'],    'forum:read',   fn (array $p) => $this->_message((int) $p[0])],
			['GET', ['events'],                    'events:read',  fn () => $this->_evenements()],
			['POST',   ['forum', 'topics'],                  'forum:write', fn () => $this->_creer_sujet()],
			['POST',   ['forum', 'topics', '#', 'messages'], 'forum:write', fn (array $p) => $this->_repondre_sujet((int) $p[0])],
			['PATCH',  ['forum', 'messages', '#'],           'forum:write', fn (array $p) => $this->_modifier_message((int) $p[0])],
			['DELETE', ['forum', 'messages', '#'],           'forum:write', fn (array $p) => $this->_supprimer_message((int) $p[0])],
			['GET',    ['discord', 'config'],                'discord:bot', fn () => $this->_discord_config()],
			['POST',   ['discord', 'heartbeat'],             'discord:bot', fn () => $this->_discord_signe_de_vie()],
			['POST',   ['discord', 'logs'],                  'discord:bot', fn () => $this->_discord_journal()],
			['GET',    ['discord', 'members'],               'discord:bot', fn () => $this->_discord_membres()],
			['GET',    ['discord', 'links'],                 'discord:bot', fn () => $this->_discord_lien()],
			['POST',   ['discord', 'links'],                 'discord:bot', fn () => $this->_discord_lier()],
		];

		$autorisees = [];

		foreach ($routes as [$verbe, $motif, $droit, $action])
		{
			if (($parametres = $this->_correspond($motif, $segments)) === NULL)
			{
				continue;
			}

			if ($verbe !== $methode)
			{
				$autorisees[] = $verbe;
				continue;
			}

			if ($droit !== NULL && !in_array($droit, $jeton['scopes'], TRUE))
			{
				$this->_erreur(403, 'forbidden', $this->lang('Cette clé n’a pas le droit « %s ».', $droit));
			}

			$donnees = $action($parametres);

			$this->_repondre($this->_code, ['data' => $donnees]);
		}

		if ($autorisees)
		{
			$this->_erreur(405, 'method_not_allowed', $this->lang('Méthode non permise sur cette adresse.'), ['Allow' => implode(', ', array_unique($autorisees))]);
		}

		$this->_erreur(404, 'not_found', $this->lang('Adresse inconnue de l’API.'));
	}

	/** @var array<string, string> */
	private array $_entetes = [];

	/** Le code HTTP d'une réponse réussie : 200, ou 201 quand l'action a créé quelque chose. */
	private int $_code = 200;

	/** Longueur maximale d'un contenu écrit par l'API, en caractères. */
	private const CONTENU_MAX = 20000;

	/** @var array{token_id: int, name: string, scopes: list<string>}|null */
	private ?array $_jeton_courant = NULL;

	// ── Les actions ─────────────────────────────────────────────────────────

	private function _statut(): array
	{
		return [
			'site'    => (string) $this->config->nf_name,
			'version' => NEOFRAG_VERSION,
			'api'     => 'v1',
			'token'   => ['name' => $this->_jeton_courant['name'] ?? '', 'scopes' => $this->_jeton_courant['scopes'] ?? []],
		];
	}

	private function _membre(int $user_id): array
	{
		$membre = $this->db	->select('u.id', 'u.username', 'u.registration_date', 'up.avatar')
							->from('nf_user u')
							->join('nf_user_profile up', 'up.id = u.id', 'LEFT')
							->where('u.id', $user_id)
							->where('u.deleted', FALSE)
							->row();

		if (!is_array($membre) || !$membre)
		{
			$this->_erreur(404, 'member_not_found', $this->lang('Membre introuvable.'));
		}

		$discord = $this->_discord_de($user_id);
		$avatar  = !empty($membre['avatar']) ? (string) NeoFrag()->model2('file', (int) $membre['avatar'])->path() : '';

		return [
			'id'                => (int) $membre['id'],
			'username'          => (string) $membre['username'],
			'url'               => absolute_url('user/'.(int) $membre['id'].'/'.url_title((string) $membre['username'])),
			'avatar'            => $avatar !== '' ? (strpos($avatar, '://') === FALSE ? site_origin().'/'.ltrim($avatar, '/') : $avatar) : NULL,
			'registration_date' => date('c', (int) strtotime((string) $membre['registration_date'])),
			'groups'            => array_values(array_map('strval', (array) $this->_groupes_coeur()((int) $membre['id']))),
			'discord'           => $discord,
		];
	}

	private function _membre_discord(string $discord_id): array
	{
		$authentificateur = $this->_authentificateur_discord();
		$user_id          = $authentificateur ? $this->db	->select('a.user_id')
															->from('nf_user_auth a')
															->join('nf_user u', 'u.id = a.user_id AND u.deleted = "0"', 'INNER')
															->where('a.authenticator_id', $authentificateur)
															->where('a.key', $discord_id)
															->row() : NULL;

		if (!$user_id)
		{
			$this->_erreur(404, 'member_not_found', $this->lang('Aucun membre n’a lié ce compte Discord.'));
		}

		return $this->_membre((int) $user_id);
	}

	private function _groupes(): array
	{
		$groupes = [];

		foreach ((array) $this->_groupes_coeur()() as $cle => $groupe)
		{
			if ($cle === 'visitors')
			{
				continue;
			}

			$groupes[] = [
				'key'     => (string) $cle,
				'title'   => (string) ($groupe['title'] ?? $cle),
				'color'   => (string) ($groupe['color'] ?? ''),
				'icon'    => (string) ($groupe['icon'] ?? ''),
				'hidden'  => !empty($groupe['hidden']),
				'auto'    => !empty($groupe['auto']),
				'members' => is_array($groupe['users'] ?? NULL) ? count($groupe['users']) : 0,
			];
		}

		return $groupes;
	}

	private function _forums(): array
	{
		$forum = $this->module('forum');

		if (!$forum || !($modele = $forum->model('forum')) instanceof \NF\Modules\Forum\Models\Forum)
		{
			$this->_erreur(404, 'module_unavailable', $this->lang('Le forum n’est pas installé sur ce site.'));
		}

		$categories = [];

		foreach ($this->db	->select('c.category_id', $modele->titre_categorie('c').' AS title', 'c.order')
							->from('nf_forum_categories c')
							->order_by('c.order', 'c.category_id')
							->get() as $c)
		{
			$categories[(int) $c['category_id']] = ['id' => (int) $c['category_id'], 'title' => (string) $c['title'], 'forums' => []];
		}

		$forums = [];

		foreach ($this->db	->select('f.forum_id', 'f.parent_id', 'f.is_subforum', $modele->titre_forum('f').' AS title', $modele->titre_forum('f', 'description').' AS description', 'f.icon', 'f.count_topics', 'f.count_messages', 'u.url')
							->from('nf_forum f')
							->join('nf_forum_url u', 'u.forum_id = f.forum_id', 'LEFT')
							->order_by('f.order', 'f.forum_id')
							->get() as $f)
		{
			$forums[(int) $f['forum_id']] = $f + ['subforums' => []];
		}

		$rendu = static fn (array $f): array => [
			'id'          => (int) $f['forum_id'],
			'title'       => (string) $f['title'],
			'description' => (string) $f['description'],
			'icon'        => (string) $f['icon'],
			'topics'      => (int) $f['count_topics'],
			'replies'     => (int) $f['count_messages'],
			'link'        => (string) ($f['url'] ?? '') !== '' ? (string) $f['url'] : NULL,
			'url'         => absolute_url('forum/'.(int) $f['forum_id'].'/'.url_title((string) $f['title'])),
		];

		foreach ($forums as $f)
		{
			if ($f['is_subforum'] && isset($forums[(int) $f['parent_id']]))
			{
				$forums[(int) $f['parent_id']]['subforums'][] = $rendu($f);
			}
		}

		foreach ($forums as $f)
		{
			if (!$f['is_subforum'] && isset($categories[(int) $f['parent_id']]))
			{
				$categories[(int) $f['parent_id']]['forums'][] = $rendu($f) + ['subforums' => $f['subforums']];
			}
		}

		return array_values($categories);
	}

	/**
	 * Un sujet du forum : son forum, son titre, son auteur, ses états, son préfixe, sa solution.
	 * La clé lit tout le forum, catégories réservées comprises : c'est l'administrateur qui la donne.
	 */
	private function _sujet(int $topic_id): array
	{
		$modele = $this->_modele_forum();
		$sujet  = $this->db	->select('t.topic_id', 't.forum_id', 't.title', 't.status', 't.views', 't.count_messages', 't.message_id', 't.last_message_id', 't.prefix_id', $modele->solution_vivante('t').' AS solution_message_id', 'm.user_id', 'm.identity_id', 'm.date', 'u.username')
							->from('nf_forum_topics t')
							->join('nf_forum_messages m', 'm.message_id = t.message_id', 'LEFT')
							->join('nf_user u', 'u.id = m.user_id AND u.deleted = "0"', 'LEFT')
							->where('t.topic_id', $topic_id)
							->row();

		if (!is_array($sujet) || !$sujet)
		{
			$this->_erreur(404, 'topic_not_found', $this->lang('Sujet introuvable.'));
		}

		$prefixes = $modele->prefixes();
		$prefixe  = $prefixes[(int) $sujet['prefix_id']] ?? NULL;

		return [
			'id'                  => (int) $sujet['topic_id'],
			'forum_id'            => (int) $sujet['forum_id'],
			'title'               => (string) $sujet['title'],
			'author'              => $this->_auteur($sujet),
			'external_author'     => $this->_auteur_externe($sujet),
			'created_at'          => $sujet['date'] ? date('c', (int) strtotime((string) $sujet['date'])) : NULL,
			'announce'            => in_array((string) $sujet['status'], ['-2', '1'], TRUE),
			'locked'              => in_array((string) $sujet['status'], ['-2', '-1'], TRUE),
			'prefix'              => $prefixe ? ['id' => $prefixe['prefix_id'], 'title' => $prefixe['title'], 'color' => $prefixe['color']] : NULL,
			'first_message_id'    => $sujet['message_id'] ? (int) $sujet['message_id'] : NULL,
			'last_message_id'     => $sujet['last_message_id'] ? (int) $sujet['last_message_id'] : NULL,
			'solution_message_id' => $sujet['solution_message_id'] ? (int) $sujet['solution_message_id'] : NULL,
			'replies'             => (int) $sujet['count_messages'],
			'views'               => (int) $sujet['views'],
			'url'                 => absolute_url('forum/topic/'.(int) $sujet['topic_id'].'/'.url_title((string) $sujet['title'])),
		];
	}

	/**
	 * Un message du forum : son sujet, son auteur, sa date, son contenu en HTML (tel que le forum
	 * l'affiche) et en texte brut. Un message supprimé rend `deleted: true` et aucun contenu.
	 */
	private function _message(int $message_id): array
	{
		$modele  = $this->_modele_forum();
		$message = $this->db	->select('m.message_id', 'm.topic_id', 'm.user_id', 'm.identity_id', 'm.message', 'm.date', 'm.deleted_at', 't.forum_id', 't.title', 't.message_id AS first_message_id', 'u.username')
								->from('nf_forum_messages m')
								->join('nf_forum_topics t', 't.topic_id = m.topic_id', 'INNER')
								->join('nf_user u', 'u.id = m.user_id AND u.deleted = "0"', 'LEFT')
								->where('m.message_id', $message_id)
								->row();

		if (!is_array($message) || !$message)
		{
			$this->_erreur(404, 'message_not_found', $this->lang('Message introuvable.'));
		}

		$supprime = $message['message'] === NULL || !empty($message['deleted_at']);
		$forum    = $this->module('forum');
		$html     = $supprime || !$forum instanceof \NF\Modules\Forum\Forum ? '' : (string) $forum->forum_render((string) $message['message']);

		return [
			'id'         => (int) $message['message_id'],
			'topic_id'   => (int) $message['topic_id'],
			'forum_id'   => (int) $message['forum_id'],
			'author'     => $this->_auteur($message),
			'external_author' => $this->_auteur_externe($message),
			'created_at' => date('c', (int) strtotime((string) $message['date'])),
			'is_first'   => (int) $message['message_id'] === (int) $message['first_message_id'],
			'deleted'    => $supprime,
			'html'       => $supprime ? NULL : $html,
			'text'       => $supprime ? NULL : trim(html_entity_decode(strip_tags(str_replace(['<br />', '<br>', '</p>'], "\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
			// Pas d'adresse propre à un message : le forum y mène par l'ancre de sa page de sujet.
			'url'        => absolute_url('forum/topic/'.(int) $message['topic_id'].'/'.url_title((string) $message['title'])).'#'.(int) $message['message_id'],
		];
	}

	/**
	 * Le fil d'événements : ceux qui suivent le curseur `after`, dans l'ordre, par lots de `limit`
	 * (100 par défaut, 500 au plus). Le programme garde le dernier `id` reçu et le redonne la fois
	 * suivante ; `more` dit s'il reste des événements à lire tout de suite.
	 */
	private function _evenements(): array
	{
		$apres   = max(0, (int) ($_GET['after'] ?? 0));
		$limite  = max(1, min(500, (int) ($_GET['limit'] ?? 100)));
		$lignes  = (array) $this->db	->select('event_id', 'type', 'payload', 'source', 'created_at')
										->from('nf_api_events')
										->where('event_id >', $apres)
										->order_by('event_id')
										->limit($limite + 1)
										->get();
		$encore  = count($lignes) > $limite;
		$lignes  = array_slice($lignes, 0, $limite);
		$suivant = $lignes ? (int) end($lignes)['event_id'] : $apres;

		return [
			'events' => array_map(static fn (array $e): array => [
				'id'         => (int) $e['event_id'],
				'type'       => (string) $e['type'],
				'data'       => (array) json_decode((string) $e['payload'], TRUE),
				'source'     => $e['source'] !== NULL ? ['token_id' => (int) $e['source']] : NULL,
				'created_at' => date('c', (int) strtotime((string) $e['created_at'])),
			], $lignes),
			'next'   => $suivant,
			'more'   => $encore,
		];
	}

	// ── Le bot Discord ─────────────────────────────────────────
	//
	// Le bot ne connaît que l'adresse du site et sa clé d'API : il lit ici sa configuration — sa clé
	// Discord, déchiffrée pour lui seul, son serveur, les correspondances —, y rend compte de son état
	// et de son journal, et y garde le lien entre un sujet et son fil, un message et le sien.

	/** La configuration du bot. */
	private function _discord_config(): array
	{
		$modele   = $this->_modele_discord();
		$reglages = $modele->reglages();

		return [
			'token'     => $modele->jeton(),
			'client_id' => $reglages['client_id'],
			'guild_id'  => $reglages['guild_id'],
			'running'   => $reglages['running'],
			'nicknames' => $reglages['nicknames'],
			'channels'  => array_map(static fn (array $s): array => ['channel_id' => $s['channel_id'], 'forum_id' => $s['forum_id'], 'mode' => $s['mode'], 'emoji' => $s['emoji']], $modele->salons()),
			'roles'     => array_map(static fn (array $r): array => ['group_key' => $r['group_key'], 'role_id' => $r['role_id']], $modele->roles()),
			'version'   => $reglages['version'],
			// Le dernier événement du fil : le bot qui (re)démarre lit le fil à partir d'ici — il vient
			// de tout resynchroniser, le passé ne lui apprendrait rien.
			'events_cursor' => (int) $this->db->select('IFNULL(MAX(event_id), 0)')->from('nf_api_events')->row(),
			// La clé qui lit cette configuration : ce que le bot écrit par l'API porte sa trace dans le
			// fil (`source`), et il ne le recopie pas une seconde fois sur Discord.
			'api_token_id'  => Api::$cle_courante,
			'texts'         => $modele->textes_affiches(),
		];
	}

	/**
	 * Le signe de vie du bot : son état et la description de son serveur (salons, rôles — pour les
	 * listes de l'administration). Il reçoit en retour l'interrupteur, le numéro de configuration et
	 * les commandes en attente.
	 */
	private function _discord_signe_de_vie(): array
	{
		$modele = $this->_modele_discord();
		$corps  = $this->_corps();
		$guilde = is_array($corps['guild'] ?? NULL) ? $corps['guild'] : [];

		$modele->poser_etat('heartbeat', (string) json_encode([
			'at'        => time(),
			'version'   => mb_substr((string) ($corps['version'] ?? ''), 0, 40),
			'connected' => !empty($corps['connected']),
			'intents'   => ['members' => !empty($corps['intents']['members']), 'content' => !empty($corps['intents']['content'])],
			'guild'     => [
				'id'       => (string) ($guilde['id'] ?? ''),
				'name'     => mb_substr((string) ($guilde['name'] ?? ''), 0, 100),
				'channels' => array_slice(array_values(array_filter(array_map(static fn ($c) => is_array($c) ? ['id' => (string) ($c['id'] ?? ''), 'name' => mb_substr((string) ($c['name'] ?? ''), 0, 100), 'type' => (int) ($c['type'] ?? -1)] : NULL, (array) ($guilde['channels'] ?? [])))), 0, 500),
				'roles'    => array_slice(array_values(array_filter(array_map(static fn ($r) => is_array($r) ? ['id' => (string) ($r['id'] ?? ''), 'name' => mb_substr((string) ($r['name'] ?? ''), 0, 100), 'managed' => !empty($r['managed'])] : NULL, (array) ($guilde['roles'] ?? [])))), 0, 250),
			],
		], JSON_UNESCAPED_UNICODE));

		$reglages = $modele->reglages();

		return ['running' => $reglages['running'], 'version' => $reglages['version'], 'commands' => $modele->prendre_commandes()];
	}

	/**
	 * Des lignes du journal du bot : `entries`, chacune `level` (info, warn, error), `message` (la
	 * phrase en français) et, pour la traduire, `template` et `args`.
	 */
	private function _discord_journal(): array
	{
		$modele = $this->_modele_discord();
		$lignes = array_slice((array) ($this->_corps()['entries'] ?? []), 0, 50);

		foreach ($lignes as $l)
		{
			if (is_array($l) && trim((string) ($l['message'] ?? '')) !== '')
			{
				$valeurs = array_values(array_filter((array) ($l['args'] ?? []), 'is_scalar'));
				$modele->journaliser((string) ($l['level'] ?? 'info'), (string) $l['message'], is_string($l['template'] ?? NULL) ? $l['template'] : '', array_map('strval', $valeurs));
			}
		}

		return ['received' => count($lignes)];
	}

	/** Les membres qui ont lié leur compte Discord : leur pseudo et leurs groupes, pour les rôles et les pseudos. */
	private function _discord_membres(): array
	{
		$this->_modele_discord();

		if (!($authentificateur = $this->_authentificateur_discord()))
		{
			return [];
		}

		$membres = [];
		$coeur   = $this->_groupes_coeur();

		foreach ((array) $this->db	->select('a.key', 'u.id', 'u.username')
									->from('nf_user_auth a')
									->join('nf_user u', 'u.id = a.user_id AND u.deleted = "0"', 'INNER')
									->where('a.authenticator_id', $authentificateur)
									->get() as $m)
		{
			$membres[] = [
				'discord_id' => (string) $m['key'],
				'member_id'  => (int) $m['id'],
				'username'   => (string) $m['username'],
				'groups'     => array_values(array_map('strval', (array) $coeur((int) $m['id']))),
			];
		}

		return $membres;
	}

	/** Le lien d'un sujet ou d'un message : `type` (topic, message) et `site_id` ou `discord_id`. */
	private function _discord_lien(): array
	{
		$type = (string) ($_GET['type'] ?? '');

		if (!in_array($type, ['topic', 'message'], TRUE) || (empty($_GET['site_id']) && empty($_GET['discord_id'])))
		{
			$this->_repondre(422, ['error' => ['code' => 'validation_failed', 'message' => (string) $this->lang('Certains champs sont invalides.'), 'fields' => ['type' => 'topic | message', 'site_id' => 'ou discord_id']]]);
		}

		$lien = $this->_modele_discord()->lien($type, !empty($_GET['site_id']) ? (int) $_GET['site_id'] : NULL, !empty($_GET['discord_id']) ? (string) $_GET['discord_id'] : NULL);

		if (!$lien)
		{
			$this->_erreur(404, 'link_not_found', $this->lang('Aucun lien pour cet élément.'));
		}

		return $lien;
	}

	private function _discord_lier(): array
	{
		$corps = $this->_corps();
		$type  = (string) ($corps['type'] ?? '');
		$site  = (int) ($corps['site_id'] ?? 0);
		$disc  = (string) ($corps['discord_id'] ?? '');

		if (!in_array($type, ['topic', 'message'], TRUE) || $site <= 0 || !ctype_digit($disc))
		{
			$this->_repondre(422, ['error' => ['code' => 'validation_failed', 'message' => (string) $this->lang('Certains champs sont invalides.'), 'fields' => ['type' => 'topic | message', 'site_id' => '> 0', 'discord_id' => 'nombre']]]);
		}

		$this->_modele_discord()->lier($type, $site, $disc);
		$this->_code = 201;

		return ['type' => $type, 'site_id' => $site, 'discord_id' => $disc];
	}

	/** Le modèle du module Discord, s'il est installé ; sinon 404 « module_unavailable ». */
	private function _modele_discord(): \NF\Modules\Discord\Models\Discord
	{
		$discord = $this->module('discord');

		if (!$discord || !($modele = $discord->model('discord')) instanceof \NF\Modules\Discord\Models\Discord)
		{
			$this->_erreur(404, 'module_unavailable', $this->lang('Le module Discord n’est pas installé sur ce site.'));
		}

		return $modele;
	}

	// ── Écrire sur le forum (étape 3) ─────────────────────────
	//
	// L'API écrit AU NOM d'un auteur, jamais en son nom propre : un membre du site, ou un compte
	// Discord. Un compte Discord lié à un membre publie sous ce membre — son vrai compte, avec son
	// pseudo, son avatar et son historique ; un compte non lié publie sous son identité externe, en
	// mode public (son pseudo Discord) jusqu'à ce qu'il en choisisse un autre.
	//
	// La clé écrit là où on lui dit d'écrire : faire correspondre un salon à un forum est le choix de
	// l'administrateur, qui lui a donné le droit `forum:write`.

	private function _creer_sujet(): array
	{
		$corps   = $this->_corps();
		$modele  = $this->_modele_forum();
		$forum   = (int) ($corps['forum_id'] ?? 0);
		$titre   = trim((string) ($corps['title'] ?? ''));
		$erreurs = [];

		$cible = $forum ? $this->db->select('f.forum_id', 'u.url')->from('nf_forum f')->join('nf_forum_url u', 'u.forum_id = f.forum_id', 'LEFT')->where('f.forum_id', $forum)->row() : NULL;

		if (!is_array($cible) || !$cible)
		{
			$erreurs['forum_id'] = (string) $this->lang('Forum inconnu.');
		}
		else if ((string) ($cible['url'] ?? '') !== '')
		{
			$erreurs['forum_id'] = (string) $this->lang('Ce forum est un lien vers une adresse extérieure.');
		}

		if ($titre === '' || mb_strlen($titre) > 100)
		{
			$erreurs['title'] = (string) $this->lang('De 1 à %d caractères.', 100);
		}

		$contenu = $this->_contenu($corps, $erreurs);
		$auteur  = $this->_auteur_ecriture($corps, $erreurs);
		$prefixe = (int) ($corps['prefix_id'] ?? 0);

		if ($prefixe && !isset($modele->prefixes()[$prefixe]))
		{
			$erreurs['prefix_id'] = (string) $this->lang('Préfixe inconnu.');
		}

		$this->_valider($erreurs);

		$topic_id = (int) $modele->add_topic($forum, $titre, $contenu, '0', $auteur);

		if ($prefixe)
		{
			$modele->set_prefix($topic_id, $prefixe);
		}

		$this->_code = 201;

		return $this->_sujet($topic_id);
	}

	private function _repondre_sujet(int $topic_id): array
	{
		$corps  = $this->_corps();
		$modele = $this->_modele_forum();
		$sujet  = $this->db->select('topic_id', 'status')->from('nf_forum_topics')->where('topic_id', $topic_id)->row();

		if (!is_array($sujet) || !$sujet)
		{
			$this->_erreur(404, 'topic_not_found', $this->lang('Sujet introuvable.'));
		}

		if (in_array((string) $sujet['status'], ['-2', '-1'], TRUE))
		{
			$this->_erreur(409, 'topic_locked', $this->lang('Ce sujet est verrouillé.'));
		}

		$erreurs  = [];
		$contenu  = $this->_contenu($corps, $erreurs);
		$auteur   = $this->_auteur_ecriture($corps, $erreurs);
		$reponse  = !empty($corps['reply_to']) ? (int) $corps['reply_to'] : NULL;

		$this->_valider($erreurs);

		$message_id = (int) $modele->add_message($topic_id, $contenu, $reponse, $auteur);

		$this->_code = 201;

		return $this->_message($message_id);
	}

	/** Modifier un message : seulement au nom de son auteur — la personne qui l'a écrit le corrige. */
	private function _modifier_message(int $message_id): array
	{
		$corps   = $this->_corps();
		$message = $this->_message_de_l_auteur($message_id, $corps);
		$erreurs = [];
		$contenu = $this->_contenu($corps, $erreurs);

		$this->_valider($erreurs);

		$this->db->where('message_id', $message_id)->update('nf_forum_messages', ['message' => $contenu]);

		// Le forum, chargé par `_modele_forum()`, écoute cet événement et le confie au fil de l'API ;
		// la charge a la forme de celle du forum, pour ses autres écouteurs (mentions…).
		$this->events->fire('forum.post.edited', [
			'message_id'      => $message_id,
			'topic_id'        => (int) $message['topic_id'],
			'forum_id'        => (int) $message['forum_id'],
			'user_id'         => $message['user_id'] ? (int) $message['user_id'] : NULL,
			'old_message'     => (string) $message['message'],
			'new_message'     => $contenu,
			'is_topic'        => (int) $message['message_id'] === (int) $message['first_message_id'],
			'mentioned_users' => [],
		]);

		return $this->_message($message_id);
	}

	/**
	 * Supprimer un message au nom de son auteur : il passe à la corbeille du forum, comme une
	 * suppression faite sur le site (restaurable par un modérateur). Le premier message d'un sujet
	 * ne se supprime pas par l'API : il porterait tout le sujet avec lui.
	 */
	private function _supprimer_message(int $message_id): array
	{
		$corps   = $this->_corps();
		$message = $this->_message_de_l_auteur($message_id, $corps);

		if ((int) $message['message_id'] === (int) $message['first_message_id'])
		{
			$this->_erreur(409, 'is_first_message', $this->lang('Le premier message d’un sujet ne se supprime pas par l’API.'));
		}

		$this->db->where('message_id', $message_id)->update('nf_forum_messages', 'message = NULL, deleted_at = CURRENT_TIMESTAMP, deleted_by = NULL, deleted_reason = "'.$this->db->escape_string((string) $this->lang('Supprimé depuis un programme relié au site (API).')).'"');

		// Même chemin que la modification : le forum écoute, et confie l'événement au fil de l'API.
		$this->events->fire('forum.post.deleted', [
			'message_id'  => $message_id,
			'topic_id'    => (int) $message['topic_id'],
			'forum_id'    => (int) $message['forum_id'],
			'is_topic'    => FALSE,
			'hard_delete' => FALSE,
			'deleted_by'  => NULL,
			'reason'      => '',
		]);

		return $this->_message($message_id);
	}

	/**
	 * Le message, s'il appartient à l'auteur donné dans la requête — sinon 404, ou 403.
	 *
	 * @return array<string, mixed>
	 */
	private function _message_de_l_auteur(int $message_id, array $corps): array
	{
		$message = $this->db	->select('m.message_id', 'm.topic_id', 'm.user_id', 'm.identity_id', 'm.message', 't.forum_id', 't.message_id AS first_message_id')
								->from('nf_forum_messages m')
								->join('nf_forum_topics t', 't.topic_id = m.topic_id', 'INNER')
								->where('m.message_id', $message_id)
								->row();

		if (!is_array($message) || !$message || $message['message'] === NULL)
		{
			$this->_erreur(404, 'message_not_found', $this->lang('Message introuvable.'));
		}

		$erreurs = [];
		$auteur  = $this->_auteur_ecriture($corps, $erreurs, FALSE);

		$this->_valider($erreurs);

		$meme = $auteur['user_id'] ? (int) $message['user_id'] === $auteur['user_id'] : ($auteur['identity_id'] && (int) $message['identity_id'] === $auteur['identity_id']);

		if (!$meme)
		{
			$this->_erreur(403, 'not_author', $this->lang('Seul l’auteur de ce message peut le modifier ou le supprimer par l’API.'));
		}

		return $message;
	}

	/**
	 * L'auteur d'une écriture, lu dans la requête : `author.member_id`, ou `author.discord` (`id`,
	 * `username`, `avatar`). Un compte Discord lié publie sous son membre ; sinon sous son identité,
	 * créée au besoin — sauf pour retrouver l'auteur d'un message existant (`$creer` à FALSE).
	 *
	 * @param array<string, string> $erreurs
	 * @return array{user_id: ?int, identity_id: ?int, name: string}
	 */
	private function _auteur_ecriture(array $corps, array &$erreurs, bool $creer = TRUE): array
	{
		$auteur = is_array($corps['author'] ?? NULL) ? $corps['author'] : [];

		if (!empty($auteur['member_id']))
		{
			$membre = $this->db->select('id', 'username')->from('nf_user')->where('id', (int) $auteur['member_id'])->where('deleted', FALSE)->row();

			if (!is_array($membre) || !$membre)
			{
				$erreurs['author'] = (string) $this->lang('Membre introuvable.');

				return ['user_id' => NULL, 'identity_id' => NULL, 'name' => ''];
			}

			return ['user_id' => (int) $membre['id'], 'identity_id' => NULL, 'name' => (string) $membre['username']];
		}

		$discord = is_array($auteur['discord'] ?? NULL) ? $auteur['discord'] : [];
		$id      = (string) ($discord['id'] ?? '');
		$pseudo  = trim((string) ($discord['username'] ?? ''));

		if (!ctype_digit($id) || strlen($id) > 20 || ($creer && ($pseudo === '' || mb_strlen($pseudo) > 100)))
		{
			$erreurs['author'] = (string) $this->lang('Un auteur est attendu : author.member_id, ou author.discord avec son id (un nombre) et son username.');

			return ['user_id' => NULL, 'identity_id' => NULL, 'name' => ''];
		}

		// Un compte Discord lié publie toujours sous son vrai compte.
		if (($authentificateur = $this->_authentificateur_discord()) && ($lie = $this->db	->select('u.id', 'u.username')
																						->from('nf_user_auth a')
																						->join('nf_user u', 'u.id = a.user_id AND u.deleted = "0"', 'INNER')
																						->where('a.authenticator_id', $authentificateur)
																						->where('a.key', $id)
																						->row()))
		{
			return ['user_id' => (int) $lie['id'], 'identity_id' => NULL, 'name' => (string) $lie['username']];
		}

		$modele = $this->_modele_forum();

		if (!$creer)
		{
			$identite = $this->db->select('identity_id')->from('nf_forum_identities')->where('provider', 'discord')->where('external_id', $id)->row();

			return ['user_id' => NULL, 'identity_id' => $identite ? (int) $identite : NULL, 'name' => ''];
		}

		$avatar   = (string) ($discord['avatar'] ?? '');
		$avatar   = $avatar !== '' && preg_match('#^https://(cdn|media)\.discordapp\.(com|net)/#', $avatar) ? $avatar : NULL;
		$identite = $modele->identite_externe('discord', $id, $pseudo, $avatar);

		return ['user_id' => NULL, 'identity_id' => $identite, 'name' => $modele->nom_identite((array) $this->db->select('provider', 'external_id', 'username', 'mode', 'custom_name')->from('nf_forum_identities')->where('identity_id', $identite)->row())];
	}

	/**
	 * Le contenu d'une écriture, en HTML sûr : `content`, en Markdown (le format de Discord, par
	 * défaut) ou en HTML (`format: "html"`). Le Markdown échappe tout HTML qu'on y glisse ; le HTML
	 * passe par le même nettoyage que le reste du site.
	 *
	 * @param array<string, string> $erreurs
	 */
	private function _contenu(array $corps, array &$erreurs): string
	{
		$texte  = (string) ($corps['content'] ?? '');
		$format = (string) ($corps['format'] ?? 'markdown');

		if (trim($texte) === '' || mb_strlen($texte) > self::CONTENU_MAX)
		{
			$erreurs['content'] = (string) $this->lang('De 1 à %d caractères.', self::CONTENU_MAX);

			return '';
		}

		if (!in_array($format, ['markdown', 'html'], TRUE))
		{
			$erreurs['format'] = (string) $this->lang('« markdown » ou « html ».');

			return '';
		}

		$html = sanitize_html($format === 'markdown' ? markdown_to_html($texte) : $texte);

		if (trim(strip_tags($html, '<img>')) === '')
		{
			$erreurs['content'] = (string) $this->lang('Le contenu est vide une fois nettoyé.');
		}

		return $html;
	}

	/**
	 * Le corps JSON de la requête. Un corps illisible est refusé : un programme doit savoir que sa
	 * requête n'a pas été comprise, plutôt qu'elle ait été prise pour vide.
	 *
	 * @return array<string, mixed>
	 */
	private function _corps(): array
	{
		$brut = (string) file_get_contents('php://input');

		if (trim($brut) === '')
		{
			return [];
		}

		$corps = json_decode($brut, TRUE);

		if (!is_array($corps))
		{
			$this->_erreur(400, 'invalid_json', $this->lang('Le corps de la requête n’est pas un JSON valide.'));
		}

		return $corps;
	}

	/** @param array<string, string> $erreurs */
	private function _valider(array $erreurs): void
	{
		if ($erreurs)
		{
			$this->_repondre(422, ['error' => ['code' => 'validation_failed', 'message' => (string) $this->lang('Certains champs sont invalides.'), 'fields' => $erreurs]]);
		}
	}

	/**
	 * L'auteur qui n'est pas un membre : l'identité externe d'un compte Discord non lié (`provider`,
	 * `external_id`, `name` — le nom affiché selon son mode), ou NULL.
	 */
	private function _auteur_externe(array $ligne): ?array
	{
		if (!empty($ligne['user_id']) || empty($ligne['identity_id']))
		{
			return NULL;
		}

		$identite = $this->db->select('provider', 'external_id', 'username', 'mode', 'custom_name')->from('nf_forum_identities')->where('identity_id', (int) $ligne['identity_id'])->row();

		return is_array($identite) && $identite ? ['provider' => (string) $identite['provider'], 'external_id' => (string) $identite['external_id'], 'name' => $this->_modele_forum()->nom_identite($identite)] : NULL;
	}

	/** L'auteur d'un sujet ou d'un message : son identifiant et son pseudo, ou NULL (membre supprimé, visiteur). */
	private function _auteur(array $ligne): ?array
	{
		return !empty($ligne['user_id']) && !empty($ligne['username']) ? ['id' => (int) $ligne['user_id'], 'username' => (string) $ligne['username']] : NULL;
	}

	/** Le modèle du forum, s'il est installé ; sinon l'API répond 404 « module_unavailable ». */
	private function _modele_forum(): \NF\Modules\Forum\Models\Forum
	{
		$forum = $this->module('forum');

		if (!$forum || !($modele = $forum->model('forum')) instanceof \NF\Modules\Forum\Models\Forum)
		{
			$this->_erreur(404, 'module_unavailable', $this->lang('Le forum n’est pas installé sur ce site.'));
		}

		return $modele;
	}

	// ── Les outils ──────────────────────────────────────────────────────────

	/** La clé de l'en-tête `Authorization: Bearer …`, vérifiée — ou NULL. */
	private function _jeton(): ?array
	{
		$entete = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

		if (!preg_match('/^Bearer\s+(\S+)$/i', trim($entete), $m))
		{
			return NULL;
		}

		return $this->_modele()->verifier($m[1]);
	}

	/**
	 * Les paramètres d'une adresse qui suit ce motif, ou NULL si elle ne le suit pas.
	 *
	 * @param list<string> $motif
	 * @param list<string> $segments
	 * @return list<string>|null
	 */
	private function _correspond(array $motif, array $segments): ?array
	{
		if (count($motif) !== count($segments))
		{
			return NULL;
		}

		$parametres = [];

		foreach ($motif as $i => $attendu)
		{
			if ($attendu === '#')
			{
				if (!ctype_digit($segments[$i]))
				{
					return NULL;
				}

				$parametres[] = $segments[$i];
			}
			else if ($attendu !== $segments[$i])
			{
				return NULL;
			}
		}

		return $parametres;
	}

	/** Le compte Discord lié à un membre, ou NULL. */
	private function _discord_de(int $user_id): ?array
	{
		if (!($authentificateur = $this->_authentificateur_discord()))
		{
			return NULL;
		}

		$lien = $this->db	->select('key', 'username')
							->from('nf_user_auth')
							->where('user_id', $user_id)
							->where('authenticator_id', $authentificateur)
							->row();

		return is_array($lien) && $lien ? ['id' => (string) $lien['key'], 'username' => (string) ($lien['username'] ?? '')] : NULL;
	}

	/** L'identifiant de l'authentificateur Discord dans `nf_addon`, ou NULL s'il n'est pas installé. */
	private function _authentificateur_discord(): ?int
	{
		$id = $this->db	->select('a.id')
						->from('nf_addon a')
						->join('nf_addon_type t', 't.id = a.type_id', 'INNER')
						->where('t.name', 'authenticator')
						->where('a.name', 'discord')
						->row();

		return $id ? (int) $id : NULL;
	}

	private function _erreur(int $code, string $erreur, $message, array $entetes = []): never
	{
		$this->_repondre($code, ['error' => ['code' => $erreur, 'message' => (string) $message]], $entetes);
	}

	private function _repondre(int $code, array $corps, array $entetes = []): never
	{
		if (!headers_sent())
		{
			http_response_code($code);
			header('Content-Type: application/json; charset=utf-8');
			header('Cache-Control: no-store');
			header('X-Content-Type-Options: nosniff');

			foreach ($this->_entetes + $entetes as $nom => $valeur)
			{
				header($nom.': '.$valeur);
			}
		}

		while (ob_get_level() > 0)
		{
			ob_end_clean();
		}

		exit(json_encode($corps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
	}

	/** Les groupes du site (le cœur `Groups`) : invoqué sans argument, il rend tous les groupes ; avec un membre, ses groupes. */
	private function _groupes_coeur(): \NF\NeoFrag\Core\Groups
	{
		$groupes = NeoFrag()->groups;

		if (!$groupes instanceof \NF\NeoFrag\Core\Groups)
		{
			throw new \LogicException('cœur des groupes introuvable');
		}

		return $groupes;
	}

	/** Le modèle de l'API, typé : pour l'analyse statique, `$this->model()` rend un modèle générique. */
	private function _modele(): \NF\Modules\Api\Models\Api
	{
		$modele = $this->model('api');

		if (!$modele instanceof \NF\Modules\Api\Models\Api)
		{
			throw new \LogicException('modèle de l’API introuvable');
		}

		return $modele;
	}
}
