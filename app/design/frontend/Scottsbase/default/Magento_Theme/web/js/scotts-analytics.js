//Script to get and set the DTM properties - all of the documented DTM properties are present


/* repository of name strings. Currently all are literally the same but this set of constants
 * will save time if names change later. */

/* generic names */
/* var TagID_article_id = "article_id";
var TagID_base_material_codes = "base_material_codes";
var TagID_cart = "cart";
var TagID_channel = "channel";
var TagID_content_type = "content_type";
var TagID_error = "error";
var TagID_hierarchy = "hierarchy";
var TagID_internal_search_number = "internal_search_number";
var TagID_internal_search_terms = "internal_search_terms";
var TagID_order_id = "order_id";
var TagID_page_events = "page_events";
var TagID_pagename = "pagename";
var TagID_payment_method = "payment_method";
var TagID_preferred_locale = "preferred_locale";
var TagID_product_category_id = "product_category_id";
var TagID_product_id = "product_id";
var TagID_product_id_list = "product_id_list";
var TagID_product_price_list = "product_price_list";
var TagID_product_qty_list = "product_qty_list";
var TagID_product_subcategory_id = "product_subcategory_id";
var TagID_product_subsubcategory_id = "product_subsubcategory_id";
var TagID_product_subsubsubcategory_id = "product_subsubsubcategory_id";
var TagID_product_subsubsubsubcategory_id = "product_subsubsubsubcategory_id";
var TagID_promo_code = "promo_code";
var TagID_promo_code_amt = "promo_code_amt";
var TagID_purchase_id = "purchase_id";
var TagID_recommend_type = "recommend_type";
var TagID_rendered_locale = "rendered_locale";
var TagID_ship_revenue = "ship_revenue";
var TagID_shipping_method = "shipping_method";
var TagID_site = "site";
var TagID_subcategory = "subcategory";
var TagID_subdomain = "subdomain";
var TagID_subsubcategory = "subsubcategory";
var TagID_tax_dollar = "tax_dollar";
var TagID_tool_name = "tool_name";
var TagID_tool_end = "tool_end";
var TagID_zip = "zip";*/

/* DTM specific names */
//initializes the DTM object
/*if(typeof dtmData == 'undefined'){ dtmData = {}; }
var DTM_article_id = "article_id";
var DTM_base_material_codes = "base_material_codes";
var DTM_cart = "cart";
var DTM_channel = "channel";
var DTM_content_type = "content_type";
var DTM_error = "error";
var DTM_hierarchy = "hierarchy";
var DTM_internal_search_number = "internal_search_number";
var DTM_internal_search_terms = "internal_search_terms";
var DTM_order_id = "order_id";
var DTM_page_events = "page_events";
var DTM_pagename = "pagename";
var DTM_payment_method = "payment_method";
var DTM_preferred_locale = "preferred_locale";
var DTM_product_category_id = "product_category_id";
var DTM_product_id = "product_id";
var DTM_product_id_list = "product_id_list";
var DTM_product_price_list = "product_price_list";
var DTM_product_qty_list = "product_qty_list";
var DTM_product_subcategory_id = "product_subcategory_id";
var DTM_product_subsubcategory_id = "product_subsubcategory_id";
var DTM_product_subsubsubcategory_id = "product_subsubsubcategory_id";
var DTM_product_subsubsubsubcategory_id = "product_subsubsubsubcategory_id";
var DTM_promo_code = "promo_code";
var DTM_promo_code_amt = "promo_code_amt";
var DTM_purchase_id = "purchase_id";
var DTM_recommend_type = "recommend_type";
var DTM_rendered_locale = "rendered_locale";
var DTM_ship_revenue = "ship_revenue";
var DTM_shipping_method = "shipping_method";
var DTM_site = "site";
var DTM_subcategory = "subcategory";
var DTM_subdomain = "subdomain";
var DTM_subsubcategory = "subsubcategory";
var DTM_tax_dollar = "tax_dollar";
var DTM_tool_name = "tool_name";
var DTM_tool_end = "tool_end";
var DTM_zip = "zip"; */

