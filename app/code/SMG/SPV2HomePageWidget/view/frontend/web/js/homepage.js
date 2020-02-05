define([
	'uiComponent',
	'jquery'
], function (Component, $) {
	return Component.extend({
		initialize() {
			var count = 1868;
			var text = document.getElementById('countdown');
			var line1_get = document.getElementById("line-1-id");
			var line2_get = document.getElementById("line-2-id");
			var animationRan = false;

			function timer() {
				if (count >= (new Date()).getFullYear()) {
					return;
				}

				count = count + 1;
				text.innerHTML = count;

				setTimeout(function () {
					timer();
				}, 78);
			}

			//trigger the countdown animation
			$(window).scroll(function () {
				if (animationRan) {
					return;
				}

				var hT = $('#trigger-anim').offset().top;
				var hH = $('#trigger-anim').outerHeight();
				var wH = $(window).height();
				var wS = $(this).scrollTop();
				if (wS > (hT + hH - wH) && (hT > wS)) {
					animationRan = true;

					//Animate images fade in out
					fadeimg();

					// Animation countdown
					timer();

					// Animate line1
					line1_get.classList.add("line-1_anim");

					// Animate line 2
					setTimeout(function () {
						line2_get.classList.add("line-2_anim");
					}, 400);
				}
			});

			function fadeimg() {
				let current_img = 1,
					slides = Array.from(document.querySelectorAll('.season-img'));
					/**
					 * Invoke the first iteration immediately, instead of waiting the 3 seconds
					 */
					slides.forEach((slide, i) => {
						slide.style.opacity = 0;
					});
					slides[0].style.opacity = 1;

				const imgInt = setInterval(() => {
					if(current_img !== slides.length - 1) {
						current_img++;
						slides.forEach((slide, i) => {
							slide.style.opacity = 0;
						});
						slides[current_img].style.opacity = 1;
					} else {
						clearInterval(imgInt);
					}
				}, 3000);
			}
		}
	});
});
