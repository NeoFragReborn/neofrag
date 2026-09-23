/**
 * Harnais de test JS — le strict nécessaire pour qu'une page d'épreuve rende un verdict lisible
 * par tools/check-js.php.
 *
 * Il existe parce que certaines propriétés du front ne s'observent QUE dans un navigateur : ce qui
 * s'exécute ou non, dans quel document, avec quel nonce CSP. Aucun test PHP ne les voit, et le
 * projet n'a pas de pile de test JS (pas de package.json — décision assumée, cf. TODO §3). Plutôt
 * que d'installer un écosystème entier, ces soixante lignes suffisent.
 *
 * Écrire une épreuve : créer `tests/Browser/<sujet>.test.html`, charger `/harness.js` puis ce qu'il
 * faut (`/nf.js` sert le module window.NF extrait du vrai gabarit), et appeler :
 *
 *     NFTest.verifie('ce qui doit être vrai', attendu, obtenu);
 *     NFTest.echec('pourquoi on abandonne');   // erreur franche
 *     NFTest.fini();                            // rend le verdict — TOUJOURS l'appeler
 *
 * Une épreuve asynchrone appelle `NFTest.fini()` depuis son callback. Sans appel à `fini()`, le
 * contrôle échoue en disant que la page n'a pas rendu de verdict — jamais un succès silencieux.
 */
window.NFTest = (function () {
	var lignes = [];
	var echecs = 0;

	function ecrire(texte) {
		var el = document.getElementById('nf-verdict');

		if (!el) {
			el = document.createElement('pre');
			el.id = 'nf-verdict';
			document.body.appendChild(el);
		}

		el.textContent = texte;
	}

	function verifie(nom, attendu, obtenu) {
		var ok;

		try {
			ok = JSON.stringify(attendu) === JSON.stringify(obtenu);
		}
		catch (e) {
			ok = attendu === obtenu; // valeur non sérialisable (nœud DOM, fonction…)
		}

		if (ok) {
			lignes.push('  OK    ' + nom);
		}
		else {
			echecs++;
			lignes.push('  ECHEC ' + nom);
			lignes.push('          attendu : ' + reduire(attendu));
			lignes.push('          obtenu  : ' + reduire(obtenu));
		}

		return ok;
	}

	function reduire(v) {
		try {
			var s = JSON.stringify(v);
			return s === undefined ? String(v) : s;
		}
		catch (e) {
			return String(v);
		}
	}

	function echec(raison) {
		echecs++;
		lignes.push('  ECHEC ' + raison);
	}

	function fini() {
		lignes.push('');
		lignes.push(echecs === 0
			? 'TOUT PASSE'
			: echecs + ' ECHEC(S)');

		ecrire(lignes.join('\n'));
		document.title = echecs === 0 ? 'NF-VERT' : 'NF-ROUGE';
	}

	// Une erreur JS non rattrapée doit faire ÉCHOUER l'épreuve, pas la laisser muette.
	window.addEventListener('error', function (e) {
		echec('erreur JS non rattrapée : ' + (e.message || e.type));
		fini();
	});

	return { verifie: verifie, echec: echec, fini: fini };
})();
