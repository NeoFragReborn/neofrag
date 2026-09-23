<?php /** Étape 3 — nom du site + compte super-administrateur. */ ?>
<form method="post" action="?step=admin">
	<input type="hidden" name="csrf" value="<?php echo nf_e($CSRF); ?>">
	<label><?php echo nf_e(lang('Nom du site')); ?>
		<input name="site_name" value="<?php echo nf_e($old['site_name'] ?? 'NeoFrag'); ?>" required>
	</label>
	<label><?php echo nf_e(lang('Pseudo administrateur')); ?>
		<input name="username" value="<?php echo nf_e($old['username'] ?? 'admin'); ?>" required>
	</label>
	<label><?php echo nf_e(lang('Adresse email')); ?>
		<input name="email" type="email" value="<?php echo nf_e($old['email'] ?? ''); ?>" required>
	</label>
	<div class="row2">
		<label><?php echo nf_e(lang('Mot de passe')); ?> <span class="hint"><?php echo nf_e(lang('(8 caractères minimum)')); ?></span>
			<input name="password" type="password" required>
		</label>
		<label><?php echo nf_e(lang('Confirmation')); ?>
			<input name="password2" type="password" required>
		</label>
	</div>
	<div class="row2">
		<label><?php echo nf_e(lang('Mot de passe webmaster')); ?> <span class="hint"><?php echo nf_e(lang('(sudo des actions sensibles, distinct du login — 8 car. min. Laisser vide pour le définir plus tard depuis Monitoring.)')); ?></span>
			<input name="webmaster_password" type="password">
		</label>
		<label><?php echo nf_e(lang('Confirmation webmaster')); ?>
			<input name="webmaster_password2" type="password">
		</label>
	</div>
	<button class="btn" type="submit"><?php echo nf_e(lang('Créer le compte')); ?></button>
</form>
