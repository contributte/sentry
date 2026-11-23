<?php declare(strict_types = 1);

namespace Contributte\Sentry\Integration;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\UploadedFile;
use GuzzleHttp\Psr7\Utils;
use Nette\Http\FileUpload;
use Nette\Http\IRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Sentry\Integration\RequestFetcherInterface;

/**
 * Converts Nette HTTP request into PSR-7 server request, so the Sentry SDK
 * (RequestIntegration) can decide which request data is attached to the event.
 */
class NetteRequestFetcher implements RequestFetcherInterface
{

	public function __construct(protected IRequest $httpRequest)
	{
	}

	public function fetchRequest(): ?ServerRequestInterface
	{
		$httpRequest = $this->httpRequest;

		$serverParams = $_SERVER;
		$remoteAddress = $httpRequest->getRemoteAddress();

		if ($remoteAddress !== null) {
			$serverParams['REMOTE_ADDR'] = $remoteAddress;
		} else {
			unset($serverParams['REMOTE_ADDR']);
		}

		$protocol = is_string($serverParams['SERVER_PROTOCOL'] ?? null)
			? str_replace('HTTP/', '', $serverParams['SERVER_PROTOCOL'])
			: '1.1';

		$request = new ServerRequest(
			$httpRequest->getMethod(),
			$httpRequest->getUrl()->getAbsoluteUrl(),
			$httpRequest->getHeaders(),
			Utils::streamFor($httpRequest->getRawBody() ?? ''),
			$protocol,
			$serverParams
		);

		$post = $httpRequest->getPost();

		return $request
			->withCookieParams($httpRequest->getCookies())
			->withQueryParams($httpRequest->getUrl()->getQueryParameters())
			->withParsedBody($post !== [] ? $post : null)
			->withUploadedFiles($this->convertFiles($httpRequest->getFiles()));
	}

	/**
	 * @param mixed[] $files
	 * @return mixed[]
	 */
	protected function convertFiles(array $files): array
	{
		$result = [];

		foreach ($files as $key => $file) {
			if ($file instanceof FileUpload) {
				$result[$key] = $this->convertFile($file);
			} elseif (is_array($file)) {
				$result[$key] = $this->convertFiles($file);
			}
		}

		return $result;
	}

	protected function convertFile(FileUpload $file): UploadedFileInterface
	{
		return new UploadedFile(
			$file->getTemporaryFile(),
			$file->getSize(),
			$file->getError(),
			$file->getUntrustedName(),
			$file->isOk() ? $file->getContentType() : null
		);
	}

}
