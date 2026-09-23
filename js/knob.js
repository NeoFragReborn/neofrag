// Jauge circulaire SVG vanilla (remplace jquery.knob, jQuery+plugin). Lit les mêmes data-* que le
// plugin (thickness, angleArc, angleOffset, min, max, width, height, fgColor, displayInput). Auto-init
// des `.knob` sur nf.load. Chaque input expose `_nfKnobUpdate(value, fgColor)` pour les MAJ dynamiques.
(function(){
	function polar(cx, cy, r, deg){
		var a = (deg - 90) * Math.PI / 180; // 0° = haut
		return [cx + r * Math.cos(a), cy + r * Math.sin(a)];
	}

	function arcPath(cx, cy, r, startDeg, endDeg){
		if (endDeg - startDeg >= 359.999){ endDeg = startDeg + 359.999; } // cercle complet : éviter le path nul
		var s = polar(cx, cy, r, endDeg);
		var e = polar(cx, cy, r, startDeg);
		var large = (endDeg - startDeg) > 180 ? 1 : 0;
		return 'M ' + s[0].toFixed(2) + ' ' + s[1].toFixed(2) + ' A ' + r + ' ' + r + ' 0 ' + large + ' 0 ' + e[0].toFixed(2) + ' ' + e[1].toFixed(2);
	}

	function nfKnob(input){
		if (input._nfKnob){ return; }
		input._nfKnob = true;

		// `dataset` met TOUTES les cles en minuscules : l'attribut `data-angleArc` s'y appelle
		// `anglearc`, et `d.angleArc` vaut donc undefined. Trois options etaient lues ainsi —
		// angleArc, angleOffset, displayInput — et retombaient silencieusement sur leur defaut :
		// la jauge de la page de supervision se dessinait en cercle ENTIER, valeur affichee au
		// centre, au lieu du demi-cercle voulu. Signale le 2026-09-22.
		//
		// `getAttribute` est insensible a la casse sur un element HTML : il lit les deux ecritures,
		// `data-angleArc` comme `data-angle-arc`.
		var lire = function(nom, defaut){
			var v = input.getAttribute('data-' + nom);
			return v === null || v === '' ? defaut : v;
		};

		var min      = parseFloat(lire('min', '0'));
		var max      = parseFloat(lire('max', '100'));
		var arc      = parseFloat(lire('angleArc', '360'));
		var offset   = parseFloat(lire('angleOffset', '0'));
		var thick    = parseFloat(lire('thickness', '0.3'));
		var w        = parseFloat(lire('width', '100'));
		var h        = parseFloat(lire('height', String(w)));
		var fg       = lire('fgColor', '#29b6f6');
		var display  = lire('displayInput', '') !== 'false';

		var cx = w / 2, cy = h / 2;
		var radius = Math.min(w, h) / 2 * 0.82;
		var sw = Math.max(2, thick * radius);
		var r  = radius - sw / 2;

		var ns = 'http://www.w3.org/2000/svg';
		var svg = document.createElementNS(ns, 'svg');
		svg.setAttribute('width', w);
		svg.setAttribute('height', h);
		svg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);

		var track = document.createElementNS(ns, 'path');
		track.setAttribute('d', arcPath(cx, cy, r, offset, offset + arc));
		track.setAttribute('fill', 'none');
		track.setAttribute('stroke', 'rgba(127,127,127,.25)');
		track.setAttribute('stroke-width', sw);
		track.setAttribute('stroke-linecap', 'round');
		svg.appendChild(track);

		var fgPath = document.createElementNS(ns, 'path');
		fgPath.setAttribute('fill', 'none');
		fgPath.setAttribute('stroke-width', sw);
		fgPath.setAttribute('stroke-linecap', 'round');
		svg.appendChild(fgPath);

		var label = null;
		if (display){
			label = document.createElementNS(ns, 'text');
			label.setAttribute('x', cx);
			label.setAttribute('y', cy);
			label.setAttribute('text-anchor', 'middle');
			label.setAttribute('dominant-baseline', 'central');
			label.setAttribute('font-size', Math.round(radius * 0.5));
			label.setAttribute('fill', 'currentColor');
			svg.appendChild(label);
		}

		function render(value, color){
			value = Math.max(min, Math.min(max, value));
			var ratio = max > min ? (value - min) / (max - min) : 0;
			fgPath.setAttribute('d', arcPath(cx, cy, r, offset, offset + arc * ratio));
			fgPath.setAttribute('stroke', color || fg);
			if (label){ label.textContent = Math.round(value); }
		}

		input.style.display = 'none';
		input.parentNode.insertBefore(svg, input);

		input._nfKnobUpdate = function(value, color){ render(parseFloat(value), color); };
		render(parseFloat(input.value || '0'), fg);
	}

	function initAll(){
		document.querySelectorAll('input.knob').forEach(nfKnob);
	}

	window.nfKnob = nfKnob;

	document.body.addEventListener('nf.load', initAll);
	if (document.readyState !== 'loading'){ initAll(); } else { document.addEventListener('DOMContentLoaded', initAll); }
})();
