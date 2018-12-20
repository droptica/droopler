<?php

namespace Drupal\Tests\facets_summary\Functional\Rest;

use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use Drupal\Tests\rest\Functional\EntityResource\XmlEntityNormalizationQuirksTrait;

/**
 * Rest test for xml, basic auth, index entity.
 *
 * @group facets
 */
class FacetSummaryXmlBasicAuthTest extends FacetSummaryResourceTestBase {

  use BasicAuthResourceTestTrait;
  use XmlEntityNormalizationQuirksTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected static $format = 'xml';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'text/xml; charset=UTF-8';

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'basic_auth';

}
