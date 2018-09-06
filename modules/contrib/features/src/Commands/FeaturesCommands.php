<?php

namespace Drupal\features\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Component\Diff\DiffFormatter;
use Drupal\config_update\ConfigDiffInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\features\Exception\DomainException;
use Drupal\features\Exception\InvalidArgumentException;
use Drupal\features\FeaturesAssignerInterface;
use Drupal\features\FeaturesGeneratorInterface;
use Drupal\features\FeaturesManagerInterface;
use Drupal\features\Plugin\FeaturesGeneration\FeaturesGenerationWrite;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Drush\Utils\StringUtils;

/**
 * Drush commands for Features.
 */
class FeaturesCommands extends DrushCommands {

  const OPTIONS =[
    'bundle' => NULL,
  ];

  const OPTIONS_ADD = self::OPTIONS;

  const OPTIONS_COMPONENTS = self::OPTIONS + [
    'exported' => NULL,
    'format' => 'table',
    'not-exported' => NULL,
  ];

  const OPTIONS_DIFF = self::OPTIONS + [
    'ctypes' => NULL,
    'lines' => NULL,
  ];

  const OPTIONS_EXPORT = self::OPTIONS + [
    'add-profile' => NULL,
  ];

  const OPTIONS_IMPORT = self::OPTIONS + [
    'force' => NULL,
  ];

  const OPTIONS_IMPORT_ALL = self::OPTIONS;

  const OPTIONS_LIST = self::OPTIONS + [
    'format' => 'table',
  ];

  const OPTIONS_STATUS = self::OPTIONS;

  /**
   * The features_assigner service.
   *
   * @var \Drupal\features\FeaturesAssignerInterface
   */
  protected $assigner;

  /**
   * The features.manager service.
   *
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $manager;

  /**
   * The features_generator service.
   *
   * @var \Drupal\features\FeaturesGeneratorInterface
   */
  protected $generator;

  /**
   * The config_update.config_diff service.
   *
   * @var \Drupal\config_update\ConfigDiffInterface
   */
  protected $configDiff;

  /**
   * The config.storage service.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * FeaturesCommands constructor.
   *
   * @param \Drupal\features\FeaturesAssignerInterface $assigner
   *   The features_assigner service.
   * @param \Drupal\features\FeaturesManagerInterface $manager
   *   The features.manager service.
   * @param \Drupal\features\FeaturesGeneratorInterface $generator
   *   The features_generator service.
   * @param \Drupal\config_update\ConfigDiffInterface $configDiff
   *   The config_update.config_diff service.
   * @param \Drupal\Core\Config\StorageInterface $configStorage
   *   The config.storage service.
   */
  public function __construct(
    FeaturesAssignerInterface $assigner,
    FeaturesManagerInterface $manager,
    FeaturesGeneratorInterface $generator,
    ConfigDiffInterface $configDiff,
    StorageInterface $configStorage
  ) {
    parent::__construct();
    $this->assigner = $assigner;
    $this->configDiff = $configDiff;
    $this->configStorage = $configStorage;
    $this->generator = $generator;
    $this->manager = $manager;
  }

  /**
   * Applies global options for Features drush commands, including the bundle.
   *
   * The option --name="bundle_name" sets the bundle namespace.
   *
   * @return \Drupal\features\FeaturesAssignerInterface
   *   The features.assigner with options applied.
   */
  protected function featuresOptions(array $options) {
    $bundleName = $this->getOption($options, 'bundle');
    if (!empty($bundleName)) {
      $bundle = $this->assigner->applyBundle($bundleName);
      if ($bundle->getMachineName() !== $bundleName) {
        $this->logger()->warning('Bundle {name} not found. Using default.', [
          'name' => $bundleName,
        ]);
      }
    }
    else {
      $this->assigner->assignConfigPackages();
    }
    return $this->assigner;
  }

  /**
   * Get the value of an option.
   *
   * @param array $options
   *   The options array.
   * @param string $name
   *   The option name.
   * @param mixed $default
   *   The default value of the option.
   *
   * @return mixed|null
   *   The option value, defaulting to NULL.
   */
  protected function getOption(array $options, $name, $default = NULL) {
    return isset($options[$name])
      ? $options[$name]
      : $default;
  }

