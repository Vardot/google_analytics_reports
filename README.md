## DESCRIPTION

Google Analytics Reports module provides graphical reporting of your site's
tracking data. Graphical reports include small path-based report in blocks, and
a full path-based report.

Google Analytics Reports API module provide API for developers to access data
from Google Analytics using Analytics Data API
https://developers.google.com/analytics/devguides/reporting/data/v1

Google Analytics Reports module provide Views query plugin to create Google
Analytics reports using Views interface.

## GA4 Notes

```
Familiarize yourself with the current list of dimensions and metrics supported by the Data API. Currently, all dimensions and metrics are compatible with each other, so there is no need to use the Dimensions and Metrics Explorer to determine compatible combinations. This behavior will change in the future.
Custom dimensions in Google Analytics 4 can be accessed using the Data API v1 custom dimensions syntax, which should be used instead of the ga:dimensionXX dimension slots of the Reporting API v4.
Custom metrics in Google Analytics 4 can be accessed using the Data API v1 custom metrics syntax, which should be used instead of the ga:metricXX metric slots of the Reporting API v4.
Certain dimensions and metrics found in Universal Analytics have a direct equivalent in Google Analytics 4. See the UA/GA4 API schema equivalence chart for more information.
Dimension and metric names no longer have ga: prefix in Google Analytics 4.
Certain functionality present in Universal Analytics is not yet available in GA4 (e.g. Campaign Manager, DV360, Search Ads 360 integration). Once this functionality is implemented in Google Analytics 4, the Data API will support it, new dimensions and metrics will be added to the API schema.
```

## REQUIREMENTS

- [x] Setup account via this doc
      https://support.google.com/analytics/answer/9304153 at
      https://www.google.com/analytics

## DEPENDENCIES

- Google Analytics Reports API has no dependencies.
- Google Analytics Reports depends on Google Analytics Reports API and Views
  modules.

## RECOMMENDED MODULES

- Charts module https://www.drupal.org/project/charts. Enable Google Charts or
  Highcharts sub-module to see graphical reports.
- Ajax Blocks module https://www.drupal.org/project/ajaxblocks for better page
  loading with Google Analytics Reports blocks.

## INSTALLATION

- Install like normal Drupal module
- Setup in composer.json to have Google lib installed. Check composer merge and
  setting in following example config:

```
"require": {
  "wikimedia/composer-merge-plugin": "^2.0"
  ...
},
"extra": {
  "merge-plugin": {
      "include": [
          "web/modules/contrib/google_analytics_reports/composer.libraries.json",
      ],
  }
}

composer require drupal/google_analytics_reports
composer update drupal/google_analytics_reports

```

## CONFIGURATION

Configuration of Google Analytics Reports API module.

Before you can get the credentials you may need to create a new project and
enable the analytics API for it:

1. Setup the API via doc
   https://developers.google.com/analytics/devguides/reporting/data/v1

1. Open Google Developers Console: https://console.developers.google.com. Find
   Google Analytics Data API and enable for your project Get the credential at
   Credential tab
1. Use the hamburger menu to select API & Services » Credentials.
1. Click the pull-down menu "Create credentials". Select "Help me choose".
1. Under "What API are you using", select "Google Analytics Reports API" and
   choose "User Data" on the page
1. Fill the information. Under "Oauth Client Id" => "Application Type" select
   "Web Application".
1. Leave empty "Authorized JavaScript origins".
1. Fill in "Authorized redirect URIs" with
   "http://YOURSITEDOMAIN/admin/config/services/google-analytics-reports-api".
   Replace "YOURSITEDOMAIN" with the base URL of your site.
1. Download client secret json and copy client + secret

On the Drupal site navigate to "Configuration » System » Google Analytics
Reports API" and copy "Client ID" and "Client secret" from the Google Developers
console into the fields. Press "Start setup and authorize account" to allow the
project access to Google Analytics data.

Configuration of Google Analytics Reports module:

1. Configure Google Analytics Reports API module first.
2. Enable Charts module and Google Charts or Highcharts sub-module to see
   graphical reports.
3. Go to "admin/reports/google-analytics-reports/summary" page to see Google
   Analytics Summary report.
4. Go to "admin/structure/block" page and enable "Google Analytics Reports
   Summary Block" and/or "Google Analytics Reports Page Block" blocks.

## CACHING

Note that Google has a moderately strict Quota Policy https://developers.google
.com/analytics/devguides/reporting/core/v3/limits-quotas#core_reporting. To aid
with this limitation, this module caches query results for a time that you
specify in the admin settings. Our recommendation is at least three days.

## CREDITS

- Joel Kitching (jkitching)
- Tony Rasmussen (raspberryman)
- Dylan Tack (grendzy)
- Nickolay Leshchev (Plazik)

# Google analytics 4

## GA4 Changes Log

- checkUpdates removed, since eTag not available

## Official

- Google Analytics 4 is event-based. All hits are events. Things like Pageviews,
  Timing hits, Transaction, and other types (from Universal Analytics) are no
  longer available. Even a pageview is now an event.
- Data models are different. Google Analytics 4 is using a more flexible data
  model where things like “event category”, “event action”, etc. are no longer
  required. You can send any custom parameters you wish. But this is an
  incredibly oversimplified example.
- Data from Apps and Websites in a single property. If your business owns
  websites and mobile apps, you can now conveniently stream data to the same
  property.
- Direct (and free) integration with BigQuery. In Universal Analytics, only
  premium users had the opportunity to stream data to BigQuery. In GA4, that
  option is possible even for free accounts.
- Enhanced Measurement. Google Analytics 4 is capable of tracking more than
  pageviews (without editing the website’s code). Things like outbound link
  clicks, scrolling, Youtube video, and other interactions can be tracked
  automatically. Learn more.
- Analysis Hub. Google Analytics 4 introduced several additional reports/tools
  for analysis, such as ad-hoc funnels and pathing. Previously, these features
  were available only for users of GA360. Learn more.
- Scope. In Google Analytics 4, all events are hit-scoped. And if you want to
  apply something to a user, you can use User Properties (that are user-scoped).
  Session scope is no longer present.
- Historical data limit. In Universal Analytics, you could set your data to
  never expire. In Google Analytics 4, the data expires after 14 months (if you
  configure it manually).
- Views. In Google Analytics 4, views are no longer present. Maybe in the
  future, they will be added? Who knows! But the probability does not look very
  high right now.
- The number of predefined reports. Google Analytics 4 is way behind Universal
  Analytics if we talk about reporting capabilities. We hope that in the future,
  this area will be improved. In the meantime, people who know BigQuery can
  benefit greatly by analyzing and visualizing data outside of GA4.
- Integrations. I have already mentioned BigQuery integration. However, some
  integrations are still missing in Google Analytics 4, such as Search Console.
  But that is expected to change at any time.
- And this list is far from over…

## Drupal GA4 Module Features

-
