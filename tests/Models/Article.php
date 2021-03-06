<?php

declare(strict_types=1);

namespace Altek\Eventually\Tests\Models;

use Altek\Eventually\Relations\BelongsToMany;
use Altek\Eventually\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use \Altek\Eventually\Eventually;

    /**
     * {@inheritdoc}
     */
    protected $table = 'articles';

    /**
     * {@inheritdoc}
     */
    protected $casts = [
        'reviewed' => 'bool',
    ];

    /**
     * {@inheritdoc}
     */
    protected $dates = [
        'published_at',
    ];

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'title',
        'content',
        'published_at',
        'reviewed',
    ];

    /**
     * Associated Users.
     *
     * @return \Altek\Eventually\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
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
