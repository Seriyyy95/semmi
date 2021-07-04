<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\GoogleAnalyticsSite
 *
 * @property int $id
 * @property int $user_id
 * @property int $profile_id
 * @property string $profile_name
 * @property string $domain
 * @property string $start_date
 * @property string $end_date
 * @property string|null $first_date
 * @property string|null $last_date
 * @property int|null $last_task_id
 * @property int $parsent
 * @property int|null $autoload
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsSite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsSite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsSite query()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsSite whereAutoload($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsSite whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsSite whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsSite whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsSite whereFirstDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsSite whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsSite whereLastDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsSite whereLastTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsSite whereParsent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsSite whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsSite whereProfileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsSite whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsSite whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleAnalyticsSite whereUserId($value)
 * @mixin \Eloquent
 */
class GoogleAnalyticsSite extends GoogleSite
{

    protected $fillable = [
        'profile_id',
        'profile_name',
        'domain',
        'start_date',
        'end_date',
    ];

    public function hasActiveTasks()
    {
        $tasks = GoogleAnalyticsTask::where("site_id", $this->id)
            ->where("status", "active")
            ->count();
        if ($tasks > 0) {
            return true;
        } else {
            return false;
        }
    }
}
