<?php
/**
 * Le consentement du visiteur (2026-10-08) : la fenêtre « Gérer mes cookies », le bandeau quand le site a
 * quelque chose à demander, et le script qui les fait vivre. Le principe, le cookie et la liste des
 * services : helpers/consentement.php.
 *
 * La fenêtre est servie sur toutes les pages publiques, fermée : le lien « Gérer mes cookies » du pied de
 * page l'ouvre, comme le « En savoir plus » des avis posés à la place des contenus tiers. Le bandeau ne
 * s'affiche que si le site propose un service qui agirait sur toutes les pages (la mesure d'audience, le
 * captcha d'un tiers) et que le visiteur n'a pas encore répondu — ou que la liste a changé depuis.
 *
 * Ni « Tout accepter » ni « Tout refuser » ne prend le pas sur l'autre : même taille, même style (CNIL,
 * recommandation « cookies et autres traceurs », 2020).
 */
$site       = nf_consentement_du_site();
$services   = nf_consentement_services();
$cookie     = nf_consentement_cookie((string) $this->url->base);
$choix      = nf_consentement_lire(nf_consentement_valeur());
$empreinte  = nf_consentement_empreinte($site['bandeau']);
$proposes   = array_merge($site['bandeau'], $site['contenus']);
$bandeau    = $site['bandeau'] && empty($this->url->admin) && ($choix === NULL || ($choix['empreinte'] !== '*' && $choix['empreinte'] !== $empreinte));
$acceptes   = $choix['services'] ?? [];
$h          = fn($texte): string => nf_texte((string) $texte);

// Ce que fait chaque catégorie, dans la langue du visiteur.
$categories = [
	'mesure'     => [$this->lang('Mesure d’audience'),          $this->lang('Compte les visites et les pages vues, pour savoir ce qui est lu sur le site.')],
	'securite'   => [$this->lang('Protection des formulaires'), $this->lang('Vérifie qu’un humain, et non un robot, envoie le formulaire. Si vous refusez, le site utilise sa propre vérification, sans aucun tiers.')],
	'videos'     => [$this->lang('Vidéos'),                     $this->lang('Les vidéos intégrées aux pages : le lecteur vient de la plateforme, qui reçoit votre adresse IP et peut déposer ses cookies.')],
	'musique'    => [$this->lang('Musique'),                    $this->lang('Les morceaux et listes de lecture intégrés aux pages : le lecteur vient de la plateforme, qui reçoit votre adresse IP et peut déposer ses cookies.')],
	'communaute' => [$this->lang('Communauté'),                 $this->lang('Le widget du serveur Discord de la communauté : il vient de Discord, qui reçoit votre adresse IP et peut déposer ses cookies.')],
];

// Les cookies du site lui-même : nécessaires, ils ne demandent pas d'accord (loi Informatique et Libertés, art. 82).
$propres  = [
	[$this->session->nom_du_cookie(), $this->lang('Garde votre connexion et protège les formulaires contre les envois frauduleux.'), $this->lang('Jusqu’à la fermeture du navigateur ; un an avec « Se souvenir de moi »')],
	[$cookie['nom'],                  $this->lang('Garde les choix que vous faites dans cette fenêtre.'),                                $this->lang('6 mois')],
	['nf_fuseau',                     $this->lang('Affiche les dates et les heures dans votre fuseau horaire.'),                       $this->lang('1 an')],
];

if (nf_theme_choix_permis())
{
	$propres[] = [nf_theme_cookie()['nom'], $this->lang('Garde le thème que vous avez choisi pour le site.'), $this->lang('1 an')];
}

$propres[] = [$this->lang('Stockage du navigateur'), $this->lang('Garde votre choix entre le mode jour et le mode nuit, et la présentation choisie pour la liste des articles.'), $this->lang('Jusqu’à ce que vous le vidiez')];

