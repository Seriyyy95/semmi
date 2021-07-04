<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\GoogleGscSite
 *
 * @property int $id
 * @property int $user_id
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
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleGscSite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleGscSite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleGscSite query()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleGscSite whereAutoload($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleGscSite whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleGscSite whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleGscSite whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleGscSite whereFirstDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleGscSite whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleGscSite whereLastDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleGscSite whereLastTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleGscSite whereParsent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleGscSite whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleGscSite whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleGscSite whereUserId($value)
 * @mixin \Eloquent
 */
class GoogleGscSite extends GoogleSite
{

    public $fillable = [
        'domain',
        'start_date',
        'end_date'
    ];

    public function hasActiveTasks(){
        $tasks = GscTask::where("site_id", $this->id)
            ->where("status", "active")
            ->count();
        if($tasks > 0){
            return true;
        }else{
            return false;
        }
    }

}
