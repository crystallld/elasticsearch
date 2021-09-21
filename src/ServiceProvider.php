<?php
namespace Elasticsearch;

use Exception;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Eloquent\Model;
use Laravel\Scout\EngineManager;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        Model::setConnectionResolver($this->app['db']);

        Model::setEventDispatcher($this->app['events']);
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        // Add database driver.
        $this->app->resolving('db', function ($db) {
            $db->extend('elasticsearch', function ($config, $name) {
                $config['name'] = $name;
                return new Connection($config);
            });
        });

//        // Add connector for queue support.
//        $this->app->resolving('queue', function ($queue) {
//            $queue->addConnector('mongodb', function () {
//                return new MongoConnector($this->app['db']);
//            });
//        });
    }
//    /**
//     * Bootstrap the application services.
//     */
//    public function boot()
//    {
//        $this->ensureElasticClientIsInstalled();
//
//        resolve(EngineManager::class)->extend('elasticsearch', function () {
//            return new Engine(
//                ClientBuilder::create()
//                    ->setHosts(config('scout.elasticsearch.hosts'))
//                    ->build()
//            );
//        });
//    }
//
//    /**
//     * Ensure the Elastic API client is installed.
//     *
//     * @return void
//     *
//     * @throws \Exception
//     */
//    protected function ensureElasticClientIsInstalled()
//    {
//        if (class_exists(ClientBuilder::class)) {
//            return;
//        }
//
//        throw new Exception('Please install the Elasticsearch PHP client: elasticsearch/elasticsearch.');
//    }
}

