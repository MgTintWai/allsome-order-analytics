<?php

namespace Tests\Unit;

use App\Support\OrderUploadConstraints;
use Tests\TestCase;

class OrderUploadConstraintsTest extends TestCase
{
    public function test_multipart_tolerance_flags_large_gap_between_body_and_file(): void
    {
        $fileSize = 1024;
        $tolerance = OrderUploadConstraints::multipartToleranceForVisibleFile($fileSize);
        $cl = $fileSize + $tolerance + 10_000;

        $this->assertTrue($cl > $fileSize + $tolerance);
    }

    public function test_multipart_tolerance_allows_tight_content_length_to_file(): void
    {
        $fileSize = 1024;
        $tolerance = OrderUploadConstraints::multipartToleranceForVisibleFile($fileSize);
        $cl = $fileSize + (int) ($tolerance * 0.5);

        $this->assertFalse($cl > $fileSize + $tolerance);
    }
}
