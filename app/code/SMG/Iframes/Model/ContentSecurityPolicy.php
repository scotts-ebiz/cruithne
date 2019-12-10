<?php
namespace SMG\Iframes\Model;

class ContentSecurityPolicy
{
  /**
   *
   * Add Custom CSP Headers to allow iframes from Drupal.
   *
   * As this is declared within the SMG iframes module it should only get called for
   * /iframes/ pages.  So no test of HTTP_URI currently used.
   * If this IS called for ALL pages then retain the HTTP_URI test and
   * if NOT /iframes/ add x-frame-options SAMEORIGIN
   *
   */
  public function setContentSecurityPolicy()
  {
    // Adding headers after body rendering has started will ERROR, so avoid.
    if(!headers_sent()) {

      $fullDomain = explode('.', $_SERVER['HTTP_HOST']);
      if (count($fullDomain) < 3) {
        list($domain, $extension) = explode('.', $_SERVER['HTTP_HOST']);
      }
      else {
        list($subdomain, $domain, $extension) = explode('.', $_SERVER['HTTP_HOST']);
      }

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
  }
}
