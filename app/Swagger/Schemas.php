<?php

declare(strict_types=1);

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SuccessResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", type: "object", nullable: true)
    ]
)]
#[OA\Schema(
    schema: "ErrorResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: false),
        new OA\Property(property: "message", type: "string", example: "Error message")
    ]
)]
#[OA\Schema(
    schema: "ValidationErrorResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: false),
        new OA\Property(property: "message", type: "string", example: "Validation failed."),
        new OA\Property(property: "errors", type: "object", example: ["field" => ["The field is required."]])
    ]
)]
#[OA\Schema(
    schema: "TokenResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(
            property: "data",
            properties: [
                new OA\Property(property: "access_token", type: "string", example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
                new OA\Property(property: "token_type", type: "string", example: "bearer"),
                new OA\Property(property: "expires_in", type: "integer", example: 3600)
            ],
            type: "object"
        )
    ]
)]
#[OA\Schema(
    schema: "UserResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(
            property: "data",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "name", type: "string", example: "Ahmed"),
                new OA\Property(property: "email", type: "string", nullable: true, example: "admin@wahmy.com"),
                new OA\Property(property: "phone", type: "string", nullable: true, example: "0500000000"),
                new OA\Property(property: "role", type: "string", example: "customer"),
                new OA\Property(property: "is_active", type: "boolean", example: true),
                new OA\Property(property: "phone_verified_at", type: "string", format: "date-time", nullable: true),
                new OA\Property(property: "email_verified_at", type: "string", format: "date-time", nullable: true),
                new OA\Property(property: "created_at", type: "string", format: "date-time"),
                new OA\Property(property: "updated_at", type: "string", format: "date-time")
            ],
            type: "object"
        )
    ]
)]
class Schemas
{
    // This class serves as a container for OpenAPI schema definitions
}
