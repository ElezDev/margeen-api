<?php

namespace Tests\Unit;

use App\Support\MoneyFormatter;
use PHPUnit\Framework\TestCase;

class MoneyFormatterTest extends TestCase
{
    public function test_formats_colombian_pesos(): void
    {
        $this->assertSame('$ 2', MoneyFormatter::cop(2));
        $this->assertSame('$ 24.000', MoneyFormatter::cop(24000));
        $this->assertSame('$ 1.250.000', MoneyFormatter::cop(1250000));
    }
}
