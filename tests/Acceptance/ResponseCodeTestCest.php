<?php
/**
 * @file
 * Response code test cest for Codeception acceptance tests.
 */
namespace Tests\Acceptance;

use Codeception\Example;
use Tests\Support\AcceptanceTester;

/**
 * @file
 * Response code test cest.
 */

/**
 * Class ResponseCodeTestCest.
 */
class ResponseCodeTestCest {
  /**
   * @var string
   * node type machine name
   */
  private $node_type;

  public function __construct() {
    $this->node_type = 'content_page';
  }

  /**
   * @return array
   */
  protected function pageProvider() {
    $type = $this->node_type;
    $output = shell_exec("drush sql:query \"SELECT nid, langcode FROM node_field_data WHERE type = '$type' AND status = 1\"");
    $lines = explode("\n", trim($output));
    $vars = [];
    $nodesID = 1;
    foreach ($lines as $line) {
        if (empty($line)) continue;
        $parts = preg_split('/\s+/', trim($line));
        if (count($parts) >= 2 && is_numeric($parts[0])) {
            $vars[$nodesID]['url'] = $parts[0];
            $vars[$nodesID]['langcode'] = $parts[1];
            $nodesID++;
        }
    }
    return $vars;
}

  /**
   * Response code test.
   *
   * @dataprovider pageProvider
   * @param \Tests\Support\AcceptanceTester $I
   */
  public function responseCodeTest(AcceptanceTester $I, Example $example) {
    $I->wantTo('Response Code Test on page: /node/' . $example['url'] . $example['langcode']);
    $I->amOnPage('/node/' . $example['url']);
    $I->seeResponseCodeIs(200);
    $I->dontSee('The website encountered an unexpected error.');
    $I->dontSeeElement('.messages--error');
  }

}
