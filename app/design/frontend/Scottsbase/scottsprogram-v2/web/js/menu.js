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
	});
});
