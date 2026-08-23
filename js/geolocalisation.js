// Requête vers neofr.ag (cross-origin) : pas de header X-Requested-With (déclencherait un preflight CORS) ;
// jQuery ne l'ajoutait pas non plus en cross-domain.
document.body.addEventListener('nf.load', function(){
	var ips = Array.from(new Set(
		Array.from(document.querySelectorAll('[data-geolocalisation]'))
			.map(function(el){ return el.getAttribute('data-geolocalisation'); })
			.filter(function(a){ return a; })
	));

	if (!ips.length){ return; }

	var body = new URLSearchParams();
	ips.forEach(function(ip){ body.append('ip_address[]', ip); });

	fetch('https://neofr.ag/geolocalisation.json', {
		method: 'POST',
		body: body
	}).then(function(response){
		return response.json();
	}).then(function(data){
		Object.keys(data).forEach(function(ip){
			var entry = data[ip];
			var src = entry['flag']
				? '<?php echo url('images/flags') ?>/' + entry['flag']
				: '<?php echo image('icons/user-silhouette-question.png') ?>';

			document.querySelectorAll('[data-geolocalisation="' + ip + '"]').forEach(function(el){
				var img = document.createElement('img');
				img.src = src;
				img.setAttribute('data-bs-toggle', 'tooltip');
				img.setAttribute('title', entry['location']);
				img.style.marginRight = '10px';
				img.alt = '';
				el.replaceWith(img);
			});
		});
	});
});
