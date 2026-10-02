// Le captcha du site : prépare chaque élément [data-nf-captcha] selon son fournisseur, y compris
// dans une fenêtre ouverte après coup (événement nf.load).
//   - ALTCHA : le widget est un élément <altcha-widget> qui se rend seul ; il faut lui donner son calcul
//     et ses textes. Le build strict n'embarque aucun worker (le build par défaut les crée en blob:, que
//     la CSP refuse) : on enregistre js/altcha/pbkdf2.js, de même origine, dont l'adresse est sur
//     l'élément. Ses textes, traduits par le site (data-textes), vont dans le registre des langues du
//     widget, sous le code de sa configuration : il n'embarque que l'anglais.
//   - Turnstile, hCaptcha, reCAPTCHA : leur script est chargé en rendu explicite et rappelle
//     window.nfCaptchaPret quand il est prêt ; chaque élément est rendu une fois.
(function(){
	var ALGORITHME = 'PBKDF2/SHA-256';

	function altcha(){
		if (!window.$altcha){
			return;
		}

		document.querySelectorAll('altcha-widget[data-textes]').forEach(function(element){
			var langue, textes;

			try {
				langue = JSON.parse(element.getAttribute('configuration') || '{}').language || '';
				textes = JSON.parse(element.getAttribute('data-textes'));
			} catch (e) {
				return;
			}

			if (langue && textes){
				window.$altcha.i18n.set(langue, Object.assign({}, window.$altcha.i18n.get('en'), textes));
			}
		});

		var registre = window.$altcha.algorithms;
		var element  = document.querySelector('altcha-widget[data-worker]');

		if (element && !registre.has(ALGORITHME)){
			var adresse = element.getAttribute('data-worker');

			registre.set(ALGORITHME, function(){
				return new Worker(adresse);
			});
		}
	}

	function tiers(){
		var api = {
			turnstile: window.turnstile,
			hcaptcha:  window.hcaptcha,
			recaptcha: window.grecaptcha
		};

		document.querySelectorAll('[data-nf-captcha]:not([data-nf-rendu])').forEach(function(element){
			var fournisseur = api[element.getAttribute('data-nf-captcha')];

			if (!fournisseur || typeof fournisseur.render !== 'function'){
				return;
			}

			var options = {sitekey: element.getAttribute('data-sitekey')};

			['theme', 'size'].forEach(function(cle){
				if (element.getAttribute('data-' + cle)){
					options[cle] = element.getAttribute('data-' + cle);
				}
			});

			if (element.getAttribute('data-nf-captcha') === 'turnstile' && element.getAttribute('data-language')){
				options.language = element.getAttribute('data-language');
			}

			fournisseur.render(element, options);
			element.setAttribute('data-nf-rendu', '');
		});
	}

	function tout(){
		altcha();
		tiers();
	}

	// Appelée par le script du fournisseur (paramètre onload) une fois son API prête.
	window.nfCaptchaPret = tiers;

	if (document.readyState !== 'loading'){
		tout();
	} else {
		document.addEventListener('DOMContentLoaded', tout);
	}

	document.addEventListener('nf.load', tout);
})();
