<?php

namespace Drupal\Tests\config_update\Unit;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\EventDispatcher\Event;

/**
 * Base class for unit testing in Config Update Manager.
 *
 * This class provides some mock classes for unit testing.
 */
abstract class ConfigUpdateUnitTestBase extends UnitTestCase {

  /**
   * The mocked entity definition information.
   *
   * They are not sorted, to test that the methods sort them. Also there are a
   * couple with prefixes that are subsets of each other.
   *
   * @var string[]
   *
   * @see ConfigUpdateUnitTestBase::getEntityManagerMock().
   */
  protected $entityDefinitionInformation = [
    ['prefix' => 'foo.bar', 'type' => 'foo'],
    ['prefix' => 'foo.barbaz', 'type' => 'bar'],
    ['prefix' => 'baz.foo', 'type' => 'baz'],
  ];

  /**
   * Creates a mock entity manager for the test.
   *
   * @see ConfigUpdateUnitTestBase::entityDefinitionInformation
   */
  protected function getEntityManagerMock() {
    $definitions = [];
    $map = [];
    foreach ($this->entityDefinitionInformation as $info) {
      $def = $this->getMockBuilder('Drupal\Core\Config\Entity\ConfigEntityTypeInterface')->getMock();
      $def
        ->expects($this->any())
        ->method('getConfigPrefix')
        ->willReturn($info['prefix']);
      $def
        ->expects($this->any())
        ->method('entityClassImplements')
        ->willReturn(TRUE);

      $def
        ->method('getKey')
        ->willReturn('id');

      $def->getConfigPrefix();

      $definitions[$info['type']] = $def;
      $map[] = [$info['type'], FALSE, $def];
      $map[] = [$info['type'], TRUE, $def];
    }

    // Add in a content entity definition, which shouldn't be recognized by the
    // config lister class.
    $def = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityTypeInterface')->getMock();
    $def
      ->expects($this->any())
      ->method('entityClassImplements')
      ->willReturn(FALSE);
    $definitions['content_entity'] = $def;

    $manager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManagerInterface')->getMock();
    $manager
      ->method('getDefinitions')
      ->willReturn($definitions);

    $manager
      ->method('getDefinition')
      ->will($this->returnValueMap($map));

    $manager
      ->method('getStorage')
      ->will($this->returnCallback([$this, 'mockGetStorage']));

    return $manager;
  }

  /**
   * Mocks the getStorage() method for the entity manager.
   */
  public function mockGetStorage($entity_type) {
    // Figure out the config prefix for this entity type.
    $prefix = '';
    foreach ($this->entityDefinitionInformation as $info) {
      if ($info['type'] == $entity_type) {
        $prefix = $info['prefix'];
      }
    }

    // This is used in ConfigReverter::import(). Although it is supposed to
    // be entity storage, we'll use our mock config object instead.
    return new MockConfig('', $prefix, $this);
  }

