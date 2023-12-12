# Droopler profile for Drupal
<img src="https://droopler-demo.droptica.com/themes/custom/droopler_subtheme/logo.svg" width=300 alt="Droopler Logo" />

## What is Droopler?
Droopler is a Drupal 10 profile designed to build corporate websites. It's based on the latest frontend technologies, including Bootstrap 5. The maintainer of Droopler is [Droptica](https://www.droptica.com).

[![Build Status](https://github.com/droptica/droopler/workflows/Drupal%20coding%20standards/badge.svg?branch=master)](https://github.com/droptica/droopler/actions)

* **Official website**: [droptica.com/droopler](https://www.droptica.com/droopler)
* **Tutorials**: [droptica.com/droopler/tutorials](https://www.droptica.com/droopler/tutorials/)
* **Demo**: [droopler-demo.droptica.com](https://droopler-demo.droptica.com)
* **Composer template**: [github.com/droptica/droopler_project](https://github.com/droptica/droopler_project)
* **Drupal.org project**: [drupal.org/project/droopler](https://www.drupal.org/project/droopler)

For the latest news please subscribe to our [Facebook](https://www.facebook.com/Droopler/) and [Twitter](https://twitter.com/DrooplerCMS).

## What's in this repository?
This repository contains a Drupal profile. When you put it in the `/profiles/contrib/droopler` directory, the Drupal installer gets modified and installs base Droopler theme, some module dependencies, and demo content.

## Installation
The Droopler profile should be installed via Composer. We recommend using [Droopler skeleton repository](https://github.com/droptica/droopler_project). If you are starting from the scratch - in the **require** section of your composer.json put:

```json
"require": {
  "droptica/droopler": "^10.0.0"
}
```

And run **composer update**.

In case of unexpected problems please update your main composer.json to comply with the latest [Droopler skeleton repository](https://github.com/droptica/droopler_project). You may run into some issues with libraries and their directories.

# Documentation
* [Droopler Product](modules/custom/d_product/README.md) - This distribution provides Drupal Product functionality.
* [Droopler Blog](modules/custom/d_blog/README.md) - This distribution provides Drupal Blog functionality.
* [Updating Droopler](UPDATE.md) - A guide on updating the distribution between major versions.
* [Using d_settings](modules/custom/d_p/README.md) - How to create new paragraph settings and modify existing ones.
* [Using SCSS](https://github.com/droptica/droopler_project/blob/master/README.md) - How to handle SCSS using Node.
* [Creating CSS subtheme](themes/custom/droopler_theme/STARTERKIT_CSS/README.md) - How to create a simple subtheme with CSS inheritance.
* [Creating SCSS subtheme](themes/custom/droopler_theme/STARTERKIT_SCSS/README.md) - How to create a complex subtheme with SCSS variables.
