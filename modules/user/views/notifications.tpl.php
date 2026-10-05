<?php
/*
 * Mes notifications (chantier A, étape A4) : vingt par page, les plus récentes d'abord, une non lue en relief, et
 * le lien vers les préférences pour couper ce qu'on ne veut plus recevoir.
 */
$notifications = $notifications ?? [];
$pagination    = $pagination ?? '';
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
	<p class="mb-0 me-auto"><?php echo $this->lang('Tout ce que le site t’a signalé. Trop de notifications ? Choisis ce que tu reçois.') ?></p>
	<a class="btn btn-sm btn-outline-primary" href="<?php echo url('user/notifications/preferences') ?>"><?php echo icon('fas fa-sliders').' '.$this->lang('Préférences') ?></a>
</div>
<?php if (!$notifications): ?>
	<div class="alert alert-info text-center mb-0"><?php echo $this->lang('Aucune notification.') ?></div>
<?php else: ?>
	<div class="list-group nf-notifs">
		<?php foreach ($notifications as $n): ?>
			<a class="list-group-item list-group-item-action<?php echo empty($n['is_read']) ? ' nf-notif-unread' : '' ?>" href="<?php echo url($n['url'] ?: 'user/notifications') ?>">
				<span class="d-block"><?php echo nf_texte($n['title']) ?></span>
				<small class="text-muted"><?php echo nf_date_heure($n['created_at']).($n['actor'] ? ' · '.nf_texte($n['actor']) : '') ?></small>
			</a>
		<?php endforeach ?>
	</div>
	<?php if ($pagination !== ''): ?>
		<div class="d-flex justify-content-center mt-3"><?php echo $pagination ?></div>
	<?php endif ?>
<?php endif ?>
