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

// Données pour le modal de détail côté client (le catalogue est déjà chargé).
// $this->lang() renvoie un objet → on caste en (string) AVANT json_encode (sinon « [object Object] »).
$map = [];
foreach ($addons as $a) { $map[$a['type'] . ':' . $a['name']] = $a; }
$dl_base  = $base_url !== '' ? $base_url : rtrim($this->url->base, '/') . '/marketplace';
$types_js = [];
foreach ($types as $k => $v) { $types_js[$k] = ['one' => (string) $v['one'], 'icon' => $v['icon']]; }
$labels = [
	'by'     => (string) $this->lang('Par'),
	'cat'    => (string) $this->lang('Catégorie'),
	'compat' => (string) $this->lang('Compatibilité'),
	'widgets'=> (string) $this->lang('Widgets fournis'),
	'size'   => (string) $this->lang('Taille'),
	'dl'     => (string) $this->lang('Télécharger'),
	'none'   => (string) $this->lang('Aucune dépendance'),
	'ident'  => (string) $this->lang('Identifiant'),
	'licence'=> (string) $this->lang('Licence'),
	'pose'   => (string) $this->lang('Installation'),
	'zip'    => (string) $this->lang('Archive à déposer dans l\'administration'),
	'scan'   => (string) $this->lang('À poser sur le disque, puis « Scanner le disque »'),
	'sceau'  => (string) $this->lang('Empreinte SHA-256'),
	// Les unités de taille, avec le nombre à la place de %s (« Mo » en français, « MB » ailleurs).
	'mo'     => (string) $this->lang('%s Mo'),
	'ko'     => (string) $this->lang('%s Ko'),
];
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
			$size  = $a['size'] > 1048576 ? $this->lang('%s Mo', round($a['size'] / 1048576, 1)) : $this->lang('%s Ko', round($a['size'] / 1024));
		?>
		<div class="mkt-card" data-type="<?php echo nf_texte($a['type']) ?>" data-key="<?php echo nf_texte($a['type'].':'.$a['name']) ?>" role="button" tabindex="0" title="<?php echo $this->lang('Voir le détail') ?>">
			<div class="mkt-card-top">
				<span class="mkt-ico mkt-ico-<?php echo nf_texte($a['type']) ?>"><i class="<?php echo $tmeta['icon'] ?>"></i></span>
				<span class="mkt-badge"><?php echo nf_texte($tmeta['one']) ?></span>
				<span class="mkt-ver">v<?php echo nf_texte($a['version']) ?></span>
			</div>
			<?php /* L'apercu n'est rendu que s'il existe : une bande vide dirait moins que rien. */ ?>
			<?php if (!empty($a['preview'])): ?>
				<?php $ap = $base_url !== '' ? $base_url.'/'.$a['preview'] : $this->url->base.'marketplace/'.$a['preview']; ?>
				<img class="mkt-apercu" src="<?php echo nf_texte($ap) ?>" alt="" loading="lazy" width="640" height="400" />
			<?php endif ?>
			<h3 class="mkt-title"><?php echo nf_texte($a['title']) ?></h3>
			<p class="mkt-desc"><?php echo nf_texte($a['description'] ?: $this->lang('Addon NeoFrag Reborn.')) ?></p>
			<div class="mkt-card-foot">
				<?php $dl = $base_url !== '' ? $base_url . '/' . $a['file'] : $this->url->base . 'marketplace/' . $a['file']; ?>
				<a class="mkt-dl" href="<?php echo nf_texte($dl) ?>" download>
					<i class="fas fa-download"></i> <?php echo $this->lang('Télécharger') ?> <span><?php echo $size ?></span>
				</a>
				<span class="mkt-more"><?php echo $this->lang('Détails') ?> <i class="fas fa-circle-info"></i></span>
			</div>
		</div>
		<?php endforeach ?>
	</div>

	<div class="mkt-help">
		<i class="fas fa-circle-info"></i>
		<?php echo $this->lang('Installation : télécharge le .zip puis va dans <b>Admin → Thèmes &amp; Addons → Ajouter</b> et envoie l\'archive.') ?>
	</div>
</div>

