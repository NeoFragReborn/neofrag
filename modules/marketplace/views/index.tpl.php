<?php
$types = [
	'module'        => ['label' => $this->lang('Modules'),     'one' => $this->lang('Module'),      'icon' => 'fas fa-cube'],
	'widget'        => ['label' => $this->lang('Widgets'),     'one' => $this->lang('Widget'),      'icon' => 'fas fa-puzzle-piece'],
	'theme'         => ['label' => $this->lang('Thèmes'),      'one' => $this->lang('Thème'),       'icon' => 'fas fa-palette'],
	'authenticator' => ['label' => $this->lang('Connecteurs'), 'one' => $this->lang('Connecteur'), 'icon' => 'fas fa-right-to-bracket'],
];
$by_type = [];
foreach ($addons as $a) { $by_type[$a['type']][] = $a; }
$total = count($addons);
?>
<div class="mkt">
	<div class="mkt-head">
		<span class="mkt-chip"><i class="fas fa-store"></i> <?php echo $this->lang('Marketplace') ?></span>
		<h1><?php echo $this->lang('Étends ton site') ?></h1>
		<p><?php echo $this->lang('%s modules, widgets, thèmes et connecteurs à télécharger et installer en quelques clics.', $total) ?></p>
	</div>

	<div class="mkt-filters">
		<button class="mkt-filter active" data-filter="all"><?php echo $this->lang('Tout') ?> <span><?php echo $total ?></span></button>
		<?php foreach ($types as $key => $meta): if (empty($by_type[$key])) continue; ?>
		<button class="mkt-filter" data-filter="<?php echo $key ?>"><i class="<?php echo $meta['icon'] ?>"></i> <?php echo $meta['label'] ?> <span><?php echo count($by_type[$key]) ?></span></button>
		<?php endforeach ?>
	</div>

	<div class="mkt-grid">
		<?php foreach ($addons as $a):
			$tmeta = $types[$a['type']] ?? ['one' => $a['type'], 'icon' => 'fas fa-cube'];
			$size  = $a['size'] > 1048576 ? round($a['size'] / 1048576, 1).' Mo' : round($a['size'] / 1024).' Ko';
		?>
		<div class="mkt-card" data-type="<?php echo htmlspecialchars($a['type']) ?>">
			<div class="mkt-card-top">
				<span class="mkt-ico mkt-ico-<?php echo htmlspecialchars($a['type']) ?>"><i class="<?php echo $tmeta['icon'] ?>"></i></span>
				<span class="mkt-badge"><?php echo htmlspecialchars((string) $tmeta['one']) ?></span>
				<span class="mkt-ver">v<?php echo htmlspecialchars($a['version']) ?></span>
			</div>
			<h3 class="mkt-title"><?php echo htmlspecialchars($a['title']) ?></h3>
			<p class="mkt-desc"><?php echo htmlspecialchars($a['description'] ?: $this->lang('Addon NeoFrag Reborn.')) ?></p>
			<div class="mkt-card-foot">
				<?php $dl = $base_url !== '' ? $base_url . '/' . $a['file'] : $this->url->base . 'marketplace/' . $a['file']; ?>
				<a class="mkt-dl" href="<?php echo htmlspecialchars($dl) ?>" download>
					<i class="fas fa-download"></i> <?php echo $this->lang('Télécharger') ?> <span><?php echo $size ?></span>
				</a>
				<?php if (($a['install'] ?? 'zip') === 'scan'): ?>
				<span class="mkt-scan" title="<?php echo $this->lang('À installer via « Scanner le disque » (l\'upload ZIP ne gère pas ce type).') ?>"><i class="fas fa-circle-info"></i></span>
				<?php endif ?>
			</div>
		</div>
		<?php endforeach ?>
	</div>

	<div class="mkt-help">
		<i class="fas fa-circle-info"></i>
		<?php echo $this->lang('Installation : télécharge le .zip puis va dans <b>Admin → Thèmes &amp; Addons → Ajouter</b> et envoie l\'archive.') ?>
	</div>
</div>

<script>
(function () {
	var btns  = document.querySelectorAll('.mkt-filter');
	var cards = document.querySelectorAll('.mkt-card');
	btns.forEach(function (b) {
		b.addEventListener('click', function () {
			btns.forEach(function (x) { x.classList.remove('active'); });
			b.classList.add('active');
			var f = b.getAttribute('data-filter');
			cards.forEach(function (c) {
				c.style.display = (f === 'all' || c.getAttribute('data-type') === f) ? '' : 'none';
			});
		});
	});
})();
</script>
