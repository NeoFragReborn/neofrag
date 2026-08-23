<div class="card mb-3">
	<div class="card-body">
		<form method="get" action="<?php echo url('forum/search') ?>" class="form-inline">
			<div class="form-group flex-grow-1 me-2 mb-2">
				<input type="text" name="q" class="form-control w-100" placeholder="<?php echo $this->lang('Rechercher dans le forum (min 3 caractères)') ?>" value="<?php echo htmlspecialchars($query) ?>" />
			</div>
			<div class="form-group me-2 mb-2">
				<select name="forum" class="form-control">
					<option value=""><?php echo $this->lang('Tous les forums') ?></option>
					<?php foreach ($forums as $key => $label): ?>
						<?php if (substr((string)$key, 0, 1) === 'f'): ?>
							<?php $fid = (int)substr($key, 1); ?>
							<option value="<?php echo $fid ?>" <?php echo $forum_id == $fid ? 'selected' : '' ?>><?php echo strip_tags($label) ?></option>
						<?php endif ?>
					<?php endforeach ?>
				</select>
			</div>
			<div class="form-group me-2 mb-2">
				<input type="text" name="author" class="form-control" placeholder="<?php echo $this->lang('Auteur') ?>" value="<?php echo htmlspecialchars((string)$author) ?>" />
			</div>
			<div class="form-group me-2 mb-2">
				<select name="sort" class="form-control">
					<option value="relevance"  <?php echo $sort === 'relevance'  ? 'selected' : '' ?>><?php echo $this->lang('Pertinence') ?></option>
					<option value="date_desc"  <?php echo $sort === 'date_desc'  ? 'selected' : '' ?>><?php echo $this->lang('Date (récent)') ?></option>
					<option value="date_asc"   <?php echo $sort === 'date_asc'   ? 'selected' : '' ?>><?php echo $this->lang('Date (ancien)') ?></option>
				</select>
			</div>
			<button type="submit" class="btn btn-primary mb-2"><?php echo icon('fas fa-search').' '.$this->lang('Rechercher') ?></button>
		</form>
	</div>
</div>

<?php if (!empty($too_short)): ?>
	<div class="alert alert-warning"><?php echo $this->lang('Tape au moins 3 caractères pour chercher.') ?></div>
<?php elseif ($query !== ''): ?>
	<?php if (empty($results)): ?>
		<div class="alert alert-info"><?php echo $this->lang('Aucun résultat pour "%s"', htmlspecialchars($query)) ?></div>
	<?php else: ?>
		<div class="card mb-3">
			<div class="card-header">
				<h5 class="m-0"><?php echo icon('fas fa-search').' '.$this->lang('%d résultat|%d résultats', count($results), count($results)) ?></h5>
			</div>
			<div class="card-body">
				<?php foreach ($results as $r): ?>
					<div class="forum-search-result mb-3 pb-3" style="border-bottom: 1px solid rgba(0,0,0,0.08);">
						<h5 class="m-0">
							<a href="<?php echo url('forum/topic/'.$r['topic_id'].'/'.url_title($r['topic_title']).'#'.$r['message_id']) ?>"><?php echo htmlspecialchars($r['topic_title']) ?></a>
						</h5>
						<div class="text-muted small mb-1">
							<?php echo icon('fas fa-folder').' '.htmlspecialchars($r['forum_title']) ?>
							·
							<?php echo icon('fas fa-user').' '.($r['user_id'] ? $this->user->link($r['user_id'], $r['username']) : '<i>'.$this->lang('Visiteur').'</i>') ?>
							·
							<?php echo icon('far fa-clock').' '.time_span($r['date']) ?>
						</div>
						<div class="forum-search-snippet"><?php echo highlight(strip_tags(str_replace('<br />', ' ', bbcode((string)$r['message']))), preg_split('/\s+/', $query), 240) ?></div>
					</div>
				<?php endforeach ?>
			</div>
		</div>
	<?php endif ?>
<?php endif ?>
