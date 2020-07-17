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
    $titles = [];

    $results = $this->database->query('SELECT nr.vid, nr.nid, nfr.title 
      FROM {node_revision} nr
      INNER JOIN {node_field_revision} nfr ON nr.vid = nfr.vid
      WHERE nr.vid IN ( :vids[] )', array(':vids[]' => $this->value))->fetchAllAssoc('vid', \PDO::FETCH_ASSOC);
    $nids = [];
    foreach ($results as $result) {
      $nids[] = $result['nid'];
    }

    $nodes = $this->nodeStorage->loadMultiple(array_unique($nids));

    foreach ($results as $result) {
      $nodes[$result['nid']]->set('title', $result['title']);
      $titles[] = $nodes[$result['nid']]->label();
    }

    return $titles;
  }

}
