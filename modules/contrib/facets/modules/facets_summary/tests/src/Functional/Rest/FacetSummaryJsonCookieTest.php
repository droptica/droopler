<?php

namespace Drupal\Tests\facets_summary\Functional\Rest;

use Drupal\Tests\rest\Functional\CookieResourceTestTrait;

/**
 * Rest test for json, cookie, index entity.
 *
 * @group facets
 */
class FacetSummaryJsonCookieTest extends FacetSummaryResourceTestBase {

  use CookieResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

}
