<?php

namespace Drupal\geysir\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Functionality to edit a paragraph.
 */
class GeysirParagraphForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $route_match = $this->getRouteMatch();
    $parent_entity_type = $route_match->getParameter('parent_entity_type');
    $parent_entity_revision = $route_match->getParameter('parent_entity_revision');
    $field_name = $route_match->getParameter('field');
    $delta = $route_match->getParameter('delta');

    $parent_entity_revision = $this->entityTypeManager->getStorage($parent_entity_type)->loadRevision($parent_entity_revision);

    $field = $parent_entity_revision->get($field_name);
    $field_definition = $field->getFieldDefinition();
    $field_label = $field_definition->getLabel();

    $form['#title'] = $this->t('Edit @delta of @field of %label', [
      '@delta' => $delta,
      '@field' => $field_label,
      '%label' => $parent_entity_revision->label(),
    ]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $route_match = $this->getRouteMatch();
    $parent_entity_type = $route_match->getParameter('parent_entity_type');
    $parent_entity_revision = $route_match->getParameter('parent_entity_revision');
    $field = $route_match->getParameter('field');
    $delta = $route_match->getParameter('delta');

    $this->entity->setNewRevision(TRUE);
    $this->entity->save();

    $parent_entity_revision = $this->entityTypeManager->getStorage($parent_entity_type)->loadRevision($parent_entity_revision);

    $parent_entity_revision->get($field)->get($delta)->setValue([
      'target_id' => $this->entity->id(),
      'target_revision_id' => $this->entity->getRevisionId(),
    ]);

    $save_status = $parent_entity_revision->save();

    $form_state->setTemporary(['parent_entity_revision' => $parent_entity_revision->getRevisionId()]);

    return $save_status;
  }

}
