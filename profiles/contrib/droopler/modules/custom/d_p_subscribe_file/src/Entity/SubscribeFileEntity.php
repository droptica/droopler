<?php

namespace Drupal\d_p_subscribe_file\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the SubscribeFile
 *
 * @ContentEntityType(
 *   id = "SubscribeFileEntity",
 *   label = @Translation("SubscribeFile"),
 *   base_table = "d_p_subscribe_file",
 *   handlers = {
 *     "views_data" = "Drupal\d_p_subscribe_file\Entity\SubscribeFileViewsData",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "name" = "name",
 *     "mail" = "mail",
 *     "created" = "created",
 *     "link_hash" = "link_hash",
 *     "file_hash" = "file_hash",
 *     "fid" = "fid",
 *   },
 * )
 */
class SubscribeFileEntity extends ContentEntityBase implements ContentEntityInterface {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Advertiser entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Advertiser entity.'))
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('name'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['mail'] = BaseFieldDefinition::create('string')
      ->setLabel(t('mail'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('created'));

    $fields['link_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('link_hash'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['file_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('file_hash'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['fid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('fid'))
      ->setSettings(array(
        'default_value' => 0,
      ));

    return $fields;
  }
}
