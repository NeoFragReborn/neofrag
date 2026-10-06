<?php
/*
 * Les dernières photos en mosaïque : la plus récente en grand, à gauche, les suivantes à côté ; chacune mène à sa page.
 * css/galerie.css la dessine avec les jetons du thème.
 */
$images = $images ?? [];
?>
<div class="nf-photos">
	<?php foreach ($images as $i => $image): ?>
	<?php
		// La plus grande garde l'original ; les petites, la vignette quand elle existe.
		$fichier = $i === 0 || empty($image['thumbnail_file_id']) ? $image['file_id'] : $image['thumbnail_file_id'];
		$titre   = trim((string) $image['title']);
	?>
	<a class="nf-photo" href="<?php echo url('gallery/image/'.$image['image_id'].'/'.url_title($titre)) ?>">
		<img src="<?php echo NeoFrag()->model2('file', $fichier)->path() ?>" alt="<?php echo nf_texte($titre) ?>" loading="lazy" />
	</a>
	<?php endforeach ?>
</div>
