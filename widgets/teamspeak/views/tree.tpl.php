<div class="widget-ts widget-ts-tree">
	<div class="widget-ts-header">
		<div class="widget-ts-icon"><i class="fas fa-microphone-alt"></i></div>
		<div class="widget-ts-meta">
			<div class="widget-ts-name"><?php echo nf_texte($server_name) ?></div>
			<div class="widget-ts-stats">
				<span class="widget-ts-dot"></span>
				<strong><?php echo (int)$clients_online ?></strong> /
				<span><?php echo (int)$clients_max ?></span>
				<span class="widget-ts-stats-lbl"><?php echo $this->lang('clients') ?></span>
			</div>
		</div>
	</div>

	<div class="widget-ts-tree-content">
		<?php
		$client_icon = function($c) {
			if (!empty($c['snd_off'])) return 'fa-volume-xmark';
			if (!empty($c['mic_off'])) return 'fa-microphone-slash';
			if (!empty($c['away']))    return 'fa-clock';
			return 'fa-microphone';
		};

		$spacer_label = function($name) {
			$label = preg_replace('/^\[[^\]]*\]/', '', $name); // retire [spacer]/[cspacer]/[*spacer]…
			return trim((string)$label, " \t-_=*.~");
		};

		$render = function($nodes) use (&$render, $client_icon, $spacer_label) {
			echo '<ul class="widget-ts-channels">';
			foreach ($nodes as $n) {
				if (!empty($n['is_spacer'])) {
					$lbl = $spacer_label($n['name']);
					echo '<li class="widget-ts-spacer">'.($lbl !== '' ? '<span>'.nf_texte($lbl).'</span>' : '').'</li>';
					continue;
				}
				echo '<li class="widget-ts-channel">';
				echo '<div class="widget-ts-channel-name"><i class="fas fa-comment-dots"></i> <span class="widget-ts-channel-label">'.nf_texte($n['name']).'</span>';
				if (!empty($n['clients'])) {
					echo ' <span class="widget-ts-channel-count">'.count($n['clients']).'</span>';
				}
				echo '</div>';
				if (!empty($n['clients'])) {
					echo '<ul class="widget-ts-clients">';
					foreach ($n['clients'] as $c) {
						$away = !empty($c['away']) ? ' is-away' : '';
						echo '<li class="widget-ts-client'.$away.'"><i class="fas '.$client_icon($c).'"></i> '.nf_texte($c['name']).'</li>';
					}
					echo '</ul>';
				}
				if (!empty($n['children'])) {
					$render($n['children']);
				}
				echo '</li>';
			}
			echo '</ul>';
		};

		$render($tree ?? []);
		?>
	</div>

	<a href="<?php echo nf_texte($ts_url) ?>" class="widget-ts-cta">
		<i class="fas fa-sign-in-alt"></i> <?php echo $this->lang('Rejoindre TeamSpeak') ?>
	</a>
</div>
