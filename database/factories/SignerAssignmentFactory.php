<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\SignerAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SignerAssignment>
 */
class SignerAssignmentFactory extends Factory
{
    protected $model = SignerAssignment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'signer_id' => User::factory()->signer(),
            'order_index' => 1,
            'status' => 'pending',
        ];
    }

    /**
     * Assignment with notified status.
     */
    public function notified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'notified',
            'notified_at' => now(),
        ]);
    }

    /**
     * Assignment with signed status.
     */
    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'signed',
            'signed_at' => now(),
        ]);
    }

    /**
     * Assignment with rejected status.
     */
    public function rejected(string $reason = 'Dokumen perlu direvisi'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }
}
