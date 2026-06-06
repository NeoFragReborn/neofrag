<?php
/**
 * Vue d'une actualité en page complète (« lire la suite »). Layout article propre,
 * distinct de la vue liste (index.tpl.php). Générale — pour tous les sites/thèmes.
 */
$cover = $image ? NeoFrag()->model2('file', $image)->path() : NULL;
?>
<article class="news-article">
	<?php if ($cover): ?>
	<div class="news-article-cover"><img src="<?php echo $cover ?>" alt="" /></div>
	<?php endif ?>

	<header class="news-article-head">
		<a class="news-article-cat" href="<?php echo url('news/category/'.$category_id.'/'.$category_name) ?>"><?php echo $category_title ?></a>
		<h1 class="news-article-title"><?php echo $title ?></h1>
		<div class="news-article-meta">
			<span><i class="far fa-user"></i> <?php echo $user_id ? $this->user->link($user_id, $username) : $this->lang('Visiteur') ?></span>
			<span><i class="far fa-clock"></i> <?php echo timetostr('j F Y', $date) ?></span>
			<span><i class="far fa-eye"></i> <?php echo (int) $views ?></span>
		</div>
	</header>

	<div class="news-article-body"><?php echo $introduction ?></div>

	<?php if ($tags): ?>
	<div class="news-article-tags">
		<?php foreach (explode(',', $tags) as $tag): ?><a href="<?php echo url('news/tag/'.url_title($tag)) ?>">#<?php echo trim($tag) ?></a><?php endforeach ?>
	</div>
	<?php endif ?>

	<?php if (isset($next)): ?>
	<?php if ($reactions = $this->module('reactions')): ?>
	<div class="news-article-reactions mt-3"><?php echo $reactions->bar('news', (int)$news_id) ?></div>
	<?php endif ?>
	<div class="news-article-share"><?php echo share_buttons(absolute_url('news/'.$news_id.'/'.url_title($title)), $title) ?></div>
	<?php endif ?>
</article>
