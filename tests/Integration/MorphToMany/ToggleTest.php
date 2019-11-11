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

class ToggleTest extends EventuallyTestCase
{
    /**
     * @test
     */
    public function itSuccessfullyRegistersEventListeners(): void
    {
        User::toggling(function ($user, $relation, $properties): void {
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

        User::toggled(function ($user, $relation, $properties): void {
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
        ], $user->awards()->toggle($award));

        $this->assertCount(1, $user->awards()->get());
    }

    /**
     * @test
     */
    public function itPreventsModelsFromBeingToggled(): void
    {
        User::toggling(static function () {
            return false;
        });

        $user   = factory(User::class)->create();
        $awards = factory(Award::class, 2)->create();

        $this->assertCount(0, $user->awards()->get());

        $this->assertFalse($user->awards()->toggle($awards));

        $this->assertCount(0, $user->awards()->get());
    }

    /**
     * @test
     * @dataProvider toggleProvider
     *
     * @param array $results
     * @param mixed $id
     * @param array $expectedPayload
     */
    public function itSuccessfullyTogglesModels(array $results, $id, array $expectedPayload): void
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

        $this->assertSame($results, $user->awards()->toggle($id));

        Event::assertDispatched(\sprintf('eloquent.toggling: %s', User::class), function ($event, $payload, $halt) use ($expectedPayload) {
            $this->assertInstanceOf(User::class, $payload[0]);

            unset($payload[0]);

            $this->assertSame($expectedPayload, $payload);

            $this->assertTrue($halt);

            return true;
        });

        Event::assertDispatched(\sprintf('eloquent.toggled: %s', User::class), function ($event, $payload) use ($expectedPayload) {
            $this->assertInstanceOf(User::class, $payload[0]);

            unset($payload[0]);

            $this->assertSame($expectedPayload, $payload);

            return true;
        });
    }

    /**
     * @return array
     */
    public function toggleProvider(): array
    {
        return [
            [
                // Results
                [
                    'attached' => [
                        1,
                    ],
                    'detached' => [],
                ],

                // Id
                1,

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
                ],

                // Id
                [
                    2,
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
                ],

                // Id
                [
                    2,
                    1,
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
                    ],
                    'detached' => [],
                ],

                // Id
                Model::class,

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
                ],

                // Id
                Collection::class,

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
                ],

                // Id
                BaseCollection::make(1),

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
                ],

                // Id
                BaseCollection::make([
                    2,
                    1,
                ]),

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
