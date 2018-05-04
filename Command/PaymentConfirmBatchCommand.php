<?php

namespace Plugin\SimpleNemPay\Command;

use Plugin\SimpleNemPay\Entity\NemOrder;
use Plugin\SimpleNemPay\Entity\NemHistory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * 入金確認実行コマンド
 *
 * app/consoleに要追記
 * $console->add(new Plugin\SimpleNemPay\Command\PaymentConfirmBatchCommand(new Eccube\Application()));
 *
 * crontab  ex. 0 * * * * /usr/bin/php /var/www/html/eccube-3.0.15/app/console simple_nempay:payment_confirm
 */
class PaymentConfirmBatchCommand extends \Knp\Command\Command
{

    private $app;

    public function __construct(\Eccube\Application $app, $name = null)
    {
        parent::__construct($name);
        $this->app = $app;
    }

    protected function configure()
    {
        $this->setName('simple_nempay:payment_confirm')
             ->setDescription('入金確認バッチ処理');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app->initialize();
        $this->app->initializePlugin();
        $this->app->boot();

        $softDeleteFilter = $this->app['orm.em']->getFilters()->getFilter('soft_delete');
        $softDeleteFilter->setExcludes(array(
            'Eccube\Entity\Order'
        ));
        
        // 対象の受注を取得
        $arrNemOrder  = $this->app['eccube.plugin.simple_nempay.repository.nem_order']->getOrderPayWaitForSimpleNemPay();
        if (empty($arrNemOrder)) {
            return;
        }
        
        // キーを変換
        $arrNemOrderTemp = array();
        foreach ($arrNemOrder as $NemOrder) {
            $shortHash = $this->app['eccube.plugin.simple_nempay.service.nem_shopping']->getShortHash($NemOrder->getOrder());
            $arrNemOrderTemp[$shortHash] = $NemOrder;
        }
        $arrNemOrder = $arrNemOrderTemp;
        
        // NEM受信トランザクション取得
        $arrData = $this->app['eccube.plugin.simple_nempay.service.nem_request']->getIncommingTransaction();
		foreach ($arrData as $data) {
            $msg = pack("H*", $data['transaction']['message']['payload']);
            
            // 対象受注
            if (isset($arrNemOrder[$msg])) {
                $NemOrder = $arrNemOrder[$msg];
                $Order = $NemOrder->getOrder();
                
                // トランザクションチェック
                $transaction_id = $data['meta']['id'];
                $NemHistoryes = $NemOrder->getNemHistoryes();
                if (!empty($NemHistoryes)) {
                    $exist_flg = false;
                    foreach ($NemHistoryes as $NemHistory) {
                        if ($NemHistory->getTransactionId() == $transaction_id) {
                            $exist_flg = true;
                        }
                    }
                    
                    if ($exist_flg) {
						$this->app['monolog.simple_nempay']->addInfo("batch error: processed transaction. transaction_id = " . $transaction_id);
                        continue;
                    }       
                }
                
                // トランザクション制御
                $em = $this->app['orm.em'];
                $em->getConnection()->beginTransaction();
                                
                $amount = $data['transaction']['amount'] / 1000000;
                $payment_amount = $NemOrder->getPaymentAmount();
                $confirm_amount = $NemOrder->getConfirmAmount();
                
                $pre_amount = empty($confirm_amount) ? 0 : $confirm_amount;
                $confirm_amount = $pre_amount + $amount;
                $NemOrder->setConfirmAmount($confirm_amount);
                
                $NemHistory = new NemHistory();
                $NemHistory->setTransactionId($transaction_id);
                $NemHistory->setAmount($amount);
                $NemHistory->setNemOrder($NemOrder);

				$this->app['monolog.simple_nempay']->addInfo("batch info: received. order_id = " . $Order->getId() . " amount = " . $amount);

                if ($payment_amount <= $confirm_amount) {
                    $OrderStatus = $this->app['eccube.repository.order_status']->find($this->app['config']['order_pre_end']);
                    $Order->setOrderStatus($OrderStatus);
                    $Order->setPaymentDate(new \DateTime());
					
					$this->sendPayEndMail($Order);
					$this->app['monolog.simple_nempay']->addInfo("batch info: pay end. order_id = " . $Order->getId());
                }
                
                // 更新
                $em->persist($NemHistory);
                $em->commit();
                $em->flush();				
            }
            
		}		
    }

    public function sendPayEndMail($Order)
    {
        $BaseInfo = $this->app['eccube.repository.base_info']->get();
        $name01 = $Order->getName01();
        $name02 = $Order->getName02();
        
        $body = <<<__EOS__
{$name01} {$name02} 様

NEM決済の入金を確認致しました。
__EOS__;

        $message = \Swift_Message::newInstance()
            ->setSubject('【' . $BaseInfo->getShopName() . '】入金確認通知')
            ->setFrom(array($BaseInfo->getEmail03() => $BaseInfo->getShopName()))
            ->setTo(array($Order->getEmail()))
            ->setBody($body);
        $this->app->mail($message);

        $this->app['swiftmailer.spooltransport']->getSpool()->flushQueue($this->app['swiftmailer.transport']);
    }

}
