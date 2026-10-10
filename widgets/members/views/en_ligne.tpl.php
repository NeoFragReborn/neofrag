<?php
/*
 * « Qui est en ligne ? (liste) » : les trois nombres, puis les présents eux-mêmes — administrateurs d'abord,
 * chacun avec son avatar, son pseudo (vers son profil) et sa dernière activité. Une pastille verte dit « en ligne ».
 */
$administrators = $administrators ?? [];
$members        = $members ?? [];
$autres         = (int) ($autres ?? 0);
$nb_admins      = (int) ($nb_admins ?? 0);
$nb_members     = (int) ($nb_members ?? 0);
$nb_visitors    = (int) ($nb_visitors ?? 0);
$groupes = [
	[$this->lang('Administrateurs'), $administrators],
	[$this->lang('Membres'),         $members]
];
?>
<div class="nf-en-ligne">
	<div class="nf-en-ligne-chiffres">
		<span><b><?php echo (int) $nb_admins ?></b><?php echo $this->lang('Admin|Admins', $nb_admins) ?></span>
		<span><b><?php echo (int) $nb_members ?></b><?php echo $this->lang('Membre|Membres', $nb_members) ?></span>
		<span><b><?php echo (int) $nb_visitors ?></b><?php echo $this->lang('Visiteur|Visiteurs', $nb_visitors) ?></span>
	</div>
	<?php foreach ($groupes as list($titre, $users)): if (!$users) continue; ?>
	<p class="nf-en-ligne-groupe"><?php echo $titre ?></p>
	<ul class="nf-en-ligne-liste">
		<?php foreach ($users as $user): ?>
		<li>
			<span class="nf-en-ligne-avatar"><?php echo $this->module('user')->model2('user', $user['user_id'])->avatar() ?></span>
			<span class="nf-en-ligne-qui">
				<?php echo $this->user->link($user['user_id'], $user['username']) ?>
				<small><?php echo time_span($user['last_activity']) ?></small>
			</span>
		</li>
		<?php endforeach ?>
	</ul>
	<?php endforeach ?>
	<?php if (!empty($autres)): ?>
	<p class="nf-en-ligne-autres"><?php echo $this->lang('et %d autre|et %d autres', $autres, $autres) ?></p>
	<?php endif ?>
	<?php if (!$administrators && !$members): ?>
	<p class="nf-en-ligne-vide"><?php echo $this->lang('Aucun membre connecté pour le moment.') ?></p>
	<?php endif ?>
</div>
