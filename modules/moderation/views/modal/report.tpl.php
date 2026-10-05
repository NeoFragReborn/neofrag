<?php
/**
 * Modal universel "Signaler ce contenu" — chargé en AJAX via /fr/ajax/moderation/report-modal
 * Reçoit en paramètres : target_type, target_id, url
 */
?>
<div class="modal-header">
	<h5 class="modal-title"><?php echo \icon('fas fa-flag').' '.$this->lang('Signaler ce contenu') ?></h5>
	<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo $this->lang('Fermer') ?>"></button>
</div>
<div class="modal-body">
	<?php
	// Privacy warning si target_type est une conv privée talks (direct/group)
	$is_private_talk = FALSE;
	if ($target_type === 'talks_message' && $target_id)
	{
		$row = $this->db->select('t.type')->from('nf_talks_messages m')
		                ->join('nf_talks t', 't.talk_id = m.talk_id')
		                ->where('m.message_id', (int)$target_id)
		                ->row(FALSE);
		// row(FALSE) : une colonne seule rendait sa valeur, et l'avertissement ne s'affichait jamais.
		$talk_type = is_array($row) ? (string) ($row['type'] ?? '') : '';
		$is_private_talk = in_array($talk_type, ['direct', 'group'], TRUE);
	}
	if ($is_private_talk):
	?>
	<div class="alert alert-warning">
		<strong><?php echo \icon('fas fa-exclamation-triangle').' '.$this->lang('Conversation privée') ?></strong>
		<p class="m-0 small mt-2"><?php echo $this->lang('En signalant ce message, son contenu deviendra accessible aux modérateurs assignés à ta demande. La conversation reste privée pour les autres participants. Confirme avec lucidité.') ?></p>
	</div>
	<?php endif ?>

	<form id="moderation-report-form">
		<input type="hidden" name="target_type" value="<?php echo nf_texte($target_type) ?>" />
		<input type="hidden" name="target_id" value="<?php echo nf_texte($target_id) ?>" />
		<input type="hidden" name="url" value="<?php echo nf_texte($url) ?>" />

		<div class="nf-field">
			<label class="fw-bold"><?php echo $this->lang('Raison du signalement') ?></label>
			<?php
			$reasons = [
				'spam'           => [\icon('fas fa-trash-alt'),    $this->lang('Spam ou contenu indésirable')],
				'harassment'     => [\icon('fas fa-user-times'),   $this->lang('Harcèlement / insultes / discrimination')],
				'illegal'        => [\icon('fas fa-balance-scale'), $this->lang('Contenu illégal (menaces, dox, mineur)')],
				'nsfw'           => [\icon('fas fa-eye-slash'),    $this->lang('Contenu sexuellement explicite (NSFW)')],
				'misinformation' => [\icon('fas fa-times-circle'), $this->lang('Désinformation / fake news')],
				'duplicate'      => [\icon('fas fa-copy'),         $this->lang('Doublon / hors sujet')],
				'other'          => [\icon('fas fa-question-circle'), $this->lang('Autre raison (préciser)')]
			];
			foreach ($reasons as $value => $info):
			?>
			<div class="form-check">
				<input class="form-check-input" type="radio" name="reason" id="reason-<?php echo $value ?>" value="<?php echo $value ?>"<?php echo $value === 'other' ? '' : '' ?> required />
				<label class="form-check-label" for="reason-<?php echo $value ?>">
					<?php echo $info[0].' '.nf_texte($info[1]) ?>
				</label>
			</div>
			<?php endforeach ?>
		</div>

		<?php
		// Détection : ce target_type a-t-il un contexte URL automatique ?
		// Les profils/users + l'absence d'URL fournie = contexte requis manuellement.
		$auto_context = !empty($url) && !in_array($target_type, ['profile', 'user'], TRUE);
		?>
		<div class="nf-field">
			<?php if ($auto_context): ?>
			<label><?php echo $this->lang('Commentaire (optionnel, 500 caractères max)') ?></label>
			<textarea name="comment" class="form-control" rows="3" maxlength="500" placeholder="<?php echo $this->lang('Précise pourquoi ce contenu pose problème (optionnel mais aide les modérateurs).') ?>"></textarea>
			<?php else: ?>
			<label class="fw-bold"><?php echo $this->lang('Contexte (obligatoire)') ?> <span class="text-danger">*</span></label>
			<textarea name="comment" class="form-control" rows="4" required minlength="15" maxlength="500" placeholder="<?php echo $this->lang('Aucune URL automatique pour ce signalement. Décris où trouver le contenu et pourquoi il pose problème. Min 15 caractères.') ?>"></textarea>
			<small class="text-muted"><?php echo \icon('fas fa-info-circle').' '.$this->lang('Sans contexte précis, les modérateurs ne pourront pas examiner ton signalement.') ?></small>
			<?php endif ?>
		</div>

		<small class="text-muted">
			<?php echo \icon('fas fa-info-circle').' '.$this->lang('Tu peux signaler 5 contenus par heure. Les abus de signalement (faux positifs répétés) sont eux-mêmes signalés.') ?>
		</small>
	</form>

	<div id="moderation-report-result" class="mt-3" style="display:none;"></div>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $this->lang('Annuler') ?></button>
	<button type="button" class="btn btn-primary" id="moderation-report-submit"><?php echo \icon('fas fa-flag').' '.$this->lang('Envoyer le signalement') ?></button>
