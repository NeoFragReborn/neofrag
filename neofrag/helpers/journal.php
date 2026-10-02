<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * Lire le journal PHP d'une installation (`logs/php.log`) : découper ses entrées, les classer, les
 * regrouper, et masquer ce qui est sensible.
 *
 * C'est l'unique endroit où l'on sait ce qu'est une ligne FAUTIVE — erreur de PHP, ou ligne écrite
 * par le produit lui-même — et comment la montrer : regroupée par message, avec son nombre
 * d'occurrences et la dernière, parce que 225 Ko de lignes brutes ne se lisent pas. Deux lecteurs :
 * la page Journal du Monitoring (l'administrateur, sans SSH ni FTP, depuis le 2026-10-02) et les
 * outils de contrôle (`tools/lib/journal.php`, `check-journal`), qui chargent ce fichier.
 *
 * Chargé à la demande : `require_once NEOFRAG_CMS.'/neofrag/helpers/journal.php'`.
 */

/** Ce qui trahit une erreur de PHP dans une ligne du journal. */
const NF_JOURNAL_MOTIFS_PHP = ['Fatal error', 'Uncaught', 'Parse error', 'Warning:', 'Notice:', 'Deprecated:', 'Recoverable fatal'];

/**
 * Une ligne écrite par le PRODUIT porte une étiquette entre crochets : `[checker]`, `[output]`,
 * `[widget]`, `[update]`… La reconnaître à sa FORME plutôt que par une liste évite qu'une étiquette
 * neuve passe inaperçue.
 */
const NF_JOURNAL_ETIQUETTE = '/^\[[a-z][a-z0-9_.-]*\] /';

/** Les étiquettes du produit qui disent un avertissement, pas une panne. */
const NF_JOURNAL_ETIQUETTES_AVERTISSEMENT = ['checker', 'banlist', 'marketplace', 'theme'];

/**
 * Les entrées d'un journal brut. Une entrée commence par un horodatage `[22-Sep-2026 …]` ; les lignes
 * qui suivent sans horodatage (la pile d'une exception) lui sont rattachées.
 *
 * @return list<array{date: ?int, texte: string}>
 */
function nf_journal_entrees(string $brut): array
{
	$entrees = [];

	foreach (explode("\n", $brut) as $ligne)
	{
		if (trim($ligne) === '')
		{
			continue;
		}

		if (preg_match('/^\[(\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2})(?: ([A-Za-z_\/+-]+))?\] (.*)$/', $ligne, $m))
		{
			$date      = DateTimeImmutable::createFromFormat('d-M-Y H:i:s', $m[1], new DateTimeZone($m[2] !== '' ? $m[2] : 'UTC'));
			$entrees[] = ['date' => $date ? $date->getTimestamp() : NULL, 'texte' => $m[3]];
		}
		else if ($entrees)
		{
			$entrees[count($entrees) - 1]['texte'] .= "\n".$ligne;
		}
		else
		{
			$entrees[] = ['date' => NULL, 'texte' => $ligne];
		}
	}

	return $entrees;
}

/**
 * La fin d'un journal, sans le lire en entier : un journal jamais vidé peut peser des centaines de
 * mégaoctets. La première entrée, souvent coupée, est écartée.
 */
function nf_journal_fin(string $fichier, int $octets = 2097152): string
{
	clearstatcache(TRUE, $fichier);

	if (!is_file($fichier) || !is_readable($fichier) || ($taille = (int) filesize($fichier)) === 0)
	{
		return '';
	}

	$debut = max(0, $taille - $octets);
	$brut  = (string) file_get_contents($fichier, FALSE, NULL, $debut);

	return $debut > 0 ? (string) substr($brut, (int) strpos($brut, "\n[") + 1) : $brut;
}

/**
 * Range chaque entrée : erreur de PHP, ligne du produit, ou autre chose (montrée, jamais jugée).
 *
 * @param  list<array{date: ?int, texte: string}> $entrees
 * @return array{php: list<array{date: ?int, texte: string}>, produit: list<array{date: ?int, texte: string}>, autres: list<array{date: ?int, texte: string}>}
 */
function nf_journal_classer(array $entrees): array
{
	$classe = ['php' => [], 'produit' => [], 'autres' => []];

	foreach ($entrees as $entree)
	{
		if (preg_match(NF_JOURNAL_ETIQUETTE, $entree['texte']))
		{
			$classe['produit'][] = $entree;
			continue;
		}

		foreach (NF_JOURNAL_MOTIFS_PHP as $motif)
		{
			if (str_contains($entree['texte'], $motif))
			{
				$classe['php'][] = $entree;
				continue 2;
			}
		}

		$classe['autres'][] = $entree;
	}

	return $classe;
}

/**
 * La gravité d'une entrée, pour l'administrateur : `fatale` (la page n'a pas pu s'afficher du
 * tout), `erreur` (une page ou une action a échoué), `avertissement` (quelque chose cloche, sans
 * rien casser à l'écran), `information` (avis de PHP, fonctions dépréciées, le reste).
 */
function nf_journal_gravite(string $texte): string
{
	$premiere = strtok($texte, "\n") ?: '';

	if (preg_match('/^\[fatal\] |Fatal error|Parse error|Uncaught/', $premiere))
	{
		return 'fatale';
	}

	if (preg_match('/^\[([a-z][a-z0-9_.-]*)\] /', $premiere, $m))
	{
		return in_array(explode('.', $m[1])[0], NF_JOURNAL_ETIQUETTES_AVERTISSEMENT, TRUE) ? 'avertissement' : 'erreur';
	}

	if (str_contains($premiere, 'Recoverable fatal'))
	{
		return 'erreur';
	}

	if (str_contains($premiere, 'Warning:'))
	{
		return 'avertissement';
	}

	return 'information';
}

/**
 * Le message d'une entrée, tel qu'on le regroupe : sa première ligne (la pile d'appels la rendrait
 * unique), sans le chemin de l'installation, les numéros `#123` confondus, et sans la référence
 * d'erreur — une même panne vue par cent visiteurs reste une seule ligne.
 */
function nf_journal_message(string $texte, string $installation = ''): string
{
	$message = strtok($texte, "\n") ?: '';

	if ($installation !== '')
	{
		$message = str_replace(rtrim($installation, '/').'/', '', $message);
	}

	$message = (string) preg_replace('/ \(réf\. [0-9A-F]{8}\)$/', '', $message);

	return (string) preg_replace('/#\d+/', '#N', $message);
}

/**
 * Regroupe des entrées par MESSAGE. Les plus récents d'abord. Chaque groupe garde ses références
 * d'erreur (les dernières) et sa dernière entrée complète, pile comprise.
 *
 * @param  list<array{date: ?int, texte: string}> $entrees
 * @return list<array{message: string, nombre: int, dernier: ?int, references: list<string>, exemple: string}>
 */
function nf_journal_regrouper(array $entrees, string $installation = ''): array
{
	$groupes = [];

	foreach ($entrees as $entree)
	{
		$message = nf_journal_message($entree['texte'], $installation);

		$groupes[$message] ??= ['message' => $message, 'nombre' => 0, 'dernier' => NULL, 'references' => [], 'exemple' => ''];
		$groupes[$message]['nombre']++;

		if (($entree['date'] ?? 0) >= ($groupes[$message]['dernier'] ?? 0))
		{
			$groupes[$message]['dernier'] = $entree['date'];
			$groupes[$message]['exemple'] = $entree['texte'];
		}

		if (preg_match('/\(réf\. ([0-9A-F]{8})\)/', $entree['texte'], $r))
		{
			$groupes[$message]['references'][] = $r[1];
		}
	}

	foreach ($groupes as &$g)
	{
		$g['references'] = array_slice(array_reverse(array_unique($g['references'])), 0, 5);
	}

	unset($g);

	usort($groupes, static fn (array $a, array $b): int => ($b['dernier'] ?? 0) <=> ($a['dernier'] ?? 0));

	return array_values($groupes);
}

/**
 * Masque ce qu'une ligne du journal peut porter de sensible, avant de la montrer à l'écran : le
 * chemin de l'installation, les mots de passe, les clés et jetons, les adresses e-mail et la fin des
 * adresses IP. Le fichier, lui, reste entier (téléchargeable par l'administrateur).
 */
function nf_journal_masquer(string $texte, string $installation = ''): string
{
	if ($installation !== '')
	{
		$texte = str_replace(rtrim($installation, '/').'/', '', $texte);
	}

	$motifs = [
		// mot de passe, jeton, clé ou secret donnés en clair : `password=…`, `"token":"…"`, `secret: …`
		'/\b((?:pass(?:word|wd)?|mot_de_passe|token|jeton|secret|api[_-]?key|cle|key))(["\']?\s*[:=]\s*["\']?)([^\s"\'&,;)]+)/i' => '$1$2•••',
		'/\bBearer\s+[A-Za-z0-9._~+\/=-]+/'          => 'Bearer •••',
		'/\bnfr_[0-9a-f]{8,}\b/'                     => 'nfr_•••',
		'/\b([A-Za-z0-9._%+-])[A-Za-z0-9._%+-]*@([A-Za-z0-9.-]+\.[A-Za-z]{2,})\b/' => '$1•••@$2',
		'/\b(\d{1,3}\.\d{1,3})\.\d{1,3}\.\d{1,3}\b/' => '$1.•••',
		// IPv6 : au moins cinq groupes, pour ne pas prendre une heure (« 10:24:12 ») pour une adresse
		'/\b((?:[0-9a-f]{0,4}:){2})(?:[0-9a-f]{0,4}:){2,}[0-9a-f]{0,4}\b/i' => '$1•••',
	];

	return (string) preg_replace(array_keys($motifs), array_values($motifs), $texte);
}

/**
 * La trace détaillée des pages (`logs/neofrag.log`, NEOFRAG_LOGS ou Monitoring → Diagnostic), découpée
 * en pages : une page par bloc, que referme une ligne de « = ». Le bloc commence par la page servie
 * (« » GET /fr/forum », depuis le 2026-10-02) ; chaque requête à la base y porte sa durée.
 *
 * @return list<array{titre: string, date: ?int, requetes: int, duree: float, memoire: string, lignes: list<string>}>
 */
function nf_trace_pages(string $texte): array
{
	$pages = [];

	foreach (preg_split('/^={20,}\R?/m', $texte) ?: [] as $bloc)
	{
		$lignes = array_values(array_filter(preg_split('/\R/', trim($bloc)) ?: [], static fn (string $l): bool => trim($l) !== ''));

		if (!$lignes)
		{
			continue;
		}

		$titre = str_starts_with($lignes[0], '» ') ? substr((string) array_shift($lignes), strlen('» ')) : '';
		$page  = ['titre' => $titre, 'date' => NULL, 'requetes' => 0, 'duree' => 0.0, 'memoire' => '', 'lignes' => $lignes];

		foreach ($lignes as $ligne)
		{
			if ($page['date'] === NULL && preg_match('/^(\d{4}-\d\d-\d\d \d\d:\d\d:\d\d)/', $ligne, $m))
			{
				$page['date'] = strtotime($m[1]) ?: NULL;
			}

			if (preg_match('/\sDB_QUERY\s+([\d.]+)ms\s/', $ligne, $m))
			{
				$page['requetes']++;
				$page['duree'] += (float) $m[1];
			}

			if (preg_match('/^\S+ \S+\s+([\d.]+Mb)\s/', $ligne, $m))
			{
				$page['memoire'] = $m[1];
			}
		}

		$pages[] = $page;
	}

	return $pages;
}

/**
 * Une ligne de trace, prête à montrer : les valeurs passées aux requêtes (le contenu des sessions, des
 * empreintes de mots de passe, des adresses) sont retirées, puis le reste est masqué comme le journal
 * des erreurs. Le fichier téléchargé, lui, garde tout.
 */
function nf_trace_masquer(string $ligne, string $installation = ''): string
{
	$ligne = (string) preg_replace('/ \[(?:[^"\]]|"(?:[^"\\\\]|\\\\.)*")*\]$/', ' […]', $ligne);

	return nf_journal_masquer($ligne, $installation);
}
