<?php

declare(strict_types=1);

namespace SeQura\Demo\Controllers;

use SeQura\Demo\Response;

final class HealthController
{
    public function check(): Response
    {
        return Response::json(['status' => 'ok']);
    }
}
