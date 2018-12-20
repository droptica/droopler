<?php

/**
 * @file
 * Defines theme functions for the Search API module.
 */

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\Utility;

/**
 * Returns HTML for a fields form table.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element representing the form.
 *
 * @return string
 *   The rendered HTML for a fields form table.
 *
 * @ingroup themeable
 */
function theme_search_api_admin_fields_table(array $variables) {
  $form = $variables['element'];
  $rows = [];
  if (!empty($form['fields'])) {
    foreach (Element::children($form['fields']) as $name) {
      $row = [];
      foreach (Element::children($form['fields'][$name]) as $field) {
        if ($cell = render($form['fields'][$name][$field])) {
          $row[] = $cell;
        }
      }
      $row = [
        'data' => $row,
        'data-field-row-id' => $name,
      ];
      if (!empty($form['fields'][$name]['description']['#value'])) {
        $row['title'] = strip_tags($form['fields'][$name]['description']['#value']);
      }
      $rows[] = $row;
    }
  }

  $note = isset($form['note']) ? $form['note'] : '';
  unset($form['note'], $form['submit']);
  $output = '';
  foreach (Element::children($form) as $key) {
    if (!empty($form[$key])) {
      $output .= render($form[$key]);
    }
  }

  $build = [
    '#theme' => 'table',
    '#header' => $form['#header'],
    '#rows' => $rows,
    '#empty' => t('No fields have been added for this datasource.'),
  ];

  $output .= render($build);
  $output .= render($note);

  return $output;
}

/**
 * Returns HTML for the data type overview table.
 *
 * @param array $variables
 *   An associative array containing:
 *   - data_types: An associative array of data types, keyed by data type ID and
 *     containing associative arrays with information about the data type:
 *     - label: The (translated) human-readable label for the data type.
 *     - description: The (translated) description of the data type.
 *     - fallback: The type ID of the fallback type.
 *   - fallback_mapping: array of fallback data types for unsupported data
 *     types.
 *
 * @return string
 *   The rendered HTML for a fields form table.
 *
 * @ingroup themeable
 */
function theme_search_api_admin_data_type_table(array $variables) {
  $data_types = $variables['data_types'];
  $fallback_mapping = $variables['fallback_mapping'];
  $header = [
    t('Data Type'),
    t('Description'),
    t('Supported'),
  ];
  // Only show the column with fallback types if there is actually an
  // unsupported type listed.
  if ($fallback_mapping) {
    $header[] = t('Fallback data type');
  }

  $rows = [];
  $yes = t('Yes');
  $yes_img = 'core/misc/icons/73b355/check.svg';
  $no = t('No');
  $no_img = 'core/misc/icons/e32700/error.svg';
  foreach ($data_types as $data_type_id => $data_type) {
    $has_fallback = isset($fallback_mapping[$data_type_id]);
    $supported_label = $has_fallback ? $no : $yes;
    $supported_icon = [
      '#theme' => 'image',
      '#uri' => $has_fallback ? $no_img : $yes_img,
      '#width' => 18,
      '#height' => 18,
      '#alt' => $supported_label,
      '#title' => $supported_label,
    ];

    $row = [
      $data_type['label'],
      $data_type['description'],
      ['data' => $supported_icon],
    ];

    if ($fallback_mapping) {
      $row[] = $has_fallback ? $data_types[$data_type['fallback']]['label'] : '';
    }

    $rows[] = $row;
  }

  $build = [
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  ];

  return render($build);
}

/**
 * Returns HTML for a list of form items.
 *
 * Wrapper around the "item_list" theme which uses the child elements as the
 * list items instead of the "#items" key.
 *
 * @param array $variables
 *   An associative array containing a single value, "element", containing the
 *   element to be rendered.
 *
 * @return string
 *   The rendered HTML for the list.
 *
 * @ingroup themeable
 */
function theme_search_api_form_item_list(array $variables) {
  $element = $variables['element'];

  $build = [
    '#theme' => 'item_list',
  ];
  if (!empty($element['#title'])) {
    $build['#title'] = $element['#title'];
  }
  foreach (Element::children($element) as $key) {
    $build['#items'][$key] = $element[$key];
  }

  return render($build);
}

/**
 * Returns HTML for a search server.
 *
 * @param array $variables
 *   An associative array containing:
 *   - server: The server that should be displayed.
 *
 * @return string
 *   The rendered HTML for a search server.
 *
 * @ingroup themeable
 */
