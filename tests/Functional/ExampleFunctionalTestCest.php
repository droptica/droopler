<?php

/**
 * @file
 * Example functional test.
 */

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

/**
 * Class ExampleFunctionalTestCest
 *
 * @package functional
 */
class ExampleFunctionalTestCest
{
    /**
     * Test - I can see node in database.
     *
     * @param FunctionalTester $I
     */
    public function exampleTestOfText(FunctionalTester $I)
    {
        $I->wantTo('Test - I can see node in database');
        $I->dontSeeInDatabase('node_field_data', ['type' => 'article', 'title' => 'Functional Test Test']);
        $I->amOnPage('/user/login');
        $I->submitForm('#user-login-form', ['name' => 'admin_user', 'pass' => '123']);
        $I->amOnPage('/node/add/article');
        $I->fillField('#edit-title-0-value', 'Functional Test');
        $I->selectOption('[id^=edit-body-0-format--]', 'restricted_html');
        $I->fillField('#edit-body-0-value', 'Test text in body article');
        $I->click('#edit-submit');
        $I->amOnPage('/user/logout');
        $I->seeInDatabase('node_field_data', ['type' => 'article', 'title' => 'Functional Test Test']);
    }
}
