<?php

namespace Drupal\paragraphs_library\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Form controller for paragraph type forms.
 */
class LibraryItemForm extends ContentEntityForm {

  /**
   * @var \Drupal\paragraphs_library\LibraryItemInterface
   */
  protected $entity;

  /**
   * Provides messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs a LibraryItemForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityManagerInterface $entity_manager, Messenger $messenger, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('messenger'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $insert = $this->entity->isNew();
    parent::save($form, $form_state);
    $form_state->setRedirect('entity.paragraphs_library_item.collection');
    if ($insert) {
      $this->messenger->addMessage(t('Paragraph %label has been created.', ['%label' => $this->entity->label()]));
    }
    else {
      $this->messenger->addMessage(t('Paragraph %label has been updated.', ['%label' => $this->entity->label()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getNewRevisionDefault() {
    return TRUE;
  }

}
