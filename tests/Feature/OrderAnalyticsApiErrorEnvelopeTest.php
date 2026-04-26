<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OrderAnalyticsApiErrorEnvelopeTest extends TestCase
{
    public function test_form_request_validation_uses_envelope_with_validation_error_type(): void
    {
        $file = UploadedFile::fake()->create('orders.png', 10, 'image/png');

        $this->postJson('/api/orders/analytics', [
            'csv' => $file,
        ])->assertStatus(422)
            ->assertJsonPath('error.type', 'validation_error')
            ->assertJsonPath('error.message', 'Invalid input');
    }

    public function test_rejects_two_files_in_the_csv_field_when_both_reach_laravel(): void
    {
        $csv = UploadedFile::fake()->create('orders.csv', 100, 'text/csv');
        $other = UploadedFile::fake()->create('x.pdf', 10, 'application/pdf');

        $this->post('/api/orders/analytics', [
            'csv' => [$csv, $other],
        ], [
            'Accept' => 'application/json',
        ])->assertStatus(422);
    }

    public function test_rejects_txt_even_when_mime_looks_like_csv(): void
    {
        $file = UploadedFile::fake()->create('data.txt', 20, 'text/csv');

        $this->postJson('/api/orders/analytics', [
            'csv' => $file,
        ])->assertStatus(422);
    }

    public function test_rejects_disallowed_mime_for_csv_filename(): void
    {
        $file = UploadedFile::fake()->create('report.csv', 20, 'application/pdf');

        $this->postJson('/api/orders/analytics', [
            'csv' => $file,
        ])->assertStatus(422);
    }
}
