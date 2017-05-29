<?php

namespace MagentoGalleryUpdate\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Exception;
use SoapClient;
use SoapFault;
use Usermgmt\Model\Entity\UserFirmStore;

/**
 * ConnectServices component
 */
class ConnectServicesComponent extends Component
{

    public $soapVersion = 1;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [];
    /**
     * Nazwa użytkownika API Magento
     *
     * @var null
     */
    private $apiUser = null;

    /**
     * Klucz API Magento
     *
     * @var null
     */
    private $apiKey = null;

    /**
     * Adres URL API Magento
     *
     * @var null
     */
    private $magentoUrl = null;

    /**
     * Numer Sessji
     *
     * @var null
     */
    protected $sessionId = null;

    /**
     * Client API Magento
     *
     * @var null
     */
    protected $client = null;

    /**
     * Magazyn 21order.com
     *
     * @var null
     */
    private $userFirmStore = null;

    /**
     * ID uzytkownika po stronie Magento który ma uprawnienia partnera
     *
     * @var null
     */
    private $magentoPartnerId = null;

    /**
     * Kategorie Magento
     *
     * @var array
     */
    protected $categories = [];

    /**
     * Tablica widoków w sklepie Magento
     *
     * @var array
     */
    private $websites = [];

    /**
     * Ustawienie wartości
     *
     * @param UserFirmStore $userFirmStore
     *
     * @return $this
     * @throws Exception
     */
    public function setInstance(UserFirmStore $userFirmStore)
    {
        $this->validateCredentials($userFirmStore);

        $this->apiUser = (string)$userFirmStore->api_user;
        $this->apiKey = (string)$userFirmStore->api_key;
        $this->magentoUrl = (string)$userFirmStore->domain;
        $this->magentoPartnerId = (int)$userFirmStore->magento_partner_id;
        $this->userFirmStore = $userFirmStore;

        return $this;
    }

    public function uploadImagesToMagento($data)
    {
        try {
            $data = [json_encode($data)];
            $this->initConnection();

            $raw_image = file_get_contents($data['path'] . $data['unique_filename']);
            $base64 = base64_encode($raw_image);

            $productId = $data['magento_product_id'];
            $file = array(
                'content' => $base64,
                'mime' => $data['mimetype']
            );

            if ($this->soapVersion == 1) {
                $response = $this->client->call($this->sessionId, 'deleteproductmedia_api.deleteallproductmedia', $data['magento_product_id']);

                $result = $this->client->call(
                    $this->sessionId,
                    'catalog_product_attribute_media.create',
                    [
                        'file' => $file,
                        'label' => $data['label'],
                        'position' => $data['order_image'],
                        'types' => (($data['main_image']) ? ['image', 'small_image', 'thumbnail'] : null),
                        'exclude' => 0
                    ]
                );

            } else {
                #TODO przygotować połacznee SOAP2
//                $response = $this->client->Deleteallproductmedia($this->sessionId, $data);
//
//
//                $result = $this->client->catalogProductAttributeMediaCreate(
//                    $this->sessionId,
//                    $productId,
//                    [
//                        'file' => $file,
//                        'label' => $data['label'],
//                        'position' => $data['order_image'],
//                        'types' => (($data['main_image']) ? ['image', 'small_image', 'thumbnail'] : null),
//                        'exclude' => 0
//                    ]
//                );
            }
        } catch (SoapFault $e) {
            debug($e->getMessage());
            die;
        }

        $this->closeConnection();

        return $response;
    }

    /**
     * Weryfikacja danych do logowania API Magento
     *
     * @param UserFirmStore $userFirmStore
     *
     * @throws Exception
     */
    private
    function validateCredentials(UserFirmStore $userFirmStore)
    {
        if (empty($userFirmStore->api_user) OR empty($userFirmStore->api_key) OR empty($userFirmStore->domain)) {
            throw new Exception('Invalid store credentials');
        }
    }


    /**
     * Połączenie z API Magento
     */
    protected
    function initConnection()
    {
        try {
            if ($this->soapVersion == 1) {
                $this->client = new SoapClient($this->magentoUrl . '/api/soap/?wsdl');

            } else {
                $this->client = new SoapClient($this->magentoUrl . '/api/v2_soap/?wsdl');
            }
            $this->sessionId = $this->client->login($this->apiUser, $this->apiKey);

        } catch (SoapFault $e) {
            debug($e->getMessage());
            die;
        }
    }

    /**
     * Zamknięcie połaczenia z API Magento
     */
    private
    function closeConnection()
    {
        $this->client->endSession($this->sessionId);
    }


    /**
     * Połącznie z Magento
     * @return $this
     */
    public
    function connect()
    {
        $this->initConnection();
        return $this;
    }

    /**
     * Zakończenie połączneia z Magento
     * @return $this
     */
    public
    function disconnect()
    {
        $this->closeConnection();
    }
}
