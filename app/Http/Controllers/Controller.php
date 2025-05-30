<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'OpenASM API',
    version: '1.0.0',
    description: 'OpenASM (Open Asset Management) REST API for comprehensive asset management with multi-tenancy support',
    contact: new OA\Contact(
        email: 'admin@openasm.com',
        name: 'OpenASM Support'
    ),
    license: new OA\License(
        name: 'MIT',
        url: 'https://opensource.org/licenses/MIT'
    )
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'OpenASM API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Enter your JWT token in the format: Bearer {token}'
)]
#[OA\Schema(
    schema: 'Error',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', description: 'Error message'),
        new OA\Property(property: 'error', type: 'string', description: 'Detailed error information'),
    ]
)]
#[OA\Schema(
    schema: 'Pagination',
    type: 'object',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', description: 'Current page number'),
        new OA\Property(property: 'last_page', type: 'integer', description: 'Last page number'),
        new OA\Property(property: 'per_page', type: 'integer', description: 'Items per page'),
        new OA\Property(property: 'total', type: 'integer', description: 'Total number of items'),
        new OA\Property(property: 'from', type: 'integer', description: 'First item number on current page'),
        new OA\Property(property: 'to', type: 'integer', description: 'Last item number on current page'),
    ]
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    type: 'object',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', description: 'Current page number'),
        new OA\Property(property: 'from', type: 'integer', nullable: true, description: 'First item number on current page'),
        new OA\Property(property: 'last_page', type: 'integer', description: 'Last page number'),
        new OA\Property(property: 'path', type: 'string', description: 'Base URL for pagination'),
        new OA\Property(property: 'per_page', type: 'integer', description: 'Items per page'),
        new OA\Property(property: 'to', type: 'integer', nullable: true, description: 'Last item number on current page'),
        new OA\Property(property: 'total', type: 'integer', description: 'Total number of items'),
        new OA\Property(
            property: 'links',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'url', type: 'string', nullable: true, description: 'Page URL'),
                    new OA\Property(property: 'label', type: 'string', description: 'Page label'),
                    new OA\Property(property: 'active', type: 'boolean', description: 'Whether this is the current page'),
                ]
            ),
            description: 'Pagination links'
        ),
    ]
)]
#[OA\Schema(
    schema: 'CreateAssetRequest',
    type: 'object',
    required: ['customer_id', 'serial_number', 'name'],
    properties: [
        new OA\Property(property: 'customer_id', type: 'string', format: 'uuid', description: 'Customer ID'),
        new OA\Property(property: 'location_id', type: 'string', format: 'uuid', nullable: true, description: 'Location ID'),
        new OA\Property(property: 'oem_id', type: 'string', format: 'uuid', nullable: true, description: 'OEM ID'),
        new OA\Property(property: 'product_id', type: 'string', format: 'uuid', nullable: true, description: 'Product ID'),
        new OA\Property(property: 'type_id', type: 'string', format: 'uuid', nullable: true, description: 'Asset type ID'),
        new OA\Property(property: 'status_id', type: 'string', format: 'uuid', nullable: true, description: 'Status ID'),
        new OA\Property(property: 'serial_number', type: 'string', maxLength: 255, description: 'Unique serial number'),
        new OA\Property(property: 'asset_tag', type: 'string', maxLength: 255, nullable: true, description: 'Asset tag'),
        new OA\Property(property: 'model_number', type: 'string', maxLength: 255, nullable: true, description: 'Model number'),
        new OA\Property(property: 'part_number', type: 'string', maxLength: 255, nullable: true, description: 'Part number'),
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Asset name'),
        new OA\Property(property: 'description', type: 'string', maxLength: 2000, nullable: true, description: 'Asset description'),
        new OA\Property(property: 'purchase_date', type: 'string', format: 'date', nullable: true, description: 'Purchase date'),
        new OA\Property(property: 'installation_date', type: 'string', format: 'date', nullable: true, description: 'Installation date'),
        new OA\Property(property: 'warranty_start_date', type: 'string', format: 'date', nullable: true, description: 'Warranty start date'),
        new OA\Property(property: 'warranty_end_date', type: 'string', format: 'date', nullable: true, description: 'Warranty end date'),
        new OA\Property(property: 'purchase_price', type: 'number', format: 'float', minimum: 0, nullable: true, description: 'Purchase price'),
        new OA\Property(property: 'current_value', type: 'number', format: 'float', minimum: 0, nullable: true, description: 'Current value'),
        new OA\Property(property: 'is_active', type: 'boolean', default: true, description: 'Active status'),
        new OA\Property(property: 'metadata', type: 'object', nullable: true, description: 'Additional metadata'),
        new OA\Property(
            property: 'tags',
            type: 'array',
            items: new OA\Items(type: 'string', format: 'uuid'),
            nullable: true,
            description: 'Array of tag IDs'
        ),
    ]
)]
#[OA\Schema(
    schema: 'UpdateAssetRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'customer_id', type: 'string', format: 'uuid', description: 'Customer ID'),
        new OA\Property(property: 'location_id', type: 'string', format: 'uuid', nullable: true, description: 'Location ID'),
        new OA\Property(property: 'oem_id', type: 'string', format: 'uuid', nullable: true, description: 'OEM ID'),
        new OA\Property(property: 'product_id', type: 'string', format: 'uuid', nullable: true, description: 'Product ID'),
        new OA\Property(property: 'type_id', type: 'string', format: 'uuid', nullable: true, description: 'Asset type ID'),
        new OA\Property(property: 'status_id', type: 'string', format: 'uuid', nullable: true, description: 'Status ID'),
        new OA\Property(property: 'serial_number', type: 'string', maxLength: 255, description: 'Unique serial number'),
        new OA\Property(property: 'asset_tag', type: 'string', maxLength: 255, nullable: true, description: 'Asset tag'),
        new OA\Property(property: 'model_number', type: 'string', maxLength: 255, nullable: true, description: 'Model number'),
        new OA\Property(property: 'part_number', type: 'string', maxLength: 255, nullable: true, description: 'Part number'),
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Asset name'),
        new OA\Property(property: 'description', type: 'string', maxLength: 2000, nullable: true, description: 'Asset description'),
        new OA\Property(property: 'purchase_date', type: 'string', format: 'date', nullable: true, description: 'Purchase date'),
        new OA\Property(property: 'installation_date', type: 'string', format: 'date', nullable: true, description: 'Installation date'),
        new OA\Property(property: 'warranty_start_date', type: 'string', format: 'date', nullable: true, description: 'Warranty start date'),
        new OA\Property(property: 'warranty_end_date', type: 'string', format: 'date', nullable: true, description: 'Warranty end date'),
        new OA\Property(property: 'purchase_price', type: 'number', format: 'float', minimum: 0, nullable: true, description: 'Purchase price'),
        new OA\Property(property: 'current_value', type: 'number', format: 'float', minimum: 0, nullable: true, description: 'Current value'),
        new OA\Property(property: 'is_active', type: 'boolean', description: 'Active status'),
        new OA\Property(property: 'metadata', type: 'object', nullable: true, description: 'Additional metadata'),
        new OA\Property(
            property: 'tags',
            type: 'array',
            items: new OA\Items(type: 'string', format: 'uuid'),
            nullable: true,
            description: 'Array of tag IDs'
        ),
    ]
)]
#[OA\Schema(
    schema: 'CreateCustomerRequest',
    type: 'object',
    required: ['name', 'customer_code'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Customer name'),
        new OA\Property(property: 'customer_code', type: 'string', maxLength: 50, description: 'Unique customer code'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, nullable: true, description: 'Customer email'),
        new OA\Property(property: 'phone', type: 'string', maxLength: 50, nullable: true, description: 'Customer phone'),
        new OA\Property(property: 'website', type: 'string', format: 'url', maxLength: 255, nullable: true, description: 'Customer website'),
        new OA\Property(property: 'address_line_1', type: 'string', maxLength: 255, nullable: true, description: 'Address line 1'),
        new OA\Property(property: 'address_line_2', type: 'string', maxLength: 255, nullable: true, description: 'Address line 2'),
        new OA\Property(property: 'city', type: 'string', maxLength: 100, nullable: true, description: 'City'),
        new OA\Property(property: 'state', type: 'string', maxLength: 100, nullable: true, description: 'State/Province'),
        new OA\Property(property: 'postal_code', type: 'string', maxLength: 20, nullable: true, description: 'Postal code'),
        new OA\Property(property: 'country', type: 'string', maxLength: 100, nullable: true, description: 'Country'),
        new OA\Property(property: 'is_active', type: 'boolean', default: true, description: 'Active status'),
        new OA\Property(property: 'metadata', type: 'object', nullable: true, description: 'Additional metadata'),
    ]
)]
#[OA\Schema(
    schema: 'UpdateCustomerRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Customer name'),
        new OA\Property(property: 'customer_code', type: 'string', maxLength: 50, description: 'Unique customer code'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, nullable: true, description: 'Customer email'),
        new OA\Property(property: 'phone', type: 'string', maxLength: 50, nullable: true, description: 'Customer phone'),
        new OA\Property(property: 'website', type: 'string', format: 'url', maxLength: 255, nullable: true, description: 'Customer website'),
        new OA\Property(property: 'address_line_1', type: 'string', maxLength: 255, nullable: true, description: 'Address line 1'),
        new OA\Property(property: 'address_line_2', type: 'string', maxLength: 255, nullable: true, description: 'Address line 2'),
        new OA\Property(property: 'city', type: 'string', maxLength: 100, nullable: true, description: 'City'),
        new OA\Property(property: 'state', type: 'string', maxLength: 100, nullable: true, description: 'State/Province'),
        new OA\Property(property: 'postal_code', type: 'string', maxLength: 20, nullable: true, description: 'Postal code'),
        new OA\Property(property: 'country', type: 'string', maxLength: 100, nullable: true, description: 'Country'),
        new OA\Property(property: 'is_active', type: 'boolean', description: 'Active status'),
        new OA\Property(property: 'metadata', type: 'object', nullable: true, description: 'Additional metadata'),
    ]
)]
#[OA\Schema(
    schema: 'CustomerResource',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', description: 'Customer ID'),
        new OA\Property(property: 'organization_id', type: 'string', format: 'uuid', description: 'Organization ID'),
        new OA\Property(property: 'name', type: 'string', description: 'Customer name'),
        new OA\Property(property: 'customer_code', type: 'string', description: 'Customer code'),
        new OA\Property(property: 'email', type: 'string', nullable: true, description: 'Customer email'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, description: 'Customer phone'),
        new OA\Property(property: 'website', type: 'string', nullable: true, description: 'Customer website'),
        new OA\Property(property: 'address_line_1', type: 'string', nullable: true, description: 'Address line 1'),
        new OA\Property(property: 'address_line_2', type: 'string', nullable: true, description: 'Address line 2'),
        new OA\Property(property: 'city', type: 'string', nullable: true, description: 'City'),
        new OA\Property(property: 'state', type: 'string', nullable: true, description: 'State/Province'),
        new OA\Property(property: 'postal_code', type: 'string', nullable: true, description: 'Postal code'),
        new OA\Property(property: 'country', type: 'string', nullable: true, description: 'Country'),
        new OA\Property(property: 'is_active', type: 'boolean', description: 'Active status'),
        new OA\Property(property: 'data_quality_score', type: 'integer', description: 'Data quality score (0-100)'),
        new OA\Property(property: 'assets_count', type: 'integer', description: 'Number of assets'),
        new OA\Property(property: 'metadata', type: 'object', nullable: true, description: 'Additional metadata'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', description: 'Creation timestamp'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', description: 'Last update timestamp'),
    ]
)]
#[OA\Schema(
    schema: 'CustomerCollection',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/CustomerResource'),
            description: 'Array of customer resources'
        ),
        new OA\Property(property: 'meta', ref: '#/components/schemas/Pagination', description: 'Pagination metadata'),
        new OA\Property(
            property: 'links',
            type: 'object',
            properties: [
                new OA\Property(property: 'first', type: 'string', nullable: true, description: 'First page URL'),
                new OA\Property(property: 'last', type: 'string', nullable: true, description: 'Last page URL'),
                new OA\Property(property: 'prev', type: 'string', nullable: true, description: 'Previous page URL'),
                new OA\Property(property: 'next', type: 'string', nullable: true, description: 'Next page URL'),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'OrganizationResource',
    title: 'Organization Resource',
    description: 'Organization data representation',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', description: 'Organization unique identifier'),
        new OA\Property(property: 'name', type: 'string', description: 'Organization name'),
        new OA\Property(property: 'code', type: 'string', description: 'Organization code'),
        new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Organization email'),
        new OA\Property(property: 'phone', type: 'string', description: 'Organization phone'),
        new OA\Property(property: 'website', type: 'string', format: 'uri', description: 'Organization website'),
        new OA\Property(property: 'description', type: 'string', description: 'Organization description'),
        new OA\Property(property: 'address', type: 'string', description: 'Organization address'),
        new OA\Property(property: 'city', type: 'string', description: 'Organization city'),
        new OA\Property(property: 'state', type: 'string', description: 'Organization state'),
        new OA\Property(property: 'country', type: 'string', description: 'Organization country'),
        new OA\Property(property: 'postal_code', type: 'string', description: 'Organization postal code'),
        new OA\Property(property: 'is_active', type: 'boolean', description: 'Whether the organization is active'),
        new OA\Property(property: 'metadata', type: 'object', description: 'Additional organization metadata'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', description: 'Creation timestamp'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', description: 'Last update timestamp'),
    ]
)]
#[OA\Schema(
    schema: 'Organization',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', description: 'Organization unique identifier'),
        new OA\Property(property: 'name', type: 'string', description: 'Organization name'),
        new OA\Property(property: 'code', type: 'string', description: 'Organization code'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, description: 'Organization email'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, description: 'Organization phone'),
        new OA\Property(property: 'website', type: 'string', format: 'uri', nullable: true, description: 'Organization website'),
        new OA\Property(property: 'description', type: 'string', nullable: true, description: 'Organization description'),
        new OA\Property(property: 'address', type: 'string', nullable: true, description: 'Organization address'),
        new OA\Property(property: 'city', type: 'string', nullable: true, description: 'Organization city'),
        new OA\Property(property: 'state', type: 'string', nullable: true, description: 'Organization state'),
        new OA\Property(property: 'country', type: 'string', nullable: true, description: 'Organization country'),
        new OA\Property(property: 'postal_code', type: 'string', nullable: true, description: 'Organization postal code'),
        new OA\Property(property: 'is_active', type: 'boolean', description: 'Whether the organization is active'),
        new OA\Property(property: 'metadata', type: 'object', nullable: true, description: 'Additional organization metadata'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', description: 'Creation timestamp'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', description: 'Last update timestamp'),
    ]
)]
#[OA\Schema(
    schema: 'User',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'first_name', type: 'string', nullable: true),
        new OA\Property(property: 'last_name', type: 'string', nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'title', type: 'string', nullable: true),
        new OA\Property(property: 'department', type: 'string', nullable: true),
        new OA\Property(property: 'organization_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'organization', ref: '#/components/schemas/Organization', nullable: true),
    ]
)]
abstract class Controller {}
