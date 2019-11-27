<?php

namespace SMG\RecommendationApi\Model;

use Elasticsearch\Common\Exceptions\Forbidden403Exception;
use Magento\Framework\Exception\SecurityViolationException;
use SMG\RecommendationApi\Api\RecommendationInterface;

class Recommendation implements RecommendationInterface
{

    /**
     * @var /SMG/RecommendationApi/Helper/RecommendationHelper
     */
    protected $_helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $_formKey;

    /**
     * @var \Magento\Framework\Webapi\Request
     */
    protected $_request;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_jsonResultFactory;

    /**
     * Quiz constructor.
     * @param \SMG\RecommendationApi\Helper\RecommendationHelper $helper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Magento\Framework\Webapi\Request $request
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     */
    public function __construct(
        \SMG\RecommendationApi\Helper\RecommendationHelper $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\Webapi\Request $request,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
    ) {
        $this->_helper = $helper;
        $this->_storeManager = $storeManager;
        $this->_formKey = $formKey;
        $this->_request = $request;
        $this->_jsonResultFactory = $jsonResultFactory;

        // Check to make sure that the module is enabled at the store level
        if ( ! $this->_helper->isActive($this->_storeManager->getStore()->getId())) {
            throw new \Magento\Framework\Exception\NotFoundException(__('File not Found'));
        }
    }

    /**
     * Get quiz template and store it's id in session
     *
     * @param string $key
     * @return array|void
     * @throws SecurityViolationException
     *
     * @api
     */
    public function new($key)
    {

        // Test the form key
        if ($this->formValidation($key)) {
            throw new SecurityViolationException(__('Unauthorized'));
        }

        if ( ! $this->_helper->getNewQuizApiPath()) {
            return;
        }

        $url = filter_var($this->_helper->getNewQuizApiPath(), FILTER_SANITIZE_URL);
        $data = '';
        $method = 'GET';

        $response = $this->request($url, $data, $method);

        if (! empty($response)) {
            if (! isset($_SESSION['quiz_template_id'])) {
                $_SESSION['quiz_template_id'] = $response[0]['id'];
            }

            return $response;
        }

        return;
    }

    /**
     * Send answers to complete quiz
     *
     * @param mixed $key
     * @param mixed $id
     * @param $answers
     * @return array|null
     * @throws \Magento\Framework\Exception\SecurityViolationException
     *
     * @api
     */
    public function save($key, $id, $answers)
    {

        // Test the form key
        if ($this->formValidation($key)) {
            throw new SecurityViolationException(__('Unauthorized'));
        }

        if (! $this->_helper->getSaveQuizApiPath() || empty($answers) || empty($id)) {
            return null;
        }

        $url = trim($this->_helper->getSaveQuizApiPath(), '/');
        $url = str_replace('{quizTemplateId}', $id, $url);
        $baseUrl = filter_var($url, FILTER_SANITIZE_URL);
        $method = 'POST';

        $response = $this->request($url, ['answers' => $answers], $method);

        if (! empty($response)) {
            return $response;
        }

        return null;
    }

    /**
     * Returns quiz data by id.
     *
     * @param mixed $key
     * @param string $id
     * @return array
     * @throws \Magento\Framework\Exception\SecurityViolationException
     *
     * @api
     */
    public function getResult($key, $id)
    {

        // Test the form key
        if ($this->formValidation($key)) {
            throw new SecurityViolationException(__('Unauthorized'));
        }

        //getQuizResultApiPath
        if (empty($id) || ! $this->_helper->getQuizResultApiPath()) {
            return;
        }

        $id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);

        $url = filter_var(
            trim(
                str_replace('{completedQuizId}', $id, $this->_helper->getQuizResultApiPath()),
                '/'
            ),
            FILTER_SANITIZE_URL
        );

        $response = $this->request($url, '', 'GET');

        if (! empty($response)) {
            return $response;
        }

        return;
    }

    /**
     * Return completed quizzes
     *
     * @param mixed $key
     * @return array
     * @throws \Magento\Framework\Exception\SecurityViolationException
     *
     * @api
     */
    public function getCompleted($key)
    {

        // Test the form key
        if ($this->formValidation($key)) {
            throw new SecurityViolationException(__('Unauthorized'));
        }

        if ( ! $this->_helper->getCompletedQuizApiPath()) {
            return;
        }

        $url = filter_var($this->_helper->getCompletedQuizApiPath(), FILTER_SANITIZE_URL);
        $data = '';
        $method = 'GET';

        $response = $this->_request($url, $data, $method);

        if (! empty($response)) {
            return $response;
        }

        return;
    }

    /**
     * Map the quiz to the user
     *
     * @param string $key
     * @param string $user_id
     * @param string $quiz_id
     * @return bool|string|void
     * @throws SecurityViolationException
     *
     * @api
     */
    public function mapToUser($key, $user_id, $quiz_id)
    {

        // Test the form key
        if ($this->formValidation($key)) {
            throw new SecurityViolationException(__('Unauthorized'));
        }

        // Make sure we have a path
        if (!$this->_helper->getMapToUserPath()) {
            return;
        }

        if (empty($user_id) || empty($quiz_id)) {
            return;
        }

        try {

            $url = 'https://lspaasdraft.azurewebsites.net/api/completedQuizzes/mapToUser';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'x-userid: ' . $user_id,
            ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, array($quiz_id));

            $response = curl_exec($ch);
            curl_close($ch);

            return $response;
        } catch (Exception $e) {
            echo $e->getMessage() . ' (' . $e->getCode() . ')';
        }
    }

    /**
     * Test the form key for CSRF form validation
     *
     * @param $key
     * @return bool
     */
    public function formValidation($key) {
        return $this->_formKey->getFormKey() !== $key;
    }

    /**
     * Curl wrapper
     *
     * @param string $url
     * @param $data
     * @param string $method
     * @return array|null
     */
    private function request($url, $data, $method = '')
    {
        if (! empty($url)) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_TIMEOUT, 45);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                if ($method == 'POST') {
                    curl_setopt($ch, CURLOPT_POST, true);
                    if (! empty($data)) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    }
                } elseif ($method == 'PUT') {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                    if (! empty($data)) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    }
                } else {
                    curl_setopt($ch, CURLOPT_POST, false);
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json; charset=utf-8',
                    'Accept: application/json',
                ]);
                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    throw new Exception(curl_error($ch));
                }

                curl_close($ch);

                // Wrap in an array because Magento strips off the top level
                // keys for some random reason.
                return [json_decode($response, true)];
            } catch (Exception $e) {
                throw new Exception($e);
            }
        }

        return;
    }

}
