<?php

/**
 * @file
 * Droopler install tasks.
 */

declare(strict_types=1);

use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\user\Entity\User;
use Drupal\droopler\Form\RecipesForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Process\Process;

/**
 * Implements hook_install_tasks().
 */
function droopler_install_tasks(&$install_state): array {
  // Check for demo content parameter from drush.
  if (!empty($install_state['forms']['install_configure_form']['enable_demo_content'])) {
    if (!isset($install_state['parameters'])) {
      $install_state['parameters'] = [];
    }
    $install_state['parameters']['recipes'] = ['default_content'];
  }

  // Force content recipe to run if selected.
  $has_content = !empty($install_state['parameters']['recipes']) &&
    in_array('default_content', $install_state['parameters']['recipes']);

  $tasks = [
    'droopler_apply_core_recipe' => [
      'display_name' => t('Install Droopler'),
      'type' => 'batch',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
      'function' => 'droopler_apply_core_recipe',
    ],
  ];

  // Only show recipes form if demo content not enabled via drush.
  if (empty($install_state['forms']['install_configure_form']['enable_demo_content'])) {
    $tasks['droopler_choose_recipes'] = [
      'display_name' => t('Choose content'),
      'type' => 'form',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
      // @phpstan-ignore-next-line
      'function' => RecipesForm::class,
    ];
  }

  // Add content recipe task if selected.
  if ($has_content) {
    $tasks['droopler_apply_content_recipe'] = [
      'display_name' => t('Install demo content'),
      'type' => 'batch',
      'run' => INSTALL_TASK_RUN_IF_NOT_COMPLETED,
      'function' => 'droopler_apply_content_recipe',
    ];
  }

  $tasks['droopler_install_finished'] = [
    'display_name' => t('Finishing up'),
    'type' => 'batch',
    'function' => 'droopler_install_finished',
  ];

  return $tasks;
}

/**
 * Implements hook_install_tasks_alter().
 */
function droopler_install_tasks_alter(array &$tasks, array $install_state): void {
  // Insert our tasks after the configure form.
  $configure_form_position = array_search('install_configure_form', array_keys($tasks), TRUE);
  if ($configure_form_position !== FALSE) {
    $tasks_before = array_slice($tasks, 0, $configure_form_position + 1, TRUE);
    $tasks_after = array_slice($tasks, $configure_form_position + 1, NULL, TRUE);

    // Get our tasks.
    $our_tasks = droopler_install_tasks($install_state);

    $tasks = $tasks_before + $our_tasks + $tasks_after;
  }

  // Set the language code to English.
  $GLOBALS['install_state']['parameters'] += ['langcode' => 'en'];
  $tasks['install_select_language']['run'] = INSTALL_TASK_SKIP;
}

/**
 * Wrapper for recipe operations that handles non-critical errors.
 */
function droopler_recipe_operation(array $operation, array &$context): void {
  try {
    [$class, $method] = $operation[0];
    $args = $operation[1];
    $class::$method(...$args);
  }
  catch (\Exception $e) {
    error_log($e->getMessage());
  }
}

/**
 * Applies the core Droopler recipe.
 */
function droopler_apply_core_recipe(array &$install_state): array {
  $recipe_dir = DRUPAL_ROOT . '/../recipes/droopler';
  if (!is_dir($recipe_dir)) {
    error_log("Droopler recipe directory not found at: $recipe_dir");
    return [];
  }

  try {
    $recipe = Recipe::createFromDirectory($recipe_dir);
    $recipe_operations = RecipeRunner::toBatchOperations($recipe);

    // Wrap each operation with our error handler.
    $operations = [];
    foreach ($recipe_operations as $operation) {
      $operations[] = [
        'droopler_recipe_operation',
        [$operation],
      ];
    }

    return [
      'operations' => $operations,
      'title' => t('Installing Droopler'),
      'progress_message' => t('Installing Droopler... @current out of @total steps.'),
      'finished' => 'droopler_recipe_finished',
    ];
  }
  catch (\Exception $e) {
    error_log("Error applying Droopler recipe: " . $e->getMessage());
    return [];
  }
}

/**
 * Applies the content recipe if selected.
 */