</div>

<script>
(function(){
	var $form = document.getElementById('moderation-report-form');
	var $btn = document.getElementById('moderation-report-submit');
	var $result = document.getElementById('moderation-report-result');
	if (!$btn || !$form) return;

	$btn.addEventListener('click', function(){
		var fd = new FormData($form);
		if (!fd.get('reason')) {
			$result.style.display = 'block';
			$result.innerHTML = '<div class="alert alert-warning"><?php echo addslashes($this->lang('Choisis une raison.')) ?></div>';
			return;
		}
		$btn.disabled = true;
		$btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo addslashes($this->lang('Envoi…')) ?>';

		fetch('<?php echo \url('ajax/moderation/report') ?>', { method: 'POST', body: fd, credentials: 'same-origin' })
			.then(function(r){ return r.json(); })
			.then(function(data){
				$result.style.display = 'block';
				if (data.ok) {
					$result.innerHTML = '<div class="alert alert-success"><i class="fas fa-check"></i> <?php echo addslashes($this->lang('Signalement envoyé. Un modérateur va l\'examiner.')) ?></div>';
					setTimeout(function(){ document.querySelectorAll('.modal.show').forEach(function(m){ bootstrap.Modal.getOrCreateInstance(m).hide(); }); }, 1500);
				} else if (data.error === 'rate_limit_or_dup') {
					$result.innerHTML = '<div class="alert alert-warning"><?php echo addslashes($this->lang('Limite atteinte ou doublon : tu as déjà signalé ce contenu récemment ou dépassé 5 reports/heure.')) ?></div>';
					$btn.disabled = false;
					$btn.innerHTML = '<i class="fas fa-flag"></i> <?php echo addslashes($this->lang('Envoyer le signalement')) ?>';
				} else if (data.error === 'context_required') {
					$result.innerHTML = '<div class="alert alert-warning"><?php echo addslashes($this->lang('Contexte requis : décris où trouver le contenu (15 caractères minimum).')) ?></div>';
					$btn.disabled = false;
					$btn.innerHTML = '<i class="fas fa-flag"></i> <?php echo addslashes($this->lang('Envoyer le signalement')) ?>';
				} else {
					$result.innerHTML = '<div class="alert alert-danger"><?php echo addslashes($this->lang('Erreur lors de l\'envoi.')) ?></div>';
					$btn.disabled = false;
					$btn.innerHTML = '<i class="fas fa-flag"></i> <?php echo addslashes($this->lang('Envoyer le signalement')) ?>';
				}
			})
			.catch(function(){
				$result.style.display = 'block';
				$result.innerHTML = '<div class="alert alert-danger"><?php echo addslashes($this->lang('Erreur réseau.')) ?></div>';
				$btn.disabled = false;
				$btn.innerHTML = '<i class="fas fa-flag"></i> <?php echo addslashes($this->lang('Envoyer le signalement')) ?>';
			});
	});
})();
</script>
