<?php

namespace Drupal\facets_summary\Processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets_summary\FacetsSummaryInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Describes a processor.
 */
interface ProcessorInterface extends ConfigurablePluginInterface, PluginInspectionInterface {

  /**
   * Processing stage: build.
   */
  const STAGE_BUILD = 'build';

  /**
   * Adds a configuration form for this processor.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param \Drupal\facets_summary\FacetsSummaryInterface $facets_summary
   *   The facet summary this processor is being added to.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetsSummaryInterface $facets_summary);

  /**
   * Validates a configuration form for this processor.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param \Drupal\facets_summary\FacetsSummaryInterface $facets_summary
   *   The facet summary this processor is being added to.
   */
  public function validateConfigurationForm(array $form, FormStateInterface $form_state, FacetsSummaryInterface $facets_summary);

  /**
   * Submits a configuration form for this processor.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param \Drupal\facets_summary\FacetsSummaryInterface $facets_summary
   *   The facet summary this processor is being added to.
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, FacetsSummaryInterface $facets_summary);

  /**
   * Checks whether this processor implements a particular stage.
   *
   * @param string $stage_identifier
   *   The stage that can be supported by the processor, check the constants
   *   defined in this class for a list of options.
   *
   * @return bool
   *   TRUE if the processor runs on a particular stage; FALSE otherwise.
   */
  public function supportsStage($stage_identifier);

  /**
   * Returns the default weight for a specific processing stage.
   *
   * Some processors should ensure they run earlier or later in a particular
   * stage. Processors with lower weights are run earlier. The default value is
   * used when the processor is first enabled. It can then be changed through
   * reordering by the user.
   *
   * @param string $stage
   *   The stage whose default weight should be returned. See
   *   \Drupal\facets\Processor\ProcessorPluginManager::getProcessingStages()
   *   for the valid values.
   *
   * @return int
   *   The default weight for the given stage.
   */
  public function getDefaultWeight($stage);

  /**
   * Determines whether this processor should always be enabled.
   *
   * @return bool
   *   TRUE if this processor should be forced enabled; FALSE otherwise.
   */
  public function isLocked();

  /**
   * Determines whether this processor should be hidden from the user.
   *
   * @return bool
   *   TRUE if this processor should be hidden from the user; FALSE otherwise.
   */
  public function isHidden();

  /**
   * Retrieves the processor description.
   *
   * @return string
   *   The description of this processor.
   */
  public function getDescription();

}
