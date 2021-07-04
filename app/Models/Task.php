<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Models\Task
 *
 * @property int $id
 * @property int $user_id
 * @property int $site_id
 * @property int $offset
 * @property string $date
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */

abstract class Task extends Model
{
    use HasFactory;

    const STATUS_ENABLED = 'enabled';
    const STATUS_DISABLED = 'disabled';
    const STATUS_FINISHED = 'finished';
    const STATUS_FAILED = 'failed';

    public abstract function site() : BelongsTo;
}
