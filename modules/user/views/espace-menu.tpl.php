<?php
/*
 * Le menu de l'espace membre (chantier A, étape A1, 2026-10-05), rendu par User::espace() : une colonne à
 * gauche sur ordinateur, une bande d'onglets qui défile au téléphone (css/user-space.css). La page courante
 * est marquée, pour l'œil (`actif`) et pour les lecteurs d'écran (`aria-current`). Les entrées viennent de
 * User::menu_espace() — modules compris.
 */
$menu  = $menu ?? [];
$actif = $actif ?? '';
?>
<nav class="nf-espace-menu" aria-label="<?php echo $this->lang('Mon espace') ?>">
	<?php foreach ($menu as $groupe => $entrees): if (!$entrees) continue; ?>
	<ul class="nf-espace-groupe">
		<?php if ($groupe === 'reglages'): ?>
		<li class="nf-espace-titre" aria-hidden="true"><?php echo $this->lang('Réglages') ?></li>
		<?php endif ?>
		<?php foreach ($entrees as $e): $courant = $e['url'] === $actif; ?>
		<li>
			<a class="nf-espace-lien<?php echo $courant ? ' actif' : '' ?>" href="<?php echo url($e['url']) ?>"<?php echo $courant ? ' aria-current="page"' : '' ?>>
				<?php echo icon($e['icone']) ?>
				<span class="nf-espace-libelle"><?php echo nf_texte($e['titre']) ?></span>
				<?php if (!empty($e['badge'])): ?><span class="nf-espace-badge"><?php echo (int) $e['badge'] ?></span><?php endif ?>
			</a>
		</li>
		<?php endforeach ?>
	</ul>
	<?php endforeach ?>
</nav>
