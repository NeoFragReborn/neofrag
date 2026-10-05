<p class="lead"><?php echo $this->lang('Informations demandées aux candidats') ?> :</p>
<p class="mb-1"><b><?php echo $this->lang('Champs de base') ?></b></p>
<ul class="list-group mb-3">
	<li class="list-group-item py-1"><?php echo $this->lang('Identifiant') ?> <i class="text-muted">(<?php echo $this->lang('pré-rempli') ?>)</i></li>
	<li class="list-group-item py-1"><?php echo $this->lang('Adresse e-mail') ?> <i class="text-muted">(<?php echo $this->lang('pré-rempli') ?>)</i></li>
	<li class="list-group-item py-1"><?php echo $this->lang('Date de naissance') ?> <i class="text-muted">(<?php echo $this->lang('pré-rempli') ?>)</i></li>
	<li class="list-group-item py-1"><?php echo $this->lang('Présentation') ?></li>
	<li class="list-group-item py-1"><?php echo $this->lang('Motivations') ?></li>
	<li class="list-group-item py-1"><?php echo $this->lang('Expériences') ?></li>
</ul>
<p class="mb-1"><b><?php echo $this->lang('Champs personnalisés') ?></b></p>
<?php if (empty($fields)): ?>
<p class="text-muted mb-0"><?php echo $this->lang('Aucun champ personnalisé pour le moment.') ?></p>
<?php else: ?>
<ul class="list-group">
	<?php foreach ($fields as $f): ?>
	<li class="list-group-item py-1"><?php echo nf_texte($f['label']) ?><?php echo $f['required'] ? ' <span class="text-red">*</span>' : '' ?></li>
	<?php endforeach ?>
</ul>
<?php endif ?>
