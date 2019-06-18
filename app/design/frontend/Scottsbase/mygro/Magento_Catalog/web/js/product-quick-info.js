define(['jquery'], function($){
    alert('This is a testy!');

    if ($("body").hasClass("product-sensors")){
        alert('Product Sensors');
        $('#sensors-quickInfo').addClass('active');
        $('#controllers-quickInfo').removeClass('active');
    } else if ($("body").hasClass("product-controllers")){
        alert('Product Controllers');
        $('#controllers-quickInfo').addClass('active');
        $('#sensors-quickInfo').removeClass('active');
    } else {
        //Do nothing
    }

});