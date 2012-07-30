(function ($) {
  Drupal.behaviors.autologout = {
    attach: function(context, settings) {

      var t = setTimeout(init, Drupal.settings.autologout.timeout);
      var paddingTimer;

      function init() {
        dialog();
        paddingTimer = setTimeout(logout, Drupal.settings.autologout.timeout_padding);
      }

      function dialog() {
        $("<div> " +  Drupal.settings.autologout.message + "</div>").dialog({
          modal: true,
          closeOnEscape: false,
          width: "auto",
          buttons: {
            Yes: function() {
              $(this).dialog("destroy");
              clearTimeout(paddingTimer);
              refresh();
            },
            No: function() {
              $(this).dialog("destroy");
              logout();
            }
          },
          title: Drupal.settings.autologout.title,
          close: function(event, ui) {
            logout();
          }
        });
      }

      function logout() {
        $.ajax({
          url: Drupal.settings.basePath + "autologout_ahah_logout",
          type: "POST",
          success: function() {
            window.location = Drupal.settings.autologout.redirect_url;
          },
          error: function(XMLHttpRequest, textStatus) {
           alert("There has been an error resetting your last access time: " + textStatus + ".")
          },
        });
      }

      function refresh() {
        $.ajax({
          url: Drupal.settings.basePath + "autologout_ahah_set_last",
          type: "POST",
          success: function() {
            t = setTimeout(init, Drupal.settings.autologout.timeout);
          },
          error: function(XMLHttpRequest, textStatus) {
            alert("There has been an error resetting your last access time: " + textStatus + ".")
          },
        });
      }

    }
  };
})(jQuery);
