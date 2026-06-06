<nav class="navbar navbar-expand-lg navbar-user<?php echo $this->config->forge_navbar_display ? ' fixed-top' : '' ?>">
	<div class="container-fluid">
		<?php if ($this->access->effective_admin()): ?>
			<a href="<?php echo url('admin') ?>" class="navbar-brand" title="<?php echo $this->lang('Administration') ?>"><?php echo icon('fas fa-tachometer-alt') ?></a>
		<?php endif ?>

		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#user-navbar" aria-controls="user-navbar" aria-expanded="false" aria-label="<?php echo $this->lang('Menu') ?>">
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="collapse navbar-collapse" id="user-navbar">
			<?php if ($this->user->id): ?>
				<span class="navbar-text mr-auto"><?php echo $this->lang('Bienvenue') ?> <a href="<?php echo url('user') ?>"><?php echo $this->user->username ?></a></span>
				<ul class="navbar-nav navbar-account ml-auto">
					<?php if ($notifications = $this->module('notifications')) echo $notifications->bell() ?>
					<li class="nav-item dropdown">
						<span class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false" style="cursor:pointer;">
							<?php echo $this->user->avatar($this->user->avatar, $this->user->sex) ?>
							<span class="ml-2"><?php echo $this->lang('Mon compte') ?></span>
						</span>
						<div class="dropdown-menu dropdown-menu-right">
							<a class="dropdown-item" href="<?php echo url('user') ?>"><?php echo icon('fas fa-user').' '.$this->lang('Mon espace') ?></a>
							<a class="dropdown-item" href="<?php echo url('user/account') ?>"><?php echo icon('fas fa-cog').' '.$this->lang('Gérer mon compte') ?></a>
							<?php if ($this->access->effective_admin()): ?>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="<?php echo url('admin') ?>"><?php echo icon('fas fa-tachometer-alt').' '.$this->lang('Administration') ?></a>
							<?php endif ?>
						</div>
					</li>
					<li class="nav-item">
						<button type="button" class="nav-link theme-toggle btn btn-link p-2" title="<?php echo $this->lang('Mode jour') ?>" aria-label="<?php echo $this->lang('Mode jour') ?>" style="border:0;background:transparent;cursor:pointer;"><i class="fas fa-sun"></i></button>
					</li>
					<li class="nav-item">
						<a href="<?php echo url('user/logout') ?>" class="nav-link" title="<?php echo $this->lang('Déconnexion') ?>"><?php echo icon('fas fa-sign-out-alt') ?></a>
					</li>
				</ul>
			<?php else: ?>
				<span class="navbar-text mr-auto"><?php echo $this->lang('Bienvenue sur le site') ?> <a href="<?php echo url() ?>"><?php echo $this->config->nf_name ?></a></span>
				<ul class="navbar-nav navbar-account ml-auto">
					<li class="nav-item">
						<button type="button" class="nav-link theme-toggle btn btn-link p-2" title="<?php echo $this->lang('Mode jour') ?>" aria-label="<?php echo $this->lang('Mode jour') ?>" style="border:0;background:transparent;cursor:pointer;"><i class="fas fa-sun"></i></button>
					</li>
					<li class="nav-item">
						<a href="<?php echo url('user') ?>" class="nav-link"><?php echo icon('fas fa-unlock').' '.$this->lang('Espace membre') ?></a>
					</li>
				</ul>
			<?php endif ?>
		</div>
	</div>
</nav>