function theme_search_api_server(array $variables) {
  // Get the search server.
  /** @var \Drupal\search_api\ServerInterface $server */
  $server = $variables['server'];

  $output = '';

  if (($description = $server->getDescription())) {
    // Sanitize the description and append to the output.
    $output .= '<p class="description">' . nl2br(Html::escape($description)) . '</p>';
  }

  // Initialize the $rows variable which will hold the different parts of server
  // information.
  $rows = [];
  // Create a row template with references so we don't have to deal with the
  // complicated structure for each individual row.
  $row = [
    'data' => [
      ['header' => TRUE],
      '',
    ],
    'class' => [''],
  ];
  // Get the individual parts of the row by reference.
  $label = &$row['data'][0]['data'];
  $info = &$row['data'][1];
  $classes = &$row['class'];

  // Check if the server is enabled.
  if ($server->status()) {
    $classes[] = 'ok';
    $info = t('enabled (<a href=":url">disable</a>)', [':url' => $server->toUrl('disable')->toString()]);
  }
  else {
    $classes[] = 'warning';
    $info = t('disabled (<a href=":url">enable</a>)', [':url' => $server->toUrl('enable')->toString()]);
  }
  // Append the row and reset variables.
  $label = t('Status');
  $classes[] = 'search-api-server-summary--status';
  $rows[] = Utility::deepCopy($row);
  $classes = [];

  // Check if the backend used by the server is valid and get its label.
  if ($server->hasValidBackend()) {
    $backend = $server->getBackend();
    $info = Html::escape($backend->label());
  }
  else {
    $classes[] = 'error';
    $info = t('Invalid or missing backend plugin: %backend_id', ['%backend_id' => $server->getBackendId()]);
  }

  // Append the row and reset variables.
  $label = t('Backend class');
  $classes[] = 'search-api-server-summary--backend';
  $rows[] = Utility::deepCopy($row);
  $classes = [];

  // Build the indexes links container.
  $indexes = [
    '#theme' => 'links',
    '#attributes' => ['class' => ['inline']],
    '#links' => [],
  ];
  // Add links for all indexes attached to this server.
  foreach ($server->getIndexes() as $index) {
    $indexes['#links'][] = [
      'title' => $index->label(),
      'url' => $index->toUrl('canonical'),
    ];
  }
  // Check if the indexes variable contains links.
  if ($indexes['#links']) {
    $label = t('Search indexes');
    $info = render($indexes);
    $classes[] = 'search-api-server-summary--indexes';
    $rows[] = Utility::deepCopy($row);
    $classes = [];
  }

  // Add backend-specific additional information.
  foreach ($server->viewSettings() as $information) {
    // Convert the extra information and append the information to the row.
    $label = $information['label'];
    $info = $information['info'];
    if (!empty($information['status'])) {
      $classes[] = $information['status'];
    }
    $rows[] = Utility::deepCopy($row);
    $classes = [];
  }

  // Append the server info table to the output.
  $server_info_table = [
    '#theme' => 'table',
    '#rows' => $rows,
    '#attributes' => [
      'class' => [
        'search-api-server-summary',
      ],
    ],
  ];
  $output .= render($server_info_table);

  return $output;
}

/**
 * Implements hook_preprocess_search_api_index().
 */
function search_api_preprocess_search_api_index(array &$variables) {
  /** @var \Drupal\search_api\IndexInterface $index */
  $index = $variables['index'];

  if ($index->status()) {
    try {
      $variables['server_count'] = $index->query()
        ->setProcessingLevel(QueryInterface::PROCESSING_NONE)
        ->addTag('server_index_status')
        ->range(0, 0)
        ->execute()
        ->getResultCount();
    }
    catch (SearchApiException $e) {
      $variables['server_count_error'] = $e->getMessage();
    }
  }
}

/**
 * Returns HTML for a search index.
 *
 * @param array $variables
 *   An associative array containing:
 *   - index: The search index to display.
 *   - server_count: The count of items on the server for this index.
 *   - server_count_error: If set, the error that occurred while trying to get
 *     the server items count.
 *
 * @return string
 *   The rendered HTML for a search index.
 *
 * @ingroup themeable
 */
