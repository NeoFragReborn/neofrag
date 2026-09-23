<?php
// Slider widget — Phase Slider Refonte
// Reçoit $slides : array d'arrays [image_url, title, caption, link, active]
$slider_uid = 'carousel-' . substr(md5(uniqid('', true)), 0, 8);

/**
 * Adresse d'affichage d'une image de diapositive.
 *
 * Elle passe par `url()`, comme toute adresse de fichier du CMS (cf. `File::path()`), et NON par un
 * `'/'` en dur. La différence ne se voit pas quand le site occupe la racine du domaine — mais dès
 * qu'il est servi depuis un SOUS-DOSSIER, `'/upload/…'` désigne la racine du domaine, pas celle du
 * site. Sur la démonstration (`/demo/`), les trois diapositives renvoyaient donc une redirection au
 * lieu d'une image : le carrousel s'effondrait à zéro pixel de haut et sa légende se retrouvait
 * plaquée sur le fond du thème. Signalé le 2026-09-16, capture à l'appui.
 *
 * Mesuré : `/upload/demo/demo-slide-0.jpg` → 302 ; `/demo/upload/demo/demo-slide-0.jpg` → 200.
 *
 * Le défaut était invisible à la capture automatique, qui sert le site à la RACINE d'un serveur
 * local : seule l'adresse publique réelle le révèle.
 */
$resolve_image = function($src) {
	if (empty($src)) return '';
	if (strpos($src, 'http://') === 0 || strpos($src, 'https://') === 0 || strpos($src, '//') === 0) return $src;
	return url(ltrim($src, '/'));
};
?>
<div id="<?php echo $slider_uid ?>" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
	<?php if (count($slides) > 1): ?>
		<div class="carousel-indicators">
			<?php foreach ($slides as $i => $slide): ?>
				<button type="button" data-bs-target="#<?php echo $slider_uid ?>" data-bs-slide-to="<?php echo $i ?>"<?php echo $i === 0 ? ' class="active" aria-current="true"' : '' ?> aria-label="<?php echo htmlspecialchars((string) $this->lang('Diapositive %d', $i + 1)) ?>"></button>
			<?php endforeach ?>
		</div>
	<?php endif ?>
	<div class="carousel-inner">
		<?php foreach ($slides as $i => $slide): ?>
			<div class="carousel-item<?php echo $i === 0 ? ' active' : '' ?>">
				<?php
					$img_src = $resolve_image($slide['image_url'] ?? '');
					$has_link = !empty($slide['link']);
				?>
				<?php if ($has_link): ?><a href="<?php echo htmlspecialchars($slide['link']) ?>"><?php endif ?>
				<?php if ($img_src): ?>
					<img class="d-block w-100" src="<?php echo htmlspecialchars($img_src) ?>" alt="<?php echo htmlspecialchars($slide['title'] ?? '') ?>" />
				<?php else: ?>
					<div class="d-block w-100" style="height:300px;background:linear-gradient(135deg,var(--nf-accent,#667eea),color-mix(in srgb,var(--nf-accent,#764ba2) 55%,#000));"></div>
				<?php endif ?>
				<?php if (!empty($slide['title']) || !empty($slide['caption'])): ?>
					<div class="carousel-caption d-none d-md-block">
						<?php if (!empty($slide['title'])): ?><h3><?php echo htmlspecialchars($slide['title']) ?></h3><?php endif ?>
						<?php if (!empty($slide['caption'])): ?><p><?php echo nl2br(htmlspecialchars($slide['caption'])) ?></p><?php endif ?>
					</div>
				<?php endif ?>
				<?php if ($has_link): ?></a><?php endif ?>
			</div>
		<?php endforeach ?>
	</div>
	<?php if (count($slides) > 1): ?>
		<button class="carousel-control-prev" type="button" data-bs-target="#<?php echo $slider_uid ?>" data-bs-slide="prev">
			<span class="carousel-control-prev-icon" aria-hidden="true"></span>
			<span class="visually-hidden"><?php echo $this->lang('Précédent') ?></span>
		</button>
		<button class="carousel-control-next" type="button" data-bs-target="#<?php echo $slider_uid ?>" data-bs-slide="next">
			<span class="carousel-control-next-icon" aria-hidden="true"></span>
			<span class="visually-hidden"><?php echo $this->lang('Suivant') ?></span>
		</button>
	<?php endif ?>
</div>
