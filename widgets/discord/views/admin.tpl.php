<ul class="nav nav-pills" id="discord-tabs" role="tablist">
	<li class="nav-item"><a class="nav-link active" id="discord-options-tab" data-toggle="pill" href="#discord-options" role="tab"><?php echo icon('fas fa-cogs').' '.$this->lang('Options') ?></a></li>
	<li class="nav-item"><a class="nav-link" id="discord-help-tab" data-toggle="pill" href="#discord-help" role="tab"><?php echo icon('far fa-life-ring').' '.$this->lang('Aide') ?></a></li>
</ul>
<div class="tab-content border-light" id="discord-tabContent">
	<div class="tab-pane fade show active" id="discord-options" role="tabpanel">
		<div class="form-group row">
			<label for="settings-discord-server" class="col-4 col-form-label"><i class="fab fa-discord"></i> <?php echo $this->lang('ID du serveur') ?></label>
			<div class="col-7">
				<input type="text" class="form-control" name="settings[server_id]" id="settings-discord-server" value="<?php echo htmlspecialchars($server_id) ?>" placeholder="ex: 81384788765712384" autocomplete="off" />
				<small class="form-text text-muted"><?php echo $this->lang('Snowflake ID numérique du serveur (clic-droit sur le nom du serveur dans Discord → "Copier l\'ID du serveur"). Mode dev requis.') ?></small>
			</div>
		</div>
		<div class="form-group row">
			<label for="settings-discord-mode" class="col-4 col-form-label"><?php echo $this->lang('Mode d\'affichage') ?></label>
			<div class="col-7">
				<select class="form-control" name="settings[mode]" id="settings-discord-mode">
					<option value="native"<?php if ($mode === 'native') echo ' selected="selected"' ?>><?php echo $this->lang('Natif (intégré au thème)') ?></option>
					<option value="iframe"<?php if ($mode === 'iframe') echo ' selected="selected"' ?>><?php echo $this->lang('Iframe officiel Discord') ?></option>
				</select>
			</div>
		</div>
		<div class="form-group row">
			<label for="settings-discord-invite" class="col-4 col-form-label"><i class="fas fa-link"></i> <?php echo $this->lang('Lien d\'invitation') ?></label>
			<div class="col-7">
				<input type="text" class="form-control" name="settings[invite]" id="settings-discord-invite" value="<?php echo htmlspecialchars($invite) ?>" placeholder="https://discord.gg/abcdef" autocomplete="off" />
				<small class="form-text text-muted"><?php echo $this->lang('Optionnel. Sinon le lien est récupéré automatiquement depuis Discord.') ?></small>
			</div>
		</div>
		<div class="form-group row">
			<label for="settings-discord-theme" class="col-4 col-form-label"><?php echo $this->lang('Thème (iframe)') ?></label>
			<div class="col-7">
				<select class="form-control" name="settings[theme]" id="settings-discord-theme">
					<option value="dark"<?php if ($theme === 'dark') echo ' selected="selected"' ?>><?php echo $this->lang('Sombre') ?></option>
					<option value="light"<?php if ($theme === 'light') echo ' selected="selected"' ?>><?php echo $this->lang('Clair') ?></option>
				</select>
			</div>
		</div>
		<div class="form-group row">
			<label for="settings-discord-height" class="col-4 col-form-label"><?php echo $this->lang('Hauteur (iframe, px)') ?></label>
			<div class="col-7">
				<input type="number" class="form-control" name="settings[height]" id="settings-discord-height" value="<?php echo (int)$height ?>" min="150" max="1000" />
			</div>
		</div>
	</div>
	<div class="tab-pane fade" id="discord-help" role="tabpanel">
		<h6><?php echo $this->lang('Comment activer le widget Discord ?') ?></h6>
		<ol class="mt-3">
			<li><?php echo $this->lang('Dans votre serveur Discord, cliquez sur <strong>Paramètres du serveur</strong>') ?></li>
			<li><?php echo $this->lang('Allez dans <strong>Widget</strong>') ?></li>
			<li><?php echo $this->lang('Activez <strong>Activer le widget du serveur</strong>') ?></li>
			<li><?php echo $this->lang('Copiez l\'ID du serveur (mode développeur Discord requis : <em>Paramètres utilisateur → Avancés → Mode développeur</em>)') ?></li>
		</ol>
		<p class="mt-3 mb-0"><strong><?php echo $this->lang('Différences entre les modes :') ?></strong></p>
		<ul class="mb-0">
			<li><strong><?php echo $this->lang('Natif') ?></strong> : <?php echo $this->lang('rendu HTML/CSS qui s\'intègre au thème du site (recommandé)') ?></li>
			<li><strong><?php echo $this->lang('Iframe') ?></strong> : <?php echo $this->lang('widget officiel Discord, plus lourd, look fixe') ?></li>
		</ul>
	</div>
</div>
