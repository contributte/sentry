<?php declare(strict_types = 1);

namespace Contributte\Sentry\Upload;

use DateTime;

class S3Signer
{

	private DateTime $date;

	public function __construct(
		private string $accessKey,
		private string $secretKey,
		private string $url,
		private string $bucket,
		private string $region = 'auto',
		private ?string $prefix = null
	)
	{
		$this->date = new DateTime('UTC');
	}

	/**
	 * @return array{url: string, headers: array<string,string>}
	 */
	public function sign(string $path): array
	{
		$fullpath = sprintf('/%s/%s', $this->bucket, ($this->prefix !== null ? trim($this->prefix, '/') . '/' : '') . $path);
		$url = sprintf('https://%s%s', $this->url, $fullpath);

		$headers = [
			'Host' => $this->url,
			'X-Amz-Date' => $this->date->format('Ymd\THis\Z'),
			'X-Amz-Content-Sha256' => 'UNSIGNED-PAYLOAD',
		];

		$headers['Authorization'] = $this->doAuthorization($fullpath, $headers);
		$headers['Content-Type'] = 'text/html; charset=utf-8';

		return ['url' => $url, 'headers' => $headers];
	}

	/**
	 * @param array<string, string> $headers
	 */
	protected function doAuthorization(string $path, array $headers): string
	{
		$method = 'PUT';
		$query = '';
		$payloadHash = 'UNSIGNED-PAYLOAD';
		$service = 's3';

		$longDate = $this->date->format('Ymd\THis\Z');

		// Sort headers by key
		$sortedHeaders = $headers;
		ksort($sortedHeaders);

		// Build headers keys and headers lines
		$signedHeaderNames = [];
		$signedHeaderLines = [];

		foreach ($sortedHeaders as $key => $value) {
			$signedHeaderNames[] = strtolower($key);
			$signedHeaderLines[] = sprintf('%s:%s', strtolower($key), $value);
		}

		$signedHeaderLines = implode("\n", $signedHeaderLines);
		$signedHeaderNames = implode(';', $signedHeaderNames);

		// Scope
		$credentialScope = sprintf('%s/%s/%s/aws4_request', $this->date->format('Ymd'), $this->region, $service);

		// Canonical
		$canonicalRequest = sprintf(
			"%s\n%s\n%s\n%s\n\n%s\n%s",
			$method,
			$path,
			$query,
			$signedHeaderLines,
			$signedHeaderNames,
			$payloadHash
		);

		// Sign string
		$hash = hash('sha256', $canonicalRequest);
		$stringToSign = sprintf("AWS4-HMAC-SHA256\n%s\n%s\n%s", $longDate, $credentialScope, $hash);

		// Sign key
		$dateKey = hash_hmac('sha256', $this->date->format('Ymd'), sprintf('AWS4%s', $this->secretKey), true);
		$regionKey = hash_hmac('sha256', $this->region, $dateKey, true);
		$serviceKey = hash_hmac('sha256', 's3', $regionKey, true);
		$signingKey = hash_hmac('sha256', 'aws4_request', $serviceKey, true);
		$signature = hash_hmac('sha256', $stringToSign, $signingKey);

		// Compute together
		return sprintf(
			'AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
			$this->accessKey,
			$credentialScope,
			$signedHeaderNames,
			$signature
		);
	}

}
