<?php

declare(strict_types=1);

namespace Altek\Eventually\Tests\Models;

use Altek\Eventually\Eventually;

use Altek\Eventually\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    use Eventually;

    /**
     * {@inheritdoc}
     */
    protected $table = 'awards';

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'name',
    ];

    /**
     * @return \Altek\Eventually\Relations\MorphToMany
     */
    public function articles(): MorphToMany
    {
        return $this->morphedByMany(Article::class, 'awardable');
    }

    /**
     * @return \Altek\Eventually\Relations\MorphToMany
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'awardable');
    }
}
