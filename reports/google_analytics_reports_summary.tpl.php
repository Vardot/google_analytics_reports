<?php
/**
 * @file
 * Theme implementation to display the Google Analytics summary.
 */
?>

<div class="google-analytics-summary">
  <div class="google-analytics-visits">
    <h3><?php print t('Visits Over the Past 30 Days'); ?></h3>
    <?php print $visit_chart; ?>
  </div>

  <div class="google-analytics-usage">
    <h3><?php print t('Site Usage'); ?></h3>
    <table>
      <tr>
        <td><?php print $visits; ?></td>
        <th>Visits</th>
        <td><?php print $bounces; ?></td>
        <th>Bounce Rate</th>
      </tr>
      <tr>
        <td><?php print $pageviews; ?></td>
        <th>Pageviews</th>
        <td><?php print $timeOnSite; ?></td>
        <th>Avg. Time on Site</th>
      </tr>
      <tr>
        <td>@TODO</td>
        <th>Pages/Visit</th>
        <td>@TODO</td>
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