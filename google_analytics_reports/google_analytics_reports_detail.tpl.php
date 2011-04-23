<?php
/**
 * @file
 * Theme implementation to display the Google Analytics detail page.
 */
?>

<?php print $pageviews_chart; ?>

<table>
  <tr class="odd">
    <td><?php print $pageviews; ?></td>
    <th>Pageviews</th>
    <td><?php print $bounce_rate; ?>%</td>
    <th>Bounce Rate</th>
  </tr>
  <tr class="even">
    <td><?php print $unique_pageviews; ?></td>
    <th>Unique Views</th>
    <td><?php print $exit_rate; ?>%</td>
    <th>Exit Rate</th>
  </tr>
  <tr class="odd">
    <td><?php print $avg_time_on_page; ?></td>
    <th>Time on Page</th>
    <td>$<?php print $goal_value; ?></td>
    <th>$ Index</th>
  </tr>
</table>

<?php print $referrals; ?>

<?php print $searches; ?>




