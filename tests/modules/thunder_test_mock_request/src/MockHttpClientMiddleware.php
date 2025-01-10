<?php

namespace Drupal\thunder_test_mock_request;

use Drupal\Core\State\StateInterface;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Sets the mocked responses.
 */
class MockHttpClientMiddleware {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * MockHttpClientMiddleware constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The current request stack.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(RequestStack $requestStack, protected readonly StateInterface $state) {
    $this->request = $requestStack->getCurrentRequest();
  }

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
  public static function addUrlResponse(string $url, string $body, array $headers = [], int $status = 200): void {

    $items = \Drupal::state()->get(static::class, []);
    $items[$url] = ['body' => $body, 'headers' => $headers, 'status' => $status];

    \Drupal::state()->set(static::class, $items);
  }

  /**
   * {@inheritdoc}
   *
   * HTTP middleware that adds the next mocked response.
   */
  public function __invoke(): callable {
    // Needed due to a bug in coder.
    // phpcs:disable Generic.CodeAnalysis.EmptyPHPStatement.SemicolonWithoutCodeDetected
    return fn($handler): callable => function (RequestInterface $request, array $options) use ($handler) {
      $items = $this->state->get(static::class, []);
      $url = (string) $request->getUri();
      if (!empty($items[$url])) {
        $response = new Response($items[$url]['status'], $items[$url]['headers'], $items[$url]['body']);
        return Create::promiseFor($response);
      }
      if (strpos($this->request->getHttpHost(), $request->getUri()->getHost()) === FALSE) {
        throw new \Exception(sprintf("No response for %s defined. See MockHttpClientMiddleware::addUrlResponse().", $url));
      }

      return $handler($request, $options);
    };
  }

}
