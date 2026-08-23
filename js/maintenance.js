// Compte à rebours de la page de maintenance (remplace jquery.countdown). Construit l'affichage
// jours/heures/minutes/secondes, décrémente chaque seconde, recharge la page à l'échéance.
(function(){
	function init(){
		var el = document.getElementById('countdown');
		if (!el){ return; }

		var timestamp = (parseInt(el.getAttribute('data-timestamp'), 10) || 0) * 1000;

		el.classList.add('countdownHolder');

		['Days', 'Hours', 'Minutes', 'Seconds'].forEach(function(name, i){
			var span = document.createElement('span');
			span.className = 'count' + name;
			span.innerHTML = '<span class="position"><span class="digit static">0</span></span>' +
							 '<span class="position"><span class="digit static">0</span></span>';
			el.appendChild(span);

			if (name !== 'Seconds'){
				var div = document.createElement('span');
				div.className = 'countDiv countDiv' + i;
				el.appendChild(div);
			}
		});

		var positions = el.querySelectorAll('.position');
		var DAYS = 24 * 60 * 60, HOURS = 60 * 60, MINUTES = 60;

		function switchDigit(position, number){
			if (!position || position._digit === number){ return; }
			position._digit = number;
			var digit = position.querySelector('.digit');
			if (digit){ digit.textContent = number; }
		}

		function updateDuo(minor, major, value){
			switchDigit(positions[minor], Math.floor(value / 10) % 10);
			switchDigit(positions[major], value % 10);
		}

		(function tick(){
			var left = Math.floor((timestamp - Date.now()) / 1000);
			if (left < 0){ left = 0; }

			var d = Math.floor(left / DAYS);    updateDuo(0, 1, d); left -= d * DAYS;
			var h = Math.floor(left / HOURS);   updateDuo(2, 3, h); left -= h * HOURS;
			var m = Math.floor(left / MINUTES); updateDuo(4, 5, m); left -= m * MINUTES;
			var s = left;                       updateDuo(6, 7, s);

			if (d === 0 && h === 0 && m === 0 && s === 0){
				location.reload();
				return;
			}

			setTimeout(tick, 1000);
		})();
	}

	if (document.readyState !== 'loading'){ init(); } else { document.addEventListener('DOMContentLoaded', init); }
})();
