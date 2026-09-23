<?php
/**
 * Email de validation d'inscription. Reçoit $user en data.
 */
?>
<p><?php echo $this->lang('Hello') ?> <?php echo htmlspecialchars($user->username) ?>,</p>

<p><?php echo $this->lang('To validate your registration on our site, please click the button below:') ?></p>

<div class="text-center" style="text-align:center;margin:24px 0">
	<a class="btn btn-primary" href="<?php echo url('user/validation/'.$user->token()) ?>" style="display:inline-block;padding:10px 20px;background:#027a66;color:#fff;text-decoration:none;border-radius:4px"><?php echo $this->lang('Validate my account') ?></a>
</div>

<p style="color:#888;font-size:0.9em"><?php echo $this->lang('If you didn\'t request this registration, ignore this email.') ?></p>
