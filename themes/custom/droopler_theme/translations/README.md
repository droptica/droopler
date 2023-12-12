All the strings within the theme should have a representative translations in their respected `.po` file
Depending on your website languages you may create different files such as:

- `fr.po` for French translations
- `de.po` for Deutsch translations
- `nb.po` for Norwegian bokmål translations
- `fa.po` for Farsi translations
- etc

For the actual string, you define a `msgid` and for the translation a `msgstr`, for more info checkout the example `nb.po` file within the `translations` directory.
you may delete it if you don't need it.

To integrate the process within your CI/CD, or updating your translation you may use the following drush commands:

- `drush locale:check` that would check if there's any new strings added to be updated.
- `drush locale:update` that would import the `*.po` translation file.
