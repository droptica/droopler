<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\facets\FacetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Provides a processor for boolean labels.
 *
 * @FacetsProcessor(
 *   id = "boolean_item",
 *   label = @Translation("Boolean item label"),
 *   description = @Translation("Display configurable On/Off labels instead 1/0 values for boolean fields."),
 *   stages = {
 *     "build" = 35
 *   }
 * )
 */
class BooleanItemProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    $config = $this->getConfiguration();

    /** @var \Drupal\facets\Result\Result $result */
    foreach ($results as $key => $result) {
      $value = '';
      if ($result->getRawValue() == 0) {
        $value = $config['off_value'];
      }
      elseif ($result->getRawValue() == 1) {
        $value = $config['on_value'];
      }
      if ($value == '') {
        unset($results[$key]);
      }
      else {
        $result->setDisplayValue($value);
      }
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $config = $this->getConfiguration();

    $build['on_value'] = [
      '#title' => $this->t('On value'),
      '#type' => 'textfield',
      '#default_value' => $config['on_value'],
      '#description' => $this->t('Use this label instead of <em>0</em> for the <em>On</em> or <em>True</em> value. Leave empty to hide this item.'),
      '#states' => [
        'required' => ['input[name="facet_settings[boolean_item][settings][off_value]"' => ['empty' => TRUE]],
      ],
    ];

    $build['off_value'] = [
      '#title' => $this->t('Off value'),
      '#type' => 'textfield',
      '#default_value' => $config['off_value'],
      '#description' => $this->t('Use this label instead of <em>1</em> for the <em>Off</em> or <em>False</em> value. Leave empty to hide this item.'),
      '#states' => [
        'required' => ['input[name="facet_settings[boolean_item][settings][on_value]"' => ['empty' => TRUE]],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {}

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'on_value' => 'On',
      'off_value' => 'Off',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFacet(FacetInterface $facet) {
    $data_definition = $facet->getDataDefinition();
    if ($data_definition->getDataType() == "boolean") {
      return TRUE;
    }
    if (!($data_definition instanceof ComplexDataDefinitionInterface)) {
      return FALSE;
    }

    $property_definitions = $data_definition->getPropertyDefinitions();
    foreach ($property_definitions as $definition) {
      if ($definition->getDataType() == "boolean") {
        return TRUE;
      }
    }
    return FALSE;
  }

}
