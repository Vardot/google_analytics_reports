<h3><?php print t('Visits Over the Past 30 Days'); ?></h3>
<?php print $visit_chart; ?>

<h3><?php print t('Site Usage'); ?></h3>
<table>
  <tr>
    <td><?php print $visits; ?></td>
    <td>Visits</td>
    <td>@TODO</td>
    <td>Bounce Rate</td>
  </tr>
  <tr>
    <td><?php print $pageviews; ?></td>
    <td>Pageviews</td>
    <td>@TODO</td>
    <td>Avg. Time on Site</td>
  </tr>
  <tr>
    <td>@TODO</td>
    <td>Pages/Visit</td>
    <td>@TODO</td>
    <td>% New Visits</td>
  </tr>
</table>

<h3><?php print t('Top Pages'); ?></h3>
<?php print $pages; ?>

<h3><?php print t('Top Referrals'); ?></h3>
<?php print $referrals; ?>

<h3><?php print t('Top Searches'); ?></h3>
<?php print $searches; ?>