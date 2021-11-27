## DESCRIPTION

Google Analytics Reports module provides graphical reporting of your site's
tracking data. Graphical reports include a small path-based report in blocks and
a full path-based report.

Google Analytics Reports API module provides API for developers to access data
from Google Analytics using Analytics Data API
https://developers.google.com/analytics/devguides/reporting/data/v1

Google Analytics Reports module provide Views query plugin to create Google
Analytics reports using the Views interface.

## REQUIREMENTS

- [x] Setup account via this doc
      https://support.google.com/analytics/answer/9304153 at
      https://www.google.com/analytics

## DEPENDENCIES

- Google Analytics Reports API has no dependencies.
- Google Analytics Reports depends on Google Analytics Reports API and Views modules.

## RECOMMENDED MODULES

- Charts module https://www.drupal.org/project/charts. Enable Google Charts or
  Highcharts sub-module to see graphical reports.
- Ajax Blocks module https://www.drupal.org/project/ajaxblocks for better page loading with Google Analytics Reports blocks.

## INSTALLATION

- Install like normal Drupal module
- **Install with Composer:**

```
composer require drupal/google_analytics_reports
```

## CONFIGURATION

Configuration of Google Analytics Reports API module.

Before you can get the credentials you may need to create a new project and
enable the analytics API for it:

1. Open Google Developers Console: https://console.developers.google.com. Find
   `Google Analytics Data API` and enable for your project. 
2. Use the hamburger menu to select API & Services » Credentials.
3. Click the pull-down menu "Create credentials". Select "Help me choose".
4. Under "What API are you using", select "Google Analytics Reports API" and
   choose "User Data" on the page
5. Fill in the information. Under "Oauth Client Id" => "Application Type" select
   "Web Application".
6. Leave empty "Authorized JavaScript origins".
7. Fill in "Authorized redirect URIs" with
   "http://YOURSITEDOMAIN/admin/config/services/google-analytics-reports-api".
   Replace "YOURSITEDOMAIN" with the base URL of your site.
8. Download client secret JSON. **For security reasons Google won't let you redownload it. So, store this credential file privately also.**
9. On the Drupal site navigate to "Configuration » System » Google Analytics
Reports API", upload the JSON file and fill the property ID. Save the form.

Configuration of Google Analytics Reports module:
1. Configure the Google Analytics Reports API module first.
2. Enable Charts module and Google Charts or Highcharts sub-module to see graphical reports.
3. Go to "admin/reports/google-analytics-reports/summary" page to see Google
   Analytics Summary report.
4. Go to the "admin/structure/block" page and enable "Google Analytics Reports
   Summary Block" and/or "Google Analytics Reports Page Block" blocks.

## CACHING

Note that Google has a moderately strict Quota Policy. To aid
with this limitation, this module caches query results for a time that you
specify in the admin settings. Our recommendation is at least three days.

## CREDITS

- [Joel Kitching (jkitching)](https://www.drupal.org/user/159067)
- [Tony Rasmussen (raspberryman)](https://www.drupal.org/user/71464)
- [Dylan Tack (grendzy)](https://www.drupal.org/user/96647)
- [Nickolay Leshchev (Plazik)](https://www.drupal.org/u/plazik)
- [Vardot](https://www.drupal.org/vardot)

# Google analytics 4

## GA4 module Changes Log

- The main API object changed entirely so all old methods are no longer available.

## API changes in GA4 from Google

- Google Analytics 4 is event-based. All hits are events. Things like Pageviews,
  Timing hits, Transaction, and other types (from Universal Analytics) are no longer available. Even a pageview is now an event.
- Data models are different. Google Analytics 4 is using a more flexible data model where things like “event category”, “event action”, etc. are no longer required. You can send any custom parameters you wish. But this is an incredibly oversimplified example.
- Data from Apps and Websites in a single property. If your business owns
  websites and mobile apps, you can now conveniently stream data to the same
  property.
- Direct (and free) integration with BigQuery. In Universal Analytics, only premium users had the opportunity to stream data to BigQuery. In GA4, that option is possible even for free accounts.
- Enhanced Measurement. Google Analytics 4 is capable of tracking more than pageviews (without editing the website’s code). Things like outbound link clicks, scrolling, Youtube video, and other interactions can be tracked automatically. Learn more.
- Analysis Hub. Google Analytics 4 introduced several additional reports/tools for analysis, such as ad-hoc funnels and pathing. Previously, these features were available only for users of GA360. Learn more.
- Scope. In Google Analytics 4, all events are hit-scoped. And if you want to
  apply something to a user, you can use User Properties (that are user-scoped).
  Session scope is no longer present.
- Historical data limit. In Universal Analytics, you could set your data to never expire. In Google Analytics 4, the data expires after 14 months (if you
  configure it manually).
- Views. In Google Analytics 4, views are no longer present. Maybe in the future, they will be added? Who knows! But the probability does not look very
  high right now.
- The number of predefined reports. Google Analytics 4 is way behind Universal
  Analytics if we talk about reporting capabilities. We hope that in the future,
  this area will be improved. In the meantime, people who know BigQuery can benefit greatly by analyzing and visualizing data outside of GA4.
- Integrations. I have already mentioned BigQuery integration. However, some
  integrations are still missing in Google Analytics 4, such as Search Console.
  But that is expected to change at any time.
- And this list is far from over…
