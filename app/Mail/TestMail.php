<?php

declare(strict_types=1);

namespace App\Mail;

use Goletter\Mail\Mailable;

class TestMail extends Mailable
{
    public function __construct(
        public string $userName = 'Tester',
    ) {
    }

    public function build(): void
    {
        $this->subject('订单已发货')
            ->htmlView('emails.orders.shipped')
            ->with([
                'userName' => $this->userName,
                // 'orderNo' => 'SO20260322001',
                // 'trackingNo' => 'SF1234567890',
            ]);
    }
}
