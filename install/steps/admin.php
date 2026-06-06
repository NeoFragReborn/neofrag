<?php /** Étape 3 — nom du site + compte super-administrateur. */ ?>
<h2>Administrateur</h2>
<form method="post" action="?step=admin">
	<input type="hidden" name="csrf" value="<?php echo nf_e($CSRF); ?>">
	<label>Nom du site
		<input name="site_name" value="<?php echo nf_e($old['site_name'] ?? 'NeoFrag'); ?>" required>
	</label>
	<label>Pseudo administrateur
		<input name="username" value="<?php echo nf_e($old['username'] ?? 'admin'); ?>" required>
	</label>
	<label>Adresse email
		<input name="email" type="email" value="<?php echo nf_e($old['email'] ?? ''); ?>" required>
	</label>
	<label>Mot de passe <span class="hint">(8 caractères minimum)</span>
		<input name="password" type="password" required>
	</label>
	<label>Confirmation
		<input name="password2" type="password" required>
	</label>
	<button class="btn" type="submit">Créer le compte</button>
</form>
