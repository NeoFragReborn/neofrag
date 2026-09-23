<?php
/**
 * Forum admin view — modern, clean.
 * IMPORTANT: NeoFrag stores titles/descriptions HTML-encoded already.
 * Don't apply htmlspecialchars() — would double-encode (G&eacute;n&eacute;ral).
 */
$forums_count    = count($forums);
$total_topics    = 0;
$total_messages  = 0;
foreach ($forums as $f) {
	$total_topics   += (int)($f['count_topics'] ?? 0);
	$total_messages += (int)($f['count_messages'] ?? 0);
}
?>
<div class="card-header" data-category-id="<?php echo $category_id ?>">
	<?php /* Le compteur revient à la ligne sous le titre quand la place manque : interdit de
	         couper (`nowrap`), il passait SOUS les boutons de la catégorie à 360 px. Et il passe
	         par les traductions : il était écrit en français dans les six langues (2026-09-23). */ ?>
	<span style="display:flex;align-items:center;flex-wrap:wrap;gap:2px 8px;flex:1;min-width:0;">
		<strong><i class="fas fa-folder" style="color:var(--nf-accent);"></i> <?php echo $title /* pre-encoded */ ?></strong>
		<small class="text-muted" style="font-weight:400;font-size:12px;">
			<?php echo $this->lang('%d forum|%d forums', $forums_count, $forums_count) ?>
			<?php if ($total_topics > 0): ?>
				· <?php echo $this->lang('%d sujet|%d sujets', $total_topics, $total_topics) ?>
				· <?php echo $this->lang('%d message|%d messages', $total_messages, $total_messages) ?>
			<?php endif ?>
		</small>
	</span>
	<span style="display:flex;gap:6px;align-items:center;flex-shrink:0;">
		<?php echo $this->button_access($category_id, 'category') ?>
		<?php echo $this->button_update('admin/forum/categories/'.$category_id.'/'.url_title($title)) ?>
		<?php echo $this->button_delete('admin/forum/categories/delete/'.$category_id.'/'.url_title($title)) ?>
	</span>
</div>

<?php if (empty($forums)): ?>
<div class="nf-empty"><i class="far fa-comments"></i><?php echo $this->lang('Aucun forum dans cette catégorie.') ?></div>
<?php else: ?>
<ul class="forum-admin-list" data-category-id="<?php echo $category_id ?>">
	<?php foreach ($forums as $forum): ?>
	<li class="forum-admin-item" data-forum-id="<?php echo $forum['forum_id'] ?>">
		<div class="forum-admin-row">
			<span class="forum-admin-handle" title="<?php echo $this->lang('Glisser-déposer pour réorganiser') ?>"><i class="fas fa-grip-vertical"></i></span>
			<div class="forum-admin-main">
				<div class="forum-admin-title">
					<?php if (!empty($forum['url'])): ?><i class="fas fa-external-link-alt forum-admin-redirect" title="<?php echo $this->lang('Forum de redirection') ?>"></i><?php endif ?>
					<a href="<?php echo url('forum/'.$forum['forum_id'].'/'.url_title($forum['title'])) ?>"><?php echo $forum['title'] /* pre-encoded */ ?></a>
				</div>
				<?php if (!empty($forum['description'])): ?>
				<div class="forum-admin-desc"><?php echo $forum['description'] /* may contain HTML */ ?></div>
				<?php endif ?>
			</div>
			<div class="forum-admin-stats">
				<?php if (!empty($forum['url'])): ?>
					<span><strong><?php echo (int)$forum['redirects'] ?></strong> <?php echo $this->lang('redirection|redirections', $forum['redirects']) ?></span>
				<?php else: ?>
					<span><strong><?php echo (int)$forum['count_topics'] ?></strong> <?php echo $this->lang('sujet|sujets', (int)$forum['count_topics']) ?></span>
					<span><strong><?php echo (int)$forum['count_messages'] ?></strong> <?php echo $this->lang('message|messages', (int)$forum['count_messages']) ?></span>
				<?php endif ?>
			</div>
			<div class="forum-admin-last">
				<?php if (empty($forum['url']) && !empty($forum['last_title'])): ?>
				<a href="<?php echo url('forum/topic/'.$forum['topic_id'].'/'.url_title($forum['last_title'])) ?>" class="forum-admin-last-title"><?php echo str_shortener($forum['last_title'], 32) ?></a>
				<small><?php echo $forum['user_id'] ? $this->user->link($forum['user_id'], $forum['username']) : '<i>'.$this->lang('Visiteur').'</i>' ?> · <?php echo time_span($forum['last_message_date']) ?></small>
				<?php elseif (empty($forum['url'])): ?>
				<span class="text-muted" style="font-size:12px;"><?php echo $this->lang('Aucun message') ?></span>
				<?php endif ?>
			</div>
			<div class="forum-admin-actions">
				<?php echo $this->button_update('admin/forum/'.$forum['forum_id'].'/'.url_title($forum['title'])) ?>
				<?php echo $this->button_delete('admin/forum/delete/'.$forum['forum_id'].'/'.url_title($forum['title'])) ?>
			</div>
		</div>

		<?php if (!empty($forum['subforums'])): ?>
		<ul class="forum-admin-subforums">
			<?php foreach ($forum['subforums'] as $subforum): ?>
			<li class="forum-admin-subforum-item" data-forum-id="<?php echo $subforum['forum_id'] ?>">
				<span class="forum-admin-handle" title="<?php echo $this->lang('Glisser-déposer') ?>"><i class="fas fa-grip-vertical"></i></span>
				<i class="fas fa-arrow-right forum-admin-sub-arrow"></i>
				<a href="<?php echo url('forum/'.$subforum['forum_id'].'/'.url_title($subforum['title'])) ?>" class="forum-admin-sub-title"><?php echo $subforum['title'] /* pre-encoded */ ?></a>
				<?php if (!empty($subforum['description'])): ?>
				<small class="forum-admin-sub-desc"><?php echo $subforum['description'] ?></small>
				<?php endif ?>
				<div class="forum-admin-actions" style="margin-left:auto;">
					<?php echo $this->button_update('admin/forum/'.$subforum['forum_id'].'/'.url_title($subforum['title'])) ?>
					<?php echo $this->button_delete('admin/forum/delete/'.$subforum['forum_id'].'/'.url_title($subforum['title'])) ?>
				</div>
			</li>
			<?php endforeach ?>
		</ul>
		<?php endif ?>
	</li>
	<?php endforeach ?>
</ul>
<?php endif ?>
