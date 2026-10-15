// Les notifications push, côté membre (d11, 2026-10-16) : activer ou couper le push sur CET appareil, et s'envoyer un
// essai. Le navigateur demande son accord au membre ; sans lui, rien ne s'abonne.
//
// L'abonnement passe par le service worker du site (servi dès que le push est ouvert, cf. Settings\Ajax) ; il porte la
// clé publique VAPID du site, et seul ce site peut ensuite écrire à ce navigateur.
NF.ready(function(){
	var boite = document.querySelector('.nf-push');
	if (!boite || boite.dataset.nfPush){ return; }
	boite.dataset.nfPush = '1';

	var etat       = boite.querySelector('.nf-push-etat');
	var activer    = boite.querySelector('.nf-push-activer');
	var desactiver = boite.querySelector('.nf-push-desactiver');
	var essai      = boite.querySelector('.nf-push-essai');
	var appareils  = boite.querySelector('.nf-push-appareils');
	var libelles   = {};

	try { libelles = JSON.parse(boite.dataset.libelles || '{}'); } catch (e) { libelles = {}; }

	function dire(cle){ if (etat){ etat.textContent = libelles[cle] || ''; } }
	function montrer(bouton, oui){ if (bouton){ bouton.hidden = !oui; } }

	function compter(n){
		if (appareils && typeof n === 'number'){
			appareils.textContent = (libelles.appareils || '%d').replace('%d', String(n));
		}
	}

	function cle(base64){
		var b = (base64 + '='.repeat((4 - base64.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/');
		var brut = atob(b);
		var octets = new Uint8Array(brut.length);
		for (var i = 0; i < brut.length; i++){ octets[i] = brut.charCodeAt(i); }
		return octets;
	}

	function envoyer(adresse, corps){
		return fetch(adresse, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
			body: corps ? JSON.stringify(corps) : '{}'
		}).then(function(r){ return r.json(); });
	}

	if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)){
		dire('impossible');
		return;
	}

	if (Notification.permission === 'denied'){
		dire('refuse');
		return;
	}

	function inscription(){
		return navigator.serviceWorker.getRegistration().then(function(r){
			return r || navigator.serviceWorker.register(boite.dataset.worker);
		}).then(function(){
			return navigator.serviceWorker.ready;
		});
	}

	function afficher(){
		return inscription().then(function(r){ return r.pushManager.getSubscription(); }).then(function(abonnement){
			dire(abonnement ? 'actif' : 'inactif');
			montrer(activer, !abonnement);
			montrer(desactiver, !!abonnement);
			montrer(essai, !!abonnement);
		}).catch(function(){ dire('impossible'); });
	}

	if (activer){
		activer.addEventListener('click', function(){
			activer.disabled = true;
			Notification.requestPermission().then(function(choix){
				if (choix !== 'granted'){ dire(choix === 'denied' ? 'refuse' : 'inactif'); return null; }
				return inscription().then(function(r){
					return r.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: cle(boite.dataset.cle) });
				}).then(function(abonnement){
					return envoyer(boite.dataset.abonner, abonnement.toJSON());
				}).then(function(reponse){
					compter(reponse && reponse.appareils);
					if (!reponse || !reponse.ok){ dire('erreur'); }
				});
			}).catch(function(){ dire('erreur'); }).then(function(){
				activer.disabled = false;
				return afficher();
			});
		});
	}

	if (desactiver){
		desactiver.addEventListener('click', function(){
			desactiver.disabled = true;
			inscription().then(function(r){ return r.pushManager.getSubscription(); }).then(function(abonnement){
				if (!abonnement){ return null; }
				var adresse = abonnement.endpoint;
				return abonnement.unsubscribe().then(function(){ return envoyer(boite.dataset.desabonner, { endpoint: adresse }); });
			}).then(function(reponse){ compter(reponse && reponse.appareils); }).catch(function(){ dire('erreur'); }).then(function(){
				desactiver.disabled = false;
				return afficher();
			});
		});
	}

	if (essai){
		essai.addEventListener('click', function(){
			essai.disabled = true;
			envoyer(boite.dataset.essai).then(function(reponse){
				// Le service de push ne connaît plus cet abonnement : le serveur l'a oublié, le navigateur aussi.
				if (reponse && reponse.oublies){
					return inscription().then(function(r){ return r.pushManager.getSubscription(); }).then(function(a){
						return a ? a.unsubscribe() : null;
					}).then(function(){ return afficher(); }).then(function(){ dire('perime'); });
				}
				dire(reponse && reponse.ok ? 'essai' : 'erreur');
				return null;
			}).catch(function(){ dire('erreur'); }).then(function(){ essai.disabled = false; });
		});
	}

	afficher();
});
