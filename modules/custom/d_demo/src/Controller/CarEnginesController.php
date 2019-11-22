<?php

namespace Drupal\d_demo\Controller;

use Drupal\Core\Controller\ControllerBase;

class CarEnginesController extends ControllerBase
{
  public function content() {
    $build = [
      '#markup' => $this->t('Car engines'),
    ];

    return $build;
  }
}
