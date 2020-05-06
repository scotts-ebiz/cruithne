<?php

namespace SMG\Framework\Plugin\App\Response\HeaderProvider;

/**
 * Adds an X-FRAME-OPTIONS header to HTTP responses to safeguard against click-jacking.
 */
class XFrameOptions
{
    public function afterGetValue()
    {
        $fullDomain = explode('.', $_SERVER['HTTP_HOST']);
        if (count($fullDomain) < 3) {
            list($domain, $extension) = explode('.', $_SERVER['HTTP_HOST']);
        } else {
            list($subdomain, $domain, $extension) = explode('.', $_SERVER['HTTP_HOST']);
        }

        $xfValue = 'SAMEORIGIN';

        // If we aren't serving up an iframe, then return sameorigin to satisfy security scans.
        $urlPath = parse_url($_SERVER["REQUEST_URI"])['path'];
        if (strpos($urlPath, 'iframes') === FALSE) {
            return $xfValue;
        }
        
        // For older browsers without [X-]Content-Security-Policy support.
        // Only ONE domain (no wildcard) allowed. So determine specific allowed domain.
        // Newer browsers should IGNORE this if Content-Sec-Policy header also present.
        if ($subdomain === 'store' || $subdomain === 'shop') {
            $xfValue = 'ALLOW-FROM https://www.' . $domain . '.com/';
        } elseif ($subdomain === 'rc') {
            $xfValue = ' ALLOW-FROM https://acsftest.' . $domain . '.com/';
        } elseif ($subdomain === 'staging') {
            $xfValue = ' ALLOW-FROM https://acsfdev.' . $domain . '.com/';
        } elseif ($subdomain === 'test') {
            $xfValue = 'ALLOW-FROM https://acsfdev.' . $domain . '.com/';
        } elseif ($subdomain === 'dev') {
            $xfValue = 'ALLOW-FROM https://acsfdev.' . $domain . '.com/';
        } elseif ($subdomain === 'cruithne' && $extension === 'local') {
            $xfValue = 'ALLOW-FROM https://' . $domain . '.test/';
        }

        return $xfValue;
    }
}
