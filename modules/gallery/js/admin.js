/* Déclaration de la zone de téléchargement des images */
Dropzone.autoDiscover = false;

NF.ready(function(){
	if (!document.getElementById('gallery-dropzone')){ return; }

	new Dropzone('#gallery-dropzone', {
		dictDefaultMessage: '<div class="text-center"><h2><?php echo icon('fas fa-cloud-upload-alt') ?> DropZone</h2><p class="text-muted"><?php echo addslashes($this->lang('Déposez vos images dans cette zone, ou cliquez ici')) ?></p></div>',
		/* Les textes de la bibliothèque (dropzone.js garde ses valeurs d'origine, en anglais) : ils
		   s'affichent à l'utilisateur — lien de retrait, refus d'un fichier trop lourd, erreur du
		   serveur — et passent donc par les traductions du module. Les {{…}} sont remplis par Dropzone. */
		dictFallbackMessage: '<?php echo addslashes($this->lang('Votre navigateur ne permet pas de déposer des fichiers par glisser-déposer.')) ?>',
		dictFallbackText: '<?php echo addslashes($this->lang('Utilisez le formulaire ci-dessous pour envoyer vos fichiers.')) ?>',
		dictFileTooBig: '<?php echo addslashes($this->lang('Fichier trop volumineux ({{filesize}} Mio). Taille maximale : {{maxFilesize}} Mio.')) ?>',
		dictInvalidFileType: '<?php echo addslashes($this->lang('Ce type de fichier n\'est pas accepté.')) ?>',
		dictResponseError: '<?php echo addslashes($this->lang('Le serveur a répondu avec le code {{statusCode}}.')) ?>',
		dictCancelUpload: '<?php echo addslashes($this->lang('Annuler')) ?>',
		dictCancelUploadConfirmation: '<?php echo addslashes($this->lang('Voulez-vous vraiment annuler cet envoi ?')) ?>',
		dictRemoveFile: '<?php echo addslashes($this->lang('Retirer')) ?>',
		dictMaxFilesExceeded: '<?php echo addslashes($this->lang('Vous ne pouvez plus envoyer d\'autres fichiers.')) ?>',
		addRemoveLinks: true,
		autoProcessQueue: false,
		parallelUploads: 20,
		init: function() {
			var myDropzone   = this;
			var submitButton = document.getElementById('gallery-dropzone-add');
			var progressBar  = document.querySelector('.progress-bar');

			function hideAll(selector){ document.querySelectorAll(selector).forEach(function(el){ el.style.display = 'none'; }); }
			function showAll(selector){ document.querySelectorAll(selector).forEach(function(el){ el.style.display = ''; }); }

			if (submitButton){ submitButton.style.display = 'none'; submitButton.disabled = false; }
			hideAll('.upload-infos');

			/* On lance l'upload sur clic du bouton */
			if (submitButton){
				submitButton.addEventListener('click', function() {
					submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo addslashes($this->lang('Téléchargement en cours...')) ?>';
					submitButton.disabled = true;
					myDropzone.processQueue();
				});
			}

			/* Event: quand un fichier est ajouté, on affiche le bouton d'upload */
			myDropzone.on('addedfile', function() {
				if (submitButton){
					submitButton.style.display = '';
					submitButton.innerHTML = '<?php echo icon('fas fa-cloud-upload-alt') ?> <?php echo addslashes($this->lang('Ajouter les images')) ?>';
					submitButton.disabled = false;
				}
				hideAll('.label-dropzone');
			});

			/* Event: fichier traité, on le retire de la DropZone */
			myDropzone.on('complete', function(file) {
				myDropzone.removeFile(file);
				if (myDropzone.getQueuedFiles().length > 0 && myDropzone.getUploadingFiles().length > 0) {
					myDropzone.processQueue();
				}
			});

			/* Event: quand un fichier est supprimé */
			myDropzone.on('removedfile', function() {
				if (myDropzone.getQueuedFiles().length === 0 && myDropzone.getUploadingFiles().length === 0) {
					if (submitButton){ submitButton.style.display = 'none'; }
					hideAll('.upload-infos');
					showAll('.label-dropzone');
				}
			});

			/* Event: quand tous les fichiers sont upload, on masque le bouton */
			myDropzone.on('queuecomplete', function() {
				if (submitButton){ submitButton.style.display = 'none'; }
				hideAll('.upload-infos');
				location.reload();
			});

			myDropzone.on('totaluploadprogress', function(totalPercentage, totalBytesToBeSent, totalBytesSent) {
				var sizeInMB     = (totalBytesToBeSent / (1024 * 1024)).toFixed(2);
				var sentsizeInMB = (totalBytesSent / (1024 * 1024)).toFixed(2);
				if (progressBar){ progressBar.style.width = totalPercentage + '%'; }
				document.querySelectorAll('.progress-percent').forEach(function(el){
					el.innerHTML = (totalPercentage === 100)
						? '<i class="fas fa-spinner fa-spin"></i> <?php echo addslashes($this->lang('Encore un tout petit instant...')) ?>'
						: '<b><i class="fas fa-spinner fa-spin"></i> ' + Math.round(totalPercentage) + '%</b> <?php echo addslashes($this->lang('Veuillez patienter...')) ?>';
				});
				document.querySelectorAll('.progress-size').forEach(function(el){ el.innerHTML = sentsizeInMB + '/' + sizeInMB + ' <?php echo addslashes($this->lang('Mo')) ?>'; });
				showAll('.upload-infos');
			});
		}
	});
});