function droopler_apply_content_recipe(array &$install_state): array {
  // Check if recipe is selected either via drush or form.
  if (empty($install_state['parameters']['recipes']) &&
    empty($install_state['forms']['install_configure_form']['enable_demo_content'])) {
    return [];
  }

  $recipe_dir = DRUPAL_ROOT . '/../recipes/default_content';
  if (!is_dir($recipe_dir)) {
    return [];
  }

  try {
    $recipe = Recipe::createFromDirectory($recipe_dir);
    $operations = RecipeRunner::toBatchOperations($recipe);

    // Return batch array.
    return [
      'operations' => $operations,
      'title' => t('Installing demo content'),
      'init_message' => t('Starting demo content installation...'),
      'progress_message' => t('Installing demo content... @current out of @total steps.'),
      'error_message' => t('An error occurred. The installation will continue.'),
      'finished' => 'droopler_content_recipe_finished',
    ];
  }
  catch (\Exception $e) {
    error_log($e->getMessage());
    return [];
  }
}

/**
 * Finished callback for content recipe.
 */
function droopler_content_recipe_finished($success, $results, $operations) {
  if ($success) {
    try {
      // Clear all caches to ensure content is visible.
      drupal_flush_all_caches();

      // Update entity type definitions.
      $entityTypeManager = \Drupal::entityTypeManager();
      $entityTypeManager->clearCachedDefinitions();

      // Update entity schema if needed.
      $entityUpdateManager = \Drupal::entityDefinitionUpdateManager();
      $pendingUpdates = $entityUpdateManager->getChangeList();
      if (!empty($pendingUpdates)) {
        foreach ($entityUpdateManager->getChangeSummary() as $entity_type_id => $changes) {
          foreach ($changes as $change) {
            error_log("Applying entity schema update for $entity_type_id: $change");
          }
        }
        $entityUpdateManager->getChangeList();
      }
    }
    catch (\Exception $e) {
      error_log("Non-critical error during content recipe cleanup: " . $e->getMessage());
    }
  }
  else {
    error_log("Content recipe application failed");
    if (!empty($results['errors'])) {
      foreach ($results['errors'] as $error) {
        error_log($error);
      }
    }
  }
}

/**
 * Batch operation finished callback.
 */
function droopler_recipe_finished($success, $results, $operations) {
  if ($success) {
    error_log("Recipe applied successfully");
  }
  else {
    error_log("Recipe application failed");
    if (!empty($results['errors'])) {
      foreach ($results['errors'] as $error) {
        error_log($error);
      }
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form.
 */
function droopler_form_install_configure_form_alter(&$form, FormStateInterface $form_state): void {
  // Hide the update notification setting (we enable update module later).
  $form['update_notifications']['#access'] = FALSE;
}

/**
 * Finish callback for the installer.
 */
function droopler_install_finished(&$install_state) {
  \Drupal::messenger()->deleteAll();

  try {
    // Switch to droopler_theme.
    \Drupal::service('theme_installer')->install(['droopler_theme']);
    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('default', 'droopler_theme')
      ->save();
  }
  catch (\Exception $e) {
    error_log($e->getMessage());
  }

  // Load user 1 and log them in.
  $user = User::load(1);
  if ($user) {
    user_login_finalize($user);
  }
}

/**
 * Custom submit handler to update the site email.
 */
function droopler_update_site_mail(array &$form, FormStateInterface $form_state): void {
  \Drupal::configFactory()
    ->getEditable('system.site')
    ->set('mail', $form_state->getValue(['admin_account', 'account', 'mail']))
    ->save();
}

/**
 * Submit handler to store form values.
 */
function droopler_install_configure_form_submit(array &$form, FormStateInterface $form_state): void {
  global $install_state;

  // Store the values in install_state for later use.
  $install_state['forms']['install_configure_form'] = [
    'account' => [
      'name' => $form_state->getValue(['admin_account', 'account', 'name']),
      'mail' => $form_state->getValue(['admin_account', 'account', 'mail']),
      'pass' => $form_state->getValue(['admin_account', 'account', 'pass']),
    ],
  ];
}

/**
 * Indexes the content after recipe application.
 */
function droopler_index_content($context): void {
  try {
    // Execute drush sapi-i command.
    $process = new Process(['drush', 'sapi-i', '--yes']);
    $process->setWorkingDirectory(DRUPAL_ROOT);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new \Exception($process->getErrorOutput());
    }

    $context['message'] = t('Content indexed successfully');
  }
  catch (\Exception $e) {
    \Drupal::messenger()->addError(t('Error indexing content: @error', ['@error' => $e->getMessage()]));
  }
}