  /**
   * Display current Features settings.
   *
   * @param string $keys
   *   A possibly empty, comma-separated, list of config information to display.
   *
   * @command features:status
   *
   * @option bundle Use a specific bundle namespace.
   *
   * @aliases fs,features-status
   */
  public function status($keys = NULL, array $options = self::OPTIONS_STATUS) {
    $this->featuresOptions($options);

    $currentBundle = $this->assigner->getBundle();
    $export_settings = $this->manager->getExportSettings();
    $methods = $this->assigner->getEnabledAssigners();
    $output = $this->output();
    if ($currentBundle->isDefault()) {
      $output->writeln(dt('Current bundle: none'));
    }
    else {
      $output->writeln(dt('Current bundle: @name (@machine_name)', [
        '@name' => $currentBundle->getName(),
        '@machine_name' => $currentBundle->getMachineName(),
      ]));
    }
    $output->writeln(dt('Export folder: @folder', [
      '@folder' => $export_settings['folder'],
    ]));
    $output
      ->writeln(dt('The following assignment methods are enabled:'));
    $output->writeln(dt('  @methods', [
      '@methods' => implode(', ', array_keys($methods)),
    ]));

    if (!empty($keys)) {
      $config = $this->manager->getConfigCollection();
      $keys = StringUtils::csvToArray($keys);
      $data = count($keys) > 1
        ? array_keys($config)
        : $config[$keys[0]];
      $output->writeln(print_r($data, TRUE));
    }
  }

  /**
   * Display a list of all generate-able existing features and packages.
   *
   * If a package name is provided as an argument, then all of the configuration
   * objects assigned to that package will be listed.
   *
   * @param string $package_name
   *   The package to list. Optional; if specified, lists all configuration
   *   objects assigned to that package. If no package is specified, lists all
   *   of the features.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields|bool
   *   The command output, or FALSE if a requested package was not found.
   *
   * @command features:list:packages
   *
   * @option bundle Use a specific bundle namespace.
   *
   * @usage drush features:list:packages
   *   Display a list of all existing features and packages available to be
   *   generated.
   * @usage drush features:list:packages 'example_article'
   *   Display a list of all configuration objects assigned to the
   *   'example_article' package.
   *
   * @field-labels
   *   config: Config
   *   name: Name
   *   machine_name: Machine name
   *   status: Status
   *   version: Version
   *   state: State
   *
   * @aliases fl,features-list-packages
   */
  public function listPackages($package_name = NULL, $options = self::OPTIONS_LIST) {
    $assigner = $this->featuresOptions($options);
    $current_bundle = $assigner->getBundle();
    $namespace = $current_bundle->isDefault() ? '' : $current_bundle->getMachineName();

    $manager = $this->manager;
    $packages = $manager->getPackages();

    $packages = $manager->filterPackages($packages, $namespace);
    $result = [];

    // If no package was specified, list all packages.
    if (empty($package_name)) {
      foreach ($packages as $package) {
        $overrides = $manager->detectOverrides($package);
        $state = $package->getState();
        if (!empty($overrides) && ($package->getStatus() != FeaturesManagerInterface::STATUS_NO_EXPORT)) {
          $state = FeaturesManagerInterface::STATE_OVERRIDDEN;
        }

        $packageState = ($state != FeaturesManagerInterface::STATE_DEFAULT)
          ? $manager->stateLabel($state)
          : '';

        $result[$package->getMachineName()] = [
          'name' => $package->getName(),
          'machine_name' => $package->getMachineName(),
          'status' => $manager->statusLabel($package->getStatus()),
          'version' => $package->getVersion(),
          'state' => $packageState,
        ];
      }
      return new RowsOfFields($result);
    }

    // A valid package was listed.
    $package = $this->manager->findPackage($package_name);

    // If no matching package found, return an error.
    if (empty($package)) {
      $this->logger()->warning(dt('Package "@package" not found.', [
        '@package' => $package_name,
      ]));
      return FALSE;
    }

    // This is a valid package, list its configuration.
    $config = array_map(function ($name) {
      return ['config' => $name];
    }, $package->getConfig());

    return new RowsOfFields($config);
  }

