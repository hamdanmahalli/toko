<?php

namespace Tests\Unit;

use App\Services\GeoService;
use PHPUnit\Framework\TestCase;

class GeoServiceTest extends TestCase
{
    private GeoService $geo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->geo = new GeoService;
    }

    public function test_jarak_nol_untuk_titik_yang_sama(): void
    {
        $jarak = $this->geo->jarakMeter(-6.1753924, 106.8271528, -6.1753924, 106.8271528);

        $this->assertLessThan(0.001, $jarak);
    }

    public function test_jarak_monas_ke_boga_sekitar_47_km(): void
    {
        // Monas -> Kota Bogor, jarak udara sekitar 46-47 km
        $jarak = $this->geo->jarakMeter(-6.1753924, 106.8271528, -6.5950000, 106.8166000);

        $this->assertGreaterThan(44_000, $jarak);
        $this->assertLessThan(48_000, $jarak);
    }

    /** ~111 m per 0.001 derajat lintang. */
    public function test_jarak_0_001_derajat_lintang_sekitar_111_m(): void
    {
        $jarak = $this->geo->jarakMeter(-6.1753924, 106.8271528, -6.1743924, 106.8271528);

        $this->assertGreaterThan(108, $jarak);
        $this->assertLessThan(114, $jarak);
    }

    public function test_simetris(): void
    {
        $a = $this->geo->jarakMeter(-6.1753924, 106.8271528, -6.2000000, 106.8000000);
        $b = $this->geo->jarakMeter(-6.2000000, 106.8000000, -6.1753924, 106.8271528);

        $this->assertEqualsWithDelta($a, $b, 0.0001);
    }

    public function test_koordinat_valid(): void
    {
        $this->assertTrue($this->geo->koordinatValid(-6.1753924, 106.8271528));
        $this->assertTrue($this->geo->koordinatValid(0.0, 0.0));

        $this->assertFalse($this->geo->koordinatValid(91.0, 106.0));
        $this->assertFalse($this->geo->koordinatValid(-6.17, 181.0));
        $this->assertFalse($this->geo->koordinatValid(null, 106.8));
        $this->assertFalse($this->geo->koordinatValid(-6.17, null));
    }

    public function test_format_jarak(): void
    {
        $this->assertSame('45 m', $this->geo->formatJarak(45.4));
        $this->assertSame('1,2 km', $this->geo->formatJarak(1234));
        $this->assertSame('-', $this->geo->formatJarak(null));
    }
}
