<p class="text-muted small"><?php echo $this->lang('Ces fichiers sont dans /upload/talks/ sans qu’aucun message ne les joigne plus, ou le site ne les connaît pas. Ils viennent de conversations privées : seul leur nom sur le disque est montré.') ?></p>
<form action="<?php echo url($this->url->request) ?>" method="post">
	<ul class="list-unstyled small mb-2">
		<?php foreach ($orphelins ?? [] as $o): ?>
			<li>
				<label class="form-check">
					<input class="form-check-input" type="checkbox" name="purger_orphelins[]" value="<?php echo nf_texte($o['path']) ?>" checked />
					<code><?php echo nf_texte($o['path']) ?></code> · <?php echo human_size($o['size']) ?> · <?php echo timetostr('j M Y', $o['date']) ?>
				</label>
			</li>
		<?php endforeach ?>
	</ul>
	<button type="submit" class="btn btn-sm btn-danger"><?php echo icon('far fa-trash-alt').' '.$this->lang('Effacer les fichiers cochés') ?></button>
</form>
