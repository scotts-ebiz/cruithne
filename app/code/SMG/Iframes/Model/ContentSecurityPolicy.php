<?php
namespace SMG\Iframes\Model;

class ContentSecurityPolicy
{
    /**
     *
     * Add Custom CSP Headers to allow iframes from Drupal.
     *
     */
    public function setContentSecurityPolicy()
    {  
        $fullDomain = explode('.', $_SERVER['HTTP_HOST']);
        if (count($fullDomain) < 3) {
            list($domain, $extension) = explode('.', $_SERVER['HTTP_HOST']);
        }
        else {
            list($subdomain, $domain, $extension) = explode('.', $_SERVER['HTTP_HOST']);
        }
       
        if(!headers_sent()) {
            // FF 23+ Chrome 25+ Safari 7+ Opera 19+
            header("Content-Security-Policy: " .
                "frame-ancestors 'self' *." . $domain . '.com '
                . $domain . '.com '
                . '*.' . $domain . '.test '
                . $domain . '.test; ' .
                "object-src 'none'; ");

            // IE 10+
            header("X-Content-Security-Policy: " .
                "frame-ancestors 'self' *." . $domain . '.com '
                . $domain . '.com '
                . '*.' . $domain . '.test '
                . $domain . '.test; ' .
                "object-src 'none'; ");
        }

        // The only frameable pages are the iframe urls, so block all other pages.
        if (strpos($_SERVER['REQUEST_URI'], '/iframes/') == 0) {
            header("X-Frame-Options: ALLOW-FROM *." . $domain . '.' . $extension);
        }
       
    }
}
