<?php

namespace Plugin\NemPay\ServiceProvider;

use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;

class NemPayServiceProvider implements ServiceProviderInterface
{

    public function register(BaseApplication $app)
    {
        // Setting
        $app->match('/' . $app["config"]["admin_route"] . '/plugin/NemPay/config', '\\Plugin\\NemPay\\Controller\\Admin\\ConfigController::index')->bind('plugin_NemPay_config');

        // shopping
        $app->match('/shopping/nem_pay', '\Plugin\NemPay\Controller\NemShoppingController::index')->bind('shopping_nem_pay');
        $app->match('/shopping/nem_pay/back', '\Plugin\NemPay\Controller\NemShoppingController::back')->bind('shopping_nem_pay_back');

        // Service
        $app['eccube.plugin.nempay.service.nem_request'] = $app->share(function () use ($app) {
            return new \Plugin\NemPay\Service\NemRequestService($app);
        });
        $app['eccube.plugin.nempay.service.nem_shopping'] = $app->share(function () use ($app) {
            return new \Plugin\NemPay\Service\NemShoppingService($app, $app['eccube.service.cart'], $app['eccube.service.order']);
        });
        $app['eccube.plugin.nempay.service.nem_mail'] = $app->share(function () use ($app) {
            return new \Plugin\NemPay\Service\NemMailService($app);
        });

        // Repository
        $app['eccube.plugin.nempay.repository.nem_info'] = $app->share(function () use ($app) {
            $nemInfoRepository = $app['orm.em']->getRepository('Plugin\NemPay\Entity\NemInfo');
            $nemInfoRepository->setApplication($app);

            return $nemInfoRepository;
        });
        $app['eccube.plugin.nempay.repository.nem_order'] = $app->share(function () use ($app) {
            $nemOrderRepository = $app['orm.em']->getRepository('Plugin\NemPay\Entity\NemOrder');
            $nemOrderRepository->setApplication($app);

            return $nemOrderRepository;
        });
        $app['eccube.plugin.nempay.repository.nem_history'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\NemPay\Entity\NemHistory');
        });
        
        // form
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new \Plugin\NemPay\Form\Type\NemPayType($app);
            return $types;
        }));

        // log file
        $app['monolog.nempay'] = $app->share(function ($app) {

            $logger = new $app['monolog.logger.class']('NemPay');

            $file = $app['config']['root_dir'] . '/app/log/NemPay.log';
            $RotateHandler = new RotatingFileHandler($file, $app['config']['log']['max_files'], Logger::INFO);
            $RotateHandler->setFilenameFormat(
                'NemPay_{date}',
                'Y-m-d'
            );

            $logger->pushHandler(
                new FingersCrossedHandler(
                    $RotateHandler,
                    new ErrorLevelActivationStrategy(Logger::INFO)
                )
            );

            return $logger;
        });
    }

    public function boot(BaseApplication $app)
    {
    }
}

 ?>
