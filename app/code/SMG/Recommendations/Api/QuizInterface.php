<?php
namespace SMG\Recommendations\Api;

/**
 * @api
 */
interface QuizInterface
{

	/**
     * Retrieve quiz templates from LSPaaS 
     *
	 * @return string
     */
	public function new();

	/**
	 * Save quiz ids. This endpoint is not complete.
	 * 
	 * @param mixed $ids
	 * @return array
	 */
	public function save($ids);
}