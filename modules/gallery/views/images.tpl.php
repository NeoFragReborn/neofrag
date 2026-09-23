<?php if ($images): ?>
<div class="nf-card-grid">
	<?php foreach ($images as $image): ?>
	<div class="card">
		<a href="#" data-modal-ajax="<?php echo url('ajax/gallery/image/'.$image['image_id'].'/'.url_title($image['title'])) ?>">
			<img class="card-img" src="<?php echo NeoFrag()->model2('file', $image['thumbnail_file_id'])->path() ?>" alt="<?php echo htmlspecialchars((string) $image['title']) ?>" />
		</a>
	</div>
	<?php endforeach ?>
</div>
<?php echo $pagination ?>
<?php else: ?>
<div class="card border-primary">
	<div class="card-body">
		<div class="text-center"><?php echo $this->lang('Aucune image dans cet album') ?></div>
	</div>
</div>
<?php endif ?>
