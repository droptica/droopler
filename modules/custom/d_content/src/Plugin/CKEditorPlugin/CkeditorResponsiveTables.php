<?php

namespace Drupal\d_content\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;

/**
 * Defines the "responsive tables" plugin.
 *
 * @CKEditorPlugin(
 *   id = "d_responsive_tables",
 *   label = @Translation("CKEditor responsive tables wrapper"),
 *   module = "d_content"
 * )
 */
class CkeditorResponsiveTables extends CKEditorPluginBase implements CKEditorPluginInterface, CKEditorPluginContextualInterface {

  /**
   * Get path to plugin folder.
   */
  public function getPluginPath() {
    return \Drupal::service('extension.path.resolver')->getPath('module', 'd_content') . '/js/plugins/d_responsive_tables';
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
   */
  public function getFile() {
    return $this->getPluginPath() . '/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {

    if (!$editor->hasAssociatedFilterFormat()) {
      return FALSE;
    }

    // Automatically enable this plugin if Table button is enabled.
    $settings = $editor->getSettings();
    if (!empty($settings)) {
      foreach ($settings['toolbar']['rows'] as $row) {
        foreach ($row as $group) {
          foreach ($group['items'] as $button) {
            if ($button === 'Table') {
              return TRUE;
            }
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
