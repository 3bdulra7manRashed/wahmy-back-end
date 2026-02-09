<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Wahmy API",
    description: "API Documentation for Wahmy Backend - Customer & Admin Authentication with JWT",
    contact: new OA\Contact(email: "support@wahmy.com")
)]
#[OA\Server(
    url: "/api/v1",
    description: "API v1 Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Enter JWT Bearer token"
)]
class OpenApi
{
    // This class serves as a container for OpenAPI attributes
}
