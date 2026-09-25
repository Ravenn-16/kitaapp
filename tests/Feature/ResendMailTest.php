<?php

namespace Tests\Feature;

use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResendMailTest extends TestCase
{
    public function test_resend_driver_resolves_with_the_installed_sdk_without_smtp(): void
    {
        config(['mail.default' => 'resend', 'services.resend.key' => 're_test_placeholder']);

        $this->assertInstanceOf(ResendTransport::class, Mail::mailer()->getSymfonyTransport());
    }
}
