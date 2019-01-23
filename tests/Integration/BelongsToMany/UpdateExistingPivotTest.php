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

class UpdateExistingPivotTest extends EventuallyTestCase
{
    /**
     * @test
     */
    public function itSuccessfullyRegistersEventListeners(): void
    {
        User::updatingExistingPivot(function ($user, $relation, $properties) {
            $this->assertInstanceOf(User::class, $user);

            $this->assertSame('articles', $relation);

            $this->assertArraySubset([
                [
                    'user_id'    => 1,
                    'article_id' => 1,
                ],
                [
                    'user_id'    => 1,
                    'article_id' => 2,
                ],
            ], $properties, true);
        });

        User::existingPivotUpdated(function ($user, $relation, $properties) {
            $this->assertInstanceOf(User::class, $user);

            $this->assertSame('articles', $relation);

            $this->assertArraySubset([
                [
                    'user_id'    => 1,
                    'article_id' => 1,
                ],
                [
                    'user_id'    => 1,
                    'article_id' => 2,
                ],
            ], $properties, true);
        });

        $user = factory(User::class)->create();

        $articles = factory(Article::class, 2)->create()->each(function (Article $article) use ($user) {
            $article->users()->attach($user);
        });

        $this->assertCount(2, $user->articles()->get());
        $this->assertSame(2, $user->articles()->updateExistingPivot($articles, [
            'liked' => true,
        ]));
    }

    /**
     * @test
     */
    public function itPreventsExistingPivotFromBeingUpdated(): void
    {
        User::updatingExistingPivot(function () {
            return false;
        });

        $user = factory(User::class)->create();

        $articles = factory(Article::class, 2)->create()->each(function (Article $article) use ($user) {
            $article->users()->attach($user);
        });

        $this->assertCount(2, $user->articles()->get());
        $this->assertFalse($user->articles()->updateExistingPivot($articles, [
            'liked' => true,
        ]));
        $this->assertCount(2, $user->articles()->get());
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

        $articles = factory(Article::class, 2)->create()->each(function (Article $article) use ($user) {
            $article->users()->attach($user);
        });

        $this->assertCount(2, $user->articles()->get());

        Event::fake();

        switch ($id) {
            case Model::class:
                $id = $articles->first();
                break;

            case Collection::class:
                $id = $articles;
                break;
        }

        $this->assertSame($results, $user->articles()->updateExistingPivot($id, $attributes));

        Event::assertDispatched(\sprintf('eloquent.updatingExistingPivot: %s', User::class), function ($event, $payload, $halt) use ($expectedPayload) {
            $this->assertArraySubset($expectedPayload, $payload, true);

            $this->assertTrue($halt);

            return true;
        });

        Event::assertDispatched(\sprintf('eloquent.existingPivotUpdated: %s', User::class), function ($event, $payload) use ($expectedPayload) {
            $this->assertArraySubset($expectedPayload, $payload, true);

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
                [],

                // Expected payload
                [
                    1 => 'articles',
                    2 => [
                        [
                            'user_id'    => 1,
                            'article_id' => 1,
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
                    'liked' => false,
                ],

                // Expected payload
                [
                    1 => 'articles',
                    2 => [
                        [
                            'user_id'    => 1,
                            'article_id' => 2,
                            'liked'      => false,
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
                [],

                // Expected payload
                [
                    1 => 'articles',
                    2 => [
                        [
                            'user_id'    => 1,
                            'article_id' => 2,
                        ],
                        [
                            'user_id'    => 1,
                            'article_id' => 1,
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
                [],

                // Expected payload
                [
                    1 => 'articles',
                    2 => [
                        [
                            'user_id'    => 1,
                            'article_id' => 1,
                        ],
                    ],
                ],
            ],

            [
                // Results
                2,

                // Id
                Collection::class,

                // Attributes
                [],

                // Expected payload
                [
                    1 => 'articles',
                    2 => [
                        [
                            'user_id'    => 1,
                            'article_id' => 1,
                        ],
                        [
                            'user_id'    => 1,
                            'article_id' => 2,
                        ],
                    ],
                ],
            ],

            [
                // Results
                1,

                // Id
                BaseCollection::make(1),

                // Attributes
                [],

                // Expected payload
                [
                    1 => 'articles',
                    2 => [
                        [
                            'user_id'    => 1,
                            'article_id' => 1,
                        ],
                    ],
                ],
            ],

            [
                // Results
                2,

                // Id
                BaseCollection::make([
                    2,
                    1,
                ]),

                // Attributes
                [],

                // Expected payload
                [
                    1 => 'articles',
                    2 => [
                        [
                            'user_id'    => 1,
                            'article_id' => 2,
                        ],
                        [
                            'user_id'    => 1,
                            'article_id' => 1,
                        ],
                    ],
                ],
            ],
        ];
    }
}
