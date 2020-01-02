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

            function timer() {
                if (count >= (new Date()).getFullYear()) {
                    return;
                }

                count = count + 1;
                text.innerHTML = count;

                setTimeout(function () {
                    timer();
                }, 150);
            }

            //trigger the countdown animation
            $(window).scroll(function () {
                var hT = $('#trigger-anim').offset().top;
                var hH = $('#trigger-anim').outerHeight();
                var wH = $(window).height();
                var wS = $(this).scrollTop();
                if (wS > (hT + hH - wH) && (hT > wS)) {
                    //Animate images fade in out
                    fadeimg();

                    //Animate line1
                    line1_get.classList.add("line-1_anim");
                    timer();

                    //Animate line 2
                    setTimeout(function () {
                        line2_get.classList.add("line-2_anim");
                    }, 400);
                }
            });

            function fadeimg() {
                //for the images
                var current_img = 0,
                    slides = document.getElementsByClassName("season-img");

                let interval = setInterval(function () {
                    for (var i = 0; i < slides.length; i++) {
                        slides[i].style.opacity = 0;
                    }
                    slides[current_img].style.opacity = 1;
                    current_img++;

                    if (current_img === slides.length - 1) {
                        clearInterval(interval);
                    }
                }, 500);

            }
        }
    });
});
