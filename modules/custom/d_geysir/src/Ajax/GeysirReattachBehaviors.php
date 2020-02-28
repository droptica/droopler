<?php

namespace Drupal\d_geysir\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines an AJAX command that reattaches behaviors.
 *
 * @ingroup ajax
 */
class GeysirReattachBehaviors implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'd_geysir_reattach_behaviors',
    ];
  }

}
