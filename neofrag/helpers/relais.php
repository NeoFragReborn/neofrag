<?php
declare(strict_types=1);
/**
 * NeoFrag Reborn — les images d'autres sites, servies par le site lui-même (2026-10-08).
 *
 * Pourquoi
 * --------
 * Une image prise ailleurs, affichée telle quelle, fait envoyer par le navigateur du visiteur son adresse
 * IP à l'hébergeur de l'image, à chaque affichage, sans qu'on le lui demande : les avatars du widget
 * Discord (cdn.discordapp.com, qui pose en prime un cookie), ceux des widgets Steam et Twitch, les GIF
 * de la messagerie, une image collée dans un article, le forum ou une annonce, une bannière de publicité.
 * Une adresse IP est une donnée personnelle (CJUE, Breyer, C-582/14) ; pour une police, un tribunal
 * allemand a jugé cette transmission illicite sans consentement (LG München I, 20 janvier 2022,
 * 3 O 17493/20), au motif que le site pouvait la servir lui-même. C'est ce que fait ce relais.
 *
 * Comment
 * -------
 *   - à l'affichage, chaque <img> d'un autre site (et chaque url() d'un attribut style) prend l'adresse
 *     du relais : `upload/relais/<empreinte>.<ext>?v=…` si l'image est déjà là et encore fraîche — le
 *     serveur web la sert alors comme n'importe quel fichier —, sinon `ajax/user/relais/<signature>/<adresse>`,
 *     que le site traite (User\Controllers\Ajax::_relais()) ;
 *   - le site va chercher l'image LUI-MÊME, depuis son serveur : par nf_fetch_public_url(), qui refuse
 *     toute adresse privée ou locale et ne suit pas les redirections (helpers/remote.php), 5 Mo au plus ;
 *     il ne garde que du JPEG, du PNG, du GIF ou du WebP, vérifiés sur leur contenu ;
 *   - la signature (HMAC, clé dérivée du site) fait que seul ce que le site a lui-même affiché passe par
 *     le relais : personne ne s'en sert pour faire télécharger au serveur une adresse de son choix ;
 *   - une image reste sept jours ; l'aperçu d'un direct Twitch, dix minutes.
 *
 * Plus aucune image ne vient donc d'ailleurs : la politique de sécurité (index.php) n'en laisse plus
 * passer — un oubli se verrait à l'écran, au lieu de laisser fuir des adresses en silence.
 */

/** Le dossier des images relayées, sous la racine du site. */
const NF_RELAIS_DOSSIER = 'upload/relais';

/** Le poids maximal d'une image relayée. */
const NF_RELAIS_OCTETS = 5242880;

/** Les formats gardés : type d'image de PHP => extension. */
const NF_RELAIS_FORMATS = [
	IMAGETYPE_JPEG => 'jpg',
	IMAGETYPE_PNG  => 'png',
	IMAGETYPE_GIF  => 'gif',
	IMAGETYPE_WEBP => 'webp',
];

/**
 * L'adresse complète d'une image d'un autre site, NULL si elle n'en est pas une : une adresse du site
 * lui-même, relative, en `data:`, ou d'un autre protocole que le web. Une adresse sans protocole
 * (`//hote/x`) prend celui du web sécurisé.
 *
 * @param string $hote_du_site l'hôte du site, que le relais ne touche pas
 */
function nf_relais_distante(string $adresse, string $hote_du_site): ?string
{
	$adresse = trim($adresse);

	if (str_starts_with($adresse, '//'))
	{
		$adresse = 'https:'.$adresse;
	}

	if (!preg_match('~^https?://[^/\s?#]+~i', $adresse))
	{
		return NULL;
	}

	$hote = strtolower((string) parse_url($adresse, PHP_URL_HOST));

	return $hote === '' || $hote === strtolower($hote_du_site) ? NULL : $adresse;
}

/** L'empreinte d'une adresse : le nom de son fichier dans le dossier du relais. */
function nf_relais_empreinte(string $adresse): string
{
	return substr(hash('sha256', $adresse), 0, 40);
}

