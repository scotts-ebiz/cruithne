define(['jquery'], function($){
//Set Active status for nav items
    if ($("body").hasClass("product-sensors")){
        $('#drupal-sensors-page').addClass('active');
        $('#drupal-controllers-page').removeClass('active');
        $('#drupal-home-page').removeClass('active');

    } else if ($("body").hasClass("product-controller")){
        $('#drupal-controllers-page').addClass('active');
        $('#drupal-sensors-page').removeClass('active');
        $('#drupal-home-page').removeClass('active');

    } else if ($("body").hasClass("cms-drupalhome")){
        $('#drupal-home-page').addClass('active');
        $('#drupal-sensors-page').removeClass('active');
        $('#drupal-controllers-page').removeClass('active');

    } else {
        //Do nothing
    }

});