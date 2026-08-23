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
			<span class="float-end text-muted"><?php echo $age ?> <?php echo $this->lang('ans') ?></span>
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
	var DAYS = ["Dimanche","Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi"];
	var MONTHS = ["janvier","février","mars","avril","mai","juin","juillet","août","septembre","octobre","novembre","décembre"];
	function pad(n){ return n < 10 ? '0' + n : '' + n; }
	function tick(){
		var d = new Date();
		var clk = document.getElementById('widget-clock-time');
		if (clk) clk.textContent = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
		var cal = document.getElementById('widget-clock-date');
		if (cal && !cal.dataset.set){
			cal.textContent = DAYS[d.getDay()] + ' ' + d.getDate() + ' ' + MONTHS[d.getMonth()] + ' ' + d.getFullYear();
			cal.dataset.set = '1';
		}
	}
	tick();
	if (document.getElementById('widget-clock-time')) setInterval(tick, 1000);
})();
</script>
<?php endif ?>
