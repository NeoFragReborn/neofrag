/*
 * Le consentement du visiteur (2026-10-08) : la fenêtre « Gérer mes cookies », le bandeau, et les avis posés
 * à la place des contenus tiers. Le principe et le cookie : neofrag/helpers/consentement.php ; le gabarit :
 * neofrag/views/theme/consentement.tpl.php.
 *
 * Tout ce que le script sait vient du serveur, posé sur la fenêtre (data-*) : le nom et le chemin du
 * cookie, l'empreinte des services du bandeau, les services que le site propose et ceux déjà acceptés.
 *
 * Enregistrer un choix : le cookie d'abord, puis un signal au site, qui en garde la trace sans adresse IP
 * (la preuve du consentement, RGPD art. 7.1) — le site lit le cookie lui-même, rien d'autre n'est envoyé.
 * Un service accepté s'affiche aussitôt ; un service RETIRÉ recharge la page, seule façon sûre de
 * décharger ce qu'il avait déjà mis en place.
 *
 * L'événement `nf:consent` annonce le choix ({ services: [...] }) : la mesure d'audience l'attend
 * (js/analytics-consent.js), et un addon qui charge lui-même un service tiers peut faire de même.
 */
(function () {
	'use strict';

	var fenetre = document.getElementById('nf-consentement');

	if (!fenetre) {
		return;
	}

	var donnees = fenetre.dataset;
	var proposes = liste(donnees.nfProposes);
	var acceptes = liste(donnees.nfAcceptes);

	function liste(valeur) {
		return (valeur || '').split('-').filter(function (s) { return /^[a-z]+$/.test(s); });
	}

	function jeton() {
		if (/^[0-9a-f]{16}$/.test(donnees.nfJeton || '')) {
			return donnees.nfJeton;
		}

		var octets = new Uint8Array(8);
		(window.crypto || window.msCrypto).getRandomValues(octets);
		donnees.nfJeton = Array.prototype.map.call(octets, function (o) { return ('0' + o.toString(16)).slice(-2); }).join('');

		return donnees.nfJeton;
	}

	/** Le choix enregistré : cookie, trace sur le site, effets dans la page. */
	function enregistrer(services) {
		services = services.filter(function (s) { return proposes.indexOf(s) >= 0; });

		var retires = acceptes.filter(function (s) { return services.indexOf(s) < 0; });

		document.cookie = donnees.nfCookie + '=' + donnees.nfEmpreinte + '.' + jeton() + '.' + services.join('-') +
			';path=' + (donnees.nfChemin || '/') + ';max-age=' + (donnees.nfDuree || 15552000) + ';samesite=lax' +
			(location.protocol === 'https:' ? ';secure' : '');

		try {
			fetch(donnees.nfPreuve, { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }, keepalive: true });
		} catch (e) { /* la trace est un plus : le choix vaut sans elle */ }

		acceptes = services;
		document.dispatchEvent(new CustomEvent('nf:consent', { detail: { services: services.slice() } }));

		var bandeau = document.getElementById('nf-consentement-bandeau');
		if (bandeau) { bandeau.remove(); }
		if (fenetre.open) { fenetre.close(); }

		if (retires.length) {
			location.reload();
			return;
		}

		services.forEach(function (s) {
			document.querySelectorAll('.nf-tiers[data-nf-tiers="' + s + '"]').forEach(afficher);
		});
	}

	/** Un avis remplacé par le contenu qu'il gardait — ou, posé par un script, par ce que ce script charge. */
	function afficher(avis) {
		if (typeof avis.nfAfficher === 'function') {
			var suite = avis.nfAfficher;
			avis.nfAfficher = null;
			avis.remove();
			suite();
			return;
		}

		var modele = avis.querySelector('template');

		if (modele) {
			avis.replaceWith(modele.content.cloneNode(true));
		}
	}

	/**
	 * Pour un contenu qu'un script charge lui-même : `charger` tout de suite si le service est accepté, sinon
	 * l'avis du service dans `conteneur`, et `charger` quand le visiteur dit oui (cette fois, ou toujours).
	 */
	function demander(service, conteneur, charger) {
		if (acceptes.indexOf(service) >= 0) {
			charger();
			return;
		}

		var modele = document.getElementById('nf-tiers-modele');
		var noms   = {};

		try { noms = JSON.parse(modele.getAttribute('data-nf-services') || '{}')[service] || {}; } catch (e) { /* noms vides */ }

		var avis = modele.content.firstElementChild.cloneNode(true);
		avis.setAttribute('data-nf-tiers', service);
		avis.querySelectorAll('p, button').forEach(function (el) {
			el.childNodes.forEach(function (n) {
				if (n.nodeType === 3) {
					n.nodeValue = n.nodeValue.split('{nom}').join(noms.nom || service).split('{editeur}').join(noms.editeur || service);
				}
			});
		});
		avis.nfAfficher = charger;
		conteneur.appendChild(avis);
	}

	function ouvrir() {
		fenetre.querySelectorAll('input[name="nf-service"]').forEach(function (c) {
			c.checked = acceptes.indexOf(c.value) >= 0;
		});

		if (typeof fenetre.showModal === 'function') {
			fenetre.showModal();
		} else {
			fenetre.setAttribute('open', '');
		}
	}

	document.addEventListener('click', function (e) {
		var cible = e.target.closest ? e.target : null;

		if (!cible) {
			return;
		}

		var el;

		if (cible.closest('[data-nf-consentement-ouvrir], a[href="#nf-consentement"]')) {
			e.preventDefault();
			ouvrir();
		} else if ((el = cible.closest('[data-nf-consentement-tout]'))) {
			enregistrer(el.getAttribute('data-nf-consentement-tout') === '1' ? proposes.slice() : []);
		} else if (cible.closest('[data-nf-consentement-enregistrer]')) {
			enregistrer(Array.prototype.map.call(fenetre.querySelectorAll('input[name="nf-service"]:checked'), function (c) { return c.value; }));
		} else if ((el = cible.closest('[data-nf-tiers-afficher]'))) {
			afficher(el.closest('.nf-tiers'));
		} else if ((el = cible.closest('[data-nf-tiers-toujours]'))) {
			var service = el.closest('.nf-tiers').getAttribute('data-nf-tiers');
			if (proposes.indexOf(service) < 0) { proposes.push(service); }
			enregistrer(acceptes.concat([service]));
		}
	});

	/** Pour les scripts qui chargent eux-mêmes un service tiers (le lecteur du widget Twitch, la carte des lieux). */
	window.NF = window.NF || {};
	window.NF.consentement = {
		accepte: function (service) { return acceptes.indexOf(service) >= 0; },
		demander: demander,
		ouvrir: ouvrir
	};
})();
