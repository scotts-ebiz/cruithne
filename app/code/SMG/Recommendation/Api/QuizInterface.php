<?php
namespace SMG\Recommendation\Api;

/**
 * @api
 */
interface QuizInterface
{

	/**
     * Retrieve quiz templates from LSPaaS 
     *
     * @param string $key
	 * @return array
     */
	public function new($key);

	/**
	 * Save quiz ids. This endpoint is not complete.
	 *
     * @param string $key
	 * @param mixed $id
	 * @param mixed $answers
	 * @return array
	 */
	public function save($key, $id, $answers);

	/**
	 * Get results by quiz id.
	 *
     * @param string $key
	 * @param string $id
	 * @return array
	 */
	public function getResult($key, $id);

    /**
     * Get completed quizzes
     *
     * @param string $key
     * @return string
     */
	public function getCompleted($key);

}
