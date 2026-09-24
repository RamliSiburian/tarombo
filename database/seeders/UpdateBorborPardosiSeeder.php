<?php

namespace Database\Seeders;

use App\Models\Node;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateBorborPardosiSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $borbor = Node::where('name', 'like', '%Borbor%')->first();
            if (!$borbor) {
                echo "Raja Borbor not found\n";
                return;
            }

            $pardosi = Node::firstOrCreate(
                ['name' => 'Pardosi', 'parent_id' => $borbor->id],
                [
                    'gender' => 'male',
                    'marga' => 'Pardosi',
                    'level' => $borbor->level + 1,
                    'status' => 'active',
                    'deskripsi' => 'Keturunan Raja Borbor',
                ]
            );

            // Update Marpaung & Pardede to have parent = Pardosi
            Node::whereIn('name', ['Marpaung', 'Pardede'])->update([
                'parent_id' => $pardosi->id,
                'level' => $pardosi->level + 1,
            ]);

            $pardosi->updateDescendantLevels();

            echo "SUCCESS: Pardosi (ID: {$pardosi->id}, Gen: " . ($pardosi->level + 1) . ") created under {$borbor->name}.\n";
            echo "Marpaung & Pardede now under Pardosi (Gen: " . ($pardosi->level + 2) . ").\n";
        });
    }
}
