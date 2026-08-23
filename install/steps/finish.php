<?php
/** Étape finale — rendue juste après la création de l'admin (db.txt déjà posé). */
$summary = $_SESSION['nf_install_summary'] ?? null;
?>
<h2>Installation terminée</h2>
<p>NeoFrag est prêt. Le verrou <code>install/db.txt</code> désactive désormais l'assistant.</p>

<?php if ($summary): ?>
	<ul class="checks">
		<li class="ok"><span>Modules installés</span> <?php echo count($summary['modules'] ?? []); ?></li>
		<li class="ok"><span>Widgets / thèmes</span> <?php echo count($summary['widgets'] ?? []) . ' / ' . count($summary['themes'] ?? []); ?></li>
		<li class="ok"><span>Page d'accueil</span> <?php echo nf_e($summary['homepage']); ?></li>
	</ul>
	<?php if (!empty($summary['errors'])): ?>
		<div class="errors">
			<?php foreach ($summary['errors'] as $err): ?><p><?php echo nf_e($err); ?></p><?php endforeach; ?>
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
