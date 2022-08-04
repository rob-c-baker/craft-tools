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

And this is needed in `composer.json` to refer to the package repository for the parent group (`web-dev` id: `3`):

```json
{
  "repositories": {
    "gitlab.alanrogers.com/3": {
      "type": "composer",
      "url": "https://gitlab.alanrogers.com/api/v4/group/3/-/packages/composer/packages.json"
    }
  }
}
```

Require this library with composer _(the latest tag from the main branch)_:

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

## Migrations

Migrations have their own track and therefor need to be manually invoked like this:

```shell
./craft migrate/up --track=craft-tools
```