  /**
   * Array of active configuration information for mocking.
   *
   * Array structure: Each element is an array whose first element is a
   * provider name, and second is an array of config items it provides.
   *
   * @var array
   *
   * @see ConfigUpdateUnitTestBase::getConfigStorageMock()
   */
  protected $configStorageActiveInfo = [
    ['foo.bar', ['foo.bar.one', 'foo.bar.two', 'foo.bar.three']],
    ['foo.barbaz', ['foo.barbaz.four', 'foo.barbaz.five', 'foo.barbaz.six']],
    ['baz.foo', []],
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

  /**
   * Array of extension configuration information for mocking.
   *
   * Array structure: Each element is an array whose first element is a
   * provider name, and second is an array of config items it provides.
   *
   * @var array
   *
   * @see ConfigUpdateUnitTestBase::getConfigStorageMock()
   */
  protected $configStorageExtensionInfo = [
    ['foo.bar', ['foo.bar.one', 'foo.bar.two', 'foo.bar.seven']],
    ['baz.foo', []],
    // This next item is assumed to be element 2 of the array. If not, you
    // will need to change ConfigUpdateUnitTestBase::getConfigStorageMock().
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

  /**
   * Array of optional configuration information for mocking.
   *
   * Array structure: Each element is an array whose first element is a
   * provider name, and second is an array of config items it provides.
   *
   * @var array
   *
   * @see ConfigUpdateUnitTestBase::getConfigStorageMock()
   */
  protected $configStorageOptionalInfo = [
    ['foo.bar', []],
    ['foo.barbaz', ['foo.barbaz.four']],
    // This next item is assumed to be element 2 of the array. If not, you
    // will need to change ConfigUpdateUnitTestBase::getConfigStorageMock().
    ['', ['foo.barbaz.four']],
  ];

  /**
   * Creates a mock config storage object for the test.
   *
   * @param string $type
   *   Type of storage object to return: 'active', 'extension', or 'optional'.
   *   In active storage, the read() method is mocked to assume you are reading
   *   core.extension to get the profile name, so it returns that information.
   *   For extension and optional storage, the getComponentNames() method is
   *   mocked, and for all storages, the listAll() method is mocked.
   *
   * @see ConfigUpdateUnitTestBase::configStorageActiveInfo
   * @see ConfigUpdateUnitTestBase::configStorageExtensionInfo
   * @see ConfigUpdateUnitTestBase::configStorageOptionalInfo
   */
  protected function getConfigStorageMock($type) {
    if ($type == 'active') {
      $storage = $this->getMockBuilder('Drupal\Core\Config\StorageInterface')->getMock();

      // Various tests assume various values of configuration that need to be
      // read from active storage.
      $map = [
        ['core.extension', ['profile' => 'standard']],
        ['foo.bar.one', ['foo.bar.one' => 'active', 'id' => 'one']],
        ['missing', FALSE],
        ['in.extension',
          ['in.extension' => 'active', '_core' => 'core_for_in.extension'],
        ],
        ['in.both', ['in.both' => 'active']],
        ['in.optional', ['in.optional' => 'active']],
      ];
      $storage
        ->method('read')
        ->will($this->returnValueMap($map));

      $storage
        ->method('listAll')
        ->will($this->returnValueMap($this->configStorageActiveInfo));
    }
    elseif ($type == 'extension') {
      $storage = $this->getMockBuilder('Drupal\Core\Config\ExtensionInstallStorage')->disableOriginalConstructor()->getMock();

      $value = [];
      foreach ($this->configStorageExtensionInfo[2][1] as $item) {
        $value[$item] = 'ignored';
      }
      $storage
        ->method('getComponentNames')
        ->willReturn($value);

      $storage
        ->method('listAll')
        ->will($this->returnValueMap($this->configStorageExtensionInfo));
      $map = [
        ['in.extension', ['in.extension' => 'extension']],
        ['in.both', ['in.both' => 'extension']],
        ['in.optional', FALSE],
        ['foo.bar.one', ['foo.bar.one' => 'extension', 'id' => 'one']],
        ['another', ['another' => 'extension', 'id' => 'one']],
        ['missing2', FALSE],
      ];
      $storage
        ->method('read')
        ->will($this->returnValueMap($map));

    }
    else {
      $storage = $this->getMockBuilder('Drupal\Core\Config\ExtensionInstallStorage')->disableOriginalConstructor()->getMock();

      $value = [];
      foreach ($this->configStorageOptionalInfo[2][1] as $item) {
        $value[$item] = 'ignored';
      }
      $storage
        ->method('getComponentNames')
        ->willReturn($value);

      $storage
        ->method('listAll')
        ->will($this->returnValueMap($this->configStorageOptionalInfo));

      $map = [
        ['in.optional', ['in.optional' => 'optional']],
        ['in.both', ['in.both' => 'optional']],
        ['missing2', FALSE],
      ];
      $storage
        ->method('read')
        ->will($this->returnValueMap($map));
    }
    return $storage;

  }

  /**
   * Creates a mock module handler for the test.
   */
  protected function getModuleHandlerMock() {
    $manager = $this->getMockBuilder('Drupal\Core\Extension\ModuleHandlerInterface')->getMock();
    $manager->method('getModuleList')
      ->willReturn(['foo_module' => '', 'standard' => '']);

    return $manager;
  }

  /**
   * Creates a mock theme handler for the test.
   */
  protected function getThemeHandlerMock() {
    $manager = $this->getMockBuilder('Drupal\Core\Extension\ThemeHandlerInterface')->getMock();
    $manager->method('listInfo')
      ->willReturn(['foo_theme' => '']);
    return $manager;
  }

  /**
   * Creates a mock string translation class for the test.
   */
  protected function getTranslationMock() {
    $translation = $this->getMockBuilder('Drupal\Core\StringTranslation\TranslationInterface')->getMock();
    $translation
      ->method('translateString')
      ->will($this->returnCallback([$this, 'mockTranslate']));
    return $translation;
  }

  /**
   * Mocks the translateString() method for the string translation mock object.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $input
   *   Object to translate.
   *
   * @return string
   *   The untranslated string from $input.
   */
  public function mockTranslate(TranslatableMarkup $input) {
    return $input->getUntranslatedString();
  }

  /**
   * List of mock-dispatched events.
   *
   * Each element of the array is the call parameters to dispatchEvent() in
   * the mocked dispatch class: name and event instance.
   *
   * @var array
   *
   * @see ConfigUpdateUnitTestBase::getEventDispatcherMock()
   */
  protected $dispatchedEvents = [];

  /**
   * Mocks the event dispatcher service.
   *
   * Stores dispatched events in ConfigUpdateUnitTestBase::dispatchedEvents.
   */
  protected function getEventDispatcherMock() {
    $event = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')->getMock();
    $event
      ->method('dispatch')
      ->will($this->returnCallback([$this, 'mockDispatch']));

    return $event;
  }

  /**
   * Mocks event dispatch.
   *
   * For \Symfony\Component\EventDispatcher\EventDispatchInterface::dispatch().
   */
  public function mockDispatch($name, Event $event = NULL) {
    $this->dispatchedEvents[] = [$name, $event];
  }

  /**
   * Mock config storage for the mock config factory.
   *
   * This is actually managed by the MockConfig class in this file.
   *
   * @var array
   */
  protected $configStorage = [];

  /**
   * Gets the value of the mocked config storage.
   */
  public function getConfigStorage() {
    return $this->configStorage;
  }

  /**
   * Sets the value of the mocked config storage.
   */
  public function setConfigStorage($values) {
    $this->configStorage = $values;
  }

  /**
   * Creates a mock config factory class for the test.
   */
  protected function getConfigFactoryMock() {
    $config = $this->getMockBuilder('Drupal\Core\Config\ConfigFactoryInterface')->getMock();
    $config
      ->method('getEditable')
      ->will($this->returnCallback([$this, 'mockGetEditable']));

    return $config;
  }

  /**
   * Mocks the getEditable() method for the mock config factory.
   *
   * @param string $name
   *   Name of the config object to get an editable object for.
   *
   * @return MockConfig
   *   Editable mock config object.
   */
  public function mockGetEditable($name) {
    return new MockConfig($name, '', $this);
  }

}

/**
 * Mock class for mutable configuration, config entity, and entity storage.
 */
class MockConfig {

  /**
   * Name of the config.
   *
   * @var string
   */
  protected $name = '';

  /**
   * Prefix for the entity type being mocked, for entity storage mocking.
   *
   * @var string
   */
  protected $entityPrefix = '';

  /**
   * Test class this comes from.
   *
   * @var \Drupal\Tests\config_update\Unit\ConfigUpdateUnitTestBase
   */
  protected $test;

  /**
   * Current value of the configuration.
   *
   * @var array
   */
  protected $value = '';

  /**
   * Constructs a mock config object.
   *
   * @param string $name
   *   Name of the config that is being mocked. Can be blank.
   * @param string $entity_prefix
   *   Prefix for the entity type that is being mocked. Often blank.
   * @param \Drupal\Tests\config_update\Unit\ConfigUpdateUnitTestBase $test
   *   Test class this comes from.
   */
  public function __construct($name, $entity_prefix, ConfigUpdateUnitTestBase $test) {
    $this->name = $name;
    $this->entityPrefix = $entity_prefix;
    $this->test = $test;

    $storage = $test->getConfigStorage();
    if ($name && isset($storage[$name])) {
      $value = $storage[$name];
      $value['is_new'] = FALSE;
    }
    else {
      $value['is_new'] = TRUE;
    }
    $value['_core'] = 'core_for_' . $name;

    $this->value = $value;
  }

  /**
   * Gets a component of the configuration value.
   */
  public function get($key) {
    return isset($this->value[$key]) ? $this->value[$key] : NULL;
  }

  /**
   * Sets a component of the configuration value.
   */
  public function set($key, $value) {
    $this->value[$key] = $value;
    return $this;
  }

  /**
   * Sets the entire configuration value.
   */
  public function setData($value) {
    // Retain the _core key.
    $core = isset($this->value['_core']) ? $this->value['_core'] : '';
    $this->value = $value;
    if ($core) {
      $this->value['_core'] = $core;
    }
    return $this;
  }

  /**
   * Saves the configuration.
   */
  public function save() {
    $config = $this->test->getConfigStorage();
    $config[$this->name] = $this->value;
    $this->test->setConfigStorage($config);
    return $this;
  }

  /**
   * Deletes the configuration.
   */
  public function delete() {
    $config = $this->test->getConfigStorage();
    unset($config[$this->name]);
    $this->test->setConfigStorage($config);
    return $this;
  }

  /**
   * Mocks the createFromStorageRecord() method from entity storage.
   */
  public function createFromStorageRecord($values) {
    if (!$this->entityPrefix) {
      return NULL;
    }

    // This is supposed to return an entity, but the only method we need is
    // save(), so instead set up and return this object.
    $this->name = $this->entityPrefix . '.' . $values['id'];
    $this->value = $values;
    $this->value['_core'] = 'core_for_' . $this->name;
    return $this;
  }

  /**
   * Mocks the updateFromStorageRecord() method from entity storage.
   */
  public function updateFromStorageRecord($object, $values) {
    return $object->createFromStorageRecord($values);
  }

  /**
   * Mocks the load() method for entity storage.
   */
  public function load($id) {
    $full_name = $this->entityPrefix . '.' . $id;
    $configs = $this->test->getConfigStorage();
    if (isset($configs[$full_name])) {
      $this->value = $configs[$full_name];
      $this->name = $full_name;
      $this->value['_core'] = 'core_for_' . $full_name;
      return $this;
    }
    return NULL;
  }

}
