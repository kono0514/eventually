<?php

declare(strict_types=1);

namespace Altek\Eventually\Tests\Integration;

use Altek\Eventually\Tests\Events\CustomEvent;
use Altek\Eventually\Tests\EventuallyTestCase;
use Altek\Eventually\Tests\Models\Article;
use Altek\Eventually\Tests\Models\User;
use Illuminate\Support\Facades\Event;

class FirePivotEventTest extends EventuallyTestCase
{
    /**
     * @test
     */
    public function itDoesNotFireEventsWhenDispatcherIsNotSet(): void
    {
        User::unsetEventDispatcher();

        $user = factory(User::class)->create();
        $articles = factory(Article::class, 2)->create();

        $this->assertCount(0, $user->articles()->get());
        $this->assertTrue($user->articles()->attach($articles));
        $this->assertCount(2, $user->articles()->get());
    }

    /**
     * @test
     */
    public function itPreventsModelsFromBeingAttachedViaCustomEventListener(): void
    {
        $articles = factory(Article::class, 2)->create();

        $user = new class() extends User {
            protected $dispatchesEvents = [
                'attaching' => CustomEvent::class,
            ];
        };

        Event::listen(CustomEvent::class, function () {
            return false;
        });

        $this->assertFalse($user->articles()->attach($articles));
    }
}
