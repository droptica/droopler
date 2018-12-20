<?php

namespace Drupal\facets_rest\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\rest\Plugin\views\style\Serializer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * The style plugin for serialized output formats.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "facets_serializer",
 *   title = @Translation("Facets serializer"),
 *   help = @Translation("Serializes views row data using the Serializer component."),
 *   display_types = {"data"}
 * )
 */
class FacetsSerializer extends Serializer {

  /**
   * Tha facet manager.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetsManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('serializer'),
      $container->getParameter('serializer.formats'),
      $container->getParameter('serializer.format_providers'),
      $container->get('facets.manager')
    );
  }

  /**
   * Constructs a FacetsSerializer object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SerializerInterface $serializer, array $serializer_formats, array $serializer_format_providers, DefaultFacetManager $facets_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer, $serializer_formats, $serializer_format_providers);
    $this->facetsManager = $facets_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['show_facets'] = ['default' => TRUE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['show_facets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show facets in the output'),
      '#default_value' => $this->options['show_facets'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = [];
    // If the Data Entity row plugin is used, this will be an array of entities
    // which will pass through Serializer to one of the registered Normalizers,
    // which will transform it to arrays/scalars. If the Data field row plugin
    // is used, $rows will not contain objects and will pass directly to the
    // Encoder.
    foreach ($this->view->result as $row_index => $row) {
      // Keep track of the current rendered row, like every style plugin has to
      // do.
      // @see \Drupal\views\Plugin\views\style\StylePluginBase::renderFields
      $this->view->row_index = $row_index;
      $rows['search_results'][] = $this->view->rowPlugin->render($row);
    }
    unset($this->view->row_index);

    // Get the content type configured in the display or fallback to the
    // default.
    if ((empty($this->view->live_preview))) {
      $content_type = $this->displayHandler->getContentType();
    }
    else {
      $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'json';
    }

    // Processing facets.
    $facetsource_id = "search_api:views_rest__{$this->view->id()}__{$this->view->getDisplay()->display['id']}";
    $facets = $this->facetsManager->getFacetsByFacetSourceId($facetsource_id);
    $this->facetsManager->updateResults($facetsource_id);

    $processed_facets = [];
    foreach ($facets as $facet) {
      $processed_facets[] = $this->facetsManager->build($facet);
    }

    $rows['facets'] = array_values($processed_facets);
    if (!$this->options['show_facets']) {
      $rows = $rows['search_results'];
    }
    return $this->serializer->serialize($rows, $content_type, ['views_style_plugin' => $this]);
  }

}
