<?php

namespace Drupal\d_product\Plugin\views\argument;

use Drupal\node\Plugin\views\argument\Vid;

/**
 * Argument handler to accept a node revision id.
 *
 * @ViewsArgument("node_vid")
 */
class NodeVid extends Vid {

  /**
   * Override the behavior of title(). Get the title of the revision.
   */
  public function titleQuery() {
    return $this->database->query('SELECT nr.vid, nr.nid, nfr.title
      FROM {node_revision} nr
      INNER JOIN {node_field_revision} nfr ON nr.vid = nfr.vid
      WHERE nr.vid IN ( :vids[] )', [':vids[]' => $this->value])->fetchAllKeyed(1, 2);
  }

}
