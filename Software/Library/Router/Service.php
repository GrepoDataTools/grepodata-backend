<?php

namespace Grepodata\Library\Router;

use Grepodata\Library\Logger\Logger;
use RateLimit\Exception\RateLimitExceededException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpFoundation\Request;

class Service
{
  private static $aRouteCollection;

  public static function GetInstance()
  {
    static $inst = null;
    if ($inst === null) {
      $inst = new Service();
      static::$aRouteCollection = new RouteCollection();
    }
    return $inst;
  }

  /**
   * Add router paths
   *
   * @param $RouteName String Route name identifier
   * @param $oRoute \Symfony\Component\Routing\Route Route options
   */
  public static function Add($RouteName, $oRoute)
  {
    static::$aRouteCollection->add($RouteName, $oRoute);
  }

  /**
   * Handle request
   *
   * @param $oRequest \Symfony\Component\Routing\RequestContext Request context object
   */
  public static function Handle()
  {
    try {
      // Request context
      $oRequest = Request::createFromGlobals();
      $oContext = new RequestContext();
      $oContext->fromRequest($oRequest);

      // Matcher
      $oMatcher = new UrlMatcher(static::$aRouteCollection, $oContext);
      $aMatchedParameters = $oMatcher->match($oContext->getPathInfo());

      // Call request handle
      if (isset($aMatchedParameters['_controller']) && isset($aMatchedParameters['_method']) && class_exists($aMatchedParameters['_controller'])) {
        $oController = new $aMatchedParameters['_controller']($oContext, $oRequest);
        $MethodName = $aMatchedParameters['_method'].$oContext->getMethod();

        // Check rate limits (default limit applies if route does not specify)
        if (!bDevelopmentMode) {
          $RateLimit = 200;
          $RateWindow = 60;
          if (isset($aMatchedParameters['_ratelimit']) && is_array($aMatchedParameters['_ratelimit'])) {
            $RateLimit = $aMatchedParameters['_ratelimit']['limit'];
            if (isset($aMatchedParameters['_ratelimit']['window'])) {
              $RateWindow = $aMatchedParameters['_ratelimit']['window'];
            }
          }

          $ResourceId = json_encode($aMatchedParameters);
          $RateLimiter = \RateLimit\RateLimiterFactory::createRedisBackedRateLimiter([
            'host' => REDIS_HOST,
            'port' => REDIS_PORT,
          ], $RateLimit, $RateWindow);
          try {
            $RateLimiter->hit($ResourceId);
          } catch (RateLimitExceededException $e) {
            die(BaseRoute::OutputJson(array('message' => 'Too many requests. You have exceeded the rate limit for this specific resource. Please try again in a minute.'), 429));
          }
        }

        if ($oContext->getMethod() == 'OPTIONS') {
          header('Access-Control-Allow-Origin: *');
          header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
          die();
        } else if (method_exists($oController, $MethodName)) {
          header("Access-Control-Allow-Origin: *");
          die($oController::{$MethodName}());
        } else {
          die(BaseRoute::OutputJson(array('message' => 'No method ' . $oContext->getMethod() . ' for route ' . $oContext->getPathInfo()), 404));
        }
      }

    } catch (ResourceNotFoundException $e) {
      // Ignore
    } catch (\Exception $e) {
      $LogMessage = "API server error! (" . $e->getMessage() . ")";
      try {
        if (isset($MethodName)) $LogMessage .= " method: " . $MethodName;
        if (isset($oRequest)) $LogMessage .= " uri: " . $oRequest->getRequestUri();
        if (isset($oContext) && $oContext->getMethod() == 'POST') {
          $params = $oRequest->request->all();
          if ($params!=null) {
            $LogMessage .= " params: " . json_encode($params);
          }
        }
      } catch (\Exception $t) {}
      $LogMessage .= " [".$e->getTraceAsString()."]";
      Logger::error($LogMessage);
      die(BaseRoute::OutputJson(array('message' => 'Internal server error'), 500));
    }
    die(BaseRoute::OutputJson(array('message' => 'Resource not found'), 404));

  }

  private function __construct()
  {
  }
}