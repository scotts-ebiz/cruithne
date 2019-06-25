define(['jquery'], function($){

    alert('Seth Avett rules!');

    if ($('body').hasClass('product-sensors')){
        $('#mygro-sensors-page').addClass('active');
        $('#mygro-controllers-page').removeClass('active');
        $('#mygro-home-page').removeClass('active');

    } else if ($('body').hasClass('product-controllers')) {
        $('#mygro-controllers-page').addClass('active');
        $('#mygro-sensors-page').removeClass('active');
        $('#mygro-home-page').removeClass('active');

    } else if ($('body').hasClass('cms-index-index')) {
        $('#mygro-home-page').addClass('active');
        $('#mygro-sensors-page').removeClass('active');
        $('#mygro-controllers-page').removeClass('active');
    } else {
        //do nothing
    }
});