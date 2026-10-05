<?php
/*
 * Les appareils où le membre est connecté (chantier A, étape A3) : une ligne par session ouverte, « Cet appareil »
 * pour celle-ci, « Déconnecter » pour les autres (un lien porteur du jeton de session, confirmé), et « Déconnecter
 * tous les autres appareils ». Sur une démonstration, le compte est partagé : rien ne s'y ferme.
 */
$autres = count(array_filter($appareils, fn ($a) => !$a['actuel']));
?>
<p class="mb-3"><?php echo $this->lang('Chaque appareil où ton compte est ouvert. Un appareil que tu ne reconnais pas ? Déconnecte-le, puis change ton mot de passe.') ?></p>
<ul class="list-group mb-3">
	<?php foreach ($appareils as $appareil): $agent = $appareil['agent'] ?>
		<li class="list-group-item d-flex flex-wrap align-items-center gap-2">
			<span class="me-auto">
				<?php echo $agent['icone'] ? icon($agent['icone']).' ' : icon('fas fa-globe').' ' ?>
				<b><?php echo nf_texte(trim($agent['navigateur'].' '.$agent['version']) ?: (string) $this->lang('Navigateur inconnu')) ?></b>
				<?php if ($agent['systeme'] !== ''): ?> · <?php echo ($agent['icone_systeme'] ? icon($agent['icone_systeme']).' ' : '').nf_texte($agent['systeme']) ?><?php endif ?>
				<br />
				<small class="text-muted">
					<?php if ($appareil['ip'] !== ''): ?><?php echo nf_texte($appareil['ip']) ?> · <?php endif ?>
					<?php echo $this->lang('Dernière activité :').' '.time_span($appareil['activite']) ?>
				</small>
			</span>
			<?php if ($appareil['actuel']): ?>
				<span class="badge text-bg-success"><?php echo icon('fas fa-check').' '.$this->lang('Cet appareil') ?></span>
			<?php elseif (!$demo): ?>
				<a class="btn btn-sm btn-outline-danger" href="<?php echo $appareil['fermer'] ?>" data-confirm="<?php echo nf_texte($this->lang('Déconnecter cet appareil ?')) ?>"><?php echo icon('fas fa-right-from-bracket').' '.$this->lang('Déconnecter') ?></a>
			<?php endif ?>
		</li>
	<?php endforeach ?>
</ul>
<?php if ($autres && !$demo): ?>
	<a class="btn btn-outline-danger" href="<?php echo $fermer_autres ?>" data-confirm="<?php echo nf_texte($this->lang('Déconnecter tous tes autres appareils ?')) ?>"><?php echo icon('fas fa-power-off').' '.$this->lang('Déconnecter tous les autres appareils') ?></a>
<?php elseif ($autres && $demo): ?>
	<p class="text-muted mb-0"><?php echo $this->lang('Sur la démonstration, le compte est partagé : ses appareils ne se déconnectent pas d’ici.') ?></p>
<?php endif ?>
