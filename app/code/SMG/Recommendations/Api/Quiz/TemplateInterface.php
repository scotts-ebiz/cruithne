<?php
namespace SMG\Recommendations\Api\Quiz;

/**
 * @api
 */
interface TemplateInterface
{
	 /**
     * Retrieve quiz templates from LSPaaS 
     *
	 * @return string
     */
	public function get();
}