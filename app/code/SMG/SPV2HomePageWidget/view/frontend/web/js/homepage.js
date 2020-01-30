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

			//Seasons animation
			//It focuses on the summer initialy (when the page load)
			//get the door and each window image
			var door = document.getElementById("door_img");
			var window1 = document.getElementById("window1_img");
			var window2 = document.getElementById("window2_img");
			var windowBig = document.getElementById("windowBig_img");
			//get seasons
			//var group7 = document.getElementById("Group-7");
			var text_early_spring = document.getElementById("e_spring_txt");
			var text_late_spring = document.getElementById("l_spring_txt");
			var text_summer = document.getElementById("summer_txt");
			var text_fall = document.getElementById("fall_txt");

			//frame_circle
			var circle_frame = document.getElementById("Oval");

			var fl = document.getElementById("Fall");
			var sm = document.getElementById("Summer");
			var ls = document.getElementById("LateSpring");
			var es = document.getElementById("EarlySpring");

			var es_stk_1 = document.getElementById("es-Stroke-1");
			var es_stk_2 = document.getElementById("es-Stroke-3");
			var es_stk_3 = document.getElementById("es-Stroke-5");
			var es_stk_4 = document.getElementById("es-Stroke-7");
			var es_stk_5 = document.getElementById("es-Stroke-9");

			var ls_stk_1 = document.getElementById("ls-Stroke-1");
			var ls_stk_2 = document.getElementById("ls-Stroke-3");
			var ls_stk_3 = document.getElementById("ls-Stroke-5");
			//var ls_stk_4 = document.getElementById("ls-Stroke-7");

			var fl_stk_1 = document.getElementById("fl-Stroke-1");
			var fl_stk_2 = document.getElementById("fl-Stroke-3");
			var fl_stk_3 = document.getElementById("fl-Stroke-5");
			var fl_stk_4 = document.getElementById("fl-Stroke-7");

			var sm_stk_1 = document.getElementById("sm-Stroke-1");
			var sm_stk_2 = document.getElementById("sm-Stroke-3");
			var sm_stk_3 = document.getElementById("sm-Stroke-5");
			var sm_stk_4 = document.getElementById("sm-Stroke-7");
			var sm_stk_5 = document.getElementById("sm-Stroke-9");
			var sm_stk_6 = document.getElementById("sm-Stroke-11");
			var sm_stk_7 = document.getElementById("sm-Stroke-13");
			var sm_stk_8 = document.getElementById("sm-Stroke-15");
			var sm_stk_9 = document.getElementById("sm-Stroke-17");
			var sm_stk_10 = document.getElementById("sm-Stroke-19");
			var sm_stk_11 = document.getElementById("sm-Stroke-21");
			var sm_stk_12 = document.getElementById("sm-Stroke-23");
			var sm_stk_13 = document.getElementById("sm-Stroke-25");
			var sm_stk_14 = document.getElementById("sm-Stroke-27");

			//animate the text for the summer
			animate_Summer_txt();

			//animate the frame
			animate_circle();

			fl.classList.add('fall_sm_in_focus');
			sm.classList.add("summer_sm_in_focus");
			ls.classList.add("lateSpring_sm_in_focus");
			es.classList.add("earlySpring_sm_in_focus");

			ls_stk_1.classList.add("ls-stroke-no-focus");
			ls_stk_2.classList.add("ls-stroke-no-focus");
			ls_stk_3.classList.add("ls-stroke-no-focus");

			//load sm_stk_.. variables into array
			var sm_stk_all = [sm_stk_1, sm_stk_2, sm_stk_3, sm_stk_4, sm_stk_5, sm_stk_6, sm_stk_7, sm_stk_8, sm_stk_9, sm_stk_10, sm_stk_11, sm_stk_12, sm_stk_13, sm_stk_14];

			//Add ..focus class on the sm_stk_[1->14] variables
			for (var i = 0; i < sm_stk_all.length; i++) {
				sm_stk_all[i].classList.add("ls-stroke-focus");
			}


			var rotation_timeout = 5000;
			function exec_rotation() {
				fall_in_focus();

				setTimeout(function () {
					earlySpring_in_focus();
				}, rotation_timeout)

				setTimeout(function () {
					lateSpring_in_focus();
				}, rotation_timeout * 2);

				setTimeout(function () {
					summer_in_focus();
				}, rotation_timeout * 3);

				setTimeout(function () {
					exec_rotation();
				}, rotation_timeout * 4);
			}
			setTimeout(exec_rotation, rotation_timeout);

			//Implement each season rotation
			//summer in focus
			function summer_in_focus() {
				fl.classList.remove("fall_ls_in_focus");
				sm.classList.remove("summer_ls_in_focus");
				ls.classList.remove("lateSpring_ls_in_focus");
				es.classList.remove("earlySpring_ls_in_focus");

				fl.classList.add('fall_sm_in_focus');
				sm.classList.add("summer_sm_in_focus");
				ls.classList.add("lateSpring_sm_in_focus");
				es.classList.add("earlySpring_sm_in_focus");

				animate_Summer_txt();

				animate_circle();

				ls_stk_1.classList.remove("ls-stroke-focus");
				ls_stk_2.classList.remove("ls-stroke-focus");
				ls_stk_3.classList.remove("ls-stroke-focus");

				ls_stk_1.classList.add("ls-stroke-no-focus");
				ls_stk_2.classList.add("ls-stroke-no-focus");
				ls_stk_3.classList.add("ls-stroke-no-focus");

				//Remove ..no-focus and add ..focus class on the sm_stk_[1->14] variables
				for (var i = 0; i < sm_stk_all.length; i++) {
					sm_stk_all[i].classList.remove("ls-stroke-no-focus");
				}

				for (var i = 0; i < sm_stk_all.length; i++) {
					sm_stk_all[i].classList.add("ls-stroke-focus");
				}
			}

			//fall in focus
			function fall_in_focus() {
				fl.classList.remove("fall_sm_in_focus");
				sm.classList.remove("summer_sm_in_focus");
				ls.classList.remove("lateSpring_sm_in_focus");
				es.classList.remove("earlySpring_sm_in_focus");

				fl.classList.add("fall_fl_in_focus");
				sm.classList.add("summer_fl_in_focus");
				ls.classList.add("lateSpring_fl_in_focus");
				es.classList.add("earlySpring_fl_in_focus");

				animate_circle();

				animate_Fall_txt();

				//Remove focus and add ...no-focus class on the sm_stk_[1->14] variables
				for (var i = 0; i < sm_stk_all.length; i++) {
					sm_stk_all[i].classList.remove("ls-stroke-focus");
				}

				for (var i = 0; i < sm_stk_all.length; i++) {
					sm_stk_all[i].classList.add("ls-stroke-no-focus");
				}



				fl_stk_1.classList.remove("ls-stroke-no-focus");
				fl_stk_2.classList.remove("ls-stroke-no-focus");
				fl_stk_3.classList.remove("ls-stroke-no-focus");
				fl_stk_4.classList.remove("ls-stroke-no-focus");

				fl_stk_1.classList.add("ls-stroke-focus");
				fl_stk_2.classList.add("ls-stroke-focus");
				fl_stk_3.classList.add("ls-stroke-focus");
				fl_stk_4.classList.add("ls-stroke-focus");
			}

			function earlySpring_in_focus() {

				fl.classList.remove("fall_fl_in_focus");
				sm.classList.remove("summer_fl_in_focus");
				ls.classList.remove("lateSpring_fl_in_focus");
				es.classList.remove("earlySpring_fl_in_focus");

				fl.classList.add("fall_es_in_focus");
				sm.classList.add("summer_es_in_focus");
				ls.classList.add("lateSpring_es_in_focus");
				es.classList.add("earlySpring_es_in_focus");

				animate_circle();

				animate_Early_Spring_txt();

				fl_stk_1.classList.remove("ls-stroke-focus");
				fl_stk_2.classList.remove("ls-stroke-focus");
				fl_stk_3.classList.remove("ls-stroke-focus");
				fl_stk_4.classList.remove("ls-stroke-focus");

				fl_stk_1.classList.add("ls-stroke-no-focus");
				fl_stk_2.classList.add("ls-stroke-no-focus");
				fl_stk_3.classList.add("ls-stroke-no-focus");
				fl_stk_4.classList.add("ls-stroke-no-focus");

				es_stk_1.classList.remove("ls-stroke-no-focus");
				es_stk_2.classList.remove("ls-stroke-no-focus");
				es_stk_3.classList.remove("ls-stroke-no-focus");
				es_stk_4.classList.remove("ls-stroke-no-focus");
				es_stk_5.classList.remove("ls-stroke-no-focus");

				es_stk_1.classList.add("ls-stroke-focus");
				es_stk_2.classList.add("ls-stroke-focus");
				es_stk_3.classList.add("ls-stroke-focus");
				es_stk_4.classList.add("ls-stroke-focus");
				es_stk_5.classList.add("ls-stroke-focus");

			}

			function lateSpring_in_focus() {
				fl.classList.remove("fall_es_in_focus");
				sm.classList.remove("summer_es_in_focus");
				ls.classList.remove("lateSpring_es_in_focus");
				es.classList.remove("earlySpring_es_in_focus");

				fl.classList.add("fall_ls_in_focus");
				sm.classList.add("summer_ls_in_focus");
				ls.classList.add("lateSpring_ls_in_focus");
				es.classList.add("earlySpring_ls_in_focus");

				animate_circle();

				animate_Spring_txt();


				es_stk_1.classList.remove("ls-stroke-focus");
				es_stk_2.classList.remove("ls-stroke-focus");
				es_stk_3.classList.remove("ls-stroke-focus");
				es_stk_4.classList.remove("ls-stroke-focus");
				es_stk_5.classList.remove("ls-stroke-focus");

				es_stk_1.classList.add("ls-stroke-no-focus");
				es_stk_2.classList.add("ls-stroke-no-focus");
				es_stk_3.classList.add("ls-stroke-no-focus");
				es_stk_4.classList.add("ls-stroke-no-focus");
				es_stk_5.classList.add("ls-stroke-no-focus");

				ls_stk_1.classList.remove("ls-stroke-no-focus");
				ls_stk_2.classList.remove("ls-stroke-no-focus");
				ls_stk_3.classList.remove("ls-stroke-no-focus");

				ls_stk_1.classList.add("ls-stroke-focus");
				ls_stk_2.classList.add("ls-stroke-focus");
				ls_stk_3.classList.add("ls-stroke-focus");

			}

			//Handle image and text animation
			function animate_Summer_txt() {
				//group7.classList.add("group7Anim");
				door.classList.add("door_anim_go");
				window1.classList.add("window1_anim_go");
				window2.classList.add("window2_anim_go");
				windowBig.classList.add("windowBig_anim_go");
				text_summer.classList.add("txt_anim_global");
				text_summer.style.display = "block";
				text_late_spring.style.display = "none";
				text_fall.style.display = "none";
				text_early_spring.style.display = "none";

				setTimeout(function () {
					//group7.classList.remove("group7Anim");
					door.classList.remove("door_anim_go");
					window1.classList.remove("window1_anim_go");
					window2.classList.remove("window2_anim_go");
					windowBig.classList.remove("windowBig_anim_go");
					text_summer.classList.remove("txt_anim_global");
				}, 1000);
			}

			function animate_Spring_txt() {
				//group7.classList.add("group7Anim");
				door.classList.add("door_anim_go");
				window1.classList.add("window1_anim_go");
				window2.classList.add("window2_anim_go");
				windowBig.classList.add("windowBig_anim_go");
				text_late_spring.classList.add("txt_anim_global");
				text_late_spring.style.display = "block";
				text_summer.style.display = "none";
				text_fall.style.display = "none";
				text_early_spring.style.display = "none";

				setTimeout(function () {
					//group7.classList.remove("group7Anim");
					door.classList.remove("door_anim_go");
					window1.classList.remove("window1_anim_go");
					window2.classList.remove("window2_anim_go");
					windowBig.classList.remove("windowBig_anim_go");
					text_late_spring.classList.remove("txt_anim_global");
				}, 1000);
			}

			function animate_Early_Spring_txt() {
				//group7.classList.add("group7Anim");
				door.classList.add("door_anim_go");
				window1.classList.add("window1_anim_go");
				window2.classList.add("window2_anim_go");
				windowBig.classList.add("windowBig_anim_go");
				text_early_spring.classList.add("txt_anim_global");
				text_early_spring.style.display = "block";
				text_summer.style.display = "none";
				text_fall.style.display = "none";
				text_late_spring.style.display = "none";

				setTimeout(function () {
					//group7.classList.remove("group7Anim");
					door.classList.remove("door_anim_go");
					window1.classList.remove("window1_anim_go");
					window2.classList.remove("window2_anim_go");
					windowBig.classList.remove("windowBig_anim_go");
					text_early_spring.classList.remove("txt_anim_global");
				}, 1000);
			}

			function animate_Fall_txt() {
				door.classList.add("door_anim_go");
				window1.classList.add("window1_anim_go");
				window2.classList.add("window2_anim_go");
				windowBig.classList.add("windowBig_anim_go");
				text_fall.classList.add("txt_anim_global");
				text_fall.style.display = "block";
				text_summer.style.display = "none";
				text_late_spring.style.display = "none";
				text_early_spring.style.display = "none";

				setTimeout(function () {
					door.classList.remove("door_anim_go");
					window1.classList.remove("window1_anim_go");
					window2.classList.remove("window2_anim_go");
					windowBig.classList.remove("windowBig_anim_go");
					text_fall.classList.remove("txt_anim_global");
				}, 1000);
			}
			//Handle the circle (frame) animation
			function animate_circle() {
				circle_frame.classList.add("circle_frame_anim");
				setTimeout(function () {
					circle_frame.classList.remove("circle_frame_anim");
				}, 1000);
			}
		}
	});
});
