<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenanceHistory;
use App\Models\MaintenancePlan;
use App\Models\MaintenancePlanActivity;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSchedule;
use App\Models\MeterLog;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use App\Models\WorkOrderWorker;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DummySeeder extends Seeder
{
    public function run(): void
    {
        $roles = $this->seedRoles();
        $users = $this->seedUsers($roles);
        $types = $this->seedEquipmentTypes();
        $areas = $this->seedCompaniesAndAreas();
        $equipment = $this->seedEquipment($areas, $types);
        $activities = $this->seedActivities($types);
        $plans = $this->seedPlans($equipment, $users, $activities);

        $this->seedSchedulesAndWorkOrders($plans, $users);
        $this->seedRequestsAndCorrectiveWorkOrders($equipment, $users);
        $this->seedMeterLogs($equipment, $users);

        $this->command?->info(sprintf(
            'Dummy siap: %d company, %d area, %d equipment, %d activity, %d plan, %d schedule, %d request, %d WO, %d history, %d meter log.',
            Company::count(),
            Area::count(),
            Equipment::count(),
            Activity::count(),
            MaintenancePlan::count(),
            MaintenanceSchedule::count(),
            MaintenanceRequest::count(),
            WorkOrder::count(),
            MaintenanceHistory::count(),
            MeterLog::count(),
        ));
    }

    /**
     * @return array<string, Role>
     */
    protected function seedRoles(): array
    {
        $names = ['super_admin', 'pengawas', 'spv_mos', 'ltsa_tad'];

        $roles = [];

        foreach ($names as $name) {
            $roles[$name] = Role::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        return $roles;
    }

    /**
     * @param  array<string, Role>  $roles
     * @return array<string, User>
     */
    protected function seedUsers(array $roles): array
    {
        $definitions = [
            'admin' => ['Admin User', 'admin@admin.com', 'ADM-001', 'super_admin'],
            'pengawas' => ['Pengawas Lapangan', 'pengawas@demo.com', 'PGW-001', 'pengawas'],
            'spv' => ['SPV MOS', 'spv@demo.com', 'SPV-001', 'spv_mos'],
            'risna' => ['Risna', 'risna@demo.com', 'TAD-001', 'ltsa_tad'],
            'kholis' => ['Kholis', 'kholis@demo.com', 'TAD-002', 'ltsa_tad'],
            'behan' => ['Behan', 'behan@demo.com', 'LTSA-001', 'ltsa_tad'],
            'irwan' => ['Irwan', 'irwan@demo.com', 'LTSA-002', 'ltsa_tad'],
        ];

        $users = [];

        foreach ($definitions as $key => [$name, $email, $employeeNumber, $role]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'employee_number' => $employeeNumber,
                    'phone' => '0812'.fake()->numerify('########'),
                    'status' => true,
                ],
            );

            if (! $user->roles()->exists()) {
                $user->assignRole($roles[$role]);
            }

            $users[$key] = $user;
        }

        return $users;
    }

    /**
     * @return array<string, EquipmentType>
     */
    protected function seedEquipmentTypes(): array
    {
        $definitions = [
            'pump' => ['pump', 'Pump', 'Pompa distribusi fluida'],
            'tank' => ['tank', 'Storage Tank', 'Tangki penyimpanan'],
            'compressor' => ['compressor', 'Compressor', 'Kompresor udara'],
            'genset' => ['genset', 'Genset', 'Generator set'],
            'panel' => ['panel', 'Panel', 'Panel listrik kontrol'],
            'fire_pump' => ['fire_pump', 'Fire Pump', 'Pompa pemadam kebakaran'],
            'motor' => ['motor', 'Electric Motor', 'Motor penggerak'],
            'valve' => ['valve', 'Valve', 'Katup kontrol'],
        ];

        $types = [];

        foreach ($definitions as $key => [$code, $name, $description]) {
            $types[$key] = EquipmentType::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'description' => $description, 'status' => true],
            );
        }

        return $types;
    }

    /**
     * @return array<string, array{0: Company, 1: array<string, Area>}>
     */
    protected function seedCompaniesAndAreas(): array
    {
        $definitions = [
            'tegal' => [
                'company' => ['ft-tegal', 'Fuel Terminal Tegal', 'Fasilitas terminal bahan bakar Tegal'],
                'areas' => [
                    ['loading', 'Loading', 'Area loading arm'],
                    ['unloading', 'Unloading', 'Area unloading'],
                    ['storage', 'Storage', 'Area tangki penyimpanan'],
                    ['mcc', 'MCC', 'Motor control center'],
                ],
            ],
            'semarang' => [
                'company' => ['ft-semarang', 'Fuel Terminal Semarang', 'Fasilitas terminal bahan bakar Semarang'],
                'areas' => [
                    ['loading', 'Loading', 'Area loading arm'],
                    ['storage', 'Storage', 'Area tangki penyimpanan'],
                    ['utility', 'Utility', 'Area utilitas'],
                ],
            ],
            'cirebon' => [
                'company' => ['ft-cirebon', 'Fuel Terminal Cirebon', 'Fasilitas terminal bahan bakar Cirebon'],
                'areas' => [
                    ['loading', 'Loading', 'Area loading arm'],
                    ['storage', 'Storage', 'Area tangki penyimpanan'],
                ],
            ],
        ];

        $result = [];

        foreach ($definitions as $key => $definition) {
            [$code, $name, $description] = $definition['company'];

            $company = Company::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'description' => $description,
                    'is_active' => true,
                    'center_lat' => -6.87,
                    'center_lng' => 109.18,
                ],
            );

            $areas = [];

            foreach ($definition['areas'] as [$areaCode, $areaName, $areaDescription]) {
                $areas[$areaCode] = Area::firstOrCreate(
                    ['company_id' => $company->id, 'code' => $areaCode],
                    [
                        'name' => $areaName,
                        'description' => $areaDescription,
                        'is_active' => true,
                        'latitude' => -6.87 + fake()->randomFloat(4, -0.02, 0.02),
                        'longitude' => 109.18 + fake()->randomFloat(4, -0.02, 0.02),
                    ],
                );
            }

            $result[$key] = [$company, $areas];
        }

        return $result;
    }

    /**
     * @param  array<string, array{0: Company, 1: array<string, Area>}>  $companies
     * @param  array<string, EquipmentType>  $types
     * @return array<string, Equipment>
     */
    protected function seedEquipment(array $companies, array $types): array
    {
        $definitions = [
            ['p-101', 'loading', 'tegal', 'pump', 'P-101', 'Pompa transfer utama', 'GRUNDFOS', 'CR32', 'critical'],
            ['p-102', 'loading', 'tegal', 'pump', 'P-102', 'Pompa transfer cadangan', 'GRUNDFOS', 'CR32', 'high'],
            ['p-103', 'unloading', 'tegal', 'pump', 'P-103', 'Pompa unloading', 'EBARA', 'U-120', 'high'],
            ['t-201', 'storage', 'tegal', 'tank', 'T-201', 'Tangki premium 5000 KL', 'PERTAMINA', 'TK-5000', 'critical'],
            ['t-202', 'storage', 'tegal', 'tank', 'T-202', 'Tangki solar 3000 KL', 'PERTAMINA', 'TK-3000', 'critical'],
            ['c-301', 'mcc', 'tegal', 'compressor', 'C-301', 'Kompresor instrumen udara', 'ATLAS COPCO', 'GA-30', 'high'],
            ['g-401', 'mcc', 'tegal', 'genset', 'G-401', 'Genset darurat 500 kVA', 'CUMMINS', 'C500', 'critical'],
            ['pnl-501', 'mcc', 'tegal', 'panel', 'PNL-501', 'Panel MCC utama', 'SCHNEIDER', 'MCC-1', 'critical'],
            ['fp-601', 'loading', 'tegal', 'fire_pump', 'FP-601', 'Pompa pemadam utama', 'EBARA', 'FP-200', 'critical'],
            ['m-701', 'loading', 'tegal', 'motor', 'M-701', 'Motor loading arm', 'SIEMENS', '1LE1', 'medium'],
            ['v-801', 'unloading', 'tegal', 'valve', 'V-801', 'Valve kontrol aliran', 'EMERSON', 'CV-100', 'medium'],
            ['p-111', 'loading', 'semarang', 'pump', 'P-111', 'Pompa transfer Semarang', 'GRUNDFOS', 'CR20', 'high'],
            ['t-211', 'storage', 'semarang', 'tank', 'T-211', 'Tangki premium Semarang', 'PERTAMINA', 'TK-4000', 'critical'],
            ['c-311', 'utility', 'semarang', 'compressor', 'C-311', 'Kompresor utilitas', 'ATLAS COPCO', 'GA-22', 'medium'],
            ['g-411', 'utility', 'semarang', 'genset', 'G-411', 'Genset 250 kVA', 'CUMMINS', 'C250', 'high'],
            ['pnl-511', 'utility', 'semarang', 'panel', 'PNL-511', 'Panel distribusi', 'SCHNEIDER', 'MCC-2', 'high'],
            ['m-711', 'loading', 'semarang', 'motor', 'M-711', 'Motor pompa loading', 'SIEMENS', '1LE2', 'medium'],
            ['p-121', 'loading', 'cirebon', 'pump', 'P-121', 'Pompa transfer Cirebon', 'EBARA', 'U-90', 'high'],
            ['t-221', 'storage', 'cirebon', 'tank', 'T-221', 'Tangki solar Cirebon', 'PERTAMINA', 'TK-2500', 'critical'],
            ['g-421', 'storage', 'cirebon', 'genset', 'G-421', 'Genset 200 kVA', 'CUMMINS', 'C200', 'medium'],
            ['v-821', 'loading', 'cirebon', 'valve', 'V-821', 'Valve loading Cirebon', 'EMERSON', 'CV-90', 'medium'],
        ];

        $equipment = [];

        foreach ($definitions as [$key, $areaCode, $companyKey, $typeKey, $tag, $description, $manufacturer, $model, $criticality]) {
            /** @var Company $company */
            [$company, $areas] = $companies[$companyKey];
            $area = $areas[$areaCode] ?? reset($areas);

            $equipment[$key] = Equipment::firstOrCreate(
                ['tag_number' => $tag],
                [
                    'area_id' => $area->id,
                    'equipment_type_id' => $types[$typeKey]->id,
                    'description' => $description,
                    'manufacturer' => $manufacturer,
                    'model' => $model,
                    'serial_number' => 'SN-'.$tag,
                    'installation_date' => now()->subYears(fake()->numberBetween(1, 8))->toDateString(),
                    'operational_unit' => 'hour',
                    'status' => 'active',
                    'criticality' => $criticality,
                ],
            );
        }

        return $equipment;
    }

    /**
     * @param  array<string, EquipmentType>  $types
     * @return array<string, array<string, Activity>>
     */
    protected function seedActivities(array $types): array
    {
        $definitions = [
            'pump' => [
                'visual' => ['Inspeksi Visual & Kebocoran Seal', 'visual_check', 'preventive', 'monthly', 'qualitative', null, null, null, null],
                'vibration' => ['Ukur Vibrasi Bearing', 'measurement', 'predictive', 'monthly', 'quantitative', 2.8, null, 4.5, 'mm/s'],
                'greasing' => ['Pelumasan Bearing Pompa', 'lubrication', 'preventive', 'quarterly', 'qualitative', null, null, null, null],
                'pressure' => ['Cek Tekanan Discharge', 'measurement', 'preventive', 'monthly', 'quantitative', 4.5, 3.5, 5.5, 'bar'],
            ],
            'tank' => [
                'visual' => ['Inspeksi Visual Tangki', 'visual_check', 'preventive', 'quarterly', 'qualitative', null, null, null, null],
                'cleaning' => ['Pembersihan Internal Tangki', 'cleaning', 'preventive', 'yearly', 'qualitative', null, null, null, null],
                'pressure' => ['Uji Tekanan Tangki', 'testing', 'preventive', 'yearly', 'quantitative', 2.5, null, 3.0, 'bar'],
            ],
            'compressor' => [
                'filter' => ['Bersihkan Filter Udara', 'cleaning', 'preventive', 'monthly', 'qualitative', null, null, null, null],
                'oil' => ['Periksa Level Oli', 'inspection', 'preventive', 'monthly', 'qualitative', null, null, null, null],
                'relief' => ['Uji Pressure Relief Valve', 'testing', 'preventive', 'quarterly', 'quantitative', 8.0, null, 8.5, 'bar'],
            ],
            'genset' => [
                'oil' => ['Ganti Oli Mesin', 'maintenance', 'preventive', 'quarterly', 'qualitative', null, null, null, null],
                'load' => ['Uji Beban Genset', 'testing', 'preventive', 'monthly', 'qualitative', null, null, null, null],
                'battery' => ['Periksa Baterai & Terminal', 'inspection', 'preventive', 'monthly', 'quantitative', 24, 23, 27, 'V'],
            ],
            'panel' => [
                'electrical' => ['Ukur Tegangan & Arus Panel', 'measurement', 'preventive', 'quarterly', 'quantitative', 380, 360, 400, 'V'],
                'cleaning' => ['Bersihkan Panel dari Debu', 'cleaning', 'preventive', 'monthly', 'qualitative', null, null, null, null],
                'breaker' => ['Uji Circuit Breaker', 'testing', 'preventive', 'yearly', 'qualitative', null, null, null, null],
            ],
            'fire_pump' => [
                'pressure' => ['Uji Tekanan Sistem', 'testing', 'preventive', 'quarterly', 'quantitative', 7.0, null, 8.0, 'bar'],
                'flow' => ['Ukur Laju Aliran', 'measurement', 'preventive', 'semi_annual', 'quantitative', 500, 450, 550, 'lpm'],
            ],
            'motor' => [
                'lube' => ['Pelumasan Bearing Motor', 'lubrication', 'preventive', 'quarterly', 'qualitative', null, null, null, null],
                'insulation' => ['Uji Resistansi Isolasi', 'testing', 'preventive', 'yearly', 'quantitative', null, 1.0, null, 'MOhm'],
            ],
            'valve' => [
                'operation' => ['Uji Operasi Buka-Tutup Valve', 'testing', 'preventive', 'monthly', 'quantitative', 30, null, 45, 'detik'],
                'seal' => ['Inspeksi Kebocoran Seal', 'visual_check', 'preventive', 'quarterly', 'qualitative', null, null, null, null],
            ],
        ];

        $activities = [];

        foreach ($definitions as $typeKey => $items) {
            foreach ($items as $key => [$name, $type, $classification, $interval, $answerType, $optimum, $minimum, $maximum, $unit]) {
                $activities[$typeKey][$key] = Activity::firstOrCreate(
                    ['equipment_type_id' => $types[$typeKey]->id, 'name' => $name],
                    [
                        'type' => $type,
                        'maintenance_classification' => $classification,
                        'interval' => $interval,
                        'answer_type' => $answerType,
                        'reference' => 'Sesuai SOP pemeliharaan '.$types[$typeKey]->name,
                        'optimum' => $optimum,
                        'minimum' => $minimum,
                        'maximum' => $maximum,
                        'unit' => $unit,
                        'status' => true,
                    ],
                );
            }
        }

        return $activities;
    }

    /**
     * @param  array<string, Equipment>  $equipment
     * @param  array<string, User>  $users
     * @param  array<string, array<string, Activity>>  $activities
     * @return array<string, MaintenancePlan>
     */
    protected function seedPlans(array $equipment, array $users, array $activities): array
    {
        $definitions = [
            ['p-101', 'pump', 'monthly', -3, 9],
            ['p-102', 'pump', 'weekly', -2, 6],
            ['t-201', 'tank', 'quarterly', -4, 8],
            ['c-301', 'compressor', 'monthly', -2, 10],
            ['g-401', 'genset', 'monthly', -3, 9],
            ['pnl-501', 'panel', 'quarterly', -4, 8],
            ['fp-601', 'fire_pump', 'quarterly', -4, 8],
            ['m-701', 'motor', 'quarterly', -3, 9],
            ['p-111', 'pump', 'monthly', -2, 10],
            ['t-211', 'tank', 'quarterly', -3, 9],
            ['p-121', 'pump', 'monthly', -1, 11],
            ['v-801', 'valve', 'monthly', -2, 10],
        ];

        $plans = [];

        foreach ($definitions as [$equipmentKey, $typeKey, $interval, $startOffset, $endOffset]) {
            $item = $equipment[$equipmentKey];

            $plan = MaintenancePlan::firstOrCreate(
                [
                    'equipment_id' => $item->id,
                    'maintenance_classification' => 'Preventive',
                    'interval' => $interval,
                ],
                [
                    'start_date' => now()->addMonths($startOffset)->startOfMonth()->toDateString(),
                    'end_date' => now()->addMonths($endOffset)->endOfMonth()->toDateString(),
                    'created_by' => $users['admin']->id,
                    'technician_coordinator_id' => $users['kholis']->id,
                    'description' => 'Preventive '.$interval.' untuk '.$item->tag_number,
                    'status' => 'Active',
                ],
            );

            $order = 0;

            foreach ($activities[$typeKey] as $activity) {
                $order++;

                MaintenancePlanActivity::firstOrCreate(
                    ['maintenance_plan_id' => $plan->id, 'activity_id' => $activity->id],
                    ['sort_order' => $order],
                );
            }

            $plans[$equipmentKey] = $plan;
        }

        return $plans;
    }

    /**
     * @param  array<string, MaintenancePlan>  $plans
     * @param  array<string, User>  $users
     */
    protected function seedSchedulesAndWorkOrders(array $plans, array $users): void
    {
        $technicians = [$users['risna'], $users['kholis'], $users['behan'], $users['irwan']];

        foreach ($plans as $equipmentKey => $plan) {
            $cursor = Carbon::parse($plan->start_date);
            $end = Carbon::parse($plan->end_date);

            while ($cursor->lte($end)) {
                $date = $cursor->copy();

                if (! $date->isWeekend()) {
                    $schedule = MaintenanceSchedule::query()
                        ->where('maintenance_plan_id', $plan->id)
                        ->whereDate('scheduled_date', $date->toDateString())
                        ->first()
                        ?? MaintenanceSchedule::create([
                            'maintenance_plan_id' => $plan->id,
                            'equipment_id' => $plan->equipment_id,
                            'scheduled_date' => $date->toDateString(),
                            'status' => MaintenanceSchedule::STATUSES['Scheduled'],
                            'description' => 'Jadwal preventif '.$plan->interval.' '.$plan->equipment->tag_number,
                        ]);

                    $isPast = $date->lt(today());

                    if ($schedule->workOrders()->exists()) {
                        // sudah pernah dibuat, jangan duplikasi saat seeder dijalankan ulang
                    } elseif ($isPast) {
                        $this->createPreventiveWorkOrder($schedule, $plan, $users, $technicians, true);
                    } elseif ($date->lte(today()->addDays(5))) {
                        $this->createPreventiveWorkOrder($schedule, $plan, $users, $technicians, false);
                    }
                }

                $cursor = match ($plan->interval) {
                    'weekly' => $cursor->copy()->addWeek(),
                    'monthly' => $cursor->copy()->addMonth(),
                    'quarterly' => $cursor->copy()->addMonths(3),
                    default => $cursor->copy()->addMonth(),
                };
            }
        }
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<int, User>  $technicians
     */
    protected function createPreventiveWorkOrder(
        MaintenanceSchedule $schedule,
        MaintenancePlan $plan,
        array $users,
        array $technicians,
        bool $completed,
    ): WorkOrder {
        $start = Carbon::parse($schedule->scheduled_date)->setTime(8, 0);
        $finish = $start->copy()->addHours(fake()->numberBetween(1, 3));

        $workOrder = WorkOrder::create([
            'equipment_id' => $schedule->equipment_id,
            'maintenance_plan_id' => $plan->id,
            'maintenance_schedule_id' => $schedule->id,
            'issued_by' => $users['spv']->id,
            'technician_coordinator_id' => $technicians[array_rand($technicians)]->id,
            'classification' => 'Preventive',
            'interval' => $plan->interval,
            'start_at' => $start,
            'finish_at' => $completed ? $finish : null,
            'note' => 'Preventif '.$plan->interval.' — '.$schedule->description,
            'status' => WorkOrder::STATUSES['In Progress'],
        ]);

        $technician = $technicians[array_rand($technicians)];

        WorkOrderWorker::create([
            'work_order_id' => $workOrder->id,
            'user_id' => $technician->id,
            'role' => 'Teknisi',
            'started_at' => $start,
            'finished_at' => $completed ? $finish : null,
            'note' => 'Pelaksana preventif',
        ]);

        $planActivities = MaintenancePlanActivity::query()
            ->where('maintenance_plan_id', $plan->id)
            ->with('activity')
            ->orderBy('sort_order')
            ->get();

        foreach ($planActivities as $planActivity) {
            $activity = $planActivity->activity;

            if (! $activity) {
                continue;
            }

            WorkOrderActivity::create([
                'work_order_id' => $workOrder->id,
                'activity_id' => $activity->id,
                'reference' => $activity->reference,
                'unit' => $activity->unit,
                'pre_inspection' => $completed ? 'Kondisi awal sesuai standar' : null,
                'final_result' => $completed ? $this->sampleResult($activity) : null,
                'executed' => $completed,
                'note' => $completed ? 'Selesai dikerjakan, hasil dalam batas normal.' : null,
            ]);
        }

        if ($completed) {
            $workOrder->update(['status' => WorkOrder::STATUSES['Completed']]);
        } else {
            $schedule->update(['status' => MaintenanceSchedule::STATUSES['In Progress']]);
        }

        return $workOrder;
    }

    protected function sampleResult(Activity $activity): string
    {
        if ($activity->answer_type === 'quantitative') {
            $value = $activity->optimum ?? $activity->maximum ?? 0;

            return (string) $value;
        }

        return fake()->randomElement(['OK', 'Baik', 'Normal', 'Tidak ada kebocoran', 'Sesuai standar']);
    }

    /**
     * @param  array<string, Equipment>  $equipment
     * @param  array<string, User>  $users
     */
    protected function seedRequestsAndCorrectiveWorkOrders(array $equipment, array $users): void
    {
        $definitions = [
            ['p-103', 'Seal pompa bocor saat operasi', 'Stopped', 'Open', null],
            ['c-311', 'Kompresor berisik dan getaran tinggi', 'Running', 'Open', null],
            ['t-221', 'Level gauge tidak menunjukkan angka stabil', 'Standby', 'Open', null],
            ['v-821', 'Valve macet saat penutupan', 'Faulty', 'Open', null],
            ['m-711', 'Motor panas berlebih setelah 2 jam operasi', 'Running', 'Open', null],
            ['p-102', 'Tekanan discharge turun drastis', 'Stopped', 'Approved', 'kholis'],
            ['g-421', 'Genset gagal start otomatis', 'Stopped', 'Approved', 'behan'],
            ['pnl-511', 'Breaker trip berulang', 'Faulty', 'In Progress', 'risna'],
            ['t-202', 'Korosi pada sambungan pipa keluar', 'Running', 'In Progress', 'irwan'],
            ['c-301', 'Filter udara tersumbat', 'Running', 'Completed', 'behan'],
            ['p-121', 'Kebocoran pada flange suction', 'Stopped', 'Rejected', null],
            ['g-411', 'Baterai lemah, indikator kuning', 'Standby', 'Open', null],
        ];

        foreach ($definitions as $index => [$equipmentKey, $description, $operationStatus, $status, $technicianKey]) {
            $item = $equipment[$equipmentKey];
            $reporter = $index % 2 === 0 ? $users['pengawas'] : $users['risna'];
            $damageDate = now()->subDays(fake()->numberBetween(0, 20));

            $request = MaintenanceRequest::firstOrCreate(
                ['equipment_id' => $item->id, 'description' => $description],
                [
                    'reported_by' => $reporter->id,
                    'operation_status' => $operationStatus,
                    'damage_date' => $damageDate->toDateString(),
                    'damage_time' => $damageDate->format('H:i:s'),
                    'equipment_condition' => fake()->randomElement([
                        'Terdengar suara abnormal',
                        'Ada kebocoran terlihat',
                        'Indikator di luar batas normal',
                        'Panas berlebih pada komponen',
                    ]),
                    'impact' => fake()->randomElement(['Produksi terhenti', 'Kapasitas turun 20%', 'Risiko keselamatan', 'Tidak ada dampak produksi']),
                    'early_action' => fake()->randomElement(['Mesin dimatikan sementara', 'Beban dikurangi', 'Lapor supervisor', 'Isolasi area']),
                    'status' => $status,
                    'approval_notes' => in_array($status, ['Approved', 'In Progress', 'Completed'], true) ? 'Disetujui untuk ditindaklanjuti.' : null,
                    'approved_by' => in_array($status, ['Approved', 'In Progress', 'Completed'], true) ? $users['spv']->id : null,
                    'approved_at' => in_array($status, ['Approved', 'In Progress', 'Completed'], true) ? now()->subDays(1) : null,
                ],
            );

            if (! in_array($status, ['Approved', 'In Progress', 'Completed'], true)) {
                continue;
            }

            if (WorkOrder::where('maintenance_request_id', $request->id)->exists()) {
                continue;
            }

            $technician = $users[$technicianKey ?? 'kholis'];
            $start = now()->subDays(fake()->numberBetween(0, 5))->setTime(9, 0);

            $workOrder = WorkOrder::create([
                'equipment_id' => $item->id,
                'maintenance_request_id' => $request->id,
                'issued_by' => $users['spv']->id,
                'technician_coordinator_id' => $technician->id,
                'classification' => 'Corrective',
                'start_at' => $start,
                'finish_at' => $status === 'Completed' ? $start->copy()->addHours(3) : null,
                'note' => $description,
                'status' => match ($status) {
                    'Completed' => WorkOrder::STATUSES['In Progress'],
                    'In Progress' => WorkOrder::STATUSES['In Progress'],
                    default => WorkOrder::STATUSES['Pending'],
                },
            ]);

            WorkOrderWorker::create([
                'work_order_id' => $workOrder->id,
                'user_id' => $technician->id,
                'role' => 'Teknisi',
                'started_at' => $start,
                'finished_at' => $status === 'Completed' ? $start->copy()->addHours(3) : null,
            ]);

            WorkOrderActivity::create([
                'work_order_id' => $workOrder->id,
                'activity_id' => $this->pickActivityFor($item),
                'reference' => 'Perbaikan korektif sesuai temuan',
                'pre_inspection' => 'Ditemukan '.strtolower($description),
                'final_result' => $status === 'Completed' ? 'Diperbaiki dan diuji normal' : null,
                'follow_up' => $status === 'Completed' ? 'Monitor 1 minggu ke depan' : null,
                'executed' => $status === 'Completed',
                'note' => $status === 'Completed' ? 'Penggantian komponen dan pengujian fungsi selesai.' : null,
            ]);

            if ($status === 'Completed') {
                $workOrder->update(['status' => WorkOrder::STATUSES['Completed']]);
            }
        }
    }

    protected function pickActivityFor(Equipment $equipment): int
    {
        $activity = Activity::query()
            ->where('equipment_type_id', $equipment->equipment_type_id)
            ->orderBy('id')
            ->first();

        if ($activity) {
            return $activity->id;
        }

        $fallbackType = EquipmentType::query()->orderBy('id')->firstOrFail();

        return Activity::firstOrCreate(
            ['equipment_type_id' => $fallbackType->id, 'name' => 'Inspeksi Umum'],
            [
                'type' => 'inspection',
                'maintenance_classification' => 'preventive',
                'interval' => 'monthly',
                'answer_type' => 'qualitative',
                'status' => true,
            ],
        )->id;
    }

    /**
     * @param  array<string, Equipment>  $equipment
     * @param  array<string, User>  $users
     */
    protected function seedMeterLogs(array $equipment, array $users): void
    {
        $recorder = $users['kholis'];

        foreach ($equipment as $item) {
            $value = fake()->numberBetween(200, 1200);

            for ($month = 5; $month >= 0; $month--) {
                $value += fake()->numberBetween(40, 180);

                $readingDate = now()->subMonths($month)->startOfMonth()->toDateString();

                $exists = MeterLog::query()
                    ->where('equipment_id', $item->id)
                    ->whereDate('reading_date', $readingDate)
                    ->exists();

                if ($exists) {
                    continue;
                }

                MeterLog::create([
                    'equipment_id' => $item->id,
                    'reading_date' => $readingDate,
                    'value' => $value,
                    'recorded_by' => $recorder->id,
                ]);
            }
        }
    }
}
