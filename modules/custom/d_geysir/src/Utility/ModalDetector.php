<?php

namespace Drupal\d_geysir\Utility;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\geysir\Controller\GeysirModalController;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides utility service to detect geysir modal.
 *
 * @package Drupal\d_geysir\Utility
 */
class ModalDetector implements ModalDetectorInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $request;

  /**
   * The controller resolver service.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $resolver;

  /**
   * Modal detector contructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver service.
   */
  public function __construct(RequestStack $request_stack, ControllerResolverInterface $controller_resolver) {
    $this->request = $request_stack->getCurrentRequest();
    $this->resolver = $controller_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function isGeysirModalRequest(): bool {
    try {
      $controller = $this->resolver->getController($this->request);

      if (isset($controller[0])) {
        return $controller[0] instanceof GeysirModalController;
      }

      return $controller instanceof GeysirModalController;
    }
    catch (\LogicException $exception) {
      return FALSE;
    }
  }

}
