<?php
/*
 * Mes comptes liés (2026-10-01) : les comptes externes (Discord, GitHub, Google) liés au
 * membre, avec de quoi les délier, puis ceux que le site propose et qu'il peut lier. Un compte
 * Discord lié fait publier sous ce compte ce qu'il écrit depuis Discord.
 */
$lignes       = $lignes ?? [];
$a_lier       = $a_lier ?? [];
$sans_secours = $sans_secours ?? FALSE;
?>
<p class="text-muted"><?php echo $this->lang('Un compte lié vous permet de vous connecter avec lui. Lié à Discord, ce que vous écrivez depuis le serveur Discord du site est publié sous votre compte.') ?></p>

<?php if ($lignes): ?>
<ul class="list-group mb-3">
	<?php foreach ($lignes as $l): ?>
	<li class="list-group-item d-flex align-items-center gap-3">
		<?php if ($l['avatar'] !== '' && str_starts_with($l['avatar'], 'https://')): ?>
		<img src="<?php echo nf_texte($l['avatar']) ?>" alt="" width="40" height="40" class="rounded-circle" loading="lazy" referrerpolicy="no-referrer" />
		<?php else: ?>
		<span class="fs-4"><?php echo icon($l['fournisseur']['icone']) ?></span>
		<?php endif ?>
		<div class="flex-grow-1">
			<strong><?php echo icon($l['fournisseur']['icone']).' '.nf_texte($l['fournisseur']['titre']) ?></strong>
			<?php if ($l['pseudo'] !== ''): ?><br /><small class="text-muted"><?php echo nf_texte($l['pseudo']) ?></small><?php endif ?>
		</div>
		<a class="btn btn-sm btn-outline-danger" href="<?php echo $l['delier'] ?>" data-confirm="<?php echo nf_texte($this->lang('Délier ce compte ? Vous ne pourrez plus vous connecter avec lui.')) ?>"><?php echo icon('fas fa-unlink').' '.$this->lang('Délier') ?></a>
	</li>
	<?php endforeach ?>
</ul>
<?php else: ?>
<div class="alert alert-info"><?php echo $this->lang('Aucun compte lié pour le moment.') ?></div>
<?php endif ?>

<?php if ($sans_secours && $lignes): ?>
<div class="alert alert-warning"><?php echo icon('fas fa-exclamation-triangle').' '.$this->lang('Votre compte n’a pas de mot de passe : vous vous connectez par un compte lié. Définissez un mot de passe dans votre profil pour ne pas en dépendre.') ?></div>
<?php endif ?>

<?php if ($a_lier): ?>
<h3 class="h6 mt-4"><?php echo $this->lang('Lier un compte') ?></h3>
<div class="d-flex flex-wrap gap-2">
	<?php foreach ($a_lier as $f): ?>
	<a class="btn btn-outline-primary" href="<?php echo $f['lier'] ?>"><?php echo icon($f['icone']).' '.nf_texte($f['titre']) ?></a>
	<?php endforeach ?>
</div>
<?php endif ?>
