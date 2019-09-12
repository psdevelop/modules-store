<?php

namespace app\modules\tickets\services;

use yii\mutex\FileMutex;
use yii\mutex\Mutex;

class LockService
{
    const LOCK_MESSAGE = 'message';

    const DEFAULT_TIMEOUT_SECONDS = 30;

    /** @var Mutex */
    private $mutex;

    /** @var string */
    private $name;

    public function  __construct(string $name)
    {
        $this->mutex = new FileMutex();
        $this->name = $name;
    }

    /**
     * @param string $name
     * @param int $timeout - seconds
     * @return bool
     */
    public function lock(int $timeout = 10): bool
    {
        return $this->mutex->acquire($this->name, $timeout);
    }

    public function unlock(): bool
    {
        if (!$this->mutex) {
            return false;
        }

        return $this->mutex->release($this->name);
    }
}