<?php

declare(strict_types=1);

use Altek\Eventually\Tests\Models\Award;
use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Award Factories
|--------------------------------------------------------------------------
|
*/

$factory->define(Award::class, static function (Faker $faker) {
    return [
        'name' => $faker->unique()->sentence(2),
    ];
});
