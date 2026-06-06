<?php
// Slider widget — Phase Slider Refonte
// Reçoit $slides : array d'arrays [image_url, title, caption, link, active]
$slider_uid = 'carousel-' . substr(md5(uniqid('', true)), 0, 8);

$resolve_image = function($src) {
	if (empty($src)) return '';
	if (strpos($src, 'http://') === 0 || strpos($src, 'https://') === 0 || strpos($src, '//') === 0) return $src;
	return '/' . ltrim($src, '/');
};
?>
<div id="<?php echo $slider_uid ?>" class="carousel slide" data-ride="carousel" data-interval="5000">
	<?php if (count($slides) > 1): ?>
		<ol class="carousel-indicators">
			<?php foreach ($slides as $i => $slide): ?>
				<li data-target="#<?php echo $slider_uid ?>" data-slide-to="<?php echo $i ?>"<?php echo $i === 0 ? ' class="active"' : '' ?>></li>
			<?php endforeach ?>
		</ol>
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
		<a class="carousel-control-prev" href="#<?php echo $slider_uid ?>" role="button" data-slide="prev">
			<span class="carousel-control-prev-icon" aria-hidden="true"></span>
			<span class="sr-only"><?php echo $this->lang('Précédent') ?></span>
		</a>
		<a class="carousel-control-next" href="#<?php echo $slider_uid ?>" role="button" data-slide="next">
			<span class="carousel-control-next-icon" aria-hidden="true"></span>
			<span class="sr-only"><?php echo $this->lang('Suivant') ?></span>
		</a>
	<?php endif ?>
</div>
