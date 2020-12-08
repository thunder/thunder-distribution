<?php

namespace Drupal\thunder_test_mock_request;

use Drupal\Core\State\StateInterface;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Promise\promise_for;
use Psr\Http\Message\RequestInterface;
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
  protected $request;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * MockHttpClientMiddleware constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The current request stack.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(RequestStack $requestStack, StateInterface $state) {
    $this->request = $requestStack->getCurrentRequest();
    $this->state = $state;
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
        $items = $this->state->get(static::class, []);
        $url = (string) $request->getUri();
        if (!empty($items[$url])) {
          $response = new Response($items[$url]['status'], $items[$url]['headers'], $items[$url]['body']);
          // @phpstan-ignore-next-line
          return promise_for($response);
        }
        elseif (strstr($this->request->getHttpHost(), $request->getUri()->getHost()) === FALSE) {
          throw new \Exception(sprintf("No response for %s defined. See MockHttpClientMiddleware::addUrlResponse().", $url));
        }

        return $handler($request, $options);
      };
    };
  }

}
