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

			var thumbnail = trigger.closest('.nf-file-preview');
			if (thumbnail && thumbnail.parentNode){ thumbnail.parentNode.remove(); }

			bootstrap.Modal.getOrCreateInstance(modalEl).hide();
		});
	});
});

/*
 * Habillage francophone du champ de fichier.
 *
 * Le widget natif écrit son libellé dans la langue de l'INTERFACE DU NAVIGATEUR, pas dans celle
 * de la page : « Choose File / No file chosen » s'affichait donc en anglais au milieu d'une
 * administration entièrement en français, et aucune propriété CSS ne permet d'en changer le texte.
 *
 * L'input natif n'est ni remplacé ni simulé : il est simplement rendu transparent et posé par
 * dessus la vitrine (cf. css/form-file.css). Le clic l'atteint, le dialogue du système s'ouvre,
 * et le fichier part dans le formulaire exactement comme avant.
 */
NF.ready(function(){
	var SANS      = "<?php echo $this->lang('Aucun fichier sélectionné') ?>";
	var PARCOURIR = "<?php echo $this->lang('Parcourir') ?>";
	var PLUSIEURS = "<?php echo $this->lang('fichiers sélectionnés') ?>";

	document.querySelectorAll('input[type="file"]').forEach(function(input){
		if (input.dataset.nfFile){ return; }
		input.dataset.nfFile = '1';

		var zone = document.createElement('div');
		zone.className = 'nf-file';
		zone.style.position = 'relative';
		input.parentNode.insertBefore(zone, input);
		zone.appendChild(input);

		// Le masquage de l'input natif est pose ICI, par le script qui cree la vitrine, et non
		// laisse a la seule feuille de style : si celle-ci n'est pas appliquee (feuille absente,
		// cache du navigateur, theme qui la surcharge), le widget natif du navigateur reste
		// visible A COTE de la vitrine et le libelle apparait en double — « Parcourir... Aucun
		// fichier selectionne.ParcourirAucun fichier selectionne ». Un composant qui remplace un
		// element doit garantir lui-meme que l'original s'efface.
		input.style.position = 'absolute';
		input.style.inset    = '0';
		input.style.width    = '100%';
		input.style.height   = '100%';
		input.style.opacity  = '0';
		input.style.cursor   = 'pointer';
		input.style.zIndex   = '2';

		var vitrine = document.createElement('span');
		vitrine.className = 'nf-file-vitrine';

		var bouton = document.createElement('span');
		bouton.className = 'nf-file-bouton';
		bouton.textContent = PARCOURIR;

		var nom = document.createElement('span');
		nom.className = 'nf-file-nom';

		vitrine.appendChild(bouton);
		vitrine.appendChild(nom);
		zone.appendChild(vitrine);

		function refleter(){
			var fichiers = input.files;

			if (!fichiers || !fichiers.length){
				nom.textContent = SANS;
				nom.classList.add('is-vide');
				return;
			}

			nom.classList.remove('is-vide');
			nom.textContent = fichiers.length > 1
				? fichiers.length + ' ' + PLUSIEURS
				: fichiers[0].name;
		}

		input.addEventListener('change', refleter);
		refleter();
	});
});
