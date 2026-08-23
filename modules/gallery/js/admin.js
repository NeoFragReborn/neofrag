/* Déclaration de la zone de téléchargement des images */
Dropzone.autoDiscover = false;

NF.ready(function(){
	if (!document.getElementById('gallery-dropzone')){ return; }

	new Dropzone('#gallery-dropzone', {
		dictDefaultMessage: '<div class="text-center"><h2><?php echo icon('fas fa-cloud-upload-alt') ?> DropZone</h2><p class="text-muted">Déposez vos images dans cette zone, ou cliquez ici</p></div>',
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
					submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Téléchargement en cours...';
					submitButton.disabled = true;
					myDropzone.processQueue();
				});
			}

			/* Event: quand un fichier est ajouté, on affiche le bouton d'upload */
			myDropzone.on('addedfile', function() {
				if (submitButton){
					submitButton.style.display = '';
					submitButton.innerHTML = '<?php echo icon('fas fa-cloud-upload-alt') ?> Ajouter les images';
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
						? '<i class="fas fa-spinner fa-spin"></i> Encore un tout petit instant...'
						: '<b><i class="fas fa-spinner fa-spin"></i> ' + Math.round(totalPercentage) + '%</b> Veuillez patienter...';
				});
				document.querySelectorAll('.progress-size').forEach(function(el){ el.innerHTML = sentsizeInMB + '/' + sizeInMB + ' Mo'; });
				showAll('.upload-infos');
			});
		}
	});
});
