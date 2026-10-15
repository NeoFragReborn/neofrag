<?php $base = 'collectives/'.(int) $collectif['collective_id'].'/'.$slug ?>
<div class="row g-3">
	<div class="col-12 col-lg-8">
		<div class="card">
			<div class="card-header"><?php echo icon('fas fa-users') ?> <?php echo $this->lang('Membres') ?> <span class="badge text-bg-secondary"><?php echo count($membres) ?></span></div>
			<div class="table-responsive">
				<table class="table table-hover mb-0">
					<tbody>
						<?php foreach ($membres as $m): ?>
							<tr>
								<td><?php echo $this->user->link((int) $m['user_id'], $m['username']) ?></td>
								<td>
									<?php if ($m['touchable'] && $donnables): ?>
										<form method="post" action="<?php echo url($base.'/rank/'.(int) $m['user_id']) ?>" class="d-flex gap-2 align-items-center">
											<input type="hidden" name="_" value="<?php echo $csrf ?>">
											<select name="rank" class="form-select form-select-sm w-auto" aria-label="<?php echo nf_texte($this->lang('Rang')) ?>">
												<?php foreach ($donnables as $r): ?>
													<option value="<?php echo $r ?>"<?php echo $r === $m['rank'] ? ' selected' : '' ?>><?php echo nf_texte($rangs[$r]) ?></option>
												<?php endforeach ?>
											</select>
											<button type="submit" class="btn btn-sm btn-outline-secondary" title="<?php echo nf_texte($this->lang('Changer le rang')) ?>"><?php echo icon('fas fa-check') ?></button>
										</form>
									<?php else: ?>
										<span class="badge <?php echo $m['rank'] === 'chef' ? 'text-bg-primary' : 'text-bg-secondary' ?>"><?php echo nf_texte($rangs[$m['rank']] ?? $m['rank']) ?></span>
									<?php endif ?>
								</td>
								<td class="text-end">
									<?php if ($m['touchable']): ?>
										<a class="btn btn-sm btn-outline-danger" href="<?php echo url($base.'/remove/'.(int) $m['user_id']) ?>?_=<?php echo $csrf ?>" data-confirm="<?php echo nf_texte($this->lang('Retirer %s du collectif ?', nf_texte_brut($m['username']))) ?>" title="<?php echo nf_texte($this->lang('Retirer du collectif')) ?>"><?php echo icon('fas fa-user-minus') ?></a>
									<?php endif ?>
								</td>
							</tr>
						<?php endforeach ?>
					</tbody>
				</table>
			</div>
			<?php if ($gere && $donnables): ?>
				<div class="card-footer">
					<form method="post" action="<?php echo url($base.'/add') ?>" class="row g-2 align-items-center">
						<input type="hidden" name="_" value="<?php echo $csrf ?>">
						<div class="col-12 col-sm-6">
							<input type="text" name="username" class="form-control form-control-sm" required maxlength="100" placeholder="<?php echo nf_texte($this->lang('Pseudo du membre')) ?>" aria-label="<?php echo nf_texte($this->lang('Pseudo du membre')) ?>">
						</div>
						<div class="col-8 col-sm-4">
							<select name="rank" class="form-select form-select-sm" aria-label="<?php echo nf_texte($this->lang('Rang')) ?>">
								<?php foreach (array_reverse($donnables) as $r): ?>
									<option value="<?php echo $r ?>"><?php echo nf_texte($rangs[$r]) ?></option>
								<?php endforeach ?>
							</select>
						</div>
						<div class="col-4 col-sm-2">
							<button type="submit" class="btn btn-sm btn-primary w-100"><?php echo icon('fas fa-user-plus') ?> <?php echo $this->lang('Ajouter') ?></button>
						</div>
					</form>
				</div>
			<?php endif ?>
		</div>
	</div>
	<div class="col-12 col-lg-4">
		<div class="card mb-3">
			<div class="card-body">
				<?php if (!empty($collectif['description'])): ?>
					<p><?php echo nl2br(nf_texte($collectif['description'])) ?></p>
				<?php endif ?>
				<?php if ($rang !== NULL): ?>
					<p class="mb-0"><?php echo $this->lang('Votre rang :') ?> <span class="badge text-bg-secondary"><?php echo nf_texte($rangs[$rang] ?? $rang) ?></span></p>
				<?php endif ?>
			</div>
		</div>
		<div class="list-group mb-3">
			<?php if ($forum): ?>
				<a class="list-group-item list-group-item-action" href="<?php echo url('forum/'.(int) $forum['forum_id'].'/'.url_title($forum['title'])) ?>"><?php echo icon('far fa-comments') ?> <?php echo $this->lang('La section du forum') ?></a>
			<?php endif ?>
			<?php if ($salon): ?>
				<a class="list-group-item list-group-item-action" href="<?php echo nf_texte($salon) ?>" target="_blank" rel="noopener"><?php echo icon('fab fa-discord') ?> <?php echo $this->lang('Le salon sur Discord') ?></a>
			<?php endif ?>
		</div>
		<?php if ($rang !== NULL && $rang !== 'chef'): ?>
			<a class="btn btn-sm btn-outline-danger" href="<?php echo url($base.'/leave') ?>?_=<?php echo $csrf ?>" data-confirm="<?php echo nf_texte($this->lang('Quitter ce collectif ? Vous perdrez l’accès à sa section du forum et à son salon.')) ?>"><?php echo icon('fas fa-door-open') ?> <?php echo $this->lang('Quitter le collectif') ?></a>
		<?php endif ?>
	</div>
</div>
