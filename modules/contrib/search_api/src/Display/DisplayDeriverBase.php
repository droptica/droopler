<?php

namespace Drupal\search_api\Display;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class for display derivers.
 */
abstract class DisplayDeriverBase extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $derivatives = NULL;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $deriver = new static();

    $entity_type_manager = $container->get('entity_type.manager');
    $deriver->setEntityTypeManager($entity_type_manager);

    $translation = $container->get('string_translation');
    $deriver->setStringTranslation($translation);

    return $deriver;
  }

  /**
   * Retrieves the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * Sets the entity manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

}
