<?php


namespace App\Repositories;


use App\Models\GscTask;

class GscTaskRepository extends TasksRepository
{

    public function __construct(){
        parent::__construct(GscTask::class);
    }
}
