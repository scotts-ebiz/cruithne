<?php

namespace SMG\RecommendationApi\Api\Interfaces;

/**
 * Interface RecommendationInterface
 * @package SMG\RecommendationApi\Api\Interfaces
 * @api
 */
interface RecommendationInterface
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
     * @param string $zip
     * @param string $lawnType
     * @param string $lawnSize
     * @return array
     */
    public function save($key, $id, $answers, $zip, $lawnType, $lawnSize);

    /**
     * Get results by quiz id.
     *
     * @param string $key
     * @param string $id
     * @param string $zip
     * @param string $lawnType
     * @param int $lawnSize
     * @return array
     */
    public function getResult($key, $id, $zip, $lawnType = '', $lawnSize = 0);

    /**
     * Get completed quizzes
     *
     * @param string $key
     * @return string
     */
    public function getCompleted($key);

    /**
     * Map quiz to user
     *
     * @param string $key
     * @param string $user_id
     * @param string $quiz_id
     * @return string
     */
    public function mapToUser($key, $user_id, $quiz_id);

    /**
     * Get product flat file information
     *
     * @param string $key
     * @return array
     */
    public function getProducts($key);
}
