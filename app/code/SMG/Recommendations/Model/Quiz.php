<?php

namespace SMG\Recommendations\Model;

use SMG\Recommendations\Api\QuizInterface;

class Quiz implements QuizInterface
{

    /**
     * @var /SMG/Api/Helper/QuizHelper
     */
    protected $_helper;

    public function __construct(
        \SMG\Recommendations\Helper\QuizHelper $helper
    ) {
        $this->_helper = $helper;
    }

    /**
     * Get quiz template and store it's id in session
     *
     * @api
     * @return array|null
     */
    public function new()
    {
        if (! $this->_helper->getNewQuizApiPath()) {
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
     * @param $id
     * @param $answers
     *
     * @return array|null
     * @api
     */
    public function save($id, $answers)
    {
        if (! $this->_helper->getSaveQuizApiPath() || empty($answers) || empty($id)) {
            return null;
        }

        $url = trim($this->_helper->getSaveQuizApiPath(), '/');

        $url = filter_var($url . '/' . $id . '/completeQuiz', FILTER_SANITIZE_URL);
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
     * @param string $quiz_id
     * @return array
     *
     * @api
     */
    public function getResult($quiz_id)
    {

        //getQuizResultApiPath

        if (empty($quiz_id) || $this->_helper->getQuizResultApiPath()) {
            return;
        }

        $quiz_id = filter_var($quiz_id, FILTER_SANITIZE_SPECIAL_CHARS);

        $url = filter_var($this->_helper->getQuizResultApiPath(), FILTER_SANITIZE_URL);
        $data = '';
        $method = 'GET';

        $response = $this->request($url, $data, $method);

        if (! empty($response)) {
            return $response;
        }

        return;
    }

    /**
     * Return completed quizzes
     *
     * @return array
     *
     * @api
     */
    public function getCompleted()
    {
        if (! $this->_helper->getCompletedQuizApiPath()) {
            return;
        }

        $url = filter_var($this->_helper->getCompletedQuizApiPath(), FILTER_SANITIZE_URL);
        $data = '';
        $method = 'GET';

        $response = $this->request($url, $data, $method);

        if (! empty($response)) {
            return $response;
        }

        return;
    }

    /**
     * cURL wrapper
     *
     * @param string $url
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
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
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
