<?php

/**
 * @file
 * Drush commands related to the Modules weight module.
 */

define('MODULES_WEIGHT_GREEN_OUTPUT', "\033[1;32;40m\033[1m[%s]\033[0m");
define('MODULES_WEIGHT_RED_OUTPUT', "\033[31;40m\033[1m[%s]\033[0m");

/**
 * Implements hook_drush_command().
 */
function modules_weight_drush_command() {
  $items['mw-show-system-modules'] = [
    'description' => dt('Configures if we can reorder the core modules.'),
    'arguments' => [
      'status' => dt('The status option (on, off).'),
    ],
    'examples' => [
      'mw-show-system-modules' => dt('Shows if we can reorder the core modules or not.'),
      'mw-show-system-modules on' => dt('Allows reorder the core modules.'),
      'mw-show-system-modules off' => dt("Don't allows reorder the core modules."),
    ],
    'aliases' => ['mw-ssm'],
  ];

  $items['mw-reorder'] = [
    'description' => dt('Configures the modules weight.'),
    'arguments' => [
      'module' => dt('The module machine name.'),
      'weight' => dt('The module weight.'),
    ],
    'options' => [
      'minus' => [
        'description' => dt('If the option is present the weight will be consider as a negative value. Read for more information https://drupal.stackexchange.com/q/246298/28275 .'),
      ],
    ],
    'examples' => [
      'mw-reorder node_revision_delete' => dt('Show the node_revision_delete module weight.'),
      'mw-reorder onlyone -5 --minus' => dt('Set the onlyone module weight to -5.'),
      'mw-reorder no_autocomplete 15' => dt("Set the no_autocomplete module weight to 15."),
    ],
    'aliases' => ['mw-r'],
  ];

  $field_labels = [
    'name' => dt('Name'),
    'machine_name' => dt('Machine Name'),
    'weight' => dt('Weight'),
    'package' => dt('Package'),
  ];

  $items['mw-list'] = [
    'description' => dt('Shows the modules weight list.'),
    'options' => [
      'force' => [
        'description' => dt('If the option is present the core modules will be shown even if the option to allow it is disabled.'),
      ],
    ],
    'outputformat' => [
      'default' => 'table',
      'pipe-format' => 'list',
      'field-labels' => $field_labels,
      'output-data-type' => 'format-table',
    ],
    'examples' => [
      'mw-list' => dt('Shows the modules weight list.'),
      'mw-list --force' => dt('Shows the modules weight list with the core modules even if the option to allow it is disabled.'),
    ],
    'aliases' => ['mw-l'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function modules_weight_drush_help($section) {
  switch ($section) {
    case 'meta:modules_weight:title':
      return dt("Modules weight commands");

    case 'meta:modules_weight:summary':
      return dt("Interacts with the Modules weight module's functionalities.");

    case 'drush:mw-show-system-modules':
      return dt('Configures if we can reorder the core modules.');

    case 'drush:mw-reorder':
      return dt('Configures the module weight.');

    case 'drush:mw-list':
      return dt('Shows the modules weight list.');
  }
}

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_modules_weight_mw_show_system_modules_validate() {
  $args = func_get_args();

  if (count($args) > 1) {
    return drush_set_error('MODULES_WEIGHT_INVALID_ARGUMENT', dt('This command use only one argument.'));
  }

  // Available options.
  $available_options = [
    'on',
    'off',
  ];

  // Check for correct argument.
  if (isset($args[0]) && !in_array($args[0], $available_options)) {
    return drush_set_error('MODULES_WEIGHT_INVALID_ARGUMENT', dt("You must specify as argument 'on' or 'off'"));
  }
}

/**
 * Callback for the mw-show-system-modules command.
 */
function drush_modules_weight_mw_show_system_modules() {
  $args = func_get_args();
  // Getting an editable config because we will get and set a value.
  $config = \Drupal::service('config.factory')->getEditable('modules_weight.settings');
  // Getting the values from the config.
  $show_system_modules = $config->get('show_system_modules');
  $status = $show_system_modules ? sprintf(MODULES_WEIGHT_GREEN_OUTPUT, dt('Activated')) : sprintf(MODULES_WEIGHT_RED_OUTPUT, dt('Disabled'));

  if (isset($args[0])) {
    list($value, $status) = $args[0] == 'on' ? [1, dt('activated')] : [0, dt('disabled')];

    // Checking if the status is already configured.
    $already_configured = FALSE;

    if ($show_system_modules && $value) {
      $already_configured = TRUE;
    }

    if (!$show_system_modules && !$value) {
      $already_configured = TRUE;
    }

    if ($already_configured) {
      $message = dt('The core modules reorder option is already @status.', ['@status' => $status]);
      drush_log($message, 'warning');
      return;
    }

    // Saving the values in the config.
    $config->set('show_system_modules', $value);
    $config->save();

    $message = dt('You have @status the core modules reorder option.', ['@status' => $status]);
    drush_log($message, 'success');
  }
  else {
    $message = dt('The core modules reorder option is: @status', ['@status' => $status]);
    drush_print($message);
  }
}

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_modules_weight_mw_reorder_validate() {
  $args = func_get_args();
  // Check for 1 or 2 arguments.
  if (count($args) < 1 || count($args) > 2) {
    return drush_set_error('MODULES_WEIGHT_INVALID_ARGUMENT', dt('This command use one or two arguments.'));
  }
  // Check for a valid or installed module machine name.
  if (!\Drupal::service('module_handler')->moduleExists($args[0])) {
    return drush_set_error('MODULES_WEIGHT_INVALID_MODULE_NAME', dt('@module_name module machine name is invalid or is not installed.', ['@module_name' => $args[0]]));
  }
  // Check for integer number.
  if (isset($args[1]) && !ctype_digit($args[1])) {
    return drush_set_error('MODULES_WEIGHT_INVALID_ARGUMENT', dt('You must enter digits for the modules-weight.'));
  }
  // Getting the --force option.
  $force = drush_get_option('force');
  // Getting the module info.
  $module = \Drupal::service('module_handler')->getModule($args[0]);
  $module = \Drupal::service('info_parser')->parse($module->getPathname());
  // Getting the config to know of we should show or not the core modules.
  $show_system_modules = \Drupal::service('config.factory')->get('modules_weight.settings')->get('show_system_modules');
  // Checking if we can reorder the Core modules.
  if (!$force && $module['package'] == 'Core' && !$show_system_modules) {
    if (!drush_confirm(dt("You're trying to reorder a Core module but Modules Weight is not configured to allow it. Do you want to continue?"))) {
      return drush_user_abort();
    }
  }
}

/**
 * Callback for the mw-reorder command.
 */
function drush_modules_weight_mw_reorder() {
  $args = func_get_args();
  if (count($args) == 2) {
    // Getting the --minus option.
    $minus = drush_get_option('minus');
    // Applying the minus option.
    $weight = $minus ? -1 * $args[1] : $args[1];
    // Setting the new weight.
    module_set_weight($args[0], $weight);
    // Printing the message.
    $message = dt('The module weight for @module_name was updated to @weight.', ['@module_name' => $args[0], '@weight' => $weight]);
    drush_log($message, 'success');
  }
  else {
    // Searching for the module weigth.
    // Getting the list of installed modules from the config.
    $installed_modules = \Drupal::service('config.factory')->get('core.extension')->get('module') ?: [];
    // Getting the module weight.
    $weight = $installed_modules[$args[0]];
    // Getting the module name.
    $module_name = \Drupal::service('module_handler')->getName($args[0]);
    // Creating the array with the sustitution values.
    $values = [
      '@module_name' => $module_name,
      '@machine_name' => $args[0],
      '@weight' => $weight,
    ];
    $message = dt('The weight of the @module_name [@machine_name] module is: @weight', $values);
    drush_print($message);
  }
}

/**
 * Callback for the mw-list command.
 */
function drush_modules_weight_mw_list() {
  $result = [];

  // Getting the --force option.
  $force = (bool) drush_get_option('force');

  // If we don't force we need to check the configuration variable to know if we
  // should show or not the core modules.
  $show_core_modules = $force ?: \Drupal::service('config.factory')->get('modules_weight.settings')->get('show_system_modules');

  // Getting the module list.
  $modules = \Drupal::service('modules_weight')->getModulesList($show_core_modules);
  // Iterate over each of the modules.
  foreach ($modules as $filename => $module) {
    // The rows info.
    $row = [];
    // Module name.
    $row['name'] = $module['name'];
    // Module machine name.
    $row['machine_name'] = $filename;
    // Module weight.
    $row['weight'] = $module['weight'];
    // Module package.
    $row['package'] = $module['package'];

    $result[] = $row;
  }

  return $result;
}
