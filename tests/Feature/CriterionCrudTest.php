<?php

namespace Tests\Feature;

use App\Models\AhpComparison;
use App\Models\Criterion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CriterionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_storing_a_criterion_creates_both_directions_of_every_new_matrix_pair(): void
    {
        $user = User::factory()->create();
        $firstCriterion = Criterion::query()->create(['code' => 'C1', 'name' => 'Akademik']);
        $secondCriterion = Criterion::query()->create(['code' => 'C2', 'name' => 'Kehadiran']);

        $response = $this->actingAs($user)->post(route('criteria.store'), [
            'name' => 'Prestasi',
            'code' => 'c3',
        ]);

        $response->assertRedirect(route('criteria.index'));

        $newCriterion = Criterion::query()->where('code', 'C3')->firstOrFail();

        foreach ([$firstCriterion, $secondCriterion] as $existingCriterion) {
            $this->assertDatabaseHas('ahp_comparisons', [
                'criterion_a_id' => $newCriterion->id,
                'criterion_b_id' => $existingCriterion->id,
                'value' => 1.0,
            ]);
            $this->assertDatabaseHas('ahp_comparisons', [
                'criterion_a_id' => $existingCriterion->id,
                'criterion_b_id' => $newCriterion->id,
                'value' => 1.0,
            ]);
        }

        $this->assertDatabaseHas('ahp_comparisons', [
            'criterion_a_id' => $newCriterion->id,
            'criterion_b_id' => $newCriterion->id,
            'value' => 1.0,
        ]);
    }

    public function test_destroying_a_criterion_removes_its_matrix_pairs(): void
    {
        $user = User::factory()->create();
        $criterion = Criterion::query()->create(['code' => 'C1', 'name' => 'Akademik']);
        $otherCriterion = Criterion::query()->create(['code' => 'C2', 'name' => 'Kehadiran']);
        AhpComparison::query()->create([
            'criterion_a_id' => $criterion->id,
            'criterion_b_id' => $otherCriterion->id,
            'value' => 1.0,
        ]);
        AhpComparison::query()->create([
            'criterion_a_id' => $otherCriterion->id,
            'criterion_b_id' => $criterion->id,
            'value' => 1.0,
        ]);

        $response = $this->actingAs($user)->delete(route('criteria.destroy', $criterion));

        $response->assertRedirect(route('criteria.index'));
        $this->assertSoftDeleted($criterion);
        $this->assertDatabaseMissing('ahp_comparisons', ['criterion_a_id' => $criterion->id]);
        $this->assertDatabaseMissing('ahp_comparisons', ['criterion_b_id' => $criterion->id]);
    }
}
