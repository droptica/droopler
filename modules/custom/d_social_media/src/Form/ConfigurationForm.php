<?php

declare(strict_types = 1);

namespace Drupal\d_social_media\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide class ConfigurationForm.
 *
 * @todo Please add validateForm method.
 */
class ConfigurationForm extends ConfigFormBase {

  /**
   * Configuration name.
   */
  const CONFIGURATION_NAME = 'd_social_media.settings';

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $configForm = parent::create($container);
    $configForm->moduleHandler = $container->get('module_handler');
    return $configForm;
  }

  /**
   * Get defined social media machine names.
   *
   * @return array
   *   Social media machine names.
   *
   * @todo Move this to other class or replace with Config API.
   */
  public static function getMediaNames() {
    return [
      'facebook',
      'twitter',
      'youtube',
      'instagram',
      'linkedin',
      'dribbble',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::CONFIGURATION_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'd_social_media_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIGURATION_NAME);

    if ($this->moduleHandler && !$this->moduleHandler->moduleExists('config_translation')) {
      $form['hint'] = [
        '#type' => 'html_tag',
        '#tag' => 'i',
        '#value' => $this->t('* If you intend to translate this form, consider installing the Config Translation module (a new tab will appear).'),
      ];
    }

    foreach (self::getMediaNames() as $name) {
      $form["link_$name"] = [
        '#type' => 'url',
        '#title' => $this->t('@name link', ['@name' => ucfirst($name)]),
        '#default_value' => $config->get("link_$name"),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    foreach (self::getMediaNames() as $name) {
      $this->config(self::CONFIGURATION_NAME)
        ->set("link_$name", $form_state->getValue("link_$name"));
    }
    $this->config(self::CONFIGURATION_NAME)->save();
  }

}