  /**
   * Import module config from all installed features.
   *
   * @command features:import:all
   *
   * @option bundle Use a specific bundle namespace.
   *
   * @usage drush features-import-all
   *   Import module config from all installed features.
   *
   * @aliases fra,fia,fim-all,features-import-all
   */
  public function importAll($options = self::OPTIONS_IMPORT_ALL) {
    $assigner = $this->featuresOptions($options);
    $currentBundle = $assigner->getBundle();
    $namespace = $currentBundle->isDefault() ? '' : $currentBundle->getMachineName();

    $manager = $this->manager;
    $packages = $manager->getPackages();
    $packages = $manager->filterPackages($packages, $namespace);
    $overridden = [];

    foreach ($packages as $package) {
      $overrides = $manager->detectOverrides($package);
      $missing = $manager->detectMissing($package);
      if ((!empty($missing) || !empty($overrides)) && ($package->getStatus() == FeaturesManagerInterface::STATUS_INSTALLED)) {
        $overridden[] = $package->getMachineName();
      }
    }

    if (!empty($overridden)) {
      $this->import($overridden);
    }
    else {
      $this->logger->info(dt('Current state already matches active config, aborting.'));
    }
  }

  /**
   * Export the configuration on your site into a custom module.
   *
   * @param array $packages
   *   A list of features to export.
   *
   * @command features:export
   *
   * @option add-profile Package features into an install profile.
   * @option bundle Use a specific bundle namespace.
   *
   * @usage drush features-export
   *   Export all available packages.
   * @usage drush features-export example_article example_page
   *   Export the example_article and example_page packages.
   * @usage drush features-export --add-profile
   *   Export all available packages and add them to an install profile.
   *
   * @aliases fex,fu,fua,fu-all,features-export
   *
   * @throws \Drupal\features\Exception\DomainException
   * @throws \Drupal\features\Exception\InvalidArgumentException
   * @throws \Drush\Exceptions\UserAbortException
   * @throws \Exception
   */
  public function export(array $packages, $options = self::OPTIONS_EXPORT) {
    $assigner = $this->featuresOptions($options);
    $manager = $this->manager;
    $generator = $this->generator;

    $current_bundle = $assigner->getBundle();

    if ($options['add-profile']) {
      if ($current_bundle->isDefault) {
        throw new InvalidArgumentException(dt("Must specify a profile name with --name"));
      }
      $current_bundle->setIsProfile(TRUE);
    }

    $all_packages = $manager->getPackages();
    foreach ($packages as $name) {
      if (!isset($all_packages[$name])) {
        throw new DomainException(dt("The package @name does not exist.", [
          '@name' => $name,
        ]));
      }
    }

    if (empty($packages)) {
      $packages = $all_packages;
      $dt_args = ['@modules' => implode(', ', array_keys($packages))];
      drush_print(dt('The following extensions will be exported: @modules',
        $dt_args));
      if (!$this->io()->confirm('Do you really want to continue?')) {
        throw new UserAbortException();
      }
    }
    else {
      $packages = array_combine($packages, $packages);
    }

    // If any packages exist, confirm before overwriting.
    if ($existing_packages = $manager->listPackageDirectories($packages,
      $current_bundle)) {
      foreach ($existing_packages as $name => $directory) {
        drush_print(dt("The extension @name already exists at @directory.",
          ['@name' => $name, '@directory' => $directory]));
      }
      // Apparently, format_plural is not always available.
      if (count($existing_packages) == 1) {
        $message = dt('Would you like to overwrite it?');
      }
      else {
        $message = dt('Would you like to overwrite them?');
      }
      if (!$this->io()->confirm($message)) {
        throw new UserAbortException();
      }
    }

    // Use the write generation method.
    $method_id = FeaturesGenerationWrite::METHOD_ID;
    $result = $generator->generatePackages($method_id, $current_bundle, array_keys($packages));

    foreach ($result as $message) {
      $method = $message['success'] ? 'success' : 'error';
      $this->logger()->$method(dt($message['message'], $message['variables']));
    }
  }

