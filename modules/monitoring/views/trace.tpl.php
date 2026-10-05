<?php
/*
 * La trace des pages : une recherche sur l'adresse, puis une ligne par page servie, la plus récente
 * d'abord — ses requêtes à la base, leur durée, la mémoire —, avec sa trace complète à déplier. Ce qui
 * est sensible est déjà masqué par le contrôleur.
 */
$pages     = $pages ?? [];
$recherche = $recherche ?? '';
?>
<form method="get" action="<?php echo url('admin/monitoring/trace') ?>" class="d-flex flex-wrap gap-2 align-items-end mb-3">
	<div class="flex-grow-1" style="min-width:12rem">
		<label class="form-label small mb-1" for="trace-recherche"><?php echo $this->lang('Rechercher une adresse') ?></label>
		<input class="form-control form-control-sm" type="search" id="trace-recherche" name="q" value="<?php echo nf_texte($recherche) ?>" maxlength="100">
	</div>
	<button class="btn btn-primary btn-sm" type="submit"><?php echo icon('fas fa-filter').' '.$this->lang('Filtrer') ?></button>
</form>

<?php if (!$pages): ?>
<?php echo $vide ?? '' ?>
<?php else: ?>
<div class="list-group list-group-flush border rounded">
	<?php foreach ($pages as $p): ?>
	<details class="list-group-item">
		<summary class="d-flex flex-wrap gap-2 align-items-start" style="cursor:pointer;list-style:none">
			<code class="flex-grow-1 small" style="min-width:0;overflow-wrap:anywhere"><?php echo nf_texte($p['titre']) ?></code>
			<span class="small text-body-secondary text-nowrap"><?php echo $this->lang('%d requête|%d requêtes', $p['requetes'], $p['requetes']) ?> · <?php echo $this->lang('%s ms', sprintf('%.1f', $p['duree'])) ?><?php echo $p['memoire'] !== '' ? ' · '.nf_texte($p['memoire']) : '' ?><?php echo $p['date'] !== '' ? ' · '.$p['date'] : '' ?></span>
		</summary>
		<pre class="small bg-body-tertiary border rounded p-2 mt-2 mb-1" style="white-space:pre-wrap;overflow-wrap:anywhere;max-height:32rem;overflow:auto"><?php echo htmlspecialchars($p['texte']) /* codage: la ligne du journal, telle quelle */ ?></pre>
	</details>
	<?php endforeach ?>
</div>
<?php endif ?>
