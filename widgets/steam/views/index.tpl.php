<?php
$online_pct = $members > 0 ? min(100, round($members_online / $members * 100)) : 0;
$ingame_pct = $members > 0 ? min(100, round($members_ingame / $members * 100)) : 0;
$group_url  = 'https://steamcommunity.com/groups/'.htmlspecialchars($url);
?>
<div class="widget-steam widget-steam-<?php echo $display ?>">
	<?php if ($show_avatar): ?>
	<div class="widget-steam-avatar-wrap">
		<div class="widget-steam-avatar-ring" style="--online-pct:<?php echo $online_pct ?>%">
			<a href="<?php echo $group_url ?>" target="_blank" rel="noopener"><img src="<?php echo $avatar ?>" class="widget-steam-avatar" alt="<?php echo htmlspecialchars($name) ?>" /></a>
		</div>
		<?php if ($online_pct > 0): ?>
		<div class="widget-steam-online-badge"><?php echo $online_pct ?>%</div>
		<?php endif ?>
	</div>
	<?php endif ?>

	<div class="widget-steam-content">
		<h6 class="widget-steam-name"><a href="<?php echo $group_url ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($name) ?></a></h6>

		<div class="widget-steam-stats">
			<div class="widget-steam-stat" title="<?php echo $this->lang('Membres') ?>">
				<i class="fas fa-users"></i>
				<span class="widget-steam-stat-num"><?php echo number_format($members, 0, ',', ' ') ?></span>
				<span class="widget-steam-stat-lbl"><?php echo $this->lang('membres') ?></span>
			</div>
			<div class="widget-steam-stat is-online" title="<?php echo $this->lang('En ligne') ?>">
				<i class="fas fa-circle"></i>
				<span class="widget-steam-stat-num"><?php echo $members_online ?></span>
				<span class="widget-steam-stat-lbl"><?php echo $this->lang('en ligne') ?></span>
			</div>
			<?php if ($members_ingame > 0): ?>
			<div class="widget-steam-stat is-ingame" title="<?php echo $this->lang('En jeu') ?>">
				<i class="fas fa-gamepad"></i>
				<span class="widget-steam-stat-num"><?php echo $members_ingame ?></span>
				<span class="widget-steam-stat-lbl"><?php echo $this->lang('en jeu') ?></span>
			</div>
			<?php endif ?>
		</div>

		<a href="<?php echo $group_url ?>" target="_blank" rel="noopener" class="btn btn-sm btn-block widget-steam-btn">
			<i class="fab fa-steam"></i> <?php echo $this->lang('Voir le groupe') ?>
			<i class="fas fa-external-link-alt ms-1"></i>
		</a>
	</div>
</div>