/* Revised by NC: possible values are */
/*var DTM_TYPE_catalog = "catalog";
var DTM_TYPE_category = "category";
var DTM_TYPE_content_faq = "content_faq";
var DTM_TYPE_content_howto = "content_howto";
var DTM_TYPE_content_project = "content_project";
var DTM_TYPE_content_solution = "content_solution";
var DTM_TYPE_content_video = "content_video";
var DTM_TYPE_FAQ = "FAQ";
var DTM_TYPE_howto = "howto";
var DTM_TYPE_library = "library";
var DTM_TYPE_product = "product";
var DTM_TYPE_project = "project";
var DTM_TYPE_section = "section";
var DTM_TYPE_shelf = "shelf";
var DTM_TYPE_solution = "solution";
var DTM_TYPE_video = "video";

// The ObservePoint platform reports a warning if any analytics values are longer than 100 characters.
// This object can modify any value to comply with their recommendations.
var AnalyticsValueFilter = (function() {

  AnalyticsValueFilter.prototype = {
    defaults: {
      truncateTo: 100,
      truncateValues: true,
      enabled: true
    },

    isEnabled: function() {
      return !!this.options.enabled
    },

    applyFilters: function(value) {
      value = this.truncate(value);
      // Add any other filters here
      // value = this.capitalize(value);
      // value = this.removeNumbers(value);
      // value = this.replaceAllSpacesWithUnderscores(value);
      // value = this.etc();

      return value;
    },

    truncate: function(value) {
      if ("string" === typeof value) {
        value = value.substring(0, this.options.truncateTo);
      }
      return value;
    }
  };

  function AnalyticsValueFilter(opts) {
    opts = ("object" === typeof opts) ? opts : {};
    this.options = $.extend(this.defaults, opts);
  }


  return AnalyticsValueFilter;

}()); */

/* This code is intended to be used in September to track the following information:
    1. The browser's preferred locale
    2. The locale in which the page was rendered
    3. The subdomain in the address bar.
 */
