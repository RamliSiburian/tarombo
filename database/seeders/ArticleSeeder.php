<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $articles = [
            [
                'title' => 'Si Raja Batak: Leluhur Marga-Marga Batak',
                'category' => 'sejarah',
                'excerpt' => 'Si Raja Batak dipercaya sebagai moyang dari semua marga Batak yang mendiami kawasan Toba dan sekitarnya. Kisah hidupnya menjadi fondasi dari sistem kekerabatan Batak.',
                'content' => "Si Raja Batak dipercaya sebagai moyang dari semua marga Batak yang mendiami kawasan Toba dan sekitarnya.\n\nDalam tradisi lisan Batak, Si Raja Batak memiliki dua orang putra yaitu Guru Tatea Bulan dan Raja Isumbaon. Dari kedua garis inilah kemudian berkembang ratusan marga Batak yang kita kenal hari ini di seluruh pelosok Nusantara hingga mancanegara.",
                'is_published' => true,
                'author_id' => $admin?->id,
            ],
            [
                'title' => 'Dalihan Na Tolu: Sistem Kekerabatan Orang Batak',
                'category' => 'budaya',
                'excerpt' => 'Dalihan Na Tolu adalah falsafah hidup orang Batak yang mengatur hubungan kekerabatan antara Hula-hula, Dongan Tubu, dan Boru. Fondasi sosial yang tak ternilai.',
                'content' => "Dalihan Na Tolu adalah falsafah hidup orang Batak yang mengatur tata hubungan kekerabatan masyarakat Batak.\n\nTiga pilar utamanya adalah:\n1. Somba Marhula-hula (hormat kepada pihak keluarga pemberi istri)\n2. Manat Mardongan Tubu (hati-hati dan saling menghargai kawan semarga)\n3. Elek Marboru (lemah lembut dan mengayomi pihak penerima istri).",
                'is_published' => true,
                'author_id' => $admin?->id,
            ],
            [
                'title' => 'Tradisi Mangongkal Holi: Menghormati Leluhur',
                'category' => 'budaya',
                'excerpt' => 'Mangongkal Holi adalah upacara pemindahan tulang belulang leluhur yang menjadi salah satu ritual terpenting dalam budaya Batak untuk menghormati para nenek moyang.',
                'content' => "Mangongkal Holi merupakan upacara adat Batak di mana tulang belulang para leluhur digali kembali dari makam lama, dibersihkan dengan tata cara adat, dan dipindahkan ke tugu bersama (tambak/simin).\n\nUpacara ini melambangkan penghormatan terdalam anak-cucu kepada nenek moyang serta menyatukan kembali keturunan yang merantau.",
                'is_published' => true,
                'author_id' => $admin?->id,
            ],
            [
                'title' => 'Pesta Bona Taon dan Silaturahmi Pomparan',
                'category' => 'kegiatan',
                'excerpt' => 'Pertemuan akbar tahunan keluarga besar marga Batak dalam rangka mempererat tali persaudaraan dan melestarikan silsilah tarombo.',
                'content' => "Kegiatan Pesta Bona Taon diselenggarakan setiap awal tahun sebagai ajang berkumpul, bersyukur, dan memperkenalkan generasi muda kepada silsilah leluhur mereka.",
                'event_date' => now()->addDays(30)->toDateString(),
                'is_published' => true,
                'author_id' => $admin?->id,
            ],
        ];

        foreach ($articles as $art) {
            Article::firstOrCreate(['title' => $art['title']], $art);
        }
    }
}