<div class="mkt-modal" id="mktModal" hidden>
	<div class="mkt-modal-bg" data-mkt-close></div>
	<div class="mkt-modal-box" role="dialog" aria-modal="true" aria-labelledby="mktmTitle">
		<button class="mkt-modal-x" type="button" data-mkt-close aria-label="<?php echo $this->lang('Fermer') ?>">&times;</button>
		<div class="mkt-modal-body"></div>
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

	var MKT   = <?php echo json_encode($map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
	var TYPES = <?php echo json_encode($types_js, JSON_UNESCAPED_UNICODE) ?>;
	var BASE  = <?php echo json_encode($dl_base, JSON_UNESCAPED_SLASHES) ?>;
	var L     = <?php echo json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
	var modal = document.getElementById('mktModal');
	var body  = modal.querySelector('.mkt-modal-body');

	function esc(s) { var d = document.createElement('div'); d.textContent = (s == null ? '' : s); return d.innerHTML; }

	function open(key) {
		var a = MKT[key]; if (!a) return;
		var t = TYPES[a.type] || { one: a.type, icon: 'fas fa-cube' };
		var size = a.size > 1048576 ? L.mo.replace('%s', (a.size / 1048576).toFixed(1)) : L.ko.replace('%s', Math.round(a.size / 1024));
		var req = [];
		if (a.requires && a.requires.base) req.push('NeoFrag ' + a.requires.base);
		if (a.requires && a.requires.addons) for (var k in a.requires.addons) req.push(k + ' ' + a.requires.addons[k]);
		var widgets = (a.provides_widgets || []).join(', ');
		var dl = BASE + '/' + a.file;
		var rows = '';
		rows += '<div><dt>' + esc(L.cat) + '</dt><dd>' + esc(a.category || '—') + '</dd></div>';
		rows += '<div><dt>' + esc(L.ident) + '</dt><dd><code>' + esc(a.name) + '</code></dd></div>';
		rows += '<div><dt>' + esc(L.compat) + '</dt><dd>' + (req.length ? esc(req.join(' · ')) : esc(L.none)) + '</dd></div>';
		if (widgets) rows += '<div><dt>' + esc(L.widgets) + '</dt><dd>' + esc(widgets) + '</dd></div>';
		rows += '<div><dt>' + esc(L.pose) + '</dt><dd>' + esc(a.install === 'scan' ? L.scan : L.zip) + '</dd></div>';
		if (a.license) rows += '<div><dt>' + esc(L.licence) + '</dt><dd>' + esc(a.license) + '</dd></div>';
		rows += '<div><dt>' + esc(L.size) + '</dt><dd>' + esc(size) + '</dd></div>';
		if (a.sha256) rows += '<div><dt>' + esc(L.sceau) + '</dt><dd><code>' + esc(a.sha256.slice(0, 16)) + '…</code></dd></div>';

		// L'apercu d'abord : c'est ce qu'on vient voir. Celui de la carte, dont le site a déjà réglé l'adresse : venu
		// du catalogue d'un autre site, il passe par le relais (helpers/relais.php), et le navigateur ne le demande
		// pas ailleurs — la politique de sécurité refuserait d'ailleurs une image d'un autre site.
		var vignette = document.querySelector('.mkt-card[data-key="' + (window.CSS && CSS.escape ? CSS.escape(key) : key) + '"] .mkt-apercu');
		var apercu = vignette ? '<img class="mkt-apercu mkt-modal-apercu" src="' + esc(vignette.getAttribute('src')) + '" alt="" />' : '';

		body.innerHTML =
			apercu +
			'<div class="mkt-modal-head">' +
				'<span class="mkt-ico mkt-ico-' + esc(a.type) + '"><i class="' + esc(t.icon) + '"></i></span>' +
				'<div><h3 id="mktmTitle">' + esc(a.title) + '</h3>' +
				'<div class="mkt-modal-meta"><span class="mkt-badge">' + esc(t.one) + '</span>' +
				'<span class="mkt-ver">v' + esc(a.version) + '</span>' +
				(a.author ? '<span class="mkt-modal-by">' + esc(L.by) + ' ' + esc(a.author) + '</span>' : '') +
				'</div></div></div>' +
			'<p class="mkt-modal-desc">' + esc(a.description || '') + '</p>' +
			'<dl class="mkt-modal-info">' + rows + '</dl>' +
			'<a class="mkt-dl mkt-modal-dl" href="' + esc(dl) + '" download><i class="fas fa-download"></i> ' + esc(L.dl) + '</a>';
		modal.hidden = false;
		document.body.style.overflow = 'hidden';
	}
	function close() { modal.hidden = true; document.body.style.overflow = ''; }

	cards.forEach(function (c) {
		c.addEventListener('click', function (e) { if (e.target.closest('.mkt-dl')) { return; } open(c.getAttribute('data-key')); });
		c.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(c.getAttribute('data-key')); } });
	});
	modal.querySelectorAll('[data-mkt-close]').forEach(function (el) { el.addEventListener('click', close); });
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) close(); });
})();
</script>
