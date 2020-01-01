define([ 
    "jquery" 
   ], function($){

       var count=1868;
       var loop=50;
       var interval;

       var text=document.getElementById('countdown');

       var line1_get = document.getElementById("line-1-id");
       var line2_get = document.getElementById("line-2-id");


   function timer(time)
       {
               if (count < 2020)
               {
                   count = count+1;
                   text.innerHTML = count;

                   interval = setTimeout(function(){
                                           timer(300);
                                       },loop);
                   
                   if(count >= 2020){
                   clearTimeout(interval);
                   return;
                   }
               }
       }

       //trigger the countdown animation
       $(window).scroll(function() {
           var hT = $('#trigger-anim').offset().top;
           var	hH = $('#trigger-anim').outerHeight();
           var	wH = $(window).height();
           var	wS = $(this).scrollTop();
           if (wS > (hT+hH-wH) && (hT > wS)){
               //Animate images fade in out
               fadeimg();
               //Animate line1
               line1_get.classList.add("line-1_anim");
               //timer(parseInt(range.value));
               setTimeout(function(){ timer(30); },200);
               //Animate line 2
               setTimeout(function(){ line2_get.classList.add("line-2_anim"); },400);
           } 
           });

           function fadeimg(){
            //for the images
            var current_img = 0,
            slides = document.getElementsByClassName("season-img");
            setInterval(function(){
                for (var i = 0; i < slides.length; i++) {
                    slides[i].style.opacity = 0;
                }
                current_img = (current_img != slides.length - 1) ? current_img + 1 : 4;
                slides[current_img].style.opacity = 1;
                
                },500);
            }
});