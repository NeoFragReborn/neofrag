<?php echo $this->lang('Envoyée le <b>%s</b>', timetostr('j M Y', $date)) ?><?php echo $team_id ? $this->lang(', pour rejoindre l\'équipe <b>%s</b> au poste de <b>%s</b>', $team_name, $role) : $this->lang(', pour le poste de <b>%s</b>', $role) ?>
<br /><?php echo $this->lang('Date de naissance:') ?> <?php echo timetostr('j M Y', $date_of_birth) ?>
<hr />
<h4><?php echo $this->lang('Présentation') ?></h4>
<?php echo $presentation ? $presentation : $this->lang('Non renseigné.') ?>
<hr />
<h4><?php echo $this->lang('Motivations') ?></h4>
<?php echo $motivations ? $motivations : $this->lang('Non renseigné.') ?>
<hr />
<h4><?php echo $this->lang('Expériences') ?></h4>
<?php echo $experiences ? $experiences : $this->lang('Non renseigné.') ?>
<?php if (!empty($custom)): foreach ($custom as $c): ?>
<hr />
<h4><?php echo nf_texte($c['label']) ?></h4>
<?php echo (isset($c['value']) && $c['value'] !== '') ? nl2br(nf_texte($c['value'])) : $this->lang('Non renseigné.') ?>
<?php endforeach; endif ?>
