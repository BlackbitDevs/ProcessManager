<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace Elements\Bundle\ProcessManagerBundle\Executor\Logger;

use Elements\Bundle\ProcessManagerBundle\Model\MonitoringItem;
use Monolog\Handler\StreamHandler;
use Pimcore\Bundle\ApplicationLoggerBundle\Handler\ApplicationLoggerDb;

abstract class AbstractLogger
{
    final public const LOG_FORMAT_SIMPLE = "[%datetime%] %channel%.%level_name%: %message% \n";

    public string $extJsClass = '';

    public string $name = '';

    /**
     * @var array<mixed>
     */
    protected array $config = [];

    /**
     * @return string
     */
    public function getExtJsClass()
    {
        return $this->extJsClass;
    }

    /**
     * @param string $extJsClass
     *
     * @return $this
     */
    public function setExtJsClass(string $extJsClass)
    {
        $this->extJsClass = $extJsClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array<mixed> $config
     *
     * @return $this
     */
    public function setConfig(array $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @param $monitoringItem MonitoringItem
     * @param array<mixed> $actionData
     *
     * @return string
     */
    abstract public function getGridLoggerHtml(MonitoringItem $monitoringItem, array $actionData): string;

    /**
     * @param array<mixed> $config
     * @param MonitoringItem $monitoringItem
     *
     */
    abstract public function createStreamHandler(array $config, MonitoringItem $monitoringItem): StreamHandler | ApplicationLoggerDb | null;
}
