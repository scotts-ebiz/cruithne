<?php
namespace SMG\Framework\App\Response\HeaderProvider;

use \Magento\Framework\App\Response\HeaderProvider\AbstractHeaderProvider;

/**
 * Adds a Content-Security-Policy header to HTTP responses.
 */
class ContentSecurityPolicy extends AbstractHeaderProvider
{
    /**
     * Content-Security-Policy Header name
     *
     * @var string
     */
    protected $headerName = "Content-Security-Policy";

    /**
     * Content-Security-Policy header value
     *
     * @var string
     */
    protected $headerValue;

    public function __construct()
    {
        $fullDomain = explode('.', $_SERVER['HTTP_HOST']);

        if (count($fullDomain) < 3) {
            list($domain, $extension) = explode('.', $_SERVER['HTTP_HOST']);
        }
        else {
            list($subdomain, $domain, $extension) = explode('.', $_SERVER['HTTP_HOST']);
        }

        // FF 23+ Chrome 25+ Safari 7+ Opera 19+

        $value = "frame-ancestors 'self' *." . $domain . '.com '
            . $domain . '.com '
            . '*.' . $domain . '.test '
            . $domain . '.test; ' .
            "object-src 'none'; ";

        $this->headerValue = $value;
    }
}
