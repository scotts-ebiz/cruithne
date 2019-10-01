require([
    'jquery',
], function ($) {
    $(document).ready(function(){
		 $('.product.info.detailed a').on("click",function(){
			var tabtext = $(this).text(); 
			var trimStr = $.trim(tabtext);
			$('#tabclick_event').remove();
			$(".product-info-main").append("<div id='tabclick_event'><script>dtmData.events.push({name: 'tabClick',tabName:'"+trimStr+"'});<\/script><\/div>");
		});
	});
});