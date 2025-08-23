<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Seeder;

class SampleTrackingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (!Schema::hasTable('sample_tracking')) {
            Schema::create('sample_tracking', function (Blueprint $table) {
                $table->id();
                $table->integer('profile_id')->nullable();
                $table->bigInteger('sample_id')->nullable();
                $table->bigInteger('order_id')->nullable();
                $table->integer('hospital_id')->nullable();
                $table->string('hospital_name')->nullable();
                $table->date('create_date')->nullable();
                $table->bigInteger('task_id')->nullable();
                $table->boolean('is_collected')->default(0);
            });
        }
        if (!Schema::hasColumn('locations', 'integration_branch_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->integer('integration_branch_id')->nullable();
                $table->string('integration_branch_name')->nullable();
            });
        }
        if (!Schema::hasColumn('samples', 'is_blazma')) {
            Schema::table('samples', function (Blueprint $table) {
                $table->integer('profile_id')->nullable();
                $table->integer('order_id')->nullable();
                $table->integer('hospital_id')->nullable();
                $table->string('hospital_name')->nullable();
                $table->boolean('is_sync')->default(0);
                $table->boolean('is_blazma')->default(0);
            });
        }
        if (!Schema::hasColumn('tasks', 'is_blazma')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->boolean('is_blazma')->default(0);
            });
        }
        if (!Schema::hasColumn('tasks', 'is_swap')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->boolean('is_swap')->default(0);
                $table->integer('old_driver_id')->nullable();
                $table->dateTime('swap_accepted_date')->nullable();
                $table->dateTime('swap_freezer_in')->nullable();
                $table->dateTime('swap_freezer_out')->nullable();
            });
        }
        if (!Schema::hasColumn('clients', 'is_blazma')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->boolean('is_blazma')->default(0);
            });
        }
        if (!Schema::hasColumn('sample_tracking', 'collection_hospital_id')) {
            Schema::table('sample_tracking', function (Blueprint $table) {
                $table->integer('collection_hospital_id')->nullable();
                $table->string('collection_hospital_name')->nullable();
            });
        }
        if (!Schema::hasColumn('samples', 'collection_hospital_id')) {
            Schema::table('samples', function (Blueprint $table) {
                $table->integer('collection_hospital_id')->nullable();
                $table->string('collection_hospital_name')->nullable();
            });
        }

        /*$branches = [
            ['Brnach_ID' => 1138, 'Blazma_name' => 'CC007 Andalus, Majmaah', 'location_id' => 136],
            ['Brnach_ID' => 1136, 'Blazma_name' => 'CC006 Awaimriya, Dalam', 'location_id' => 137],
            ['Brnach_ID' => 1134, 'Blazma_name' => 'CC005 Montazah, Kharj', 'location_id' => 138],
            ['Brnach_ID' => 1133, 'Blazma_name' => 'CC002 Sulaymaniyah, Kharj', 'location_id' => 139],
            ['Brnach_ID' => 1135, 'Blazma_name' => 'CC025 Khuzamaa, Kharj', 'location_id' => 635],
            ['Brnach_ID' => 1140, 'Blazma_name' => 'CC009 Okaz, Riyadh', 'location_id' => 140],
            ['Brnach_ID' => 1137, 'Blazma_name' => 'CC004 Nuzha, Riyadh', 'location_id' => 141],
            ['Brnach_ID' => 1132, 'Blazma_name' => 'CC001 Olaya, Riyadh', 'location_id' => 142],
            ['Brnach_ID' => 1093, 'Blazma_name' => 'CC003 Laban, Riyadh', 'location_id' => 176],
            ['Brnach_ID' => 1141, 'Blazma_name' => 'CC010 Sahafa, Riyadh', 'location_id' => 255],
            ['Brnach_ID' => 1142, 'Blazma_name' => 'CC013 Aaziziyah, Riyadh', 'location_id' => 268],
            ['Brnach_ID' => 1143, 'Blazma_name' => 'CC012 Yarmouk, Riyadh', 'location_id' => 270],
            ['Brnach_ID' => 1150, 'Blazma_name' => 'CC011 King Fahad, Riyadh', 'location_id' => 271],
            ['Brnach_ID' => 1114, 'Blazma_name' => 'CC016 Murjan, Jeddah', 'location_id' => 273],
            ['Brnach_ID' => 1146, 'Blazma_name' => 'CC019 Izdihar, Riyadh', 'location_id' => 284],
            ['Brnach_ID' => 1149, 'Blazma_name' => 'CC018 Tuwaiq, Riyadh', 'location_id' => 287],
            ['Brnach_ID' => 1139, 'Blazma_name' => 'CC008 Wurud, Hotat Tamim', 'location_id' => 288],
            ['Brnach_ID' => 1144, 'Blazma_name' => 'CC015 Rawda, Riyadh', 'location_id' => 289],
            ['Brnach_ID' => 1155, 'Blazma_name' => 'CC014 Ladam, Dawasir', 'location_id' => 290],
            ['Brnach_ID' => 1156, 'Blazma_name' => 'CC021 King Abdulaziz , Sulayyil', 'location_id' => 293],
            ['Brnach_ID' => 1157, 'Blazma_name' => 'CC020 Aaziziyah, Aflaj', 'location_id' => 294],
            ['Brnach_ID' => 1145, 'Blazma_name' => 'CC017 Jafuniya, Quwayiyah', 'location_id' => 327],
            ['Brnach_ID' => 1154, 'Blazma_name' => 'CC023 Sharaya, Mecca', 'location_id' => 534],
            ['Brnach_ID' => 1147, 'Blazma_name' => 'CC022 Alrubwa, Riyadh', 'location_id' => 599],
            ['Brnach_ID' => 1151, 'Blazma_name' => 'CC024 lshiraa, Jeddah', 'location_id' => 628],
            ['Brnach_ID' => 1152, 'Blazma_name' => 'CC026 Faisalyah, Jamoum', 'location_id' => 695],
            ['Brnach_ID' => 1148, 'Blazma_name' => 'CC027 Suwaidi, Riyadh', 'location_id' => 817],
            ['Brnach_ID' => 1120, 'Blazma_name' => 'CC028 Raya, Medina', 'location_id' => 831],
            ['Brnach_ID' => 1153, 'Blazma_name' => 'CC029 Fazie, Khulais', 'location_id' => 832],
            ['Brnach_ID' => 1166, 'Blazma_name' => 'CC031 Shoqiyah, Mecca', 'location_id' => 850],
            ['Brnach_ID' => 1167, 'Blazma_name' => 'CC032 Manar, Jeddah', 'location_id' => 856],
            ['Brnach_ID' => 1169, 'Blazma_name' => 'CC033 Rabwh, Jeddah', 'location_id' => 922],
            ['Brnach_ID' => 1165, 'Blazma_name' => 'CC034 Salam, Rania', 'location_id' => 924],
            ['Brnach_ID' => 1170, 'Blazma_name' => 'CC035 Rimal, Riyadh', 'location_id' => 932],
            ['Brnach_ID' => 1172, 'Blazma_name' => 'CC036 Nazeem,Riyadh', 'location_id' => 943],
            ['Brnach_ID' => 1174, 'Blazma_name' => 'CC037 Zuhur ,Dammam', 'location_id' => 963],
            ['Brnach_ID' => 1177, 'Blazma_name' => 'CC039 Irqah,Riyadh', 'location_id' => 976],
            ['Brnach_ID' => 1183, 'Blazma_name' => 'CC045 Safa, khobar', 'location_id' => 979],
            ['Brnach_ID' => 1175, 'Blazma_name' => 'CC038 Sadad,Taif', 'location_id' => 1048],
            ['Brnach_ID' => 1180, 'Blazma_name' => 'CC041 Hamra, Dammam', 'location_id' => 1083],
            ['Brnach_ID' => 1181, 'Blazma_name' => 'CC042 Dhabbab, Dammam', 'location_id' => 1084],
            ['Brnach_ID' => 1185, 'Blazma_name' => 'CC043 Narjis, Riyadh', 'location_id' => 1118],
            ['Brnach_ID' => 1186, 'Blazma_name' => 'CC046 Sanabel, Jeddah', 'location_id' => 1136],
            ['Brnach_ID' => 1188, 'Blazma_name' => 'CC048 Salamah, Jeddah', 'location_id' => 1137],
            ['Brnach_ID' => 1189, 'Blazma_name' => 'CC047 Khuzamaa, Khobar', 'location_id' => 1138],
            ['Brnach_ID' => 1194, 'Blazma_name' => 'CC051 Aqrabiyah, Khobar', 'location_id' => 1140],
            ['Brnach_ID' => 1195, 'Blazma_name' => 'CC052 Alawali, Mecca', 'location_id' => 1141],
            ['Brnach_ID' => 1193, 'Blazma_name' => 'CC049 Mazro\'a, Hofuf', 'location_id' => 1142],
            ['Brnach_ID' => 1184, 'Blazma_name' => 'CC044 Sifah, Hofuf', 'location_id' => 1104],
        ];
        foreach ($branches as $branch){
            $location = Location::find($branch['location_id']);
            if (isset($location->id)){
                $location->integration_branch_id = $branch['Brnach_ID'];
                $location->integration_branch_name = $branch['Blazma_name'];
                $location->save();
            }
        }*/




    }
}
