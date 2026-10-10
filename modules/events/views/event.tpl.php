<?php $link = url('events/'.$event_id.'/'.url_title($title)) ?>
<?php if ($image_id): ?>
	<a href="<?php echo $link ?>"><img src="<?php echo NeoFrag()->model2('file', $image_id)->path() ?>" class="img-fluid" alt="" /></a>
<?php endif ?>
<?php if (!empty($match['opponent']))://Matches ?>
<?php
	// L'équipe, le score, l'adversaire, sur une ligne. La colonne de l'adversaire était en `col-xs-…`, du Bootstrap 3 qui
	// n'existe plus : elle passait sous l'équipe (2026-10-10, quand la fiche d'un match a de nouveau montré son match).
	// L'équipe d'abord : display_scores() pose la couleur du score.
	$equipe       = $match['team']['title'].' '.$this->model('matches')->display_scores($match['scores'], $color);
	$couleur      = $color;
	$icone_equipe = NeoFrag()->model2('file', $match['team']['icon_id'])->path();
	$adversaire   = $this->model('matches')->display_scores($match['scores'], $color, TRUE).' '.$match['opponent']['title'];

	if ($match['opponent']['country'])
	{
		$adversaire .= '<img src="'.url('images/flags/'.$match['opponent']['country'].'.png').'" data-bs-toggle="tooltip" title="'.country_name($match['opponent']['country']).'" class="ms-2" alt="" />';
	}

	// Le site de l'adversaire : une adresse sûre (pas de `javascript:`), échappée (audit du 2026-10-09).
	if ($match['opponent']['website'] && nf_url_sure((string) $match['opponent']['website']))
	{
		$adversaire = '<a href="'.nf_texte($match['opponent']['website']).'" target="_blank" rel="noopener noreferrer">'.$adversaire.'</a>';
	}
?>
<div class="card-body">
	<div class="d-flex align-items-center justify-content-center gap-3">
		<div class="flex-fill text-end" style="flex-basis: 0">
			<h5 class="m-0 d-inline-flex flex-wrap align-items-center justify-content-end gap-2">
				<a href="<?php echo url('events/team/'.$match['team_id'].'/'.$match['team']['name']) ?>"><?php echo $equipe ?></a>
				<?php if ($icone_equipe): ?><img src="<?php echo $icone_equipe ?>" style="max-height: 48px" alt="" /><?php endif ?>
			</h5>
		</div>
		<div class="flex-shrink-0 text-center">
			<h3 class="m-0<?php echo $match['scores'] ? ' '.$couleur : '' ?>"><?php echo $match['scores'] ? $match['scores'][0].':'.$match['scores'][1] : 'VS' ?></h3>
		</div>
		<div class="flex-fill text-start" style="flex-basis: 0">
			<h5 class="m-0 d-inline-flex flex-wrap align-items-center gap-2">
				<?php if ($match['opponent']['image_id']): ?><img src="<?php echo NeoFrag()->model2('file', $match['opponent']['image_id'])->path() ?>" style="max-height: 48px" alt="" /><?php endif ?>
				<span><?php echo $adversaire ?></span>
			</h5>
		</div>
	</div>
</div>
<?php endif ?>
<?php if (!empty($rounds)): ?>
<div class="card-body">
	<?php if ($mode): ?>
		<p class="<?php echo count($rounds) > 1 ? 'float-end' : 'text-center' ?>"><?php echo icon('fas fa-cog').$this->lang('Mode : %s', $mode) ?></p>
	<?php endif ?>
	<?php if (count($rounds) > 1): ?>
		<p class="fw-bold"><?php echo $this->lang('Détail des manches') ?></p>
		<?php for ($i = 0; $i < count($rounds); $i++) { ?>
			<?php /* Une manche, une carte sur une ligne : au téléphone, le groupe de trois cartes s'empilait (2026-10-10). */ ?>
			<div class="card mb-2">
				<div class="d-flex align-items-stretch text-center">
				<div class="flex-fill d-flex align-items-center justify-content-center p-2" style="flex-basis: 0">
					<h6 class="m-0"><?php echo ($match['team']['title'] ?? '').' '.$this->model('matches')->display_scores([$rounds[$i]['score1'], $rounds[$i]['score2']], $color) ?></h6>
				</div>
				<div class="p-2 text-center border-start border-end" style="min-width: 7.5rem">
					<span class="badge text-bg-dark"><?php echo $this->lang('Manche %d', $i + 1) ?></span>
					<h4 class="my-2"><?php echo $rounds[$i]['score1'] ?>:<?php echo $rounds[$i]['score2'] ?></h4>
					<a href="#"><?php echo $this->label($rounds[$i]['title'], 'far fa-map')->popover_if($rounds[$i]['image_id'], function($id){ return utf8_htmlentities('<img src="'.NeoFrag()->model2('file', $id)->path().'" class="img-fluid" alt="" />') /* codage: du HTML posé dans un attribut — le décoder réveillerait les balises qu’il cite */; })?></a>
				</div>
				<div class="flex-fill d-flex align-items-center justify-content-center p-2" style="flex-basis: 0">
					<h6 class="m-0"><?php echo $this->model('matches')->display_scores([$rounds[$i]['score1'], $rounds[$i]['score2']], $color, TRUE).' '.($match['opponent']['title'] ?? '') ?></h6>
				</div>
				</div>
			</div>
		<?php } ?>
	<?php endif ?>