  /**
   * Add a config item to a feature package.
   *
   * @param array|null $components
   *   Patterns of config to add, see features:components for the format to use.
   *
   * @command features:add
   *
   * @todo @param $feature Feature package to export and add config to.
   *
   * @option bundle Use a specific bundle namespace.
   *
   * @aliases fa,fe,features-add
   *
   * @throws \Drush\Exceptions\UserAbortException
   * @throws \Exception
   */
  public function add($components = NULL, $options = self::OPTIONS_ADD) {
    if ($components) {
      $assigner = $this->featuresOptions($options);
      $manager = $this->manager;
      $generator = $this->generator;

      $current_bundle = $assigner->getBundle();

      $module = array_shift($args);
      if (empty($args)) {
        throw new \Exception('No components supplied.');
      }
      $components = $this->componentList();
      $options = [
        'exported' => FALSE,
      ];

      $filtered_components = $this->componentFilter($components, $args,
        $options);
      $items = $filtered_components['components'];

      if (empty($items)) {
        throw new \Exception('No components to add.');
      }

      $packages = [$module];
      // If any packages exist, confirm before overwriting.
      if ($existing_packages = $manager->listPackageDirectories($packages)) {
        foreach ($existing_packages as $name => $directory) {
          drush_print(dt("The extension @name already exists at @directory.",
            ['@name' => $name, '@directory' => $directory]));
        }
        // Apparently, format_plural is not always available.
        if (count($existing_packages) == 1) {
          $message = dt('Would you like to overwrite it?');
        }
        else {
          $message = dt('Would you like to overwrite them?');
        }
        if (!$this->io()->confirm($message)) {
          throw new UserAbortException();
        }
      }
      else {
        $package = $manager->initPackage($module, NULL, '', 'module',
          $current_bundle);
        list($full_name, $path) = $manager->getExportInfo($package,
          $current_bundle);
        drush_print(dt('Will create a new extension @name in @directory',
          ['@name' => $full_name, '@directory' => $path]));
        if (!$this->io()->confirm(dt('Do you really want to continue?'))) {
          throw new UserAbortException();
        }
      }

      $config = $this->buildConfig($items);

      $manager->assignConfigPackage($module, $config);

      // Use the write generation method.
      $method_id = FeaturesGenerationWrite::METHOD_ID;
      $result = $generator->generatePackages($method_id, $current_bundle,
        $packages);

      foreach ($result as $message) {
        $method = $message['success'] ? 'success' : 'error';
        $this->logger()->$method(dt($message['message'],
          $message['variables']));
      }
    }
    else {
      throw new \Exception('No feature name given.');
    }
  }

  /**
   * List features components.
   *
   * @param array $patterns
   *   The components types to list. Omit this argument to list them all.
   *
   * @command features:components
   *
   * @option exported Show only components that have been exported.
   * @option not-exported Show only components that have not been exported.
   * @option bundle Use a specific bundle namespace.
   *
   * @aliases fc,features-components
   *
   * @field-labels
   *  source: Available sources
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields|null
   *   The command output. May be empty.
   */
  public function components(array $patterns, $options = self::OPTIONS_COMPONENTS) {
    $args = $patterns;
    $this->featuresOptions($options);

    $components = $this->componentList();
    ksort($components);
    // If no args supplied, prompt with a list.
    if (empty($args)) {
      $types = array_keys($components);
      array_unshift($types, 'all');
      $choice = $this->io()
        ->choice('Enter a number to choose which component type to list.', $types);
      if ($choice === FALSE) {
        return NULL;
      }

      $args = ($choice == 0) ? ['*'] : [$types[$choice]];
    }
    $options = [
      'provided by' => TRUE,
    ];
    if ($options['exported']) {
      $options['not exported'] = FALSE;
    }
    elseif ($options['not-exported']) {
      $options['exported'] = FALSE;
    }

    $filtered_components = $this->componentFilter($components, $args, $options);
    if ($filtered_components) {
      return $this->componentPrint($filtered_components);
    }
  }

