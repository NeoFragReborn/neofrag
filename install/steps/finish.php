<?php
/** Étape finale — rendue juste après la création de l'admin (db.txt déjà posé). */
$summary = $_SESSION['nf_install_summary'] ?? null;
?>
<p><?php echo lang('NeoFrag est prêt. Le verrou <code>install/db.txt</code> désactive désormais l\'assistant.'); ?></p>

<?php if ($summary): ?>
	<?php if (!empty($summary['added'])): ?>
		<p class="hint" style="margin-bottom:18px">
			<?php echo nf_e(lang('Ajoutés d\'office parce que d\'autres modules en dépendent :')); ?>
			<strong><?php echo nf_e(implode(', ', $summary['added'])); ?></strong>.
		</p>
	<?php endif; ?>

	<ul class="checks">
		<li class="ok"><span class="ico" aria-hidden="true">&#10003;</span><span><span class="t"><?php echo nf_e(lang('Modules installés')); ?></span><span class="v"><?php echo count($summary['modules'] ?? []); ?></span></span></li>
		<li class="ok"><span class="ico" aria-hidden="true">&#10003;</span><span><span class="t"><?php echo nf_e(lang('Widgets / thèmes')); ?></span><span class="v"><?php echo count($summary['widgets'] ?? []) . ' / ' . count($summary['themes'] ?? []); ?></span></span></li>
		<li class="ok"><span class="ico" aria-hidden="true">&#10003;</span><span><span class="t"><?php echo nf_e(lang('Page d\'accueil')); ?></span><span class="v"><?php echo nf_e($summary['homepage']); ?></span></span></li>
	</ul>
	<?php if (!empty($summary['errors'])): ?>
		<div class="errors">
			<?php foreach ($summary['errors'] as $err): ?><p><?php echo nf_e($err); ?></p><?php endforeach; ?>
		</div>
	<?php endif; ?>
<?php endif; ?>

<p class="hint"><?php echo nf_e(lang('Vous pourrez ajouter d\'autres modules depuis le marketplace, et tout reconfigurer dans l\'administration.')); ?></p>
<p class="hint"><?php echo lang('Pour plus de sécurité, supprimez le dossier <code>install/</code> de votre serveur.'); ?></p>
<p>
	<a class="btn" href="./"><?php echo nf_e(lang('Aller sur le site')); ?></a>
	<a class="btn ghost" href="admin"><?php echo nf_e(lang('Administration')); ?></a>
</p>
<?php unset($_SESSION['nf_install_summary']); ?>
