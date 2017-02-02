# drupal-copy-profile

[![Latest Stable Version](https://poser.pugx.org/digipolisgent/drupal-copy-profile/v/stable)](https://packagist.org/packages/digipolisgent/drupal-copy-profile)
[![Latest Unstable Version](https://poser.pugx.org/digipolisgent/drupal-copy-profile/v/unstable)](https://packagist.org/packages/digipolisgent/drupal-copy-profile)
[![Total Downloads](https://poser.pugx.org/digipolisgent/drupal-copy-profile/downloads)](https://packagist.org/packages/digipolisgent/drupal-copy-profile)
[![PHP 7 ready](http://php7ready.timesplinter.ch/digipolisgent/drupal-copy-profile/develop/badge.svg)](https://travis-ci.org/digipolisgent/drupal-copy-profile)
[![License](https://poser.pugx.org/digipolisgent/drupal-copy-profile/license)](https://packagist.org/packages/digipolisgent/drupal-copy-profile)

[![Build Status](https://travis-ci.org/digipolisgent/drupal-copy-profile.svg?branch=develop)](https://travis-ci.org/digipolisgent/drupal-copy-profile)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/a0ef4f50-4a6c-4db7-9e4b-03847fab3673/mini.png)](https://insight.sensiolabs.com/projects/a0ef4f50-4a6c-4db7-9e4b-03847fab3673)
[![Code Climate](https://codeclimate.com/github/digipolisgent/drupal-copy-profile/badges/gpa.svg)](https://codeclimate.com/github/digipolisgent/drupal-copy-profile)
[![Test Coverage](https://codeclimate.com/github/digipolisgent/drupal-copy-profile/badges/coverage.svg)](https://codeclimate.com/github/digipolisgent/drupal-copy-profile/coverage)
[![Issue Count](https://codeclimate.com/github/digipolisgent/drupal-copy-profile/badges/issue_count.svg)](https://codeclimate.com/github/digipolisgent/drupal-copy-profile)

Composer plugin to assist in installation profile development. This allows the install
profile to be used as if your project is like `drupal-composer/drupal-project`, with
your project being an install profile to test and being the root project.

## Credits

This project is basically a rip-off of `drupal-composer/drupal-scaffold`. Thanks
to the developers of that package for the inspiration.

## Usage

Run `composer require --dev digipolisgent/drupal-copy-profile:dev-master` in your
composer project before installing or updating `drupal/core`.

Once drupal-copy-profile is required by your project, it will automatically copy
the install profile in the project directory to the correct profiles directory in
an installed drupal site.

You can use this easily by requiring these packages in the `require-dev` section
of the composer.json.

* drupal/core
* composer/installers
* drupal-composer/drupal-scaffold

You'll also have to configure the drupal-scaffold paths as follows:

```
    "extra": {
        "installer-paths": {
            "web/core": ["type:drupal-core"],
            "web/libraries/{$name}": ["type:drupal-library"],
            "web/modules/contrib/{$name}": ["type:drupal-module"],
            "web/profiles/contrib/{$name}": ["type:drupal-profile"],
            "web/themes/contrib/{$name}": ["type:drupal-theme"],
            "drush/contrib/{$name}": ["type:drupal-drush"]
        },
    }
```

## Configuration

You can configure the plugin with providing some settings in the `extra` section
of your root `composer.json`.

```json
{
  "extra": {
    "drupal-copy-profile": {
      "excludes": [
        "scripts"
      ],
      "profile-name": "lightning",
      "web-root": "www",
      "omit-defaults": false
    }
  }
}
```

With the `drupal-copy-profile` option `excludes`, you can provide additional paths
that should not be copied from the root project. The plugin provides these default
includes:

```
web-root
vendor-path
.git
```

`web-root` is configurable through the options, but defaults to the location where
`drupal/core` is installed. `vendor-path` is taken from the composer config and
defaults to `vendor`

When setting `omit-defaults` to `true`, the default excludes will not be used; in
this instance, only those files explicitly listed in the `excludes` options will
be considered. If `omit-defaults` is `false` (the default), then any items listed
in `excludes` will be in addition to the usual defaults.

The `profile-name` option allows you to set the install profile name. It defaults
to the project name of the root package (the second part of the package name)

The `web-root` option allows you to set an alternative path to copy the profile to.
It defaults to the install location of `drupal/core`, which should be fine.

## Custom command

The plugin by default is executed on each composer install and update. If you want
to call it manually, you can add the command callback to the `scripts`-section of
your root `composer.json`, like this:

```json
{
  "scripts": {
    "drupal-copy-profile": "DigipolisGent\\DrupalCopyProfile\\Plugin::copyProfile"
  }
}
```

After that you can manually copy the profile according to your configuration by
using `composer drupal-scaffold`.
