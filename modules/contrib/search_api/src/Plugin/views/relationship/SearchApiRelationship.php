<?php

namespace Drupal\search_api\Plugin\views\relationship;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;

/**
 * Views relationship plugin for datasources.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("search_api")
 */
class SearchApiRelationship extends RelationshipPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

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
   *   The new entity type manager.
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
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['skip_access']['default'] = FALSE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['required']['#access'] = FALSE;

    $form['skip_access'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip access checks'),
      '#description' => $this->t('Do not verify that the user has access to the entities referenced through this relationship. This will allow you to display data to the user to which they normally would not have access. This should therefore be used with care.'),
      '#default_value' => $this->options['skip_access'],
      '#weight' => -1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->alias = $this->field;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];

    if (!empty($this->definition['entity type'])) {
      $entity_type = $this->getEntityTypeManager()
        ->getDefinition($this->definition['entity type']);
      if ($entity_type) {
        $dependencies['module'][] = $entity_type->getProvider();
      }
    }

    return $dependencies;
  }

}
