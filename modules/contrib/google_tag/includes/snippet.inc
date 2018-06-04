<?php

/**
 * @file
 * Contains the JavaScript snippet insertion code.
 *
 * @author Jim Berry ("solotandem", http://drupal.org/user/240748)
 */

/**
 * Returns JavaScript snippets.
 *
 * @return array
 *   Associative array of snippets keyed by type: script, noscript and
 *   data_layer.
 */
function google_tag_snippets() {
  $snippets = [
    'script' => _google_tag_script_snippet(),
    'noscript' => _google_tag_noscript_snippet(),
    'data_layer' => _google_tag_data_layer_snippet(),
  ];
  // Allow other modules to alter the snippets.
  \Drupal::moduleHandler()->alter('google_tag_snippets', $snippets);
  return $snippets;
}

/**
 * Returns JavaScript script snippet.
 *
 * @return array
 *   The script snippet.
 */
function _google_tag_script_snippet() {
  // Gather data.
  $config = \Drupal::config('google_tag.settings');
  $container_id = _google_tag_variable_clean('container_id');
  $data_layer = _google_tag_variable_clean('data_layer');
  $query = _google_tag_environment_query();
  $compact = $config->get('compact_snippet');

  // Build script snippet.
  $script = <<<EOS
(function(w,d,s,l,i){

  w[l]=w[l]||[];
  w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
  var f=d.getElementsByTagName(s)[0];
  var j=d.createElement(s);
  var dl=l!='dataLayer'?'&l='+l:'';
  j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl+'$query';
  j.async=true;
  f.parentNode.insertBefore(j,f);

})(window,document,'script','$data_layer','$container_id');
EOS;
  if ($compact) {
    $script = str_replace(["\n", '  '], '', $script);
  }
/*
  $script = <<<EOS
<!-- Google Tag Manager -->
$script
<!-- End Google Tag Manager -->
EOS;
*/
  return $script;
}

/**
 * Returns JavaScript noscript snippet.
 *
 * @return array
 *   The noscript snippet.
 */
function _google_tag_noscript_snippet() {
  // Gather data.
  $config = \Drupal::config('google_tag.settings');
  $container_id = _google_tag_variable_clean('container_id');
  $query = _google_tag_environment_query();
  $compact = $config->get('compact_snippet');

  // Build noscript snippet.
  // @todo Core removes the 'style' attribute from the snippet; so omit it.
  // style="display:none;visibility:hidden"
  $noscript = <<<EOS
<noscript aria-hidden="true"><iframe src="https://www.googletagmanager.com/ns.html?id=$container_id$query"
 height="0" width="0" title="Google Tag Manager"></iframe></noscript>
EOS;
  if ($compact) {
    $noscript = str_replace("\n", '', $noscript);
  }
/*
  $noscript = <<<EOS
<!-- Google Tag Manager -->
$noscript
<!-- End Google Tag Manager -->
EOS;
*/
  return $noscript;
}

/**
 * Returns JavaScript data layer snippet or adds items to data layer.
 *
 * @return string|null
 *   The data layer snippet or NULL.
 */
function _google_tag_data_layer_snippet() {
  // Gather data.
  $config = \Drupal::config('google_tag.settings');
  $data_layer = _google_tag_variable_clean('data_layer');
  $whitelist = $config->get('whitelist_classes');
  $blacklist = $config->get('blacklist_classes');

  $classes = [];
  $names = ['whitelist', 'blacklist'];
  foreach ($names as $name) {
    $$name = explode("\n", $$name);
    if (empty($$name)) {
      continue;
    }
    $classes["gtm.$name"] = $$name;
  }

  if ($classes) {
    // Build data layer snippet.
    $script = "var $data_layer = [" . json_encode($classes) . '];';
    return $script;
  }
}

/**
 * Returns a query string with the environment parameters.
 *
 * @return string
 *   The query string.
 */
function _google_tag_environment_query() {
  $config = \Drupal::config('google_tag.settings');
  if (!$config->get('include_environment')) {
    return '';
  }

  // Gather data.
  $environment_id = _google_tag_variable_clean('environment_id');
  $environment_token = _google_tag_variable_clean('environment_token');

  // Build query string.
  return "&gtm_auth=$environment_token&gtm_preview=$environment_id&gtm_cookies_win=x";
}

/**
 * Returns a cleansed variable.
 *
 * @param string $variable
 *   The variable name.
 *
 * @return string
 *   The cleansed variable.
 */
function _google_tag_variable_clean($variable) {
  $config = \Drupal::config('google_tag.settings');
  return trim(json_encode($config->get($variable)), '"');
}
