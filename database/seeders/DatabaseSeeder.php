<?php

namespace Database\Seeders;

use App\Models\Node;
use App\Models\NodeSpouse;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user (safe to re-run)
        User::updateOrCreate(
            ['email' => 'admin@tarombo.id'],
            [
                'name'     => 'Administrator',
                'password' => Hash::make('password'),
                'role'     => 'admin',
            ]
        );

        // Silsilah Batak seed data (only if no nodes exist)
        if (Node::count() > 0) {
            return;
        }

        // Level 0 - Root
        $rajaDatak = Node::create([
            'parent_id'   => null,
            'name'        => 'Si Raja Batak',
            'gender'      => 'male',
            'marga'       => null,
            'asal_daerah' => 'Pusuk Buhit, Samosir',
            'tahun_lahir' => null,
            'tahun_wafat' => null,
            'deskripsi'   => 'Leluhur seluruh orang Batak. Dipercaya berasal dari Pusuk Buhit di tepi Danau Toba, Samosir.',
            'status'      => 'active',
            'level'       => 0,
        ]);

        // Level 1
        $guruTateaBulan = Node::create([
            'parent_id'   => $rajaDatak->id,
            'name'        => 'Guru Tatea Bulan',
            'gender'      => 'male',
            'marga'       => null,
            'asal_daerah' => 'Samosir',
            'deskripsi'   => 'Putra pertama Si Raja Batak. Menurunkan banyak marga Batak Toba bagian barat (Borbor dan lainnya).',
            'status'      => 'active',
            'level'       => 1,
        ]);
        $guruTateaBulan->spouses()->create(['name' => 'Si Boru Pareme', 'marga' => 'Lontung']);

        $rajaisumbaon = Node::create([
            'parent_id'   => $rajaDatak->id,
            'name'        => 'Raja Isumbaon',
            'gender'      => 'male',
            'marga'       => null,
            'asal_daerah' => 'Samosir',
            'deskripsi'   => 'Putra kedua Si Raja Batak. Menurunkan marga-marga Batak bagian timur (Sumba dan lainnya).',
            'status'      => 'active',
            'level'       => 1,
        ]);

        // Level 2 - dari Guru Tatea Bulan
        $rajaLontung = Node::create([
            'parent_id'   => $guruTateaBulan->id,
            'name'        => 'Raja Lontung',
            'gender'      => 'male',
            'marga'       => 'Lontung',
            'asal_daerah' => 'Samosir',
            'deskripsi'   => 'Leluhur marga-marga golongan Lontung.',
            'status'      => 'active',
            'level'       => 2,
        ]);

        $rajaBorbor = Node::create([
            'parent_id'   => $guruTateaBulan->id,
            'name'        => 'Raja Borbor',
            'gender'      => 'male',
            'marga'       => 'Borbor',
            'asal_daerah' => 'Samosir',
            'deskripsi'   => 'Leluhur marga-marga golongan Borbor.',
            'status'      => 'active',
            'level'       => 2,
        ]);

        $limbongMulana = Node::create([
            'parent_id'   => $guruTateaBulan->id,
            'name'        => 'Limbong Mulana',
            'gender'      => 'male',
            'marga'       => 'Limbong',
            'asal_daerah' => 'Samosir',
            'deskripsi'   => 'Leluhur marga Limbong.',
            'status'      => 'active',
            'level'       => 2,
        ]);

        $sagalaRaja = Node::create([
            'parent_id'   => $guruTateaBulan->id,
            'name'        => 'Sagala Raja',
            'gender'      => 'male',
            'marga'       => 'Sagala',
            'asal_daerah' => 'Samosir',
            'deskripsi'   => 'Leluhur marga Sagala.',
            'status'      => 'active',
            'level'       => 2,
        ]);

        // Level 2 - dari Raja Isumbaon
        $tuanSoriMangaraja = Node::create([
            'parent_id'   => $rajaisumbaon->id,
            'name'        => 'Tuan Sori Mangaraja',
            'gender'      => 'male',
            'marga'       => 'Sumba',
            'asal_daerah' => 'Samosir',
            'deskripsi'   => 'Putra Raja Isumbaon, leluhur marga Sumba.',
            'status'      => 'active',
            'level'       => 2,
        ]);

        $sipahutar = Node::create([
            'parent_id'   => $rajaisumbaon->id,
            'name'        => 'Raja Nai Ambaton',
            'gender'      => 'male',
            'marga'       => 'Naiambaton',
            'asal_daerah' => 'Samosir',
            'deskripsi'   => 'Putra Raja Isumbaon, leluhur marga-marga Naiambaton.',
            'status'      => 'active',
            'level'       => 2,
        ]);

        // Level 3 - dari Raja Lontung
        $simangala = Node::create([
            'parent_id'   => $rajaLontung->id,
            'name'        => 'Sinaga',
            'gender'      => 'male',
            'marga'       => 'Sinaga',
            'asal_daerah' => 'Samosir',
            'deskripsi'   => 'Leluhur marga Sinaga, termasuk golongan Lontung.',
            'status'      => 'active',
            'level'       => 3,
        ]);

        $situmorang = Node::create([
            'parent_id'   => $rajaLontung->id,
            'name'        => 'Situmorang',
            'gender'      => 'male',
            'marga'       => 'Situmorang',
            'asal_daerah' => 'Samosir',
            'deskripsi'   => 'Leluhur marga Situmorang.',
            'status'      => 'active',
            'level'       => 3,
        ]);

        $pandiangan = Node::create([
            'parent_id'   => $rajaLontung->id,
            'name'        => 'Pandiangan',
            'gender'      => 'male',
            'marga'       => 'Pandiangan',
            'asal_daerah' => 'Samosir',
            'deskripsi'   => 'Leluhur marga Pandiangan.',
            'status'      => 'active',
            'level'       => 3,
        ]);

        // Level 3 - dari Raja Borbor
        $marpaung = Node::create([
            'parent_id'   => $rajaBorbor->id,
            'name'        => 'Marpaung',
            'gender'      => 'male',
            'marga'       => 'Marpaung',
            'asal_daerah' => 'Toba',
            'deskripsi'   => 'Leluhur marga Marpaung dari golongan Borbor.',
            'status'      => 'active',
            'level'       => 3,
        ]);

        $pardede = Node::create([
            'parent_id'   => $rajaBorbor->id,
            'name'        => 'Pardede',
            'gender'      => 'male',
            'marga'       => 'Pardede',
            'asal_daerah' => 'Toba',
            'deskripsi'   => 'Leluhur marga Pardede dari golongan Borbor.',
            'status'      => 'active',
            'level'       => 3,
        ]);

        // Level 3 - dari Limbong Mulana
        Node::create([
            'parent_id'   => $limbongMulana->id,
            'name'        => 'Limbong',
            'gender'      => 'male',
            'marga'       => 'Limbong',
            'asal_daerah' => 'Samosir',
            'deskripsi'   => 'Marga Limbong, keturunan langsung Limbong Mulana.',
            'status'      => 'active',
            'level'       => 3,
        ]);

        // Level 3 - dari Sagala Raja
        Node::create([
            'parent_id'   => $sagalaRaja->id,
            'name'        => 'Sagala',
            'gender'      => 'male',
            'marga'       => 'Sagala',
            'asal_daerah' => 'Samosir',
            'deskripsi'   => 'Marga Sagala, keturunan langsung Sagala Raja.',
            'status'      => 'active',
            'level'       => 3,
        ]);
    }
}
