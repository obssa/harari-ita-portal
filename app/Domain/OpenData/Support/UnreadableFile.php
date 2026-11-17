<?php

namespace App\Domain\OpenData\Support;

use RuntimeException;

/** A dataset file that cannot be read as the format it claims (ADR-0025). */
final class UnreadableFile extends RuntimeException {}
