<?php

namespace MagentoGalleryUpdate\Shell\Task;

use Cake\Console\Shell;
use MagentoGalleryUpdate\Controller\Component\ConnectServicesComponent;
use Queue\Shell\Task\QueueTask;
use Cake\Controller\ComponentRegistry;

/**
 * AssignManufacturerTask shell task.
 */
class QueueUpdateImagesToMagentoTask extends QueueTask
{
    /**
     * @var \Queue\Model\Table\QueuedTasksTable
     */
    public $QueuedTask;
    public $dataUpload;

    private $user_firm_store_id, $magento_product_id, $manufacturer_name;

    public function setUserFirmStoreId($ufsID)
    {
        $this->user_firm_store_id = $ufsID;
        return $this;
    }

    public function setMagentoProductId($magentoProductID)
    {
        $this->magento_product_id = $magentoProductID;
        return $this;
    }

    public function setUpload($upload)
    {
        $this->dataUpload = [
            'user_firm_store_id' => $this->user_firm_store_id,
            'product_stock_id' => $this->productStock->id,
            'magento_product_id' => $this->magento_product_id,
            'unique_filename' => $upload->unique_filename,
            'mimetype' => $upload->mimetype,
            'path' => WWW_ROOT . "library" . DS . 'productphotos' . DS . $upload->subfolder . DS,
            'label' => $upload->_joinData->label,
            'order_image' => $upload->_joinData->order_image,
            'file_type_id' => $upload->_joinData->file_type_id,
            'main_image' => $upload->_joinData->main_image,
        ];
        return $this;
    }

    /**
     * main() method.
     *
     * @return bool|int|null Success or error code.
     */
    public function add()
    {
        return (bool)$this->QueuedJobs->createJob('UpdateImagesToMagento', $this->dataUpload);
    }

    public function run(array $data, $id)
    {
        try {
            $this->loadModel('Usermgmt.UserFirmStores');
            $userFirmStore = $this->UserFirmStores->find()
                ->where(['UserFirmStores.id' => $data['user_firm_store_id']])
                ->first();
            if ($userFirmStore) {
                $this->ConnectServices = new ConnectServicesComponent(new ComponentRegistry());

                $this->ConnectServices
                    ->setInstance($userFirmStore)
                    ->connect()
                    ->uploadImagesToMagento($data);

                $this->ConnectServices->disconnect();

            }
        } catch (Exception $e) {
            return false;
        }

        return true;


    }
}
