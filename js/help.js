(function(){
	function init(){
		document.querySelectorAll('a[data-help]').forEach(function(link){
			link.addEventListener('click', function(e){
				e.preventDefault();

				if (document.querySelector('.help.alert')){ return; }

				var self = this;
				fetch('<?php echo url() ?>' + self.dataset.help, {
					headers: {'X-Requested-With': 'XMLHttpRequest'}
				}).then(function(response){
					return response.text();
				}).then(function(data){
					var alerts = document.getElementById('alerts');
					if (!alerts){ return; }

					var column = document.createElement('div');
					column.className = 'col-12';
					column.innerHTML = '<div class="help alert alert-info alert-dismissible fade show">'
						+ '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>'
						+ '<h4 class="alert-heading"><?php echo icon('far fa-life-ring').' '.$this->lang('Aide') ?></h4>'
						+ data
						+ '</div>';
					alerts.appendChild(column);
				});
			});
		});
	}
	if (document.readyState !== 'loading'){ init(); } else { document.addEventListener('DOMContentLoaded', init); }
})();
