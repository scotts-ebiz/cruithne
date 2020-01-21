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
    }
    else {
      list($subdomain, $domain, $extension) = explode('.', $_SERVER['HTTP_HOST']);
    }
    // For older browsers without [X-]Content-Security-Policy support.
    // Only ONE domain (no wildcard) allowed. So determine specific allowed domain.
    // Newer browsers should IGNORE this if Content-Sec-Policy header also present.
    $xfValue = 'SAMEORIGIN';
    if ($subdomain === 'store' || $subdomain === 'shop') {
      $xfValue = 'ALLOW-FROM https://www.' . $domain . '.com/';
    }
    else if ($subdomain === 'rc') {
      $xfValue = ' ALLOW-FROM https://acsftest.' . $domain . '.com/';
    }
    else if ($subdomain === 'staging') {
      $xfValue = ' ALLOW-FROM https://acsfdev.' . $domain . '.com/';
    }
    else if ($subdomain === 'test') {
      $xfValue = 'ALLOW-FROM https://acsfdev.' . $domain . '.com/';
    }
    else if ($subdomain === 'dev') {
      $xfValue = 'ALLOW-FROM https://acsfdev.' . $domain . '.com/';
    }
    else if ($subdomain === 'cruithne' && $extension === 'local') {
      $xfValue = 'ALLOW-FROM https://' . $domain . '.test/';
    }

    return $xfValue;
  }
}
