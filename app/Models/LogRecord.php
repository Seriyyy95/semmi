<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\LogRecord
 *
 * @property int $id
 * @property string $source
 * @property string $message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $context
 * @method static \Illuminate\Database\Eloquent\Builder|LogRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LogRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LogRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder|LogRecord whereContext($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogRecord whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogRecord whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LogRecord whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class LogRecord extends Model
{
    public $table = "logs";

    public $casts = [
        'context' => 'array',
    ];
}
