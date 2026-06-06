<?php
// R1.9 — En mode preview, on affiche l'identité publique du target (avatar/username) pour simuler l'expérience visuelle,
// mais on CACHE tous les liens vers du contenu personnel (Mon espace, Gérer compte, Messagerie) pour respecter la vie
// privée du target. L'admin réel ne doit JAMAIS pouvoir consulter le compte/MP de bob via ce widget.
$preview_user   = method_exists($this->access, 'preview_user') ? $this->access->preview_user() : NULL;
$preview_target = method_exists($this->access, 'get_preview_target') ? $this->access->get_preview_target() : NULL;
$preview_active = $preview_target !== NULL;

if ($preview_user)
{
	$display_avatar   = $preview_user->avatar()->append_attr('class', 'm-auto');
	$display_name     = $preview_user->username;
	$display_title    = !empty($preview_user->title) ? $preview_user->title : NULL;
	$display_username = $preview_user->username;
}
else
{
	$display_avatar   = $this->user->avatar()->append_attr('class', 'm-auto');
	$display_name     = $this->user->username;
	$display_title    = $this->user->title ?: NULL;
	$display_username = $username;
}
?>
<div class="card-body text-center">
	<?php echo $display_avatar ?>
	<div class="user-name mt-3"><?php if (!$preview_active): ?><a href="<?php echo url('user') ?>"><?php echo $display_name ?></a><?php else: ?><?php echo htmlspecialchars($display_name) ?><?php endif ?></div>
	<?php if ($display_title): ?>
	<div class="user-role"><?php echo $display_title ?></div>
	<?php endif ?>
	<?php if ($preview_active && $preview_target['type'] === 'role'): ?>
	<div class="text-muted small mt-2"><?php echo icon('fas fa-user-shield').' '.htmlspecialchars($preview_target['label']) ?></div>
	<?php endif ?>
	<?php if ($preview_active): ?>
	<div class="alert alert-warning mt-3 mb-0 py-2 px-3 small text-left">
		<?php echo icon('fas fa-eye') ?> <strong><?php echo $this->lang('Mode preview') ?></strong><br>
		<?php echo $this->lang('Les liens vers les données personnelles sont masqués pour respecter la vie privée du compte cible.') ?>
	</div>
	<?php endif ?>
</div>
<?php if (!$preview_active): ?>
<ul class="list-group list-group-flush nf-user-menu">
	<li class="list-group-item">
		<?php echo icon('fas fa-user') ?> <a href="<?php echo url('user') ?>"><?php echo $this->lang('Mon espace') ?></a>
	</li>
	<li class="list-group-item">
		<?php echo icon('fas fa-cogs') ?> <a href="<?php echo url('user/account') ?>"><?php echo $this->lang('Gérer mon compte') ?></a>
	</li>
	<li class="list-group-item">
		<?php echo icon('far fa-eye') ?> <a href="<?php echo url('user/'.$this->user->id.'/'.url_title($username)) ?>"><?php echo $this->lang('Voir mon profil') ?></a>
	</li>
	<li class="list-group-item">
		<?php echo icon('far fa-envelope') ?> <a href="<?php echo url('talks?type=private') ?>"><?php echo $this->lang('Messagerie') ?></a>
		<?php if ($messages = $this->module('talks')->model()->get_unread_count($this->user->id)): ?><span class="badge badge-danger nf-user-menu-badge"><?php echo $messages ?></span><?php endif ?>
	</li>
	<?php if ($this->access('moderation', 'view_reports')): ?>
	<li class="list-group-item">
		<?php echo icon('fas fa-shield-alt') ?> <a href="<?php echo url('moderation') ?>"><?php echo $this->lang('Modération') ?></a>
		<?php $pending = (int)$this->db->select('COUNT(*)')->from('nf_reports')->where('status', 'pending')->row(); if ($pending > 0): ?><span class="badge badge-warning nf-user-menu-badge"><?php echo $pending ?></span><?php endif ?>
	</li>
	<?php endif ?>
	<?php if ($this->access->effective_admin()): ?>
	<li class="list-group-item">
		<?php echo icon('fas fa-tachometer-alt') ?> <a href="<?php echo url('admin') ?>"><?php echo $this->lang('Administration') ?></a>
	</li>
	<?php endif ?>
</ul>
<style>
/* Alignement robuste : icône + libellé toujours en colonne, badge poussé à droite (badge ou non). */
.nf-user-menu .list-group-item { display: flex; align-items: center; gap: 8px; }
.nf-user-menu .list-group-item > .icon { flex: 0 0 auto; }
.nf-user-menu-badge { margin-left: auto; }
</style>
<?php else: ?>
<ul class="list-group list-group-flush">
	<?php if ($this->access('moderation', 'view_reports')): ?>
	<li class="list-group-item">
		<?php echo icon('fas fa-shield-alt') ?> <span class="text-muted"><?php echo $this->lang('Modération') ?></span>
	</li>
	<?php endif ?>
	<?php if ($this->access->effective_admin()): ?>
	<li class="list-group-item">
		<?php echo icon('fas fa-tachometer-alt') ?> <span class="text-muted"><?php echo $this->lang('Administration') ?></span>
	</li>
	<?php endif ?>
</ul>
<?php endif ?>
