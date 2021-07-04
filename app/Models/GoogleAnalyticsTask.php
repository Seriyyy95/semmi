<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Models\GoogleAnalyticsTask
 *
 * @property int $id
 * @property int $user_id
 * @property int $site_id
 * @property int $offset
 * @property string $date
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsTask query()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsTask whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsTask whereOffset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsTask whereSiteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsTask whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsTask whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsTask whereUserId($value)
 * @mixin \Eloquent
 */
class GoogleAnalyticsTask extends Task
{
    protected $table = "google_analytics_tasks";

    public function site() : BelongsTo {
        return $this->belongsTo(GoogleAnalyticsSite::class, 'site_id', 'id');
    }
}
