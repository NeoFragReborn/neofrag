<ul class="list-group list-group-flush widget-clock">
	<?php if ($clock): ?>
	<li class="list-group-item text-center">
		<span class="widget-clock-time" id="widget-clock-time">--:--:--</span>
	</li>
	<?php endif ?>

	<?php if ($calendar): ?>
	<li class="list-group-item">
		<i class="far fa-calendar fa-fw"></i>
		<span class="widget-clock-date" id="widget-clock-date"></span>
	</li>
	<?php endif ?>

	<?php if ($birthday): ?>
		<?php if (!empty($birthdays)): ?>
		<li class="list-group-item widget-clock-birthday-header">
			<i class="fas fa-birthday-cake fa-fw"></i> <strong><?php echo $this->lang('Anniversaires du jour') ?></strong>
		</li>
			<?php foreach ($birthdays as $b):
				$age = date_diff(date_create($b['date_of_birth']), date_create('today'))->y;
			?>
		<li class="list-group-item widget-clock-birthday-item">
			<i class="fas fa-gift fa-fw"></i>
			<?php echo $this->user->link($b['user_id'], $b['username']) ?>
			<span class="float-end text-muted"><?php echo $this->lang('%d an|%d ans', $age, $age) ?></span>
		</li>
			<?php endforeach ?>
		<?php else: ?>
		<li class="list-group-item text-muted text-center">
			<i class="fas fa-birthday-cake fa-fw"></i> <?php echo $this->lang('Aucun anniversaire aujourd\'hui') ?>
		</li>
		<?php endif ?>
	<?php endif ?>
</ul>

<?php if ($clock || $calendar): ?>
<script>
(function(){
	// Jour et mois dans la langue de la page (<html lang>), par le navigateur : « Dimanche 5 janvier 2026 »,
	// « Sunday, January 5, 2026 », « Sonntag, 5. Januar 2026 »… Première lettre en capitale, comme avant.
	var OPTIONS = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
	function dateLongue(d){
		var s;
		try { s = d.toLocaleDateString(document.documentElement.lang || 'fr', OPTIONS); }
		catch (e) { s = d.toLocaleDateString('fr', OPTIONS); }
		return s.charAt(0).toUpperCase() + s.slice(1);
	}
	function pad(n){ return n < 10 ? '0' + n : '' + n; }
	function tick(){
		var d = new Date();
		var clk = document.getElementById('widget-clock-time');
		if (clk) clk.textContent = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
		var cal = document.getElementById('widget-clock-date');
		if (cal && !cal.dataset.set){
			cal.textContent = dateLongue(d);
			cal.dataset.set = '1';
		}
	}
	tick();
	if (document.getElementById('widget-clock-time')) setInterval(tick, 1000);
})();
</script>
<?php endif ?>
