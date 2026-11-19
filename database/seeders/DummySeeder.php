<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\Geo;
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
        $this->assignEquipmentTypeResponsibles($types, $users);
        $areas = $this->seedCompaniesAndAreas();
        $equipment = $this->seedEquipment($areas, $types);
        $this->seedActivities($types);

        $this->seedRequests($equipment, $users);

        $this->command?->info(sprintf(
            'Dummy siap: %d company, %d area, %d equipment, %d activity, %d request, %d WO.',
            Company::count(),
            Area::count(),
            Equipment::count(),
            Activity::count(),
            MaintenanceRequest::count(),
            WorkOrder::count(),
        ));
    }

    /**
     * @return array<string, Role>
     */
    protected function seedRoles(): array
    {
        $names = ['super_admin', 'pengawas', 'spv_mos', 'spv_gos', 'spv_hsse', 'ltsa_tad'];

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
            'spvgos' => ['SPV GOS', 'spvgos@demo.com', 'SPV-GOS-001', 'spv_gos'],
            'spvhsse' => ['SPV HSSE', 'spvhsse@demo.com', 'SPV-HSSE-001', 'spv_hsse'],
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
     * Tetapkan penanggung jawab (teknisi) pada tiap tipe equipment.
     *
     * @param  array<string, EquipmentType>  $types
     * @param  array<string, User>  $users
     */
    protected function assignEquipmentTypeResponsibles(array $types, array $users): void
    {
        $technicians = [$users['risna'], $users['kholis'], $users['behan'], $users['irwan']];
        $index = 0;

        foreach ($types as $type) {
            if (blank($type->responsible_user_id)) {
                $type->update(['responsible_user_id' => $technicians[$index % count($technicians)]->id]);
            }

            $index++;
        }
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

            $centerLat = (float) ($company->center_lat ?? -6.87);
            $centerLng = (float) ($company->center_lng ?? 109.18);

            foreach ($definition['areas'] as [$areaCode, $areaName, $areaDescription]) {
                $offset = fake()->randomFloat(4, -0.01, 0.01);
                $size = fake()->randomFloat(4, 0.002, 0.006);

                $baseLat = $centerLat + $offset;
                $baseLng = $centerLng + $offset;

                $polygon = [
                    [round($baseLat - $size, 7), round($baseLng - $size, 7)],
                    [round($baseLat - $size, 7), round($baseLng + $size, 7)],
                    [round($baseLat + $size, 7), round($baseLng + $size, 7)],
                    [round($baseLat + $size, 7), round($baseLng - $size, 7)],
                ];

                $center = Geo::polygonCenter($polygon);

                $areas[$areaCode] = Area::firstOrCreate(
                    ['company_id' => $company->id, 'code' => $areaCode],
                    [
                        'name' => $areaName,
                        'description' => $areaDescription,
                        'is_active' => true,
                        'polygon' => $polygon,
                        'latitude' => $center['lat'] ?? null,
                        'longitude' => $center['lng'] ?? null,
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
     */
    protected function seedRequests(array $equipment, array $users): void
    {
        $definitions = [
            ['p-103', 'Seal pompa bocor saat operasi', 'Stopped', 'Open'],
            ['c-301', 'Kompresor berisik dan getaran tinggi', 'Running', 'Open'],
            ['t-202', 'Level gauge tidak menunjukkan angka stabil', 'Standby', 'Open'],
            ['v-801', 'Valve macet saat penutupan', 'Faulty', 'Open'],
            ['m-701', 'Motor panas berlebih setelah 2 jam operasi', 'Running', 'Open'],
            ['p-102', 'Tekanan discharge turun drastis', 'Stopped', 'Approved'],
            ['g-401', 'Genset gagal start otomatis', 'Stopped', 'Approved'],
            ['pnl-501', 'Breaker trip berulang', 'Faulty', 'In Progress'],
            ['t-201', 'Korosi pada sambungan pipa keluar', 'Running', 'In Progress'],
            ['c-301', 'Filter udara tersumbat', 'Running', 'Completed'],
            ['fp-601', 'Kebocoran pada flange suction', 'Stopped', 'Rejected'],
        ];

        foreach ($definitions as $index => [$equipmentKey, $description, $operationStatus, $status]) {
            $item = $equipment[$equipmentKey];
            $reporter = $index % 2 === 0 ? $users['pengawas'] : $users['risna'];
            $damageDate = now()->subDays(fake()->numberBetween(0, 20));

            MaintenanceRequest::firstOrCreate(
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
        }
    }
}
