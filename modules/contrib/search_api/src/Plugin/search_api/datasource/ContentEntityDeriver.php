<?php

namespace Drupal\search_api\Plugin\search_api\datasource;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives a datasource plugin definition for every content entity type.
 *
 * @see \Drupal\search_api\Plugin\search_api\datasource\ContentEntityDatasource
 */
class ContentEntityDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $derivatives = NULL;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $deriver = new static();

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    $deriver->setEntityTypeManager($entity_type_manager);

    /** @var \Drupal\Core\StringTranslation\TranslationInterface $translation */
    $translation = $container->get('string_translation');
    $deriver->setStringTranslation($translation);

    return $deriver;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager ?: \Drupal::entityTypeManager();
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!isset($this->derivatives)) {
      $plugin_derivatives = [];
      foreach ($this->getEntityTypeManager()->getDefinitions() as $entity_type => $entity_type_definition) {
        // We only support content entity types at the moment, since config
        // entities don't implement \Drupal\Core\TypedData\ComplexDataInterface.
        if ($entity_type_definition instanceof ContentEntityType) {
          $plugin_derivatives[$entity_type] = [
            'entity_type' => $entity_type,
            'label' => $entity_type_definition->getLabel(),
            'description' => $this->t('Provides %entity_type entities for indexing and searching.', ['%entity_type' => $entity_type_definition->getLabel()]),
          ] + $base_plugin_definition;
        }
      }

      $this->derivatives = $plugin_derivatives;
    }

    return $this->derivatives;
  }

}
