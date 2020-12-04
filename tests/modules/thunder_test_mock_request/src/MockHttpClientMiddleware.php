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
   * @param string $content
   *   The content body of the response.
   * @param array $headers
   *   The response headers.
   */
  public static function add($content, array $headers = []) {

    $items = \Drupal::state()->get(static::class, []);
    $items[] = [$content, $headers];

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
          if ($items) {
            $item = array_shift($items);
            $items = \Drupal::state()->set(static::class, $items);
            $handler->append(new Response(200, $item[1], $item[0]));
          }
        }
        return $handler($request, $options);
      };
    };
  }

}
