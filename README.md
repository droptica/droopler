# Droopler profile for Drupal 8 #
[![N|Solid](https://www.droopler.pl/sites/default/files/logo_droopler.jpg)](http://droopler.pl)

## What is Droopler? ##
Droopler is a Drupal 8 profile designed to kickstart a new webpage in a few minutes. It's based on the latest frontend technologies, including Bootstrap 4. The maintainer of Droopler is [Droptica](https://droptica.com).

## What is this Droopler profile? ##
This is Drupal installation profile, which can be chosen in Drupal installer (the install.php script). It contains base Droopler theme, some module dependencies and sample content.

## Installation ##
The Droopler profile should be installed via Composer. We recommend using [Droopler skeleton repository](https://github.com/droptica/droopler_project). If you are starting from the scratch - in the **require** section of your composer.json put:

```json
"require": {
  "droptica/droopler": "^8.1.0-alpha1"
}
```

Also add a new repository to **repositories** section:
```json
"repositories": {
  "droptica-droopler": {
    "type": "git",
    "url":  "git@github.com:droptica/droopler.git"
  }
}
```

And run **composer update**.

In case of unexpected problem please update your main composer.json to comply with the [Droopler skeleton repository](https://github.com/droptica/droopler_project).
