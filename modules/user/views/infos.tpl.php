<div class="row">
	<div class="col-12 col-sm-4">
		<b><?php echo $this->lang('Inscrit depuis le') ?></b><br />
		<?php echo $user->registration_date ?>
	</div>
	<div class="col-12 col-sm-4">
		<b><?php echo $this->lang('Dernière activité') ?></b><br />
		<?php echo $user->last_activity_date ?>
	</div>
	<div class="col-12 col-sm-4">
		<b><?php echo $this->lang('Groupes') ?></b><br />
		<?php echo $user->groups() ?>
	</div>
</div>

<?php
/**
 * Champs définis par l'administrateur et marqués PUBLICS.
 *
 * Rien ne s'affiche si aucun champ n'est public, ou si ce membre n'a rien saisi : une rubrique vide
 * sur une fiche publique laisse croire à une information manquante.
 *
 * `get_public_values()` filtre déjà sur `public` ET sur les valeurs non vides — le gabarit ne
 * décide pas de ce qui est publiable, il affiche ce que le modèle a autorisé.
 */
$nf_champs = $this->module('user')->model('fields')->get_public_values($user->id);

if ($nf_champs):
?>
<hr />
<div class="row">
<?php foreach ($nf_champs as $nf_champ): ?>
	<div class="col-12 col-sm-4 mb-2">
		<b><?php echo htmlspecialchars($nf_champ['label']) ?></b><br />
		<?php
		// Le type `url` est le seul rendu en lien, et seulement si l'adresse en est vraiment une :
		// une valeur saisie librement ne devient pas cliquable sur la seule foi du type choisi.
		if ($nf_champ['type'] === 'url' && filter_var($nf_champ['value'], FILTER_VALIDATE_URL)):
		?>
		<a href="<?php echo htmlspecialchars($nf_champ['value'], ENT_QUOTES) ?>" target="_blank" rel="noopener nofollow"><?php echo htmlspecialchars($nf_champ['value']) ?></a>
		<?php elseif ($nf_champ['type'] === 'checkbox'): ?>
		<i class="fas fa-check text-success"></i>
		<?php else: ?>
		<?php echo nl2br(htmlspecialchars($nf_champ['value'])) ?>
		<?php endif ?>
	</div>
<?php endforeach ?>
</div>
<?php endif ?>
