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
	 * @param mixed $quiz_template_id
	 * @param mixed $answers
	 * @return array
	 */
	public function save($quiz_template_id, $answers);

	/**
	 * Get results by quiz id.
	 * 
	 * @param string $quiz_id
	 * @return string
	 */
	public function getResult($quiz_id);

	/**
	 * Get completed quizzes
	 * 
	 * @return string
	 */
	public function getCompleted();
}