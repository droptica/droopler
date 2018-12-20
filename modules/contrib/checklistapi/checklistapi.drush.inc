<?php

/**
 * @file
 * Drush commands for the Checklist API module.
 */

use Drupal\Core\Render\Element;
use Drupal\user\Entity\User;

/**
 * Implements hook_drush_command().
 */
function checklistapi_drush_command() {
  $items = [];

  $items['checklistapi-list'] = [
    'aliases' => ['capi-list', 'capil'],
    'description' => 'Get an overview of your installed checklists with progress details.',
  ];
  $items['checklistapi-info'] = [
    'aliases' => ['capi-info', 'capii'],
    'description' => 'Show detailed info for a given checklist.',
    'arguments' => [
      'checklist' => 'The checklist machine name, e.g., "example_checklist".',
    ],
    'required-arguments' => TRUE,
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function checklistapi_drush_help($section) {
  switch ($section) {
    case 'meta:checklistapi:title':
      return dt('Checklist API commands');
  }
}

/**
 * Drush callback for checklist listing.
 */
function drush_checklistapi_list() {
  $definitions = checklistapi_get_checklist_info();

  if (empty($definitions)) {
    return drush_print(dt('No checklists available.'));
  }

  // Build table rows.
  $rows = [];
  // The first row is the table header.
  $rows[] = [
    dt('Checklist'),
    dt('Progress'),
    dt('Last updated'),
    dt('Last updated by'),
  ];
  foreach ($definitions as $id => $definition) {
    $checklist = checklistapi_checklist_load($id);
    $row = [];
    $row[] = dt('!title (@id)', [
      '!title' => strip_tags($checklist->title),
      '@id' => $id,
    ]);
    $row[] = dt('@completed of @total (@percent%)', [
      '@completed' => $checklist->getNumberCompleted(),
      '@total' => $checklist->getNumberOfItems(),
      '@percent' => round($checklist->getPercentComplete()),
    ]);
    $row[] = $checklist->getLastUpdatedDate();
    $row[] = $checklist->getLastUpdatedUser();
    $rows[] = $row;
  }

  return drush_format_table($rows, TRUE);
}

/**
 * Implements drush_hook_COMMAND_validate().
 *
 * @see drush_checklistapi_info()
 */
function drush_checklistapi_info_validate() {
  $arguments = drush_get_arguments();
  $id = $arguments[1];
  $checklist = checklistapi_checklist_load($id);

  // Make sure the given checklist exists.
  if (!$checklist) {
    drush_set_error('CHECKLISTAPI_INVALID_ARGUMENT', dt('No such checklist "@id".', [
      '@id' => $id,
    ]));
  }

  drush_set_context('checklist', $checklist);
}

/**
 * Drush callback for checklist info.
 */
function drush_checklistapi_info() {
  $checklist = drush_get_context('checklist');

  // Print the help.
  if (!empty($checklist->help)) {
    drush_print(strip_tags($checklist->help));
  }

  // Print last updated and progress details.
  if ($checklist->hasSavedProgress()) {
    drush_print('');
    drush_print(dt('Last updated @date by @user', [
      '@date' => $checklist->getLastUpdatedDate(),
      '@user' => $checklist->getLastUpdatedUser(),
    ]));
    drush_print(dt('@completed of @total (@percent%) complete', [
      '@completed' => $checklist->getNumberCompleted(),
      '@total' => $checklist->getNumberOfItems(),
      '@percent' => round($checklist->getPercentComplete()),
    ]));
  }

  // Loop through groups.
  $groups = $checklist->items;
  foreach (Element::children($groups) as $group_key) {
    $group = &$groups[$group_key];

    // Print group title.
    drush_print('');
    drush_print(strip_tags($group['#title']) . ':');

    // Loop through items.
    foreach (Element::children($group) as $item_key) {
      $item = &$group[$item_key];
      $saved_item = !empty($checklist->savedProgress['#items'][$item_key]) ? $checklist->savedProgress['#items'][$item_key] : 0;
      // Build title.
      $title = strip_tags($item['#title']);
      if ($saved_item) {
        // Append completion details.
        $user = User::load($saved_item['#uid']);
        $title .= ' - ' . dt('Completed @time by @user', [
          '@time' => format_date($saved_item['#completed'], 'short'),
          '@user' => $user->getUsername(),
        ]);
      }
      // Print the list item.
      drush_print(dt(' [@x] !title', [
        '@x' => ($saved_item) ? 'x' : ' ',
        '!title' => $title,
      ]));
    }
  }
}
