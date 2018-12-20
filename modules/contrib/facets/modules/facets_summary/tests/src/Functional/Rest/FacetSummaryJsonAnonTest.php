<?php

namespace Drupal\Tests\facets_summary\Functional\Rest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * Rest test for json, anonymous, index entity.
 *
 * @group facets
 */
class FacetSummaryJsonAnonTest extends FacetSummaryResourceTestBase {

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