/** La signature d'une adresse, avec la clé du site. */
function nf_relais_signature(string $adresse, string $cle): string
{
	return substr(hash_hmac('sha256', $adresse, $cle), 0, 20);
}

/** Combien de temps une image relayée reste fraîche, en secondes. */
function nf_relais_duree(string $adresse): int
{
	// L'aperçu d'un direct change sans changer d'adresse.
	return preg_match('#^https://static-cdn\.jtvnw\.net/previews-ttv/#i', $adresse) ? 600 : 604800;
}

/**
 * L'adresse locale d'une image d'un autre site : le fichier relayé s'il est là et frais, l'adresse du
 * relais sinon.
 *
 * @param string $racine        le dossier du site sur le disque
 * @param string $cle           la clé de signature du site
 * @param string $base_fichiers l'adresse de la racine du site (`/`, ou `/club/`)
 * @param string $base_relais   l'adresse du relais, à laquelle s'ajoutent la signature et l'adresse
 */
function nf_relais_adresse(string $adresse, string $racine, string $cle, string $base_fichiers, string $base_relais): string
{
	$empreinte = nf_relais_empreinte($adresse);
	$limite    = time() - nf_relais_duree($adresse);

	foreach (NF_RELAIS_FORMATS as $extension)
	{
		$fichier = $racine.'/'.NF_RELAIS_DOSSIER.'/'.$empreinte.'.'.$extension;

		if (is_file($fichier) && ($date = (int) filemtime($fichier)) >= $limite)
		{
			return $base_fichiers.NF_RELAIS_DOSSIER.'/'.$empreinte.'.'.$extension.'?v='.$date;
		}
	}

	return $base_relais.nf_relais_signature($adresse, $cle).'/'.bin2hex($adresse);
}

/**
 * Le filtre des pages : chaque image d'un autre site prend l'adresse du relais — l'attribut src d'une <img>,
 * et les url() d'un attribut style. Le contenu des <script> et des <textarea> n'est pas touché.
 *
 * @param callable(string): ?string $relayer l'adresse locale d'une adresse distante, NULL pour la laisser
 */
function nf_relais_filtrer(string $html, callable $relayer): string
{
	if (stripos($html, '<img') === FALSE && stripos($html, 'url(') === FALSE)
	{
		return $html;
	}

	$reserves = [];
	$html     = (string) preg_replace_callback('#<(script|textarea)\b[^>]*>.*?</\1\s*>#is', function (array $m) use (&$reserves): string {
		$reserves[] = $m[0];

		return "\x1Anf-relais-".(count($reserves) - 1)."\x1A";
	}, $html);

	$remplacer = function (string $valeur) use ($relayer): string {
		$brute = html_entity_decode($valeur, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$local = $relayer($brute);

		return $local === NULL ? $valeur : nf_texte($local);
	};

	// <img … src="…">
	$html = (string) preg_replace_callback('#<img\b[^>]*>#i', function (array $m) use ($remplacer): string {
		return (string) preg_replace_callback('#(\ssrc\s*=\s*)(["\'])(.*?)\2#is', fn(array $s): string => $s[1].$s[2].$remplacer($s[3]).$s[2], $m[0], 1);
	}, $html);

	// style="… url(…) …"
	$html = (string) preg_replace_callback('#(\sstyle\s*=\s*)(["\'])(.*?)\2#is', function (array $m) use ($remplacer): string {
		if (stripos($m[3], 'url(') === FALSE)
		{
			return $m[0];
		}

		$style = (string) preg_replace_callback('#url\(\s*(&quot;|&\#0?39;|["\']?)(.*?)\1\s*\)#i', fn(array $u): string => 'url('.$u[1].$remplacer($u[2]).$u[1].')', $m[3]);

		return $m[1].$m[2].$style.$m[2];
	}, $html);

	return (string) preg_replace_callback("#\x1Anf-relais-(\d+)\x1A#", fn(array $m): string => $reserves[(int) $m[1]], $html);
}
