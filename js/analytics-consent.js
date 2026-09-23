// Google Analytics ne se charge qu'avec le consentement COMPLET du visiteur.
//
// La bannière de cookies (neofrag/views/theme/main.tpl.php) pose le cookie `nf_consent` — « full » ou
// « essentials » — et émet l'événement `nf:consent` avec ce niveau. Ce script, émis par
// theme/analytics.tpl.php quand aucun consentement complet n'est encore donné, attend cet événement :
// tant qu'il ne vient pas, ou s'il vient avec un autre niveau, aucune requête ne part vers Google.
//
// Le script de Google est inséré avec le nonce de la page (lu sur cette balise même), sans quoi la
// politique de sécurité stricte le refuserait — c'est aussi pour cela que index.php n'autorise l'origine
// googletagmanager.com que lorsqu'un identifiant Analytics est configuré.
(function(){
	var balise = document.currentScript;
	var id     = balise && balise.getAttribute('data-analytics-id');
	var nonce  = (balise && (balise.nonce || balise.getAttribute('nonce'))) || '';
	var charge = false;

	if (!id){
		return;
	}

	function charger(){
		if (charge){
			return;
		}

		charge = true;

		window.dataLayer = window.dataLayer || [];
		window.gtag = window.gtag || function(){ window.dataLayer.push(arguments); };
		window.gtag('js', new Date());
		window.gtag('config', id);

		var s = document.createElement('script');
		s.async = true;
		if (nonce){ s.setAttribute('nonce', nonce); }
		s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
		document.head.appendChild(s);
	}

	document.addEventListener('nf:consent', function(e){
		if (e.detail && e.detail.level === 'full'){
			charger();
		}
	});
})();
