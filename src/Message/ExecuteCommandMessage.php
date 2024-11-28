<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace Elements\Bundle\ProcessManagerBundle\Message;

class ExecuteCommandMessage
{
    public function __construct(
        private readonly string $command,
        private readonly int $monitoringItemId,
        private readonly ?string $outputFile = null
    ) {
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getMonitoringItemId(): int
    {
        return $this->monitoringItemId;
    }

    public function getOutputFile(): ?string
    {
        return $this->outputFile;
    }
}
