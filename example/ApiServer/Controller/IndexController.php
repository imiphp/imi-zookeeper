<?php

declare(strict_types=1);

namespace app\ApiServer\Controller;

use Imi\Aop\Annotation\Inject;
use Imi\Config;
use Imi\ConfigCenter\ConfigCenter;
use Imi\Controller\HttpController;
use Imi\Server\Http\Route\Annotation\Action;
use Imi\Server\Http\Route\Annotation\Controller;
use Imi\Server\Http\Route\Annotation\Route;

/**
 * @Controller("/")
 */
class IndexController extends HttpController
{
    /**
     * @Inject("ConfigCenter")
     */
    protected ConfigCenter $configCenter;

    /**
     * @Action
     * @Route("/")
     *
     * @return mixed
     */
    public function index()
    {
        $this->response->getBody()->write('imi');

        return $this->response;
    }

    /**
     * @Action
     *
     * @return mixed
     */
    public function get()
    {
        return [
            'config' => Config::get('zooKeeper'),
        ];
    }

    /**
     * @Action
     *
     * @return mixed
     */
    public function set(string $name, string $value)
    {
        $this->configCenter->getDriver('zk')->push($name, $value);
    }
}
