<?php

namespace Drupal\geysir\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Functionality to delete a paragraph through a modal.
 */
class GeysirModalParagraphDeleteForm extends GeysirParagraphDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // We don't need this title on the Modal because we stay on the same page
    // using a Modal, thus we don't loose context.
    unset($form['#title']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['#prefix'] = '<div id="geysir-modal-form">';
    $form['#suffix'] = '</div>';

    // @TODO: fix problem with form is outdated.
    $form['#token'] = FALSE;

    // Define alternative submit callbacks using AJAX by copying the default
    // submit callbacks to the AJAX property.
    $submit = &$form['actions']['submit'];
    $submit['#ajax'] = [
      'callback' => '::ajaxDelete',
      'event'    => 'click',
      'progress' => [
        'type'    => 'throbber',
        'message' => NULL,
      ],
    ];

    $form['actions']['cancel'] = [
      '#type'   => 'button',
      '#value'  => t('Cancel'),
      '#ajax' => [
        'callback' => '::ajaxCancel',
        'event'    => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxCancel(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxDelete(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Get all necessary data to be able to correctly update the correct
    // field on the parent node.
    $route_match = $this->getRouteMatch();
    $parent_entity_type = $route_match->getParameter('parent_entity_type');
    $temporary_data = $form_state->getTemporary();
    $parent_entity_revision = isset($temporary_data['parent_entity_revision']) ?
      $temporary_data['parent_entity_revision'] :
      $route_match->getParameter('parent_entity_revision');
    $field_name = $route_match->getParameter('field');
    $field_wrapper_id = $route_match->getParameter('field_wrapper_id');
    $parent_entity_revision = \Drupal::entityManager()
      ->getStorage($parent_entity_type)
      ->loadRevision($parent_entity_revision);

    // Refresh the paragraphs field.
    $response->addCommand(
      new HtmlCommand(
        '[data-geysir-field-paragraph-field-wrapper=' . $field_wrapper_id . ']',
        $parent_entity_revision->get($field_name)->view('default')
      )
    );

    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

}
