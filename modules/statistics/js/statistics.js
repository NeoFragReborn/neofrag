$(function(){
	var updating = false;

	// Lit une variable CSS de la charte admin (s'adapte au thème clair/sombre)
	function cssVar(name, fallback){
		var v = getComputedStyle(document.documentElement).getPropertyValue(name);
		v = (v || '').trim();
		return v || fallback;
	}

	function withAlpha(color, alpha){
		try { return Highcharts.color(color).setOpacity(alpha).get('rgba'); }
		catch (e){ return color; }
	}

	function theme(){
		var accent = cssVar('--nf-accent', '#03c1a2');
		return {
			palette : [accent, cssVar('--nf-info', '#3aa0ff'), cssVar('--nf-success', '#34c759'), cssVar('--nf-warning', '#ff9f0a')],
			text    : cssVar('--nf-text', '#e8edf2'),
			muted   : cssVar('--nf-muted', '#8a93a3'),
			grid    : cssVar('--nf-border-soft', 'rgba(255,255,255,.08)'),
			surface : cssVar('--nf-surface', '#141b27'),
			font    : cssVar('--nf-font', 'inherit')
		};
	}

	var update = function(){
		if (updating){
			return;
		}

		updating = true;

		var data = {};

		$.each($('form').serializeArray(), function(){
			if (data[this.name] !== undefined){
				if (!data[this.name].push){
					data[this.name] = [data[this.name]];
				}

				data[this.name].push(this.value || '');
			}
			else {
				data[this.name] = this.value || '';
			}
		});

		$.post('<?php echo url('admin/ajax/statistics.json') ?>', data, function(series){
			var t = theme();

			(series || []).forEach(function(s, i){
				var c = s.color || t.palette[i % t.palette.length];
				s.type      = 'areaspline';
				s.color     = c;
				s.lineWidth = 2.5;
				s.fillColor = {
					linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
					stops: [[0, withAlpha(c, 0.28)], [1, withAlpha(c, 0)]]
				};
				s.marker = { enabled: false, radius: 3, states: { hover: { enabled: true, radius: 4, lineWidth: 2 } } };
			});

			$('#highcharts').highcharts({
				chart: {
					type: 'areaspline',
					backgroundColor: 'transparent',
					zoomType: 'x',
					style: { fontFamily: t.font },
					spacing: [10, 4, 6, 4]
				},
				title:   { text: null },
				credits: { enabled: false },
				legend: {
					enabled: (series || []).length > 1,
					itemStyle:      { color: t.muted, fontWeight: '600' },
					itemHoverStyle: { color: t.text }
				},
				xAxis: {
					type: 'datetime',
					lineColor: t.grid,
					tickColor: t.grid,
					gridLineWidth: 0,
					labels: { style: { color: t.muted, fontSize: '11px' } }
				},
				yAxis: {
					min: 0,
					title: { text: null },
					gridLineColor: t.grid,
					gridLineDashStyle: 'Dash',
					labels: { style: { color: t.muted, fontSize: '11px' } }
				},
				tooltip: {
					shared: true,
					backgroundColor: t.surface,
					borderColor: t.grid,
					borderRadius: 8,
					shadow: false,
					style: { color: t.text },
					xDateFormat: '%e %b %Y'
				},
				plotOptions: {
					areaspline: { states: { hover: { lineWidth: 3 } } },
					series: { marker: { enabled: false } }
				},
				series: series
			});
		}).always(function(){
			updating = false;
		});
	};

	update();
	$('form input, form select, .date').on('change changeDate dp.change', update);
});
