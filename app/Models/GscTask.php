<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\GscTask
 *
 * @property int $id
 * @property int $user_id
 * @property int $site_id
 * @property int $offset
 * @property string $date
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|GscTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GscTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GscTask query()
 * @method static \Illuminate\Database\Eloquent\Builder|GscTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GscTask whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GscTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GscTask whereOffset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GscTask whereSiteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GscTask whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GscTask whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GscTask whereUserId($value)
 * @mixin \Eloquent
 */
class GscTask extends Task
{

    public function site() : BelongsTo {
        return $this->belongsTo(GoogleGscSite::class, 'site_id', 'id');
    }

}
