jQuery(document).ready(function($) {
    var $extraAddressBtn = $('.extraAddressBtn'),
        $extraAddress = $('.extraAddress');

/*
	$(".product-image-slider").slick({
		dots: true,
		arrows: false
	});

	$(".product-opts > a").on("click", function(e) {
		var optionIndex = $(this).index(),
			activeClass = 'active',
			isInStock = ($(this).data("stock") == "in-stock");
		
		$("#product-image-slider-" + optionIndex).addClass(activeClass).siblings().removeClass(activeClass);
		
		$(this).addClass("active").find("input").val(1).end().siblings().removeClass("active").find("input").val(0);
		$("#quantity").val(1);
		
		if(isInStock) {
			$(".add-to-cart-buttons button").attr("disabled", false).removeClass("disabled").find("span span").text("Add to Cart");
		} else {
			$(".add-to-cart-buttons button").attr("disabled", "disabled").addClass("disabled").find("span span").text("Coming Soon");
		}
		
		e.preventDefault();
	});
	
	$(".product-opts > .active").trigger("click");
	
	if($(".catalog-product-view").length > 0) {
		var $productSlider = $('.product-image-slider');
		$(document).on('keydown', function(e) {
            if(e.keyCode == 37) {
                $productSlider.slick('slickPrev');
            }
            if(e.keyCode == 39) {
                $productSlider.slick('slickNext');
            }
        });
	}
*/

/*
	$("#nav-open, #nav-close, #nav-overlay").on("click", function(e) {
		$("body").toggleClass("nav-open");
		
		e.preventDefault();
	});
	
	$(".quantity-picker span").on("click", function() {
		var plusQuantity = ($(this).attr("id") == "plus"),
			$quantity = $("#quantity"),
			currentQuantity = $quantity.val(),
			isDisabled = $(this).hasClass("disabled");
			
		if(!isDisabled) {
			$("#minus").removeClass("disabled");
			
			if(plusQuantity) {
				currentQuantity++;
			} else {
				if(currentQuantity == 2) {
					$("#minus").addClass("disabled");
				}
				currentQuantity--;
			}
			
			$quantity.val(currentQuantity);
			$quantity.trigger("change");
		}
	});
	
	$("#compare").on("click", function(e) {
		var productDetailsHeight = $(".product-image-details-wrapper").outerHeight();
		
		$("body, html").animate({ 
			scrollTop: productDetailsHeight
		}, 500);
		
		e.preventDefault();
	});
*/
/*
    $(".video-link").click(function(){
        alert("The paragraph was clicked.");
    });

 */
	$(".video-link").on("click", function(e) {
        alert("The paragraph was clicked.");
	    var embedCode = $(this).find(".embed-code").html();
        alert("The paragraph was clicked2.");
		$("body").toggleClass("video-open");
        alert("The paragraph was clicked3.");
		$("#video-container .video-frame").html(embedCode);
        alert("The paragraph was clicked4.");
		e.preventDefault();
        alert("You're out of preventDefault.");
	});


	$(".video-overlay").on("click", function(e) {
		$("body").toggleClass("video-open");
		$("#video-container .video-frame").html('');
	});

    /*
	$("#quantity").on("change", function() {
		var currentQuantity = $(this).val(),
			$selectedOption = $(".product-opts .active");
				
		$selectedOption.find("input").val(currentQuantity);
	});

    $extraAddressBtn.on({
		click: function() {
			$(this).hide();
			$extraAddress.show();
		}
	});
	*/
});