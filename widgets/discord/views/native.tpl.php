<div class="widget-discord">
	<div class="widget-discord-header">
		<div class="widget-discord-icon">
			<?php if (!empty($icon_url)): ?>
				<img src="<?php echo nf_texte($icon_url) ?>" alt="" class="widget-discord-server-icon" loading="lazy" />
			<?php else: ?>
				<i class="fab fa-discord"></i>
			<?php endif ?>
		</div>
		<div class="widget-discord-meta">
			<div class="widget-discord-name"><?php echo nf_texte($name) ?></div>
			<div class="widget-discord-status">
				<span class="widget-discord-dot"></span>
				<strong><?php echo (int)$presence_count ?></strong>
				<?php echo $this->lang($presence_count > 1 ? 'membres en ligne' : 'membre en ligne') ?>
			</div>
		</div>
	</div>

	<?php if (!empty($visible_members)): ?>
	<div class="widget-discord-members">
		<?php foreach ($visible_members as $m):
			$avatar = !empty($m['avatar_url']) ? $m['avatar_url'] : '';
			$status = $m['status'] ?? 'online';
			$initial = nf_texte(mb_strtoupper(mb_substr(nf_texte_brut($m['username'] ?? '?'), 0, 1)));
			$nick    = nf_texte($m['nick'] ?? $m['username'] ?? '');
		?>
		<div class="widget-discord-member" title="<?php echo $nick ?>">
			<?php if ($avatar): ?>
				<img src="<?php echo nf_texte($avatar) ?>" alt="" class="widget-discord-avatar" />
			<?php else: ?>
				<div class="widget-discord-avatar widget-discord-avatar-fallback"><?php echo $initial ?></div>
			<?php endif ?>
			<span class="widget-discord-presence widget-discord-presence-<?php echo nf_texte($status) ?>"></span>
		</div>
		<?php endforeach ?>
		<?php if ($total_members > count($visible_members)): ?>
		<div class="widget-discord-member widget-discord-more" title="<?php echo $this->lang('%d autres', $total_members - count($visible_members)) ?>">
			+<?php echo $total_members - count($visible_members) ?>
		</div>
		<?php endif ?>
	</div>
	<?php endif ?>

	<?php if (!empty($voice_channels)): ?>
	<div class="widget-discord-channels">
		<div class="widget-discord-section-title"><i class="fas fa-volume-up"></i> <?php echo $this->lang('Salons vocaux') ?></div>
		<ul class="widget-discord-channel-list">
			<?php foreach (array_slice($voice_channels, 0, 5) as $ch): ?>
			<li class="widget-discord-channel">
				<i class="fas fa-volume-up"></i>
				<span><?php echo nf_texte($ch['name']) ?></span>
			</li>
			<?php endforeach ?>
		</ul>
	</div>
	<?php endif ?>

	<?php if ($instant_invite): ?>
	<a href="<?php echo nf_texte($instant_invite) ?>" target="_blank" rel="noopener" class="widget-discord-cta">
		<i class="fab fa-discord"></i>
		<span><?php echo $this->lang('Rejoindre le serveur') ?></span>
		<i class="fas fa-external-link-alt ms-auto"></i>
	</a>
	<?php endif ?>
</div>
