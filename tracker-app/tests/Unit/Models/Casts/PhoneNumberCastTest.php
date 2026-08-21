<?php


declare(strict_types=1);

namespace Tests\Unit\Models\Casts;

use App\Models\Casts\PhoneNumberCast;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class PhoneNumberCastTest extends TestCase
{
    public function test_get_formats_a_ten_digit_phone_number(): void
    {
        $subject = new PhoneNumberCast();
        $model = new class extends Model
        {
        };

        $result = $subject->get($model, 'phone_number', '3213213144', []);

        $this->assertSame('321-321-3144', $result);
    }

    public function test_get_returns_null_for_null_value(): void
    {
        $subject = new PhoneNumberCast();
        $model = new class extends Model
        {
        };

        $result = $subject->get($model, 'phone_number', null, []);

        $this->assertNull($result);
    }

    public function test_set_strips_non_numeric_characters_for_storage(): void
    {
        $subject = new PhoneNumberCast();
        $model = new class extends Model
        {
        };

        $result = $subject->set($model, 'phone_number', '(321) 321-3144', []);

        $this->assertSame('3213213144', $result);
    }

    public function test_get_leaves_non_ten_digit_values_unmodified(): void
    {
        $subject = new PhoneNumberCast();
        $model = new class extends Model
        {
        };

        $result = $subject->get($model, 'phone_number', '321-321-314', []);

        $this->assertSame('321-321-314', $result);
    }
}
