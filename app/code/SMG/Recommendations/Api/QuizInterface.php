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

	/**
	 * Process quiz options and build the order project
	 * 
	 * @param string $subscription_plan
	 * @param mixed $data
	 * @param mixed $addons
	 * @return array
	 */
	public function processOrder($subscription_plan, $data, $addons);
}
