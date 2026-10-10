<?php
/* « Mes sanctions » : les sanctions du membre depuis un an (Moderation::mes_sanctions()), la plus récente d'abord. */
$sanctions  = $sanctions ?? [];
$moderation = $this->module('moderation');
?>
<div class="card">
	<div class="card-header"><?php echo icon('fas fa-gavel').' '.$this->lang('Mes sanctions') ?></div>
	<?php if (!$sanctions): ?>
	<div class="card-body text-muted"><?php echo $this->lang('Aucune sanction depuis un an.') ?></div>
	<?php else: ?>
	<ul class="list-group list-group-flush">
		<?php foreach ($sanctions as $s):
			$fin      = !empty($s['expires_at']) ? strtotime((string) $s['expires_at']) : NULL;
			$en_cours = empty($s['revoked_at']) && ($fin === NULL || $fin > time());
		?>
		<li class="list-group-item">
			<div class="d-flex flex-wrap justify-content-between gap-2">
				<strong><?php echo nf_texte($moderation->libelle('sanction', $s['type'])) ?><?php if ($s['scope'] !== 'global'): ?> · <?php echo nf_texte($moderation->libelle('portee', $s['scope'])) ?><?php endif ?></strong>
				<?php if (!empty($s['revoked_at'])): ?>
				<span class="badge text-bg-secondary"><?php echo $this->lang('Levée') ?></span>
				<?php elseif ($en_cours): ?>
				<span class="badge text-bg-danger"><?php echo $this->lang('En cours') ?></span>
				<?php else: ?>
				<span class="badge text-bg-light"><?php echo $this->lang('Terminée') ?></span>
				<?php endif ?>
			</div>
			<div class="small text-muted">
				<?php echo $this->lang('Depuis le %s', nf_date_heure((string) ($s['approved_at'] ?: $s['starts_at'] ?: $s['created_at']))) ?>
				· <?php echo $fin !== NULL ? $this->lang('jusqu’au %s', nf_date_heure((string) $s['expires_at'])) : $this->lang('sans fin') ?>
			</div>
			<?php if ((string) $s['reason'] !== ''): ?>
			<div class="mt-1"><?php echo nl2br(nf_texte($s['reason'])) ?></div>
			<?php endif ?>
		</li>
		<?php endforeach ?>
	</ul>
	<?php endif ?>
</div>
