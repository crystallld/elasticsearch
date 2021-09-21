<?php

namespace Elasticsearch;

use Illuminate\Database\Connection as BaseConnection;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Query\Builder as QueryBuilder;

class Connection extends BaseConnection
{
    /**
     * The MongoDB database handler.
     * @var \Elasticsearch\Database
     */
    protected $db;

    /**
     * The Elasticsearch connection handler.
     * @var \Elasticsearch\Client
     */
    protected $connection;

    /**
     * The Elasticsearch index prefix
     * @var string
     */
    protected $prefix;

    /**
     * Create a new database connection instance.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $this->prefix = $config['prefix']?? null;

        // Create the connection
        $this->connection = $this->createConnection();

        $this->db = $this->connection;

        $this->useDefaultPostProcessor();

        $this->useDefaultSchemaGrammar();

        $this->useDefaultQueryGrammar();
    }

    /**
     * Begin a fluent query against a index.
     * @param string $index
     * @return Query\Builder
     */
    public function index($index)
    {
        return $this->query()->from($index);
    }

    /**
     * Get a new query builder instance.
     *
     * @return Query\Builder
     */
    public function query()
    {
        return new QueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    /**
     * Get the MongoDB database object.
     * @return \Elasticsearch\Database
     */
    public function getDB()
    {
        return $this->db;
    }

    /**
     * return MongoDB object.
     * @return \MongoDB\Client
     */
    public function getClient()
    {
        return $this->connection;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Create a new Elasticsearch connection.
     * @param array $config
     * @return \Elasticsearch\Client
     */
    protected function createConnection(array $config = [])
    {
        $config = $this->config;

        // Build the connection node
        $node = $this->getActiveNode($config);

        $builder = ClientBuilder::create()->setHosts($node);

        $options = Arr::get($config, 'options', []);
        if (!empty($username = $config['username']?? $options['username']?? false)
            && !empty($password = $config['password']?? $options['password']?? false)
        ) {
            $builder->setBasicAuthentication($username, $password);
        }

        return $builder->build();
    }

    /**
     * @inheritdoc
     */
    public function disconnect()
    {
        unset($this->connection);
    }

    /**
     * Create a node from a configuration.
     * @param array $config
     * @return array
     */
    protected function getActiveNode(array $config)
    {
        if (empty($host = $config['host']?? false)) {
            if (empty($nodes = $config['nodes']?? false))
                throw new InvalidArgumentException('nodes not found.');

            $cycle = 0;
            $count = count($nodes);
            do {
                $node = $nodes[array_rand($nodes)];
                if (!empty($host = $node['host']?? false)) break;

                $cycle++;
                if ($cycle == $count) break;
            }while(true);
        }

        $dsn = [
            'host' => $host,
            'port' => $config['port']?? $node['port']?? 9200
        ];

        return $dsn;
    }

    /**
     * @inheritdoc
     */
    public function getElapsedTime($start)
    {
        return parent::getElapsedTime($start);
    }

    /**
     * @inheritdoc
     */
    public function getDriverName()
    {
        return 'elasticsearch';
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultPostProcessor()
    {
        return new Query\Processor();
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultQueryGrammar()
    {
        return new Query\Grammar();
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultSchemaGrammar()
    {
        return new Schema\Grammar();
    }

    /**
     * Dynamically pass methods to the connection.
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->db, $method], $parameters);
    }
}
