<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\GoogleSite
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
 *
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleSite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleSite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoogleSite query()
 * @mixin \Eloquent
 */
abstract class GoogleSite extends Model
{

    private $nextDate;

    public $next_date;
    public $queue_start_date;
    public $queue_end_date;
    public $stored_start_date;
    public $stored_end_date;

    public function __construct(){
      parent::__construct();
    }

    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = parent::newFromBuilder($attributes, $connection);
        $model->nextDate = new \DateTime($model->start_date);
        $model->next_date = $model->nextDate->format("Y-m-d");
        return $model;
    }

    public function setQueuedDates($start_date, $end_date){
      $this->queue_start_date = $start_date;
      $this->queue_end_date = $end_date;
      $this->updateNextDate($end_date);
    }

    public function setStoredDates($start_date, $end_date){
      $this->stored_start_date = $start_date;
      $this->stored_end_date = $end_date;
      $this->updateNextDate($end_date);
    }

    private function updateNextDate($new_date){
      $newDate = new \DateTime($new_date);
      $endAvailableDate = new \DateTime($this->getAttribute("end_date"));
      if($this->nextDate < $newDate){
          $this->nextDate = $newDate;
          if($this->nextDate < $endAvailableDate){
            $this->nextDate->add(new \DateInterval("P1D"));
          }
          $this->next_date = $this->nextDate->format("Y-m-d");
      }
    }
}
