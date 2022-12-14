<?php

namespace League\Flysystem\AkamaiNetStorage\Tests;

/**
 * guzzlehttp-vcr middleware
 *
 * Records and automatically replays responses on subsequent requests
 * for unit testing
 *
 * @package Dshafik\GuzzleHttp
 */
class VcrHandler
{
    /**
     * @var string
     */
    protected $cassette;

    /**
     * @param string $cassette fixture path
     * @return \GuzzleHttp\HandlerStack
     */
    public static function turnOn($cassette)
    {
        if (!file_exists($cassette)) {
            $handler = \GuzzleHttp\HandlerStack::create();
            $handler->after('allow_redirects', new static($cassette), 'vcr_recorder');
            return $handler;
        } else {
            $responses = self::decodeResponses($cassette);

            $queue = [];
            $class = new \ReflectionClass(\GuzzleHttp\Psr7\Response::class);
            foreach ($responses as $response) {
                $queue[] = $class->newInstanceArgs($response);
            }

            return \GuzzleHttp\HandlerStack::create(new \GuzzleHttp\Handler\MockHandler($queue));
        }
    }

    /**
     * Constructor
     *
     * @param string $cassette fixture path
     */
    protected function __construct($cassette)
    {
        $this->cassette = $cassette;
    }

    /**
     * Decodes every responses body from base64
     *
     * @param $cassette
     * @return array
     */
    protected static function decodeResponses($cassette)
    {
        $responses = json_decode(file_get_contents($cassette), true);

        array_walk($responses, function (&$response) {
            $response['body'] = base64_decode($response['body']);
        });

        return $responses;
    }

    /**
     * Handle the request/response
     *
     * @param callable $handler
     * @return \Closure
     */
    public function __invoke(callable $handler)
    {
        return function (\Psr\Http\Message\RequestInterface $request, array $config) use ($handler) {
            return $handler($request, $config)->then(
                function (\Psr\Http\Message\ResponseInterface $response) use ($request) {
                    $responses = [];
                    if (file_exists($this->cassette)) {
                        //No need to base64 decode body of response here.
                        $responses = json_decode(file_get_contents($this->cassette), true);
                    }
                    $cassette = $response->withAddedHeader('X-VCR-Recording', time());
                    $responses[] = [
                        'status' =>  $cassette->getStatusCode(),
                        'headers' => $cassette->getHeaders(),
                        'body' => base64_encode((string) $cassette->getBody()),
                        'version' => $cassette->getProtocolVersion(),
                        'reason' => $cassette->getReasonPhrase()
                    ];

                    file_put_contents($this->cassette, json_encode($responses, JSON_PRETTY_PRINT));
                    return $response;
                },
                function (\Exception $reason) {
                    return new \GuzzleHttp\Promise\RejectedPromise($reason);
                }
            );
        };
    }
}
