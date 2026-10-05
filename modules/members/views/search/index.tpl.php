<?php /* La dernière visite, si le membre montre sa présence (chantier A, étape A2). */ ?>
<?php if (!empty($montrer_statut) || ($this->user() && (int) $this->user->id === (int) $id)): ?><p class="float-end"><?php echo icon('far fa-clock').' '.$this->lang('Vu %s', time_span($last_activity_date)) ?></p><?php endif ?>
<big><b><a href="<?php echo url('user/'.$id.'/'.url_title($pseudo)) ?>"><?php echo icon('fas fa-user').' '.$username ?></a></b></big>
<?php if ($nom): ?>
<br />
<br />
<p><?php echo $nom ?></p>
<?php endif ?>
