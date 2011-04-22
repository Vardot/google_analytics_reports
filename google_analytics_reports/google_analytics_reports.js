Drupal.behaviors.googleAnalyticsReports = function(context) {
  if ($('.google-analytics-reports-path-mini', context).length) {
    $.ajax({
      url: '/google-analytics-reports/ajax/path-mini',
      dataType: 'json',
      data: ({ path: window.location.pathname }),
      success: function(data) {
        $('.google-analytics-reports-path-mini').html(data.content);
      },
      error: function(data) {

      }
    });
  }
}
