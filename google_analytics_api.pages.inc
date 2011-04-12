<?php

/**
 * @file
 * Admin and OAuth callbacks.
 */

/**
 * Menu callback - admin form for OAuth and other settings.
 */
function google_analytics_api_admin() {

  $form = array();
  $account = google_analytics_api_account_data();
  
  /* If there is at least one profile */
  if (!empty($account->results)) {
    $options = array();
    foreach ($account->results as $profile_id => $profile) {
      $options[$profile_id] = $profile['accountName'] . ' (' . $profile['title'] . ' - ' . $profile['webPropertyId'] . ')';
    }

    $form['ga'] = array(
      '#type' => 'fieldset',
      '#title' => t('Google Analytics settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#weight' => 1,
    );
    $form['ga']['google_analytics_reports_profile_id'] = array(
      '#type' => 'select',
      '#title' => t('Reports profile'),
      '#options' => $options,
      '#default_value' => variable_get('google_analytics_reports_profile_id', 0),
      '#description' => t("Choose your Google Analytics profile."),
      '#required' => TRUE,
    );
    $form['ga']['profile_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save profile'),
    );
    $form['revoke'] = array(
      '#type' => 'fieldset',
      '#title' => t('Revoke access and logout'),
      '#description' => t('Revoke your access token to Google Analytics.  This action will log you out of your Google Analytics account and stop all reports from displaying on your site.'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#weight' => 5,
    );
    $form['revoke']['revoke_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Revoke access token'),
    );
  }
  /* Else, there are no profiles, and we should just leave it at setup */
  else {
    $form['setup'] = array(
      '#type' => 'fieldset',
      '#title' => t('Initial setup'),
      '#description' => t("When you submit this form, you will be redirected to Google for authentication.  Login with the account that has credentials to the Google Analytics profile you'd like to use."),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['setup']['google_analytics_reports_hd'] = array(
      '#type' => 'textfield',
      '#title' => t('Google Apps for Business Domain (optional)'),
      '#description' => t('Provide the domain name (example.com) if your domain is registered with Google Apps for Business.  Otherwise, leave blank.'),
      '#default_value' => variable_get('google_analytics_reports_hd', ''),
    );
   $form['setup']['setup_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Start setup and authorize account'),
    );
  }
  return $form;
}

/**
 * Submit handler.  Steps throuh the OAuth process, revokes tokens, saves profiles.
 */
function google_analytics_api_admin_submit($form, &$form_state) {
  $op = isset($form_state['values']['op']) ? $form_state['values']['op'] : '';
  cache_clear_all('GAFeed', 'cache', '*');
  switch ($op) {
    case t('Start setup and authorize account'):
        variable_set('google_analytics_reports_hd', $form_state['values']['google_analytics_reports_hd']);
        /* Anonymous keys are a Google default */
        $key = variable_get('google_analytics_reports_consumer_key', 'anonymous');
        $secret = variable_get('google_analytics_reports_consumer_secret', 'anonymous');
        $GAFeed = new GAFeed($key, $secret);

        /* Step #1 of OAuth */
        $token = $GAFeed->getRequestToken();      
        $_SESSION['google_analytics_reports_oauth']['token'] = $token;
        $_SESSION['google_analytics_reports_oauth']['destination'] = $_GET['q'];

        /* Step #2 of OAuth */
        $GAFeed->obtainAuthorization($token);
      break;  
    case t('Save profile'):
        variable_set('google_analytics_reports_profile_id', $form_state['values']['google_analytics_reports_profile_id']);
        drupal_set_message(t('Profile ID has been saved successfully.'));
      break;  
    case t('Revoke access token'):
        google_analytics_api_revoke();
        drupal_set_message(t('Access token has been successfully revoked.'));
      break;  
  }
}

/**
 * Page callback - Provided for Google to call back during the OAuth process.
 */
function google_analytics_reports_oauth_callback(&$form_state) {
  $form['#post']['oauth_token'] = $_GET['oauth_token'];
  $form['#post']['oauth_verifier'] = $_GET['oauth_verifier'];

  $form['oauth_token'] = array(
    '#type' => 'hidden',
    '#default_value' => $_GET['oauth_token'],
  );
  $form['oauth_verifier'] = array(
    '#type' => 'hidden',
    '#default_value' => $_GET['oauth_verifier'],
  );

  return $form;
}

/**
 * Validation handler.  Verify that we have OAuth tokens.  If so, 
 * request the Access Token.
 */
function google_analytics_reports_oauth_callback_validate($form, &$form_state) {
  $key = variable_get('google_analytics_reports_consumer_key', 'anonymous');
  $secret = variable_get('google_analytics_reports_consumer_secret', 'anonymous');

  $form_state['google_analytics_reports_oauth'] = $_SESSION['google_analytics_reports_oauth'];
  unset($_SESSION['google_analytics_reports_oauth']);

  $token = $form_state['google_analytics_reports_oauth']['token'];
  if (!is_array($token) || !$key || !$secret) {
    form_set_error('oauth_token', t('Invalid Google Analytics OAuth request'));
  }

  if ($token['oauth_token'] != $form_state['values']['oauth_token']) {
    form_set_error('oauth_token', t('Invalid OAuth token.'));
  }

  $GAFeed = new GAFeed($key, $secret, $token['oauth_token'], $token['oauth_token_secret']);

  /* Google required the verifier */
  $GAFeed->setVerifier($form_state['values']['oauth_verifier']);
  $response = $GAFeed->getAccessToken();
  $form_state['google_analytics_reports_oauth']['response'] = $response;
}

/**
 * Submit handler.  Save the results of OAuth.
 */
function google_analytics_reports_oauth_callback_submit(&$form, &$form_state) {
  $key = variable_get('google_analytics_reports_consumer_key', 'anonymous');
  $secret = variable_get('google_analytics_reports_consumer_secret', 'anonymous');
  $response = $form_state['google_analytics_reports_oauth']['response'];

  variable_set('google_analytics_reports_oauth_token', $response['oauth_token']);
  variable_set('google_analytics_reports_oauth_token_secret', $response['oauth_token_secret']);
  
  $form_state['redirect'] = $form_state['google_analytics_reports_oauth']['destination'];
  $form['#programmed'] = FALSE;
}