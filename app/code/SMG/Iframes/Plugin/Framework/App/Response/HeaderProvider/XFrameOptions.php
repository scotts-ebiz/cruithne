<?php

namespace SMG\Iframes\Plugin\Framework\App\Response\HeaderProvider;

use \Magento\Framework\App\Response\Http;

/**
 * Adds an X-FRAME-OPTIONS header to HTTP responses to safeguard against click-jacking.
 */
class XFrameOptions extends \Magento\Framework\App\Response\HeaderProvider\AbstractHeaderProvider
{
    /** Deployment config key for frontend x-frame-options header value */
    const DEPLOYMENT_CONFIG_X_FRAME_OPT = 'x-frame-options';

    /** Always send SAMEORIGIN in backend x-frame-options header */
    const BACKEND_X_FRAME_OPT = 'SAMEORIGIN';

    /**
     * x-frame-options Header name
     *
     * @var string
     */
    protected $headerName = Http::HEADER_X_FRAME_OPT;

    /**
     * x-frame-options header value
     *
     * @var string
     */
    protected $headerValue;

    /**
     * @param string $xFrameOpt
     */
    public function __construct($xFrameOpt = null)
    {
      if ($xFrameOpt == null) {
        $fullDomain = explode('.', $_SERVER['HTTP_HOST']);
        if (count($fullDomain) < 3) {
          list($domain, $extension) = explode('.', $_SERVER['HTTP_HOST']);
        }
        else {
          list($subdomain, $domain, $extension) = explode('.', $_SERVER['HTTP_HOST']);
        }

        // For older browsers without [X-]Content-Security-Policy support.
        // Only ONE domain (no wildcard) allowed. So determine specific allowed domain.
        // Newer browsers should IGNORE this if Content-Sec-Policy header also present.
        $xFrameOpt = 'SAMEORIGIN';
        if ($subdomain === 'store' || $subdomain === 'shop') {
          $xFrameOpt = 'ALLOW-FROM https://www.' . $domain . '.com/';
        }
        else if ($subdomain === 'staging') {
          $xFrameOpt = ' ALLOW-FROM https://acsftest.' . $domain . '.com/';
        }
        else if ($subdomain === 'test') {
          $xFrameOpt = 'ALLOW-FROM https://acsfdev.' . $domain . '.com/';
        }
        else if ($subdomain === 'dev') {
          $xFrameOpt = 'ALLOW-FROM https://acsfdev.' . $domain . '.com/';
        }
        else if ($subdomain === 'cruithne' && $extension === 'local') {
          $xFrameOpt = 'ALLOW-FROM https://' . $domain . '.test/';
        }
        $this->headerValue = $xFrameOpt;
      }
    }
}
