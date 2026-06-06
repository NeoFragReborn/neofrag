<?php if ($status == 1): ?>
	<div class="alert alert-info"><?php echo $this->lang('Candidature <b>en cours d\'éxamination</b> par les recruteurs.') ?></div>
<?php elseif ($status == 2): ?>
	<div class="alert alert-success"><?php echo $this->lang('Candidature <b>acceptée</b> !') ?></div>
<?php else: ?>
	<div class="alert alert-danger"><?php echo $this->lang('Candidature <b>refusée</b> !') ?></div>
<?php endif ?>
<?php if ($reply_text): ?>
	<h3><?php echo $this->lang('Réponse des recruteurs') ?></h3>
	<?php echo $reply_text ?>
<?php endif ?>
