# d_p module for Droopler distribution #
***
## The ParagraphSetting plugin ##
The d_p module provides a new plugin type `ParagraphSetting`
which is used in the setting field widget on the paragraphs form.
You can review the plugins implemented by the
module under the namespace `Plugin\ParagraphSetting`.
The paragraph setting plugins are based on the Drupal Form API which is used
to render, process and validate the settings form elements.

### Plugin manager ###
The plugins are managed by the plugin manager
`d_p.paragraph_settings.plugin.manager`.
This manager is responsible for loading all the available plugins
into the widget,
as an array of the form elements.
The configuration form is built only once, then it is stored
in the cache. You need to clear the cache each
time you changed something in your plugin.

### Adding the custom plugin ###
In order to create a custom paragraph setting plugin, you need to put your
plugin file in the `your_module/src/Plugin/ParagraphSettings` directory
of your custom module.
The easiest way is to extend the `Drupal\d_p\ParagraphSettingPluginBase`
and give your own implementation of some methods ex.`formElement`.
All the methods are documented in the class and the interface.
If the type of your form element will be the `select` then
it is required to implement the `Drupal\d_p\ParagraphSettingSelectInterface`
as this interface is checked in the further processing of the select elements.

Each plugin needs to be described using `Drupal\d_p\Annotation\ParagraphSetting`
annotation. In addition to the `id`
which is always required to define a unique plugin,
there are the `label` and the `settings`
that should be defined. The `settings` array may
contain the `weight` and the `parent` keys
(at least those two are only used in the further processing).

By default, the annotation is used in the form element and to define
the relations between the elements:
* `label` - is used as `#title` and it is returned by the
* `ParagraphSettingPluginBase::label`
* `weight` - is used as `#weight` to defined the order of the elements
* and it is returned by the `ParagraphSettingPluginBase::getWeight`
* `parent` - is used to nest the element under the defined parent id

The example of the plugin annotation may look like this:
```php
/**
 * @ParagraphSetting(
 *   id = "example-plugin-id",
 *   label = @Translation("Example paragraph setting plugin"),
 *   settings = {
 *      "parent" = "example-parent-plugin-id",
 *      "weight" = -10,
 *   }
 * )
 */
```

### Alterations ###
#### Replacing the existing plugin ####
By default, the plugin manager sets the alter info id which is used to invoke
a specific hook to alter the plugins definitions. In order to alter the
ParagraphSetting plugin you will have to define hook:
`hook_paragraph_setting_info_alter`.

The example hook may look like this:
```php
function my_module_paragraph_setting_info_alter(array &$definitions) {
  $definitions['example_plugin_id'] = [
    'class' => 'Drupal\my_module\Plugin\ParagraphSetting\ExampleAltered',
  ];
}
```

#### Replace the setting form ####
The setting from containing all the form elements
defined by each `ParagraphSetting` plugin
is built by the plugin manager. These form elements may be
altered right before being stored in the cache.
In order to do that, you can use the `hook_d_settings_alter`
hook described in the `d_p.api.php`.

The example hook implementation may look like this:
```php
function my_module_d_settings_alter(array &$settings_form) {
  $settings_form['example_plugin_id']['#weight'] = 10;
}
```

### Altering the widget ###
You can alter the
`Drupal\d_p\Plugin\Field\FieldWidget\SettingsWidget`
(`field_d_p_set_settings`) widget
the same way you usually alter any other Drupal widget.
It is possible to define a new widget which extends the SettingsWidget and use
it instead. You can also use hooks to alter the widget forms:
* [hook_field_widget_form_alter](https://api.drupal.org/api/drupal/core%21modules%21field%21field.api.php/function/hook_field_widget_form_alter/8.2.x)
* [hook_field_widget_WIDGET_TYPE_form_alter](https://api.drupal.org/api/drupal/core%21modules%21field%21field.api.php/function/hook_field_widget_WIDGET_TYPE_form_alter/8.2.x)

**Note:** This method is not recommended, as long as there
is no other way to alter the form elements or validate them
using the alter methods described above.
