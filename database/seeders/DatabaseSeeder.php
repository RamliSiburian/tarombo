<?php

namespace Database\Seeders;

use App\Models\Node;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::updateOrCreate(
            ['email' => 'admin@tarombo'],
            [
                'name'     => 'Admin Tarombo',
                'password' => Hash::make('admintarombo123'),
                'role'     => 'admin',
            ]
        );

        // Reset existing nodes
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Node::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Level 0: Root Node
        $rajaBatak = Node::create([
            'parent_id'   => null,
            'name'        => 'RAJA BATAK',
            'gender'      => 'male',
            'status'      => 'active',
            'level'       => 0,
            'deskripsi'   => 'Leluhur Seluruh Marga Batak',
        ]);

        // Level 1: Keturunan RAJA BATAK
        $guruTateaBulan = Node::create([
            'parent_id'   => $rajaBatak->id,
            'name'        => 'Guru Tatea Bulan',
            'gender'      => 'male',
            'deskripsi'   => 'BELAHAN LONTUNG',
            'status'      => 'active',
            'level'       => 1,
        ]);

        $guruIsombaon = Node::create([
            'parent_id'   => $rajaBatak->id,
            'name'        => 'Guru Isombaon',
            'gender'      => 'male',
            'deskripsi'   => 'BELAHAN SUMBA',
            'status'      => 'active',
            'level'       => 1,
        ]);

        // Level 2: Keturunan Guru Tatea Bulan (Belahan Lontung)
        Node::create([
            'parent_id'   => $guruTateaBulan->id,
            'name'        => 'Raja Biak-biak Ke Aceh?',
            'gender'      => 'male',
            'status'      => 'active',
            'level'       => 2,
        ]);

        Node::create([
            'parent_id'   => $guruTateaBulan->id,
            'name'        => 'Sariburaja',
            'gender'      => 'male',
            'status'      => 'active',
            'level'       => 2,
        ]);

        Node::create([
            'parent_id'   => $guruTateaBulan->id,
            'name'        => 'Limbong Maulana',
            'gender'      => 'male',
            'marga'       => 'Limbong',
            'status'      => 'active',
            'level'       => 2,
        ]);

        Node::create([
            'parent_id'   => $guruTateaBulan->id,
            'name'        => 'Sagala Raja',
            'gender'      => 'male',
            'marga'       => 'Sagala',
            'status'      => 'active',
            'level'       => 2,
        ]);

        Node::create([
            'parent_id'   => $guruTateaBulan->id,
            'name'        => 'Malau Raja',
            'gender'      => 'male',
            'marga'       => 'Malau',
            'status'      => 'active',
            'level'       => 2,
        ]);

        // Level 2: Keturunan Guru Isombaon (Belahan Sumba)
        Node::create([
            'parent_id'   => $guruIsombaon->id,
            'name'        => 'Tuan Sori Mangaraja',
            'gender'      => 'male',
            'status'      => 'active',
            'level'       => 2,
        ]);
    }
}

