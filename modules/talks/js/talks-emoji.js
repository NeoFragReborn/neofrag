// Emoji picker simple pour talks (Phase T8) — sans dépendance externe.
// Liste fixe de 64 emojis populaires + insertion à la position du curseur.
// GIFs : l'user colle une URL Giphy/Tenor, le render serveur les transforme en <img>.
(function(){
	'use strict';

	var EMOJIS = [
		'😀','😂','🤣','😅','😊','😎','😍','🥰',
		'😘','😋','😜','🤪','🤔','🤨','😐','😑',
		'😴','🤤','🥱','😪','😵','🤯','😱','😨',
		'😢','😭','😤','😠','🤬','🤢','🤮','🤧',
		'👍','👎','👌','✌️','🤞','🤟','🤘','👋',
		'👏','🙌','💪','🫡','🙏','🫶','❤️','🔥',
		'⭐','💯','🎉','🎊','🚀','💥','✨','💎',
		'🤝','👀','💭','💬','📣','🏆','🎯','🎮'
	];

	function insertAtCursor(textarea, text){
		var start = textarea.selectionStart;
		var end   = textarea.selectionEnd;
		var v     = textarea.value;
		textarea.value = v.substring(0, start) + text + v.substring(end);
		textarea.selectionStart = textarea.selectionEnd = start + text.length;
		textarea.focus();
	}

	function buildPicker(targetInput){
		var p = document.createElement('div');
		p.className = 'talks-emoji-picker';
		p.style.cssText = 'position:absolute;background:#fff;border:1px solid #ccc;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);padding:8px;display:none;z-index:9999;width:280px;font-size:20px;line-height:1.6;';

		EMOJIS.forEach(function(e){
			var b = document.createElement('span');
			b.textContent = e;
			b.style.cssText = 'cursor:pointer;display:inline-block;width:32px;text-align:center;border-radius:4px;';
			b.addEventListener('click', function(ev){
				ev.preventDefault();
				ev.stopPropagation();
				insertAtCursor(targetInput, e);
				p.style.display = 'none';
			});
			b.addEventListener('mouseenter', function(){ b.style.background = '#eef'; });
			b.addEventListener('mouseleave', function(){ b.style.background = ''; });
			p.appendChild(b);
		});

		// Aide GIF
		var hint = document.createElement('div');
		hint.style.cssText = 'border-top:1px solid #eee;margin-top:8px;padding-top:8px;font-size:11px;color:#666;line-height:1.4;';
		// eslint-disable-next-line no-restricted-syntax -- texte traduit côté serveur par lang(), sans aucune donnée saisie
		hint.innerHTML = '💡 ' + <?php echo json_encode((string) $this->lang('<b>GIF</b> : copie le lien d\'un GIF depuis %s ou %s et colle-le ici. Il sera affiché en image.', '<a href="https://giphy.com" target="_blank" rel="noopener">giphy.com</a>', '<a href="https://tenor.com" target="_blank" rel="noopener">tenor.com</a>')) ?>;
		p.appendChild(hint);

		document.body.appendChild(p);
		return p;
	}

	function attach(input){
		if (input._emojiAttached) return;
		input._emojiAttached = true;

		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'btn btn-light';
		btn.title = 'Emoji / GIF';
		btn.innerHTML = '😀';

		// Le bouton se pose juste avant le champ, en enfant DIRECT du groupe : c'est ainsi que
		// Bootstrap 5 soude les pièces d'un input-group. Il se glissait dans l'enveloppe
		// `input-group-prepend` de Bootstrap 4, retirée du balisage (2026-09-23), et portait des
		// couleurs écrites en dur — un carré blanc en mode sombre.
		if (input.closest('.input-group')) input.before(btn);

		var picker = buildPicker(input);

		btn.addEventListener('click', function(e){
			e.preventDefault();
			e.stopPropagation();
			if (picker.style.display === 'block'){
				picker.style.display = 'none';
				return;
			}
			var rect = btn.getBoundingClientRect();
			picker.style.top  = (rect.bottom + window.scrollY + 4) + 'px';
			picker.style.left = (rect.left + window.scrollX) + 'px';
			picker.style.display = 'block';
		});

		document.addEventListener('click', function(e){
			if (!picker.contains(e.target) && e.target !== btn){
				picker.style.display = 'none';
			}
		});
	}

	function init(){
		document.querySelectorAll('input[name="talk_message"]').forEach(attach);
	}

	if (document.readyState === 'loading'){
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
