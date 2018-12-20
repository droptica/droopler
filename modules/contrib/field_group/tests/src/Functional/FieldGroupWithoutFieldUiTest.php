<?php

namespace Drupal\Tests\field_group\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test field_group without field_ui.
 *
 * @group field_group
 */
class FieldGroupWithoutFieldUiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field_group', 'block'];

  /**
   * Test that local actions show up without field ui enabled.
   */
  public function testLocalActions() {
    // Local actions of field_group should not depend on field_ui
    // @see https://www.drupal.org/node/2719569
    $this->placeBlock('local_actions_block', ['id' => 'local_actions_block']);
    $this->drupalGet(Url::fromRoute('user.login'));
  }

}
