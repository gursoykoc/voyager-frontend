<?php

namespace Pvtl\VoyagerFrontend;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Translatable;
use Laravel\Scout\Searchable;
use Pvtl\VoyagerFrontend\Helpers\BladeCompiler;

class Page extends Model
{
    use Translatable, Searchable;

    public $asYouType = false;

    protected $translatable = ['title', 'slug', 'body'];

    /**
     * Statuses.
     */
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_INACTIVE = 'INACTIVE';

    /**
     * List of statuses.
     *
     * @var array
     */
    public static $statuses = [self::STATUS_ACTIVE, self::STATUS_INACTIVE];

    protected $guarded = [];

    public function save(array $options = [])
    {
        // If no author has been assigned, assign the current user's id as the author of the post
        if (!$this->author_id && Auth::user()) {
            $this->author_id = Auth::user()->id;
        }

        parent::save();
    }

    /**
     * Scope a query to only include active pages.
     *
     * @param  $query  \Illuminate\Database\Eloquent\Builder
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', static::STATUS_ACTIVE);
    }

    /**
     * Get the indexed data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return $this->toArray();
    }
}
