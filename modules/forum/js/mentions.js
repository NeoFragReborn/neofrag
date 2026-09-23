// Forum mentions autocomplete (@username) — Phase 4-bis
// Léger sans dépendance externe : trigger sur '@', popup suggestion, fetch ajax JSON.
// Compatible textarea (utile pour edit raw); le WYSIBB iframe est out of scope MVP.
(function(){
	'use strict';

	// L'URL pattern NeoFrag pour les ajax modules : /<lang>/ajax/<module>/<route>
	// Détecte le préfixe lang depuis pathname courant.
	var langMatch = location.pathname.match(/^\/([a-z]{2,5})\//);
	var langPrefix = langMatch ? '/' + langMatch[1] : '';
	var ENDPOINT = window.NF_FORUM_AUTOCOMPLETE_URL || (langPrefix + '/ajax/forum/mentions/autocomplete');
	var MIN_CHARS = 1;
	var MAX_RESULTS = 8;

	function debounce(fn, ms){
		var t;
		return function(){
			var args = arguments, ctx = this;
			clearTimeout(t);
			t = setTimeout(function(){ fn.apply(ctx, args); }, ms);
		};
	}

	function ensurePopup(textarea){
		var p = textarea._mentionsPopup;
		if (!p){
			p = document.createElement('div');
			p.className = 'forum-mentions-popup';
			p.style.cssText = 'position:absolute;background:#fff;border:1px solid #ccc;border-radius:4px;box-shadow:0 4px 12px rgba(0,0,0,0.15);min-width:200px;z-index:9999;display:none;max-height:240px;overflow:auto;font-size:14px;';
			document.body.appendChild(p);
			textarea._mentionsPopup = p;
		}
		return p;
	}

	function getCaretCoords(textarea){
		// Approximation : utilise position du textarea + ligne courante
		var rect = textarea.getBoundingClientRect();
		var lineHeight = parseInt(getComputedStyle(textarea).lineHeight) || 20;
		var lines = textarea.value.substr(0, textarea.selectionStart).split('\n');
		var line = lines.length;
		return {
			top:  rect.top + window.scrollY + (line * lineHeight) + 4,
			left: rect.left + window.scrollX + 16
		};
	}

	function getCurrentMention(textarea){
		var pos = textarea.selectionStart;
		var text = textarea.value.substr(0, pos);
		// `-` en fin de classe est déjà littéral : l'échapper n'ajoutait rien et brouillait la lecture.
		var match = text.match(/@([a-zA-Z0-9_-]*)$/);
		if (match){
			return { prefix: match[1], start: pos - match[0].length };
		}
		return null;
	}

	function renderPopup(p, suggestions, onPick){
		if (!suggestions.length){
			p.style.display = 'none';
			return;
		}
		p.innerHTML = '';
		suggestions.forEach(function(s, i){
			var item = document.createElement('div');
			item.className = 'forum-mentions-item';
			item.textContent = '@' + s.username;
			item.style.cssText = 'padding:6px 12px;cursor:pointer;' + (i === 0 ? 'background:#eef;' : '');
			item.addEventListener('mouseenter', function(){
				p.querySelectorAll('.forum-mentions-item').forEach(function(it){ it.style.background = ''; });
				item.style.background = '#eef';
			});
			item.addEventListener('mousedown', function(e){
				e.preventDefault();
				onPick(s);
			});
			p.appendChild(item);
		});
		p.style.display = 'block';
	}

	function fetchSuggestions(prefix, cb){
		var xhr = new XMLHttpRequest();
		xhr.open('GET', ENDPOINT + '?q=' + encodeURIComponent(prefix), true);
		xhr.onload = function(){
			if (xhr.status >= 200 && xhr.status < 300){
				try { cb(JSON.parse(xhr.responseText)); } catch(e){ cb([]); }
			} else {
				cb([]);
			}
		};
		xhr.onerror = function(){ cb([]); };
		xhr.send();
	}

	function attach(textarea){
		if (textarea._mentionsAttached) return;
		textarea._mentionsAttached = true;

		var p = ensurePopup(textarea);

		var update = debounce(function(){
			var ctx = getCurrentMention(textarea);
			if (!ctx || ctx.prefix.length < MIN_CHARS){
				p.style.display = 'none';
				return;
			}
			fetchSuggestions(ctx.prefix, function(suggestions){
				suggestions = suggestions.slice(0, MAX_RESULTS);
				var coords = getCaretCoords(textarea);
				p.style.top  = coords.top + 'px';
				p.style.left = coords.left + 'px';
				renderPopup(p, suggestions, function(picked){
					var pos = textarea.selectionStart;
					var before = textarea.value.substr(0, ctx.start);
					var after  = textarea.value.substr(pos);
					var insert = '@' + picked.username + ' ';
					textarea.value = before + insert + after;
					textarea.selectionStart = textarea.selectionEnd = before.length + insert.length;
					p.style.display = 'none';
					textarea.focus();
				});
			});
		}, 180);

		textarea.addEventListener('input', update);
		textarea.addEventListener('keyup', update);
		textarea.addEventListener('blur', function(){
			setTimeout(function(){ p.style.display = 'none'; }, 150);
		});
	}

	function init(){
		document.querySelectorAll('textarea.editor, textarea[name*="message"]').forEach(attach);
	}

	if (document.readyState === 'loading'){
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
