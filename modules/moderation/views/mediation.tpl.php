<?php
/* Une médiation proposée à celui qui a signalé (Index::_mediation()) : ce qu'elle implique, et sa réponse. */
$report       = $report ?? [];
$conversation = $conversation ?? '';
$csrf         = $csrf ?? '';
$moderation   = $this->module('moderation');
$ouvert       = in_array($report['status'], ['pending', 'reviewed'], TRUE);
?>
<div class="card">
	<div class="card-header"><?php echo icon('fas fa-handshake').' '.$this->lang('Proposition de médiation') ?></div>
	<div class="card-body">
		<p class="text-muted small mb-3">
			<?php echo $this->lang('Ton signalement du %s', nf_date_heure((string) $report['created_at'])) ?> :
			<?php echo nf_texte($moderation->libelle('cible', $report['target_type'])) ?> — <?php echo nf_texte($moderation->libelle('raison', $report['reason'])) ?>
			<?php if (!empty($report['target_username'])): ?>(@<?php echo nf_texte($report['target_username']) ?>)<?php endif ?>
		</p>

		<?php if ($report['mediation_accord'] === 'oui'): ?>
		<div class="alert alert-success mb-0">
			<?php echo $this->lang('Tu as accepté la médiation le %s.', nf_date_heure((string) $report['mediation_reponse_le'])) ?>
			<?php if ($conversation !== ''): ?>
			<a class="alert-link" href="<?php echo url($conversation) ?>"><?php echo $this->lang('Ouvrir la conversation') ?></a>
			<?php endif ?>
		</div>
		<?php elseif ($report['mediation_accord'] === 'non'): ?>
		<div class="alert alert-secondary mb-0"><?php echo $this->lang('Tu as refusé la médiation le %s. Ton signalement reste anonyme et la modération le traite.', nf_date_heure((string) $report['mediation_reponse_le'])) ?></div>
		<?php elseif (!$ouvert): ?>
		<div class="alert alert-secondary mb-0"><?php echo $this->lang('Ton signalement a été traité entre-temps : la médiation n’a plus lieu d’être.') ?></div>
		<?php else: ?>
		<p><?php echo $this->lang('Un modérateur te propose une médiation : une conversation privée entre toi, le membre que tu as signalé et ce modérateur, pour régler le désaccord sans sanction.') ?></p>
		<div class="alert alert-warning">
			<?php echo icon('fas fa-eye').' '.$this->lang('Si tu acceptes, le membre que tu as signalé saura que c’est toi qui l’as signalé.') ?>
		</div>
		<p><?php echo $this->lang('Si tu refuses, rien ne change pour toi : ton signalement reste anonyme et la modération le traite comme d’habitude.') ?></p>
		<form method="post" action="<?php echo url('moderation/mediation/'.(int) $report['id']) ?>" class="d-flex flex-wrap gap-2">
			<input type="hidden" name="_" value="<?php echo nf_texte($csrf) ?>">
			<button type="submit" name="reponse" value="oui" class="btn btn-primary"><?php echo icon('fas fa-handshake').' '.$this->lang('J’accepte la médiation') ?></button>
			<button type="submit" name="reponse" value="non" class="btn btn-outline-secondary"><?php echo icon('fas fa-times').' '.$this->lang('Je refuse') ?></button>
		</form>
		<?php endif ?>
	</div>
</div>
