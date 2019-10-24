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

        if( ! empty( $this->request( $url, $data, 'GET' ) ) )
        {
            $response = $this->request( $url, $data, 'GET' );

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