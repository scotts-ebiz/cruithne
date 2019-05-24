define(['jquery'], function($){
    $('#product_selector').change(function () {
        var selectValue = this.value;
        //alert( this.value );
        alert(selectValue);
    });
});