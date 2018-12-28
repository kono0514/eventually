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

class ToggleTest extends EventuallyTestCase
{
    /**
     * @test
     */
    public function itSuccessfullyRegistersEventListeners(): void
    {
        User::toggling(function ($user, $relation, $data) {
            $this->assertInstanceOf(User::class, $user);

            $this->assertSame('articles', $relation);

            $this->assertArraySubset([
                1 => [],
            ], $data, true);
        });

        User::toggled(function ($user, $relation, $data) {
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
        ], $user->articles()->toggle($article), true);
        $this->assertCount(1, $user->articles()->get());
    }

    /**
     * @test
     */
    public function itPreventsModelsFromBeingToggled(): void
    {
        User::toggling(function () {
            return false;
        });

        $user = factory(User::class)->create();
        $articles = factory(Article::class, 2)->create();

        $this->assertCount(0, $user->articles()->get());
        $this->assertFalse($user->articles()->toggle($articles));
        $this->assertCount(0, $user->articles()->get());
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

        $this->assertArraySubset($results, $user->articles()->toggle($id), true);

        Event::assertDispatched(sprintf('eloquent.toggling: %s', User::class), function ($event, $payload, $halt) use ($expectedPayload) {
            $this->assertArraySubset($expectedPayload, $payload, true);

            $this->assertTrue($halt);

            return true;
        });

        Event::assertDispatched(sprintf('eloquent.toggled: %s', User::class), function ($event, $payload) use ($expectedPayload) {
            $this->assertArraySubset($expectedPayload, $payload, true);

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
                    1 => 'articles',
                    2 => [
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
                ],

                // Id
                [
                    2,
                ],

                // Expected payload
                [
                    1 => 'articles',
                    2 => [
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
                ],

                // Id
                [
                    2,
                    1,
                ],

                // Expected payload
                [
                    1 => 'articles',
                    2 => [
                        2 => [],
                        1 => [],
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
                    1 => 'articles',
                    2 => [
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
                ],

                // Id
                Collection::class,

                // Expected payload
                [
                    1 => 'articles',
                    2 => [
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
                ],

                // Id
                BaseCollection::make(1),

                // Expected payload
                [
                    1 => 'articles',
                    2 => [
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
                ],

                // Id
                BaseCollection::make([
                    2,
                    1,
                ]),

                // Expected payload
                [
                    1 => 'articles',
                    2 => [
                        2 => [],
                        1 => [],
                    ],
                ],
            ],
        ];
    }
}
