<?php
/** Étape finale — rendue juste après la création de l'admin (db.txt déjà posé). */
$summary = $_SESSION['nf_install_summary'] ?? null;
?>
<h2>Installation terminée</h2>
<p>NeoFrag est prêt. Le verrou <code>install/db.txt</code> désactive désormais l'assistant.</p>

<?php if ($summary): ?>
	<ul class="checks">
		<li class="ok"><span>Profil</span> <?php echo nf_e($summary['preset']); ?></li>
		<li class="ok">
			<span>Modules activés</span>
			<?php echo $summary['modules'] ? nf_e(implode(', ', $summary['modules'])) : 'cœur seul'; ?>
		</li>
		<li class="ok"><span>Page d'accueil</span> <?php echo nf_e($summary['homepage']); ?></li>
		<?php if (!empty($summary['tier2'])): ?>
			<li class="ok"><span>Marketplace</span> <?php echo nf_e(implode(', ', $summary['tier2'])); ?></li>
		<?php endif; ?>
	</ul>
	<?php if (!empty($summary['errors']) || !empty($summary['tier2_skipped'])): ?>
		<div class="errors">
			<?php foreach (($summary['errors'] ?? []) as $err): ?><p><?php echo nf_e($err); ?></p><?php endforeach; ?>
			<?php foreach (($summary['tier2_skipped'] ?? []) as $skip): ?><p>Addon sauté : <?php echo nf_e($skip); ?></p><?php endforeach; ?>
		</div>
	<?php endif; ?>
<?php endif; ?>

<p class="hint">Vous pourrez ajouter d'autres modules depuis le marketplace, et tout reconfigurer dans l'administration.</p>
<p class="hint">Pour plus de sécurité, supprimez le dossier <code>install/</code> de votre serveur.</p>
<p>
	<a class="btn" href="./">Aller sur le site</a>
	<a class="btn ghost" href="admin">Administration</a>
</p>
<?php unset($_SESSION['nf_install_summary']); ?>
