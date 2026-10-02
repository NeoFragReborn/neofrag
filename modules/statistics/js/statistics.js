NF.ready(function(){
	var updating = false;
	var chart    = null;

	// Lit une variable CSS de la charte admin (s'adapte au thème clair/sombre).
	function cssVar(name, fallback){
		var v = getComputedStyle(document.documentElement).getPropertyValue(name);
		v = (v || '').trim();
		return v || fallback;
	}

	// rgba(color, alpha) sans dépendance externe (gère #rgb, #rrggbb, rgb()/rgba()).
	function withAlpha(color, alpha){
		color = (color || '').trim();
		var m;
		if ((m = color.match(/^#([0-9a-f]{3})$/i))){
			color = '#' + m[1].replace(/./g, '$&$&');
		}
		if ((m = color.match(/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i))){
			return 'rgba(' + parseInt(m[1],16) + ',' + parseInt(m[2],16) + ',' + parseInt(m[3],16) + ',' + alpha + ')';
		}
		if ((m = color.match(/^rgba?\(([^)]+)\)/i))){
			var p = m[1].split(',').slice(0,3).map(function(x){ return x.trim(); });
			return 'rgba(' + p.join(',') + ',' + alpha + ')';
		}
		return color;
	}

	function theme(){
		var accent = cssVar('--nf-accent', '#03c1a2');
		return {
			palette : [accent, cssVar('--nf-info', '#3aa0ff'), cssVar('--nf-success', '#34c759'), cssVar('--nf-warning', '#ff9f0a')],
			text    : cssVar('--nf-text', '#e8edf2'),
			muted   : cssVar('--nf-muted', '#8a93a3'),
			grid    : cssVar('--nf-border-soft', 'rgba(255,255,255,.08)'),
			surface : cssVar('--nf-surface', '#141b27')
		};
	}

	// Dans la langue de la page, et non celle du navigateur (« 2 oct. 2025 » sur le site en français).
	function fmtDate(ms){
		return new Date(ms).toLocaleDateString(document.documentElement.lang || undefined, { day: 'numeric', month: 'short', year: 'numeric' });
	}

	var update = function(){
		if (updating){
			return;
		}

		updating = true;

		// Sérialise le formulaire avec ses names natifs (les champs portent déjà des `name[]` :
		// URLSearchParams(FormData) = la soumission native du form, sans ré-ajouter de crochets).
		var formEl = document.querySelector('form');
		var params = formEl ? new URLSearchParams(new FormData(formEl)) : new URLSearchParams();

		NF.ajax({ url: '<?php echo url('admin/ajax/statistics.json') ?>', method: 'POST', body: params }).then(function(series){
			var t = theme();
			series = series || [];

			// Au-delà de trois séries, les aires remplies se recouvrent : des lignes seules.
			var remplir = series.length <= 3;
			var minX = Infinity, maxX = -Infinity;
			series.forEach(function(s){
				(s.data || []).forEach(function(p){ minX = Math.min(minX, p[0]); maxX = Math.max(maxX, p[0]); });
			});

			var datasets = series.map(function(s, i){
				var c = s.color || t.palette[i % t.palette.length];

				return {
					label                : s.name,
					data                  : (s.data || []).map(function(p){ return { x: p[0], y: p[1] }; }),
					borderColor           : c,
					borderWidth           : 2.5,
					// Lissée sans dépasser les points : une courbe à 0 ne plonge pas sous l'axe.
					cubicInterpolationMode : 'monotone',
					fill                  : remplir,
					pointRadius           : 0,
					pointHoverRadius      : 4,
					pointHoverBorderWidth : 2,
					pointHoverBorderColor : c,
					pointHoverBackgroundColor : t.surface,
					// Dégradé vertical de remplissage (fondu vers le bas) — recalculé quand l'aire est connue.
					backgroundColor       : function(ctx){
						var area = ctx.chart.chartArea;
						if (!area){
							return withAlpha(c, 0.15);
						}
						var g = ctx.chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
						g.addColorStop(0, withAlpha(c, 0.28));
						g.addColorStop(1, withAlpha(c, 0));
						return g;
					}
				};
			});

			var cfg = {
				type: 'line',
				data: { datasets: datasets },
				options: {
					responsive: true,
					maintainAspectRatio: false,
					animation: { duration: 300 },
					interaction: { mode: 'index', intersect: false },
					plugins: {
						// Les cases à cocher du filtre, avec leur pastille de couleur, font office de légende.
						legend: { display: false },
						tooltip: {
							backgroundColor: t.surface,
							borderColor: t.grid,
							borderWidth: 1,
							titleColor: t.text,
							bodyColor: t.text,
							padding: 10,
							cornerRadius: 8,
							callbacks: {
								title: function(items){ return items.length ? fmtDate(items[0].parsed.x) : ''; }
							}
						}
					},
					scales: {
						x: {
							type: 'linear',
							// L'axe s'arrête aux bornes de la période, et non aux dates rondes d'à côté.
							min: isFinite(minX) ? minX : undefined,
							max: isFinite(maxX) ? maxX : undefined,
							grid: { display: false },
							border: { color: t.grid },
							ticks: {
								color: t.muted,
								font: { size: 11 },
								maxTicksLimit: 8,
								maxRotation: 0,
								autoSkip: true,
								callback: function(v){ return fmtDate(v); }
							}
						},
						y: {
							beginAtZero: true,
							grid: { color: t.grid },
							border: { display: false },
							ticks: { color: t.muted, font: { size: 11 }, precision: 0 }
						}
					}
				}
			};

			if (chart){
				chart.destroy();
			}

			var canvas = document.getElementById('stats-chart');
			if (canvas && typeof Chart !== 'undefined'){
				chart = new Chart(canvas, cfg);
			}
		}).catch(function(){}).finally(function(){
			updating = false;
		});
	};

	update();

	// flatpickr émet un `change` natif sur l'input (les anciens events changeDate/dp.change sont obsolètes).
	document.querySelectorAll('form input, form select, .date').forEach(function(el){
		el.addEventListener('change', update);
	});
});
