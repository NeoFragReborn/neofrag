<?php
$count = count($partners);
foreach ($partners as $i => $partner):
	// Le lien passe par la visite comptée (`partners/{id}/{nom}`, checker `_partner`), qui renvoie
	// vers le site : la colonne « Visites » de l'administration compte la page comme le widget.
	$visite = ((string) $partner['website'] !== '' && nf_url_sure((string) $partner['website'])) ? url('partners/'.$partner['partner_id'].'/'.$partner['name']) : '#';
?>
	<div class="row">
		<div class="col-12 col-lg-4 text-center">
			<a href="<?php echo htmlspecialchars($visite) ?>" target="_blank" class="d-block img-thumbnail" style="padding: 10px;">
				<?php if ($partner[$this->config->partners_logo_display]): ?>
				<img src="<?php echo NeoFrag()->model2('file', $partner[$this->config->partners_logo_display])->path() ?>" class="img-fluid" alt="" />
				<?php else: ?>
				<h3><?php echo $partner['title'] ?></h3>
				<?php endif ?>
			</a>
		</div>
		<div class="col-12 col-lg-8">
			<h4><?php echo $this->lang('À propos de %s', $partner['title']) ?></h4>
			<p><?php echo $this->lang('Site internet') ?> : <a href="<?php echo htmlspecialchars($visite) ?>" target="_blank"><?php echo htmlspecialchars((string) preg_replace('_https?://_', '', (string) $partner['website'])) ?></a></p>
			<ul class="list-inline">
				<?php if ($partner['facebook'] && nf_url_sure((string) $partner['facebook'])) echo '<li class="list-inline-item"><a href="'.htmlspecialchars((string) $partner['facebook'], ENT_QUOTES, 'UTF-8', FALSE).'" class="btn btn-primary" target="_blank" data-bs-toggle="tooltip" title="Facebook">'.icon('fab fa-facebook-f').'</a></li>' ?>
				<?php if ($partner['twitter'] && nf_url_sure((string) $partner['twitter'])) echo '<li class="list-inline-item"><a href="'.htmlspecialchars((string) $partner['twitter'], ENT_QUOTES, 'UTF-8', FALSE).'" class="btn btn-info" target="_blank" data-bs-toggle="tooltip" title="Twitter">'.icon('fab fa-twitter').'</a></li>' ?>
				<?php if ($partner['code']) echo '<li class="list-inline-item"><span data-bs-toggle="tooltip" title="'.$this->lang('Code promotionnel').'">'.icon('fas fa-gift').' '.$partner['code'].'</span></li>' ?>
			</ul>
			<?php if ($partner['description']) echo '<p>'.bbcode($partner['description']).'</p>' ?>
		</div>
	</div>
<?php
	if ($i < $count - 1) echo '<hr />';
endforeach;
?>
