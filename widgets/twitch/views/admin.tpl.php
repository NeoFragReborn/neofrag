<ul class="nav nav-pills" id="tw-tabs" role="tablist">
	<li class="nav-item"><a class="nav-link active" id="tw-options-tab" data-bs-toggle="pill" href="#tw-options" role="tab"><?php echo icon('fas fa-cogs').' '.$this->lang('Options') ?></a></li>
	<li class="nav-item"><a class="nav-link" id="tw-help-tab" data-bs-toggle="pill" href="#tw-help" role="tab"><?php echo icon('far fa-life-ring').' '.$this->lang('Aide') ?></a></li>
</ul>
<div class="tab-content border-light" id="tw-tabContent">
	<div class="tab-pane fade show active" id="tw-options" role="tabpanel">
		<div class="nf-field row">
			<label for="settings-tw-channels" class="col-12 col-lg-4 col-form-label"><i class="fas fa-broadcast-tower"></i> <?php echo $this->lang('Chaînes') ?></label>
			<div class="col-12 col-lg-7">
				<textarea class="form-control" name="settings[channels]" id="settings-tw-channels" rows="4" placeholder="twitch:ninja&#10;youtube:UCxxxxxxxx" autocomplete="off"><?php echo htmlspecialchars($channels) ?></textarea>
				<small class="form-text text-muted"><?php echo $this->lang('Une par ligne, au format <code>provider:chaîne</code> (ex. <code>twitch:ninja</code>, <code>youtube:UC...</code>). Provider omis = Twitch. Max 12.') ?></small>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-tw-cid" class="col-12 col-lg-4 col-form-label"><i class="fab fa-twitch"></i> <?php echo $this->lang('Client ID') ?></label>
			<div class="col-12 col-lg-7">
				<input type="text" class="form-control" name="settings[client_id]" id="settings-tw-cid" value="<?php echo htmlspecialchars($client_id) ?>" autocomplete="off" />
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-tw-csecret" class="col-12 col-lg-4 col-form-label"><i class="fab fa-twitch"></i> <?php echo $this->lang('Client Secret') ?></label>
			<div class="col-12 col-lg-7">
				<input type="password" class="form-control" name="settings[client_secret]" id="settings-tw-csecret" value="<?php echo htmlspecialchars($client_secret) ?>" autocomplete="new-password" />
				<small class="form-text text-muted"><?php echo $this->lang('Identifiants Twitch (onglet Aide). Requis pour le statut des chaînes Twitch.') ?></small>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-tw-ytkey" class="col-12 col-lg-4 col-form-label"><i class="fab fa-youtube"></i> <?php echo $this->lang('Clé API YouTube') ?></label>
			<div class="col-12 col-lg-7">
				<input type="password" class="form-control" name="settings[api_key]" id="settings-tw-ytkey" value="<?php echo htmlspecialchars($api_key) ?>" autocomplete="new-password" />
				<small class="form-text text-muted"><?php echo $this->lang('Clé YouTube Data API v3 (Google Cloud). Requise pour le statut des chaînes YouTube. Quota strict.') ?></small>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-tw-open" class="col-12 col-lg-4 col-form-label"><?php echo $this->lang('Ouvrir le stream') ?></label>
			<div class="col-12 col-lg-7">
				<select class="form-select" name="settings[open_mode]" id="settings-tw-open">
					<option value="popup"<?php if ($open_mode === 'popup') echo ' selected="selected"' ?>><?php echo $this->lang('Popup intégrée (lecteur sur le site)') ?></option>
					<option value="newtab"<?php if ($open_mode === 'newtab') echo ' selected="selected"' ?>><?php echo $this->lang('Nouvel onglet (chaîne externe)') ?></option>
				</select>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-tw-offline" class="col-12 col-lg-4 col-form-label"><?php echo $this->lang('Afficher hors ligne') ?></label>
			<div class="col-12 col-lg-7">
				<select class="form-select" name="settings[show_offline]" id="settings-tw-offline">
					<option value="1"<?php if ($show_offline === '1') echo ' selected="selected"' ?>><?php echo $this->lang('Toujours afficher (avec badge OFFLINE)') ?></option>
					<option value="0"<?php if ($show_offline === '0') echo ' selected="selected"' ?>><?php echo $this->lang('Masquer quand hors ligne') ?></option>
				</select>
			</div>
		</div>
	</div>
	<div class="tab-pane fade" id="tw-help" role="tabpanel">
		<h6><i class="fab fa-twitch"></i> <?php echo $this->lang('Identifiants Twitch (Client ID & Secret)') ?></h6>
		<ol class="small">
			<li><?php echo $this->lang('Connecte-toi sur <a href="https://dev.twitch.tv/console" target="_blank" rel="noopener">dev.twitch.tv/console</a>.') ?></li>
			<li><?php echo $this->lang('<strong>Applications</strong> → <strong>Register Your Application</strong> (type <strong>Confidential</strong>, redirect <code>http://localhost</code>).') ?></li>
			<li><?php echo $this->lang('Copie le <strong>Client ID</strong> et génère un <strong>Client Secret</strong> ; colle-les ci-dessus.') ?></li>
		</ol>
		<p class="small"><?php echo $this->lang('Auth <code>client_credentials</code> (server-to-server) — tes visiteurs n\'ont pas à se connecter ; le token est mis en cache.') ?></p>
		<h6 class="mt-3"><i class="fab fa-youtube"></i> <?php echo $this->lang('Clé API YouTube') ?></h6>
		<ol class="small">
			<li><?php echo $this->lang('Sur <a href="https://console.cloud.google.com/" target="_blank" rel="noopener">Google Cloud Console</a>, active <strong>YouTube Data API v3</strong>.') ?></li>
			<li><?php echo $this->lang('Crée une <strong>clé API</strong> (Identifiants) et colle-la ci-dessus. Pour la chaîne, utilise son <code>channelId</code> (UC...).') ?></li>
		</ol>
		<div class="alert alert-info small mt-2"><i class="fas fa-info-circle"></i> <?php echo $this->lang('Sans identifiants, les chaînes restent listées avec un statut « indisponible » et un lien direct.') ?></div>
	</div>
</div>
