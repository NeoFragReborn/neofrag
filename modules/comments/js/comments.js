(function(){
	function init(){
		document.querySelectorAll('[id^=comment-] a.comment-reply').forEach(function(link){
			link.addEventListener('click', function(e){
				e.preventDefault();
				var hidden = document.querySelector('input[type="hidden"][name$="[comment_id]"]');
				if (hidden){ hidden.value = this.dataset.commentId; }
				var label = document.querySelector('label[for$="[comment]"]');
				if (label){ label.innerHTML = '<?php echo $this->lang('Votre réponse') ?>'; }
				var textarea = document.querySelector('textarea[name$="[comment]"]');
				if (textarea){ textarea.focus(); }
			});
		});
	}
	if (document.readyState !== 'loading'){ init(); } else { document.addEventListener('DOMContentLoaded', init); }
})();
