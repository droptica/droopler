<?php

namespace Drupal\Tests\config_update\Unit;

/**
 * Tests the \Drupal\config_update\ConfigListerWithProviders class.
 *
 * The methods from \Drupal\config_update\ConfigLister are also tested.
 *
 * @group config_update
 *
 * @coversDefaultClass \Drupal\config_update\ConfigListerWithProviders
 */
class ConfigListerTest extends ConfigUpdateUnitTestBase {

  /**
   * The config lister to test.
   *
   * @var \Drupal\config_update\ConfigListerWithProviders
   */
  protected $configLister;

  /**
   * List of configuration by provider in the mocks.
   *
   * This is an array whose keys are provider names, and whose values are
   * each an array containing the provider type, the name of one config item
   * mocked to be in config/install, and one in config/optional.
   *
   * @var array
   */
  protected $configProviderList = [
    'foo_module' => ['module', 'foo.barbaz.one', 'foo.barbaz.two'],
    'foo_theme' => ['theme', 'foo.bar.one', 'foo.bar.two'],
    'standard' => ['profile', 'baz.bar.one', 'baz.bar.two'],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $lister = $this->getMockBuilder('Drupal\config_update\ConfigListerWithProviders')
      ->setConstructorArgs([
        $this->getEntityManagerMock(),
        $this->getConfigStorageMock('active'),
        $this->getConfigStorageMock('extension'),
        $this->getConfigStorageMock('optional'),
        $this->getModuleHandlerMock(),
        $this->getThemeHandlerMock(),
      ])
      ->setMethods(['listProvidedItems', 'getProfileName'])
      ->getMock();

    $lister->method('getProfileName')
      ->willReturn('standard');

    $map = [];
    foreach ($this->configProviderList as $provider => $info) {
      // Info has: [type, install storage item, optional storage item].
      // Map needs: [type, provider name, isOptional, [config items]].
      $map[] = [$info[0], $provider, FALSE, [$info[1]]];
      $map[] = [$info[0], $provider, TRUE, [$info[2]]];
    }
    $lister->method('listProvidedItems')
      ->will($this->returnValueMap($map));

    $this->configLister = $lister;
  }

  /**
   * @covers \Drupal\config_update\ConfigListerWithProviders::listConfig
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
      // Arguments are $list_type, $name, and return value is that list of
      // configuration in active, extension, and optional storage.
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
      ['profile', 'dummy',
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
          ['baz.bar.one'],
          ['baz.bar.two'],
        ],
      ],
      ['module', 'foo_module',
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
          ['foo.barbaz.one'],
          ['foo.barbaz.two'],
        ],
      ],
      ['theme', 'foo_theme',
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
          ['foo.bar.one'],
          ['foo.bar.two'],
        ],
      ],
    ];
  }

  /**
   * @covers \Drupal\config_update\ConfigListerWithProviders::getType
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
   * @covers \Drupal\config_update\ConfigListerWithProviders::getTypeByPrefix
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
   * @covers \Drupal\config_update\ConfigListerWithProviders::getTypeNameByConfigName
   */
  public function testGetTypeNameByConfigName() {
    $return = $this->configLister->getTypeNameByConfigName('not_in_list');
    $this->assertNull($return);

    foreach ($this->entityDefinitionInformation as $info) {
      $return = $this->configLister->getTypeNameByConfigName($info['prefix'] . '.something');
      $this->assertEquals($return, $info['type']);
    }
  }

  /**
   * @covers \Drupal\config_update\ConfigListerWithProviders::listTypes
   */
  public function testListTypes() {
    $return = $this->configLister->listTypes();
    // Should return an array in sorted order, of just the config entities
    // that $this->getEntityManagerMock() set up.
    $expected = ['bar' => 'foo.barbaz', 'baz' => 'baz.foo', 'foo' => 'foo.bar'];
    $this->assertEquals(array_keys($return), array_keys($expected));
    foreach ($return as $key => $definition) {
      $this->assertTrue($definition->entityClassImplements('Drupal\Core\Config\Entity\ConfigEntityInterface'));
      $this->assertEquals($definition->getConfigPrefix(), $expected[$key]);
    }
  }

  /**
   * @covers \Drupal\config_update\ConfigListerWithProviders::listProviders
   */
  public function testListProviders() {
    // This method's return value is not sorted in any particular way.
    $return = $this->configLister->listProviders();
    $expected = [];
    foreach ($this->configProviderList as $provider => $info) {
      // Info has: [type, install storage item, optional storage item].
      // Expected needs: key is item name, value is [type, provider name].
      $expected[$info[1]] = [$info[0], $provider];
      $expected[$info[2]] = [$info[0], $provider];
    }
    ksort($return);
    ksort($expected);
    $this->assertEquals($return, $expected);
  }

  /**
   * @covers \Drupal\config_update\ConfigListerWithProviders::getConfigProvider
   * @dataProvider getConfigProviderProvider
   */
  public function testGetConfigProvider($a, $expected) {
    $this->assertEquals($expected, $this->configLister->getConfigProvider($a));
  }

  /**
   * Data provider for self:testGetConfigProvider().
   */
  public function getConfigProviderProvider() {
    $values = [];
    foreach ($this->configProviderList as $provider => $info) {
      // Info has: [type, install storage item, optional storage item].
      // Values needs: [item, [type, provider name]].
      $values[] = [$info[1], [$info[0], $provider]];
      $values[] = [$info[2], [$info[0], $provider]];
    }
    $values[] = ['not.a.config.item', NULL];
    return $values;
  }

  /**
   * @covers \Drupal\config_update\ConfigListerWithProviders::providerHasConfig
   * @dataProvider providerHasConfigProvider
   */
  public function testProviderHasConfig($a, $b, $expected) {
    $this->assertEquals($expected, $this->configLister->providerHasConfig($a, $b));
  }

  /**
   * Data provider for self:testProviderHasConfig().
   */
  public function providerHasConfigProvider() {
    $values = [];
    foreach ($this->configProviderList as $provider => $info) {
      // Info has: [type, install storage item, optional storage item].
      // Values needs: [type, provider name, TRUE] for valid providers,
      // change the last to FALSE for invalid providers.
      $values[] = [$info[0], $provider, TRUE];
      $values[] = [$info[0], $provider . '_suffix', FALSE];
    }
    $values[] = ['invalid_type', 'foo_module', FALSE];
    return $values;
  }

}
