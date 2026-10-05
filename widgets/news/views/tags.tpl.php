<?php
// $tags : [nom_du_tag => fréquence]. La taille de police traduit la fréquence (nuage de tags).
$max = max($tags);
$min = min($tags);
?>
<div class="tag-cloud">
<?php foreach ($tags as $tag => $count): ?>
	<?php $size = 0.85 + ($max > $min ? ($count - $min) / ($max - $min) : 0) * 0.75; ?>
	<a href="<?php echo url('news/tag/'.url_title($tag)) ?>" style="font-size:<?php echo number_format($size, 2, '.', '') ?>em" title="<?php echo (int)$count ?>"><?php echo nf_texte($tag) ?></a>
<?php endforeach ?>
</div>
