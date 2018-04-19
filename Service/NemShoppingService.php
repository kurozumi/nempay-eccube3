<?php

namespace Plugin\NemPay\Service;

use Plugin\NemPay\Entity\NemOrder;
use Eccube\Application;
use Eccube\Entity\MailHistory;
use Eccube\Entity\Order;

require_once(__DIR__.'/../Vendor/Image/QRCode.php');

class NemShoppingService
{
    /** @var \Eccube\Application */
    public $app;

    /** @var array */
    private $nemSettings;

    public function __construct(Application $app, $cartService, $orderService)
    {
        $this->app = $app;

        // NemPay設定値読み込み
        $this->nemSettings = $app['eccube.plugin.nempay.repository.nem_info']->getNemSettings();
    }

    /**
     * 受注メール送信を行う
     *
     * @param Order $Order
     * @return MailHistory
     */
    public function sendOrderMail(Order $Order)
    {
        // メール送信
        $message = $this->app['eccube.plugin.nempay.service.nem_mail']->sendOrderMail($Order);

        // 送信履歴を保存.
        $MailTemplate = $this->app['eccube.repository.mail_template']->find(1);

        $MailHistory = new MailHistory();
        $MailHistory
            ->setSubject($message->getSubject())
            ->setMailBody($message->getBody())
            ->setMailTemplate($MailTemplate)
            ->setSendDate(new \DateTime())
            ->setOrder($Order);

        $this->app['orm.em']->persist($MailHistory);
        $this->app['orm.em']->flush($MailHistory);

        return $MailHistory;

    }

    /**
     * Nemの受注情報を登録
     *
     * @param Order $Order
     */
    public function getNemOrder(Order $Order)
    {
        $NemOrder = $this->app['eccube.plugin.nempay.repository.nem_order']->findOneBy(array('Order' => $Order));
        
        if (empty($NemOrder)) {
            // Nem受注情報を登録
            $NemOrder = new NemOrder();
            $NemOrder->setOrder($Order);
            
            $this->app['orm.em']->persist($NemOrder);
            $this->app['orm.em']->flush();
        }

        return $NemOrder;
    }
    
    public function getPaymentInfo(NemOrder $NemOrder, $msg) {
        $amount = $NemOrder->getPaymentAmount();
        
        $arrData = array();
        $arrData['title']['name'] = 'NEM決済についてのご連絡';
        $arrData['title']['value'] = true;
        $arrData['qr_explain_title']['value'] = '【お支払いについてのご説明】';
        $arrData['qr_explain']['value'] = <<< __EOS__
お客様の注文はまだ決済が完了していません。
お支払い情報に記載されている支払い先アドレスに指定の金額とメッセージを送信してください。
送金から一定時間経過後、本サイトに反映され決済が完了します。

※NanoWalletでQRコードをスキャンするとお支払い情報が読み込まれます。
※メッセージが誤っている場合、注文に反映されませんのご注意ください。
※送金金額が受付時の金額に満たない場合、決済は完了されません。複数回送金された場合は合算した金額で判定されます。

__EOS__;
        $arrData['pay_info']['value'] = '【お支払い情報】';
        $arrData['address']['name'] = '支払先アドレス';
        $arrData['address']['value'] = $this->nemSettings['seller_addr'];
        $arrData['amount']['name'] = 'お支払い金額';
        $arrData['amount']['value'] = $amount . ' XEM';
        $arrData['message']['name'] = 'メッセージ';
        $arrData['message']['value'] = $msg;
        
        return $arrData;
    }
    
    function createQrcodeImage(Order $Order, $NemOrder, $msg) {
        $amount = $NemOrder->getPaymentAmount();
        
        $arrData = array(
            'v' => 2,
            'type' => 2,
            'data' => 
                array (
                    'addr' => $this->nemSettings['seller_addr'],
                    'amount' => $amount * 1000000,
                    'msg' => $msg,
                    'name' => '',
            ),
        );
        
        $filepath = $this->getQrcodeImagePath($Order);

        $qr = new \Image_QRCode();
        $image = $qr->makeCode(json_encode($arrData), 
                               array('output_type' => 'return'));
        imagepng($image, $filepath);
        imagedestroy($image);
    }
    
    function getQrcodeImagePath(Order $Order) {
        return  __DIR__ . '/../Resource/qrcode/'. $Order->getId() . '.png';
    }
    
    function getShortHash(Order $Order) {
        return rtrim(base64_encode(md5($this->nemSettings['seller_addr'] . $Order->getId() . $this->app['config']['auth_magic'], true)), '=');  
    }

}
