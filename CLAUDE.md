# Aturan proyek

## Tutorial pengguna wajib selalu diperbarui

Tutorial pengguna ada di [docs/TUTORIAL.md](docs/TUTORIAL.md) (Bahasa Indonesia).

Setiap kali mengubah aplikasi dengan dampak yang terlihat pengguna, perbarui tutorial **dalam perubahan yang sama** (jangan ditunda):

- fitur, menu, atau halaman baru / dihapus / dipindah / diganti nama,
- field, filter, tombol, atau alur kerja yang berubah,
- aturan hak akses, role, atau visibilitas data yang berubah,
- modul yang sebelumnya placeholder menjadi berfungsi (mis. LOTOTO).

Langkah:
1. Ubah bagian yang relevan di `docs/TUTORIAL.md` (dan daftar isi bila ada bagian baru).
2. Tambahkan satu baris di tabel **Riwayat Pembaruan** (tanggal + ringkasan).
3. Perubahan yang murni internal (refactor, test, performa tanpa efek ke pengguna) tidak perlu mengubah tutorial.

Sebelum menyatakan pekerjaan selesai, pastikan tutorial sudah sesuai dengan perilaku aplikasi yang sebenarnya.
