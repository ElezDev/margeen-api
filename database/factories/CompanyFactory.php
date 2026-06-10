<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'document' => fake()->numerify('##########'),
            'phone' => fake()->numerify('3#########'),
            'address' => fake()->address(),
            'default_margin_percent' => 20,
            'invoice_prefix' => 'FAC',
            'next_invoice_number' => 1,
        ];
    }
}
