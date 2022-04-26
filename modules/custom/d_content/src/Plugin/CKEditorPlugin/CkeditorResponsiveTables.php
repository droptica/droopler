<?php

namespace Drupal\d_content\Plugin\CKEditorPlugin;

use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "responsive tables" plugin.
 *
 * @CKEditorPlugin(
 *   id = "d_responsive_tables",
 *   label = @Translation("CKEditor responsive tables wrapper"),
 *   module = "d_content"
 * )
 */
class CkeditorResponsiveTables extends CKEditorPluginBase implements CKEditorPluginInterface, CKEditorPluginContextualInterface, ContainerFactoryPluginInterface {

  /**
   * ExtensionPathResolver service.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected ExtensionPathResolver $extensionPathResolver;

  /**
   * CkeditorResponsiveTables constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extension_path_resolver
   *   The extension path resolver.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ExtensionPathResolver $extension_path_resolver
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->extensionPathResolver = $extension_path_resolver;
  }

  /**
   * Creates a new CkeditorResponsiveTables instance.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('extension.path.resolver')
    );
  }

  /**
   * Get path to plugin folder.
   */
  public function getPluginPath() {
    return $this->extensionPathResolver->getPath('module', 'd_content') . '/js/plugins/d_responsive_tables';
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
