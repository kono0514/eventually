<?php

declare(strict_types=1);

namespace Altek\Eventually\Tests\Integration\MorphToMany;

use Altek\Eventually\Tests\EventuallyTestCase;
use Altek\Eventually\Tests\Models\Award;
use Altek\Eventually\Tests\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Event;

class SyncTest extends EventuallyTestCase
{
    /**
     * @test
     */
    public function itSuccessfullyRegistersEventListeners(): void
    {
        User::syncing(function ($user, $relation, $properties) {
            $this->assertInstanceOf(User::class, $user);

            $this->assertSame('awards', $relation);

            $this->assertSame([
                [
                    'awardable_id'   => 1,
                    'awardable_type' => User::class,
                    'award_id'       => 1,
                ],
            ], $properties);
        });

        User::synced(function ($user, $relation, $properties) {
            $this->assertInstanceOf(User::class, $user);

            $this->assertSame('awards', $relation);

            $this->assertSame([
                [
                    'awardable_id'   => 1,
                    'awardable_type' => User::class,
                    'award_id'       => 1,
                ],
            ], $properties);
        });

        $user  = factory(User::class)->create();
        $award = factory(Award::class)->create();

        $this->assertCount(0, $user->awards()->get());

        $this->assertSame([
            'attached' => [
                1,
            ],
            'detached' => [],
            'updated'  => [],
        ], $user->awards()->sync($award));

        $this->assertCount(1, $user->awards()->get());
    }

    /**
     * @test
     */
    public function itPreventsModelsFromBeingSynced(): void
    {
        User::syncing(function () {
            return false;
        });

        $user   = factory(User::class)->create();
        $awards = factory(Award::class, 2)->create();

        $this->assertCount(0, $user->awards()->get());

        $this->assertFalse($user->awards()->sync($awards));

        $this->assertCount(0, $user->awards()->get());
    }

    /**
     * @test
     * @dataProvider syncProvider
     *
     * @param array $results
     * @param mixed $id
     * @param array $attributes
     * @param array $expectedPayload
     */
    public function itSuccessfullySyncsModels(array $results, $id, array $attributes, array $expectedPayload): void
    {
        $user   = factory(User::class)->create();
        $awards = factory(Award::class, 2)->create();

        $this->assertCount(0, $user->awards()->get());

        Event::fake();

        switch ($id) {
            case Model::class:
                $id = $awards->first();
                break;

            case Collection::class:
                $id = $awards;
                break;
        }

        $this->assertSame($results, $user->awards()->sync($id, $attributes));

        Event::assertDispatched(\sprintf('eloquent.syncing: %s', User::class), function ($event, $payload, $halt) use ($expectedPayload) {
            $this->assertInstanceOf(User::class, $payload[0]);

            unset($payload[0]);

            $this->assertSame($expectedPayload, $payload);

            $this->assertTrue($halt);

            return true;
        });

        Event::assertDispatched(\sprintf('eloquent.synced: %s', User::class), function ($event, $payload) use ($expectedPayload) {
            $this->assertInstanceOf(User::class, $payload[0]);

            unset($payload[0]);

            $this->assertSame($expectedPayload, $payload);

            return true;
        });
    }

    /**
     * @return array
     */
    public function syncProvider(): array
    {
        return [
            [
                // Results
                [
                    'attached' => [
                        1,
                    ],
                    'detached' => [],
                    'updated'  => [],
                ],

                // Id
                1,

                // Attributes
                [],

                // Expected payload
                [
                    1 => 'awards',
                    2 => [
                        [
                            'awardable_id'   => 1,
                            'awardable_type' => User::class,
                            'award_id'       => 1,
                        ],
                    ],
                ],
            ],

            [
                // Results
                [
                    'attached' => [
                        2,
                    ],
                    'detached' => [],
                    'updated'  => [],
                ],

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
                            'award_id'       => 2,
                        ],
                    ],
                ],
            ],

            [
                // Results
                [
                    'attached' => [
                        2,
                        1,
                    ],
                    'detached' => [],
                    'updated'  => [],
                ],

                // Id
                [
                    2 => [
                        'prize' => 2048,
                    ],
                    1 => [
                        'prize' => 512,
                    ],
                ],

                // Attributes
                [],

                // Expected payload
                [
                    1 => 'awards',
                    2 => [
                        [
                            'awardable_id'   => 1,
                            'awardable_type' => User::class,
                            'prize'          => 2048,
                            'award_id'       => 2,
                        ],
                        [
                            'awardable_id'   => 1,
                            'awardable_type' => User::class,
                            'prize'          => 512,
                            'award_id'       => 1,
                        ],
                    ],
                ],
            ],

            [
                // Results
                [
                    'attached' => [
                        1,
                    ],
                    'detached' => [],
                    'updated'  => [],
                ],

                // Id
                Model::class,

                // Attributes
                [],

                // Expected payload
                [
                    1 => 'awards',
                    2 => [
                        [
                            'awardable_id'   => 1,
                            'awardable_type' => User::class,
                            'award_id'       => 1,
                        ],
                    ],
                ],
            ],

            [
                // Results
                [
                    'attached' => [
                        1,
                        2,
                    ],
                    'detached' => [],
                    'updated'  => [],
                ],

                // Id
                Collection::class,

                // Attributes
                [],

                // Expected payload
                [
                    1 => 'awards',
                    2 => [
                        [
                            'awardable_id'   => 1,
                            'awardable_type' => User::class,
                            'award_id'       => 1,
                        ],
                        [
                            'awardable_id'   => 1,
                            'awardable_type' => User::class,
                            'award_id'       => 2,
                        ],
                    ],
                ],
            ],

            [
                // Results
                [
                    'attached' => [
                        1,
                    ],
                    'detached' => [],
                    'updated'  => [],
                ],

                // Id
                BaseCollection::make(1),

                // Attributes
                [],

                // Expected payload
                [
                    1 => 'awards',
                    2 => [
                        [
                            'awardable_id'   => 1,
                            'awardable_type' => User::class,
                            'award_id'       => 1,
                        ],
                    ],
                ],
            ],

            [
                // Results
                [
                    'attached' => [
                        2,
                        1,
                    ],
                    'detached' => [],
                    'updated'  => [],
                ],

                // Id
                BaseCollection::make([
                    2,
                    1,
                ]),

                // Attributes
                [],

                // Expected payload
                [
                    1 => 'awards',
                    2 => [
                        [
                            'awardable_id'   => 1,
                            'awardable_type' => User::class,
                            'award_id'       => 2,
                        ],
                        [
                            'awardable_id'   => 1,
                            'awardable_type' => User::class,
                            'award_id'       => 1,
                        ],
                    ],
                ],
            ],
        ];
    }
}
