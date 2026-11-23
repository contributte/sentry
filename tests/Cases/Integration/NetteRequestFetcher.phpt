<?php declare(strict_types = 1);

use Contributte\Sentry\Integration\NetteRequestFetcher;
use Contributte\Tester\Toolkit;
use Nette\Http\FileUpload;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Conversion
Toolkit::test(function (): void {
	$rawBody = fn (): string => 'name=John';
	$httpRequest = new Request(
		new UrlScript('https://example.com/foo/bar?baz=1&qux=2'),
		['name' => 'John'],
		[
			'avatar' => new FileUpload([
				'name' => 'avatar.png',
				'size' => 0,
				'tmp_name' => '',
				'error' => UPLOAD_ERR_NO_FILE,
			]),
		],
		['PHPSESSID' => 'secret-session'],
		['Authorization' => 'Bearer secret-token', 'Content-Type' => 'application/x-www-form-urlencoded'],
		'POST',
		'192.168.122.1',
		null,
		$rawBody
	);

	$request = (new NetteRequestFetcher($httpRequest))->fetchRequest();

	Assert::type(ServerRequestInterface::class, $request);
	Assert::same('POST', $request->getMethod());
	Assert::same('https://example.com/foo/bar?baz=1&qux=2', (string) $request->getUri());
	Assert::same('baz=1&qux=2', $request->getUri()->getQuery());
	Assert::same(['baz' => '1', 'qux' => '2'], $request->getQueryParams());
	Assert::same('Bearer secret-token', $request->getHeaderLine('Authorization'));
	Assert::same('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
	Assert::same(['PHPSESSID' => 'secret-session'], $request->getCookieParams());
	Assert::same(['name' => 'John'], $request->getParsedBody());
	Assert::same('name=John', (string) $request->getBody());
	Assert::same('192.168.122.1', $request->getServerParams()['REMOTE_ADDR']);

	$files = $request->getUploadedFiles();
	Assert::type(UploadedFileInterface::class, $files['avatar']);
	Assert::same('avatar.png', $files['avatar']->getClientFilename());
	Assert::same(UPLOAD_ERR_NO_FILE, $files['avatar']->getError());
});

// Empty request
Toolkit::test(function (): void {
	$httpRequest = new Request(new UrlScript('https://example.com/'));

	$request = (new NetteRequestFetcher($httpRequest))->fetchRequest();

	Assert::type(ServerRequestInterface::class, $request);
	Assert::same('GET', $request->getMethod());
	Assert::same('https://example.com/', (string) $request->getUri());
	Assert::same([], $request->getQueryParams());
	Assert::same([], $request->getCookieParams());
	Assert::null($request->getParsedBody());
	Assert::same('', (string) $request->getBody());
	Assert::false(isset($request->getServerParams()['REMOTE_ADDR']));
});
