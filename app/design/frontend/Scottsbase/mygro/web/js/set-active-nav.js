define(['jquery'], function($){
//Set Active status for nav items
    if ($("body").hasClass("product-sensors")){
        $('#mygro-sensors-page').addClass('active');
        $('#mygro-controllers-page').removeClass('active');
        $('#mygro-home-page').removeClass('active');

    } else if ($("body").hasClass("product-controllers")){
        $('#mygro-controllers-page').addClass('active');
        $('#mygro-sensors-page').removeClass('active');
        $('#mygro-home-page').removeClass('active');

    } else if ($("body").hasClass("cms-mygrohome")){
        $('#mygro-home-page').addClass('active');
        $('#mygro-sensors-page').removeClass('active');
        $('#mygro-controllers-page').removeClass('active');

    } else {
        //Do nothing
    }

});