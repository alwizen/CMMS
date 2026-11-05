<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Equipment;
use App\Models\MaintenancePlan;
use App\Models\MaintenancePlanActivity;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use App\Models\WorkOrderWorker;
use Illuminate\Database\Seeder;

class MovFlowDemoSeeder extends Seeder
{
    /**
     * Demo rantai lengkap: Activity -> Plan -> Schedule -> Work Order -> History.
     *
     * Contoh equipment: MOV-TGL-502B (mengikuti equipment_type yang terpasang).
     */
    public function run(): void
    {
        $equipment = Equipment::query()->where('tag_number', 'MOV-TGL-502B')->first();

        if (! $equipment) {
            $this->command?->warn('Equipment MOV-TGL-502B tidak ditemukan, seeder dilewati.');

            return;
        }

        $coordinator = User::query()->orderBy('id')->firstOrFail();

        $activities = collect([
            [
                'name' => 'Inspeksi Visual & Kebocoran Seal',
                'type' => 'visual_check',
                'maintenance_classification' => 'preventive',
                'interval' => 'monthly',
                'answer_type' => 'qualitative',
                'reference' => 'Cek kebocoran di flange, gland packing, dan body valve; pastikan tidak ada korosi atau rembesan.',
                'optimum' => null,
                'minimum' => null,
                'maximum' => null,
                'unit' => null,
            ],
            [
                'name' => 'Uji Operasi Buka-Tutup Valve',
                'type' => 'testing',
                'maintenance_classification' => 'preventive',
                'interval' => 'monthly',
                'answer_type' => 'quantitative',
                'reference' => 'Operasikan valve full open lalu full close, catat waktu tempuh aktuator.',
                'optimum' => 30,
                'minimum' => null,
                'maximum' => 45,
                'unit' => 'detik',
            ],
            [
                'name' => 'Pelumasan Grease Stem & Gearbox Aktuator',
                'type' => 'lubrication',
                'maintenance_classification' => 'preventive',
                'interval' => 'quarterly',
                'answer_type' => 'qualitative',
                'reference' => 'Bersihkan stem, beri grease sesuai spesifikasi pabrikan; cek kondisi gearbox.',
                'optimum' => null,
                'minimum' => null,
                'maximum' => null,
                'unit' => null,
            ],
        ])->map(fn (array $data): Activity => Activity::query()->firstOrCreate(
            [
                'equipment_type_id' => $equipment->equipment_type_id,
                'name' => $data['name'],
            ],
            $data + ['status' => true],
        ));

        $startDate = now()->startOfMonth()->addMonth();

        $plan = MaintenancePlan::query()->firstOrCreate(
            [
                'equipment_id' => $equipment->id,
                'description' => 'Demo alur pemeliharaan MOV-TGL-502B',
            ],
            [
                'maintenance_classification' => 'Preventive',
                'interval' => 'monthly',
                'start_date' => $startDate->toDateString(),
                'end_date' => $startDate->copy()->addMonths(11)->toDateString(),
                'created_by' => $coordinator->id,
                'technician_coordinator_id' => $coordinator->id,
                'status' => 'Active',
            ],
        );

        $activities->values()->each(function (Activity $activity, int $index) use ($plan): void {
            MaintenancePlanActivity::query()->firstOrCreate(
                [
                    'maintenance_plan_id' => $plan->id,
                    'activity_id' => $activity->id,
                ],
                ['sort_order' => $index + 1],
            );
        });

        $schedules = collect(range(0, 11))->map(fn (int $month): MaintenanceSchedule => MaintenanceSchedule::query()->firstOrCreate(
            [
                'maintenance_plan_id' => $plan->id,
                'scheduled_date' => $startDate->copy()->addMonths($month)->toDateString(),
            ],
            [
                'equipment_id' => $equipment->id,
                'status' => MaintenanceSchedule::STATUSES['Scheduled'],
                'description' => $plan->description,
            ],
        ));

        $firstSchedule = $schedules->first();

        $workOrder = WorkOrder::query()->firstOrCreate(
            ['maintenance_schedule_id' => $firstSchedule->id],
            [
                'equipment_id' => $equipment->id,
                'maintenance_plan_id' => $plan->id,
                'maintenance_request_id' => null,
                'issued_by' => $coordinator->id,
                'technician_coordinator_id' => $coordinator->id,
                'classification' => 'Preventive',
                'interval' => 'monthly',
                'start_at' => $firstSchedule->scheduled_date->copy()->setTime(8, 0),
                'finish_at' => $firstSchedule->scheduled_date->copy()->setTime(10, 30),
                'note' => 'Demo alur preventif MOV-TGL-502B',
                'status' => 'In Progress',
            ],
        );

        $activities->values()->each(function (Activity $activity) use ($workOrder): void {
            WorkOrderActivity::query()->firstOrCreate(
                [
                    'work_order_id' => $workOrder->id,
                    'activity_id' => $activity->id,
                ],
                [
                    'reference' => $activity->reference,
                    'unit' => $activity->unit,
                    'pre_inspection' => 'Sesuai kondisi awal',
                    'final_result' => $activity->answer_type === 'quantitative'
                        ? (string) $activity->optimum
                        : 'OK',
                    'executed' => true,
                    'note' => 'Demo hasil eksekusi teknisi',
                ],
            );
        });

        WorkOrderWorker::query()->firstOrCreate(
            [
                'work_order_id' => $workOrder->id,
                'user_id' => $coordinator->id,
            ],
            [
                'role' => 'Teknisi',
                'started_at' => $workOrder->start_at,
                'finished_at' => $workOrder->finish_at,
                'note' => 'Demo pelaksana',
            ],
        );

        if ($workOrder->status !== 'Completed') {
            $workOrder->update(['status' => 'Completed']);
        }

        $firstSchedule->update(['status' => MaintenanceSchedule::STATUSES['Completed']]);

        $this->command?->info("Demo MOV-TGL-502B siap: plan #{$plan->id}, {$schedules->count()} schedule, WO {$workOrder->work_order_number}.");
    }
}
