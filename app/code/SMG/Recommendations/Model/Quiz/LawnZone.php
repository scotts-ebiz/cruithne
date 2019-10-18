<?php

namespace SMG\Recommendations\Model\Quiz;

use SMG\Recommendations\Api\Quiz\LawnZoneInterface;

class LawnZone implements LawnZoneInterface
{

  	/**
     * Returns lawn zoenes
     *
     * @api
     */
	public function get() {
		$data = array(
            array(
                'id'    => '1',
                'name'  => 'Lawn Zone 1',
            ),
            array(
                'id'    => 2,
                'name'  => 'Lawn Zone 2',
            ),
            array(
                'id'    => 3,
                'name'  => 'Lawn Zone 3'
            )
        );

        return $data;
	}
}