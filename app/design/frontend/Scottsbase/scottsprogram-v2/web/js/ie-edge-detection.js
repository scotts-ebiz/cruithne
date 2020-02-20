require(["jquery"], function($) {
  $(document).ready(function() {
    if (
      (false ||
        !!document.documentMode /** IE 6-11 detection */ ||
        (!(false || !!document.documentMode) &&
        !!window.StyleMedia)) /** Edge non-chromium detection */ &&
        window.location.pathname != "/browser"
     ) {
      window.location.href = "/browser";
    }
  });
});
