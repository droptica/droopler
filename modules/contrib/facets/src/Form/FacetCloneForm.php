<?php

namespace Drupal\facets\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Drupal\search_api\Display\DisplayPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for creating and editing facets.
 */
class FacetCloneForm extends EntityForm {

  /**
   * Facet source plugin manager.
   *
   * @var \Drupal\facets\FacetSource\FacetSourcePluginManager
   */
  protected $facetSourcePluginManager;

  /**
   * The facet entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $facetStorage;

  /**
   * Search API display source plugin manager.
   *
   * @var \Drupal\search_api\Display\DisplayPluginManager
   */
  protected $displayPluginManager;

  /**
   * Creates the class.
   *
   * @param \Drupal\facets\FacetSource\FacetSourcePluginManager $facetSourcePluginManager
   *   The facet source plugin manager.
   * @param \Drupal\search_api\Display\DisplayPluginManager $displayPluginManager
   *   Search api's display plugin manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $facetStorage
   *   Entity storage class.
   */
  public function __construct(FacetSourcePluginManager $facetSourcePluginManager, DisplayPluginManager $displayPluginManager, EntityStorageInterface $facetStorage) {
    $this->facetSourcePluginManager = $facetSourcePluginManager;
    $this->displayPluginManager = $displayPluginManager;
    $this->facetStorage = $facetStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.facets.facet_source'),
      $container->get('plugin.manager.search_api.display'),
      $container->get('entity_type.manager')->getStorage('facets_facet')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $this->getEntity();

    if (strpos($facet->getFacetSourceId(), 'search_api:') === FALSE) {
      // We don't know how to clone other kinds of facets.
      drupal_set_message($this->t('We can only clone Search API based facets.'));
      return [];
    }

    /** @var \Drupal\search_api\Display\DisplayInterface[] $facet_source_definitions */
    $facet_source_definitions = $this->facetSourcePluginManager->getDefinitions();

    // Get the base table from the facet source's view.
    $facet_source_id = $facet->getFacetSourceId();
    $search_api_display_id = $facet_source_definitions[$facet_source_id]['display_id'];

    /** @var \Drupal\search_api\Display\DisplayInterface $current_display */
    $current_display = $this->displayPluginManager
      ->createInstance($search_api_display_id);
    $current_index = $current_display->getIndex()->id();

    // Create a list of all other search api displays that have the same index.
    $options = [];
    foreach ($facet_source_definitions as $source) {
      $current_display = $this->displayPluginManager
        ->createInstance($source['display_id']);
      if ($current_display->getIndex()->id() !== $current_index) {
        continue;
      }

      $options[$source['id']] = $source['label'];
    }

    $form['destination_facet_source'] = [
      '#type' => 'radios',
      '#title' => $this->t("Clone the facet to this facet source:"),
      '#options' => $options,
      '#required' => TRUE,
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('The administrative name used for this facet.'),
      '#default_value' => $this->t('Duplicate of @label', ['@label' => $this->entity->label()]),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => '',
      '#machine_name' => [
        'exists' => [$this->facetStorage, 'load'],
        'source' => ['name'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Duplicate'),
    ];
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\facets\FacetInterface $facet */
    $facet = $this->entity->createDuplicate();
    $facet->set('name', $form_state->getValue('name'));
    $facet->set('id', $form_state->getValue('id'));
    $facet->set('facet_source_id', $form_state->getValue('destination_facet_source'));
    $facet->save();

    drupal_set_message($this->t('Facet cloned to :label', [':label' => $facet->label()]));

    // Redirect the user to the view admin form.
    $form_state->setRedirectUrl($facet->toUrl('edit-form'));
  }

}
