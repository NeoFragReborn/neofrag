<div class="payments-page">
	<h2 class="mb-3"><?php echo icon('fas fa-credit-card').' '.$this->lang('Recharge & VIP') ?></h2>

	<?php if (!empty($adhesion)): ?>
		<div class="alert alert-success"><?php echo icon('fas fa-id-card').' '.$this->lang('Vous êtes adhérent jusqu’au %s.', nf_date($adhesion)) ?></div>
	<?php endif ?>

	<?php if (!$enabled): ?>
		<div class="alert alert-info"><?php echo $this->lang('Les paiements ne sont pas disponibles pour le moment.') ?></div>
	<?php elseif (!$logged): ?>
		<div class="alert alert-warning"><?php echo $this->lang('Connecte-toi pour acheter.') ?> <a href="<?php echo url('user') ?>"><?php echo $this->lang('Connexion') ?></a></div>
	<?php endif ?>

	<div class="row">
		<?php foreach ($packs as $p): ?>
			<div class="col-sm-6 col-md-4 mb-3">
				<div class="card h-100 text-center">
					<div class="card-body">
						<div style="font-size:30px;opacity:.85;"><?php echo icon(['vip' => 'fas fa-crown', 'adhesion' => 'fas fa-id-card'][$p['kind']] ?? 'fas fa-coins') ?></div>
						<h5 class="card-title"><?php echo nf_texte($p['label']) ?></h5>
						<p class="card-text text-muted"><?php echo match ($p['kind']) { 'vip' => $this->lang('%d jours de VIP', (int)$p['units']), 'adhesion' => $this->lang('Adhésion de %d jours', (int)$p['units']), default => $this->lang('%d points', (int)$p['units']) } ?></p>
						<div style="font-weight:700;font-size:18px;"><?php echo number_format((int)$p['price_cents'] / 100, 2).' '.strtoupper($p['currency']) ?></div>
					</div>
					<div class="card-footer">
						<?php if ($enabled && $logged): ?>
							<button type="button" class="btn btn-sm btn-primary" data-pay-buy data-url="<?php echo nf_texte(url('ajax/payments/checkout/'.(int)$p['id']).'?_='.rawurlencode((string) ($jeton ?? ''))) ?>"><?php echo icon('fas fa-credit-card').' '.$this->lang('Payer') ?></button>
						<?php else: ?>
							<span class="btn btn-sm btn-secondary disabled"><?php echo $this->lang('Indisponible') ?></span>
						<?php endif ?>
					</div>
				</div>
			</div>
		<?php endforeach ?>

		<?php if (!$packs): ?>
			<div class="col-12"><div class="alert alert-info"><?php echo $this->lang('Aucun pack disponible.') ?></div></div>
		<?php endif ?>
	</div>
</div>
