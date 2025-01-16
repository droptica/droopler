<?php

declare(strict_types=1);

use Drupal\Core\Recipe\Recipe;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\user\Entity\User;
use Symfony\Component\Process\Process;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Element\Password;

/**
 * Implements hook_install_tasks().
 */
function droopler_install_tasks(&$install_state): array {
    return [
        'droopler_install_finished' => [
            'display_name' => t('Finishing up'),
            'type' => 'normal',
            'function' => 'droopler_install_finished',
        ],
    ];
}

/**
 * Implements hook_install_tasks_alter().
 */
function droopler_install_tasks_alter(array &$tasks, array $install_state): void {
    // Wrap the install_profile_modules() function to add recipe application
    $tasks['install_profile_modules']['function'] = 'droopler_apply_recipes';

    // Store install state globally
    $GLOBALS['install_state'] = &$install_state;

    // Override the install_finished task
    $tasks['install_finished']['function'] = 'droopler_install_finished';
}

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form.
 */
function droopler_form_install_configure_form_alter(&$form, FormStateInterface $form_state): void {
    global $install_state;

    $form['#title'] = t('Create your account');

    $form['help'] = [
        '#prefix' => '<p class="installer-subhead">',
        '#markup' => t('Creating an account allows you to log in to your site.'),
        '#suffix' => '</p>',
        '#weight' => -40,
    ];

    $form['site_information']['#type'] = 'container';
    unset($form['site_information']['site_mail']);

    $form['admin_account']['#type'] = 'container';

    // Add username field
    $form['admin_account']['account']['name'] = [
        '#prefix' => '<div class="form-group">',
        '#suffix' => '</div>',
        '#type' => 'textfield',
        '#title' => t('Username'),
        '#required' => TRUE,
        '#default_value' => 'admin',
        '#weight' => 5,
    ];

    $form['admin_account']['account']['mail'] = [
        '#prefix' => '<div class="form-group">',
        '#suffix' => '</div>',
        '#type' => 'email',
        '#title' => t('Email'),
        '#required' => TRUE,
        '#default_value' => $install_state['forms']['install_configure_form']['account']['mail'] ?? '',
        '#weight' => 10,
    ];

    $form['admin_account']['account']['pass'] = [
        '#prefix' => '<div class="form-group">',
        '#suffix' => '</div>',
        '#type' => 'password',
        '#title' => t('Password'),
        '#required' => TRUE,
        '#default_value' => $install_state['forms']['install_configure_form']['account']['pass']['pass1'] ?? '',
        '#weight' => 20,
        '#value_callback' => '_droopler_password_value',
    ];

    // Hide timezone selection
    $form['regional_settings']['#attributes']['class'][] = 'visually-hidden';
    $form['regional_settings']['date_default_timezone']['#attributes']['tabindex'] = -1;

    // Hide update notifications
    $form['update_notifications']['#access'] = FALSE;

    // Add our submit handlers
    $form['#submit'][] = 'droopler_update_site_mail';
    $form['#submit'][] = 'droopler_install_configure_form_submit';
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

    // Store the values in install_state for later use
    $install_state['forms']['install_configure_form'] = [
        'account' => [
            'name' => $form_state->getValue(['admin_account', 'account', 'name']),
            'mail' => $form_state->getValue(['admin_account', 'account', 'mail']),
            'pass' => $form_state->getValue(['admin_account', 'account', 'pass']),
        ],
    ];
}

/**
 * Password value callback to handle both web and drush installation.
 */
function _droopler_password_value(&$element, $input, FormStateInterface $form_state): mixed {
    // Handle drush site-install and programmatic form submissions
    if (is_array($input) && $form_state->isProgrammed()) {
        $input = $input['pass1'];
    }
    return Password::valueCallback($element, $input, $form_state);
}

/**
 * Finish callback for the installer.
 */
function droopler_install_finished(&$install_state) {
    \Drupal::messenger()->deleteAll();

    // Switch to droopler_theme
    \Drupal::service('theme_installer')->install(['droopler_theme']);
    \Drupal::configFactory()
        ->getEditable('system.theme')
        ->set('default', 'droopler_theme')
        ->save();

    // Load user 1 and log them in
    $user = User::load(1);
    if ($user) {
        user_login_finalize($user);
    }

    // Clear all caches after everything is set up
    $drush_path = DRUPAL_ROOT . '/../vendor/bin/drush';
    shell_exec($drush_path . ' cr 2>&1');
}

/**
 * Runs a batch job that applies the Droopler recipe.
 */
function droopler_apply_recipes(array &$install_state): array {
    $batch = install_profile_modules($install_state);
    $batch['title'] = t('Setting up Droopler');

    // Get the recipe path from project root
    $recipe_path = dirname(DRUPAL_ROOT) . '/recipes/droopler';

    if (is_dir($recipe_path)) {
        try {
            $recipe = Recipe::createFromDirectory($recipe_path);
            $recipe_operations = RecipeRunner::toBatchOperations($recipe);

            // Wrap each operation in our error handler
            $wrapped_operations = [];
            foreach ($recipe_operations as $operation) {
                $wrapped_operations[] = [
                    'droopler_recipe_operation',
                    [$operation],
                ];
            }

            // Only do each recipe's batch operations once
            foreach ($wrapped_operations as $operation) {
                if (in_array($operation, $batch['operations'], TRUE)) {
                    continue;
                }
                $batch['operations'][] = $operation;
            }

            // Add content indexing operation
            $batch['operations'][] = [
                'droopler_index_content',
                [],
            ];
        }
        catch (\Exception $e) {
            \Drupal::logger('droopler')->error('Recipe error: @error', [
                '@error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    return $batch;
}

/**
 * Wrapper for recipe operations that handles non-critical errors.
 */
function droopler_recipe_operation(array $operation, array &$context): void {
    try {
        // Extract operation details
        $callable = $operation[0];
        $args = $operation[1] ?? [];

        // Call the original operation
        call_user_func_array($callable, $args);
    }
    catch (\Exception $e) {
        // Check if this is a config update error
        if (strpos($e->getMessage(), 'does not exist so can not be updated') !== FALSE) {
            // Log it but don't stop the installation
            \Drupal::logger('droopler')->notice('Non-critical configuration error during installation: @error', [
                '@error' => $e->getMessage(),
            ]);
            return;
        }

        // For other errors, rethrow
        throw $e;
    }
}

/**
 * Indexes the content after recipe application.
 */
function droopler_index_content($context): void {
    try {
        // Execute drush sapi-i command
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
