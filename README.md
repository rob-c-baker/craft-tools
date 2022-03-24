# Craft Tools

A collection of craft custom fields, twig extensions, and various helpers and tools used on one or more of our Crafts sites.

Exists as a Yii `module`.

## Getting started

Add an `auth.json` file to allow composer to authenticate against our composer package repository file to the root of the parent project with contents like the following:

```json
{
  "gitlab-token": {
    "gitlab.alanrogers.com": "********************"
  }
}
```

`********************` Must be replaced with a [token from Gitlab](https://docs.gitlab.com/ee/user/profile/personal_access_tokens.html#creating-a-personal-access-token). It must have the `read_api` _(recommended)_ or `api` scope.

In the project's `composer.json` the following must be present:

```json
{
  "config": {
    "gitlab-domains": [
      "gitlab.alanrogers.com"
    ]
  }
}
```

Require this library with composer:

```shell
composer require alanrogers/craft-tools
```

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