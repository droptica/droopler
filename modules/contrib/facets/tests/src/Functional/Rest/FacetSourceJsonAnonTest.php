<?php

namespace Drupal\Tests\facets\Functional\Rest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * Rest test for json, anonymous, index entity.
 *
 * @group facets
 */
class FacetSourceJsonAnonTest extends FacetSourceResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

}
