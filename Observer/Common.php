<?php

namespace ProxiBlue\SimilarSkuUpdate\Observer;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\State;


abstract class Common
{
    protected $stockRegistry;
    protected $searchCriteriaBuilder;
    protected $logger;
    protected $productCollectionFactory;
    protected $messageManager;
    private  $state;

    public function __construct(
        StockRegistryInterface $stockRegistry,
        LoggerInterface $logger,
        CollectionFactory $productCollectionFactory,
        ManagerInterface $messageManager,
        State $state
    )
    {
        $this->stockRegistry = $stockRegistry;
        $this->logger = $logger;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->messageManager = $messageManager;
        $this->state = $state;
    }


    /**
     * Adjust similar skus,
     * force if checkout, since QTY woudl have decreased, and original data is already set to deducted value
     * @param $product
     * @param bool $force
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateProduct($product, $force = false)
    {
        if ($product->getTypeId() == 'simple') {
            $sku = $product->getSku();
            $skuParts = [];
            preg_match_all( '/([^\-\_!   ?]*[\-\_!?])/', $sku , $skuParts );
            if (count($skuParts) > 1) {
                $stockItem = $this->stockRegistry->getStockItemBySku($sku);
                $originalData = $product->getOrigData();
                if (isset($originalData['quantity_and_stock_status'])
                    && isset($originalData['quantity_and_stock_status']['qty'])) {
                    $qty = $stockItem->getQty();
                    if ($qty != $originalData['quantity_and_stock_status']['qty'] || $force) {
                        if(isset($skuParts[0]) && isset($skuParts[0][0]) and strlen($skuParts[0][0]) > 4) {
                            $lookupSku = $skuParts[0][0];
                            $exactSku = substr($lookupSku,0,-1);
                            $collection = $this->productCollectionFactory->create()
                                ->addAttributeToFilter(
                                    [
                                        ['attribute'=> 'sku','like' => str_replace(['_'],['\_'], $lookupSku) . '%' ],
                                        ['attribute'=> 'sku','eq' => str_replace(['_'],['\_'], $exactSku) ]
                                    ]
                                )
                                ->addAttributeToFilter('sku', array('neq' => $sku))
                                ->addStoreFilter()
                                ->load();
                            $count = $collection->count();
                            $this->logger->notice((string) $collection->getSelect());
                            if ($count > 0) {
                                $this->handleData($count,$collection, $sku, $qty);
                            }
                        } else {
                            // need to check if this sku has potentials upscale similar ones
                            $collection = $this->productCollectionFactory->create()
                                ->addAttributeToFilter(
                                    [
                                        ['attribute'=> 'sku','like' => str_replace(['_'],['\_'], $sku) . '-%' ],
                                        ['attribute'=> 'sku','like' => str_replace(['_'],['\_'], $sku) . '_%' ],
                                    ]
                                )
                                ->addAttributeToFilter('sku', array('neq' => $sku))
                                ->addStoreFilter()
                                ->load();
                            $count = $collection->count();
                            $this->logger->notice((string) $collection->getSelect());
                            if ($count > 0) {
                                $this->handleData($count,$collection, $sku, $qty);
                            }
                        }
                    }
                }
            }
        }

    }

    private function handleData($count,$collection, $sku, $qty) {
        $this->logger->notice("{$count} Similar Skus detected to {$sku}");
        $updatedSku = [];
        foreach ($collection as $changeProduct) {
            $stockItem = $this->stockRegistry->getStockItemBySku($changeProduct->getSku());
            $stockItem->setQty($qty);
            //$stockItem->setIsInStock((bool)$qty);
            $this->stockRegistry->updateStockItemBySku($changeProduct->getSku(), $stockItem);
            $this->logger->notice("Updating {$changeProduct->getSku()} stock value to {$qty}");
            $updatedSku[] = $changeProduct->getSku();
        }
        if(count($updatedSku) > 0) {
            if(isset($_POST['namespace']) && $_POST['namespace'] == 'product_listing'){
                return;
            }
            $areaCode = $this->state->getAreaCode();
            if($areaCode == \Magento\Framework\App\Area::AREA_ADMINHTML) {
                $this->messageManager->addNotice('Alternative products on SKUs were also updated: ' . implode(', ', $updatedSku));
            }
        }
    }
}