<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitiesTableSeeder extends Seeder
{
    public function run()
    {


        $countryId = DB::table('countries')->where('code', 'EG')->first()->id;
        $stateIds = DB::table('states')->pluck('id', 'code');

        DB::table('cities')->insert([
            ['name' => 'Cairo', 'code' => 'C', 'country_id' => $countryId, 'state_id' => $stateIds['C']],
            ['name' => 'Alexandria', 'code' => 'ALX', 'country_id' => $countryId, 'state_id' => $stateIds['ALX']],
            ['name' => 'Giza', 'code' => 'GZ', 'country_id' => $countryId, 'state_id' => $stateIds['GZ']],
            ['name' => 'Aswan', 'code' => 'ASN', 'country_id' => $countryId, 'state_id' => $stateIds['ASN']],
            ['name' => 'Luxor', 'code' => 'LX', 'country_id' => $countryId, 'state_id' => $stateIds['LX']],
            ['name' => 'Suez', 'code' => 'SUZ', 'country_id' => $countryId, 'state_id' => $stateIds['SUZ']],
            ['name' => 'Port Said', 'code' => 'PTS', 'country_id' => $countryId, 'state_id' => $stateIds['PTS']],
            ['name' => 'Qalyubia', 'code' => 'KB', 'country_id' => $countryId, 'state_id' => $stateIds['KB']],
            ['name' => 'Dakahlia', 'code' => 'DK', 'country_id' => $countryId, 'state_id' => $stateIds['DK']],
            ['name' => 'Damietta', 'code' => 'DT', 'country_id' => $countryId, 'state_id' => $stateIds['DT']],
            ['name' => 'Sharqia', 'code' => 'SHR', 'country_id' => $countryId, 'state_id' => $stateIds['SHR']],
            ['name' => 'Monufia', 'code' => 'MNF', 'country_id' => $countryId, 'state_id' => $stateIds['MNF']],
            ['name' => 'Beheira', 'code' => 'BH', 'country_id' => $countryId, 'state_id' => $stateIds['BH']],
            ['name' => 'Kafr El Sheikh', 'code' => 'KFS', 'country_id' => $countryId, 'state_id' => $stateIds['KFS']],
            ['name' => 'Gharbia', 'code' => 'GH', 'country_id' => $countryId, 'state_id' => $stateIds['GH']],
            ['name' => 'Faiyum', 'code' => 'FYM', 'country_id' => $countryId, 'state_id' => $stateIds['FYM']],
            ['name' => 'Beni Suef', 'code' => 'BNS', 'country_id' => $countryId, 'state_id' => $stateIds['BNS']],
            ['name' => 'Minya', 'code' => 'MN', 'country_id' => $countryId, 'state_id' => $stateIds['MN']],
            ['name' => 'Assiut', 'code' => 'AST', 'country_id' => $countryId, 'state_id' => $stateIds['AST']],
            ['name' => 'Sohag', 'code' => 'SHG', 'country_id' => $countryId, 'state_id' => $stateIds['SHG']],
            ['name' => 'Qena', 'code' => 'KN', 'country_id' => $countryId, 'state_id' => $stateIds['KN']],
            ['name' => 'Red Sea', 'code' => 'BA', 'country_id' => $countryId, 'state_id' => $stateIds['BA']],
            ['name' => 'New Valley', 'code' => 'WAD', 'country_id' => $countryId, 'state_id' => $stateIds['WAD']],
            ['name' => 'Matruh', 'code' => 'MT', 'country_id' => $countryId, 'state_id' => $stateIds['MT']],
            ['name' => 'North Sinai', 'code' => 'SIN', 'country_id' => $countryId, 'state_id' => $stateIds['SIN']],
            ['name' => 'South Sinai', 'code' => 'JS', 'country_id' => $countryId, 'state_id' => $stateIds['JS']],
        ]);
    }
}
