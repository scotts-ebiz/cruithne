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
    )
    {
        $this->_helper = $helper;
    }

    /**
     * Get quiz template and store it's id in session
     * 
     * @api
     */
    public function new()
    {

        if( ! $this->_helper->getNewQuizApiPath() ) {
            return;
        }

        $url = filter_var($this->_helper->getNewQuizApiPath(), FILTER_SANITIZE_URL);
        $data = '';
        $method = 'GET';

        $response = $this->request( $url, $data, $method );

        if( ! empty( $response ) )
        {
            if( ! isset( $_SESSION['quiz_template_id'] ) ) {
                $_SESSION['quiz_template_id'] = $response['id'];
            }

            return $response;
        }

        return;
    }

    /**
     * Send answers to complete quiz
     * 
     * @api
     */
    public function save($quiz_template_id, $data)
    {
        if( ! $this->_helper->getSaveQuizApiPath() || empty( $data ) ) {
            return;
        }

        $quiz_template_id = filter_var( $quiz_template_id, FILTER_SANITIZE_SPECIAL_CHARS );

        $url = filter_var( $this->_helper->getSaveQuizApiPath() . '/' . $quiz_template_id . '/completeQuiz', FILTER_SANITIZE_URL );
        $method = 'POST';

        $response = $this->request( $url, $data, $method );

        if( ! empty( $response ) ) {
            return $response;
        }

        return;
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

        if( empty( $quiz_id ) || $this->_helper->getQuizResultApiPath() ) {
            return;
        }

        $quiz_id = filter_var( $quiz_id, FILTER_SANITIZE_SPECIAL_CHARS );

        $url = filter_var($this->_helper->getQuizResultApiPath(), FILTER_SANITIZE_URL);
        $data = '';
        $method = 'GET';

        $response = $this->request( $url, $data, $method );

        if( ! empty( $response ) ) {
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

        if( ! $this->_helper->getCompletedQuizApiPath() ) {
            return;
        }


        $url = filter_var($this->_helper->getCompletedQuizApiPath(), FILTER_SANITIZE_URL);
        $data = '';
        $method = 'GET';

        $response = $this->request( $url, $data, $method );

        if( ! empty( $response ) ) {
            return $response;
        }

        return;
    }

    /**
     * cURL wrapper
     * 
     * @param string $url
     * @param string $method
     * @return array
     */
    private function request( $url, $data, $method = '' )
    {
        if( ! empty( $url ) ) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_TIMEOUT, 45);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER,TRUE);
                if( $method == 'POST' ) {
                    curl_setopt($ch, CURLOPT_POST, TRUE);
                    if( ! empty( $data ) ) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    }
                } elseif( $method == 'PUT' ) {
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                    if( ! empty( $data ) ) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    }
                } else {
                    curl_setopt($ch, CURLOPT_POST, FALSE);
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json; charset=utf-8',
                    'Accept: application/json',
                ));
                $response = curl_exec($ch);

                if(curl_errno($ch)) {
                    throw new Exception(curl_error($ch));
                }

                curl_close($ch);

                return json_decode($response, true);
            } catch(Exception $e) {
                throw new Exception($e);
            }
        }

        return;
    }
}