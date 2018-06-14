<?php

namespace Drupal\tvi\Service;

use Drupal\tvi\Service\TaxonomyViewsIntegratorManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\views\Views;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Default implementation of TaxonomyViewsIntegratorManagerInterface
 *
 * The manager will inspect the configuration of the passed TermInterface object
 * and determine which view will be injected as the page output.
 *
 * At a later point, it would be great to support adherence to the Views permission
 * settings, there is an outstanding patch and issue for that.
 */
class TaxonomyViewsIntegratorManager implements TaxonomyViewsIntegratorManagerInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * @var \Drupal\Core\ConfigFactory;
   */
  private $config;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  private $requestStack;

  /**
   * TaxonomyViewsIntegratorManager constructor.
   * @param \Drupal\Core\Config\ConfigFactory $config
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   */
  public function __construct(ConfigFactory $config, EntityTypeManagerInterface $entity_type_manager, RequestStack $requestStack) {
    $this->config = $config;
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $config = $container->get('config.factory');
    $entity_type_manager = $container->get('entity_type.manager');
    $requestStack = $container->get('request_stack');
    return new static($config, $entity_type_manager, $requestStack);
  }

  /**
   * Get the taxonomy view integrator settings for this term entity.
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   * @return \Drupal\Core\Config\Config
   */
  public function getTermConfigSettings(TermInterface $taxonomy_term) {
    return $this->config->get('tvi.taxonomy_term.' . $taxonomy_term->id());
  }

  /**
   * Get the taxonomy view integrator settings for this terms vocabulary entity.
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   * @return \Drupal\Core\Config\Config
   */
  public function getVocabularyConfigSettings(TermInterface $taxonomy_term) {
    return $this->config->get('tvi.taxonomy_vocabulary.' . $taxonomy_term->getVocabularyId());
  }

  /**
   * Get the default taxonomy view integrator settings.
   * @return \Drupal\Core\Config\Config
   */
  public function getDefaultConfigSettings() {
    return $this->config->get('tvi.global_settings');
  }

  /**
   * Wrapper method for obtaining parents of a given taxonomy term.
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   * @return array
   */
  public function getTermParents(TermInterface $taxonomy_term) {
    return $this->entityTypeManager->getStorage('taxonomy_term')->loadAllParents($taxonomy_term->id());
  }

  /**
   * Return an array of arguments from the URI.
   * It is assumed tha URI will be taxonomy/term/{taxonomy_term}, so anything after that will be returned.
   * @return array
   */
  public function getRequestUriArguments() {
    return array_slice(explode('/',  $this->requestStack->getCurrentRequest()->getRequestUri()), 3);
  }

  /**
   * {@inheritdoc}
   */
  public function getTaxonomyTermView(TermInterface $taxonomy_term) {
    $config = $this->getTermConfigSettings($taxonomy_term);
    $check_vocab = true;
    $check_global = true;

    // if we have no matches, we are going to call the default term view that ships with core Drupal
    $view_name = 'taxonomy_term';
    $view_id = 'page_1';
    $view_arguments = [$taxonomy_term->id()];

    if ($config->get('enable_override')) {
      $view_name = $config->get('view');
      $view_id = $config->get('view_display');
    } else {
      // check parent terms for settings
      // if parent is enabled and has 'children inherit settings' is checked off
      foreach ($this->getTermParents($taxonomy_term) as $parent) {
        // skip the current term, it is returned by loadAllParents
        if ($taxonomy_term->id() === $parent->id()) {
          continue;
        }

        $config = $this->getTermConfigSettings($parent);

        if ($config->get('enable_override') && $config->get('inherit_settings')) {
          $view_name = $config->get('view');
          $view_id = $config->get('view_display');
          $check_vocab = false;
          break;
        }
      }

      // check vocab for settings
      if ($check_vocab) {
        $config = $this->getVocabularyConfigSettings($taxonomy_term);

        if ($config->get('enable_override') && $config->get('inherit_settings')) {
          $view_name = $config->get('view');
          $view_id = $config->get('view_display');
          $check_global = false;
        }
      }

      // check for global settings
      if ($check_global) {
        $config = $this->getDefaultConfigSettings();

        if ($config->get('enable_override')) {
          $view_name = $config->get('view');
          $view_id = $config->get('view_display');
        }
      }
    }

    // if the option to pass all args to views is enabled, pass them on
    if ($config->get('pass_arguments')) {
      $view_arguments += $this->getRequestUriArguments();
    }

    return Views::getView($view_name)->executeDisplay($view_id, $view_arguments);
  }
}