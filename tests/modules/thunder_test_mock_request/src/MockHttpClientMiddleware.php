<?php

namespace Drupal\thunder_test_mock_request;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Sets the mocked responses.
 */
class MockHttpClientMiddleware {

  /**
   * Add a mocked response.
   *
   * @param string $url
   *   URL of the request.
   * @param string $body
   *   The content body of the response.
   * @param array $headers
   *   The response headers.
   * @param int $status
   *   The response status code.
   */
  public static function addUrlResponse($url, $body, array $headers = [], $status = 200) {

    $items = \Drupal::state()->get(static::class, []);
    $items[$url] = ['body' => $body, 'headers' => $headers, 'status' => $status];

    \Drupal::state()->set(static::class, $items);
  }

  /**
   * {@inheritdoc}
   *
   * HTTP middleware that adds the next mocked response.
   */
  public function __invoke() {
    return function ($handler) {
      return function (RequestInterface $request, array $options) use ($handler) {
        if ($handler instanceof MockHandler) {
          $items = \Drupal::state()->get(static::class, []);
          $url = (string) $request->getUri();
          if ($items[$url]) {
            $handler->append(new Response($items[$url]['status'], $items[$url]['headers'], $items[$url]['body']));
          }
        }
        return $handler($request, $options);
      };
    };
  }

}
