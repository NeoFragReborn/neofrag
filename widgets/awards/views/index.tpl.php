<?php
/*
 * Les derniers palmarès : la place (une coupe d'or, d'argent ou de bronze pour le podium), le nom de la compétition
 * en entier, et dessous le lieu et la plateforme.
 *
 * Réécrit le 2026-10-06 (chantier B, Forge « Coulée », qui met ce widget sur son tableau de bord) : la ligne
 * commençait par la PLATEFORME (« PC »), et le nom de la compétition, coupé à vingt caractères par le serveur,
 * finissait à droite derrière une épingle (« Tournoi... »). Le nom se coupe maintenant à la largeur réelle, par
 * le navigateur ; la place du podium se dit en toutes lettres (« 1er », « 2e », « 3e ») au lieu de la seule info-bulle.
 * Rien que des classes de Bootstrap : chaque thème garde son dessin des listes.
 */
?>
<ul class="list-group list-group-flush">
	<?php foreach ($awards as $award): ?>
	<?php
		$rang  = (int) $award['ranking'];
		$coupe = [1 => 'trophy-gold', 2 => 'trophy-silver', 3 => 'trophy-bronze'][$rang] ?? '';
		$place = [1 => $this->lang('1er'), 2 => $this->lang('2e'), 3 => $this->lang('3e')][$rang] ?? $this->lang('%dème', $rang);
		$sur   = $rang == 1 ? $this->lang('%der / %d équipes', $rang, $award['participants']) : $this->lang('%dème / %d équipes', $rang, $award['participants']);
		$infos = array_filter([(string) $award['location'], (string) $award['platform']], 'strlen');
	?>
	<li class="list-group-item d-flex align-items-center gap-3">
		<span class="text-nowrap" data-bs-toggle="tooltip" title="<?php echo $sur ?>"><?php echo $coupe ? icon('fas fa-trophy '.$coupe).' ' : '' ?><strong><?php echo $place ?></strong></span>
		<span class="flex-grow-1 text-truncate">
			<a href="<?php echo url('awards/'.$award['award_id'].'/'.url_title($award['name'])) ?>"><?php echo $award['name'] ?></a>
			<?php if ($infos): ?><small class="d-block text-muted text-truncate"><?php echo implode(' · ', $infos) ?></small><?php endif ?>
		</span>
	</li>
	<?php endforeach ?>
</ul>
