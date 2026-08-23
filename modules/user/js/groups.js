(function(){
	function init(){
		document.querySelectorAll('ul.groups input[type=checkbox]').forEach(function(checkbox){
			checkbox.addEventListener('change', function(){
				if (this.value === 'admins' || this.value === 'members'){
					var selector = this.value === 'admins'
						? 'ul.groups input[type=checkbox][value=members]'
						: 'ul.groups input[type=checkbox][value=admins]';
					var other = document.querySelector(selector);
					if (other){ other.checked = !this.checked; }
				}
			});
		});
	}
	if (document.readyState !== 'loading'){ init(); } else { document.addEventListener('DOMContentLoaded', init); }
})();
