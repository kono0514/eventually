<?php

declare(strict_types=1);

use Altek\Eventually\Tests\Models\User;
use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| User Factories
|--------------------------------------------------------------------------
|
*/

$factory->define(User::class, static function (Faker $faker) {
    return [
        'is_admin'   => $faker->boolean(),
        'first_name' => $faker->firstName,
        'last_name'  => $faker->lastName,
        'email'      => $faker->unique()->safeEmail,
    ];
});
