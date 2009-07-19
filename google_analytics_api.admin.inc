<?php
// $Id$

/**
 * The admin form.
 */
function google_analytics_api_admin() {
  $form = array();

  if (google_analytics_api_check_token()) {
    $form['login_status'] = array(
      '#value' => t('Currently you are logged in. '),
      '#weight' => -10,
    );
    $form['logout'] = array(
      '#type' => 'submit',
      '#value' => t('Logout'),
      '#weight' => -5,
    );
    $form['profile_id'] = array(
      '#type' => 'select',
      '#title' => t('Profile'),
      '#options' => google_analytics_api_admin_account_data_options(),
      '#default_value' => variable_get('google_analytics_api_profile_id', 0),
      '#description' => t('Choose your website\'s profile'),
      '#required' => TRUE,
      '#weight' => -2,
    );
    /*$profile = google_analytics_api_current_profile();
    $date = strtotime($profile->getUpdated());
    $form['profile_status'] = array(
      '#value' => '<div><em>(Profile last updated ' . date('F j, Y h:i:s A', $date) . ')</em></div>',
      '#weight' => -1,
    );*/
    $form['buttons']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save configuration'),
      '#weight' => 3,
    );
  } else {
    $form['logout_status'] = array(
      '#value' => t('Please ' . l('Log In', '/admin/settings/google_analytics_api') . ' before trying to access statistics.'),
      '#weight' => -10,
    );
    $form['buttons']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Login to a Google account'),
    );
  }

  return $form;
}

/*
 * Returns an associative array of profile IDs and their names for an account.
 */
function google_analytics_api_admin_account_data_options() {
    $accounts = google_analytics_api_account_data();
    $options = array();
    $i = 0;
    foreach ($accounts as $account)
      $options[$account->getProfileId()] = $account->getTitle() . ' (' . $account->getProfileId() . ')';
    return $options;
}

/*
 * Admin form callback, run on submission of any buttons.
 */
function google_analytics_api_admin_submit($form, &$form_state) {
  if (variable_get('google_analytics_api_token', null)) {
    if ($form_state['clicked_button']['#value'] == 'Logout') {
      drupal_set_message(t('You have been logged out of your Google account.'));
      return google_analytics_api_logout();
    } elseif ($form_state['clicked_button']['#value'] == 'Save configuration') {
      variable_set('google_analytics_api_profile_id', intval($form_state['values']['profile_id']));
    }
  } else {
    return google_analytics_api_authenticate();
  }
}
