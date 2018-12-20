<?php

namespace Drupal\geysir\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFieldManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class GeysirControllerBase extends ControllerBase {

  /**
   * The entity field manager.
   *
   * @var Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityFieldManager $entityFieldManager) {
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entityFieldManager = $container->get('entity_field.manager');
    return new static($entityFieldManager);
  }

  /**
   * Returns the paragraph title set for the current paragraph field.
   *
   * @param $parent_entity_type
   *   The entity type of the parent entity of this paragraphs field.
   * @param $parent_entity_bundle
   *   The bundle of the parent entity of this paragraphs field.
   * @param $field
   *   The machine name of the paragraphs field.
   *
   * @return string
   *   The paragraph title set for the current paragraph field.
   */
  protected function getParagraphTitle($parent_entity_type, $parent_entity_bundle, $field) {
    $form_mode = 'default';

    $parent_field_settings = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($parent_entity_type . '.' . $parent_entity_bundle . '.' . $form_mode)
      ->getComponent($field);

    $paragraph_title = isset($parent_field_settings['settings']['title']) ?
      $parent_field_settings['settings']['title'] :
      $this->t('Paragraph');

    return strtolower($paragraph_title);
  }

}
