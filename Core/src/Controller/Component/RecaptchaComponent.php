<?php
declare(strict_types=1);
namespace Croogo\Core\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Http\Client;

/**
 * Recaptcha Component
 *
 * @package Croogo.Croogo.Controller.Component
 * @category Component
 */
class RecaptchaComponent extends Component
{

    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    const VERSION = 'php_1.1.2';

    private $_controller = null;

    private $_publicKey = '';
    private $_privateKey = '';

    protected $_defaultConfig = [
        'actions' => []
    ];

    /**
     * Constructor
     */
    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        $this->_defaultConfig['modelClass'] = $registry->getController()->getName();
        parent::__construct($registry, $config);
    }

    /**
     * initialize
     */
    public function initialize(array $config): void
    {
        $controller = $this->_registry->getController();
        $this->_controller = $controller;
        if ($controller->getName() === 'CakeError') {
            return;
        }

        if (in_array($controller->getRequest()->getParam('action'), $this->getConfig('actions'))) {
            $controller->Security->validatePost = false;
        }

        $controller->viewBuilder()->setHelpers(['Croogo/Core.Recaptcha']);
    }

    /**
     * startup
     */
    public function startup()
    {
        $this->_publicKey = Configure::read('Service.recaptcha_public_key');
        $this->_privateKey = Configure::read('Service.recaptcha_private_key');

        Configure::write('Recaptcha.pubKey', $this->_publicKey);
        Configure::write('Recaptcha.privateKey', $this->_privateKey);
    }

    /**
     * verify reCAPTCHA
     */
    public function verify()
    {
        $request = $this->getController()->getRequest();
        if ($request->getData('g-recaptcha-response')) {
            $captcha = $request->getData('g-recaptcha-response');
            $response = $this->_getApiResponse($captcha);

            if (!$response->success) {
                $this->_controller->Flash->error($this->_errorMsg($response->{'error-codes'}));

                return false;
            }

            return true;
        }
    }

    /**
     * Get reCAPTCHA response
     *
     * @return array Body of the reCAPTCHA response
     */
    protected function _getApiResponse($captcha): object
    {
        $data = [
            'secret' => $this->_privateKey,
            'response' => $captcha,
            'remoteip' => env('REMOTE_ADDR'),
            'version' => self::VERSION
        ];
        $HttpSocket = new Client();
        $request = $HttpSocket->post(self::SITE_VERIFY_URL, $data);

        return json_decode($request->getBody());
    }

    /**
     * Error message
     */
    protected function _errorMsg($errorCodes = null)
    {
        switch ($errorCodes) {
            case 'missing-input-secret':
                $msg = 'The secret parameter is missing.';
                break;
            case 'invaid-input-secret':
                $msg = 'The secret parameter is invalid or malformed.';
                break;
            case 'missing-input-response':
                $msg = 'The response parameter is missing.';
                break;
            case 'invalid-input-response':
                $msg = 'The response parameter is invalid or malformed.';
                break;
            default:
                $msg = null;
                break;
        }

        return $msg;
    }
}
