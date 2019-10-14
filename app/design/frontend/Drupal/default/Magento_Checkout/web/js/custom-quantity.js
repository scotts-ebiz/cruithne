define([
    'jquery'
],function($){

    function qtyDown(id){
        var qty_el = document.getElementById('cart-' + id + '-qty');
        var qty = qty_el.value;
        if( qty == 2) {
            $('.box_down' + id).css({
                'visibility' : 'visible'
            });
        }
        if( !isNaN( qty ) && qty > 0 ){
            qty_el.value--;
        }
        return false;
    }

    function qtyUp(id){
        var qty_el = document.getElementById('cart-' + id + '-qty');
        var qty = qty_el.value;
        if( !isNaN( qty )) {
            qty_el.value++;
        }
        $('.box_down' + id).css({
            'visibility' : 'visible'
        });
        return false;
    }

    var bod = $('body');

    bod.on('click', '.qtyplus', function () {
        var self = $(this);
        return qtyUp(self.attr('data-item-id'));
    });

    bod.on('click', '.qtyminus', function () {
        var self = $(this);
        return qtyDown(self.attr('data-item-id'));
    });
});