  /**
   * Show the difference between active|default config from a feature package.
   *
   * @param string $feature
   *   The feature in question.
   *
   * @command features:diff
   *
   * @option ctypes Comma-separated list of component types to limit the output
   *   to. Defaults to all types.
   * @option lines Generate diffs with <n> lines of context instead of the
   *   usual two.
   * @option bundle Use a specific bundle namespace.
   *
   * @aliases fd,features-diff
   *
   * @throws \Exception
   */
  public function diff($feature, $options = self::OPTIONS_DIFF) {
    $manager = $this->manager;
    $assigner = $this->featuresOptions($options);
    $assigner->assignConfigPackages();

    $module = $feature;

    // @FIXME Actually do something with the "ctypes" option.
    $filter_ctypes = $options['ctypes'];
    if ($filter_ctypes) {
      $filter_ctypes = explode(',', $filter_ctypes);
    }

    $feature = $manager->loadPackage($module, TRUE);
    if (empty($feature)) {
      throw new DomainException(dt('No such feature is available: @module', [
        '@module' => $module,
      ]));
    }

    $lines = $options['lines'];
    $lines = isset($lines) ? $lines : 2;

    $formatter = new DiffFormatter();
    $formatter->leading_context_lines = $lines;
    $formatter->trailing_context_lines = $lines;
    $formatter->show_header = FALSE;

    if (drush_get_context('DRUSH_NOCOLOR')) {
      $red = $green = "%s";
    }
    else {
      $red = "\033[31;40m\033[1m%s\033[0m";
      $green = "\033[0;32;40m\033[1m%s\033[0m";
    }

    $overrides = $manager->detectOverrides($feature);
    $missing = $manager->reorderMissing($manager->detectMissing($feature));
    $overrides = array_merge($overrides, $missing);

    $output = $this->output();

    if (empty($overrides)) {
      $output->writeln(dt('Active config matches stored config for @module.', [
        '@module' => $module,
      ]));
    }
    else {
      $config_diff = $this->configDiff;

      // Print key for colors.
      $output->writeln(dt('Legend: '));
      $output->writeln(sprintf($red,
        dt('Code:   drush features-import will replace the active config with the displayed code.')));
      $output->writeln(sprintf($green,
        dt('Active: drush features-export will update the exported feature with the displayed active config')));

      foreach ($overrides as $name) {
        $message = '';
        if (in_array($name, $missing)) {
          $extension = [];
          $message = sprintf($red, dt('(missing from active)'));
        }
        else {
          $active = $manager->getActiveStorage()->read($name);
          $extension = $manager->getExtensionStorages()->read($name);
          if (empty($extension)) {
            $extension = [];
            $message = sprintf($green, dt('(not exported)'));
          }
          $diff = $config_diff->diff($extension, $active);
          $rows = explode("\n", $formatter->format($diff));
        }

        $output->writeln('');
        $output->writeln(dt("Config @name @message", [
          '@name' => $name,
          '@message' => $message,
        ]));

        if (!empty($extension)) {
          foreach ($rows as $row) {
            if (strpos($row, '>') === 0) {
              $output->writeln(sprintf($green, $row));
            }
            elseif (strpos($row, '<') === 0) {
              $output->writeln(sprintf($red, $row));
            }
            else {
              $output->writeln($row);
            }
          }
        }
      }
    }
  }

  /**
   * Import a module config into your site.
   *
   * @param string $feature
   *   A comma-delimited list of features or feature:component pairs to import.
   *
   * @command features:import
   *
   * @option force Force import even if config is not overridden.
   * @option bundle Use a specific bundle namespace.
   *
   * @usage drush features-import foo:node.type.page
   *   foo:taxonomy.vocabulary.tags bar Import node and taxonomy config of
   *   feature "foo". Import all config of feature "bar".
   *
   * @aliases fim,fr,features-import
   *
   * @throws \Exception
   */
  public function import($feature, $options = self::OPTIONS_IMPORT) {
    $this->featuresOptions($options);

    $features = StringUtils::csvToArray($feature);
    if (empty($features)) {
      drush_invoke_process('@self', 'features:list:packages', [], $options);
      return;
    }

    // Determine if revert should be forced.
    $force = $this->getOption($options, 'force');

    // Determine if -y was supplied. If so, we can filter out needless output
    // from this command.
    $skip_confirmation = drush_get_context('DRUSH_AFFIRMATIVE');
    $manager = $this->manager;

    // Parse list of arguments.
    $modules = [];
    foreach ($features as $featureString) {
      list($module, $component) = explode(':', $featureString);

      // We cannot use just a component name without its module.
      if (empty($module)) {
        continue;
      }

      // We received just a feature name, meaning we need all of its components.
      if (empty($component)) {
        $modules[$module] = TRUE;
        continue;
      }

      if (empty($modules[$module])) {
        $modules[$module] = [];
      }

      if ($modules[$module] !== TRUE) {
        $modules[$module][] = $component;
      }
    }

    // Process modules.
    foreach ($modules as $module => $componentsNeeded) {
      // Reset the arguments on each loop pass.
      $dt_args = ['@module' => $module];

      /** @var \Drupal\features\Package $feature */
      $feature = $manager->loadPackage($module, TRUE);
      if (empty($feature)) {
        throw new DomainException(dt('No such feature is available: @module', $dt_args));
      }

      if ($feature->getStatus() != FeaturesManagerInterface::STATUS_INSTALLED) {
        throw new DomainException(dt('No such feature is installed: @module', $dt_args));
      }

      // Forcefully revert all components of a feature.
      if ($force) {
        $components = $feature->getConfigOrig();
      }
      // Only revert components that are detected to be Overridden.
      else {
        $overrides = $manager->detectOverrides($feature);
        $missing = $manager->reorderMissing($manager->detectMissing($feature));

        // Be sure to import missing components first.
        $components = array_merge($missing, $overrides);
      }

      if (!empty($componentsNeeded) && is_array($componentsNeeded)) {
        $components = array_intersect($components, $componentsNeeded);
      }

      if (empty($components)) {
        $this->logger()->info(dt('Current state already matches active config, aborting.'));
        continue;
      }

      // Determine which config the user wants to import/revert.
      $configToCreate = [];
      foreach ($components as $component) {
        $dt_args['@component'] = $component;
        $confirmation_message = 'Do you really want to import @module : @component?';
        if ($skip_confirmation || $this->io()->confirm(dt($confirmation_message, $dt_args))) {
          $configToCreate[$component] = '';
        }
      }

      // Perform the import/revert.
      $importedConfig = $manager->createConfiguration($configToCreate);

      // List the results.
      foreach ($components as $component) {
        $dt_args['@component'] = $component;
        if (isset($importedConfig['new'][$component])) {
          $this->logger()->info(dt('Imported @module : @component.', $dt_args));
        }
        elseif (isset($importedConfig['updated'][$component])) {
          $this->logger()->info(dt('Reverted @module : @component.', $dt_args));
        }
        elseif (!isset($configToCreate[$component])) {
          $this->logger()->info(dt('Skipping @module : @component.', $dt_args));
        }
        else {
          $this->logger()->error(dt('Error importing @module : @component.', $dt_args));
        }
      }
    }
  }