// Les noms des services du bandeau, avec ce qu'ils font, pour la phrase du bandeau.
$noms_bandeau = array_map(fn(string $s): string => '<strong>'.$h($services[$s]['nom']).'</strong> ('.mb_strtolower($categories[$services[$s]['categorie']][0]).')', $site['bandeau']);
$noms_contenus = implode(', ', array_map(fn(string $s): string => $h($services[$s]['nom']), $site['contenus']));
?>
<link rel="stylesheet" href="<?php echo path('consentement.css', 'css') ?>?v=<?php echo asset_version('consentement.css', 'css') ?: (int) $this->config->nf_version_css ?>">
<dialog id="nf-consentement" class="nf-consentement" aria-labelledby="nf-consentement-titre"
	data-nf-cookie="<?php echo $h($cookie['nom']) ?>" data-nf-chemin="<?php echo $h($cookie['chemin']) ?>" data-nf-duree="<?php echo NF_CONSENTEMENT_DUREE ?>"
	data-nf-empreinte="<?php echo $empreinte ?>" data-nf-jeton="<?php echo $h($choix['jeton'] ?? '') ?>" data-nf-acceptes="<?php echo $h(implode('-', $acceptes)) ?>"
	data-nf-proposes="<?php echo $h(implode('-', $proposes)) ?>" data-nf-preuve="<?php echo url('ajax/user/consentement') ?>">
	<form method="dialog" class="nf-consentement__corps">
		<header class="nf-consentement__tete">
			<h2 id="nf-consentement-titre"><?php echo $this->lang('Gérer mes cookies') ?></h2>
			<button type="submit" value="fermer" class="nf-consentement__fermer" aria-label="<?php echo $this->lang('Fermer') ?>"><i class="fas fa-xmark" aria-hidden="true"></i></button>
		</header>
		<p><?php echo $this->lang('Le site ne dépose que les cookies nécessaires à son fonctionnement : ils ne demandent pas votre accord. Les autres services appartiennent à d’autres sociétés ; ils ne se chargent que si vous les acceptez. Votre choix est gardé six mois, et vous pouvez le changer à tout moment.') ?></p>

		<section class="nf-consentement__groupe">
			<h3><?php echo $this->lang('Nécessaires au site') ?> <span class="nf-consentement__toujours"><?php echo $this->lang('Toujours actifs') ?></span></h3>
			<ul class="nf-consentement__propres">
				<?php foreach ($propres as [$nom, $role, $duree]): ?>
				<li><code><?php echo $h($nom) ?></code> <span><?php echo $role ?></span> <small><?php echo $duree ?></small></li>
				<?php endforeach ?>
			</ul>
		</section>

		<?php if ($proposes): ?>
		<?php foreach ($categories as $categorie => [$titre, $role]): ?>
		<?php if ($cles = array_values(array_filter($proposes, fn(string $s): bool => $services[$s]['categorie'] === $categorie))): ?>
		<section class="nf-consentement__groupe">
			<h3><?php echo $titre ?></h3>
			<p><?php echo $role ?></p>
			<?php foreach ($cles as $cle): $service = $services[$cle]; ?>
			<label class="nf-consentement__service">
				<input type="checkbox" role="switch" name="nf-service" value="<?php echo $cle ?>"<?php echo in_array($cle, $acceptes, TRUE) ? ' checked' : '' ?>>
				<span class="nf-consentement__nom"><strong><?php echo $h($service['nom']) ?></strong> <small><?php echo $h($service['editeur']) ?> · <a href="<?php echo $h($service['politique']) ?>" target="_blank" rel="noopener"><?php echo $this->lang('sa politique de confidentialité') ?></a></small></span>
			</label>
			<?php endforeach ?>
		</section>
		<?php endif ?>
		<?php endforeach ?>
		<?php else: ?>
		<p class="nf-consentement__aucun"><?php echo $this->lang('Ce site n’utilise aucun service d’une autre société : il n’y a rien à accepter ni à refuser.') ?></p>
		<?php endif ?>

		<footer class="nf-consentement__actions">
			<?php if ($proposes): ?>
			<button type="button" class="nf-consentement__bouton" data-nf-consentement-tout="0"><?php echo $this->lang('Tout refuser') ?></button>
			<button type="button" class="nf-consentement__bouton" data-nf-consentement-tout="1"><?php echo $this->lang('Tout accepter') ?></button>
			<button type="button" class="nf-consentement__bouton nf-consentement__bouton--plein" data-nf-consentement-enregistrer><?php echo $this->lang('Enregistrer mes choix') ?></button>
			<?php else: ?>
			<button type="submit" value="fermer" class="nf-consentement__bouton nf-consentement__bouton--plein"><?php echo $this->lang('Fermer') ?></button>
			<?php endif ?>
		</footer>
	</form>
</dialog>
<?php if ($bandeau): ?>
<div id="nf-consentement-bandeau" class="nf-consentement-bandeau" role="region" aria-label="<?php echo $this->lang('Vos choix sur ce site') ?>">
	<p class="nf-consentement-bandeau__texte">
		<strong><?php echo $this->lang('Vos choix sur ce site') ?></strong>
		<?php echo $this->lang('Ce site aimerait utiliser %s.', implode(', ', $noms_bandeau)) ?>
		<?php if ($noms_contenus !== ''): ?><?php echo $this->lang('Là où une page en contient, il afficherait aussi des contenus d’autres sites (%s).', $noms_contenus) ?><?php endif ?>
		<?php echo $this->lang('Rien ne se charge sans votre accord, et vous pourrez changer d’avis à tout moment par le lien « Gérer mes cookies », en bas de chaque page.') ?>
	</p>
	<div class="nf-consentement-bandeau__boutons">
		<button type="button" class="nf-consentement__bouton" data-nf-consentement-tout="0"><?php echo $this->lang('Tout refuser') ?></button>
		<button type="button" class="nf-consentement__bouton" data-nf-consentement-tout="1"><?php echo $this->lang('Tout accepter') ?></button>
		<button type="button" class="nf-consentement__bouton nf-consentement__bouton--lien" data-nf-consentement-ouvrir><?php echo $this->lang('Personnaliser') ?></button>
	</div>
</div>
<?php endif ?>
<?php
// Le modèle d'avis des contenus qu'un script charge lui-même, et les noms qui le remplissent.
$noms_services = array_map(fn(array $s): array => ['nom' => $s['nom'], 'editeur' => $s['editeur']], $services);
?>
<template id="nf-tiers-modele" data-nf-services="<?php echo $h(json_encode($noms_services, JSON_UNESCAPED_UNICODE)) ?>"><?php echo nf_consentement_avis('', '', fn(string $texte, ...$valeurs): string => (string) $this->lang($texte, ...$valeurs)) ?></template>
<script src="<?php echo path('consentement.js', 'js') ?>?v=<?php echo asset_version('consentement.js', 'js') ?: (int) $this->config->nf_version_css ?>"></script>
