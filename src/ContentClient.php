<?php

namespace Cerpus\REContentClient;


use Cerpus\REContentClient\Exceptions\JsonDecodeException;
use Cerpus\REContentClient\Exceptions\UnexpectedReturnStatusException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Response;
use Illuminate\Log\Logger;

class ContentClient
{
    protected $client;
    protected $lastRequestException = null;
    protected $logger = null;

    public function __construct(Client $client, Logger $logger = null)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Create content
     * @param REContent $content
     * @return bool
     */
    public function create(REContent $content): bool
    {
        $path = "/content-index";

        $payload = $content->generatePayload();

        try {
            $this->doRequest("POST", $path, $payload);

            return $this->getLastRequestException() === null;
        } catch (RequestException $e) {
            if($this->logger){
                $this->logger->error(__METHOD__."({$e->getCode()}) {$e->getMessage()}");
            }
            return false;
        }
    }

    /**
     * Update content
     * @param REContent $content
     * @return bool
     */
    public function update(REContent $content): bool
    {
        $path = sprintf("content-index/%s", $content->getId());

        $payload = $content->generatePayload();
        try {
            $response = $this->doRequest("PATCH", $path, $payload);

            return $this->getLastRequestException() === null;
        } catch (RequestException $e) {
            if($this->logger){
                $this->logger->error(__METHOD__."({$e->getCode()}) {$e->getMessage()}");
            }
            return false;
        }
    }

    /**
     * Remove content by ID.
     *
     * @param string $contentId
     * @return bool
     */
    public function remove(string $contentId): bool
    {
        $path = sprintf("content-index/%s", $contentId);

        try {
            $response = $this->doRequest("DELETE", $path);

            return $this->getLastRequestException() === null;
        } catch (RequestException $e) {
            if($this->logger){
                $this->logger->error(__METHOD__."({$e->getCode()}) {$e->getMessage()}");
            }
            return false;
        }
    }


    /**
     * @param $contentId
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function exists($contentId): bool
    {
        $path = sprintf("content-index/%s/exists", $contentId);

        try {
            $response = $this->doRequest("GET", $path, null, false, Response::HTTP_OK);

            return $this->getLastRequestException() === null;
        } catch (ClientException $e) {
            if ($e->getCode() === Response::HTTP_NOT_FOUND) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * @param array $ids Ids to check for existence of.
     * @return array Ids that exist in the content index
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function multiExists(array $ids = []): array
    {
        if (empty($ids)) {
            return [];
        }

        $path = "content-index/exists";

        $payload = [
            "check_ids" => $ids,
        ];

        try {
            $response = $this->doRequest("POST", $path, $payload, [], Response::HTTP_OK);

            if ($this->getLastRequestException() === null) {
                return $response;
            }

            return [];
        } catch (RequestException $e) {
            if($this->logger){
                $this->logger->error(__METHOD__ . ": ({$e->getCode()}) {$e->getMessage()}");
            }

            return [];
        }
    }


    /**
     * @param string $method
     * @param string $endpoint
     * @param array|null $payload
     * @param bool $defaultResult
     * @param int $expectedResponseCode
     * @return bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function doRequest(string $method, string $endpoint, ?array $payload = null, $defaultResult = false, $expectedResponseCode = Response::HTTP_ACCEPTED)
    {
        $this->setLastRequestException(null);

        $options = [];

        if ($payload) {
            $options[RequestOptions::JSON] = $payload;
        }

        $result = $defaultResult;

        try {
            if($this->logger){
                $this->logger->debug("Headers", $this->getClient()->getConfig("headers"));
            }

            $response = $this->getClient()->request($method, $endpoint, $options);

            if ($response->getStatusCode() !== $expectedResponseCode) {
                throw new UnexpectedReturnStatusException("Unexpected return status code. Expected: $expectedResponseCode, Got: {$response->getStatusCode()}");
            }

            if ($response->hasHeader("Content-Type") && ($response->getHeader("Content-Type")[0] === "application/json")) {
                $result = json_decode($response->getBody()->getContents());
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $result = $defaultResult;
                    $errorCode = json_last_error();
                    $errorMessage = json_last_error_msg();
                    throw new JsonDecodeException("Unable to decode response as JSON. Json error: ($errorCode): $errorMessage");
                }
            }
        } catch (UnexpectedReturnStatusException $e) {
            $this->setLastRequestException($e);
        } catch (JsonDecodeException $e) {
            $this->setLastRequestException($e);
        } catch (ClientException $e) {
            throw $e;
        } catch (\Throwable $t) {
            $this->setLastRequestException($t);
            throw $t;
        } finally {
            if ($this->logger && $this->getLastRequestException()) {
                $this->logger->error($this->getLastRequestException());
            }
        }

        return $result;
    }

    // ****************************
    // Utility functions
    // ****************************
    public function removeIfExists(REContent $content): bool
    {
        if ($this->exists($content->getId())) {
            return $this->remove($content->getId());
        }
        return true;
    }

    public function updateOrCreate(REContent $content): bool
    {
        if ($this->exists($content->getId())) {
            return $this->update($content);
        }

        return $this->create($content);
    }

    /**
     * Get the last exception thrown. This get reset between each request.
     *
     * @return \Throwable|null
     */
    public function getLastRequestException(): ?\Throwable
    {
        return $this->lastRequestException;
    }

    public function setLastRequestException(?\Throwable $t): void
    {
        $this->lastRequestException = $t;
    }
}
