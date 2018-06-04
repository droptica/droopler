<?php

namespace Drupal\tvi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tvi\Service\TaxonomyViewsIntegratorManagerInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TaxonomyViewsIntegratorTermPageController extends ControllerBase {

  /**
   * @var \Drupal\tvi\Service\TaxonomyViewsIntegratorManager
   */
  private $term_display_manager;

  /**
   * TaxonomyViewsIntegratorTermPageController constructor.
   * @param \Drupal\tvi\Service\TaxonomyViewsIntegratorManagerInterface $term_display_manager
   */
  public function __construct(TaxonomyViewsIntegratorManagerInterface $term_display_manager) {
    $this->term_display_manager = $term_display_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $term_display_manager = $container->get('tvi.tvi_manager');
    return new static($term_display_manager);
  }

  /**
   * Returns a render array from a View based on the taxonomy view integrator configuration settings for the requested term entity.
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function render(TermInterface $taxonomy_term) {
    return $this->term_display_manager->getTaxonomyTermView($taxonomy_term);
  }
}