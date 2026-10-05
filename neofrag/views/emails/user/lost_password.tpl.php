<?php
/**
 * Email de réinitialisation de mot de passe. Reçoit $user en data.
 */
?>
<p><?php echo $this->lang('Hello') ?> <?php echo nf_texte($user->username) ?>,</p>

<p><?php echo $this->lang('You requested a password reset. Click the button below to choose a new password.') ?></p>

<div class="text-center" style="text-align:center;margin:24px 0">
	<a class="btn btn-primary" href="<?php echo url('user/lost-password/'.$user->token()) ?>" style="display:inline-block;padding:10px 20px;background:#027a66;color:#fff;text-decoration:none;border-radius:4px"><?php echo $this->lang('Reset my password') ?></a>
</div>

<p style="color:#888;font-size:0.9em"><?php echo $this->lang('This link expires in 24 hours. If you didn\'t request this, ignore this email — your password will remain unchanged.') ?></p>
