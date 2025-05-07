<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

use Codeception\Module;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class UserLogin extends Module
{
    public function loginAsAdmin() {
        /** @var \Codeception\Module\WebDriver $I */
        $I = $this->getModule('WebDriver');
    
        $I->amOnPage('/user');
    
        $I->fillField('[name="name"]', 'admin');
        $I->fillField('[name="pass"]', '123');
        $I->click('input[type="submit"]');
    }

    
}
