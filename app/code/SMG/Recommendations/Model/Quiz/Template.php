<?php

namespace SMG\Recommendations\Model\Quiz;

use SMG\Recommendations\Api\Quiz\TemplateInterface;

class Template implements TemplateInterface
{

  	/**
     * Returns response from LSPaaS
     *
     * @api
     */
	public function get() {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://lspaasdraft.azurewebsites.net/api/quizzes/template');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    	curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    	curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      		'Content-Type: application/json; charset=utf-8',
      		'Accept: application/json',
    	));
    	$response = curl_exec($ch);
    	curl_close($ch);

    	$data = json_decode($response, true);
    	if( ! isset( $_SESSION['quiz_template_id'] ) ) {
    		$_SESSION['quiz_template_id'] = $data['id'];
    	}
    	
    	return json_decode($response, true);
	}
}