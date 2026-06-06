<?php
$display_name = $user['display_name'] ?? ucfirst($username);
$avatar       = $user['profile_image_url'] ?? '';
$thumb        = '';
if ($is_live && !empty($stream['thumbnail_url'])) {
	$thumb = str_replace(['{width}', '{height}'], ['440', '248'], $stream['thumbnail_url']).'?_='.time(); // bypass cache
}
$widget_uid = 'twitch-'.substr(md5($username), 0, 6);
?>
<div class="widget-twitch <?php echo $is_live ? 'is-live' : 'is-offline' ?>" data-twitch-channel="<?php echo htmlspecialchars($username) ?>">
	<?php if ($is_live && $thumb): ?>
	<div class="widget-twitch-thumb">
		<img src="<?php echo htmlspecialchars($thumb) ?>" alt="" loading="lazy" />
		<div class="widget-twitch-live-badge"><span class="dot"></span> LIVE</div>
		<?php if (!empty($stream['viewer_count'])): ?>
		<div class="widget-twitch-viewers"><i class="fas fa-eye"></i> <?php echo number_format((int)$stream['viewer_count'], 0, ',', ' ') ?></div>
		<?php endif ?>
		<button type="button" class="widget-twitch-play"
				data-channel="<?php echo htmlspecialchars($username) ?>"
				data-embed="<?php echo htmlspecialchars($embed_url) ?>"
				data-mode="<?php echo htmlspecialchars($open_mode) ?>"
				data-channel-url="<?php echo htmlspecialchars($channel_url) ?>"
				data-uid="<?php echo $widget_uid ?>">
			<i class="fas fa-play"></i>
		</button>
	</div>
	<?php endif ?>

	<div class="widget-twitch-body">
		<div class="widget-twitch-streamer">
			<?php if ($avatar): ?>
				<img src="<?php echo htmlspecialchars($avatar) ?>" class="widget-twitch-avatar" alt="" />
			<?php else: ?>
				<div class="widget-twitch-avatar widget-twitch-avatar-fallback">
					<i class="fab fa-twitch"></i>
				</div>
			<?php endif ?>
			<div class="widget-twitch-meta">
				<a href="<?php echo htmlspecialchars($channel_url) ?>" target="_blank" rel="noopener" class="widget-twitch-name"><?php echo htmlspecialchars($display_name) ?></a>
				<?php if (!$has_creds): ?>
				<div class="widget-twitch-status widget-twitch-status-unknown"><i class="fas fa-question-circle"></i> <?php echo $this->lang('Statut indisponible (sans API)') ?></div>
				<?php elseif ($is_live): ?>
				<div class="widget-twitch-status widget-twitch-status-live"><span class="dot"></span> <?php echo $this->lang('EN DIRECT') ?></div>
				<?php else: ?>
				<div class="widget-twitch-status widget-twitch-status-offline"><i class="far fa-circle"></i> <?php echo $this->lang('Hors ligne') ?></div>
				<?php endif ?>
			</div>
		</div>

		<?php if ($is_live && !empty($stream['title'])): ?>
		<div class="widget-twitch-title" title="<?php echo htmlspecialchars($stream['title']) ?>"><?php echo htmlspecialchars($stream['title']) ?></div>
		<?php endif ?>

		<?php if ($is_live && !empty($stream['game_name'])): ?>
		<div class="widget-twitch-game"><i class="fas fa-gamepad"></i> <?php echo htmlspecialchars($stream['game_name']) ?></div>
		<?php endif ?>

		<?php if ($is_live): ?>
			<button type="button" class="widget-twitch-cta widget-twitch-play"
					data-channel="<?php echo htmlspecialchars($username) ?>"
					data-embed="<?php echo htmlspecialchars($embed_url) ?>"
					data-mode="<?php echo htmlspecialchars($open_mode) ?>"
					data-channel-url="<?php echo htmlspecialchars($channel_url) ?>"
					data-uid="<?php echo $widget_uid ?>">
				<i class="fas fa-play"></i> <?php echo $this->lang('Regarder le live') ?>
			</button>
		<?php else: ?>
			<a href="<?php echo htmlspecialchars($channel_url) ?>" target="_blank" rel="noopener" class="widget-twitch-cta widget-twitch-cta-secondary">
				<i class="fab fa-twitch"></i> <?php echo $this->lang('Voir la chaîne') ?>
			</a>
		<?php endif ?>
	</div>
</div>

<!-- Embedded popup (modal) — single shared instance per page -->
<script>
(function(){
	if (window._nfTwitchPlayerInit) return;
	window._nfTwitchPlayerInit = true;

	var modal, iframe, label, current = null;

	function ensureModal(){
		if (modal) return;
		modal = document.createElement('div');
		modal.className = 'nf-twitch-modal';
		modal.innerHTML =
			'<div class="nf-twitch-modal-backdrop"></div>' +
			'<div class="nf-twitch-modal-dialog">' +
				'<div class="nf-twitch-modal-header">' +
					'<span class="nf-twitch-modal-label"><i class="fab fa-twitch"></i> <span></span></span>' +
					'<a href="#" target="_blank" rel="noopener" class="nf-twitch-modal-open" title="Ouvrir sur twitch.tv"><i class="fas fa-external-link-alt"></i></a>' +
					'<button type="button" class="nf-twitch-modal-close" aria-label="Fermer">×</button>' +
				'</div>' +
				'<div class="nf-twitch-modal-body"><iframe allowfullscreen frameborder="0" scrolling="no"></iframe></div>' +
			'</div>';
		document.body.appendChild(modal);
		iframe = modal.querySelector('iframe');
		label  = modal.querySelector('.nf-twitch-modal-label span');

		var close = function(){
			modal.classList.remove('show');
			iframe.src = ''; // stop player
		};
		modal.querySelector('.nf-twitch-modal-backdrop').addEventListener('click', close);
		modal.querySelector('.nf-twitch-modal-close').addEventListener('click', close);
		document.addEventListener('keydown', function(e){
			if (e.key === 'Escape' && modal.classList.contains('show')) close();
		});
	}

	document.addEventListener('click', function(e){
		var btn = e.target.closest('.widget-twitch-play');
		if (!btn) return;
		e.preventDefault();
		var mode = btn.dataset.mode;
		if (mode === 'newtab'){
			window.open(btn.dataset.channelUrl, '_blank', 'noopener');
			return;
		}
		ensureModal();
		iframe.src = btn.dataset.embed + '&autoplay=true';
		label.textContent = btn.dataset.channel;
		modal.querySelector('.nf-twitch-modal-open').href = btn.dataset.channelUrl;
		modal.classList.add('show');
	});
})();
</script>
