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
   * Wrapper format.
   *
   * @var string
   */
  protected $wrapperFormat;

  /**
   * Triggering element.
   *
   * @var string
   */
  protected $triggeringElement;

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
    $this->wrapperFormat = $this->request->query->get('_wrapper_format');
    $this->triggeringElement = $this->request->request->get('_triggering_element_name');
  }

  /**
   * {@inheritdoc}
   */
  public function isGeysirModalRequest(): bool {
    try {
      $controller = $this->resolver->getController($this->request);

      if (isset($controller[0])) {
        return $controller[0] instanceof GeysirModalController && $this->isGeysirModal();
      }

      return $controller instanceof GeysirModalController && $this->isGeysirModal();
    }
    catch (\LogicException $exception) {
      return FALSE;
    }
  }

  /**
   * Check if the wrapper format is drupal modal.
   *
   * @return bool
   *   True if the wrapper format is drupal modal, false otherwise.
   */
  private function isModal(): bool {
    return $this->wrapperFormat === 'drupal_modal';
  }

  /**
   * Check if the wrapper format is drupal ajax.
   *
   * @return bool
   *   True if the wrapper format is drupal ajax, false otherwise.
   */
  private function isAjax(): bool {
    return $this->wrapperFormat === 'drupal_ajax';
  }

  /**
   * Check if the triggering element name is not op.
   *
   * @return bool
   *   True if the triggering element name is not op, false otherwise.
   */
  private function isNotOpElement(): bool {
    return $this->triggeringElement !== 'op';
  }

  /**
   * Check if we have geysir modal or some ajax inside geysir modal.
   *
   * @return bool
   *   True if we have geysir modal or some ajax inside geysir modal,
   *   false otherwise.
   */
  private function isGeysirModal(): bool {
    return $this->isModal() || ($this->isAjax() && $this->isNotOpElement());
  }

}
