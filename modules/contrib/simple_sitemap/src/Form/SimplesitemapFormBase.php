<?php

namespace Drupal\simple_sitemap\Form;

use Drupal\simple_sitemap\EntityHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\Core\Path\PathValidator;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Class SimplesitemapFormBase
 * @package Drupal\simple_sitemap\Form
 */
abstract class SimplesitemapFormBase extends ConfigFormBase {

  /**
   * @var \Drupal\simple_sitemap\Simplesitemap
   */
  protected $generator;

  /**
   * @var \Drupal\simple_sitemap\Form\FormHelper
   */
  protected $formHelper;

  /**
   * @var \Drupal\simple_sitemap\EntityHelper
   */
  protected $entityHelper;

  /**
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * SimplesitemapFormBase constructor.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   * @param \Drupal\simple_sitemap\Form\FormHelper $form_helper
   * @param \Drupal\simple_sitemap\EntityHelper $entity_helper
   * @param \Drupal\Core\Path\PathValidator $path_validator
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  public function __construct(
    Simplesitemap $generator,
    FormHelper $form_helper,
    EntityHelper $entity_helper,
    PathValidator $path_validator,
    LanguageManagerInterface $language_manager
  ) {
    $this->generator = $generator;
    $this->formHelper = $form_helper;
    $this->entityHelper = $entity_helper;
    $this->pathValidator = $path_validator;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.form_helper'),
      $container->get('simple_sitemap.entity_helper'),
      $container->get('path.validator'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_sitemap.settings'];
  }

  /**
   *
   */
  protected function getDonationText() {
    return '<div class="description">' . $this->t('If you would like to say thanks and support the development of this module, a <a target="_blank" href="@url">donation</a> is always appreciated.', ['@url' => 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5AFYRSBLGSC3W']) . '</div>';
  }

}
