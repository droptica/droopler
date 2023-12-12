<?php

declare(strict_types = 1);

namespace Drupal\d_social_media\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\d_social_media\Form\ConfigurationForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block contains links to social media.
 *
 * @Block(
 *   id = "social_media_block",
 *   admin_label = @Translation("Social Media Block"),
 * )
 */
class SocialMediaBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a MyConfigBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $links = [];
    $config = $this->configFactory->get('d_social_media.settings');
    foreach (ConfigurationForm::getMediaNames() as $name) {
      if (!empty($config->get("link_$name"))) {
        $links[] = [
          'name' => $name,
          'link' => $config->get("link_$name"),
        ];
      }
    }

    // Not render block if links are empty.
    if (empty($links)) {
      return [];
    }

    return [
      '#theme' => 'd_social_media',
      '#attached' => [
        'library' => [
          'd_social_media/last-element-in-a-row',
        ],
      ],
      '#links' => $links,
    ];
  }

}
