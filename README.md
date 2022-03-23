# Craft Tools

A collection of craft custom fields, twig extensions, and various helpers and tools used on one or more of our Crafts sites.

Designed to be included in parent projects as a git `submodule` within the `modules` directory.

## Getting started

Clone this repo as a git `submodule` of the parent project within it's own folder in the `modules` folder. i.e. `modules/tools`.

Add something like the following to the parent Craft project's `config/app.php` file:

```php
return [
    'modules' => [
        'tools' => alanrogers\tools\CraftTools::class 
    ],
    'bootstrap' => [
        'tools'
    ]
];
```

Add the namespace to `composer.json` for autoloading:

```json
{
  "autoload": {
    "psr-4": {
      "alanrogers\\tools": "modules/tools/src/"
    }
  }
}
```

Then: 

```shell
composer dumpautoload
```

Ensure you adjust CI jobs on the parent project to recursively fetch from git repositories:

```yaml
variables:
  GIT_SUBMODULE_STRATEGY: recursive
```