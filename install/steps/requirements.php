<?php
/** Étape 1 — prérequis serveur. $NF_CONFIG / $NF_ROOT fournis par index.php. */
$checks = [
	'PHP ≥ 8.2'                       => version_compare(PHP_VERSION, '8.2.0', '>='),
	'Extension mysqli'                => extension_loaded('mysqli'),
	'Extension mbstring'              => extension_loaded('mbstring'),
	'Extension gd'                    => extension_loaded('gd'),
	'Extension zip'                   => extension_loaded('zip'),
	'Extension curl'                  => extension_loaded('curl'),
	'Extension intl'                  => extension_loaded('intl'),
	'Dossier config/ inscriptible'    => is_dir($NF_CONFIG) ? is_writable($NF_CONFIG) : is_writable($NF_ROOT),
];
$all_ok = !in_array(false, $checks, true);
?>
<h2>Prérequis serveur</h2>
<ul class="checks">
	<?php foreach ($checks as $label => $pass): ?>
		<li class="<?php echo $pass ? 'ok' : 'ko'; ?>"><?php echo nf_e($label); ?><span><?php echo $pass ? '✓' : '✗'; ?></span></li>
	<?php endforeach; ?>
</ul>
<?php if ($all_ok): ?>
	<a class="btn" href="?step=database">Continuer</a>
<?php else: ?>
	<p class="hint">Corrigez les points en rouge (extensions PHP, droits du dossier <code>config/</code>) puis revérifiez.</p>
	<a class="btn ghost" href="?step=requirements">Revérifier</a>
<?php endif; ?>
