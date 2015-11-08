<?php

namespace Gmoigneu\JackRabbit;

use Illuminate\Support\ServiceProvider;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain as DriverChain;
use Doctrine\ODM\PHPCR\Tools\Console\Helper\DocumentManagerHelper;

class JackRabbitServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/jackrabbit.php' => config_path('jackrabbit.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerSession();

        $chain = new DriverChain();
        // Bind the annotation driver
        $chain->addDriver($this->getAnnotationDriver(), 'App');

        $this->addCommandsToArtisan();
        $this->addHelpersToArtisan();

    }

    protected function registerSession()
    {
        $this->app->singleton('phpcr.session', function() {
            // Register Jackrabbit service
            $factory = new \Jackalope\RepositoryFactoryJackrabbit();
            $repository = $factory->getRepository(
                ["jackalope.jackrabbit_uri" => \Config::get('jackrabbit.server.url')]
            );
            $credentials = new \PHPCR\SimpleCredentials(
                \Config::get('jackrabbit.server.user'),
                \Config::get('jackrabbit.server.pass')
            );

            return $repository->login($credentials, \Config::get('jackrabbit.server.workspace'));
        });
    }

    protected function addCommandsToArtisan()
    {
        $app = $this->app;

        $app['commands.phpcr.workspace.create'] = new \PHPCR\Util\Console\Command\WorkspaceCreateCommand;
        $app['commands.phpcr.workspace.export'] = new \PHPCR\Util\Console\Command\WorkspaceExportCommand;
        $app['commands.phpcr.workspace.import'] = new \PHPCR\Util\Console\Command\WorkspaceImportCommand;
        $app['commands.phpcr.workspace.list'] = new \PHPCR\Util\Console\Command\WorkspaceListCommand;
        $app['commands.phpcr.workspace.import'] = new \PHPCR\Util\Console\Command\WorkspaceImportCommand;
        $app['commands.phpcr.workspace.purge'] = new \PHPCR\Util\Console\Command\WorkspacePurgeCommand;
        $app['commands.phpcr.workspace.query'] = new \PHPCR\Util\Console\Command\WorkspaceQueryCommand;
        $app['commands.phpcr.nodetype.register'] = new \PHPCR\Util\Console\Command\NodeTypeRegisterCommand;
        $app['commands.phpcr.node.dump'] = new \PHPCR\Util\Console\Command\NodeDumpCommand;
        $app['commands.phpcr.nodetype.register'] = new \PHPCR\Util\Console\Command\NodeTypeRegisterCommand;
        $app['commands.phpcr.nodetype.register.system'] = new \Doctrine\ODM\PHPCR\Tools\Console\Command\RegisterSystemNodeTypesCommand;
        $app['commands.phpcr.querybuilder.dump'] = new \Doctrine\ODM\PHPCR\Tools\Console\Command\DumpQueryBuilderReferenceCommand;


        $this->commands(array(
            'commands.phpcr.workspace.create',
            'commands.phpcr.workspace.export',
            'commands.phpcr.workspace.import',
            'commands.phpcr.workspace.list',
            'commands.phpcr.workspace.import',
            'commands.phpcr.workspace.purge',
            'commands.phpcr.workspace.query',
            'commands.phpcr.nodetype.register',
            'commands.phpcr.node.dump',
            'commands.phpcr.nodetype.register',
            'commands.phpcr.nodetype.register.system',
            'commands.phpcr.querybuilder.dump'
        ));
    }

    protected function addHelpersToArtisan()
    {
        // Add the helperset to artisan
        $this->app['events']->listen('artisan.start', function($artisan) {
            $h = $artisan->getHelperSet();
            $h->set(new \Symfony\Component\Console\Helper\DialogHelper(), 'dialog');
            $h->set(new \PHPCR\Util\Console\Helper\PhpcrHelper($this->app['phpcr.session']), 'phpcr');
            $h->set(new \PHPCR\Util\Console\Helper\PhpcrConsoleDumperHelper(), 'phpcr_console_dumper');
            $h->set(new DocumentManagerHelper(null, $this->app['phpcr.manager']), 'dm');

            $artisan->setHelperSet($h);

        });
    }

    protected function getAnnotationDriver()
    {
        AnnotationRegistry::registerFile($this->app['path.base'].'/vendor/doctrine/phpcr-odm/lib/Doctrine/ODM/PHPCR/Mapping/Annotations/DoctrineAnnotations.php');
        $reader = new AnnotationReader();
        $driver = new AnnotationDriver($reader, [$this->app['path.base'].'/app/']);

        $config = new \Doctrine\ODM\PHPCR\Configuration();
        $config->setMetadataDriverImpl($driver);

        $this->app->singleton('phpcr.manager', function() use ($config) {
            return DocumentManager::create($this->app->make('phpcr.session'), $config);
        });

        return $driver;
    }
}
