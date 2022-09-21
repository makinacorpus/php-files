<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Bridge\Symfony\DependencyInjection;

use MakinaCorpus\Files\FileManager;
use MakinaCorpus\Files\Bridge\Goat\Index\GoatFileIndex;
use MakinaCorpus\Files\Bridge\Symfony\Command\FileIndexDeleteCommand;
use MakinaCorpus\Files\Bridge\Symfony\Command\FileOrphanDeleteCommand;
use MakinaCorpus\Files\Index\FileIndex;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Twig\Environment;

/**
 * @codeCoverageIgnore
 * @todo This should be tested.
 */
final class FilesExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('services.yml');

        if (\class_exists(Environment::class)) {
            $loader->load('twig.yml');
        }

        $this->configureFileManagerSchemes($config, $container);
        $this->configureFileIndex($config, $container);
    }

    private function configureFileManagerSchemes(array $config, ContainerBuilder $container): void
    {
        $fileManagerDef = $container->getDefinition('files.file_manager');
        $knownSchemes = [];

        // Determine default paths, that should be defined as
        // environment variables. Parameters can be null if the
        // environment variables are not set in the .env file.
        $knownSchemes[FileManager::SCHEME_TEMPORARY] = \sys_get_temp_dir();
        $knownSchemes[FileManager::SCHEME_PRIVATE] = $container->getParameter('files.private_directory');
        $knownSchemes[FileManager::SCHEME_PUBLIC] = $container->getParameter('files.public_directory');
        $knownSchemes[FileManager::SCHEME_UPLOAD] = $container->getParameter('files.upload_directory');

        // @todo user driven schemes (should be from configuration)
        $fileManagerDef->setArgument(0, $knownSchemes);
    }

    private function configureFileIndex(array $config, ContainerBuilder $container): void
    {
        $driver = $config['index']['driver'] ?? null;
        $driverOptions = $config['index']['driver_options'] ?? [];
        $serviceId = null;

        if (!$driver) {
            return;
        }

        switch ($driver) {
            case 'goat':
                $serviceId = 'files.index.goat';
                $runnerId = 'goat.runner.' . $driverOptions['runner'];

                $definition = new Definition(GoatFileIndex::class, [
                    new Reference('files.file_manager'),
                    new Reference($runnerId),
                    $driverOptions['table_files'] ?? 'public.file',
                    $driverOptions['table_attributes'] ?? 'public.file_attribute',
                ]);
                $container->setDefinition($serviceId, $definition);
                break;

            default:
                throw new InvalidArgumentException(\sprintf("'%s' is not a valid file index driver"));
        }

        $container->setAlias(FileIndex::class, $serviceId);
        $container->setAlias('files.index', $serviceId);

        $this->configureFileIndexDeleteCommand($config, $container, $serviceId);
        $this->configureFileOrphanDeleteCommand($config, $container, $serviceId);
    }

    private function configureFileIndexDeleteCommand(array $config, ContainerBuilder $container, string $serviceId): void
    {
        $definition = new Definition(FileIndexDeleteCommand::class, [
            new Reference('files.file_manager'),
            new Reference($serviceId)
        ]);
        $definition->addMethodCall('setLogger', [new Reference('logger')]);
        $definition->addTag('console.command');

        $container->setDefinition(FileIndexDeleteCommand::class, $definition);
    }

    private function configureFileOrphanDeleteCommand(array $config, ContainerBuilder $container, string $serviceId): void
    {
        $definition = new Definition(FileOrphanDeleteCommand::class, [
            new Reference('files.file_manager'),
            new Reference($serviceId)
        ]);
        $definition->addMethodCall('setLogger', [new Reference('logger')]);
        $definition->addTag('console.command');

        $container->setDefinition(FileOrphanDeleteCommand::class, $definition);
    }
}
