<?php

namespace Drupal\geysir\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\geysir\Ajax\GeysirOpenModalDialogCommand;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Controller for all modal dialogs.
 */
class GeysirModalController extends GeysirControllerBase {
   /**
    * Create a modal dialog to add the first paragraph.
    */
   public function addFirst($parent_entity_type, $parent_entity_bundle, $parent_entity_revision, $field, $field_wrapper_id, $delta, $position, $js = 'nojs', $bundle = NULL) {
    if ($js == 'ajax') {
        $response = new AjaxResponse();
        $paragraph_title = $this->getParagraphTitle($parent_entity_type, $parent_entity_bundle, $field);

        if ($bundle) {
            $newParagraph = Paragraph::create(['type' => $bundle]);
            $form = $this->entityFormBuilder()->getForm($newParagraph, 'geysir_modal_add', []);

            $response->addCommand(new GeysirOpenModalDialogCommand($this->t('Add @paragraph_title', ['@paragraph_title' => $paragraph_title]), render($form)));
        } else {
            $entity = $this->entityTypeManager()->getStorage($parent_entity_type)->loadRevision($parent_entity_revision);
            $bundle_fields = $this->entityFieldManager->getFieldDefinitions($parent_entity_type, $entity->bundle());
            $field_definition = $bundle_fields[$field];
            $bundles = $field_definition->getSetting('handler_settings')['target_bundles'];

            $routeParams = [
                'parent_entity_type' => $parent_entity_type,
                'parent_entity_bundle' => $parent_entity_bundle,
                'parent_entity_revision' => $parent_entity_revision,
                'field' => $field,
                'field_wrapper_id' => $field_wrapper_id,
                'delta' => $delta,
                'position' => $position,
                'js' => $js,
            ];

            $form = \Drupal::formBuilder()->getForm('\Drupal\geysir\Form\GeysirModalParagraphAddSelectTypeForm', $routeParams, $bundles);
            $response->addCommand(new GeysirOpenModalDialogCommand($this->t('Add @paragraph_title', ['@paragraph_title' => $paragraph_title]), render($form)));
        }
        return $response;
    }

    return $this->t('Javascript is required for this functionality to work properly.');
  }

  /**
   * Create a modal dialog to add a single paragraph.
   */
  public function add($parent_entity_type, $parent_entity_bundle, $parent_entity_revision, $field, $field_wrapper_id, $delta, $paragraph, $paragraph_revision, $position, $js = 'nojs', $bundle = NULL) {
    if ($js == 'ajax') {
      $response = new AjaxResponse();
      $paragraph_title = $this->getParagraphTitle($parent_entity_type, $parent_entity_bundle, $field);

      if ($bundle) {
        $newParagraph = Paragraph::create(['type' => $bundle]);
        $form = $this->entityFormBuilder()->getForm($newParagraph, 'geysir_modal_add', []);

        $response->addCommand(new GeysirOpenModalDialogCommand($this->t('Add @paragraph_title', ['@paragraph_title' => $paragraph_title]), render($form)));
      }
      else {
        $entity = $this->entityTypeManager()->getStorage($parent_entity_type)->loadRevision($parent_entity_revision);
        $bundle_fields = $this->entityFieldManager->getFieldDefinitions($parent_entity_type, $entity->bundle());
        $field_definition = $bundle_fields[$field];
        $bundles = $field_definition->getSetting('handler_settings')['target_bundles'];

        $routeParams = [
          'parent_entity_type'     => $parent_entity_type,
          'parent_entity_bundle'   => $parent_entity_bundle,
          'parent_entity_revision' => $parent_entity_revision,
          'field'                  => $field,
          'field_wrapper_id'       => $field_wrapper_id,
          'delta'                  => $delta,
          'paragraph'              => $paragraph->id(),
          'paragraph_revision'     => $paragraph->getRevisionId(),
          'position'               => $position,
          'js'                     => $js,
        ];

        $form = \Drupal::formBuilder()->getForm('\Drupal\geysir\Form\GeysirModalParagraphAddSelectTypeForm', $routeParams, $bundles);
        $response->addCommand(new GeysirOpenModalDialogCommand($this->t('Add @paragraph_title', ['@paragraph_title' => $paragraph_title]), render($form)));
      }

      return $response;
    }

    return $this->t('Javascript is required for this functionality to work properly.');
  }

  /**
   * Create a modal dialog to edit a single paragraph.
   */
  public function edit($parent_entity_type, $parent_entity_bundle, $parent_entity_revision, $field, $field_wrapper_id, $delta, $paragraph, $paragraph_revision, $js = 'nojs') {
    if ($js == 'ajax') {
      $response = new AjaxResponse();
      $form = $this->entityFormBuilder()->getForm($paragraph, 'geysir_modal_edit', []);
      $paragraph_title = $this->getParagraphTitle($parent_entity_type, $parent_entity_bundle, $field);
      $response->addCommand(new GeysirOpenModalDialogCommand($this->t('Edit @paragraph_title', ['@paragraph_title' => $paragraph_title]), render($form)));

      return $response;
    }

    return $this->t('Javascript is required for this functionality to work properly.');
  }

  /**
   * Create a modal dialog to delete a single paragraph.
   */
  public function delete($parent_entity_type, $parent_entity_bundle, $parent_entity_revision, $field, $field_wrapper_id, $delta, $paragraph, $paragraph_revision, $js = 'nojs') {
    if ($js == 'ajax') {
      $options = [
        'dialogClass' => 'geysir-dialog',
        'width' => '20%',
      ];

      $form = $this->entityFormBuilder()->getForm($paragraph, 'geysir_modal_delete', []);

      $response = new AjaxResponse();
      $paragraph_title = $this->getParagraphTitle($parent_entity_type, $parent_entity_bundle, $field);
      $response->addCommand(new OpenModalDialogCommand($this->t('Delete @paragraph_title', ['@paragraph_title' => $paragraph_title]), render($form), $options));
      return $response;
    }

    return $this->t('Javascript is required for this functionality to work properly.');
  }

}
