<?php declare(strict_types = 1);

namespace Contributte\Sentry\Upload;

use Contributte\Sentry\Exception\Runtime\UploadException;
use Throwable;

class S3Uploader
{

	/** @var S3Signer */
	private $signer;

	public function __construct(S3Signer $signer)
	{
		$this->signer = $signer;
	}

	/**
	 * @return array{url: string}
	 */
	public function upload(string $file): array
	{
		$filename = basename($file);
		$signed = $this->signer->sign($filename);

		// Prepare vars
		$headers = [];
		foreach ($signed['headers'] as $key => $value) {
			$headers[] = sprintf('%s:%s', $key, $value);
		}

		$url = $signed['url'];

		// Read file
		$content = file_get_contents($file);

		try {
			$curl = curl_init();
			curl_setopt_array($curl, [
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => false,
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_CUSTOMREQUEST => 'PUT',
				CURLOPT_POSTFIELDS => $content,
				CURLOPT_HTTPHEADER => $headers,
			]);

			$response = curl_exec($curl);

			curl_close($curl);
		} catch (Throwable $e) {
			throw new UploadException('Cannot upload', 0, $e);
		}

		if ($response !== true) {
			throw new UploadException('Upload failed');
		}

		return ['url' => $url];
	}

}
