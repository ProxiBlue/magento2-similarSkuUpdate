<?php

namespace ProxiBlue\SimilarSkuUpdate\Observer;

use Magento\Framework\Event\ObserverInterface;


class ProcessOrder extends Common implements ObserverInterface
{

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            foreach($order->getAllItems() as $item) {
                $product = $item->getProduct();
                $this->updateProduct($product, true);
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}