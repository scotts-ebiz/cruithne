require(["jquery"], function($) {
  $(document).ready(function() {
    if (
      (false ||
        !!document.documentMode /** IE 6-11 detection */ ||
        (!(false || !!document.documentMode) &&
        !!window.StyleMedia)) /** Edge non-chromium detection */ &&
        window.location.pathname != "/browser"
     ) {
      var headerInterval = setInterval(function() {
        if (document.querySelector('header#sp-header')) {
          document.querySelector('header#sp-header').style.flex = '0 0 auto';

          clearInterval(headerInterval);
        }
      }, 100);
      window.location.href = "/browser";
    }
  });
});
