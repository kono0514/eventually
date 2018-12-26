<?php

declare(strict_types=1);

namespace Altek\Eventually\Tests\Integration\BelongsToMany;

use Altek\Eventually\Tests\EventuallyTestCase;
use Altek\Eventually\Tests\Models\Article;
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
        User::syncing(function ($user, $relation, $data) {
            $this->assertInstanceOf(User::class, $user);

            $this->assertSame('articles', $relation);

            $this->assertArraySubset([
                1 => [],
            ], $data, true);
        });

        User::synced(function ($user, $relation, $data) {
            $this->assertInstanceOf(User::class, $user);

            $this->assertSame('articles', $relation);

            $this->assertArraySubset([
                1 => [],
            ], $data, true);
        });

        $user = factory(User::class)->create();
        $article = factory(Article::class)->create();

        $this->assertCount(0, $user->articles()->get());
        $this->assertArraySubset([
            'attached' => [
                1,
            ],
            'detached' => [],
            'updated'  => [],
        ], $user->articles()->sync($article), true);
        $this->assertCount(1, $user->articles()->get());
    }

    /**
     * @test
     */
    public function itPreventsModelsFromBeingSynced(): void
    {
        User::syncing(function () {
            return false;
        });

        $user = factory(User::class)->create();
        $articles = factory(Article::class, 2)->create();

        $this->assertCount(0, $user->articles()->get());
        $this->assertFalse($user->articles()->sync($articles));
        $this->assertCount(0, $user->articles()->get());
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
        $user = factory(User::class)->create();
        $articles = factory(Article::class, 2)->create();

        $this->assertCount(0, $user->articles()->get());

        Event::fake();

        switch ($id) {
            case Model::class:
                $id = $articles->first();
                break;

            case Collection::class:
                $id = $articles;
                break;
        }

        $this->assertArraySubset($results, $user->articles()->sync($id, $attributes), true);

        Event::assertDispatched(sprintf('eloquent.syncing: %s', User::class), function ($event, $payload, $halt) use ($expectedPayload) {
            $this->assertArraySubset($expectedPayload, $payload, true);

            $this->assertTrue($halt);

            return true;
        });

        Event::assertDispatched(sprintf('eloquent.synced: %s', User::class), function ($event, $payload) use ($expectedPayload) {
            $this->assertArraySubset($expectedPayload, $payload, true);

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
                    'relation' => 'articles',
                    'data'     => [
                        1 => [],
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
                    'liked' => false,
                ],

                // Expected payload
                [
                    'relation' => 'articles',
                    'data'     => [
                        2 => [],
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
                        'liked' => false,
                    ],
                    1 => [
                        'liked' => true,
                    ],
                ],

                // Attributes
                [],

                // Expected payload
                [
                    'relation' => 'articles',
                    'data'     => [
                        2 => [
                            'liked' => false,
                        ],
                        1 => [
                            'liked' => true,
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
                    'relation' => 'articles',
                    'data'     => [
                        1 => [],
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
                    'relation' => 'articles',
                    'data'     => [
                        1 => [],
                        2 => [],
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
                    'relation' => 'articles',
                    'data'     => [
                        1 => [],
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
                    'relation' => 'articles',
                    'data'     => [
                        2 => [],
                        1 => [],
                    ],
                ],
            ],
        ];
    }
}
