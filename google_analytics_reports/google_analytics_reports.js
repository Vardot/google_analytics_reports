Drupal.behaviors.googleAnalyticsReports = function(context) {
  if ($('.google-analytics-reports-path-mini', context).length) {
    $.ajax({
      url: '/google-analytics-reports/ajax/path-mini',
      dataType: 'json',
      data: ({ path: window.location.pathname }),
      success: function(data) {
        $('.google-analytics-reports-path-mini').html(data.content).hide().slideDown('fast');
      },
      error: function(data) {
        // @TODO
      }
    });
  }

  if ($('.google-analytics-reports-path-mini', context).length) {
    $.ajax({
      url: '/google-analytics-reports/ajax/summary',
      dataType: 'json',
      success: function(data) {
        $('.google-analytics-reports-summary').html(data.content).hide().slideDown('fast');
      },
      error: function(data) {
        // @TODO
      }
    });
  }
}
