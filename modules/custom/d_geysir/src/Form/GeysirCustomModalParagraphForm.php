<?php

namespace Drupal\d_geysir\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\geysir\Form\GeysirModalParagraphForm;
use Drupal\d_geysir\Ajax\GeysirReattachBehaviors;

/**
 * Override GeysirModalParagraphForm class.
 */
class GeysirCustomModalParagraphForm extends GeysirModalParagraphForm {

  /**
   * {@inheritdoc}
   */
  public function ajaxSave(array $form, FormStateInterface $form_state) {
    $response = parent::ajaxSave($form, $form_state);

    if (!$form_state->getErrors()) {
      // Reattach behaviors after refreshing.
      $response->addCommand(new GeysirReattachBehaviors());
    }

    return $response;
  }

}
