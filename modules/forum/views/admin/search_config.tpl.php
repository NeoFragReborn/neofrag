<?php
/**
 * Les deux compteurs passent par la GRILLE DE STATISTIQUES partagée (`admin_stats`, rendue en
 * `.nf-stats-grid`) au lieu de deux `<div class="card">` roulées à la main : mêmes tuiles, mêmes
 * espacements et mêmes couleurs que partout ailleurs dans l'administration.
 */
?>
<?php echo $compteurs ?>

<form action="<?php echo url($this->url->request) ?>" method="post">
	<div class="alert alert-info">
		<?php echo icon('fas fa-info-circle').' '.$this->lang('Les indexes FULLTEXT sont reconstruits via OPTIMIZE TABLE. Cette opération peut prendre quelques minutes selon la taille des tables.') ?>
	</div>

	<button type="submit" name="reindex" value="1" class="btn btn-warning"
			data-confirm="<?php echo htmlspecialchars($this->lang('Lancer la reconstruction des indexes FULLTEXT ? L\'opération peut prendre plusieurs minutes et bloque temporairement les recherches.'), ENT_QUOTES) ?>"
			data-confirm-title="<?php echo htmlspecialchars($this->lang('Reconstruire les indexes FULLTEXT'), ENT_QUOTES) ?>"
			data-confirm-style="warning"
			data-confirm-icon="fas fa-sync-alt"
			data-confirm-ok="<?php echo htmlspecialchars($this->lang('Lancer'), ENT_QUOTES) ?>"><?php echo icon('fas fa-sync-alt').' '.$this->lang('Reconstruire les indexes FULLTEXT') ?></button>

	<a href="<?php echo url('forum/search') ?>" class="btn btn-light"><?php echo icon('fas fa-search').' '.$this->lang('Tester la recherche') ?></a>
</form>
