/**
 * NeoFrag — les images collées (Ctrl+V) ou glissées dans l'éditeur riche (TinyMCE), envoyées au site.
 *
 * TinyMCE garde une image collée sous une adresse `blob:` le temps de l'envoyer. Rien ne l'envoyait :
 * elle restait cassée à l'écran, puis partait en `data:` à l'enregistrement — que l'assainisseur du site
 * retire (signalé le 2026-10-04 sur une réponse du forum). Chaque éditeur reçoit désormais, de
 * Editeur_Images::tinymce() côté PHP, un `images_upload_handler` qui appelle `NFEditeur.televerser()` :
 *
 *   - POST du fichier (`file`) et du jeton de la session (`_`) vers `ajax/user/editeur-image`, cookies
 *     compris ; le site rend `{"location": "/upload/editeur/…"}`, que TinyMCE met à la place du `blob:`,
 *     ou `{"error": "<message traduit>"}` ;
 *   - un refus connu d'avance (visiteur, démonstration) ne fait aucune requête ;
 *   - en cas d'échec, l'image est RETIRÉE de l'éditeur (`remove: true`) et le motif s'affiche : jamais
 *     d'image cassée laissée dans le texte.
 *
 * Et un garde : un formulaire envoyé alors qu'une image part encore attend la fin de l'envoi — sinon
 * TinyMCE l'enregistrerait en `data:`, et elle serait perdue.
 *
 * Épreuve : tests/Browser/editeur-images.test.html.
 */
(function () {
	'use strict';

	/** Délai au-delà duquel un envoi est abandonné : 5 Mo sur une connexion lente. */
	var DELAI = 120000;

	var prefixeRetire = false;

	/**
	 * TinyMCE préfixe le motif d'un échec par « Failed to upload image: », en anglais quelle que soit la
	 * langue du site (le produit ne livre pas ses packs de langue). Le motif, déjà traduit par le site,
	 * se suffit : on retire le préfixe de la langue par défaut de l'éditeur.
	 */
	function retirerPrefixe() {
		if (!prefixeRetire && window.tinymce && typeof window.tinymce.addI18n === 'function') {
			window.tinymce.addI18n('en', { 'Failed to upload image: {0}': '{0}' });
			prefixeRetire = true;
		}
	}

	/**
	 * L'envoi d'une image. `reglages` : { url, jeton, refus, echec } — `refus`, s'il est rempli, est le
	 * motif d'un refus connu d'avance ; `echec`, le message d'une panne sans réponse lisible.
	 */
	function televerser(fichier, progression, reglages) {
		retirerPrefixe();

		return new Promise(function (resoudre, rejeter) {
			if (reglages.refus) {
				rejeter({ message: reglages.refus, remove: true });
				return;
			}

			var xhr = new XMLHttpRequest();

			xhr.open('POST', reglages.url);
			xhr.withCredentials = true;
			xhr.timeout = DELAI;

			if (xhr.upload && typeof progression === 'function') {
				xhr.upload.onprogress = function (e) {
					if (e.lengthComputable && e.total > 0) {
						progression(e.loaded / e.total * 100);
					}
				};
			}

			xhr.onload = function () {
				var reponse = lire(xhr.responseText);

				if (xhr.status === 200 && reponse && typeof reponse.location === 'string' && reponse.location !== '') {
					resoudre(reponse.location);
					return;
				}

				rejeter({ message: reponse && typeof reponse.error === 'string' && reponse.error !== '' ? reponse.error : reglages.echec, remove: true });
			};

			xhr.onerror = xhr.ontimeout = function () {
				rejeter({ message: reglages.echec, remove: true });
			};

			var donnees = new FormData();
			donnees.append('file', fichier.blob(), fichier.filename());
			donnees.append('_', reglages.jeton);

			xhr.send(donnees);
		});
	}

	/** La réponse du site, ou null si elle n'est pas du JSON (une page d'erreur, une coupure). */
	function lire(texte) {
		try {
			return JSON.parse(texte);
		}
		catch (erreur) {
			return null;
		}
	}

	/** Un éditeur a-t-il encore une image en cours d'envoi (une adresse `blob:`) ? */
	function enAttente(editeur) {
		var corps = typeof editeur.getBody === 'function' ? editeur.getBody() : null;

		return !!(corps && corps.querySelector('img[src^="blob:"]'));
	}

	// Le garde du formulaire, en phase de capture : il passe avant TinyMCE et avant tout autre écouteur.
	document.addEventListener('submit', function (e) {
		var formulaire = e.target;

		if (!window.tinymce || !formulaire || formulaire.nfEditeurPret) {
			return;
		}

		var envois = [];

		window.tinymce.get().forEach(function (editeur) {
			if (editeur.formElement === formulaire && enAttente(editeur)) {
				envois.push(editeur.uploadImages());
			}
		});

		if (!envois.length) {
			return;
		}

		e.preventDefault();
		e.stopImmediatePropagation();

		var bouton = e.submitter && e.submitter.form === formulaire ? e.submitter : null;

		Promise.all(envois).catch(function () {
			return null;
		}).then(function () {
			// Une seule reprise : une image restée en `blob:` (envoi en échec) ne bloque pas le formulaire.
			formulaire.nfEditeurPret = true;

			try {
				if (typeof formulaire.requestSubmit === 'function') {
					formulaire.requestSubmit(bouton || undefined);
				}
				else {
					formulaire.submit();
				}
			}
			finally {
				formulaire.nfEditeurPret = false;
			}
		});
	}, true);

	window.NFEditeur = { televerser: televerser };
})();
