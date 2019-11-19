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
	 * @return array
     */
	public function new();

	/**
	 * Save quiz ids. This endpoint is not complete.
	 * 
	 * @param mixed $id
	 * @param mixed $answers
	 * @return array
	 */
	public function save($id, $answers);

	/**
	 * Get results by quiz id.
	 * 
	 * @param string $id
	 * @return array
	 */
	public function getResult($id);

	/**
	 * Get completed quizzes
	 * 
	 * @return string
	 */
	public function getCompleted();
}
