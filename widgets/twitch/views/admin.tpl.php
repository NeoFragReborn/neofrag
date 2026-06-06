<ul class="nav nav-pills" id="tw-tabs" role="tablist">
	<li class="nav-item"><a class="nav-link active" id="tw-options-tab" data-toggle="pill" href="#tw-options" role="tab"><?php echo icon('fas fa-cogs').' '.$this->lang('Options') ?></a></li>
	<li class="nav-item"><a class="nav-link" id="tw-help-tab" data-toggle="pill" href="#tw-help" role="tab"><?php echo icon('far fa-life-ring').' '.$this->lang('Aide') ?></a></li>
</ul>
<div class="tab-content border-light" id="tw-tabContent">
	<div class="tab-pane fade show active" id="tw-options" role="tabpanel">
		<div class="form-group row">
			<label for="settings-tw-user" class="col-4 col-form-label"><i class="fab fa-twitch"></i> <?php echo $this->lang('Pseudo Twitch') ?></label>
			<div class="col-7">
				<input type="text" class="form-control" name="settings[username]" id="settings-tw-user" value="<?php echo htmlspecialchars($username) ?>" placeholder="ex: ninja" autocomplete="off" />
				<small class="form-text text-muted"><?php echo $this->lang('Login (URL twitch.tv/<b>VOTRE-LOGIN</b>), pas le nom d\'affichage.') ?></small>
			</div>
		</div>
		<div class="form-group row">
			<label for="settings-tw-cid" class="col-4 col-form-label"><?php echo $this->lang('Client ID') ?></label>
			<div class="col-7">
				<input type="text" class="form-control" name="settings[client_id]" id="settings-tw-cid" value="<?php echo htmlspecialchars($client_id) ?>" autocomplete="off" />
			</div>
		</div>
		<div class="form-group row">
			<label for="settings-tw-csecret" class="col-4 col-form-label"><?php echo $this->lang('Client Secret') ?></label>
			<div class="col-7">
				<input type="password" class="form-control" name="settings[client_secret]" id="settings-tw-csecret" value="<?php echo htmlspecialchars($client_secret) ?>" autocomplete="new-password" />
				<small class="form-text text-muted"><?php echo $this->lang('Voir l\'onglet Aide pour générer ces identifiants.') ?></small>
			</div>
		</div>
		<div class="form-group row">
			<label for="settings-tw-open" class="col-4 col-form-label"><?php echo $this->lang('Ouvrir le stream') ?></label>
			<div class="col-7">
				<select class="form-control" name="settings[open_mode]" id="settings-tw-open">
					<option value="popup"<?php if ($open_mode === 'popup') echo ' selected="selected"' ?>><?php echo $this->lang('Popup intégrée (lecteur Twitch sur le site)') ?></option>
					<option value="newtab"<?php if ($open_mode === 'newtab') echo ' selected="selected"' ?>><?php echo $this->lang('Nouvel onglet (twitch.tv)') ?></option>
				</select>
			</div>
		</div>
		<div class="form-group row">
			<label for="settings-tw-offline" class="col-4 col-form-label"><?php echo $this->lang('Afficher hors ligne') ?></label>
			<div class="col-7">
				<select class="form-control" name="settings[show_offline]" id="settings-tw-offline">
					<option value="1"<?php if ($show_offline === '1') echo ' selected="selected"' ?>><?php echo $this->lang('Toujours afficher (avec badge OFFLINE)') ?></option>
					<option value="0"<?php if ($show_offline === '0') echo ' selected="selected"' ?>><?php echo $this->lang('Masquer quand hors ligne') ?></option>
				</select>
			</div>
		</div>
	</div>
	<div class="tab-pane fade" id="tw-help" role="tabpanel">
		<h6><?php echo $this->lang('Comment générer Client ID & Client Secret') ?></h6>
		<ol class="small">
			<li><?php echo $this->lang('Connecte-toi sur <a href="https://dev.twitch.tv/console" target="_blank" rel="noopener">dev.twitch.tv/console</a> avec ton compte Twitch.') ?></li>
			<li><?php echo $this->lang('<strong>Applications</strong> → <strong>Register Your Application</strong>.') ?></li>
			<li><?php echo $this->lang('Nom : <code>NeoFrag widget</code> (libre)') ?></li>
			<li><?php echo $this->lang('OAuth Redirect URLs : <code>http://localhost</code> (pas utilisé pour ce widget mais champ obligatoire)') ?></li>
			<li><?php echo $this->lang('Catégorie : <code>Website Integration</code>') ?></li>
			<li><?php echo $this->lang('Type de client : <strong>Confidential</strong>') ?></li>
			<li><?php echo $this->lang('Crée → tu obtiens un <strong>Client ID</strong>') ?></li>
			<li><?php echo $this->lang('Clic sur l\'app → <strong>New Secret</strong> → copie le <strong>Client Secret</strong>') ?></li>
			<li><?php echo $this->lang('Colle les deux dans les champs ci-dessus.') ?></li>
		</ol>
		<div class="alert alert-info small mt-3">
			<i class="fas fa-info-circle"></i>
			<?php echo $this->lang('Le widget utilise l\'authentification <code>client_credentials</code> (Server-to-Server) — pas besoin que tes visiteurs se connectent à Twitch. Le token d\'accès est mis en cache 60 jours.') ?>
		</div>
		<h6 class="mt-3"><?php echo $this->lang('Sans identifiants ?') ?></h6>
		<p class="small mb-0"><?php echo $this->lang('Le widget fonctionne quand même : il affichera juste un bouton "Voir sur Twitch" sans le statut live ni les détails du stream.') ?></p>
	</div>
</div>
