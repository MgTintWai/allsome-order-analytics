<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OrderAnalyticsApiTest extends TestCase
{
    public function test_post_uploaded_csv_produces_expected_interview_output(): void
    {
        $path = base_path('tests/Fixtures/allsome_interview_test_orders.csv');

        $response = $this->post('/api/orders/analytics', [
            'csv' => new UploadedFile(
                $path,
                'allsome_interview_test_orders.csv',
                'text/csv',
                null,
                true
            ),
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('total_revenue', 710.0)
            ->assertJsonPath('best_selling_sku.sku', 'SKU-A123')
            ->assertJsonPath('best_selling_sku.total_quantity', 5);

        $this->assertStringContainsString('"total_revenue":710.00', $response->getContent());
    }
}
