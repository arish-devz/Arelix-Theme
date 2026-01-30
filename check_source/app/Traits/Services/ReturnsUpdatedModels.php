<?php

namespace Pterodactyl\Traits\Services;

trait ReturnsUpdatedModels
{
    private bool $updatedModel = false;

    public function getUpdatedModel(): bool
    {
        return $this->updatedModel;
    }

    
    public function returnUpdatedModel(bool $toggle = true): self
    {
        $this->updatedModel = $toggle;

        return $this;
    }
}
