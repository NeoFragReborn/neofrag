<form class="nf-form-inline <?php echo !empty($align) ? $align : 'float-end' ?>" action="<?php echo url('search') ?>" method="get" autocomplete="off">
	<div class="input-group input-group-sm nf-search">
		<input type="text" class="form-control" name="q" placeholder="<?php echo $this->lang('Rechercher...') ?>" aria-label="<?php echo $this->lang('Rechercher...') ?>" data-suggest-url="<?php echo url('ajax/search/suggest') ?>" />
		<button class="btn btn-light" type="submit"><?php echo icon('fas fa-search') ?></button>
		<div class="nf-search-suggest" role="listbox" hidden></div>
	</div>
</form>
