<?php

namespace Tests\Unit\Http\Requests\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    use RefreshDatabase;

    private LoginRequest $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new LoginRequest();
    }

    public function test_authorize_returns_true(): void
    {
        $this->assertTrue($this->subject->authorize());
    }

    public function test_prepare_for_validation_handles_remember_me_and_username(): void
    {
        $input_data = [
            'username' => 'TK-421',
            'remember_me' => 'Y',
        ];

        $this->subject->merge($input_data);

        $this->invokeMethod($this->subject, 'prepareForValidation');

        $this->assertTrue($this->subject->input('remember_me'));
        $this->assertEquals('TK-421', $this->subject->input(Trooper::USERNAME));
    }

    public function test_prepare_for_validation_handles_missing_remember_me(): void
    {
        $input_data = [
            'username' => 'TK-421',
        ];

        $this->subject->merge($input_data);

        $this->invokeMethod($this->subject, 'prepareForValidation');

        $this->assertFalse($this->subject->input('remember_me'));
    }

    public function test_validation_fails_with_invalid_data(): void
    {
        // The username does not exist
        $bad_data = [
            Trooper::USERNAME => 'FN-2187',
            Trooper::PASSWORD => 'password',
        ];

        $validator = Validator::make($bad_data, $this->subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Trooper::USERNAME));
    }

    public function test_validation_fails_with_missing_data(): void
    {
        $validator = Validator::make([], $this->subject->rules());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(Trooper::USERNAME));
        $this->assertTrue($validator->errors()->has(Trooper::PASSWORD));
    }
}
