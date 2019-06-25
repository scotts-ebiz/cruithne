define(['jquery'], function($){

       alert('Document is ready!');


    if ($("body").hasClass("product-sensors")){
        alert('Product Sensors Yo!');
        $('#mygro-sensors-page').addClass('active');
        $('#mygro-controllers-page').removeClass('active');
        $('#mygro-home-page').removeClass('active');
    } else if ($("body").hasClass("product-controllers")){
        alert('Product Controllers yo!');
        $('#mygro-controllers-page').addClass('active');
        $('#mygro-sensors-page').removeClass('active');
        $('#mygro-home-page').removeClass('active');
    } else {
        //Do nothing
    }

});