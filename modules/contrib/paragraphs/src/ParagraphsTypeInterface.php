<?php

namespace Drupal\paragraphs;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a ParagraphsType entity.
 */
interface ParagraphsTypeInterface extends ConfigEntityInterface {

  /**
   * Returns the ordered collection of feature plugin instances.
   *
   * @return \Drupal\paragraphs\ParagraphsBehaviorCollection
   *   The behavior plugins collection.
   */
  public function getBehaviorPlugins();

  /**
   * Returns an individual plugin instance.
   *
   * @param string $instance_id
   *   The ID of a behavior plugin instance to return.
   *
   * @return \Drupal\paragraphs\ParagraphsBehaviorInterface
   *   A specific feature plugin instance.
   */
  public function getBehaviorPlugin($instance_id);

  /**
   * Retrieves all the enabled plugins.
   *
   * @return \Drupal\paragraphs\ParagraphsBehaviorInterface[]
   *   Array of the enabled plugins as instances.
   */
  public function getEnabledBehaviorPlugins();

  /**
   * Returns the icon file entity.
   *
   * @return \Drupal\file\FileInterface|bool
   *   The icon's file entity or FALSE if icon does not exist.
   */
  public function getIconFile();

  /**
   * Returns the icon's URL.
   *
   * @return string|bool
   *   The icon's URL or FALSE if icon does not exits.
   */
  public function getIconUrl();

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this paragraph type.
   */
  public function getDescription();

  /**
   * Returns TRUE if $plugin_id is enabled on this ParagraphType Entity.
   *
   * @param string $plugin_id
   *   The plugin id, as specified in the plugin annotation details.
   *
   * @return bool
   *   TRUE if the plugin is enabled, FALSE otherwise.
   */
  public function hasEnabledBehaviorPlugin($plugin_id);

}