function theme_search_api_index(array $variables) {
  // Get the index.
  /** @var \Drupal\search_api\IndexInterface $index */
  $index = $variables['index'];
  $server = $index->hasValidServer() ? $index->getServerInstance() : NULL;
  $tracker = $index->hasValidTracker() ? $index->getTrackerInstance() : NULL;

  $output = '';

  if (($description = $index->getDescription())) {
    // Sanitize the description and append to the output.
    $output .= '<p class="description">' . nl2br(Html::escape($description)) . '</p>';
  }

  // Initialize the $rows variable which will hold the different parts of server
  // information.
  $rows = [];
  // Create a row template with references so we don't have to deal with the
  // complicated structure for each individual row.
  $row = [
    'data' => [
      ['header' => TRUE],
      '',
    ],
    'class' => [],
  ];
  // Get the individual parts of the row by reference.
  $label = &$row['data'][0]['data'];
  $info = &$row['data'][1];
  $classes = &$row['class'];

  // Check if the index is enabled.
  if ($index->status()) {
    $classes[] = 'ok';
    $info = t('enabled (<a href=":url">disable</a>)', [':url' => $index->toUrl('disable')->toString()]);
  }
  // Check if a server is available and enabled.
  elseif ($server && $server->status()) {
    $classes[] = 'warning';
    $info = t('disabled (<a href=":url">enable</a>)', [':url' => $index->toUrl('enable')->toString()]);
  }
  else {
    $classes[] = 'warning';
    $info = t('disabled');
  }
  // Append the row and reset variables.
  $label = t('Status');
  $classes[] = 'search-api-index-summary--status';
  $rows[] = Utility::deepCopy($row);
  $classes = [];

  foreach ($index->getDatasourceIds() as $datasource_id) {
    // Check if the datasource is valid.
    if ($index->isValidDatasource($datasource_id)) {
      $info = $index->getDatasource($datasource_id)->label();
      if ($tracker) {
        $args = [
          '@indexed' => $tracker->getIndexedItemsCount($datasource_id),
          '@total' => $tracker->getTotalItemsCount($datasource_id),
        ];
        $indexed = t('@indexed/@total indexed', $args);
        $args = [
          '@datasource' => $info,
          '@indexed' => $indexed,
        ];
        $info = new FormattableMarkup('@datasource <small>(@indexed)</small>', $args);
      }
    }
    else {
      $classes[] = 'error';
      $info = t('Invalid or missing datasource plugin: %datasource_id', ['%datasource_id' => $datasource_id]);
    }
    // Append the row and reset variables.
    $label = t('Datasource');
    $classes[] = 'search-api-index-summary--datasource';
    $rows[] = Utility::deepCopy($row);
    $classes = [];
  }

  // Check if the tracker is valid.
  if ($tracker) {
    $info = $tracker->label();
  }
  else {
    $classes[] = 'error';
    $info = t('Invalid or missing tracker plugin: %tracker_id', ['%tracker_id' => $index->getTrackerId()]);
  }
  // Append the row and reset variables.
  $label = t('Tracker');
  $classes[] = 'search-api-index-summary--tracker';
  $rows[] = Utility::deepCopy($row);
  $classes = [];

  // Check if a server is available.
  $classes[] = 'search-api-index-summary--server';
  if ($server) {
    $label = t('Server');
    $info = $server->toLink(NULL, 'canonical')->toString();
    $rows[] = Utility::deepCopy($row);
  }
  elseif ($index->getServerId()) {
    $classes[] = 'error';
    $label = t('Server');
    $info = t('Unknown server set for index: %server_id', ['%server_id' => $index->getServerId()]);
    $rows[] = Utility::deepCopy($row);
  }
  $classes = [];

  // Check if the index is enabled.
  if ($index->status()) {
    $label = t('Server index status');
    if (isset($variables['server_count'])) {
      $vars = [':url' => Url::fromUri('https://drupal.org/node/2009804#server-index-status')->toString()];
      // Build the server index status info.
      $info = \Drupal::translation()->formatPlural($variables['server_count'], 'There is 1 item indexed on the server for this index. (<a href=":url">More information</a>)', 'There are @count items indexed on the server for this index. (<a href=":url">More information</a>)', $vars);
    }
    else {
      $args = ['@message' => $variables['server_count_error']];
      $info = t('Error while checking server index status: @message', $args);
      $classes[] = 'error';
    }
    $classes[] = 'search-api-index-summary--server-index-status';
    $rows[] = Utility::deepCopy($row);
    $classes = [];

    $cron_limit = $index->getOption('cron_limit', \Drupal::config('search_api.settings')->get('default_cron_limit'));
    // Check if the cron limit is higher than zero.
    if ($cron_limit != 0) {
      $classes[] = 'ok';
      if ($cron_limit > 0) {
        $info = \Drupal::translation()->formatPlural($cron_limit, 'During cron runs, 1 item will be indexed per batch.', 'During cron runs, @count items will be indexed per batch.');
      }
      else {
        $info = t('All items will be indexed at once during cron runs.');
      }
    }
    else {
      $classes[] = 'warning';
      $info = t('No items will be indexed during cron runs.');
    }
    // Append the row and reset variables.
    $label = t('Cron batch size');
    $classes[] = 'search-api-index-summary--cron-batch-size';
    $rows[] = Utility::deepCopy($row);
    $classes = [];

    // Add the indexing progress bar.
    if ($tracker) {
      $indexed_count = $tracker->getIndexedItemsCount();
      $total_count = $tracker->getTotalItemsCount();

      $index_progress = [
        '#theme' => 'progress_bar',
        '#percent' => $total_count ? (int) (100 * $indexed_count / $total_count) : 100,
        '#message' => t('@indexed/@total indexed', ['@indexed' => $indexed_count, '@total' => $total_count]),
      ];
      $output .= '<h3>' . t('Index status') . '</h3>';
      $output .= '<div class="search-api-index-status">' . render($index_progress) . '</div>';
    }
  }

  // Append the index info table to the output.
  $index_info_table = [
    '#theme' => 'table',
    '#rows' => $rows,
    '#attributes' => [
      'class' => [
        'search-api-index-summary',
      ],
    ],
  ];
  $output .= render($index_info_table);

  return $output;
}
