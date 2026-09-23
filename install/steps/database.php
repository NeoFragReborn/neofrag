<?php /** Étape 2 — connexion base de données. */ ?>
<p class="hint" style="margin-bottom:22px"><?php echo lang('Renseignez une base <strong>vierge</strong> : NeoFrag y créera ses tables et ses données de base.'); ?></p>
<form method="post" action="?step=database">
	<input type="hidden" name="csrf" value="<?php echo nf_e($CSRF); ?>">
	<div class="row2 large">
		<label><?php echo nf_e(lang('Hôte')); ?>
			<input name="hostname" value="<?php echo nf_e($old['hostname'] ?? 'localhost'); ?>" required>
		</label>
		<label><?php echo nf_e(lang('Port')); ?>
			<input name="port" type="number" value="<?php echo nf_e($old['port'] ?? '3306'); ?>">
		</label>
	</div>
	<div class="row2">
		<label><?php echo nf_e(lang('Utilisateur')); ?>
			<input name="username" value="<?php echo nf_e($old['username'] ?? ''); ?>" required>
		</label>
		<label><?php echo nf_e(lang('Mot de passe')); ?>
			<input name="password" type="password" value="">
		</label>
	</div>
	<label><?php echo nf_e(lang('Nom de la base')); ?>
		<input name="database" value="<?php echo nf_e($old['database'] ?? 'neofrag'); ?>" required>
	</label>
	<button class="btn" type="submit"><?php echo nf_e(lang('Tester et installer')); ?></button>
</form>
