<?php

namespace App\Application\Contracts\Out\Managers\Notifier;

interface INotifierManager
{
    /**
     * @param int $userId
     * @param mixed $notification
     * @return mixed
     */
    public function sendNotification(int $userId, mixed $notification);
}
