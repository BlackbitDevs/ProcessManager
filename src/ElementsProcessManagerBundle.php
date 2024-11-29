<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace Elements\Bundle\ProcessManagerBundle;

use Elements\Bundle\ProcessManagerBundle\DependencyInjection\Compiler;
use Elements\Bundle\ProcessManagerBundle\Model\Configuration;
use Elements\Bundle\ProcessManagerBundle\Model\MonitoringItem;
use Pimcore\Bundle\AdminBundle\PimcoreAdminBundle;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\Extension\Bundle\PimcoreBundleAdminClassicInterface;
use Pimcore\Extension\Bundle\Traits\BundleAdminClassicTrait;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Pimcore\HttpKernel\Bundle\DependentBundleInterface;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

class ElementsProcessManagerBundle extends AbstractPimcoreBundle implements PimcoreBundleAdminClassicInterface, DependentBundleInterface
{
    use ExecutionTrait;
    use PackageVersionTrait;
    use BundleAdminClassicTrait;

    /**
     * @return array<mixed>
     */
    public static function getMaintenanceOptions(): array
    {

        $logDir = str_replace(PIMCORE_PROJECT_ROOT, '', (string) self::getLogDir());

        return [
            'autoCreate' => true,
            'name' => 'ProcessManager maintenance',
            'loggers' => [
                [
                    'logLevel' => 'DEBUG',
                    'class' => '\\' . \Elements\Bundle\ProcessManagerBundle\Executor\Logger\Console::class,
                    'simpleLogFormat' => true,
                ],
                [
                    'logLevel' => 'DEBUG',
                    'filepath' => $logDir . 'process-manager-maintenance.log',
                    'class' => '\\' . \Elements\Bundle\ProcessManagerBundle\Executor\Logger\File::class,
                    'simpleLogFormat' => true,
                    'maxFileSizeMB' => 50,
                ],
            ],
        ];
    }

    /**
     * @var BundleConfiguration|null
     */
    protected static BundleConfiguration|null $_config = null;

    protected static ?MonitoringItem $monitoringItem = null;

    final public const BUNDLE_NAME = 'ElementsProcessManagerBundle';

    final public const TABLE_NAME_CONFIGURATION = 'bundle_process_manager_configuration';

    final public const TABLE_NAME_MONITORING_ITEM = 'bundle_process_manager_monitoring_item';

    final public const TABLE_NAME_CALLBACK_SETTING = 'bundle_process_manager_callback_setting';

    final public const MONITORING_ITEM_ENV_VAR = 'monitoringItemId';

    /**
     * @return array<mixed>
     */
    public function getCssPaths(): array
    {
        return [
            '/bundles/elementsprocessmanager/css/admin.css',
        ];
    }

