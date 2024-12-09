# Craft Tools

A collection of craft custom fields, twig extensions, and various helpers and tools used on one or more of our Craft sites.

Exists as a Yii `module`.

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
