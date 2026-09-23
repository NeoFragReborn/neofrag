<?php if ($image): ?>
	<a href="<?php echo url('news/'.$news_id.'/'.url_title($title)) ?>">
		<img class="card-img-top" src="<?php echo NeoFrag()->model2('file', $image)->path() ?>" alt="" />
	</a>
<?php endif ?>
<div class="card-body">
	<h5 class="card-title"><a href="<?php echo url('news/'.$news_id.'/'.url_title($title)) ?>"><?php echo $title ?></a></h5>
	<p class="card-text"><?php echo $introduction ?></p>
	<?php /* L'auteur, la date, la catégorie : une ligne d'informations, et non une citation. Écrite en
	        `<blockquote>`, elle prenait la taille d'une citation de Bootstrap 5 — plus grosse que le
	        texte de l'actualité, sur téléphone surtout (signalé le 2026-09-23). */ ?>
	<div class="nf-news-meta small text-body-secondary">
		<?php if (isset($next)): ?>
		<div class="float-end">
			<?php echo share_buttons(absolute_url('news/'.$news_id.'/'.url_title($title)), $title) ?>
		</div>
		<?php endif ?>
		<?php echo $this->lang('Par').' '.($user_id ? $this->user->link($user_id, $username) : $this->lang('Visiteur')).' '.$this->lang('le').' '.timetostr('j M Y', $date) ?> · <a href="<?php echo url('news/category/'.$category_id.'/'.$category_name) ?>"><?php echo $category_title ?></a><?php echo (($comments = $this->module('comments')) && $comments->is_enabled()) ? ' · '.$comments->link('news', $news_id, 'news/'.$news_id.'/'.url_title($title)) : '' ?>
	</div>
</div>
<?php if($tags || $content): ?>
<div class="card-footer">
	<?php if ($tags): ?>
		<ul class="list-inline mb-0 float-start">
			<li class="list-inline-item"><small><?php echo icon('fas fa-tag') ?></small></li>
			<?php foreach (explode(',', $tags) as $tag): ?>
				<li class="list-inline-item"><a href="<?php echo url('news/tag/'.url_title($tag)) ?>"><small><?php echo $tag ?></small></a></li>
			<?php endforeach ?>
		</ul>
	<?php endif ?>
	<?php if ($content): ?>
		<a href="<?php echo url('news/'.$news_id.'/'.url_title($title)) ?>" class="btn btn-sm btn-secondary float-end"><?php echo $this->lang('Continuer à lire') ?></a>
	<?php endif ?>
</div>
<?php endif ?>
