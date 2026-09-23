<?php
// Card user épurée pour le forum (Phase A — refonte UI)
// Affiche : avatar 80px + pseudo + badge rôle principal uniquement.
// Au hover 2s : popover avec stats + autres groupes/équipes.

if (!empty($user_id))
{
	// Liste tous les groupes de l'user pour identifier le rôle principal et les extras
	$user_groups_html = [];
	$primary_group_html = '';
	foreach ($this->groups() as $gid => $g)
	{
		if (!empty($g['users']) && in_array($user_id, $g['users']) && (!$g['hidden'] || ($this->access->effective_admin() && $this->url->admin)))
		{
			$label = (string)$this->groups->display($gid, TRUE);
			if ($primary_group_html === '')
			{
				$primary_group_html = $label;
			}
			else
			{
				$user_groups_html[] = $label;
			}
		}
	}

	// Contenu du popover (stats + autres groupes)
	$stats_str = $this->lang('%d sujet|%d sujets', $topics, $topics).' · '.$this->lang('%d réponse|%d réponses', $replies, $replies);
	$popover_html  = '<div class="forum-profile-popover">';
	$popover_html .= '<div class="mb-2"><i class="far fa-comment-dots"></i> '.htmlspecialchars(strip_tags($stats_str)).'</div>';
	if (!empty($user_groups_html))
	{
		$popover_html .= '<div class="forum-profile-popover-groups">'.implode(' ', $user_groups_html).'</div>';
	}
	$popover_html .= '</div>';
	$has_extras = !empty($user_groups_html) || $topics > 0 || $replies > 0;
?>
<div class="forum-profile<?php echo $has_extras ? ' forum-profile-has-popover' : '' ?>"
	<?php if ($has_extras): ?>
		data-bs-toggle="popover"
		data-bs-trigger="hover focus"
		data-bs-placement="right"
		data-bs-html="true"
		data-bs-delay='{"show":2000,"hide":150}'
		data-bs-content="<?php echo htmlspecialchars($popover_html, ENT_QUOTES) ?>"
	<?php endif ?>
>
	<div class="forum-profile-avatar">
		<?php echo $this->module('user')->model2('user', $user_id)->avatar()->append_attr('class', 'forum-profile-avatar-img') ?>
	</div>
	<div class="forum-profile-username"><?php echo $this->user->link($user_id, $username) ?></div>
	<?php if ($primary_group_html !== ''): ?>
		<div class="forum-profile-role"><?php echo $primary_group_html ?></div>
	<?php endif ?>
</div>
<?php
}
else
{
?>
<div class="forum-profile">
	<div class="forum-profile-avatar">
		<?php echo $this->module('user')->model2('user')->avatar()->append_attr('class', 'forum-profile-avatar-img') ?>
	</div>
	<div class="forum-profile-username"><i><?php echo $this->lang('Visiteur') ?></i></div>
</div>
<?php
}
?>
