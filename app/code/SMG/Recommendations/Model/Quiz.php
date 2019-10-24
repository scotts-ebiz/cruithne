<?php

namespace SMG\Recommendations\Model;

use SMG\Recommendations\Api\QuizInterface;

class Quiz implements QuizInterface
{

    /**
     * Get quiz template and store it's id in session
     * 
     * @api
     */
    public function new()
    {
        $url = 'https://lspaasdraft.azurewebsites.net/api/quizzes/template';
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
     * This endpoint is not complete.
     * 
     * { "ids": [1, 2] } in body
     * 
     * @api
     */
    public function save($ids)
    {
        return array( array( 'status' => 200 ) );
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

        if( empty( $quiz_id ) ) {
            return;
        }

        $url = 'https://lspaasdraft.azurewebsites.net/api/completedQuizzes/cdaf7de7-115c-41be-a7e4-3259d2f511f8';
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
        $url = 'https://lspaasdraft.azurewebsites.net/api/completedQuizzes';
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
            curl_close($ch);

            return json_decode($response, true);
        }

        return;
    }
}