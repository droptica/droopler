# Droopler profile for Drupal 8 #
<img src="https://demo.droopler.com/themes/custom/droopler_subtheme/logo.svg" width=300 alt="Droopler Logo" />

## What is Droopler? ##
Droopler is a Drupal 8 profile designed to kickstart a new webpage in a few minutes. It's based on the latest frontend technologies, including Bootstrap 4. The maintainer of Droopler is [Droptica](https://droptica.com).

* **Official website**: [droopler.com](https://droopler.com)
* **Documentation**: [droopler.com/developers](https://droopler.com/developers)
* **Demo**: [demo.droopler.com](https://demo.droopler.com)
* **Composer template**: [github.com/droptica/droopler_project](https://github.com/droptica/droopler_project)
* **Drupal.org project**: [drupal.org/project/droopler](https://www.drupal.org/project/droopler)

For the latest news please subscribe to our [Facebook](https://www.facebook.com/Droopler/) and [Twitter](https://twitter.com/DrooplerCMS).

## What's in this repository? ##
This repository contains a Drupal profile. When you put it in your /profiles directory, the Drupal installer gets modified and installs base Droopler theme, some module dependencies, and demo content.

## Installation ##
The Droopler profile should be installed via Composer. We recommend using [Droopler skeleton repository](https://github.com/droptica/droopler_project). If you are starting from the scratch - in the **require** section of your composer.json put:

```json
"require": {
  "droptica/droopler": "^8.1.4"
}
```

And run **composer update**.

In case of unexpected problems please update your main composer.json to comply with the [Droopler skeleton repository](https://github.com/droptica/droopler_project). You may run into some issues with libraries and their directories.
