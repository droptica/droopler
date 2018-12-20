<?php

namespace Drupal\checklistapi\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\user\Entity\User;

/**
 * Provides a checklist form.
 */
class ChecklistapiChecklistForm implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'checklistapi_checklist_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $checklist_id = NULL) {
    $form['#checklist'] = $checklist = checklistapi_checklist_load($checklist_id);
    $user_has_edit_access = $checklist->userHasAccess('edit');

    // Progress bar.
    $form['progress_bar'] = [
      '#theme' => 'checklistapi_progress_bar',
      '#message' => ($checklist->hasSavedProgress()) ? t('Last updated @date by @user', [
        '@date' => $checklist->getLastUpdatedDate(),
        '@user' => $checklist->getLastUpdatedUser(),
      ]) : '',
      '#number_complete' => $checklist->getNumberCompleted(),
      '#number_of_items' => $checklist->getNumberOfItems(),
      '#percent_complete' => (int) round($checklist->getPercentComplete()),
      '#attached' => [
        'library' => [
          'classy/progress',
        ],
      ],
    ];

    // Compact mode.
    if (checklistapi_compact_mode_is_on()) {
      $form['#attributes']['class'] = ['compact-mode'];
    }
    $form['compact_mode_link'] = [
      '#markup' => '<div class="compact-link"></div>',
    ];

    // General properties.
    $form['checklistapi'] = [
      '#attached' => [
        'library' => ['checklistapi/checklistapi'],
      ],
      '#tree' => TRUE,
      '#type' => 'vertical_tabs',
    ];

    // Loop through groups.
    $num_autochecked_items = 0;
    $groups = $checklist->items;
    foreach (Element::children($groups) as $group_key) {
      $group = &$groups[$group_key];
      $form[$group_key] = [
        '#title' => Xss::filter($group['#title']),
        '#type' => 'details',
        '#group' => 'checklistapi',
      ];
      if (!empty($group['#description'])) {
        $form[$group_key]['#description'] = Xss::filterAdmin($group['#description']);
      }

      // Loop through items.
      foreach (Element::children($group) as $item_key) {
        $item = &$group[$item_key];
        $saved_item = !empty($checklist->savedProgress['#items'][$item_key]) ? $checklist->savedProgress['#items'][$item_key] : 0;
        // Build title.
        $title = Xss::filter($item['#title']);
        if ($saved_item) {
          // Append completion details.
          $title .= '<span class="completion-details"> - ' . t('Completed @time by @user', [
            '@time' => format_date($saved_item['#completed'], 'short'),
            '@user' => User::load($saved_item['#uid'])->getUsername(),
          ]) . '</span>';
        }
        // Set default value.
        $default_value = FALSE;
        if ($saved_item) {
          $default_value = TRUE;
        }
        elseif (!empty($item['#default_value'])) {
          if ($default_value = $item['#default_value']) {
            $num_autochecked_items++;
          }
        }
        // Get description.
        $description = (isset($item['#description'])) ? '<p>' . Xss::filterAdmin($item['#description']) . '</p>' : '';
        // Append links.
        $links = [];
        foreach (Element::children($item) as $link_key) {
          $link = &$item[$link_key];
          $links[] = \Drupal::l($link['#text'], $link['#url']);
        }
        if (count($links)) {
          $description .= '<div class="links">' . implode(' | ', $links) . '</div>';
        }
        // Compile the list item.
        $form[$group_key][$item_key] = [
          '#attributes' => ['class' => ['checklistapi-item']],
          '#default_value' => $default_value,
          '#description' => Xss::filterAdmin($description),
          '#disabled' => !($user_has_edit_access),
          '#title' => Xss::filterAdmin($title),
          '#type' => 'checkbox',
          '#group' => $group_key,
          '#parents' => ['checklistapi', $group_key, $item_key],
        ];
      }
    }

    // Actions.
    $form['actions'] = [
      '#access' => $user_has_edit_access,
      '#type' => 'actions',
      '#weight' => 100,
      'save' => [
        '#button_type' => 'primary',
        '#type' => 'submit',
        '#value' => t('Save'),
      ],
      'clear' => [
        '#access' => $checklist->hasSavedProgress(),
        '#button_type' => 'danger',
        '#attributes' => ['class' => ['clear-saved-progress']],
        '#submit' => [[$this, 'clear']],
        '#type' => 'submit',
        '#value' => t('Clear saved progress'),
      ],
    ];

    // Alert the user of autochecked items. Only set the message on GET requests
    // to prevent it from reappearing after saving the form. (Testing the
    // request method may not be the "correct" way to accomplish this.)
    if ($num_autochecked_items && $_SERVER['REQUEST_METHOD'] == 'GET') {
      $args = [
        '%checklist' => $checklist->title,
        '@num' => $num_autochecked_items,
      ];
      $message = \Drupal::translation()->formatPlural(
        $num_autochecked_items,
        t('%checklist found 1 unchecked item that was already completed and checked it for you. Save the form to record the change.', $args),
        t('%checklist found @num unchecked items that were already completed and checked them for you. Save the form to record the changes.', $args)
      );
      drupal_set_message($message, 'status');
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\checklistapi\ChecklistapiChecklist $checklist */
    $checklist = $form['#checklist'];

    // Save progress.
    $values = $form_state->getValue('checklistapi');
    $checklist->saveProgress($values);

    // Preserve the active tab after submission.
    $form_state->setRedirect($checklist->getRouteName(), [], [
      'fragment' => $values['checklistapi__active_tab'],
    ]);
  }

  /**
   * Form submission handler for the 'clear' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function clear(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect($form['#checklist']->getRouteName() . '.clear');
  }

}