/*var ScottsLocaleAnalytics = {
  getBrowserLanguage: function getBrowserLanguage() {
    var language = window.navigator.userLanguage // IE
      || window.navigator.language; // Everybody else
    language = language.replace(/-/g, '_');
    return language;
  },

  getSubdomain: function getSubdomain() {
    var location = window.location.hostname;
    var subdomain = '';
    location = location.split(/\./);
    if (location.length && location.length > 2) {
      // Pop off the tld and the domain name.
      location = location.slice(0, -2);

      subdomain = location.join('.');
    }
    // If the subdomain is 'www', then report it as 'en-us'.
    if (subdomain && 'www' === subdomain.toLowerCase()) {
      subdomain = 'en-us';
    }

    return subdomain;
  },

  // Smartling adds some <meta> tags that contain the language the site was translated to.
  // See if we can find one.
  getRenderedLocale: function getRenderedLocale() {
    var tags = document.getElementsByTagName("meta"),
      renderedLocale = "en_US",
      foundRenderedLocale = false,
      re = /Content-Language/i, // The http-equiv attribute value to look for; case-insensitive


      inspectTag = function inspectTag() {
        var tag = this;
        if (re.test(tag.httpEquiv)) {
          renderedLocale = tag.content;
          foundRenderedLocale = true;
        }
      };

    for (var i = 0; i < tags.length; i++) {
      inspectTag.apply(tags[i]);
      if (foundRenderedLocale) {
        break;
      }
    }
    renderedLocale = renderedLocale.replace(/-/, '_');
    return renderedLocale;
  },

  isDebugging: function() {
    var is = false;
    if ('localStorage' in window && window['localStorage'] !== null) {
      var maybe = localStorage.getItem("dtmdebug");
      if (maybe) {
        is = Boolean(maybe);
      }
    }
    return is;
  },

  log: function(object) {
   document.write("<span style='border:1px solid red;background-color:white;font-family:consolas,monaco,monospace;display:inline-block;'><b>@TYPE</b>: @NAME => @VALUE</span>".replace(/@NAME/, object.name).replace(/@VALUE/, object.value).replace(/@TYPE/, object.type));
   if ('localStorage' in window && window['localStorage'] !== null) {
     var log = localStorage.getItem('dtmlog');
     if (!log || log.length < 1) {
       log = [];
     } else {
       log = JSON.parse(log);
     }
     log.push(object);
     localStorage.setItem("dtmlog", JSON.stringify(log));
   }
  },

  summarize: function() {
    if ('localStorage' in window && window['localStorage'] !== null) {
      var log = JSON.parse(localStorage.getItem('dtmlog'));
      if (log) {
        var a = [];
        a.push("<hr><center><h2>Summary of DTM data actions taken on this page</h2><table style='margin:0 auto 100px;font-family:consolas,monaco,monospace;' align=center cellpadding=4 cellspacing=4 border=1><thead><tr><th>Operation</th><th>Name</th><th>Value</th></tr></thead><tbody>");
        for (var i = 0; i < log.length; i++) {
          var o = log[i];
          try {
            a.push("<tr><th>@TYPE</th><td>@NAME</td><td>@VALUE</td></tr>".replace(/@TYPE/, o.type).replace(/@NAME/, o.name).replace(/@VALUE/, (o.value) ? o.value : '<FONT COLOR=RED>NOT SET</FONT>'));
          } catch (e) {
          }
        }
        a.push("</tbody></table></center>");
        document.write(a.join(''));
      }

      localStorage.removeItem('dtmlog');
    }

  }

};
*/
/** Set up the clientside dtmdebug stuff. Like whether it's supported and turned on and so on. */
/*(function() {
  if ('localStorage' in window && window['localStorage'] !== null) {
    var a = document.createElement("a");
    a.href = window.location.href;
    if (a.search.indexOf("dtmdebug=1") != -1) {
      localStorage.setItem('dtmdebug', true);
    } else if (a.search.indexOf("dtmdebug=0") != -1) {
      localStorage.removeItem('dtmdebug');
    }
  }
}());




//if the function being called needs to have multiple arguments, pass the arguments as an array and set isMultipleArgsArray to true
function CallTrackingFunction(trackingObjName, func, arg, isMultipleArgsArray) {
  try {
    var trackingObj = window[trackingObjName];
    if ('object' == typeof trackingObj && 'function' == typeof trackingObj[func]) {
      if (isMultipleArgsArray && Array.isArray(arg)) {
        trackingObj[func].apply(this || window, arg);
      }
      else {
        trackingObj[func](arg);
      }
    }
  }
  catch (e) {
    console.warn(e.message);
  }

  if (ScottsLocaleAnalytics.isDebugging()) {
    ScottsLocaleAnalytics.log({
      type: 'CALL',
      name: "@OBJECT['@FUNC'](@ARG)".replace(/@OBJECT/, trackingObjName).replace(/@FUNC/, func).replace(/@ARG/, arg),
      value: arg
    });
  }
}

function HasTrackingTag(name) {
  if (dtmData[name] != null) {
    return false;
  }
  return true;
}

function SetTrackingTag(name, value) {
  dtmData[window["DTM_" + name]] = value;
  if (ScottsLocaleAnalytics.isDebugging()) {
    ScottsLocaleAnalytics.log({
      type: 'SET',
      name: name,
      value: value
    });

  }
}
function AppendTrackingTag(name, value) {
  dtmData[window["DTM_" + name]] += value;
  if (ScottsLocaleAnalytics.isDebugging()) {
    ScottsLocaleAnalytics.log({
      type: 'APPEND',
      name: name,
      value: value
    });
  }
}

// A function to ensure that all tag values are within a predefined set of constraints inside the AnalyticsValueFilter
// object. This function covers all cases, whether the dtmData value was collected via the SetTrackingTag function
// or via a direct assignment on the dtmData object itself.
function CleanTrackingTags() {
  var filter, key, value;

  filter = new AnalyticsValueFilter();
  if (filter.isEnabled()) {
    for (key in window.dtmData) {
      if (window.dtmData.hasOwnProperty(key)) {

        value = window.dtmData[key];
        value = filter.applyFilters(value);
        window.dtmData[key] = value;
      }
    }
  }
}


function EndTracking() {
  if (ScottsLocaleAnalytics.isDebugging()) {
    ScottsLocaleAnalytics.summarize();
  }
} */
