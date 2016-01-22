<?php

namespace TH\Lock;

class AggregationException extends RuntimeException
{
	private $exceptions;

	public function __construct(array $exceptions, $message = "", $code = 0)
	{
		parent::__construct($message, $code);
		$this->exceptions = $exceptions;
	}
}
