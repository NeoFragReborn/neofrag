<div id="gallery_Carousel<?php echo $id ?>" class="carousel slide" data-bs-ride="carousel">
	<div class="carousel-inner">
		<?php foreach ($images as $image): ?>
		<div class="carousel-item<?php echo !isset($active) ? $active = ' active' : '' ?>">
			<a href="<?php echo url('gallery/image/'.$image['image_id'].'/'.url_title($image['title'])) ?>"><img class="d-block w-100" src="<?php echo NeoFrag()->model2('file', $image['file_id'])->path() ?>" data-bs-toggle="tooltip" title="<?php echo $image['title'] ?>" alt="" /></a>
		</div>
		<?php endforeach ?>
	</div>
	<button class="carousel-control-prev" type="button" data-bs-target="#gallery_Carousel<?php echo $id ?>" data-bs-slide="prev">
		<span class="carousel-control-prev-icon" aria-hidden="true"></span>
		<span class="visually-hidden"><?php echo $this->lang('Précédent') ?></span>
	</button>
	<button class="carousel-control-next" type="button" data-bs-target="#gallery_Carousel<?php echo $id ?>" data-bs-slide="next">
		<span class="carousel-control-next-icon" aria-hidden="true"></span>
		<span class="visually-hidden"><?php echo $this->lang('Suivant') ?></span>
	</button>
</div>
