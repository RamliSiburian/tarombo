<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    article: {
        type: Object,
        default: null,
    },
});

const isEditing = !!props.article;

const form = useForm({
    _method: isEditing ? 'PUT' : 'POST',
    title: props.article?.title || '',
    category: props.article?.category || 'kegiatan',
    excerpt: props.article?.excerpt || '',
    content: props.article?.content || '',
    event_date: props.article?.event_date ? props.article.event_date.substring(0, 10) : '',
    is_published: props.article ? Boolean(props.article.is_published) : true,
    image: null,
});

const imagePreview = ref(props.article?.image ? `/storage/${props.article.image}` : null);

const onImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    if (isEditing) {
        // Inertia multipart PUT workaround via POST with _method
        form.post(route('admin.articles.update', props.article.id), {
            forceFormData: true,
        });
    } else {
        form.post(route('admin.articles.store'), {
            forceFormData: true,
        });
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Kegiatan/Berita' : 'Tambah Kegiatan/Berita'" />
    <AdminLayout>
        <template #title>
            {{ isEditing ? 'Edit Kegiatan / Berita' : 'Tambah Kegiatan / Berita Baru' }}
        </template>

        <div class="max-w-4xl">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-white">
                        {{ isEditing ? `Edit: ${article.title}` : 'Formulir Kegiatan / Berita Baru' }}
                    </h2>
                    <p class="text-xs text-gray-400 mt-0.5">Lengkapi informasi di bawah ini untuk mempublikasikan kegiatan atau berita.</p>
                </div>
                <Link
                    :href="route('admin.articles.index')"
                    class="px-3.5 py-2 rounded-xl bg-gray-900 hover:bg-gray-800 text-gray-300 border border-white/10 text-xs font-medium transition-colors"
                >
                    ← Kembali
                </Link>
            </div>

            <!-- Form Card -->
            <form @submit.prevent="submit" class="bg-gray-900 border border-white/10 rounded-2xl p-6 sm:p-8 space-y-6 shadow-xl">
                <!-- Title -->
                <div>
                    <label class="block text-gray-300 text-sm font-semibold mb-1.5">
                        Judul Kegiatan / Berita <span class="text-red-400">*</span>
                    </label>
                    <input
                        v-model="form.title"
                        type="text"
                        placeholder="Contoh: Pesta Bona Taon Parsadaan Marga Siburian 2026"
                        class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-primary-500/50 transition-colors"
                    />
                    <div v-if="form.errors.title" class="text-red-400 text-xs mt-1">{{ form.errors.title }}</div>
                </div>

                <!-- Category & Event Date -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-gray-300 text-sm font-semibold mb-1.5">
                            Kategori <span class="text-red-400">*</span>
                        </label>
                        <select
                            v-model="form.category"
                            class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-primary-500/50 transition-colors"
                        >
                            <option value="kegiatan">🎉 Kegiatan / Acara Marga</option>
                            <option value="berita">📰 Berita & Warta Komunitas</option>
                            <option value="sejarah">📜 Sejarah & Tarombo</option>
                            <option value="budaya">🏺 Adat & Budaya Batak</option>
                        </select>
                        <div v-if="form.errors.category" class="text-red-400 text-xs mt-1">{{ form.errors.category }}</div>
                    </div>

                    <div>
                        <label class="block text-gray-300 text-sm font-semibold mb-1.5">
                            Tanggal Pelaksanaan Acara (opsional)
                        </label>
                        <input
                            v-model="form.event_date"
                            type="date"
                            class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-primary-500/50 transition-colors"
                        />
                        <div v-if="form.errors.event_date" class="text-red-400 text-xs mt-1">{{ form.errors.event_date }}</div>
                    </div>
                </div>

                <!-- Cover Image -->
                <div>
                    <label class="block text-gray-300 text-sm font-semibold mb-1.5">
                        Foto Sampul (Cover Image)
                    </label>
                    <div class="flex flex-col sm:flex-row items-center gap-4">
                        <div v-if="imagePreview" class="w-32 h-24 rounded-xl overflow-hidden border border-white/10 flex-shrink-0 bg-gray-800">
                            <img :src="imagePreview" class="w-full h-full object-cover" />
                        </div>
                        <div v-else class="w-32 h-24 rounded-xl border border-dashed border-white/10 flex items-center justify-center text-3xl bg-gray-800/50 flex-shrink-0">
                            🖼️
                        </div>

                        <label class="flex-1 w-full cursor-pointer border-2 border-dashed border-white/10 hover:border-primary-500/40 rounded-xl px-5 py-4 text-center transition-colors">
                            <span class="text-sm text-gray-300">Pilih foto dari komputer (JPG, PNG, WebP maks 2MB)</span>
                            <input type="file" accept="image/*" @change="onImageChange" class="hidden" />
                        </label>
                    </div>
                    <div v-if="form.errors.image" class="text-red-400 text-xs mt-1">{{ form.errors.image }}</div>
                </div>

                <!-- Excerpt -->
                <div>
                    <label class="block text-gray-300 text-sm font-semibold mb-1.5">
                        Ringkasan Singkat (Excerpt)
                    </label>
                    <textarea
                        v-model="form.excerpt"
                        rows="2"
                        placeholder="Ringkasan 1-2 kalimat untuk cuplikan di beranda..."
                        class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-primary-500/50 transition-colors resize-none"
                    ></textarea>
                    <div v-if="form.errors.excerpt" class="text-red-400 text-xs mt-1">{{ form.errors.excerpt }}</div>
                </div>

                <!-- Full Content -->
                <div>
                    <label class="block text-gray-300 text-sm font-semibold mb-1.5">
                        Detail Lengkap / Isi Kegiatan <span class="text-red-400">*</span>
                    </label>
                    <textarea
                        v-model="form.content"
                        rows="8"
                        placeholder="Tuliskan detail jadwal kegiatan, lokasi, susunan panitia, atau isi berita..."
                        class="w-full bg-gray-800 border border-white/10 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-primary-500/50 transition-colors resize-y leading-relaxed"
                    ></textarea>
                    <div v-if="form.errors.content" class="text-red-400 text-xs mt-1">{{ form.errors.content }}</div>
                </div>

                <!-- Status Publish -->
                <div class="flex items-center gap-3 p-4 rounded-xl bg-white/5 border border-white/5">
                    <input
                        v-model="form.is_published"
                        type="checkbox"
                        id="is_published"
                        class="rounded border-white/20 bg-gray-800 text-primary-500 focus:ring-primary-500 w-5 h-5 cursor-pointer"
                    />
                    <label for="is_published" class="text-sm font-medium text-gray-200 cursor-pointer">
                        Publikasikan langsung (dapat dilihat oleh pengunjung website)
                    </label>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-white/10">
                    <Link
                        :href="route('admin.articles.index')"
                        class="px-5 py-2.5 rounded-xl border border-white/10 hover:border-white/20 text-gray-300 hover:text-white text-sm font-medium transition-colors"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-400 hover:to-primary-500 text-gray-950 font-bold text-sm shadow-lg shadow-primary-500/20 transition-all flex items-center gap-2 disabled:opacity-50 cursor-pointer"
                    >
                        <span v-if="form.processing" class="w-4 h-4 border-2 border-gray-950 border-t-transparent rounded-full animate-spin"></span>
                        <span>{{ isEditing ? 'Simpan Perubahan' : '✓ Publikasikan Sekarang' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