    /**
     * @return array<mixed>
     */
    public function getJsPaths(): array
    {
        $files = [
            '/bundles/elementsprocessmanager/js/startup.js',
            '/bundles/elementsprocessmanager/js/window/detailwindow.js',
            '/bundles/elementsprocessmanager/js/helper/form.js',

            '/bundles/elementsprocessmanager/js/panel/config.js',
            '/bundles/elementsprocessmanager/js/panel/general.js',
            '/bundles/elementsprocessmanager/js/panel/monitoringItem.js',
            '/bundles/elementsprocessmanager/js/panel/callbackSetting.js',

            '/bundles/elementsprocessmanager/js/executor/class/abstractExecutor.js',
            '/bundles/elementsprocessmanager/js/executor/class/command.js',
            '/bundles/elementsprocessmanager/js/executor/class/classMethod.js',
            '/bundles/elementsprocessmanager/js/executor/class/pimcoreCommand.js',

            '/bundles/elementsprocessmanager/js/executor/action/abstractAction.js',
            '/bundles/elementsprocessmanager/js/executor/action/download.js',
            '/bundles/elementsprocessmanager/js/executor/action/openItem.js',
            '/bundles/elementsprocessmanager/js/executor/action/jsEvent.js',

            '/bundles/elementsprocessmanager/js/executor/logger/abstractLogger.js',
            '/bundles/elementsprocessmanager/js/executor/logger/file.js',
            '/bundles/elementsprocessmanager/js/executor/logger/console.js',
            '/bundles/elementsprocessmanager/js/executor/logger/application.js',
            '/bundles/elementsprocessmanager/js/executor/logger/emailSummary.js',

            '/bundles/elementsprocessmanager/js/executor/callback/abstractCallback.js',
            '/bundles/elementsprocessmanager/js/executor/callback/example.js',
            '/bundles/elementsprocessmanager/js/executor/callback/default.js',
            '/bundles/elementsprocessmanager/js/window/activeProcesses.js',
        ];

        $callbackClasses = ElementsProcessManagerBundle::getConfiguration()->getClassTypes()['executorCallbackClasses'];
        foreach ($callbackClasses as $e) {
            if ($file = $e['jsFile']) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * @inheritDoc
     */
    public function getInstaller(): InstallerInterface
    {
        return $this->container->get(Installer::class);
    }

    public function build(ContainerBuilder $container): void
    {
        $container
            ->addCompilerPass(new Compiler\ExecutorDefinitionPass());
    }

    /**
     * @param array<mixed> $arguments
     *
     * @return void
     */
    public static function shutdownHandler(array $arguments): void
    {
        if (($monitoringItem = self::getMonitoringItem()) instanceof MonitoringItem) {
            $error = error_get_last();
            Helper::executeMonitoringItemLoggerShutdown($monitoringItem);

            if (in_array($error['type'], [E_WARNING, E_DEPRECATED, E_STRICT, E_NOTICE])) {
                if (($config = Configuration::getById($monitoringItem->getConfigurationId())) instanceof \Elements\Bundle\ProcessManagerBundle\Model\Configuration) {
                    $versions = $config->getKeepVersions();
                    if (is_numeric($versions)) {
                        $list = new MonitoringItem\Listing();
                        $list->setOrder('DESC')->setOrderKey('id')->setOffset((int)$versions)->setLimit(100_000_000_000); //a limit has to defined otherwise the offset wont work
                        $list->setCondition('status ="finished" AND configurationId=? AND IFNULL(pid,0) != ? AND parentId IS NULL ', [$config->getId(), $monitoringItem->getPid()]);

                        $items = $list->load();
                        foreach ($items as $item) {
                            $item->delete();
                        }
                    }
                }
                if (!$monitoringItem->getMessage()) {
                    $monitoringItem->setMessage('finished');
                }
                $monitoringItem->setCompleted();
                $monitoringItem->setPid(null)->save();
            } else {
                $monitoringItem->setMessage('ERROR:' . print_r($error, true) . $monitoringItem->getMessage());
                $monitoringItem->setPid(null)->setStatus($monitoringItem::STATUS_FAILED)->save();
            }
        }
    }

    /**
     * @param array<mixed> $arguments
     *
     * @return void
     */
    public static function startup(array $arguments): void
    {
        $monitoringItem = $arguments['monitoringItem'];
        if ($monitoringItem instanceof MonitoringItem) {
            $monitoringItem->resetState()->save();
            $monitoringItem->setPid(getmypid());
            $monitoringItem->setStatus($monitoringItem::STATUS_RUNNING);
            $monitoringItem->save();
        }
    }

    /**
     * @return BundleConfiguration
     */
    public static function getConfiguration(): BundleConfiguration
    {
        if (is_null(self::$_config)) {
            $configArray = \Pimcore::getKernel()->getContainer()->getParameter('elements_process_manager');
            self::$_config = new BundleConfiguration($configArray);
        }

        return self::$_config;
    }

    public static function getLogDir(): string
    {
        $dir = PIMCORE_LOG_DIRECTORY . '/process-manager/';
        if (!is_dir($dir)) {
            $filesystem = new Filesystem();
            $filesystem->mkdir($dir, 0775);
        }

        return $dir;
    }

    public function getDescription(): string
    {
        return 'Process Manager';
    }

    public static function setMonitoringItem(mixed $monitoringItem): void
    {
        self::$monitoringItem = $monitoringItem;
    }

    /**
     * @param bool $createDummyObjectIfRequired
     *
     * @return MonitoringItem|null
     */
    public static function getMonitoringItem(bool $createDummyObjectIfRequired = true): ?MonitoringItem
    {
        if ($createDummyObjectIfRequired && !self::$monitoringItem) {
            if (getenv(self::MONITORING_ITEM_ENV_VAR)) {
                self::$monitoringItem = MonitoringItem::getById((int)getenv(self::MONITORING_ITEM_ENV_VAR));
                self::$monitoringItem->setStatus(MonitoringItem::STATUS_RUNNING)->save();
            } else {
                self::$monitoringItem = new MonitoringItem();
                self::$monitoringItem->setIsDummy(true);
            }
        }

        return self::$monitoringItem;
    }

    protected function getComposerPackageName(): string
    {
        return 'elements/process-manager-bundle';
    }

    public function getNiceName(): string
    {
        return self::BUNDLE_NAME;
    }

    public static function registerDependentBundles(BundleCollection $collection): void
    {
        $collection->addBundle(new PimcoreAdminBundle(), 60);
    }
}
