NF.ready(function(){
	function fitLogo(img, withHeight){
		var box = img.parentNode.parentNode;
		img.style.transition = 'max-width 500ms ease, max-height 500ms ease, opacity 500ms ease';
		img.style.maxWidth = (box.offsetWidth - 20) + 'px';
		if (withHeight){ img.style.maxHeight = (box.offsetHeight - 30) + 'px'; }
		img.style.opacity = '0.7';
	}

	function resizeSlider(){
		document.querySelectorAll('.partner-item img.logo').forEach(function(img){ fitLogo(img, true); });
	}

	resizeSlider();

	document.querySelectorAll('.carousel.slide').forEach(function(carousel){
		carousel.addEventListener('slid.bs.carousel', resizeSlider);
	});

	document.querySelectorAll('.column-partners img.logo').forEach(function(img){ fitLogo(img, false); });

	document.querySelectorAll('.partner-item img.logo, .column-partners img.logo').forEach(function(img){
		img.addEventListener('mouseenter', function(){ img.style.opacity = '1'; });
		img.addEventListener('mouseleave', function(){ img.style.opacity = '0.7'; });
	});
});
