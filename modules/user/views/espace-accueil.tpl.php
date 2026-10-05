<?php
/*
 * L'en-tête de « Mon espace » (chantier A, étape A1) : qui je suis, et les deux accès qu'on y cherche — voir
 * son profil comme les autres le voient, le modifier. La grande carte du profil, posée au-dessus du menu,
 * repoussait celui-ci d'un écran entier au téléphone ; le profil public, lui, se refait à l'étape A2.
 */
$user    = $user ?? NeoFrag()->user;
$groupes = NeoFrag()->groups($user->id);
?>
<div class="nf-espace-accueil">
	<?php echo $user->avatar() ?>
	<div class="nf-espace-accueil-nom">
		<h2><?php echo nf_texte($user->username) ?></h2>
		<?php if ($groupes): ?>
		<div class="user-profile-groups">
			<?php foreach ($groupes as $gid): ?>
				<?php echo NeoFrag()->groups->display($gid, TRUE, FALSE) ?>
			<?php endforeach ?>
		</div>
		<?php endif ?>
		<?php if ($user->registration_date): ?>
		<small class="text-muted"><?php echo $this->lang('Inscrit le %s', timetostr($this->lang('d/m/Y'), $user->registration_date)) ?></small>
		<?php endif ?>
	</div>
	<div class="nf-espace-accueil-actions">
		<a class="btn btn-outline-secondary" href="<?php echo url('user/'.(int) $user->id.'/'.url_title((string) $user->username)) ?>"><?php echo icon('far fa-eye').' '.$this->lang('Voir mon profil') ?></a>
		<a class="btn btn-primary" href="<?php echo url('user/profile') ?>"><?php echo icon('fas fa-pen').' '.$this->lang('Modifier mon profil') ?></a>
	</div>
</div>
