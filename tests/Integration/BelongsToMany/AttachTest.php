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

class AttachTest extends EventuallyTestCase
{
    /**
     * @test
     */
    public function itSuccessfullyRegistersEventListeners(): void
    {
        User::attaching(function ($user, $relation, $data) {
            $this->assertInstanceOf(User::class, $user);

            $this->assertSame('articles', $relation);

            $this->assertArraySubset([
                1 => [],
                2 => [],
            ], $data, true);
        });

        User::attached(function ($user, $relation, $data) {
            $this->assertInstanceOf(User::class, $user);

            $this->assertSame('articles', $relation);

            $this->assertArraySubset([
                1 => [],
                2 => [],
            ], $data, true);
        });

        $user = factory(User::class)->create();
        $articles = factory(Article::class, 2)->create();

        $this->assertCount(0, $user->articles()->get());
        $this->assertTrue($user->articles()->attach($articles));
        $this->assertCount(2, $user->articles()->get());
    }

    /**
     * @test
     */
    public function itPreventsModelsFromBeingAttached(): void
    {
        User::attaching(function () {
            return false;
        });

        $user = factory(User::class)->create();
        $articles = factory(Article::class, 2)->create();

        $this->assertCount(0, $user->articles()->get());
        $this->assertFalse($user->articles()->attach($articles));
        $this->assertCount(0, $user->articles()->get());
    }

    /**
     * @test
     * @dataProvider attachProvider
     *
     * @param mixed $id
     * @param array $attributes
     * @param array $expectedPayload
     */
    public function itSuccessfullyAttachesModels($id, array $attributes, array $expectedPayload): void
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

        $this->assertTrue($user->articles()->attach($id, $attributes));

        Event::assertDispatched(sprintf('eloquent.attaching: %s', User::class), function ($event, $payload, $halt) use ($expectedPayload) {
            $this->assertArraySubset($expectedPayload, $payload, true);

            $this->assertTrue($halt);

            return true;
        });

        Event::assertDispatched(sprintf('eloquent.attached: %s', User::class), function ($event, $payload) use ($expectedPayload) {
            $this->assertArraySubset($expectedPayload, $payload, true);

            return true;
        });
    }

    /**
     * @return array
     */
    public function attachProvider(): array
    {
        return [
            [
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
                ]
            ],

            [
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
                        2 => [
                            'liked' => false,
                        ],
                    ],
                ]
            ],

            [
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
                ]
            ],

            [
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
                ]
            ],

            [
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
                ]
            ],

            [
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
                ]
            ],

            [
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
                ]
            ],
        ];
    }
}
