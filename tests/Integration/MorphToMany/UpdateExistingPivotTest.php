<?php

declare(strict_types=1);

namespace Altek\Eventually\Tests\Integration\MorphToMany;

use Altek\Eventually\Tests\EventuallyTestCase;
use Altek\Eventually\Tests\Models\Award;
use Altek\Eventually\Tests\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

class UpdateExistingPivotTest extends EventuallyTestCase
{
    /**
     * @test
     */
    public function itSuccessfullyRegistersEventListeners(): void
    {
        User::updatingExistingPivot(function ($user, $relation, $properties): void {
            $this->assertInstanceOf(User::class, $user);

            $this->assertSame('awards', $relation);

            $this->assertSame([
                [
                    'awardable_id'   => 1,
                    'awardable_type' => User::class,
                    'prize'          => 4096,
                    'award_id'       => 1,
                ],
                [
                    'awardable_id'   => 1,
                    'awardable_type' => User::class,
                    'prize'          => 4096,
                    'award_id'       => 2,
                ],
            ], $properties);
        });

        User::existingPivotUpdated(function ($user, $relation, $properties): void {
            $this->assertInstanceOf(User::class, $user);

            $this->assertSame('awards', $relation);

            $this->assertSame([
                [
                    'awardable_id'   => 1,
                    'awardable_type' => User::class,
                    'prize'          => 4096,
                    'award_id'       => 1,
                ],
                [
                    'awardable_id'   => 1,
                    'awardable_type' => User::class,
                    'prize'          => 4096,
                    'award_id'       => 2,
                ],
            ], $properties);
        });

        $user = factory(User::class)->create();

        $awards = factory(Award::class, 2)->create()->each(static function (Award $award) use ($user): void {
            $award->users()->attach($user);
        });

        $this->assertCount(2, $user->awards()->get());

        $this->assertSame(2, $user->awards()->updateExistingPivot($awards, [
            'prize' => 4096,
        ]));
    }

    /**
     * @test
     */
    public function itPreventsExistingPivotFromBeingUpdated(): void
    {
        User::updatingExistingPivot(static function () {
            return false;
        });

        $user = factory(User::class)->create();

        $awards = factory(Award::class, 2)->create()->each(static function (Award $award) use ($user): void {
            $award->users()->attach($user);
        });

        $this->assertCount(2, $user->awards()->get());

        $this->assertFalse($user->awards()->updateExistingPivot($awards, [
            'prize' => 256,
        ]));

        $this->assertCount(2, $user->awards()->get());
    }

    /**
     * @test
     * @dataProvider updateExistingPivotProvider
     *
     * @param int   $results
     * @param mixed $id
     * @param array $attributes
     * @param array $expectedPayload
     */
    public function itSuccessfullyUpdatesExistingPivot(int $results, $id, array $attributes, array $expectedPayload): void
    {
        $user = factory(User::class)->create();

        $awards = factory(Award::class, 2)->create()->each(static function (Award $award) use ($user): void {
            $award->users()->attach($user);
        });

        $this->assertCount(2, $user->awards()->get());

        Event::fake();

        switch ($id) {
            case Model::class:
                $id = $awards->first();
                break;

            case Collection::class:
                $id = $awards;
                break;
        }

        $this->assertSame($results, $user->awards()->updateExistingPivot($id, $attributes));

        Event::assertDispatched(\sprintf('eloquent.updatingExistingPivot: %s', User::class), function ($event, $payload, $halt) use ($expectedPayload) {
            $this->assertInstanceOf(User::class, $payload[0]);

            unset($payload[0]);

            $this->assertSame($expectedPayload, $payload);

            $this->assertTrue($halt);

            return true;
        });

        Event::assertDispatched(\sprintf('eloquent.existingPivotUpdated: %s', User::class), function ($event, $payload) use ($expectedPayload) {
            $this->assertInstanceOf(User::class, $payload[0]);

            unset($payload[0]);

            $this->assertSame($expectedPayload, $payload);

            return true;
        });
    }

    /**
     * @return array
     */
    public function updateExistingPivotProvider(): array
    {
        return [
            [
                // Results
                1,

                // Id
                1,

                // Attributes
                [
                    'prize' => 128,
                ],

                // Expected payload
                [
                    1 => 'awards',
                    2 => [
                        [
                            'awardable_id'   => 1,
                            'awardable_type' => User::class,
                            'prize'          => 128,
                            'award_id'       => 1,
                        ],
                    ],
                ],
            ],

            [
                // Results
                1,

                // Id
                [
                    2,
                ],

                // Attributes
                [
                    'prize' => 1024,
                ],

                // Expected payload
                [
                    1 => 'awards',
                    2 => [
                        [
                            'awardable_id'   => 1,
                            'awardable_type' => User::class,
                            'prize'          => 1024,
                            'award_id'       => 2,
                        ],
                    ],
                ],
            ],

            [
                // Results
                2,

                // Id
                [
                    2,
                    1,
                ],

                // Attributes
                [
                    'prize' => 32768,
                ],

                // Expected payload
                [
                    1 => 'awards',
                    2 => [
                        [
                            'awardable_id'   => 1,
                            'awardable_type' => User::class,
                            'prize'          => 32768,
                            'award_id'       => 2,
                        ],
                        [
                            'awardable_id'   => 1,
                            'awardable_type' => User::class,
                            'prize'          => 32768,
                            'award_id'       => 1,
                        ],
                    ],
                ],
            ],

            [
                // Results
                1,

                // Id
                Model::class,

                // Attributes
                [
                    'prize' => 16384,
                ],

                // Expected payload
                [
                    1 => 'awards',
                    2 => [
                        [
                            'awardable_id'   => 1,
                            'awardable_type' => User::class,
                            'prize'          => 16384,
                            'award_id'       => 1,
                        ],
                    ],
                ],
            ],
        ];
    }
}
