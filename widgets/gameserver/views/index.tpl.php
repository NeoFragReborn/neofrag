<?php
$pct = ($online && !empty($data['players_max']))
	? min(100, round($data['players'] / $data['players_max'] * 100))
	: 0;

// Build connect URL based on engine
$connect_url = '';
$connect_label = $this->lang('Copier l\'IP');
if ($online && in_array($engine, ['source', 'goldsource'], TRUE)) {
	$connect_url = 'steam://connect/'.$host.':'.$port;
	$connect_label = $this->lang('Rejoindre via Steam');
}
$ip_string = $host.':'.$port;
?>
<div class="widget-gs widget-gs-<?php echo htmlspecialchars($engine) ?> <?php echo $online ? 'is-online' : 'is-offline' ?>">
	<?php if (!$online): ?>
	<div class="widget-gs-offline">
		<i class="fas fa-power-off"></i>
		<div class="widget-gs-offline-text">
			<strong><?php echo $this->lang('Hors ligne') ?></strong>
			<small><?php echo htmlspecialchars($ip_string) ?></small>
		</div>
	</div>
	<?php else: ?>

	<?php if (in_array($engine, ['mc-java', 'mc-bedrock'], TRUE) && !empty($data['icon'])): ?>
	<div class="widget-gs-icon">
		<img src="<?php echo htmlspecialchars($data['icon']) ?>" alt="" width="64" height="64" />
	</div>
	<?php endif ?>

	<?php if (!empty($data['motd_html'])): ?>
	<div class="widget-gs-motd"><?php echo $data['motd_html'] ?></div>
	<?php endif ?>

	<div class="widget-gs-stats">
		<div class="widget-gs-stat-row">
			<i class="fas fa-users"></i>
			<span class="widget-gs-stat-num"><?php echo (int)$data['players'] ?>/<?php echo (int)$data['players_max'] ?></span>
			<span class="widget-gs-stat-lbl"><?php echo $this->lang('joueurs') ?></span>
		</div>
		<div class="widget-gs-bar">
			<div class="widget-gs-bar-fill" style="width: <?php echo $pct ?>%"></div>
		</div>
	</div>

	<?php if (!empty($data['map']) || !empty($data['version']) || !empty($data['game'])): ?>
	<div class="widget-gs-meta">
		<?php if (!empty($data['game'])): ?>
		<span class="widget-gs-pill"><i class="fas fa-gamepad"></i> <?php echo htmlspecialchars($data['game']) ?></span>
		<?php endif ?>
		<?php if (!empty($data['map'])): ?>
		<span class="widget-gs-pill"><i class="fas fa-map"></i> <?php echo htmlspecialchars($data['map']) ?></span>
		<?php endif ?>
		<?php if (!empty($data['version'])): ?>
		<span class="widget-gs-pill"><i class="fas fa-code-branch"></i> <?php echo htmlspecialchars(strip_tags($data['version'])) ?></span>
		<?php endif ?>
		<?php if (!empty($data['vac'])): ?>
		<span class="widget-gs-pill widget-gs-pill-vac" title="VAC secured"><i class="fas fa-shield-alt"></i> VAC</span>
		<?php endif ?>
	</div>
	<?php endif ?>

	<?php if (!empty($data['players_list']) && is_array($data['players_list'])): ?>
	<details class="widget-gs-players">
		<summary><?php echo $this->lang('Liste des joueurs') ?> (<?php echo count($data['players_list']) ?>)</summary>
		<ul class="widget-gs-players-list">
			<?php foreach ($data['players_list'] as $p):
				$pname = is_array($p) ? ($p['name'] ?? '?') : $p;
				$pscore = is_array($p) ? ($p['score'] ?? NULL) : NULL;
			?>
			<li>
				<i class="fas fa-user"></i>
				<span><?php echo htmlspecialchars($pname) ?></span>
				<?php if ($pscore !== NULL): ?><span class="widget-gs-player-score"><?php echo (int)$pscore ?></span><?php endif ?>
			</li>
			<?php endforeach ?>
		</ul>
	</details>
	<?php endif ?>

	<?php if ($connect_url): ?>
	<a href="<?php echo htmlspecialchars($connect_url) ?>" class="widget-gs-cta"><i class="fas fa-sign-in-alt"></i> <?php echo $connect_label ?></a>
	<?php else: ?>
	<button type="button" class="widget-gs-cta widget-gs-copy" data-ip="<?php echo htmlspecialchars($ip_string) ?>"><i class="fas fa-copy"></i> <?php echo $ip_string ?></button>
	<?php endif ?>

	<?php endif ?>
</div>

<script>
(function(){
	document.querySelectorAll('.widget-gs-copy').forEach(function(btn){
		if (btn.dataset.bound) return;
		btn.dataset.bound = '1';
		btn.addEventListener('click', function(){
			var ip = btn.dataset.ip;
			if (!navigator.clipboard) return;
			navigator.clipboard.writeText(ip).then(function(){
				var prev = btn.innerHTML;
				btn.innerHTML = '<i class="fas fa-check"></i> <?php echo addslashes($this->lang('Copié !')) ?>';
				setTimeout(function(){ btn.innerHTML = prev; }, 1500);
			});
		});
	});
})();
</script>
