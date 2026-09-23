<?php
declare(strict_types=1);
/**
 * NeoFrag Reborn — la police du site, choisie par l'administrateur.
 *
 * Pourquoi un helper plutôt qu'une constante dans le contrôleur : la liste sert à DEUX endroits qui
 * ne doivent jamais diverger — le formulaire d'administration, qui la propose, et le gabarit, qui
 * l'applique. Le gabarit la relit pour valider ce qu'il trouve en base : le réglage est un choix
 * dans une liste, mais rien n'empêche un POST de porter autre chose, et cette valeur finirait dans
 * une adresse envoyée à Google et dans une feuille de style. On ne fait donc confiance qu'à la liste.
 *
 * Les polices sont servies par Google Fonts, que la politique de sécurité autorise déjà en
 * `style-src` (cf. index.php). C'est la seule exception au principe « aucun tiers » du projet, et
 * elle est délibérée : embarquer des dizaines de fichiers de fontes alourdirait le paquet livré, et
 * l'administrateur qui ne veut pas de tiers garde « Police du thème », qui ne demande rien à personne.
 *
 * `--nf-font-mono` n'est pas concernée : du code se lit en chasse fixe, toujours.
 */

/**
 * Les polices proposées, de la plus neutre à la plus marquée.
 *
 * Le libellé vaut le nom : ce sont des noms propres, ils ne se traduisent pas. L'entrée « aucune »
 * — la seule à traduire — est ajoutée par le formulaire d'administration, seul endroit qui ait la
 * langue de l'utilisateur sous la main.
 *
 * @return array<string, string> nom envoyé à Google Fonts => libellé affiché
 */
function polices_disponibles(): array
{
	return [
		'Inter'           => 'Inter',
		'Open Sans'       => 'Open Sans',
		'Roboto'          => 'Roboto',
		'Lato'            => 'Lato',
		'Nunito'          => 'Nunito',
		'Source Sans 3'   => 'Source Sans 3',
		'Rubik'           => 'Rubik',
		'Montserrat'      => 'Montserrat',
		'Poppins'         => 'Poppins',
		'Titillium Web'   => 'Titillium Web',
		'Space Grotesk'   => 'Space Grotesk',
		'Oswald'          => 'Oswald',
	];
}

/**
 * La police retenue, ou NULL s'il faut laisser le thème décider.
 *
 * Rend NULL aussi quand la valeur en base ne figure pas dans la liste — réglage écrit à la main,
 * police retirée de la liste depuis, import d'une autre installation.
 */
function police_du_site(): ?string
{
	$choix = trim((string) NeoFrag()->config->nf_font);

	if ($choix === '' || !array_key_exists($choix, polices_disponibles()))
	{
		return NULL;
	}

	return $choix;
}

/**
 * La pile de repli, pour que le texte reste lisible avant que la fonte arrive — et si elle n'arrive
 * jamais, Google Fonts étant un tiers qui peut être bloqué, lent, ou injoignable.
 */
function police_du_site_pile(string $police): string
{
	return '"'.$police.'", "Segoe UI", system-ui, sans-serif';
}
