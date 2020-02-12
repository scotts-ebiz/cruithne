require(['jquery'], function($){
	function Menu($header) {
		var self = this;
		this.$header = $header;

		this.init = function () {
			this.$toggle = this.$header.find('.sp-header__nav-toggle');

			$(document).on('click', '.sp-header__nav-toggle', function(e) {
				self.$header.toggleClass('sp-is-active');
				$('body').toggleClass('sp-menu-active');

				if (self.$toggle.attr('aria-expanded') === 'true') {
					self.$toggle.attr('aria-expanded', false);
				} else {
					self.$toggle.attr('aria-expanded', true);
				}
			})
		}
	}

	$(document).ready(function () {
		var $header = $('#sp-header');
		if ($header.length) {
			var menu = new Menu($('#sp-header'));
			menu.init();
		}

		//account navigation
		const accountNav = document.querySelector('.account-nav');
		if(accountNav) {
			const toggle = document.createElement('span');
			const current = document.createElement('div');
			const currentText = accountNav.querySelector('.current').innerText;
			
			current.classList.add('current-text');
			toggle.classList.add('toggle-arrow');

			current.innerText = currentText;

			accountNav.appendChild(current);
			accountNav.appendChild(toggle);

			toggle.addEventListener('click', (event) => {
				event.preventDefault();
				accountNav.classList.contains('active') ? accountNav.classList.remove('active') : accountNav.classList.add('active');
			})
		}
	});
});
