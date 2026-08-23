// Requête vers neofr.ag (cross-origin) : pas de header X-Requested-With (préserve le comportement jQuery
// cross-domain et évite un preflight CORS).
document.body.addEventListener('nf.load', function(){
	var agents = Array.from(new Set(
		Array.from(document.querySelectorAll('[data-user-agent]'))
			.map(function(el){ return el.getAttribute('data-user-agent'); })
			.filter(function(a){ return a; })
	));

	if (!agents.length){ return; }

	var body = new URLSearchParams();
	agents.forEach(function(ua){ body.append('user_agent[]', ua); });

	fetch('https://neofr.ag/user-agent.json', {
		method: 'POST',
		body: body
	}).then(function(response){
		return response.json();
	}).then(function(data){
		Object.keys(data).forEach(function(userAgent){
			var entry  = data[userAgent];
			var output = '';
			var img    = '';
			var img2   = '';

			if (entry['agent_type'] === 'Browser'){
				if (entry['agent_name'] === 'Firefox'){
					img = '<?php echo image('icons/firefox.png') ?>';
				}
				else if (entry['agent_name'] === 'Chrome'){
					img = '<?php echo image('icons/chrome.png') ?>';
				}
				else if (entry['agent_name'] === 'Safari'){
					img = '<?php echo image('icons/safari.png') ?>';
				}
				else if (entry['agent_name'] === 'Internet Explorer'){
					img = '<?php echo image('icons/ie.png') ?>';
				}

				if (img){
					output += '<img src="' + img + '" data-bs-toggle="tooltip" title="' + entry['agent_name'] + ' ' + entry['agent_version'] + '" alt="" /> ';
				}
			}

			if (entry['os_type'] === 'Windows'){
				img2 = '<?php echo image('icons/windows.png') ?>';
			}
			else if (entry['os_type'] === 'Linux'){
				img2 = '<?php echo image('icons/animal-penguin.png') ?>';
			}
			else if (entry['os_type'] === 'Macintosh'){
				img2 = '<?php echo image('icons/mac-os.png') ?>';
			}

			if (img2){
				output += '<img src="' + img2 + '" data-bs-toggle="tooltip" title="' + entry['os_name'] + '" alt="" />';
			}

			if (output === ''){
				output += '<img src="<?php echo image('icons/user-silhouette-question.png') ?>" data-bs-toggle="tooltip" title="' + userAgent + '" alt="" />';
			}

			document.querySelectorAll('[data-user-agent="' + userAgent + '"]').forEach(function(el){
				var span = document.createElement('span');
				span.className = 'no-wrap';
				span.innerHTML = output;
				el.replaceWith(span);
			});
		});
	});
});
