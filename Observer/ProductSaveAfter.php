<?php

namespace ProxiBlue\SimilarSkuUpdate\Observer;

use Magento\Framework\Event\ObserverInterface;

class ProductSaveAfter extends Common implements ObserverInterface
{

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $product = $observer->getProduct();
            $this->updateProduct($product, true);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}