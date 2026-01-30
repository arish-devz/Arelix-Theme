<?php

namespace Pterodactyl\Traits\Services;

use Pterodactyl\Models\User;

trait HasUserLevels
{
    private int $userLevel = User::USER_LEVEL_USER;

    
    public function setUserLevel(int $level): self
    {
        $this->userLevel = $level;

        return $this;
    }

    
    public function getUserLevel(): int
    {
        return $this->userLevel;
    }

    
    public function isUserLevel(int $level): bool
    {
        return $this->getUserLevel() === $level;
    }
}