  /**
   * Returns an array of full config names given a array[$type][$component].
   *
   * @param array $items
   *   The items to return data for.
   *
   * @return array
   *   An array of config items.
   */
  protected function buildConfig(array $items) {
    $result = [];
    foreach ($items as $config_type => $item) {
      foreach ($item as $item_name => $title) {
        $result[] = $this->manager->getFullName($config_type, $item_name);
      }
    }
    return $result;
  }

  /**
   * Returns a listing of all known components, indexed by source.
   */
  protected function componentList() {
    $result = [];
    $config = $this->manager->getConfigCollection();
    foreach ($config as $item) {
      $result[$item->getType()][$item->getShortName()] = $item->getLabel();
    }
    return $result;
  }

  /**
   * Filters components by patterns.
   */
  protected function componentFilter($all_components, $patterns = [], $options = []) {
    $options += [
      'exported' => TRUE,
      'not exported' => TRUE,
      'provided by' => FALSE,
    ];
    $pool = [];
    // Maps exported components to feature modules.
    $components_map = $this->componentMap();
    // First filter on exported state.
    foreach ($all_components as $source => $components) {
      foreach ($components as $name => $title) {
        $exported = count($components_map[$source][$name]) > 0;
        if ($exported) {
          if ($options['exported']) {
            $pool[$source][$name] = $title;
          }
        }
        else {
          if ($options['not exported']) {
            $pool[$source][$name] = $title;
          }
        }
      }
    }

    $state_string = '';

    if (!$options['exported']) {
      $state_string = 'unexported';
    }
    elseif (!$options['not exported']) {
      $state_string = 'exported';
    }

    $selected = [];
    foreach ($patterns as $pattern) {
      // Rewrite * to %. Let users use both as wildcard.
      $pattern = strtr($pattern, ['*' => '%']);
      $sources = [];
      list($source_pattern, $component_pattern) = explode(':', $pattern, 2);
      // If source is empty, use a pattern.
      if ($source_pattern == '') {
        $source_pattern = '%';
      }
      if ($component_pattern == '') {
        $component_pattern = '%';
      }

      $preg_source_pattern = strtr(preg_quote($source_pattern, '/'),
        ['%' => '.*']);
      $preg_component_pattern = strtr(preg_quote($component_pattern, '/'),
        ['%' => '.*']);
      // If it isn't a pattern, but a simple string, we don't anchor the
      // pattern. This allows for abbreviating. Otherwise, we do, as this seems
      // more natural for patterns.
      if (strpos($source_pattern, '%') !== FALSE) {
        $preg_source_pattern = '^' . $preg_source_pattern . '$';
      }
      if (strpos($component_pattern, '%') !== FALSE) {
        $preg_component_pattern = '^' . $preg_component_pattern . '$';
      }
      $matches = [];

      // Find the sources.
      $all_sources = array_keys($pool);
      $matches = preg_grep('/' . $preg_source_pattern . '/', $all_sources);
      if (count($matches) > 0) {
        // If we have multiple matches and the source string wasn't a
        // pattern, check if one of the matches is equal to the pattern, and
        // use that, or error out.
        if (count($matches) > 1 and $preg_source_pattern[0] != '^') {
          if (in_array($source_pattern, $matches)) {
            $matches = [$source_pattern];
          }
          else {
            throw new \Exception(dt('Ambiguous source "@source", matches @matches',
              [
                '@source' => $source_pattern,
                '@matches' => implode(', ', $matches),
              ]));
          }
        }
        // Loose the indexes preg_grep preserved.
        $sources = array_values($matches);
      }
      else {
        throw new \Exception(dt('No @state sources match "@source"',
          ['@state' => $state_string, '@source' => $source_pattern]));
      }

      // Now find the components.
      foreach ($sources as $source) {
        // Find the components.
        $all_components = array_keys($pool[$source]);
        // See if there's any matches.
        $matches = preg_grep('/' . $preg_component_pattern . '/',
          $all_components);
        if (count($matches) > 0) {
          // If we have multiple matches and the components string wasn't a
          // pattern, check if one of the matches is equal to the pattern, and
          // use that, or error out.
          if (count($matches) > 1 and $preg_component_pattern[0] != '^') {
            if (in_array($component_pattern, $matches)) {
              $matches = [$component_pattern];
            }
            else {
              throw new \Exception(dt('Ambiguous component "@component", matches @matches',
                [
                  '@component' => $component_pattern,
                  '@matches' => implode(', ', $matches),
                ]));
            }
          }
          if (!is_array($selected[$source])) {
            $selected[$source] = [];
          }
          $selected[$source] += array_intersect_key($pool[$source],
            array_flip($matches));
        }
        else {
          // No matches. If the source was a pattern, just carry on, else
          // error out. Allows for patterns like ":*field*".
          if ($preg_source_pattern[0] != '^') {
            throw new \Exception(dt('No @state @source components match "@component"',
              [
                '@state' => $state_string,
                '@component' => $component_pattern,
                '@source' => $source,
              ]));
          }
        }
      }
    }

    // Lastly, provide feature module information on the selected components, if
    // requested.
    $provided_by = [];
    if ($options['provided by'] && $options['exported']) {
      foreach ($selected as $source => $components) {
        foreach ($components as $name => $title) {
          $exported = count($components_map[$source][$name]) > 0;
          if ($exported) {
            $provided_by[$source . ':' . $name] = implode(', ',
              $components_map[$source][$name]);
          }
        }
      }
    }

    return [
      'components' => $selected,
      'sources' => $provided_by,
    ];
  }

  /**
   * Provides a component to feature map (port of features_get_component_map).
   */
  protected function componentMap() {
    $result = [];
    $manager = $this->manager;
    // Recalc full config list without running assignments.
    $config = $manager->getConfigCollection();
    $packages = $manager->getPackages();

    foreach ($config as $item) {
      $type = $item->getType();
      $short_name = $item->getShortName();
      if (!isset($result[$type][$short_name])) {
        $result[$type][$short_name] = [];
      }
      if (!empty($item->getPackage())) {
        $package = $packages[$item->getPackage()];
        $result[$type][$short_name][] = $package->getMachineName();
      }
    }

    return $result;
  }

  /**
   * Prints a list of filtered components.
   */
  protected function componentPrint($filtered_components) {
    $rows = [];
    foreach ($filtered_components['components'] as $source => $components) {
      foreach ($components as $name => $value) {
        $row = ['source' => $source . ':' . $name];
        if (isset($filtered_components['sources'][$source . ':' . $name])) {
          $row['source'] = dt('Provided by') . ': ' . $filtered_components['sources'][$source . ':' . $name];
        }
        $rows[] = $row;
      }
    }

    return new RowsOfFields($rows);
  }

}
