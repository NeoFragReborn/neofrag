<?php
$count = count($partners);
foreach ($partners as $i => $partner): ?>
	<div class="row">
		<div class="col-12 col-lg-4 text-center">
			<a href="<?php echo $partner['website'] ?>" target="_blank" class="d-block img-thumbnail" style="padding: 10px;">
				<?php if ($partner[$this->config->partners_logo_display]): ?>
				<img src="<?php echo NeoFrag()->model2('file', $partner[$this->config->partners_logo_display])->path() ?>" class="img-fluid" alt="" />
				<?php else: ?>
				<h3><?php echo $partner['title'] ?></h3>
				<?php endif ?>
			</a>
		</div>
		<div class="col-12 col-lg-8">
			<h4><?php echo $this->lang('À propos de %s', $partner['title']) ?></h4>
			<p><?php echo $this->lang('Site internet') ?> : <a href="<?php echo $partner['website'] ?>" target="_blank"><?php echo preg_replace('_https?://_', '', $partner['website']) ?></a></p>
			<ul class="list-inline">
				<?php if ($partner['facebook']) echo '<li class="list-inline-item"><a href="'.$partner['facebook'].'" class="btn btn-primary" target="_blank" data-bs-toggle="tooltip" title="Facebook">'.icon('fab fa-facebook-f').'</a></li>' ?>
				<?php if ($partner['twitter']) echo '<li class="list-inline-item"><a href="'.$partner['twitter'].'" class="btn btn-info" target="_blank" data-bs-toggle="tooltip" title="Twitter">'.icon('fab fa-twitter').'</a></li>' ?>
				<?php if ($partner['code']) echo '<li class="list-inline-item"><span data-bs-toggle="tooltip" title="'.$this->lang('Code promotionnel').'">'.icon('fas fa-gift').' '.$partner['code'].'</span></li>' ?>
			</ul>
			<?php if ($partner['description']) echo '<p>'.bbcode($partner['description']).'</p>' ?>
		</div>
	</div>
<?php
	if ($i < $count - 1) echo '<hr />';
endforeach;
?>
