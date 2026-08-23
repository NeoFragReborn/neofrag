<ul class="nav nav-pills" id="ts-tabs" role="tablist">
	<li class="nav-item"><a class="nav-link active" id="ts-options-tab" data-bs-toggle="pill" href="#ts-options" role="tab"><?php echo icon('fas fa-cogs').' '.$this->lang('Options') ?></a></li>
	<li class="nav-item"><a class="nav-link" id="ts-help-tab" data-bs-toggle="pill" href="#ts-help" role="tab"><?php echo icon('far fa-life-ring').' '.$this->lang('Aide') ?></a></li>
</ul>
<div class="tab-content border-light" id="ts-tabContent">
	<div class="tab-pane fade show active" id="ts-options" role="tabpanel">
		<div class="form-group row">
			<label for="settings-ts-mode" class="col-4 col-form-label"><?php echo $this->lang('Mode') ?></label>
			<div class="col-7">
				<select class="form-control" name="settings[mode]" id="settings-ts-mode">
					<option value="simple"<?php if ($mode === 'simple') echo ' selected="selected"' ?>><?php echo $this->lang('Simple (carte + bouton rejoindre)') ?></option>
					<option value="tree"<?php if ($mode === 'tree') echo ' selected="selected"' ?>><?php echo $this->lang('Arborescence (channels + clients en temps réel)') ?></option>
				</select>
			</div>
		</div>
		<div class="form-group row">
			<label for="settings-ts-host" class="col-4 col-form-label"><i class="fas fa-server"></i> <?php echo $this->lang('Adresse du serveur') ?></label>
			<div class="col-7">
				<input type="text" class="form-control" name="settings[host]" id="settings-ts-host" value="<?php echo htmlspecialchars($host) ?>" placeholder="ex: ts.example.com" autocomplete="off" />
			</div>
		</div>
		<div class="form-group row">
			<label for="settings-ts-vport" class="col-4 col-form-label"><?php echo $this->lang('Port voix') ?></label>
			<div class="col-3">
				<input type="number" class="form-control" name="settings[voice_port]" id="settings-ts-vport" value="<?php echo (int)$voice_port ?>" min="1" max="65535" />
				<small class="form-text text-muted"><?php echo $this->lang('Défaut : 9987') ?></small>
			</div>
		</div>
		<div class="form-group row">
			<label for="settings-ts-label" class="col-4 col-form-label"><?php echo $this->lang('Titre personnalisé') ?></label>
			<div class="col-7">
				<input type="text" class="form-control" name="settings[label]" id="settings-ts-label" value="<?php echo htmlspecialchars($label) ?>" placeholder="<?php echo $this->lang('Optionnel : remplace le nom du serveur') ?>" maxlength="60" />
			</div>
		</div>

		<div class="alert alert-info mt-3" data-show-when-mode="tree">
			<strong><i class="fas fa-info-circle"></i> <?php echo $this->lang('Mode arborescence — ServerQuery requis') ?></strong>
			<p class="mb-0 mt-2 small"><?php echo $this->lang('Pour afficher channels & clients, le widget se connecte au ServerQuery (TCP, port 10011 par défaut). Vous devez créer un compte query dédié sur votre serveur TS3 (commande TSDNS : <code>serverqueryadd client_login_name=neofrag_viewer client_login_password=…</code>).') ?></p>
		</div>

		<div class="form-group row" data-show-when-mode="tree">
			<label for="settings-ts-qport" class="col-4 col-form-label"><?php echo $this->lang('Port ServerQuery') ?></label>
			<div class="col-3">
				<input type="number" class="form-control" name="settings[query_port]" id="settings-ts-qport" value="<?php echo (int)$query_port ?>" min="1" max="65535" />
				<small class="form-text text-muted"><?php echo $this->lang('Défaut : 10011') ?></small>
			</div>
		</div>
		<div class="form-group row" data-show-when-mode="tree">
			<label for="settings-ts-quser" class="col-4 col-form-label"><?php echo $this->lang('Utilisateur Query') ?></label>
			<div class="col-7">
				<input type="text" class="form-control" name="settings[query_user]" id="settings-ts-quser" value="<?php echo htmlspecialchars($query_user) ?>" autocomplete="off" />
			</div>
		</div>
		<div class="form-group row" data-show-when-mode="tree">
			<label for="settings-ts-qpass" class="col-4 col-form-label"><?php echo $this->lang('Mot de passe Query') ?></label>
			<div class="col-7">
				<input type="password" class="form-control" name="settings[query_pass]" id="settings-ts-qpass" value="<?php echo htmlspecialchars($query_pass) ?>" autocomplete="new-password" />
			</div>
		</div>
	</div>
	<div class="tab-pane fade" id="ts-help" role="tabpanel">
		<h6><?php echo $this->lang('Modes d\'affichage') ?></h6>
		<ul>
			<li><strong><?php echo $this->lang('Simple') ?></strong> — <?php echo $this->lang('Carte basique avec nom du serveur et bouton "Rejoindre" (lien <code>ts3server://</code>). Aucune authentification requise. Recommandé si tu veux juste un raccourci.') ?></li>
			<li><strong><?php echo $this->lang('Arborescence') ?></strong> — <?php echo $this->lang('Affiche les channels et les clients connectés en temps réel via ServerQuery. Nécessite un compte query sur le serveur TS3. Le port 10011 doit être accessible depuis le serveur web.') ?></li>
		</ul>
		<h6 class="mt-3"><?php echo $this->lang('Créer un compte ServerQuery') ?></h6>
		<ol class="mb-0 small">
			<li><?php echo $this->lang('Connectez-vous à votre TS3 en tant qu\'admin via le client.') ?></li>
			<li><?php echo $this->lang('Outils → ServerQuery Login → entrez un nom (ex: <code>neofrag_viewer</code>) et copiez le mot de passe généré.') ?></li>
			<li><?php echo $this->lang('Le port 10011 (TCP) doit être ouvert depuis votre hébergement web. Si non, restez en mode "Simple".') ?></li>
		</ol>
	</div>
</div>

<script>
(function(){
	var modeSel = document.getElementById('settings-ts-mode');
	if (!modeSel) return;
	function update(){
		var mode = modeSel.value;
		document.querySelectorAll('[data-show-when-mode]').forEach(function(el){
			el.style.display = (el.dataset.showWhenMode === mode) ? '' : 'none';
		});
	}
	modeSel.addEventListener('change', update);
	update();
})();
</script>
