<?php

namespace Plugin\NemPay\Repository;

use Doctrine\ORM\EntityRepository;

class NemOrderRepository extends EntityRepository
{
    protected $app;

    public function setApplication($app)
    {
        $this->app = $app;
    }
    
    public function getOrderPayWaitForNemPay()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        
        // 入金待ち
        $OrderStatus = $this->app['eccube.repository.order_status']->find($this->app['config']['order_pay_wait']);
        
        $qb
            ->select('no')
            ->from('\Plugin\NemPay\Entity\NemOrder', 'no')
            ->innerJoin('\Eccube\Entity\Order', 'o', 'WITH', 'o = no.Order')
            ->andWhere('o.OrderStatus = :OrderStatus')
            ->setParameter('OrderStatus', $OrderStatus); 
            
        return $qb
            ->getQuery()
            ->getResult();
    }
}
