define([
    'jquery',
    'jquery/ui',
    'mage/requirejs' // usually widget can be found in /lib/web/mage dir
], function($){

    $.widget('SMG.LandingPage', $.mage.requirejs, {
    });

    alert('Penis');

    return $.SMG.LandingPage;
});