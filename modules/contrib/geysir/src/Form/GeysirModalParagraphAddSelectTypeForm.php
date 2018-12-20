<?php

namespace Drupal\geysir\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Functionality to select a paragraph type.
 */
class GeysirModalParagraphAddSelectTypeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'geysir.modal.add_select_type_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="geysir-modal-form">';
    $form['#suffix'] = '</div>';

    $routeParams = $form_state->getBuildInfo()['args'][0];
    $bundles = $form_state->getBuildInfo()['args'][1];

    $parent_entity_type = $routeParams['parent_entity_type'];
    $parent_entity_bundle = $routeParams['parent_entity_bundle'];
    $form_mode = 'default';
    $field = $routeParams['field'];

    $parent_field_settings = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($parent_entity_type . '.' . $parent_entity_bundle . '.' . $form_mode)
      ->getComponent($field);

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $bundles = $this->getAllowedBundles($bundles);
    $paragraphs_type_storage = \Drupal::entityTypeManager()->getStorage('paragraphs_type');

    $default_icon = drupal_get_path('module', 'geysir') . '/images/geysir-puzzle.svg';

    foreach ($bundles as $bundle => $label) {
      $icon_url = $default_icon;
      if ($paragraphs_type_storage->load($bundle)->getIconUrl()) {
        $icon_url = $paragraphs_type_storage->load($bundle)->getIconUrl();
      }
      $routeParams['bundle'] = $bundle;
      $form['description'][$bundle] = [
        '#type' => 'image_button',
        '#prefix' => '<div class="geysir-add-type">',
        '#suffix' => '<span>' . $label . '</span></div>',
        '#src' => $icon_url,
        '#value' => $label,
        '#ajax' => [
          'url' => Url::fromRoute(isset($routeParams['paragraph'])? 'geysir.modal.add_form': 'geysir.modal.add_form_first', $routeParams),
          'wrapper' => 'geysir-modal-form',
        ],
      ];
    }

    return $form;
  }

  /**
   * Returns a list of allowed Paragraph bundles to add.
   *
   * @param array $allowed_bundles
   *   An array with Paragraph bundles which are allowed to add.
   *
   * @return array
   *   Array with allowed Paragraph bundles.
   */
  protected function getAllowedBundles($allowed_bundles) {
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo('paragraph');

    if (is_array($allowed_bundles) && count($allowed_bundles)) {
      // Preserve order of allowed bundles setting.
      $allowed_bundles_order = array_flip($allowed_bundles);
      // Only keep allowed bundles.
      $bundles = array_intersect_key(
        array_replace($allowed_bundles_order, $bundles),
        $allowed_bundles_order
      );
    }

    // Enrich bundles with their label.
    foreach ($bundles as $bundle => $props) {
      $label = empty($props['label']) ? ucfirst($bundle) : $props['label'];
      $bundles[$bundle] = $label;
    }

    return $bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return [];
  }

}
