<?php
// R1.3 — Page de sélection : choix d'un module pour ouvrir sa matrice de permissions, rangés par
// rubrique comme dans la barre latérale.
$modules   = $modules ?? [];
$rubriques = nf_rubriques_admin();
$groupes   = [];
foreach ($modules as $m)
{
	$cle = 'autres';
	foreach ($rubriques as $nom => $rubrique)
	{
		if (in_array($m['name'], $rubrique['modules'], TRUE))
		{
			$cle = $nom;
			break;
		}
	}
	$groupes[$cle][] = $m;
}
?>
<?php if (empty($modules)): ?>
	<div class="nf-empty">
		<i class="fas fa-th"></i>
		<div class="nf-empty-title"><?php echo $this->lang('Aucun module ne déclare de permissions.') ?></div>
	</div>
<?php else: ?>
	<p class="text-muted mb-3">
		<?php echo $this->lang('Sélectionne un module pour gérer ses permissions par rôle :') ?>
	</p>
	<div class="matrix-rubriques">
	<?php foreach ($rubriques as $nom => $rubrique): ?>
		<?php if (empty($groupes[$nom])) continue ?>
		<div class="matrix-rubrique">
			<div class="matrix-rubrique-titre">
				<i class="<?php echo $rubrique['icon'] ?>"></i> <?php echo nf_texte($rubrique['title']) ?>
				<span class="matrix-rubrique-compte"><?php echo count($groupes[$nom]) ?></span>
			</div>
			<div class="matrix-modules">
				<?php foreach ($groupes[$nom] as $m): ?>
					<a class="matrix-module-btn" href="<?php echo url('admin/access/matrix/'.urlencode($m['name'])) ?>" title="<?php echo nf_texte($m['name']) ?>">
						<i class="<?php echo nf_texte($m['icon']) ?> fa-fw"></i>
						<span><?php echo nf_texte($m['title']) ?></span>
					</a>
				<?php endforeach ?>
			</div>
		</div>
	<?php endforeach ?>
	</div>
<?php endif ?>
