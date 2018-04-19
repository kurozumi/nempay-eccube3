<?php

namespace Plugin\NemPay\Controller\Admin;

use Eccube\Application;
use Plugin\NemPay\Form\Type\Admin\ConfigType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

class ConfigController
{

    private $app;
    private $const;

    public function index(Application $app, Request $request)
    {
        $this->app = $app;
        $this->const = $app['config']['NemPay']['const'];

        $nemSettings = $app['eccube.plugin.nempay.repository.nem_info']->getNemSettings();
        $configFrom = new ConfigType($this->app, $nemSettings);
        $form = $this->app['form.factory']->createBuilder($configFrom)->getForm();

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $formData = $form->getData();

                // 設定値を登録
                $app['eccube.plugin.nempay.repository.nem_info']->registerSettings($formData);

                $app->addSuccess('admin.register.complete', 'admin');
                return $app->redirect($app['url_generator']->generate('plugin_NemPay_config'));
            }
        }

        return $this->app['view']->render('NemPay/Twig/admin/config.twig',
            array(
                'form' => $form->createView(),
            ));
    }
}
