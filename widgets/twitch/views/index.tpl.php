<?php
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$provider_icon = ['twitch' => 'fab fa-twitch', 'youtube' => 'fab fa-youtube'];

// Embed complet par provider (parent requis pour Twitch ; autoplay pour les deux), seulement si live.
$embed_full = function($c) use ($host) {
	if (empty($c['is_live']) || empty($c['embed_url'])) { return ''; }
	if ($c['provider'] === 'twitch') { return $c['embed_url'].'&parent='.rawurlencode($host).'&autoplay=true'; }
	return $c['embed_url'].'?autoplay=1';
};
?>
<div class="widget-live-list">
<?php foreach ($channels as $c): $is_live = !empty($c['is_live']); $unknown = !empty($c['unknown']); $embed = $embed_full($c); $picon = $provider_icon[$c['provider']] ?? 'fas fa-broadcast-tower'; ?>
	<div class="widget-twitch <?php echo $is_live ? 'is-live' : 'is-offline' ?>">
		<?php if ($is_live && !empty($c['thumbnail'])): ?>
		<div class="widget-twitch-thumb">
			<img src="<?php echo nf_texte($c['thumbnail']).'?_='.time() ?>" alt="" loading="lazy" />
			<div class="widget-twitch-live-badge"><span class="dot"></span> LIVE</div>
			<?php if (!empty($c['viewers'])): ?>
			<div class="widget-twitch-viewers"><i class="fas fa-eye"></i> <?php echo number_format((int)$c['viewers'], 0, ',', ' ') ?></div>
			<?php endif ?>
			<?php if ($embed): ?>
			<button type="button" class="widget-twitch-play" data-channel="<?php echo nf_texte($c['display_name']) ?>" data-embed="<?php echo nf_texte($embed) ?>" data-mode="<?php echo nf_texte($open_mode) ?>" data-channel-url="<?php echo nf_texte($c['channel_url']) ?>"><i class="fas fa-play"></i></button>
			<?php endif ?>
		</div>
		<?php endif ?>

		<div class="widget-twitch-body">
			<div class="widget-twitch-streamer">
				<?php if (!empty($c['avatar'])): ?>
					<img src="<?php echo nf_texte($c['avatar']) ?>" class="widget-twitch-avatar" alt="" />
				<?php else: ?>
					<div class="widget-twitch-avatar widget-twitch-avatar-fallback"><i class="<?php echo $picon ?>"></i></div>
				<?php endif ?>
				<div class="widget-twitch-meta">
					<a href="<?php echo nf_texte($c['channel_url']) ?>" target="_blank" rel="noopener" class="widget-twitch-name"><i class="<?php echo $picon ?>"></i> <?php echo nf_texte($c['display_name']) ?></a>
					<?php if ($unknown): ?>
					<div class="widget-twitch-status widget-twitch-status-unknown"><i class="fas fa-question-circle"></i> <?php echo $this->lang('Statut indisponible (sans API)') ?></div>
					<?php elseif ($is_live): ?>
					<div class="widget-twitch-status widget-twitch-status-live"><span class="dot"></span> <?php echo $this->lang('EN DIRECT') ?></div>
					<?php else: ?>
					<div class="widget-twitch-status widget-twitch-status-offline"><i class="far fa-circle"></i> <?php echo $this->lang('Hors ligne') ?></div>
					<?php endif ?>
				</div>
			</div>

			<?php if ($is_live && !empty($c['title'])): ?>
			<div class="widget-twitch-title" title="<?php echo nf_texte($c['title']) ?>"><?php echo nf_texte($c['title']) ?></div>
			<?php endif ?>

			<?php if ($is_live && !empty($c['game'])): ?>
			<div class="widget-twitch-game"><i class="fas fa-gamepad"></i> <?php echo nf_texte($c['game']) ?></div>
			<?php endif ?>

			<?php if ($is_live && $embed): ?>
				<button type="button" class="widget-twitch-cta widget-twitch-play" data-channel="<?php echo nf_texte($c['display_name']) ?>" data-embed="<?php echo nf_texte($embed) ?>" data-mode="<?php echo nf_texte($open_mode) ?>" data-channel-url="<?php echo nf_texte($c['channel_url']) ?>"><i class="fas fa-play"></i> <?php echo $this->lang('Regarder le live') ?></button>
			<?php else: ?>
				<a href="<?php echo nf_texte($c['channel_url']) ?>" target="_blank" rel="noopener" class="widget-twitch-cta widget-twitch-cta-secondary"><i class="<?php echo $picon ?>"></i> <?php echo $this->lang('Voir la chaîne') ?></a>
			<?php endif ?>
		</div>
	</div>
<?php endforeach ?>
</div>

<!-- Lecteur intégré (modal) — une instance partagée par page -->
<script>
(function(){
	if (window._nfLivePlayerInit) return;
	window._nfLivePlayerInit = true;

	var modal, iframe, label;

	function ensureModal(){
		if (modal) return;
		modal = document.createElement('div');
		modal.className = 'nf-twitch-modal';
		modal.innerHTML =
			'<div class="nf-twitch-modal-backdrop"></div>' +
			'<div class="nf-twitch-modal-dialog">' +
				'<div class="nf-twitch-modal-header">' +
					'<span class="nf-twitch-modal-label"><i class="fas fa-broadcast-tower"></i> <span></span></span>' +
					'<a href="#" target="_blank" rel="noopener" class="nf-twitch-modal-open" title="Ouvrir la chaîne"><i class="fas fa-external-link-alt"></i></a>' +
					'<button type="button" class="nf-twitch-modal-close" aria-label="Fermer">×</button>' +
				'</div>' +
				'<div class="nf-twitch-modal-body"><iframe allowfullscreen frameborder="0" scrolling="no"></iframe></div>' +
			'</div>';
		document.body.appendChild(modal);
		iframe = modal.querySelector('iframe');
		label  = modal.querySelector('.nf-twitch-modal-label span');

		var close = function(){ modal.classList.remove('show'); iframe.src = ''; };
		modal.querySelector('.nf-twitch-modal-backdrop').addEventListener('click', close);
		modal.querySelector('.nf-twitch-modal-close').addEventListener('click', close);
		document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && modal.classList.contains('show')) close(); });
	}

	document.addEventListener('click', function(e){
		var btn = e.target.closest('.widget-twitch-play');
		if (!btn) return;
		e.preventDefault();
		if (btn.dataset.mode === 'newtab'){ window.open(btn.dataset.channelUrl, '_blank', 'noopener'); return; }
		ensureModal();
		iframe.src = btn.dataset.embed;
		label.textContent = btn.dataset.channel;
		modal.querySelector('.nf-twitch-modal-open').href = btn.dataset.channelUrl;
		modal.classList.add('show');
	});
})();
</script>
