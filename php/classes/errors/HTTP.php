<?hh // strict

namespace beatbox\errors;

class HTTP {
	/**
	 * Throw a HTTP error
	 *
	 * @param int $code The HTTP status code
	 * @param string $status The status description
	 */
	public static function error(int $code, ?string $status = null) : void {
		throw new HTTP_Exception($status, $code);
	}

	/**
	 * Redirect to the given page, or to the fallback if the
	 * given page doesn't exist or is in another domain.
	 *
	 * If no fallback is given, it defaults to the home page
	 *
	 * @param string $to the URL to redirect to
	 * @param string $fallback the URL to fallback to
	 */
	public static function redirect(string $to, ?string $fallback=null, int $code = 302): void {
		$e = new HTTP_Exception(null, $code);

		if($to) {
			$uri = base_url();
			if(strpos($to, $uri) !== 0) {
				$parts = parse_url($to);
				assert(is_array($parts));
				$parts = new Map($parts);
				if($parts && !$parts->contains('scheme') && !$parts->contains('host')) {
					$to = rtrim($uri, '/') . '/' . ltrim($to, '/');
				} else {
					$to = null;
				}
			}
		}
		if(!$to) {
			$to = $fallback ?: '';

			$uri = base_url();

			$to = rtrim($uri, '/') . '/' . ltrim($to, '/');
		}

		assert((is_cli() && $to[0] == '/') ||
				(!is_cli() && substr($to, 0, 6) == substr(base_url(), 0, 6)));

		$e->setHeader('Location', $to);

		throw $e;
	}

	/**
	 * Redirect to the previous page, or to the fallback if the
	 * previous page doesn't exist or is in another domain.
	 *
	 * If no fallback is given, it defaults to the home page
	 *
	 * @param string $fallback the URL to fallback to
	 */
	public static function redirect_back(?string $fallback = null) : void {
		$referer = (string)server_var('HTTP_REFERER');
		self::redirect($referer, $fallback);
	}
}

class HTTP_Exception extends Exception {
	protected static ImmMap<int, string> $status_map = ImmMap {
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		208 => 'Already Reported',
		226 => 'IM Used',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => 'Switch Proxy',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		419 => 'Authentication Timeout',
		420 => 'Enhance Your Calm',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		424 => 'Method Failure',
		425 => 'Unordered Collection',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		444 => 'No Response',
		449 => 'Retry With',
		450 => 'Blocked by Windows Parental Controls',
		451 => 'Unavailable For Legal Reasons',
		494 => 'Request Header Too Large',
		495 => 'Cert Error',
		496 => 'No Cert',
		497 => 'HTTP to HTTPS',
		499 => 'Client Closed Request',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		509 => 'Bandwidth Limit Exceeded',
		510 => 'Not Extended',
		511 => 'Network Authentication Required',
		598 => 'Network read timeout error',
		599 => 'Network connect timeout error',
	};

	public function __construct(?string $message = null, int $code=0,
								?\Exception $previous=null) {
		if(!$message && self::$status_map->contains($code)) {
			$message = self::$status_map[$code];
		} else if ($message == false){
			$message = "There was an error";
		}
		assert(is_string($message));
		parent::__construct($message, $code, $previous);
	}

	protected Vector<Pair<string, bool>> $headers = Vector {};

	public function setHeader(string $header, string $value, bool $replace = true) : void {
		assert(strpos($header, ':') === false);
		$this->headers[] = Pair {"$header: $value", $replace };
	}

	public function getEventPrefix() : string {
		return 'http:';
	}

	public function sendToBrowser() : void {
		$line = 'HTTP/1.1 ' . $this->getBaseCode();
		if(static::$status_map->contains($this->getBaseCode())) {
			$line .= ' ' . static::$status_map[$this->getBaseCode()];
		}
		if(is_ajax()) {
			if ($this->getBaseCode() >= 300 && $this->getBaseCode() <= 399) {
				header('x-ajax: ' . $line);
			} else {
				header($line, true, $this->getBaseCode());
			}
			foreach($this->headers as $h) {
				header('x-ajax-' . $h[0], $h[1]);
			}
		} else {
			header($line, true, $this->getBaseCode());
			foreach($this->headers as $h) {
				header($h[0], $h[1]);
			}
		}
	}
}
