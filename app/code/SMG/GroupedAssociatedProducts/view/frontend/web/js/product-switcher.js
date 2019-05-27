define(['jquery'], function($){
    $('#product_selector').change(function () {
        var selectValue = this.value;
        //alert(selectValue);

        if ($(selectValue == 'product_item_id__1') && !$("#product_summary_id__1").hasClass("active")){
            $('#product_summary_id__1').addClass('active');
            $('#product_summary_id__2').removeClass('active');
            $('#product_summary_id__3').removeClass('active');
            $('#product_summary_id__4').removeClass('active');
            $('#product_summary_id__5').removeClass('active');
            $('#product_summary_id__6').removeClass('active');
        } else if ($(selectValue == 'product_item_id__2') && !$("#product_summary_id__2").hasClass("active")){
            $('#product_summary_id__2').addClass('active');
            $('#product_summary_id__1').removeClass('active');
            $('#product_summary_id__3').removeClass('active');
            $('#product_summary_id__4').removeClass('active');
            $('#product_summary_id__5').removeClass('active');
            $('#product_summary_id__6').removeClass('active');
        } else if ($(selectValue == 'product_item_id__3') && !$("#product_summary_id__3").hasClass("active")){
            $('#product_summary_id__3').addClass('active');
            $('#product_summary_id__1').removeClass('active');
            $('#product_summary_id__2').removeClass('active');
            $('#product_summary_id__4').removeClass('active');
            $('#product_summary_id__5').removeClass('active');
            $('#product_summary_id__6').removeClass('active');
        } else if ($(selectValue == 'product_item_id__4') && !$("#product_summary_id__4").hasClass("active")){
            $('#product_summary_id__4').addClass('active');
            $('#product_summary_id__1').removeClass('active');
            $('#product_summary_id__2').removeClass('active');
            $('#product_summary_id__3').removeClass('active');
            $('#product_summary_id__5').removeClass('active');
            $('#product_summary_id__6').removeClass('active');
        } else if ($(selectValue == 'product_item_id__5') && !$("#product_summary_id__5").hasClass("active")){
            $('#product_summary_id__5').addClass('active');
            $('#product_summary_id__1').removeClass('active');
            $('#product_summary_id__2').removeClass('active');
            $('#product_summary_id__3').removeClass('active');
            $('#product_summary_id__4').removeClass('active');
            $('#product_summary_id__6').removeClass('active');
        } else if ($(selectValue == 'product_item_id__6') && !$("#product_summary_id__6").hasClass("active")){
            $('#product_summary_id__6').addClass('active');
            $('#product_summary_id__1').removeClass('active');
            $('#product_summary_id__2').removeClass('active');
            $('#product_summary_id__3').removeClass('active');
            $('#product_summary_id__4').removeClass('active');
            $('#product_summary_id__5').removeClass('active');
        } else {
            //do nothing
        }
    });
});
