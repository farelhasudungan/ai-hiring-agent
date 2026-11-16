<?php

namespace App\Services\Screening\Exceptions;

use RuntimeException;

/**
 * The AI answered, but the reply lacks the fields a screening needs.
 */
class InvalidScreeningResponseException extends RuntimeException {}
