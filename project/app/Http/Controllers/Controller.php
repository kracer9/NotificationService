<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Notification Service",
    description: "Сервис рассылки уведомлений",
)]
abstract class Controller
{
    //
}
