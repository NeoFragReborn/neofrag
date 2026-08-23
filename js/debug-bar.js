NF.ready(function(){
	var bar = document.querySelector('.debug-bar');

	function paddingBody(){
		if (bar){ document.body.style.paddingBottom = bar.offsetHeight + 'px'; }
	}

	paddingBody();

	document.querySelectorAll('.debug-bar-tab').forEach(function(tabEl){
		tabEl.addEventListener('click', function(){
			if (this.classList.contains('active')){ return; }

			var tab = NF.data(this, 'debug-bar');
			if (bar){ bar.classList.add('active'); }

			document.querySelectorAll('.debug-bar-tab.active, .debug-bar-pane.active').forEach(function(el){
				el.classList.remove('active');
			});
			this.classList.add('active');

			var pane = document.querySelector('.debug-bar-pane[data-tab="' + tab + '"]');
			if (pane){ pane.classList.add('active'); }

			NF.post('<?php echo url('ajax/settings/debug-bar') ?>', { tab: tab });
			paddingBody();
		});
	});

	var resizing = false;
	var offset   = 0;

	function onMouseMove(e){
		if (!resizing){ return; }
		var height = offset - e.clientY;
		if (height > 200){
			var content = document.querySelector('.debug-bar-content');
			if (content){ content.style.height = height + 'px'; }
			paddingBody();
		}
	}

	document.querySelectorAll('.debug-bar-resize').forEach(function(handle){
		handle.addEventListener('mousedown', function(e){
			resizing = true;
			var nav  = document.querySelector('.debug-bar > nav');
			var navH = nav ? nav.offsetHeight : 0;
			var top  = handle.getBoundingClientRect().top + window.pageYOffset;
			offset   = window.innerHeight - navH + e.pageY - top;
			document.addEventListener('mousemove', onMouseMove);
		});
	});

	document.addEventListener('mouseup', function(){
		if (!resizing){ return; }
		var content = document.querySelector('.debug-bar-content');
		NF.post('<?php echo url('ajax/settings/debug-bar') ?>', { height: content ? content.clientHeight : 0 });
		resizing = false;
		document.removeEventListener('mousemove', onMouseMove);
	});

	document.querySelectorAll('.debug-bar-close').forEach(function(closeEl){
		closeEl.addEventListener('click', function(){
			document.querySelectorAll('.debug-bar.active, .debug-bar .active').forEach(function(el){
				el.classList.remove('active');
			});
			NF.post('<?php echo url('ajax/settings/debug-bar') ?>', { tab: '' });
			paddingBody();
		});
	});

	document.querySelectorAll('.dropdown-toggle').forEach(function(toggle){
		toggle.addEventListener('click', function(){
			var next = this.nextElementSibling;
			if (!next){ return; }
			var visible = getComputedStyle(next).display !== 'none';
			next.style.display = visible ? 'none' : 'block';
		});
	});

	document.querySelectorAll('.dropdown-menu.keep-open').forEach(function(menu){
		menu.addEventListener('click', function(e){ e.stopPropagation(); });
	});

	document.querySelectorAll('.console-filter').forEach(function(cf){
		cf.addEventListener('click', function(){
			var child = this.firstElementChild;
			if (!child){ return; }
			var filter = NF.data(this, 'filter');

			if (child.classList.contains('fa-square')){
				child.classList.remove('fa-square');
				child.classList.add('fa-check-square');
				document.querySelectorAll('.row-' + filter).forEach(function(r){ r.style.display = ''; });
			}
			else if (child.classList.contains('fa-check-square')){
				child.classList.remove('fa-check-square');
				child.classList.add('fa-square');
				document.querySelectorAll('.row-' + filter).forEach(function(r){ r.style.display = 'none'; });
			}
		});
	});
});
