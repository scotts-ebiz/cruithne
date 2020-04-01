define(['jquery'], function($){

    if ($("body").hasClass("product-sensors")){
        $('#sensors-quickInfo').addClass('active');
        $('#controllers-quickInfo').removeClass('active');
    } else if ($("body").hasClass("product-controller")){
        $('#controllers-quickInfo').addClass('active');
        $('#sensors-quickInfo').removeClass('active');
    } else {
        //Do nothing
    }

});