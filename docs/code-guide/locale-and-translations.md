

Example pluzalization array:

```php
'#_users' => [
    '_' => '%s users',
    0   => 'no users',
    1   => '1 user',
]
```

Output:

```php
> $translator->translate('main.#_users', 5);
= "5 users"

> $translator->translate('main.#_users', '0');
= "no users"

> $translator->translate('main.#_users', '1');
= "1 user"
```