</div>
<?php endif ?>
<?php if ($description): ?>
<div class="card-body">
	<?php echo bbcode($description) ?>
</div>
<?php endif ?>
<?php
if (!empty($show_details) && $list_participants && $private_description):
	foreach ($list_participants as $participant):
		if ($this->access->effective_admin() || ($participant['user_id'] == $this->user->id)): ?>
			<div class="card-body">
				<?php echo bbcode($private_description) ?>
			</div>
			<?php
			break;
		endif;
	endforeach;
endif;
?>
<?php if ($webtv || $website): ?>
<div class="card-body">
	<ul class="list-inline m-0">
		<?php // Des adresses saisies : sûres (pas de `javascript:`) et échappées (audit du 2026-10-09). ?>
		<?php echo $webtv && nf_url_sure((string) $webtv) ? '<li class="list-inline-item"><a href="'.nf_texte($webtv).'" target="_blank" rel="noopener noreferrer">'.icon('fab fa-twitch').' '.$this->lang('Retransmission sur Twitch').'</a></li>' : '' ?>
		<?php echo $website && nf_url_sure((string) $website) ? '<li class="list-inline-item"><a href="'.nf_texte($website).'" target="_blank" rel="noopener noreferrer">'.icon('far fa-newspaper').' '.$this->lang('On en parle ici').'</a></li>' : '' ?>
	</ul>
</div>
<?php endif ?>
<div class="card-footer">
	<div class="float-end">
		<ul class="list-inline m-0">
			<li class="list-inline-item"><a href="<?php echo $link.'#participants' ?>"><?php echo icon('fas fa-users').' '.$participants ?></a></li>
			<?php if (($comments = $this->module('comments')) && $comments->is_enabled()): ?>
				<li class="list-inline-item"><?php echo $comments->link('events', $event_id, 'events/'.$event_id.'/'.url_title($title)) ?></li>
			<?php endif ?>
		</ul>
	</div>
	<ul class="list-inline m-0">
		<li class="list-inline-item"><?php echo $this->label($type['title'], $type['icon'], $type['color'], 'events/type/'.$type['type_id'].'/'.url_title($type['title'])) ?></li>
		<li class="list-inline-item"><?php echo icon('far fa-clock') ?> <?php echo '<span data-bs-toggle="tooltip" title="'.timetostr(NeoFrag()->lang('l j F Y, H:i'), $date).'">'.timetostr(NeoFrag()->lang('d/m/Y H:i'), $date).'</span>'.($date_end ? '&nbsp;&nbsp;<span data-bs-toggle="tooltip" title="'.$this->lang('Durée').'"><i>'.icon('fas fa-hourglass-end').(ceil((strtotime($date_end) - strtotime($date)) / ( 60 * 60 ))).'h</i></span>' : '') ?></li>
		<?php if (strtotime($date) > time()): ?>
		<li class="list-inline-item"><?php echo icon('far fa-hourglass-half') ?> <span class="nf-countdown" data-countdown="<?php echo (int)strtotime($date) ?>"></span></li>
		<?php endif ?>
		<?php
		if (!empty($show_details) && $list_participants && $location):
			foreach ($list_participants as $participant):
				if ($this->access->effective_admin() || ($participant['user_id'] == $this->user->id)): ?>
					<?php if (($location = explode("\n", $location))) echo '<li class="list-inline-item">'.$this->label(current($location), 'fas fa-map-marker-alt')->popover_if(count($location) > 1, implode('<br>', $location)).'</li>' ?>
					<?php
					break;
				endif;
			endforeach;
		endif;
		?>
	</ul>
</div>
