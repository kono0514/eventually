# Installation
To get started with Eventually, use [Composer](https://getcomposer.org/doc/00-intro.md) to include the package to your dependencies.

The following command will install the latest available version:

```sh
composer require altek/eventually
```

> **NOTICE:** This package supports [illuminate/database](https://packagist.org/packages/illuminate/database) from version **5.4** onward.

# Model setup
By using the `Altek\Eventually\Eventually` trait on an Eloquent model, all the pivot event functionality becomes available.

## Example
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use \Altek\Eventually\Eventually;

    // ...
}
```

From this point on, any associated `BelongsToMany` or `MorphToMany` relationship will fire the corresponding events when using `toggle()`, `sync()`, `updateExistingPivot()`, `attach()` or `detach()`.
