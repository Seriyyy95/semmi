<?php

declare(strict_types=1);

namespace App\Models\Dto;

/**
 * Class AddDatesDto
 * @package App\Models\Dto
 */
class AddDatesDto
{
    public ?int $count;
    public ?int $lastTaskId;

    /**
     * AddDatesDto constructor.
     * @param $count
     * @param $lastTaskId
     */
    public function __construct(int $count, ?int $lastTaskId)
    {
        $this->count = $count;
        $this->lastTaskId = $lastTaskId;
    }
}
