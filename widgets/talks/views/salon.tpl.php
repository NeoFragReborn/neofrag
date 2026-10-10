<?php
/*
 * Un salon public : ses derniers messages (le plus récent en bas, comme dans la messagerie), puis le lien pour le
 * rejoindre. Pour un visiteur ($messages === NULL) : l'invitation à se connecter, sans le texte des messages.
 */
$messages = $messages ?? NULL;
$lien     = (string) ($lien ?? url('talks'));
$sans_balises = static fn (string $texte): string => trim((string) preg_replace('#\[/?[a-z*]+(=[^\]]*)?\]#i', '', $texte));
?>
<div class="nf-salon">
	<?php if ($messages === NULL): ?>
	<p class="nf-salon-invitation"><?php echo $this->lang('Connecte-toi pour lire le salon et y répondre.') ?></p>
	<a class="btn btn-primary btn-sm" href="<?php echo url('user/login') ?>" data-modal-ajax="<?php echo url('ajax/user/login') ?>"><?php echo $this->lang('Se connecter') ?></a>
	<?php else: ?>
		<?php if ($messages): ?>
		<ul class="nf-salon-messages">
			<?php foreach ($messages as $message): ?>
			<li>
				<span class="nf-salon-avatar"><?php echo $this->module('user')->model2('user', $message['user_id'])->avatar() ?></span>
				<span class="nf-salon-texte">
					<span class="nf-salon-auteur"><?php echo $this->user->link($message['user_id'], $message['username']) ?> <time datetime="<?php echo nf_texte(date('c', strtotime((string) $message['date']))) ?>"><?php echo time_span($message['date']) ?></time></span>
					<span class="nf-salon-message"><?php echo nf_texte($sans_balises((string) $message['message']), 140) ?></span>
				</span>
			</li>
			<?php endforeach ?>
		</ul>
		<?php else: ?>
		<p class="nf-salon-vide"><?php echo $this->lang('Personne n’a encore écrit dans ce salon.') ?></p>
		<?php endif ?>
	<a class="nf-salon-rejoindre" href="<?php echo $lien ?>"><?php echo icon('far fa-paper-plane') ?> <?php echo $this->lang('Écrire dans le salon') ?></a>
	<?php endif ?>
</div>
