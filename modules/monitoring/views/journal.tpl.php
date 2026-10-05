<?php
/*
 * Le journal des erreurs, regroupé : un filtre (gravité, période, recherche — une référence d'erreur
 * se cherche ici), puis une ligne par message, la plus récente d'abord, avec sa dernière occurrence
 * complète à déplier. Ce qui est sensible est déjà masqué par le contrôleur.
 */
$groupes   = $groupes ?? [];
$gravites  = $gravites ?? [];
$etiquettes = $etiquettes ?? [];
$comptes   = $comptes ?? [];
$filtre    = $filtre ?? '';
$periode   = $periode ?? '7j';
$recherche = $recherche ?? '';
$periodes  = $periodes ?? [];
$couleurs  = ['fatale' => 'danger', 'erreur' => 'danger', 'avertissement' => 'warning', 'information' => 'secondary'];
?>
<form method="get" action="<?php echo url('admin/monitoring/journal') ?>" class="d-flex flex-wrap gap-2 align-items-end mb-3">
	<div>
		<label class="form-label small mb-1" for="journal-gravite"><?php echo $this->lang('Gravité') ?></label>
		<select class="form-select form-select-sm" id="journal-gravite" name="gravite">
			<option value=""><?php echo $this->lang('Toutes') ?></option>
			<?php foreach ($gravites as $cle => $libelle): ?>
			<option value="<?php echo $cle ?>"<?php echo $filtre === $cle ? ' selected' : '' ?>><?php echo $libelle ?> (<?php echo (int) ($comptes[$cle] ?? 0) ?>)</option>
			<?php endforeach ?>
		</select>
	</div>
	<div>
		<label class="form-label small mb-1" for="journal-periode"><?php echo $this->lang('Période') ?></label>
		<select class="form-select form-select-sm" id="journal-periode" name="periode">
			<?php foreach ($periodes as $cle => $libelle): ?>
			<option value="<?php echo $cle ?>"<?php echo $periode === $cle ? ' selected' : '' ?>><?php echo $libelle ?></option>
			<?php endforeach ?>
		</select>
	</div>
	<div class="flex-grow-1" style="min-width:12rem">
		<label class="form-label small mb-1" for="journal-recherche"><?php echo $this->lang('Rechercher (texte ou référence)') ?></label>
		<input class="form-control form-control-sm" type="search" id="journal-recherche" name="q" value="<?php echo nf_texte($recherche) ?>" maxlength="100">
	</div>
	<button class="btn btn-primary btn-sm" type="submit"><?php echo icon('fas fa-filter').' '.$this->lang('Filtrer') ?></button>
</form>

<?php if (!$groupes): ?>
<?php echo $vide ?? '' ?>
<?php else: ?>
<div class="list-group list-group-flush border rounded">
	<?php foreach ($groupes as $g): ?>
	<details class="list-group-item">
		<summary class="d-flex flex-wrap gap-2 align-items-start" style="cursor:pointer;list-style:none">
			<span class="badge text-bg-<?php echo $couleurs[$g['gravite']] ?? 'secondary' ?>"><?php echo $etiquettes[$g['gravite']] ?? '' ?></span>
			<span class="flex-grow-1 small" style="min-width:0;overflow-wrap:anywhere"><?php echo nf_texte($g['message']) ?></span>
			<span class="small text-body-secondary text-nowrap"><?php echo $this->lang('%d×', $g['nombre']) ?> · <?php echo $g['dernier'] ?></span>
		</summary>
		<?php if ($g['references']): ?>
		<p class="small mt-2 mb-1"><?php echo $this->lang('Références') ?> : <?php foreach ($g['references'] as $r): ?><code class="me-1"><?php echo $r ?></code><?php endforeach ?></p>
		<?php endif ?>
		<pre class="small bg-body-tertiary border rounded p-2 mt-2 mb-1" style="white-space:pre-wrap;overflow-wrap:anywhere;max-height:24rem;overflow:auto"><?php echo htmlspecialchars($g['exemple']) /* codage: la ligne du journal, telle quelle */ ?></pre>
	</details>
	<?php endforeach ?>
</div>
<?php endif ?>
