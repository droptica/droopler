<?php

/**
 * @file
 * Administration functions for features.module.
 */

use Drupal\Core\Render\Element;
Use \Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;

/**
 * Returns HTML for the features listing form.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @ingroup themeable
 */
function theme_features_listing(array $variables) {
  $form = $variables['form'];
  $renderer = \Drupal::service('renderer');

  // Individual table headers.
  $rows = [];
  // Iterate through all the features, which are children of this element.
  foreach (Element::children($form) as $key) {
    // Stick the key into $module for easier access.
    $element = $form[$key];
    // Create the row for the table.
    $row = [];
    // Add the checkbox into the first cell.
    unset($element['enable']['#title']);

    $row[] = ['class' => ['checkbox'], 'data' => $renderer->render($element['enable'])];

    // Add the module label and expand/collapse functionalty.
    $id = Html::getUniqueId('feature-' . $key);
    $col2 = new FormattableMarkup('<label id="@id" for="@for" class="module-name table-filter-text-source">@name</label>',
      [
        '@id' => $id,
        '@for' => $element['enable']['#id'],
        '@name' => $renderer->render($element['name']),
      ]
    );
    $row[] = ['class' => ['module'], 'data' => $col2];

    $row[] = ['class' => ['machine_name'], 'data' => $renderer->render($element['machine_name'])];

    $description = t('@details', ['@details' => $renderer->render($element['details'])]);
    $details = [
      '#type' => 'details',
      '#title' => new FormattableMarkup('<span class="text">@desc</span>', [ '@desc' => $renderer->render($element['description'])]),
      '#attributes' => ['id' => $element['enable']['#id'] . '-description'],
      '#description' => $description,
    ];
    $row[] = [
      'class' => ['description', 'expand'],
      'data' => $renderer->render($details),
    ];
    $row[] = [
      'class' => ['feature-version'],
      'data' => $renderer->render($element['version']),
    ];
    $row[] = [
      'class' => ['feature-state'],
      'data' => $renderer->render($element['state']),
    ];

    $rows[] = ['data' => $row];
  }

  $table = [
    '#type' => 'tableselect',
    '#header' => $form['#header'],
    '#options' => $rows,
    '#empty' => t('No Features packages available.'),
  ];
  return $renderer->render($table);
}

/**
 * Prepares variables for package assignment configuration form.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 */
function template_preprocess_features_assignment_configure_form(&$variables) {
  $form =& $variables['form'];

  $header = [
    t('Assignment method'),
    t('Description'),
    t('Enabled'),
    t('Weight'),
  ];

  // If there is at least one operation enabled, show the operation column.
  if ($form['#show_operations']) {
    $header[] = t('Operations');
  }

  $table = [
    '#type' => 'table',
    '#weight' => 5,
    '#header' => $header,
    '#attributes' => ['id' => 'features-assignment-methods'],
    '#tabledrag' => [
      [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'assignment-method-weight',
      ],
    ],
  ];

  foreach ($form['title'] as $id => $element) {
    // Do not take form control structures.
    if (is_array($element) && Element::child($id)) {
      $table[$id]['#attributes']['class'][] = 'draggable';
      $table[$id]['#weight'] = $element['#weight'];

      $table[$id]['title'] = [
        '#prefix' => '<strong>',
        $form['title'][$id],
        '#suffix' => '</strong>',
      ];
      $table[$id]['description'] = $form['description'][$id];
      $table[$id]['enabled'] = $form['enabled'][$id];
      $table[$id]['weight'] = $form['weight'][$id];
      if ($form['#show_operations']) {
        $table[$id]['operation'] = $form['operation'][$id];
      }
      // Unset to prevent rendering along with children.
      unset($form['title'][$id]);
      unset($form['description'][$id]);
      unset($form['enabled'][$id]);
      unset($form['weight'][$id]);
      unset($form['operation'][$id]);
    }
  }

  // For some reason, the #weight is not being handled by drupal_render.
  // So we remove the actions and then put them back into the form after the
  // table.
  $actions = $form['actions'];
  unset($form['actions']);
  $form['table'] = $table;
  $form['actions'] = $actions;
}

/**
 * Themes individual items in an item list.
 */
function theme_features_items(array $variables) {
  $items = $variables['items'];

  $list = [];
  foreach ($items as $item) {
    $class = !empty($item['class']) ? $item['class'] : '';
    $list[] = '<span class="features-item ' . $class . '" title="' . $item['name'] . '">' . $item['label'] . '</span>';
  }

  return '<span class="features-item-list">' . implode(' ', $list) . '</span>';
}

/**
 * Themes the assignment form.
 */
function theme_assignment_form(array $variables) {
  $renderer = \Drupal::service('renderer');
  return $renderer->render($variables['form']);
}
