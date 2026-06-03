<?php

declare(strict_types=1);

namespace Drupal\d_content\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Markup;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\NodeInterface;

/**
 * Hook implementations for the d_content module.
 */
class Hooks {

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    if ($form_id !== 'node_content_page_edit_form' && $form_id !== 'node_content_page_form') {
      return;
    }
    if (!isset($form['field_header_cta'])) {
      return;
    }

    $form['field_header_cta']['#states'] = [
      'visible' => [
        ':input[name="field_header_layout"]' => ['value' => 'header_with_cta'],
      ],
    ];
  }

  /**
   * Implements hook_field_widget_single_element_form_alter().
   */
  #[Hook('field_widget_single_element_form_alter')]
  public function fieldWidgetSingleElementFormAlter(array &$element, FormStateInterface $form_state, array $context): void {
    $field_definition = $context['items']->getFieldDefinition();
    if (!$field_definition instanceof FieldConfig) {
      return;
    }
    if ($field_definition->getName() !== 'field_header_cta') {
      return;
    }
    if (!empty($element['url']['#default_value'])) {
      return;
    }
    if (!empty($element['options']['attributes']['class']['#default_value'])) {
      return;
    }

    $element['options']['attributes']['class']['#default_value'] = 'btn btn-primary';
  }

  /**
   * Implements hook_preprocess_page().
   */
  #[Hook('preprocess_page')]
  public function preprocessPage(array &$variables): void {
    $node = $variables['node'] ?? NULL;
    if (!$node instanceof NodeInterface) {
      return;
    }
    if ($node->getType() !== 'content_page') {
      return;
    }

    $variables['cta_button'] = $node->field_header_cta->view('d_header');

    if (empty($variables['cta_button'][0]['#title'])) {
      $variables['cta_button'] = FALSE;
      return;
    }

    $new_title = '<span class="d-md-none icon-hand-pointer-o"></span>';
    $new_title .= '<span class="d-none d-md-block">' . $variables['cta_button'][0]['#title'] . '</span>';
    $variables['cta_button'][0]['#title'] = Markup::create($new_title);
  }

}
