<ul class="nav nav-pills" id="gs-tabs" role="tablist">
	<li class="nav-item"><a class="nav-link active" id="gs-options-tab" data-bs-toggle="pill" href="#gs-options" role="tab"><?php echo icon('fas fa-cogs').' '.$this->lang('Options') ?></a></li>
	<li class="nav-item"><a class="nav-link" id="gs-help-tab" data-bs-toggle="pill" href="#gs-help" role="tab"><?php echo icon('far fa-life-ring').' '.$this->lang('Aide') ?></a></li>
</ul>
<div class="tab-content border-light" id="gs-tabContent">
	<div class="tab-pane fade show active" id="gs-options" role="tabpanel">
		<div class="nf-field row">
			<label for="settings-gs-engine" class="col-12 col-lg-4 col-form-label"><?php echo $this->lang('Type de serveur') ?></label>
			<div class="col-12 col-lg-7">
				<select class="form-select" name="settings[engine]" id="settings-gs-engine">
					<option value="mc-java"<?php if ($engine === 'mc-java') echo ' selected="selected"' ?>>Minecraft Java</option>
					<option value="mc-bedrock"<?php if ($engine === 'mc-bedrock') echo ' selected="selected"' ?>>Minecraft Bedrock</option>
					<option value="source"<?php if ($engine === 'source') echo ' selected="selected"' ?>>Source (CS2, CS:GO, GMod, ARMA, Rust, TF2, L4D2…)</option>
					<option value="goldsource"<?php if ($engine === 'goldsource') echo ' selected="selected"' ?>>GoldSource (CS 1.6, HL, DoD…)</option>
				</select>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-gs-host" class="col-12 col-lg-4 col-form-label"><i class="fas fa-server"></i> <?php echo $this->lang('Adresse') ?></label>
			<div class="col-12 col-lg-7">
				<input type="text" class="form-control" name="settings[host]" id="settings-gs-host" value="<?php echo nf_texte($host) ?>" placeholder="ex: play.example.com OU 88.123.45.67" autocomplete="off" />
				<small class="form-text text-muted"><?php echo $this->lang('Nom de domaine ou adresse IP du serveur (sans le port).') ?></small>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-gs-port" class="col-12 col-lg-4 col-form-label"><?php echo $this->lang('Port') ?></label>
			<div class="col-12 col-lg-3">
				<input type="number" class="form-control" name="settings[port]" id="settings-gs-port" value="<?php echo (int)$port ?: '' ?>" min="1" max="65535" placeholder="auto" />
				<small class="form-text text-muted"><?php echo $this->lang('Auto : 25565 (MC Java), 19132 (Bedrock), 27015 (Source/GoldSrc).') ?></small>
			</div>
		</div>
		<div class="nf-field row">
			<label for="settings-gs-label" class="col-12 col-lg-4 col-form-label"><?php echo $this->lang('Titre personnalisé') ?></label>
			<div class="col-12 col-lg-7">
				<input type="text" class="form-control" name="settings[label]" id="settings-gs-label" value="<?php echo nf_texte($label) ?>" placeholder="<?php echo $this->lang('Optionnel : remplace le nom du serveur') ?>" maxlength="60" />
			</div>
		</div>
	</div>
	<div class="tab-pane fade" id="gs-help" role="tabpanel">
		<h6><?php echo $this->lang('Quel type choisir ?') ?></h6>
		<ul>
			<li><strong>Minecraft Java</strong> — <?php echo $this->lang('Tout serveur Minecraft Java Edition (1.7 → 1.21+, vanilla, Paper, Spigot, Forge, Fabric…). Données via <a href="https://api.mcsrvstat.us" target="_blank" rel="noopener">mcsrvstat.us</a>.') ?></li>
			<li><strong>Minecraft Bedrock</strong> — <?php echo $this->lang('Serveurs Bedrock (Win10/PE/Switch/Xbox).') ?></li>
			<li><strong>Source</strong> — <?php echo $this->lang('Counter-Strike 2, CS:GO, Garry\'s Mod, Team Fortress 2, ARMA 2/3, Rust, Squad, DayZ, Insurgency, L4D2, Killing Floor 2…') ?></li>
			<li><strong>GoldSource</strong> — <?php echo $this->lang('Counter-Strike 1.6, Half-Life 1, Day of Defeat, TFC, Ricochet… (vieux moteur Half-Life)') ?></li>
		</ul>
		<h6 class="mt-3"><?php echo $this->lang('Pré-requis') ?></h6>
		<ul class="mb-0">
			<li><?php echo $this->lang('<strong>Minecraft</strong> : aucun, l\'API <code>mcsrvstat.us</code> ping le serveur depuis l\'extérieur.') ?></li>
			<li><?php echo $this->lang('<strong>Source / GoldSource</strong> : le serveur doit autoriser les requêtes A2S sur son port UDP. Le PHP du site doit pouvoir ouvrir des connexions UDP sortantes.') ?></li>
		</ul>
	</div>
</div>
