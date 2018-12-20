<?php

/**
 * @file
 * Hooks provided by the Checklist API module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Define all checklists provided by the module.
 *
 * Any number of checklists can be defined in an implementation of this hook.
 * Checklist API will register menu items and create permissions for each one.
 *
 * For a working example, see checklistapiexample.module.
 *
 * @return array
 *   An array of checklist definitions. Each definition is keyed by an arbitrary
 *   unique identifier. The corresponding multidimensional array describing the
 *   checklist may contain the following key-value pairs:
 *   - #title: The title of the checklist.
 *   - #path: The Drupal path where the checklist will be accessed.
 *   - #callback: A callback to provide the checklist items. See
 *     callback_checklistapi_checklist_items(). (This value is technically
 *     optional in order to provide backward compatibility for modules using the
 *     deprecated method of defining checklist items right in this array.)
 *   - #description: (optional) A brief description of the checklist for its
 *     corresponding menu item.
 *   - #help: (optional) User help to be displayed in the "System help" block
 *     via hook_help().
 *   - #menu_name: (optional) The machine name of a menu to place the checklist
 *     into (e.g., "main-menu" or "navigation"). If this is omitted, Drupal will
 *     try to infer the correct menu placement from the specified path.
 *   - #weight: (optional) A floating point number used to sort the list of
 *     checklists before being output. Lower numbers appear before higher
 *     numbers.
 *   - (deprecated) Any number of arrays representing groups of items, to be
 *     presented as vertical tabs. Use #callback instead.
 *
 * @see callback_checklistapi_checklist_items()
 * @see hook_checklistapi_checklist_info_alter()
 * @see checklistapiexample_checklistapi_checklist_info()
 */
function hook_checklistapi_checklist_info() {
  $definitions = [];
  $definitions['example_checklist'] = [
    '#title' => t('Example checklist'),
    '#path' => 'example-checklist',
    '#callback' => 'callback_checklistapi_checklist_items',
    '#description' => t('An example checklist.'),
    '#help' => t('<p>This is an example checklist.</p>'),
  ];
  return $definitions;
}

/**
 * Define the checklist items for a given checklist.
 *
 * Declared in hook_checklistapi_checklist_info().
 *
 * @return array
 *   An array of arrays representing groups of items, to be presented as
 *   vertical tabs. Each group is keyed by an arbitrary identifier, unique in
 *   the scope of the checklist. The corresponding multimensional array
 *   describing the group may contain the following key-value pairs:
 *   - #title: The title of the group, used as the vertical tab label.
 *   - #description: (optional) A description of the group.
 *   - #weight: (optional) A floating point number used to sort the list of
 *     groups before being output. Lower numbers appear before higher numbers.
 *   - Any number of arrays representing checklist items. Each item is keyed by
 *     an arbitrary identifier, unique in the scope of the checklist. The
 *     corresponding multimensional array describing the item may contain the
 *     following key-value pairs:
 *     - #title: The title of the item.
 *     - #description: (optional) A description of the item, for display beneath
 *       the title.
 *     - #default_value: (optional) The default checked state of the item--TRUE
 *       for checked or FALSE for unchecked. Defaults to FALSE.
 *       This is useful for automatically checking items that can be
 *       programmatically tested (e.g., a module is installed or a configuration
 *       setting has a certain value).
 *     - #weight: (optional) A floating point number used to sort the list of
 *       items before being output. Lower numbers appear before higher
 *       numbers.
 *     - Any number of arrays representing links. Each link is keyed by an
 *       arbitrary unique identifier. The corresponding multimensional array
 *       describing the link may contain the following key-value pairs:
 *       - #text: The link text.
 *       - #url: The link url as a \Drupal\Core\Url object.
 *       - #weight: (optional) A floating point number used to sort the list of
 *         items before being output. Lower numbers appear before higher
 *         numbers.
 *
 * @see hook_checklistapi_checklist_info()
 * @see checklistapiexample_checklistapi_checklist_items()
 */
function callback_checklistapi_checklist_items() {
  return [
    'example_group' => [
      '#title' => t('Example group'),
      '#description' => t('<p>Here are some example items.</p>'),
      'example_item_1' => [
        '#title' => t('Example item 1'),
        'example_link' => [
          '#text' => t('Example.com'),
          '#url' => \Drupal\Core\Url::fromUri('http://www.example.com/'),
        ],
      ],
      'example_item_2' => [
        '#title' => t('Example item 2'),
      ],
    ],
  ];
}

/**
 * Alter checklist definitions.
 *
 * This hook is invoked by checklistapi_get_checklist_info(). The checklist
 * definitions are passed in by reference. Additional checklists may be added,
 * or existing checklists may be altered or removed.
 *
 * Note: Checklist paths (#path) cannot be altered. See
 * https://www.drupal.org/docs/8/api/routing-system/altering-existing-routes-and-adding-new-routes-based-on-dynamic-ones
 * instead.
 *
 * @param array $definitions
 *   The multidimensional array of checklist definitions returned by
 *   hook_checklistapi_checklist_info().
 *
 * @see hook_checklistapi_checklist_info()
 * @see checklistapiexample_checklistapi_checklist_info_alter()
 */
function hook_checklistapi_checklist_info_alter(array &$definitions) {
  // Add an item.
  $definitions['example_checklist']['example_group']['new_item'] = [
    'title' => t('New item'),
  ];
  // Add a group.
  $definitions['example_checklist']['new_group'] = [
    '#title' => t('New group'),
  ];
  // Move an item.
  $definitions['example_checklist']['new_group']['example_item_1'] = $definitions['example_checklist']['example_group']['example_item_1'];
  unset($definitions['example_checklist']['example_group']['example_item_1']);
  // Remove an item.
  unset($definitions['example_checklist']['example_group']['example_item_2']);
}

/**
 * @} End of "addtogroup hooks".
 */
