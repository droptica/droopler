<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

/**
 * @file
 * Response code test cest.
 */

/**
 * Class ResponseCodeTestCest
 */
class ResponseCodeTestCest
{
    /**
     * @var string
     * node type machine name
     */
    private $node_type;

    public function __construct()
    {
        $this->node_type = 'content_page';
    }

    /**
     * @return array
     */
    protected function pageProvider()
    {
        $query = \Drupal::database()->select('node_field_data', 'nd');
        $query->fields('nd', ['nid', 'type', 'status', 'langcode']);
        $nodes = $query->execute();
        $nodesID = 1;

        while ($row = $nodes->fetchAssoc()) {
            if ($row['type'] == $this->node_type && $row['status'] == '1') {
                $vars[$nodesID]['url'] = $row['nid'];
                $vars[$nodesID]['langcode'] = $row['langcode'];
                $nodesID += 1;
            }
        }
        return $vars;
    }

    /**
     * Response code test.
     *
     * @dataprovider pageProvider
     * @param AcceptanceTester $I
     */
    public function responseCodeTest(AcceptanceTester $I, \Codeception\Example $example)
    {
        $I->wantTo('Response Code Test on page: /node/' . $example['url'] . $example['langcode']);
        $I->prepareLogWatch();
        $I->amOnPage('/node/' . $example['url']);
        $I->seeResponseCodeIs(200);
        $I->dontSee('The website encountered an unexpected error.');
        $I->dontSeeElement('.messages--error');
        $I->checkLogs();
    }
}
