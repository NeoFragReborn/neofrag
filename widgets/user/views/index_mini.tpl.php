<ul class="nav <?php echo !empty($align) ? $align : 'justify-content-end' ?>">
	<?php if ($this->user()): ?>
		<li class="nav-item"><span class="nav-link"><?php echo $this->lang('Bienvenue <a href="'.url('user').'">'.$this->user->username.'</a>') ?></span></li>
		<li class="nav-item" data-bs-toggle="tooltip" title="<?php echo $this->lang('Éditer mon profil') ?>"><a class="nav-link" href="<?php echo url('user/profile') ?>"><?php echo icon('fas fa-cog') ?></a></li>
		<li class="nav-item" data-bs-toggle="tooltip" title="<?php echo $this->lang('Messagerie') ?>">
			<a class="nav-link" href="<?php echo url('talks?type=private') ?>">
				<?php echo icon('far fa-envelope') ?>
				<?php if ($messages = $this->module('talks')->model()->get_unread_count($this->user->id)): ?><span class="badge text-bg-danger"><?php echo $messages ?></span><?php endif  ?>
			</a>
		</li>
		<?php if ($this->access->effective_admin()): ?>
			<li class="nav-item" data-bs-toggle="tooltip" title="<?php echo $this->lang('Administration') ?>"><a class="nav-link" href="<?php echo url('admin') ?>"><?php echo icon('fas fa-tachometer-alt') ?></a></li>
		<?php endif ?>
		<li data-bs-toggle="tooltip" title="<?php echo $this->lang('Déconnexion') ?>"><a class="nav-link" href="<?php echo url('user/logout') ?>"><?php echo icon('fas fa-times') ?></a></li>
	<?php else: ?>
		<?php if ($this->config->nf_registration_status): ?>
		<li class="nav-item"><a class="nav-link" href="#" data-modal-ajax="<?php echo url('ajax/user/register') ?>"><?php echo $this->lang('Créer un compte') ?></a></li>
		<?php endif ?>
		<li class="nav-item"><a class="nav-link" href="#" data-modal-ajax="<?php echo url('ajax/user/auth') ?>"><?php echo icon('fas fa-sign-in-alt').' '.$this->lang('Connexion') ?></a></li>
	<?php endif ?>
</ul>
