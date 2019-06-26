define(['jquery'], function($){

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

    } else if ($("body").hasClass("cms-mygrohome")){
        alert('You are home!');
        $('#mygro-home-page').addClass('active');
        $('#mygro-sensors-page').removeClass('active');
        $('#mygro-controllers-page').removeClass('active');

    } else {
        //Do nothing
    }

});