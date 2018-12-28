# Installation
To get started with Eventually, use [Composer](https://getcomposer.org/doc/00-intro.md) to include the package to your dependencies.

The following command will install the latest available version:

```sh
composer require altek/eventually
```

> **NOTICE:** This package supports [illuminate/database](https://packagist.org/packages/illuminate/database) from version **5.4** onward.

# Model setup
By using the `Altek\Eventually\Eventually` trait on an Eloquent model, the new pivot events become available.

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

From this point on, any `BelongsToMany` or `MorphToMany` relation will fire the corresponding [events](events.md) when using the `toggle()`, `sync()`, `updateExistingPivot()`, `attach()` or `detach()` method.
