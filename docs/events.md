# Events
As with regular Eloquent [events](https://laravel.com/docs/eloquent#events), this package allows you to hook into a pivot's lifecycle by subscribing and listening for specific events.

Event name              | Triggered by
------------------------|------------------------------------------
`toggling`              | `toggle()`
`toggled`               | `toggle()`
`syncing`               | `sync()`
`synced`                | `sync()`
`updatingExistingPivot` | `updateExistingPivot()`, `sync()`
`existingPivotUpdated`  | `updateExistingPivot()`, `sync()`
`attaching`             | `attach()`, `toggle()`, `sync()`
`attached`              | `attach()`, `toggle()`, `sync()`
`detaching`             | `detach()`, `toggle()`, `sync()`
`detached`              | `detach()`, `toggle()`, `sync()`

::: danger CAVEAT
The `sync()` and `toggle()` methods fire multiple events, since they call `attach()`, `detach()` and `updateExistingPivot()` internally. Keep that in mind when defining [listeners](https://laravel.com/docs/events#defining-listeners) or [observers](https://laravel.com/docs/eloquent#observers), to avoid surprises.
:::

## Event listeners
The package comes with `static` methods to quickly register event listeners with the dispatcher.

These can be set in a model's `boot()` method.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use \Altek\Eventually\Eventually;

    protected static function boot(): void
    {
        parent::boot();
    
        static::toggling(function ($model, $relation, $properties) {
            // ...
        });
        
        static::toggled(function ($model, $relation, $properties) {
            // ...
        });
        
        static::syncing(function ($model, $relation, $properties) {
            // ...
        });
        
        static::synced(function ($model, $relation, $properties) {
            // ...
        });
        
        static::updatingExistingPivot(function ($model, $relation, $properties) {
            // ...
        });
        
        static::existingPivotUpdated(function ($model, $relation, $properties) {
            // ...
        });
        
        static::attaching(function ($model, $relation, $properties) {
            // ...
        });
        
        static::attached(function ($model, $relation, $properties) {
            // ...
        });
        
        static::detaching(function ($model, $relation, $properties) {
            // ...
        });
        
        static::detached(function ($model, $relation, $properties) {
            // ...
        });
    }
}
```

Alternatively, the same can be achieved with the `Event::listen()` method, an [Observer](https://laravel.com/docs/eloquent#observers) or an [Event Listener](https://laravel.com/docs/events#defining-listeners).

## Halting event propagation
To cease the event propagation, simply return `false` from the listener's handling method/function.

As a side effect, the pivot operation will be aborted when doing so from the `toggling`, `syncing`, `updatingExistingPivot`, `attaching` or `detaching` events.

# Examples
```php
// Stop event propagation and abort the toggle operation as a consequence
static::toggling(function ($model, $relation, $properties) {
    return false;
});

// Stop event propagation and abort the sync operation as a consequence
static::syncing(function ($model, $relation, $properties) {
    return false;
});

// Stop event propagation and abort the updateExistingPivot operation as a consequence
static::updatingExistingPivot(function ($model, $relation, $properties) {
    return false;
});

// Stop event propagation and abort the attach operation as a consequence
static::attaching(function ($model, $relation, $properties) {
    return false;
});

// Stop event propagation and abort the detach operation as a consequence
static::detaching(function ($model, $relation, $properties) {
    return false;
});
```

::: tip
For more information on this subject, please refer to the [official documentation](https://laravel.com/docs/events).
:::
