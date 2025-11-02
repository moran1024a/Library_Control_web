<?php

namespace App\Core;

use App\Models\LogModel;

class Logger
{
    public function __construct(private LogModel $logModel)
    {
    }

    public function record(?int $userId, string $action, string $details): void
    {
        $this->logModel->recordAction($userId, $action, $details);
    }
}
