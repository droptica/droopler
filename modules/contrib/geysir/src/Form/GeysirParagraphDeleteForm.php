<?php

namespace Drupal\geysir\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Functionality to delete a paragraph.
 */
class GeysirParagraphDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $route_match = $this->getRouteMatch();
    $parent_entity_type = $route_match->getParameter('parent_entity_type');
    $parent_entity_revision = $route_match->getParameter('parent_entity_revision');
    $field_name = $route_match->getParameter('field');
    $delta = $route_match->getParameter('delta');

    $parent_entity_revision = $this->entityTypeManager->getStorage($parent_entity_type)->loadRevision($parent_entity_revision);

    $field = $parent_entity_revision->get($field_name);
    $field_definition = $field->getFieldDefinition();
    $field_label = $field_definition->getLabel();

    return $this->t('Are you sure you want to delete #@delta of @field of %label?', [
      '@delta' => $delta,
      '@field' => $field_label,
      '%label' => $parent_entity_revision->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route_match = $this->getRouteMatch();
    $parent_entity_type = $route_match->getParameter('parent_entity_type');
    $parent_entity_revision = $route_match->getParameter('parent_entity_revision');
    $field_name = $route_match->getParameter('field');
    $delta = $route_match->getParameter('delta');

    $parent_entity_revision = $this->entityTypeManager->getStorage($parent_entity_type)->loadRevision($parent_entity_revision);

    $parent_entity_revision->get($field_name)->removeItem($delta);
    $parent_entity_revision->save();

    $form_state->setTemporary(['parent_entity_revision' => $parent_entity_revision->getRevisionId()]);

    $form_state->setRedirectUrl($this->getRedirectUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getRedirectUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    $route_match = $this->getRouteMatch();
    $parent_entity_type = $route_match->getParameter('parent_entity_type');
    $parent_entity_revision = $route_match->getParameter('parent_entity_revision');

    $parent_entity_revision = $this->entityTypeManager->getStorage($parent_entity_type)->loadRevision($parent_entity_revision);
    return $parent_entity_revision->toUrl();
  }

}
