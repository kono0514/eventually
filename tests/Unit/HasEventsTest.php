<?php

declare(strict_types=1);

namespace Altek\Eventually\Tests\Integration\BelongsToMany;

use Altek\Eventually\Tests\EventuallyTestCase;
use Altek\Eventually\Tests\Models\User;

class HasEventsTest extends EventuallyTestCase
{
    /**
     * @test
     */
    public function itAssuresThePivotEventsAreObservable(): void
    {
        $user = new User();

        $this->assertContains('toggling', $user->getObservableEvents());
        $this->assertContains('toggled', $user->getObservableEvents());
        $this->assertContains('syncing', $user->getObservableEvents());
        $this->assertContains('synced', $user->getObservableEvents());
        $this->assertContains('updatingExistingPivot', $user->getObservableEvents());
        $this->assertContains('existingPivotUpdated', $user->getObservableEvents());
        $this->assertContains('attaching', $user->getObservableEvents());
        $this->assertContains('attached', $user->getObservableEvents());
        $this->assertContains('detaching', $user->getObservableEvents());
        $this->assertContains('detached', $user->getObservableEvents());
    }
}
