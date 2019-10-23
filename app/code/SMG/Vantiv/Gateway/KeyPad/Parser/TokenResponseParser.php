<?php

namespace SMG\Vantiv\Gateway\KeyPad\Parser;

use Vantiv\Payment\Gateway\Common\Parser\AbstractResponseParser;

class TokenResponseParser extends AbstractResponseParser
{
    /**
     * Const for <registerTokenResponse> XML node.
     *
     * @var string
     */
    const REGISTER_TOKEN_RESPONSE_NODE = 'TokenCreateResponse';

    const KEYPAD_ERROR_RESPONSE_ROOT_NODE_NAME = "Response";
    const KEYPAD_RESPONSE_CODE_NODE_NAME = "ExpressResponseCode";
    const KEYPAD_RESPONSE_MESSAGE_NODE_NAME = "ExpressResponseMessage";
    const KEYPAD_TOKEN_ID_NODE_NAME = "TokenID";

    /**
     * Get token response path prefix.
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return self::REGISTER_TOKEN_RESPONSE_NODE;
    }

    /**
     * Get the token from the token response
     *
     * @return string
     */
    public function getToken()
    {
        return $this->getValue(self::KEYPAD_TOKEN_ID_NODE_NAME);
    }

    /**
     * Get the response code from the token response
     *
     * @return string
     */
    public function getResponseCode()
    {
        return $this->getValue(self::KEYPAD_RESPONSE_CODE_NODE_NAME);
    }

    /**
     * Get the response message from the token response
     *
     * @return string
     */
    public function getResponseMessage()
    {
        return $this->getValue(self::KEYPAD_RESPONSE_MESSAGE_NODE_NAME);
    }

    /**
     * Get root node.
     *
     * @return \SimpleXMLElement
     */
    public function getRootNode()
    {
        if ($this->rootNode === null) {
            $this->rootNode = simplexml_load_string($this->toXml());
        }

        return $this->rootNode;
    }

    /**
     * Get response data by key.
     *
     * @param string $key
     * @return string
     */
    public function getValue($key)
    {
        return $this->searchNodeByPath($this->getRootNode(), $key);
    }

    /**
     * Search through the tree for the desired node.
     * Unfortunately, I was not able to get the Xpath
     * on SimpleXMLElement to work for some reason
     * so this was the work around. XPath would be a much
     * better solution in the long run.
     *
     * @param $node \SimpleXMLElement
     * @param $key
     * @return string
     */
    private function searchNodeByPath($node, $key)
    {
        // initialize return value
        $result = '';

        // make sure that a node was passed in
        if (isset($node))
        {
            // check to see if this node is the node that we
            // are looking for then set the return as the value
            if ($node->getName() == $key)
            {
                $result = $node->__toString();
            }
            else
            {
                // check to see if there are any children for this node
                if ($node->count() > 0)
                {
                    // get the children from the node and loop through them until
                    $children = $node->children();
                    foreach ($children as $child)
                    {
                        $result = $this->searchNodeByPath($child, $key);

                        // check to see if the return value has been set
                        // if it has been then break out of the loop
                        if ($result != '')
                        {
                            break;
                        }
                    }
                }
            }
        }

        // return
        return $result;
    }

    /**
     * Determine if the request was successful
     *
     * @return bool
     */
    public function isSuccess()
    {
        // initialize
        $isSuccess = true;

        // get the response code
        $responseCode = $this->getResponseCode();
        if (!isset($responseCode) || $responseCode != "0")
        {
            $isSuccess = false;
        }

        // return
        return $isSuccess;
    }
}
