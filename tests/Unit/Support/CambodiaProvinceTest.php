<?php

namespace Tests\Unit\Support;

use App\Support\CambodiaProvince;
use Tests\TestCase;

class CambodiaProvinceTest extends TestCase
{
    /** Ensure the official divisions and default delivery fees remain complete. */
    public function test_official_divisions_have_expected_default_fees(): void
    {
        $this->assertCount(25, CambodiaProvince::names());
        $this->assertSame(1.0, CambodiaProvince::defaultFees()['Phnom Penh']);
        $this->assertSame(2.0, CambodiaProvince::defaultFees()['Siem Reap']);
    }
}
