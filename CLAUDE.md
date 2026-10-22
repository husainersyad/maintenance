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

## Penanda akses di tutorial (isi Help menyesuaikan pengguna)

Halaman Help (`/help`) merender `docs/TUTORIAL.md` dan menyaringnya per pengguna lewat [HelpGuide](app/Support/HelpGuide.php). Saat menulis/mengubah tutorial:

- Bungkus bagian yang hanya relevan untuk sebagian pengguna dengan `<!-- only:KUNCI -->` ... `<!-- /only -->` (di baris sendiri, bisa bersarang). Beberapa kunci dipisah `|` artinya "salah satu".
- Kunci: nilai `SidebarMenu` (mis. `employees`, `skill-matrix`, `training-management`), `edit:<MenuKey>` (pengguna boleh mengedit menu itu), `guest`, `not-guest`, `admin` (bisa kelola user), `settings`.
- Bagian `## ...` harus dibungkus dengan gabungan kunci semua sub-bagiannya, supaya judul tidak tertinggal kosong.
- `<!-- hide-in-app:start -->` ... `<!-- hide-in-app:end -->` = hanya untuk developer, tidak tampil di aplikasi.
- Jangan beri nomor pada judul `##` (nomor jadi bolong bila ada bagian yang disembunyikan) dan jangan membuat tautan `#anchor` ke bagian yang bisa tersembunyi.
- Menu baru di sidebar: tambahkan juga ke `SidebarMenu` dan [MenuAccess](app/Support/MenuAccess.php); `HelpTest` memeriksa MenuAccess sama dengan sidebar.
