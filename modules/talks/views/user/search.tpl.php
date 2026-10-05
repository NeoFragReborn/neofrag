<div class="card mb-3">
	<div class="card-body">
		<form method="get" action="<?php echo url('talks/search') ?>" class="nf-form-inline">
			<input type="text" name="q" class="form-control flex-grow-1 me-2 mb-2" placeholder="<?php echo $this->lang('Rechercher (min 3 caractères)') ?>" value="<?php echo nf_texte($query) ?>" />
			<select name="talk_id" class="form-select me-2 mb-2">
				<option value=""><?php echo $this->lang('Toutes mes conversations') ?></option>
				<?php foreach ($my_convs as $c): ?>
					<option value="<?php echo (int)$c['talk_id'] ?>" <?php echo $talk_id == $c['talk_id'] ? 'selected' : '' ?>><?php echo nf_texte($c['name']) ?></option>
				<?php endforeach ?>
			</select>
			<button type="submit" class="btn btn-primary mb-2"><?php echo \icon('fas fa-search').' '.$this->lang('Rechercher') ?></button>
		</form>
	</div>
</div>

<?php if (!empty($too_short)): ?>
	<div class="alert alert-warning"><?php echo $this->lang('Tape au moins 3 caractères pour chercher.') ?></div>
<?php elseif ($query !== ''): ?>
	<?php if (empty($results)): ?>
		<div class="alert alert-info"><?php echo $this->lang('Aucun résultat pour "%s"', nf_texte($query)) ?></div>
	<?php else: ?>
		<div class="card">
			<div class="card-header">
				<h6 class="m-0"><?php echo \icon('fas fa-search').' '.$this->lang('%d résultat|%d résultats', count($results), count($results)) ?></h6>
			</div>
			<div class="card-body">
				<?php foreach ($results as $r): ?>
					<div class="mb-2 pb-2" style="border-bottom: 1px dashed rgba(0,0,0,0.1);">
						<div>
							<strong><a href="<?php echo url('talks/'.(int)$r['talk_id'].'/'.\url_title($r['talk_name'])) ?>"><?php echo nf_texte($r['talk_name']) ?></a></strong>
							<small class="text-muted ms-2">
								<?php echo \icon('fas fa-user').' '.nf_texte($r['username'] ?? '?') ?>
								· <?php echo \time_span($r['date']) ?>
							</small>
						</div>
						<div class="small text-muted"><?php echo highlight(nf_texte($r['message'], 200), preg_split('/\s+/', $query)) ?></div>
					</div>
				<?php endforeach ?>
			</div>
		</div>
	<?php endif ?>
<?php endif ?>
