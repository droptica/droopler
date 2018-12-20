<?php

namespace Drupal\geysir\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Functionality to edit a paragraph through a modal.
 */
class GeysirModalParagraphAddForm extends GeysirModalParagraphForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $route_match = $this->getRouteMatch();
    $parent_entity_type = $route_match->getParameter('parent_entity_type');
    $temporary_data = $form_state->getTemporary();
    $parent_entity_revision = isset($temporary_data['parent_entity_revision']) ?
      $temporary_data['parent_entity_revision'] :
      $route_match->getParameter('parent_entity_revision');

    $this->entity->setNewRevision(TRUE);
    $this->entity->save();

    $parent_entity_revision = $this->entityTypeManager
      ->getStorage($parent_entity_type)
      ->loadRevision($parent_entity_revision);

    // If we add the first paragraph, no need for reordering.
    if (!empty($route_match->getParameter('paragraph'))) {
      $this->reorderItemList($parent_entity_revision);
    }
    else {
      $this->insertFirstItem($parent_entity_revision);
    }

    $save_status = $parent_entity_revision->save();

    $form_state->setTemporary(['parent_entity_revision' => $parent_entity_revision->getRevisionId()]);

    return $save_status;
  }

  /**
   * Reorder the ItemList in the parent entity.
   */
  protected function reorderItemList($parent_entity) {
    $route_match = $this->getRouteMatch();
    $field = $route_match->getParameter('field');
    $delta = $route_match->getParameter('delta');

    $list_items = $parent_entity->get($field)->getIterator();

    for ($index = $parent_entity->get($field)->count() - 1; $index >= 0; $index--) {
      $parent_entity->get($field)->removeItem($index);
    }

    foreach ($list_items as $item_delta => $item) {
      if ($item_delta == $delta) {
        $this->insertItemIntoList($parent_entity, $item);
      }
      else {
        $parent_entity->get($field)->appendItem($item->getValue());
      }
    }
  }

  /**
   * Insert the value into the ItemList either before or after.
   */
  protected function insertItemIntoList($parent_entity, $item) {
    $route_match = $this->getRouteMatch();
    $field = $route_match->getParameter('field');
    $position = $route_match->getParameter('position');

    $value = [
      'target_id' => $this->entity->id(),
      'target_revision_id' => $this->entity->getRevisionId(),
    ];

    if ($position == 'before') {
      $parent_entity->get($field)->appendItem($value);
      $parent_entity->get($field)->appendItem($item->getValue());
    }
    else {
      $parent_entity->get($field)->appendItem($item->getValue());
      $parent_entity->get($field)->appendItem($value);
    }
  }

  /**
   * Insert the first paragraph for the node.
   */
  protected function insertFirstItem($parent_entity) {
    $route_match = $this->getRouteMatch();
    $field = $route_match->getParameter('field');
    $value = [
      'target_id' => $this->entity->id(),
      'target_revision_id' => $this->entity->getRevisionId(),
    ];
    $parent_entity->get($field)->appendItem($value);
  }

}
