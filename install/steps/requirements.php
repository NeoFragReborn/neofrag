<?php
/**
 * Étape 1 — prérequis serveur. $NF_CONFIG / $NF_ROOT fournis par index.php.
 *
 * Chaque contrôle annonce sa VALEUR constatée, pas seulement un ✓ : quand quelque chose manque,
 * savoir quelle version de PHP tourne réellement évite un aller-retour avec l'hébergeur. Le titre
 * de l'étape est porté par le bandeau du gabarit (install/layout.php) — pas de <h2> ici.
 */
$dossier_config = is_dir($NF_CONFIG) ? $NF_CONFIG : $NF_ROOT;

/** libellé => [réussi ?, valeur constatée] */
$checks = [
	'PHP ≥ 8.2' => [version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION],
];

foreach (['mysqli', 'mbstring', 'gd', 'zip', 'curl', 'intl'] as $extension) {
	$checks[lang('Extension %s', $extension)] = [extension_loaded($extension), extension_loaded($extension) ? lang('présente') : lang('absente')];
}

$checks[lang('Dossier config/')] = [is_writable($dossier_config), is_writable($dossier_config) ? lang('inscriptible') : lang('non inscriptible')];

$all_ok = TRUE;
foreach ($checks as [$pass, $_]) {
	$all_ok = $all_ok && $pass;
}
?>
<ul class="checks">
	<?php foreach ($checks as $label => [$pass, $value]): ?>
		<li class="<?php echo $pass ? 'ok' : 'ko'; ?>">
			<span class="ico" aria-hidden="true"><?php echo $pass ? '&#10003;' : '&#10007;'; ?></span>
			<span>
				<span class="t"><?php echo nf_e($label); ?></span>
				<span class="v"><?php echo nf_e($value); ?></span>
			</span>
		</li>
	<?php endforeach; ?>
</ul>

<?php if ($all_ok): ?>
	<p class="hint"><?php echo nf_e(lang('Tout est en ordre. Votre hébergement remplit les conditions nécessaires.')); ?></p>
	<a class="btn" href="?step=database"><?php echo nf_e(lang('Continuer')); ?></a>
<?php else: ?>
	<p class="hint"><?php echo lang('Corrigez les points en rouge — extensions PHP manquantes, ou droits d\'écriture sur le dossier <code>config/</code> — puis revérifiez.'); ?></p>
	<a class="btn ghost" href="?step=requirements"><?php echo nf_e(lang('Revérifier')); ?></a>
<?php endif; ?>
