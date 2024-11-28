<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace Elements\Bundle\ProcessManagerBundle\Message;

class CheckCommandAliveMessage
{
    public function __construct(
        private readonly int $monitoringItemId,
    ) {
    }

    public function getMonitoringItemId(): int
    {
        return $this->monitoringItemId;
    }
}
