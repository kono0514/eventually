<?php

declare(strict_types=1);

namespace Altek\Eventually\Tests\Models;

use Altek\Eventually\Relations\BelongsToMany;
use Altek\Eventually\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use \Altek\Eventually\Eventually;

    /**
     * {@inheritdoc}
     */
    protected $table = 'users';

    /**
     * Associated Articles.
     *
     * @return \Altek\Eventually\Relations\BelongsToMany
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class)
            ->withPivot('liked')
            ->withTimestamps();
    }

    /**
     * Associated Awards.
     *
     * @return \Altek\Eventually\Relations\MorphToMany
     */
    public function awards(): MorphToMany
    {
        return $this->morphToMany(Award::class, 'awardable')
            ->withPivot('prize');
    }
}
