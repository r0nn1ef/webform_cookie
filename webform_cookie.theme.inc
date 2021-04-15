<?php

/**
 * @file
 * Theme and preprocess functions for webform_cookie module.
 */

/**
 * Implements hook_preporcess_webform_handler_cookie_summary().
 */
function webform_cookie_preprocess_webform_handler_cookie_summary(array &$variables) {
  $settings = $variables['settings'];

  $variables['name'] = trim($settings['name']);
  $variables['value'] = $settings['value'];
  $variables['expires'] = $settings['expires'] > 0 ? \Drupal::service('date.formatter')->formatInterval($settings['expires']) : t('Session');
  $variables['domain'] = trim($settings['domain']) ?: \Drupal::request()->getHost();
  $variables['secure'] = $settings['secure'] ? t('Yes') : t('No');
  $variables['http_only'] = $settings['http_only'] ? t('Yes') : t('No');
}
