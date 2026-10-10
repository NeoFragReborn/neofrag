<?php
/* Le formulaire qui prononce une sanction : depuis un signalement (sa fiche), ou directement depuis l'historique d'un
   membre (2026-10-09 : on ne sanctionnait que depuis un signalement). Moderation::sanctionner() le reçoit. */
$action = $action ?? '#';
$csrf   = $csrf ?? '';
?>
<form method="post" action="<?php echo $action ?>"><input type="hidden" name="_" value="<?php echo $csrf ?>">
	<div class="row">
		<div class="col-md-6">
			<label><?php echo $this->lang('Type de sanction') ?></label>
			<select name="type" class="form-select" required>
				<option value=""><?php echo $this->lang('— Choisir —') ?></option>
				<?php
				$types = [
					'warning'           => $this->lang('Avertissement'),
					'mute'              => $this->lang('Mute (empêche de poster)'),
					'ban_temp'          => $this->lang('Ban temporaire'),
					'ban_perm'          => $this->lang('Ban définitif'),
					'restrict_upload'   => $this->lang('Restreindre l\'upload de fichiers'),
					'restrict_links'    => $this->lang('Restreindre les liens'),
					'restrict_avatar'   => $this->lang('Restreindre la modif d\'avatar'),
					'restrict_signature' => $this->lang('Restreindre la modif de signature'),
					'restrict_comment'  => $this->lang('Restreindre les commentaires'),
					'shadow_ban'        => $this->lang('Shadow ban (silencieux, sans notif)')
				];
				foreach ($types as $val => $label): ?>
				<option value="<?php echo $val ?>"><?php echo nf_texte($label) ?></option>
				<?php endforeach ?>
			</select>
		</div>
		<div class="col-md-3">
			<label><?php echo $this->lang('Scope') ?></label>
			<?php /* Les portées que la carte des sanctions applique (« wiki » n'en était pas ; profil, tickets et
			         recrutement manquaient). Seuls le muet et les bannissements en ont une (audit du 2026-10-09). */ ?>
			<select name="scope" class="form-select">
				<?php foreach (['global', 'forum', 'talks', 'comments', 'gallery', 'guestbook', 'profile', 'bugtracker', 'recruits'] as $portee): ?>
				<option value="<?php echo $portee ?>"><?php echo nf_texte($this->module('moderation')->libelle('portee', $portee)) ?></option>
				<?php endforeach ?>
			</select>
			<small class="text-muted"><?php echo $this->lang('Pour un muet ou un ban ; le reste vaut pour tout le site.') ?></small>
		</div>
		<div class="col-md-3">
			<label><?php echo $this->lang('Durée (heures)') ?></label>
			<input type="number" class="form-control" name="duration_seconds_h" min="0" step="1" placeholder="<?php echo $this->lang('Vide = par défaut') ?>" />
			<small class="text-muted"><?php echo $this->lang('Vide : la durée des réglages pour un muet ou un ban temporaire, sans fin pour le reste ; un ban définitif n’en a pas.') ?></small>
		</div>
	</div>
	<div class="nf-field mt-3">
		<label id="reason_label"><?php echo $this->lang('Raison (visible par le user sanctionné)') ?></label>
		<textarea name="reason" id="reason_textarea" class="form-control" rows="3" required maxlength="1000" placeholder="<?php echo $this->lang('Explique la sanction. Tu peux référencer le commentaire du reporter.') ?>"></textarea>
	</div>
	<div class="form-check mt-2">
		<input type="checkbox" name="notify_user" id="notify_user" value="1" checked class="form-check-input" />
		<label for="notify_user" class="form-check-label" id="notify_user_label"><?php echo $this->lang('Notifier l\'utilisateur (email + in-site)') ?></label>
		<small class="d-block text-muted" id="shadow_ban_hint" style="display:none !important;"><i class="fas fa-user-secret"></i> <?php echo $this->lang('Shadow ban : pas de notif par nature (silencieux côté user, raison écrite obligatoire pour le staff).') ?></small>
	</div>
	<input type="hidden" name="duration_seconds" id="duration_seconds_hidden" value="" />
	<script>
		(function(){
			var hourInput = document.querySelector('input[name="duration_seconds_h"]');
			var hidden    = document.getElementById('duration_seconds_hidden');
			hourInput.addEventListener('input', function(){
				hidden.value = this.value ? (parseInt(this.value, 10) * 3600) : '';
			});

			var typeSel  = document.querySelector('select[name="type"]');
			var notifyCb = document.getElementById('notify_user');
			var notifyLb = document.getElementById('notify_user_label');
			var hint     = document.getElementById('shadow_ban_hint');
			var reasonLb = document.getElementById('reason_label');
			var reasonTa = document.getElementById('reason_textarea');
			var REASON_PUBLIC = <?php echo json_encode((string)$this->lang('Raison (visible par le user sanctionné)')) ?>;
			var REASON_INTERN = <?php echo json_encode((string)$this->lang('Raison interne (visible uniquement par le staff)')) ?>;
			var PH_PUBLIC = <?php echo json_encode((string)$this->lang('Explique la sanction. Tu peux référencer le commentaire du reporter.')) ?>;
			var PH_INTERN = <?php echo json_encode((string)$this->lang('Justification interne pour le staff. Le user ne verra jamais ce texte.')) ?>;
			typeSel.addEventListener('change', function(){
				var isShadow = (this.value === 'shadow_ban');
				notifyCb.checked  = !isShadow;
				notifyCb.disabled = isShadow;
				notifyLb.classList.toggle('text-muted', isShadow);
				hint.style.cssText = isShadow ? '' : 'display:none !important;';
				reasonLb.textContent = isShadow ? REASON_INTERN : REASON_PUBLIC;
				reasonTa.placeholder = isShadow ? PH_INTERN : PH_PUBLIC;
			});
		})();
	</script>
	<div class="mt-3">
		<button type="submit" class="btn btn-primary"><i class="fas fa-gavel"></i> <?php echo $this->lang('Appliquer la sanction') ?></button>
	</div>
</form>
