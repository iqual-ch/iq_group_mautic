(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.iqGroupMautic = {
      attach: function attach(context, settings) {
        if (drupalSettings.user && drupalSettings.user.uid) {
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push({"iq_group_mautic_drupal_id": drupalSettings.user.uid});
        }
      }
    };
})(jQuery, Drupal, drupalSettings);