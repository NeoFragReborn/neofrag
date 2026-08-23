document.addEventListener('DOMContentLoaded', function(){
	var el = document.getElementById('calendar');
	if (!el){ return; }

	var calendar = new FullCalendar.Calendar(el, {
		height: 350,
		locale: '<?php echo NeoFrag()->config->lang->info()->name ?>',
		initialView: 'dayGridMonth',
		headerToolbar: { left: 'title', right: 'today prev,next' },
		events: '<?php echo url('ajax/events.json') ?>',
		// Les dates viennent en SQL ('YYYY-MM-DD HH:MM:SS') -> ISO pour le parseur v6.
		eventDataTransform: function(e){
			if (typeof e.start === 'string'){ e.start = e.start.replace(' ', 'T'); }
			if (typeof e.end === 'string'){ e.end = e.end.replace(' ', 'T'); }
			return e;
		},
		eventDidMount: function(info){
			var icon = info.event.extendedProps.icon;
			if (icon){
				var i = document.createElement('i');
				i.className = 'icon ' + icon + ' fa-fw';
				i.style.marginRight = '3px';
				var titleEl = info.el.querySelector('.fc-event-title');
				if (titleEl){ titleEl.parentNode.insertBefore(i, titleEl); }
				else { info.el.insertBefore(i, info.el.firstChild); }
			}

			var elx = info.el;

			elx.addEventListener('mouseenter', function(){
				if (bootstrap.Popover.getInstance(elx)){
					bootstrap.Popover.getOrCreateInstance(elx).show();
					return;
				}

				var cache = document.querySelector('.event-cache[data-event-id="' + info.event.id + '"]');

				if (cache){
					new bootstrap.Popover(elx, { content: cache.innerHTML, container: 'body', html: true }).show();
				}
				else {
					elx._ctrl = new AbortController();
					fetch('<?php echo url('ajax/events') ?>/' + info.event.id + '/' + info.event.extendedProps.url_title, {
						headers: { 'X-Requested-With': 'XMLHttpRequest' },
						credentials: 'same-origin',
						signal: elx._ctrl.signal
					})
					.then(function(r){ return r.text(); })
					.then(function(data){
						var cache = document.createElement('div');
						cache.setAttribute('data-event-id', info.event.id);
						cache.className = 'event-cache';
						cache.style.display = 'none';
						cache.innerHTML = data;
						document.body.appendChild(cache);
						new bootstrap.Popover(elx, { content: data, container: 'body', html: true }).show();
					})
					.catch(function(){});
				}
			});

			elx.addEventListener('mouseleave', function(){
				if (elx._ctrl){ elx._ctrl.abort(); }
				bootstrap.Popover.getOrCreateInstance(elx).hide();
			});
		}
	});

	calendar.render();
});
