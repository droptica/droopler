<?php

/**
 * @file
 * Example unit test.
 */

namespace Tests\Unit;

use Drupal\node\Entity\Node;

/**
 * Class ExampleUnitTest
 *
 * @package unit
 */
class ExampleUnitTest extends \Codeception\Test\Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * Test that module is disabled.
     */
    public function testModulesDisabled()
    {
        $modules = array();
        $modules[] = 'devel';

        foreach ($modules as $module_name) {
            $this->assertEquals(false, Drupal::moduleHandler()->moduleExists($module_name));
        }
    }
}
