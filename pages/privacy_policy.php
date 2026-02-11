<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Kebijakan Privasi';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="mb-4 text-coffee fw-bold">Kebijakan Privasi DailyCup</h1>
            <p class="text-muted">Terakhir diperbarui: 15 Januari 2026</p>

            <section class="mb-5">
                <h3 class="h5 fw-bold text-coffee mb-3">1. Informasi yang Kami Kumpulkan</h3>
                <p>Kami mengumpulkan informasi yang Anda berikan langsung kepada kami saat Anda membuat akun, melakukan pemesanan, atau menghubungi layanan pelanggan kami. Informasi ini mencakup:</p>
                <ul>
                    <li>Nama, alamat email, dan nomor telepon.</li>
                    <li>Alamat pengiriman.</li>
                    <li>Riwayat pesanan dan preferensi produk.</li>
                    <li>Informasi pembayaran (diproses secara aman melalui gerbang pembayaran).</li>
                </ul>
            </section>

            <section class="mb-5">
                <h3 class="h5 fw-bold text-coffee mb-3">2. Bagaimana Kami Menggunakan Informasi Anda</h3>
                <p>Kami menggunakan informasi yang kami kumpulkan untuk:</p>
                <ul>
                    <li>Memproses dan mengirimkan pesanan Anda.</li>
                    <li>Mengelola program loyalty points Anda.</li>
                    <li>Mengirimkan update status pesanan dan notifikasi keamanan.</li>
                    <li>Meningkatkan layanan dan pengalaman belanja Anda melalui analitik.</li>
                    <li>Memenuhi kewajiban hukum dan regulasi.</li>
                </ul>
            </section>

            <section class="mb-5">
                <h3 class="h5 fw-bold text-coffee mb-3">3. Perlindungan Data (GDPR)</h3>
                <p>Kami berkomitmen untuk mematuhi General Data Protection Regulation (GDPR). Anda memiliki hak-hak berikut terkait data pribadi Anda:</p>
                <ul>
                    <li><strong>Hak Akses:</strong> Anda dapat melihat data profil Anda kapan saja.</li>
                    <li><strong>Hak Portabilitas:</strong> Anda dapat mengunduh data Anda dalam format yang dapat dibaca mesin melalui menu Profil.</li>
                    <li><strong>Hak untuk Dilupakan:</strong> Anda dapat meminta penghapusan permanen akun dan data Anda.</li>
                    <li><strong>Hak Perbaikan:</strong> Anda dapat memperbarui informasi akun Anda kapan saja.</li>
                </ul>
            </section>

            <section class="mb-5">
                <h3 class="h5 fw-bold text-coffee mb-3">4. Penggunaan Cookie</h3>
                <p>Situs kami menggunakan cookie untuk meningkatkan fungsionalitas, seperti tetap masuk (keep login) dan menyimpan keranjang belanja Anda. Anda dapat mengatur browser Anda untuk menolak cookie, namun beberapa fitur situs mungkin tidak berfungsi dengan baik.</p>
            </section>

            <section class="mb-5">
                <h3 class="h5 fw-bold text-coffee mb-3">5. Keamanan</h3>
                <p>Kami mengimplementasikan berbagai langkah keamanan teknis dan organisasional untuk melindungi data pribadi Anda dari akses yang tidak sah, pengungkapan, atau penghancuran yang tidak semestinya.</p>
            </section>

            <section class="mb-5">
                <h3 class="h5 fw-bold text-coffee mb-3">6. Hubungi Kami</h3>
                <p>Jika Anda memiliki pertanyaan tentang Kebijakan Privasi ini, silakan hubungi tim perlindungan data kami di:</p>
                <div class="p-3 bg-light rounded shadow-sm border-start border-4 border-coffee">
                    <p class="mb-0"><strong>Email:</strong> privacy@dailycup.com</p>
                    <p class="mb-0"><strong>Alamat:</strong> Jl. Kopi Nikmat No. 123, Jakarta Selatan, Indonesia</p>
                </div>
            </section>

            <div class="mt-5 text-center">
                <a href="<?php echo SITE_URL; ?>" class="btn btn-coffee px-4">Kembali ke Beranda</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
