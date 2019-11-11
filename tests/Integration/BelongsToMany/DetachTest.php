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

class DetachTest extends EventuallyTestCase
{
    /**
     * @test
     */
    public function itSuccessfullyRegistersEventListeners(): void
    {
        User::detaching(function ($user, $relation, $properties): void {
            $this->assertInstanceOf(User::class, $user);

            $this->assertSame('articles', $relation);

            $this->assertSame([
                [
                    'user_id'    => 1,
                    'article_id' => 1,
                ],
                [
                    'user_id'    => 1,
                    'article_id' => 2,
                ],
            ], $properties);
        });

        User::detached(function ($user, $relation, $properties): void {
            $this->assertInstanceOf(User::class, $user);

            $this->assertSame('articles', $relation);

            $this->assertSame([
                [
                    'user_id'    => 1,
                    'article_id' => 1,
                ],
                [
                    'user_id'    => 1,
                    'article_id' => 2,
                ],
            ], $properties);
        });

        $user = factory(User::class)->create();

        $articles = factory(Article::class, 2)->create()->each(static function (Article $article) use ($user): void {
            $article->users()->attach($user);
        });

        $this->assertCount(2, $user->articles()->get());

        $this->assertSame(2, $user->articles()->detach($articles));

        $this->assertCount(0, $user->articles()->get());
    }

    /**
     * @test
     */
    public function itPreventsModelsFromBeingDetached(): void
    {
        User::detaching(static function () {
            return false;
        });

        $user = factory(User::class)->create();

        $articles = factory(Article::class, 2)->create()->each(static function (Article $article) use ($user): void {
            $article->users()->attach($user);
        });

        $this->assertCount(2, $user->articles()->get());

        $this->assertFalse($user->articles()->detach($articles));

        $this->assertCount(2, $user->articles()->get());
    }

    /**
     * @test
     * @dataProvider detachProvider
     *
     * @param int   $results
     * @param mixed $id
     * @param array $expectedPayload
     */
    public function itSuccessfullyDetachesModels(int $results, $id, array $expectedPayload): void
    {
        $user = factory(User::class)->create();

        $articles = factory(Article::class, 2)->create()->each(static function (Article $article) use ($user): void {
            $article->users()->attach($user, [
                'liked' => (bool) \random_int(0, 1),
            ]);
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

        $this->assertSame($results, $user->articles()->detach($id));

        $this->assertCount(2 - $results, $user->articles()->get());

        Event::assertDispatched(\sprintf('eloquent.detaching: %s', User::class), function ($event, $payload, $halt) use ($expectedPayload) {
            $this->assertInstanceOf(User::class, $payload[0]);

            unset($payload[0]);

            $this->assertSame($expectedPayload, $payload);

            $this->assertTrue($halt);

            return true;
        });

        Event::assertDispatched(\sprintf('eloquent.detached: %s', User::class), function ($event, $payload) use ($expectedPayload) {
            $this->assertInstanceOf(User::class, $payload[0]);

            unset($payload[0]);

            $this->assertSame($expectedPayload, $payload);

            return true;
        });
    }

    /**
     * @return array
     */
    public function detachProvider(): array
    {
        return [
            [
                // Results
                2,

                // Id
                null,

                // Expected payload
                [
                    1 => 'articles',
                    2 => [
                        [
                            'user_id'    => 1,
                            'article_id' => '1',
                        ],
                        [
                            'user_id'    => 1,
                            'article_id' => '2',
                        ],
                    ],
                ],
            ],

            [
                // Results
                1,

                // Id
                1,

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

                // Expected payload
                [
                    1 => 'articles',
                    2 => [
                        [
                            'user_id'    => 1,
                            'article_id' => 2,
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
