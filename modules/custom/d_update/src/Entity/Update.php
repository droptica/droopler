<?php

declare(strict_types=1);

namespace Drupal\d_update\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Update entity.
 *
 * @ContentEntityType(
 *   id = "d_update_update",
 *   label = @Translation("Update"),
 *   base_table = "d_update_update",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class Update extends ContentEntityBase implements UpdateInterface {

  /**
   * {@inheritdoc}
   *
   * Tag the entity with the current uid; `\Drupal::currentUser()` is used
   * here because `preCreate()` runs before the entity is wired to the
   * service container.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values): void {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return (int) $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime(): int {
    return (int) $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp): self {
    $this->set('changed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTimeAcrossTranslations(): int {
    $changed = $this->getUntranslated()->getChangedTime();
    foreach ($this->getTranslationLanguages(FALSE) as $language) {
      $translation_changed = $this->getTranslation($language->getId())->getChangedTime();
      $changed = max($translation_changed, $changed);
    }
    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function wasSuccessfulByHook(): bool {
    return (bool) $this->get('successful_by_hook')->value;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function setSuccessfulByHook(bool $success): self {
    $this->set('successful_by_hook', $success);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = [];

    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Update entity.'))
      ->setReadOnly(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length'    => 50,
        'text_processing' => 0,
      ]);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Update entity.'))
      ->setReadOnly(TRUE);

    $fields['successful_by_hook'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Successful by Hook'))
      ->setDescription(t('Indicates if the update hook was successful.'));

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of Update entity.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
