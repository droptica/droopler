<?php

namespace Drupal\search_api\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface as DrupalConfigurablePluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides default implementations for plugin configuration forms.
 *
 * @see \Drupal\Core\Plugin\PluginFormInterface
 */
trait PluginFormTrait {

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @see \Drupal\Core\Plugin\PluginFormInterface::validateConfigurationForm()
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @see \Drupal\Core\Plugin\PluginFormInterface::submitConfigurationForm()
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($this instanceof DrupalConfigurablePluginInterface) {
      $this->setConfiguration($form_state->getValues());
    }
  }

}
