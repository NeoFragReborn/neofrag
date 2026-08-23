NF.ready(function(){
	document.body.addEventListener('click', function(e){
		var trigger = e.target.closest('.form-file-delete');
		if (!trigger){ return; }
		e.preventDefault();

		var wrapper = document.createElement('div');
		wrapper.innerHTML = '\
			<div class="modal fade" role="dialog">\
				<div class="modal-dialog modal-sm">\
					<div class="modal-content">\
						<div class="modal-body">\
							<button type="button" class="btn-close float-end" data-bs-dismiss="modal" aria-label="<?php echo $this->lang('Fermer') ?>"></button>\
							<h4 class="modal-title"><?php echo $this->lang('Supprimer le fichier ?') ?></h4>\
							<div class="text-end" style="margin-top: 15px;">\
								<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $this->lang('Annuler') ?></button>\
								<button type="button" class="btn btn-danger"><?php echo $this->lang('Supprimer') ?></button>\
							</div>\
						</div>\
					</div>\
				</div>\
			</div>';

		var modalEl = wrapper.firstElementChild;
		document.body.appendChild(modalEl);
		bootstrap.Modal.getOrCreateInstance(modalEl).show();

		modalEl.addEventListener('hidden.bs.modal', function(){ modalEl.remove(); });

		modalEl.querySelector('.btn-danger').addEventListener('click', function(){
			var input = trigger.dataset.input;
			var field = document.querySelector('[name="' + input + '"]');
			if (field){
				var hidden = document.createElement('input');
				hidden.type  = 'hidden';
				hidden.name  = input;
				hidden.value = 'delete';
				field.parentNode.insertBefore(hidden, field);
			}

			var thumbnail = trigger.closest('.thumbnail');
			if (thumbnail && thumbnail.parentNode){ thumbnail.parentNode.remove(); }

			bootstrap.Modal.getOrCreateInstance(modalEl).hide();
		});
	});
});
