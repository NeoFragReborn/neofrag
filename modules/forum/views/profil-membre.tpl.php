<?php
/*
 * L'onglet « Forum » du profil public d'un membre (Forum::profil_membre(), chantier A, étape A2) : ses sujets,
 * puis ses derniers messages — ceux que celui qui regarde peut lire.
 */
?>
<?php if ($sujets): ?>
	<h3><?php echo $this->lang('Sujets lancés') ?></h3>
	<ul class="nf-membre-liste">
		<?php foreach ($sujets as $sujet): ?>
			<li>
				<a href="<?php echo url('forum/topic/'.(int) $sujet['topic_id'].'/'.url_title((string) $sujet['title'])) ?>"><?php echo nf_texte($sujet['title']) ?></a>
				<small><?php echo $this->lang('<b>%d</b> réponse|<b>%d</b> réponses', (int) $sujet['count_messages'], (int) $sujet['count_messages']).' · '.timetostr('j M Y', (int) $sujet['date']) ?></small>
			</li>
		<?php endforeach ?>
	</ul>
<?php endif ?>

<?php if ($messages): ?>
	<h3><?php echo $this->lang('Derniers messages') ?></h3>
	<ul class="nf-membre-liste">
		<?php foreach ($messages as $message): ?>
			<li>
				<div>
					<a href="<?php echo url('forum/topic/'.(int) $message['topic_id'].'/'.url_title((string) $message['title'])).'#'.(int) $message['message_id'] ?>"><?php echo nf_texte($message['title']) ?></a>
					<?php if (($extrait = mb_substr(trim(strip_tags((string) $message['message'])), 0, 160)) !== ''): ?>
						<div class="text-muted small"><?php echo nf_texte($extrait) ?></div>
					<?php endif ?>
				</div>
				<small><?php echo timetostr('j M Y', (int) $message['date']) ?></small>
			</li>
		<?php endforeach ?>
	</ul>
<?php endif ?>
