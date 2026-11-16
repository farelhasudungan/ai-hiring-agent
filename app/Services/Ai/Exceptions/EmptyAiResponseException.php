<?php

namespace App\Services\Ai\Exceptions;

/**
 * The vendor answered successfully but returned no text.
 */
class EmptyAiResponseException extends AiProviderException {}
