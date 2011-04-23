<?php
/**
 * @file
 * Theme implementation to display the Google Analytics summary.
 * @FIXME - translate strings
 */
?>

<div class="google-analytics-summary google-analytics-reports">
  <div class="google-analytics-visits">
    <h3><?php print t('Visits Over the Past 30 Days'); ?></h3>
    <?php print $visit_chart; ?>
  </div>

  <div class="google-analytics-usage">
    <h3><?php print t('Site Usage'); ?></h3>
    <table>
      <tr class="odd">
        <td><?php print $entrances; ?></td>
        <th>Visits</th>
        <td><?php print $bounces; ?></td>
        <th>Bounce Rate</th>
      </tr>
      <tr class="even">
        <td><?php print $pageviews; ?></td>
        <th>Pageviews</th>
        <td><?php print $timeOnSite; ?></td>
        <th>Avg. Time on Site</th>
      </tr>
      <tr class="odd">
        <td><?php print $pages_per_visit; ?></td>
        <th>Pages/Visit</th>
        <td><?php print $newVisits ?></td>
        <th>% New Visits</th>
      </tr>
    </table>
  </div>

  <div class="google-analytics-pages">
    <h3><?php print t('Top Pages'); ?></h3>
    <?php print $pages; ?>
  </div>

  <div class="google-analytics-referrals">
    <h3><?php print t('Top Referrals'); ?></h3>
    <?php print $referrals; ?>
  </div>

  <div class="google-analytics-searches">
    <h3><?php print t('Top Searches'); ?></h3>
    <?php print $searches; ?>
  </div>
</div>