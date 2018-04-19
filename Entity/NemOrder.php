<?php

namespace Plugin\NemPay\Entity;

class NemOrder extends \Eccube\Entity\AbstractEntity
{

    /**
     * @var integer
     */
    private $id;

    /**
     * @var Order
     */
    private $Order;

    /**
     * @var float
     */
    private $rate;

    /**
     * @var float
     */
    private $payment_amount;

    /**
     * @var float
     */
    private $confirm_amount;
    
    /**
     * @var float
     */
    private $payment_info;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $NemHistoryes;

    /**
     * Set id
     *
     * @param integer $id
     * @return NemOrder
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set Order
     *
     * @param  \Eccube\Entity\Order $order
     * @return NemOrder
     */
    public function setOrder(\Eccube\Entity\Order $order)
    {
        $this->Order = $order;

        return $this;
    }

    /**
     * Get Order
     *
     * @return \Eccube\Entity\Order
     */
    public function getOrder()
    {
        return $this->Order;
    }

    /**
     * Set rate
     *
     * @param  float rate
     * @return NemOrder
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * Get rate
     *
     * @return float
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * Set payment_amount
     *
     * @param  float $paymentAmount
     * @return NemOrder
     */
    public function setPaymentAmount($paymentAmount)
    {
        $this->payment_amount = $paymentAmount;

        return $this;
    }

    /**
     * Get payment_amount
     *
     * @return float
     */
    public function getPaymentAmount()
    {
        return $this->payment_amount;
    }

    /**
     * Set confirm_amount
     *
     * @param  float $confirmAmount
     * @return NemOrder
     */
    public function setConfirmAmount($confirmAmount)
    {
        $this->confirm_amount = $confirmAmount;

        return $this;
    }

    /**
     * Get payment_info
     *
     * @return string
     */
    public function getPaymentInfo()
    {
        return $this->payment_info;
    }
    
    /**
     * Set payment_info
     *
     * @param  string $paymentInfo
     * @return NemOrder
     */
    public function setPaymentInfo($paymentInfo)
    {
        $this->payment_info = $paymentInfo;

        return $this;
    }

    /**
     * Get confirm_amount
     *
     * @return float
     */
    public function getConfirmAmount()
    {
        return $this->confirm_amount;
    }

    /**
     * Add NemHistoryes
     *
     * @param  \Plugin\NemPay\Entity\NemHistory $NemHistory
     * @return Order
     */
    public function addNemHistory(\Plugin\NemPay\Entity\NemHistory $NemHistory)
    {
        $this->NemHistoryes[] = $NemHistory;

        return $this;
    }

    /**
     * Get NemHistoryes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNemHistoryes()
    {
        return $this->NemHistoryes;
    }
}
