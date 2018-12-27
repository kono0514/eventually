# Events
As with regular Eloquent [events](https://laravel.com/docs/5.7/eloquent#events), this package allows you to hook into a pivot's lifecycle by subscribing and listening for specific events.

Event name              | Triggered by
------------------------|------------------------------------------
`toggling`              | `toggle()`
`toggled`               | `toggle()`
`syncing`               | `sync()`
`synced`                | `sync()`
`syncing`               | `sync()`
`updatingExistingPivot` | `updateExistingPivot()`
`existingPivotUpdated`  | `updateExistingPivot()`
`attaching`             | `attach()`, `toggle()`, `sync()`
`attached`              | `attach()`, `toggle()`, `sync()`
`detaching`             | `detach()`, `toggle()`, `sync()`
`detached`              | `detach()`, `toggle()`, `sync()`

> **CAVEAT:** The `sync()` and `toggle()` methods trigger multiple events, since they call `attach()` and `detach()` internally. Keep that in mind when defining [listeners](https://laravel.com/docs/5.7/events#defining-listeners) or [observers](https://laravel.com/docs/5.7/eloquent#observers), to avoid surprises.

## Event listeners
The package provides `static` methods to quickly register event listeners with the dispatcher.

These can be set in the model's `boot()` method.

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
    
        static::toggling(function ($model, $relation, $data) {
            // ...
        });
        
        static::toggled(function ($model, $relation, $data) {
            // ...
        });
        
        static::syncing(function ($model, $relation, $data) {
            // ...
        });
        
        static::synced(function ($model, $relation, $data) {
            // ...
        });
        
        static::updatingExistingPivot(function ($model, $relation, $data) {
            // ...
        });
        
        static::existingPivotUpdated(function ($model, $relation, $data) {
            // ...
        });
        
        static::attaching(function ($model, $relation, $data) {
            // ...
        });
        
        static::attached(function ($model, $relation, $data) {
            // ...
        });
        
        static::detaching(function ($model, $relation, $data) {
            // ...
        });
        
        static::detached(function ($model, $relation, $data) {
            // ...
        });
    }
}
```

> **TIP:** Other event handling alternatives are the `Event::listen()` method, creating a listener and registering it in the `EventServiceProvider` or defining an [Observer](https://laravel.com/docs/5.7/eloquent#observers).

## Halting event propagation
To cease event propagation, simply return `false` from the listener's `handle()` method.

# Examples
```php
// Prevent a relation from being toggled
static::toggling(function ($model, $relation, $data) {
    return false;
});

// Prevent a relation from being synced
static::syncing(function ($model, $relation, $data) {
    return false;
});

// Prevent a pivot from being updated
static::updatingExistingPivot(function ($model, $relation, $data) {
    return false;
});

// Prevent a relation from being attached
static::attaching(function ($model, $relation, $data) {
    return false;
});

// Prevent a relation from being detached
static::detaching(function ($model, $relation, $data) {
    return false;
});
```

> **TIP:** For more information on this subject, please refer to the [official documentation](https://laravel.com/docs/5.7/events).
