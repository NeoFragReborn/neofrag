<?php /** Étape 2 — connexion base de données. */ ?>
<h2>Base de données</h2>
<p class="hint">Renseignez une base <strong>vierge</strong> : NeoFrag y créera ses tables et ses données de base.</p>
<form method="post" action="?step=database">
	<input type="hidden" name="csrf" value="<?php echo nf_e($CSRF); ?>">
	<label>Hôte
		<input name="hostname" value="<?php echo nf_e($old['hostname'] ?? 'localhost'); ?>" required>
	</label>
	<label>Port
		<input name="port" type="number" value="<?php echo nf_e($old['port'] ?? '3306'); ?>">
	</label>
	<label>Utilisateur
		<input name="username" value="<?php echo nf_e($old['username'] ?? ''); ?>" required>
	</label>
	<label>Mot de passe
		<input name="password" type="password" value="">
	</label>
	<label>Nom de la base
		<input name="database" value="<?php echo nf_e($old['database'] ?? 'neofrag'); ?>" required>
	</label>
	<button class="btn" type="submit">Tester et installer</button>
</form>
