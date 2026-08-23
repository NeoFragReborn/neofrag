// onloadCallback doit rester global : l'API reCAPTCHA l'invoque par son nom (param onload=onloadCallback).
window.onloadCallback = function(){
	function render(){
		document.querySelectorAll('.g-recaptcha').forEach(function(captcha){
			var data = {
				sitekey: "<?php echo $this->config->nf_captcha_public_key ?>"
			};

			['theme', 'size'].forEach(function(key){
				if (captcha.dataset[key]){
					data[key] = captcha.dataset[key];
				}
			});

			grecaptcha.render(captcha, data);
		});
	}

	if (document.readyState !== 'loading'){ render(); } else { document.addEventListener('DOMContentLoaded', render); }
};

(function(){
	function init(){
		var script = document.createElement('script');
		script.src = 'https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=<?php echo $this->config->lang->info()->name ?>&_=';
		document.head.appendChild(script);

		document.body.addEventListener('nf.load', function(){
			window.onloadCallback();
		});
	}
	if (document.readyState !== 'loading'){ init(); } else { document.addEventListener('DOMContentLoaded', init); }
})();
