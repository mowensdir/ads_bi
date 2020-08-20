(function ($, window, document) {
  $(function () {
    bootstrapDisplay();
  });

  function bootstrapDisplay() {
    setSidebarHeight();
    activateNav();
  }

  function setSidebarHeight() {
    var elem = document.getElementById("adsbi-sidebar-wrapper"),
        rect = elem.getBoundingClientRect(),
        toth = document.body.clientHeight,
        diff = toth - rect.bottom;
    if (0 < diff) {
      var mh = rect.bottom - rect.top + diff;
      elem.style.minHeight = mh + "px";
    }
  }

  function activateNav() {
    var slct = $("#content-wrapper").data("activate"),
        $elm = $(slct);
    $elm.addClass("active");
    if ($elm.hasClass("item")) {
      var $grp = $elm.closest("group-wrapper"),
          $nav = $grp.find(".group").first();
      $nav.addClass('show');
    }
  }
}(jQuery, window, document));
