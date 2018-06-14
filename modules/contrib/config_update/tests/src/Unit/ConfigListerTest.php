<?php

namespace Drupal\Tests\config_update\Unit;

use Drupal\config_update\ConfigLister;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the \Drupal\config_update\ConfigLister class.
 *
 * @group config_update
 *
 * @coversDefaultClass \Drupal\config_update\ConfigLister
 */
class ConfigListerTest extends UnitTestCase {

  /**
   * The config lister to test.
   *
   * @var \Drupal\config_update\ConfigLister
   */
  protected $configLister;

  /**
   * The mocked entity definition information.
   *
   * @var string[]
   */
  protected $entityDefinitionInformation;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->configLister = new ConfigLister($this->getEntityManagerMock(), $this->getConfigStorageMock('active'), $this->getConfigStorageMock('extension'), $this->getConfigStorageMock('optional'));
  }

  /**
   * Creates a mock entity manager for the test.
   */
  protected function getEntityManagerMock() {
    // Make a list of fake entity definitions. Make sure they are not sorted,
    // to test that the methods sort them. Also make sure there are a couple
    // with prefixes that are subsets of each other.
    $this->entityDefinitionInformation = [
      ['prefix' => 'foo.bar', 'type' => 'foo'],
      ['prefix' => 'foo.barbaz', 'type' => 'bar'],
      ['prefix' => 'baz.foo', 'type' => 'baz'],
    ];

    $definitions = [];
    foreach ($this->entityDefinitionInformation as $info) {
      $def = $this->getMockBuilder('Drupal\Core\Config\Entity\ConfigEntityTypeInterface')->getMock();
      $def
        ->expects($this->any())
        ->method('getConfigPrefix')
        ->willReturn($info['prefix']);
      $def
        ->expects($this->any())
        ->method('isSubclassOf')
        ->willReturn(TRUE);

      $def->getConfigPrefix();

      $definitions[$info['type']] = $def;
    }

    $manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManagerInterface')->getMock();
    $manager
      ->method('getDefinitions')
      ->willReturn($definitions);

    return($manager);
  }

  /**
   * Creates a mock config storage object for the test.
   *
   * @param string $type
   *   Type of storage object to return: 'active', 'extension', or 'optional'.
   */
  protected function getConfigStorageMock($type) {
    if ($type == 'active') {
      $storage = $this->getMockBuilder('Drupal\Core\Config\StorageInterface')->getMock();

      // The only use of the read() method on active storage is
      // with the core.extension config, to get the profile name.
      $storage
        ->method('read')
        ->willReturn(['profile' => 'standard']);

      $map = [
        ['foo.bar', ['foo.bar.one', 'foo.bar.two', 'foo.bar.three']],
        ['foo.barbaz', ['foo.barbaz.four', 'foo.barbaz.five', 'foo.barbaz.six']],
        ['baz.foo'], [],
        ['',
          [
            'foo.bar.one',
            'foo.bar.two',
            'foo.bar.three',
            'foo.barbaz.four',
            'foo.barbaz.five',
            'foo.barbaz.six',
            'something.else',
            'another.one',
          ],
        ],
      ];

      $storage
        ->method('listAll')
        ->will($this->returnValueMap($map));
    }
    elseif ($type == 'extension') {
      $storage = $this->getMockBuilder('Drupal\Core\Config\ExtensionInstallStorage')->disableOriginalConstructor()->getMock();
      $storage
        ->method('getComponentNames')
        ->willReturn([
          'foo.bar.one' => 'ignored',
          'foo.bar.two' => 'ignored',
          'foo.bar.seven' => 'ignored',
          'foo.barnot.three' => 'ignored',
          'something.else' => 'ignored',
        ]);

      $map = [
        ['foo.bar', ['foo.bar.one', 'foo.bar.two', 'foo.bar.seven']],
        ['baz.foo'], [],
        ['',
          [
            'foo.bar.one',
            'foo.bar.two',
            'foo.bar.seven',
            'foo.barbaz.four',
            'foo.barnot.three',
            'something.else',
          ],
        ],
      ];

      $storage
        ->method('listAll')
        ->will($this->returnValueMap($map));
    }
    else {
      $storage = $this->getMockBuilder('Drupal\Core\Config\ExtensionInstallStorage')->disableOriginalConstructor()->getMock();
      $storage
        ->method('getComponentNames')
        ->willReturn([
          'foo.barbaz.four' => 'ignored',
        ]);

      $map = [
        ['foo.bar'], [],
        ['foo.barbaz', ['foo.barbaz.four']],
        ['', ['foo.barbaz.four']],
      ];
      $storage
        ->method('listAll')
        ->will($this->returnValueMap($map));
    }
    return $storage;

  }

  /**
   * @covers \Drupal\config_update\ConfigLister::listConfig
   * @dataProvider listConfigProvider
   */
  public function testListConfig($a, $b, $expected) {
    $this->assertEquals($expected, $this->configLister->listConfig($a, $b));
  }

  /**
   * Data provider for self:testListConfig().
   */
  public function listConfigProvider() {
    return [
      // Arguments are $list_type, $name.
      // We cannot really test the extension types here, because they rely
      // on the going out to the file system to find out what config objects
      // are there. This is too complex to mock. It is tested in the tests for
      // the report output in the config_update_ui module tests. Anyway, we
      // can test the other types.
      ['type', 'system.all',
        [
          [
            'foo.bar.one',
            'foo.bar.two',
            'foo.bar.three',
            'foo.barbaz.four',
            'foo.barbaz.five',
            'foo.barbaz.six',
            'something.else',
            'another.one',
          ],
          [
            'foo.bar.one',
            'foo.bar.two',
            'foo.bar.seven',
            'foo.barbaz.four',
            'foo.barnot.three',
            'something.else',
          ],
          ['foo.barbaz.four'],
        ],
      ],
      ['type', 'system.simple',
        [
          ['something.else', 'another.one'],
          ['foo.barnot.three', 'something.else'],
          [],
        ],
      ],
      ['type', 'foo',
        [
          ['foo.bar.one', 'foo.bar.two', 'foo.bar.three'],
          ['foo.bar.one', 'foo.bar.two', 'foo.bar.seven'],
          [],
        ],
      ],
      ['type', 'unknown.type', [[], [], []]],
    ];
  }

  /**
   * @covers \Drupal\config_update\ConfigLister::getType
   */
  public function testGetType() {
    $return = $this->configLister->getType('not_in_list');
    $this->assertNull($return);

    foreach ($this->entityDefinitionInformation as $info) {
      $return = $this->configLister->getType($info['type']);
      $this->assertEquals($return->getConfigPrefix(), $info['prefix']);
    }
  }

  /**
   * @covers \Drupal\config_update\ConfigLister::getTypeByPrefix
   */
  public function testGetTypeByPrefix() {
    $return = $this->configLister->getTypeByPrefix('not_in_list');
    $this->assertNull($return);

    foreach ($this->entityDefinitionInformation as $info) {
      $return = $this->configLister->getTypeByPrefix($info['prefix']);
      $this->assertEquals($return->getConfigPrefix(), $info['prefix']);
    }
  }

  /**
   * @covers \Drupal\config_update\ConfigLister::getTypeNameByConfigName
   */
  public function testGetTypeNameByConfigName() {
    $return = $this->configLister->getTypeNameByConfigName('not_in_list');
    $this->assertNull($return);

    foreach ($this->entityDefinitionInformation as $info) {
      $return = $this->configLister->getTypeNameByConfigName($info['prefix'] . '.something');
      $this->assertEquals($return, $info['type']);
    }
  }

}
