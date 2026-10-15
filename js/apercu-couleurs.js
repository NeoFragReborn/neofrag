// L'aperçu des couleurs d'un thème, sur une vraie page du site (d10, 2026-10-15) — cf. nf_apercu_couleurs().
//
// Une page publique est chargée dans le cadre, dans le thème configuré. À chaque couleur changée, la feuille du
// thème y est remplacée par celle que le serveur génère avec les couleurs NON ENREGISTRÉES (`?apercu=1&…`) : c'est
// ce que donnerait l'enregistrement, teintes dérivées comprises. Les rapports de contraste suivent, avec les seuils
// du contrôle qui refusera l'enregistrement (4,5 pour le texte, 3 pour l'accent).
NF.ready(function(){
	document.querySelectorAll('.nf-apercu-couleurs').forEach(function(boite){
		if (boite.dataset.nfApercu){ return; }
		boite.dataset.nfApercu = '1';

		var cadre   = boite.querySelector('.nf-apercu-cadre');
		var page    = boite.querySelector('.nf-apercu-page');
		var ratios  = boite.querySelector('.nf-apercu-ratios');
		var feuille = boite.dataset.feuille || '';
		var defauts = {};

		try { defauts = JSON.parse(boite.dataset.defauts || '{}'); } catch (e) { defauts = {}; }

		// Les champs de la page : l'accent et le texte portent leur nom ; le fond s'appelle `background_color`, ou
		// `color` dans le formulaire d'arrière-plan des thèmes qui en ont un (celui qui porte l'image de FOND —
		// Forge et Granite ont aussi une couleur d'en-tête, dans un autre formulaire).
		function champ(cle){
			var direct = document.querySelector('input[name$="[' + cle + ']"]');
			if (direct || cle !== 'background_color'){ return direct; }

			var trouve = null;
			document.querySelectorAll('form').forEach(function(form){
				if (!trouve && form.querySelector('[name$="[background]"]')){
					trouve = form.querySelector('input[name$="[color]"]');
				}
			});
			return trouve;
		}

		var champs = { theme_color: champ('theme_color'), background_color: champ('background_color'), text_color: champ('text_color') };

		function hexa(valeur){
			valeur = (valeur || '').trim();
			if (/^#[0-9a-f]{3}$/i.test(valeur)){
				valeur = '#' + valeur[1] + valeur[1] + valeur[2] + valeur[2] + valeur[3] + valeur[3];
			}
			return /^#[0-9a-f]{6}$/i.test(valeur) ? valeur.toLowerCase() : '';
		}

		function couleurs(){
			var c = {};
			Object.keys(champs).forEach(function(cle){
				c[cle] = (champs[cle] && hexa(champs[cle].value)) || hexa(defauts[cle]) || '';
			});
			return c;
		}

		// Les mêmes calculs que neofrag/helpers/color.php (WCAG 2.1).
		function luminance(hex){
			var canaux = [1, 3, 5].map(function(i){
				var v = parseInt(hex.substr(i, 2), 16) / 255;
				return v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
			});
			return 0.2126 * canaux[0] + 0.7152 * canaux[1] + 0.0722 * canaux[2];
		}

		function contraste(a, b){
			var la = luminance(a), lb = luminance(b);
			return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
		}

		var nombre = new Intl.NumberFormat(document.documentElement.lang || undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 });

		function mesure(libelle, a, b, seuil){
			var bloc = document.createElement('span');
			if (!a || !b){ return bloc; }
			var r  = contraste(a, b);
			var ok = r + 0.01 >= seuil;
			var requis = document.createElement('span');
			bloc.className = 'me-3 ' + (ok ? 'text-success' : 'text-danger fw-bold');
			bloc.textContent = (ok ? '✓ ' : '✗ ') + libelle + ' : ' + nombre.format(r) + ' : 1 ';
			requis.className = 'text-muted fw-normal';
			requis.textContent = '(' + (boite.dataset.libelleRequis || '%s').replace('%s', nombre.format(seuil)) + ')';
			bloc.appendChild(requis);
			return bloc;
		}

		function appliquer(){
			var c = couleurs();

			if (ratios){
				ratios.replaceChildren(
					mesure(boite.dataset.libelleTexte || '', c.text_color, c.background_color, parseFloat(boite.dataset.seuilTexte || '4.5')),
					mesure(boite.dataset.libelleAccent || '', c.theme_color, c.background_color, parseFloat(boite.dataset.seuilAccent || '3'))
				);
			}

			var doc;
			try { doc = cadre.contentDocument; } catch (e) { doc = null; }
			if (!doc || !feuille){ return; }

			var lien = Array.prototype.find.call(doc.querySelectorAll('link[rel="stylesheet"]'), function(l){
				return (l.getAttribute('href') || '').split('?')[0].slice(-feuille.length) === feuille;
			});
			if (!lien){ return; }

			var params = ['apercu=1'];
			Object.keys(c).forEach(function(cle){
				if (c[cle]){ params.push(cle + '=' + encodeURIComponent(c[cle])); }
			});
			params.push('_=' + Date.now());

			// La nouvelle feuille se pose À CÔTÉ de l'ancienne, qui ne part qu'une fois la nouvelle chargée : pas de
			// page nue entre les deux.
			var neuve = lien.cloneNode();
			neuve.setAttribute('href', feuille + '?' + params.join('&'));
			neuve.addEventListener('load', function(){ if (lien.parentNode){ lien.parentNode.removeChild(lien); } });
			lien.parentNode.insertBefore(neuve, lien.nextSibling);
		}

		var attente = null;
		function plus_tard(){
			clearTimeout(attente);
			attente = setTimeout(appliquer, 250);
		}

		Object.keys(champs).forEach(function(cle){
			if (champs[cle]){
				champs[cle].addEventListener('input', plus_tard);
				champs[cle].addEventListener('change', plus_tard);
			}
		});

		cadre.addEventListener('load', appliquer);

		function charger(){ if (page){ cadre.setAttribute('src', page.value); } }
		if (page){ page.addEventListener('change', charger); }
		charger();
	});
});
