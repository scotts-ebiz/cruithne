define([
    'jquery'
], function ($) {
    return Component.extend({
        initialize(config) {
			console.log(config.recommendation_id);
			zaius.event("quiz",{
				action: "submitted",
				recommendation_id: '"'+config.recommendation_id+'"',
				new_id: '"'+config.new_id+'"',
				product_id: '"'+config.product_id+'"',
				applicationstartdate: '"'+config.applicationstartdate+'"',
				applicationenddate: '"'+config.applicationenddate+'"',
				product_order: '"'+config.product_order+'"',
				quiz_zip_code: '"'+config.quiz_zip_code+'"'
			})
        }
    });
});

