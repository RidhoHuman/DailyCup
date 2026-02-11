'use client';

import Link from 'next/link';
import { useState } from 'react';

export default function KurirInfoPage() {
  const [showModal, setShowModal] = useState(false);

  return (
    <div className="min-h-screen bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50">
      {/* Hero Section */}
      <div className="relative overflow-hidden bg-gradient-to-r from-[#a97456] to-[#8b6043] text-white">
        <div className="absolute inset-0 opacity-10">
          <div className="absolute top-0 left-0 w-96 h-96 bg-white rounded-full -translate-x-1/2 -translate-y-1/2"></div>
          <div className="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full translate-x-1/2 translate-y-1/2"></div>
        </div>
        
        <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
          <div className="text-center">
            <div className="inline-flex items-center justify-center w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl mb-6">
              <i className="bi bi-truck text-5xl"></i>
            </div>
            <h1 className="text-4xl sm:text-5xl font-bold mb-4">
              Bergabung dengan <span className="text-yellow-300">DailyVery</span>
            </h1>
            <p className="text-xl text-white/90 max-w-2xl mx-auto mb-8">
              Program kurir internal DailyCup untuk karyawan terpilih
            </p>
            <button
              onClick={() => setShowModal(true)}
              className="inline-flex items-center gap-2 px-8 py-4 bg-white text-[#a97456] rounded-xl font-semibold text-lg hover:shadow-lg transition-all"
            >
              <i className="bi bi-info-circle"></i>
              Pelajari Persyaratan
            </button>
          </div>
        </div>
      </div>

      {/* Benefits Section */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h2 className="text-3xl font-bold text-center text-gray-800 mb-12">
          Keuntungan Menjadi Kurir DailyCup
        </h2>
        
        <div className="grid md:grid-cols-3 gap-8">
          {[
            {
              icon: 'bi-cash-stack',
              title: 'Penghasilan Kompetitif',
              desc: 'Dapatkan gaji tetap plus bonus per delivery. Semakin banyak antar, semakin besar penghasilan!',
              color: 'green'
            },
            {
              icon: 'bi-calendar-check',
              title: 'Jadwal Fleksibel',
              desc: 'Atur jadwal kerja sendiri. Online saat siap, offline saat istirahat.',
              color: 'blue'
            },
            {
              icon: 'bi-shield-check',
              title: 'Jaminan & Perlindungan',
              desc: 'BPJS Kesehatan, asuransi kecelakaan, dan perlengkapan keselamatan dari perusahaan.',
              color: 'purple'
            },
            {
              icon: 'bi-gear',
              title: 'Subsidi Kendaraan',
              desc: 'Bantuan perawatan kendaraan dan BBM untuk kurir dengan performa terbaik.',
              color: 'orange'
            },
            {
              icon: 'bi-graph-up',
              title: 'Jenjang Karir',
              desc: 'Kesempatan promosi menjadi supervisor atau koordinator kurir.',
              color: 'cyan'
            },
            {
              icon: 'bi-people',
              title: 'Komunitas Solid',
              desc: 'Bergabung dengan tim internal DailyCup yang suportif dan profesional.',
              color: 'pink'
            }
          ].map((benefit, idx) => (
            <div key={idx} className="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
              <div className={`w-14 h-14 bg-${benefit.color}-100 text-${benefit.color}-600 rounded-xl flex items-center justify-center mb-4`}>
                <i className={`bi ${benefit.icon} text-2xl`}></i>
              </div>
              <h3 className="text-xl font-bold text-gray-800 mb-2">{benefit.title}</h3>
              <p className="text-gray-600">{benefit.desc}</p>
            </div>
          ))}
        </div>
      </div>

      {/* How It Works */}
      <div className="bg-white py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h2 className="text-3xl font-bold text-center text-gray-800 mb-12">
            Cara Menjadi Kurir DailyCup
          </h2>
          
          <div className="grid md:grid-cols-4 gap-6">
            {[
              { step: '1', title: 'Undangan dari Admin', desc: 'HR atau manajer DailyCup akan mengirim undangan khusus ke calon kurir terpilih.' },
              { step: '2', title: 'Verifikasi Dokumen', desc: 'Lengkapi dokumen: KTP, SIM, STNK, dan foto profil untuk proses verifikasi.' },
              { step: '3', title: 'Training & Orientasi', desc: 'Ikuti training SOP delivery, safety, dan penggunaan aplikasi kurir.' },
              { step: '4', title: 'Mulai Bekerja', desc: 'Setelah lulus training, Anda siap menerima order dan mulai mengantar!' }
            ].map((item, idx) => (
              <div key={idx} className="relative text-center">
                <div className="w-16 h-16 bg-gradient-to-br from-[#a97456] to-[#8b6043] text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">
                  {item.step}
                </div>
                <h3 className="font-bold text-gray-800 mb-2">{item.title}</h3>
                <p className="text-sm text-gray-600">{item.desc}</p>
                {idx < 3 && (
                  <div className="hidden md:block absolute top-8 left-full w-full h-0.5 bg-gray-200 -translate-x-1/2"></div>
                )}
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* CTA Section */}
      <div className="bg-gradient-to-r from-[#a97456] to-[#8b6043] text-white py-16">
        <div className="max-w-4xl mx-auto text-center px-4">
          <i className="bi bi-envelope-paper text-5xl mb-4"></i>
          <h2 className="text-3xl font-bold mb-4">Tertarik Bergabung?</h2>
          <p className="text-xl text-white/90 mb-8">
            Hubungi HR atau manajer DailyCup untuk mendapatkan undangan pendaftaran.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <a 
              href="mailto:hr@dailycup.com" 
              className="inline-flex items-center gap-2 px-6 py-3 bg-white text-[#a97456] rounded-xl font-semibold hover:shadow-lg transition-all"
            >
              <i className="bi bi-envelope"></i> Email HR
            </a>
            <a 
              href="https://wa.me/6281234567890" 
              className="inline-flex items-center gap-2 px-6 py-3 bg-green-500 text-white rounded-xl font-semibold hover:bg-green-600 transition-all"
            >
              <i className="bi bi-whatsapp"></i> WhatsApp
            </a>
          </div>
          <p className="text-sm text-white/70 mt-6">
            <i className="bi bi-lock"></i> Program ini hanya untuk karyawan internal DailyCup
          </p>
        </div>
      </div>

      {/* Footer */}
      <div className="bg-gray-50 py-8 border-t">
        <div className="max-w-7xl mx-auto px-4 text-center">
          <Link href="/" className="text-[#a97456] hover:underline font-medium">
            ← Kembali ke DailyCup
          </Link>
          <p className="text-sm text-gray-500 mt-2">
            © 2026 DailyCup. Semua hak dilindungi.
          </p>
        </div>
      </div>

      {/* Modal Persyaratan */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" onClick={() => setShowModal(false)}>
          <div className="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-8 max-h-[90vh] overflow-y-auto" onClick={(e) => e.stopPropagation()}>
            <div className="flex justify-between items-center mb-6">
              <h3 className="text-2xl font-bold text-gray-800">Persyaratan Kurir DailyCup</h3>
              <button onClick={() => setShowModal(false)} className="text-gray-400 hover:text-gray-600">
                <i className="bi bi-x-lg text-2xl"></i>
              </button>
            </div>
            
            <div className="space-y-6">
              <div>
                <h4 className="font-bold text-gray-800 mb-3 flex items-center gap-2">
                  <i className="bi bi-person-badge text-[#a97456]"></i>
                  Persyaratan Umum
                </h4>
                <ul className="space-y-2 text-gray-600">
                  <li className="flex items-start gap-2">
                    <i className="bi bi-check-circle-fill text-green-500 mt-1"></i>
                    <span>Usia minimal 21 tahun, maksimal 50 tahun</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <i className="bi bi-check-circle-fill text-green-500 mt-1"></i>
                    <span>Karyawan DailyCup atau direkomendasikan oleh manajemen</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <i className="bi bi-check-circle-fill text-green-500 mt-1"></i>
                    <span>Sehat jasmani dan rohani</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <i className="bi bi-check-circle-fill text-green-500 mt-1"></i>
                    <span>Berdomisili di area operasional DailyCup</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <i className="bi bi-check-circle-fill text-green-500 mt-1"></i>
                    <span>Memiliki smartphone Android/iOS</span>
                  </li>
                </ul>
              </div>

              <div>
                <h4 className="font-bold text-gray-800 mb-3 flex items-center gap-2">
                  <i className="bi bi-file-earmark-text text-[#a97456]"></i>
                  Dokumen yang Diperlukan
                </h4>
                <ul className="space-y-2 text-gray-600">
                  <li className="flex items-start gap-2">
                    <i className="bi bi-check-circle-fill text-green-500 mt-1"></i>
                    <span>KTP asli dan fotocopy</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <i className="bi bi-check-circle-fill text-green-500 mt-1"></i>
                    <span>SIM C (motor) atau SIM A (mobil) yang masih berlaku</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <i className="bi bi-check-circle-fill text-green-500 mt-1"></i>
                    <span>STNK kendaraan atas nama sendiri/keluarga</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <i className="bi bi-check-circle-fill text-green-500 mt-1"></i>
                    <span>Kartu Keluarga (KK)</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <i className="bi bi-check-circle-fill text-green-500 mt-1"></i>
                    <span>Pas foto berwarna 4x6 (2 lembar)</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <i className="bi bi-check-circle-fill text-green-500 mt-1"></i>
                    <span>Rekening bank atas nama sendiri</span>
                  </li>
                </ul>
              </div>

              <div>
                <h4 className="font-bold text-gray-800 mb-3 flex items-center gap-2">
                  <i className="bi bi-scooter text-[#a97456]"></i>
                  Persyaratan Kendaraan
                </h4>
                <ul className="space-y-2 text-gray-600">
                  <li className="flex items-start gap-2">
                    <i className="bi bi-check-circle-fill text-green-500 mt-1"></i>
                    <span>Kondisi kendaraan layak jalan</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <i className="bi bi-check-circle-fill text-green-500 mt-1"></i>
                    <span>Memiliki box delivery (akan disediakan perusahaan)</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <i className="bi bi-check-circle-fill text-green-500 mt-1"></i>
                    <span>Tahun kendaraan minimal 2015</span>
                  </li>
                </ul>
              </div>

              <div className="bg-amber-50 border-l-4 border-amber-500 p-4 rounded">
                <p className="text-sm text-gray-700">
                  <i className="bi bi-info-circle text-amber-600"></i> <strong>Catatan:</strong> Hanya calon kurir yang sudah menerima undangan resmi dari admin DailyCup yang dapat mendaftar.
                </p>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
