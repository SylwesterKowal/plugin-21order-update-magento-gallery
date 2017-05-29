<?php

namespace MagentoGalleryUpdate\Shell\Task;

use Cake\Console\Shell;
use MagentoGalleryUpdate\Shell\Task\QueueUpdateImagesToMagentoTask;
use Queue\Shell\Task\QueueTask;
use Cake\ORM\TableRegistry;

/**
 * AssignManufacturerInitTask shell task.
 */
class QueueUpdateImagesToMagentoInitTask extends QueueTask
{
    /**
     * @var \Queue\Model\Table\QueuedTasksTable
     */
    public $QueuedTask;

    protected $ProductStocksMagento;

    /**
     * main() method.
     *
     * @return bool|int|null Success or error code.
     */
    public function add()
    {
        $data = [];

        return (bool)$this->QueuedJobs->createJob('AssignManufacturerInit', $data);
    }

    public function run(array $data, $id)
    {

        try {
            $this->ProductStocksMagento = TableRegistry::get('Product.ProductStocksMagento');
            if ($products = $this->ProductStocksMagento->find()
                ->contain(['ProductStocks.Products.Uploads'])
                ->limit(1)
                ->toArray()
            ) {

                $updateImagesToMagento = new QueueUpdateImagesToMagentoTask();

                debug($products);
                
                foreach ($products as $key => $product) {
                    if (isset($product->product_stock->product->uploads->name)
                        && isset($product->user_firm_store_id)
                        && isset($product->magento_product_id)
                    )
                        $updateImagesToMagento
                            ->setUserFirmStoreId($product->user_firm_store_id)
                            ->setMagentoProductId($product->magento_product_id)
                            ->setUpload($product->product_stock->product->uploads)
                            ->add();

                }
            }

        } catch (Exception $e) {
            return false;
        }

        return true;


    }
}
