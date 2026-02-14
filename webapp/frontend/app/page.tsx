"use client";

import Image from "next/image";
import Link from "next/link";
import { useState, useEffect } from "react";
import Header from "../components/Header";
import FeaturedProducts from "../components/FeaturedProducts";
import { FlashSaleBanner } from "../components/flash-sale/flash-sale-banner";
import { RecentlyViewed } from "../components/products/recently-viewed";
import { useRecentlyViewedStore } from "../lib/stores/recently-viewed-store";

export default function Home() {
  const [showBackToTop, setShowBackToTop] = useState(false);
  const recentlyViewedItems = useRecentlyViewedStore((state) => state.items);
  const [mounted, setMounted] = useState(false);

  useEffect(() => setMounted(true), []);

  const [newsletterEmail, setNewsletterEmail] = useState("");
  const [isSubscribing, setIsSubscribing] = useState(false);
  const [flashSaleEndTime, setFlashSaleEndTime] = useState<Date | null>(null);

  useEffect(() => {
    const endTime = new Date();
    endTime.setHours(endTime.getHours() + 12);
    setFlashSaleEndTime(endTime);
    setMounted(true);
  }, []);

  const flashSaleProducts = [
    {
      id: "fs-1",
      name: "Ethiopian Yirgacheffe Grade 1",
      originalPrice: 185000,
      salePrice: 145000,
      stock: 50,
      sold: 42,
      image: "/uploads/products/product_699045961508a_1771062678.jfif"
    },
    {
      id: "fs-2", 
      name: "Sumatra Mandheling",
      originalPrice: 160000,
      salePrice: 125000,
      stock: 30,
      sold: 8,
      image: "/uploads/products/product_699045a386d37_1771062691.jfif"
    },
     {
      id: "fs-3", 
      name: "Vietnam Drip Special",
      originalPrice: 95000,
      salePrice: 75000,
      stock: 100,
      sold: 88,
      image: "/uploads/products/product_699045c91881b_1771062729.png"
    }
  ];

  useEffect(() => {
    const handleScroll = () => {
      setShowBackToTop(window.scrollY > 300);
    };

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const handleNewsletterSubscribe = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newsletterEmail.trim()) return;

    setIsSubscribing(true);
    try {
      // TODO: Replace with actual API call
      console.log("Newsletter subscription:", newsletterEmail);
      
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      // Show success message (you can implement toast notification here)
      alert("Thank you for subscribing! We'll keep you updated with our latest offers.");
      setNewsletterEmail("");
    } catch (error) {
      console.error("Newsletter subscription error:", error);
      alert("Failed to subscribe. Please try again.");
    } finally {
      setIsSubscribing(false);
    }
  };

  const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // Component for images with fallback
  interface ImageWithFallbackProps {
    src: string;
    alt: string;
    width: number;
    height: number;
    className?: string;
    fallbackSrc?: string;
  }

  const ImageWithFallback = ({ src, alt, width, height, className, fallbackSrc = "/assets/image/cup.png" }: ImageWithFallbackProps) => {
    const [imgSrc, setImgSrc] = useState(src);
    const [hasError, setHasError] = useState(false);

    const handleError = () => {
      if (!hasError) {
        setImgSrc(fallbackSrc);
        setHasError(true);
      }
    };

    return (
      <Image
        src={imgSrc}
        alt={alt}
        width={width}
        height={height}
        className={className}
        onError={handleError}
        placeholder="blur"
        blurDataURL="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjNmNGY2Ii8+PC9zdmc+"
      />
    );
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-[#f6efe9] to-[#f6efe9] relative overflow-x-hidden">
      <Header />

      {/* Hero Section */}
      <section id="home" className="min-h-screen flex items-center pt-24 pb-16 px-6 relative overflow-hidden">
        <div className="max-w-7xl mx-auto w-full">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            {/* Left Content */}
            <div className="relative z-10">
              <h1 className="text-6xl lg:text-8xl font-bold text-[#a15e3f] leading-tight mb-6 font-['Quantico'] tracking-wider">
                <span className="block">Discover</span>
                <span className="block">Your</span>
                <span className="block">Perfect Cup</span>
              </h1>

              {/* Decorative bean */}
              <div className="absolute -left-10 -top-8 w-72 h-72 opacity-20 pointer-events-none z-0">
                <ImageWithFallback
                  src="/assets/image/biji_kopi-removebg-preview.png"
                  alt="Coffee Bean"
                  width={300}
                  height={300}
                  className="w-full h-full object-contain filter grayscale"
                />
              </div>

              <div className="relative z-10 mb-8 italic text-gray-800">
                <p className="text-lg leading-relaxed mb-2">
                  &quot;Where every pour meets precision and every sip tells a story of excellence.&quot;
                </p>
                <p className="text-right font-semibold">- Ardan Pramudya</p>
              </div>

              <Link href="#menu" className="inline-block bg-white text-[#a15e3f] px-8 py-4 rounded-full font-semibold shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1 mb-8">
                Order Now
              </Link>

              <div className="flex flex-wrap gap-6 text-[#b67f61] font-semibold italic">
                <span className="flex items-center gap-2">• Refined Beans</span>
                <span className="flex items-center gap-2">• Signature Blends</span>
                <span className="flex items-center gap-2">• Maximum Taste</span>
                <span className="flex items-center gap-2">• Special Sponsors</span>
              </div>

              {/* Sponsors */}
              <div className="flex flex-wrap justify-center gap-4 mt-8">
                <div className="w-16 h-16 bg-white rounded-full shadow-lg flex items-center justify-center hover:scale-110 transition-transform overflow-hidden">
                  <ImageWithFallback src="/assets/image/sponsor_kfc.png" alt="KFC" width={60} height={60} className="object-contain" />
                </div>
                <div className="w-16 h-16 bg-white rounded-full shadow-lg flex items-center justify-center hover:scale-110 transition-transform overflow-hidden">
                  <ImageWithFallback src="/assets/image/sponsor_mcd.png" alt="McDonald's" width={60} height={60} className="object-contain" />
                </div>
                <div className="w-16 h-16 bg-white rounded-full shadow-lg flex items-center justify-center hover:scale-110 transition-transform overflow-hidden">
                  <ImageWithFallback src="/assets/image/sponsor_shopee.png" alt="Shopee" width={60} height={60} className="object-contain" />
                </div>
                <div className="w-16 h-16 bg-white rounded-full shadow-lg flex items-center justify-center hover:scale-110 transition-transform overflow-hidden">
                  <ImageWithFallback src="/assets/image/sponsor_gofood.png" alt="GoFood" width={60} height={60} className="object-contain" />
                </div>
                <div className="w-16 h-16 bg-white rounded-full shadow-lg flex items-center justify-center hover:scale-110 transition-transform overflow-hidden">
                  <ImageWithFallback src="/assets/image/sponsor_rotio.png" alt="Rotio" width={60} height={60} className="object-contain" />
                </div>
                <div className="w-16 h-16 bg-white rounded-full shadow-lg flex items-center justify-center hover:scale-110 transition-transform overflow-hidden">
                  <ImageWithFallback src="/assets/image/sponsor_gacoan.png" alt="Gacoan" width={60} height={60} className="object-contain" />
                </div>
              </div>
            </div>

            {/* Right Content - Hero Image */}
            <div className="flex justify-center items-center">
              <div className="relative">
                <ImageWithFallback
                  src="/assets/image/orangngopi1.png"
                  alt="People enjoying coffee"
                  width={480}
                  height={480}
                  className="rounded-2xl shadow-2xl object-cover"
                />
                <div className="absolute -bottom-4 -right-4 w-24 h-24 bg-[#D4A574] rounded-full shadow-lg flex items-center justify-center">
                  <span className="text-2xl">✨</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Flash Sale Section */}
      <div className="max-w-7xl mx-auto px-6 py-8">
        {mounted && flashSaleEndTime && (
          <FlashSaleBanner 
             endTime={flashSaleEndTime}
             products={flashSaleProducts}
             onViewAll={() => window.location.href = '/menu?featured=true'}
          />
        )}
      </div>

      {/* Categories Section */}
      <section id="categories" className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-6">
          <h2 className="text-4xl font-bold text-center mb-16 text-[#a15e3f] font-['Russo_One'] tracking-wider">
            MENU CATEGORIES
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            {[
              { name: 'Espresso', description: 'Kopi hitam klasik dengan cita rasa autentik', icon: '⚫', color: 'from-amber-600 to-amber-800' },
              { name: 'Latte', description: 'Perpaduan sempurna kopi dan susu lembut', icon: '🥛', color: 'from-blue-500 to-blue-700' },
              { name: 'Cappuccino', description: 'Kopi dengan foam susu yang creamy', icon: '☕', color: 'from-orange-500 to-orange-700' },
              { name: 'Cold Brew', description: 'Kopi dingin yang menyegarkan', icon: '🧊', color: 'from-cyan-500 to-cyan-700' }
            ].map((category, index) => (
              <Link key={index} href={`/menu?category=${category.name.toLowerCase()}`} className="group">
                <div className="bg-white border-2 border-gray-100 rounded-2xl p-8 text-center hover:shadow-2xl hover:border-[#a15e3f] transition-all transform hover:-translate-y-2">
                  <div className={`w-20 h-20 bg-gradient-to-br ${category.color} rounded-full flex items-center justify-center mx-auto mb-6 text-3xl shadow-lg group-hover:scale-110 transition-transform`}>
                    {category.icon}
                  </div>
                  <h3 className="text-xl font-bold mb-3 text-gray-800 group-hover:text-[#a15e3f] transition-colors">{category.name}</h3>
                  <p className="text-gray-600 leading-relaxed">{category.description}</p>
                </div>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* Featured Products Section */}
      <FeaturedProducts />

      {/* Recently Viewed Section */}
      <div className="max-w-7xl mx-auto px-6 py-12">
        {mounted && <RecentlyViewed products={recentlyViewedItems} />}
      </div>

      {/* About Us Section */}
      <section id="about" className="py-20 bg-white">
        <div className="max-w-6xl mx-auto px-6">
          <h2 className="text-4xl font-bold text-center mb-16 text-[#a15e3f] font-['Russo_One'] tracking-wider">
            ABOUT US
          </h2>

          {/* Team Section */}
          <div className="space-y-20 mb-20">
            {/* CEO */}
            <div className="flex flex-col lg:flex-row items-center gap-12">
              <div className="flex-1">
                <ImageWithFallback
                  src="/assets/image/User Photo.png"
                  alt="Ardan Pramudya - CEO"
                  width={400}
                  height={300}
                  className="w-80 h-100 object-cover rounded-2x2 shadow-x1"
                />
              </div>
              <div className="flex-1">
                <h3 className="text-2xl font-bold mb-4 text-[#a15e3f] font-['Quantico'] flex items-center gap-3">
                  <span>👤</span> Chief Executive Officer
                </h3>
                <p className="text-gray-700 leading-relaxed">
                  Sebagai Chief Executive Officer, <strong>Ardan Pramudya</strong> memimpin Tim & Arah Gerak Bisnis DailyCup dengan visi yang jelas dan orientasi masa depan. Ardan melihat kopi bukan hanya sebagai minuman, tetapi sebagai representasi gaya hidup produktif dan modern. Di bawah kepemimpinannya, DailyCup mengusung nilai kesederhanaan yang berkelas, menghadirkan pengalaman minum kopi yang bermakna dan relevan bagi setiap pelanggan.
                </p>
              </div>
            </div>

            {/* Manager */}
            <div className="flex flex-col lg:flex-row-reverse items-center gap-12">
              <div className="flex-1">
                <ImageWithFallback
                  src="/assets/image/User Photo (1).png"
                  alt="Hafiz Ferozaldi - Manager"
                  width={400}
                  height={300}
                  className="w-80 h-100 object-cover rounded-2x2 shadow-x1"
                />
              </div>
              <div className="flex-1">
                <h3 className="text-2xl font-bold mb-4 text-[#a15e3f] font-['Quantico'] flex items-center gap-3">
                  <span>👤</span> Manager
                </h3>
                <p className="text-gray-700 leading-relaxed">
                  Sebagai Manager, <strong>Hafiz Ferozaldi</strong> memegang peran vital dalam memastikan seluruh operasional DailyCup berjalan dengan efisien dan terkoordinasi. Ia mengawasi alur kerja dari awal hingga akhir, memastikan kualitas layanan tetap konsisten dan setiap pelanggan mendapatkan pengalaman terbaik.
                </p>
              </div>
            </div>

            {/* Marketing + HRD */}
            <div className="flex flex-col lg:flex-row items-center gap-12">
              <div className="flex-1">
                <ImageWithFallback
                  src="/assets/image/User Photo (2).png"
                  alt="Ridho Human Daryata - Marketing + HRD"
                  width={400}
                  height={300}
                  className="w-80 h-100 object-cover rounded-2x2 shadow-x1"
                />
              </div>
              <div className="flex-1">
                <h3 className="text-2xl font-bold mb-4 text-[#a15e3f] font-['Quantico'] flex items-center gap-3">
                  <span>👤</span> Marketing + HRD
                </h3>
                <p className="text-gray-700 leading-relaxed">
                  Sebagai Marketing + HRD, <strong>Ridho Human Daryata</strong> bertanggung jawab dalam membangun identitas dan energi DailyCup melalui strategi pemasaran yang kreatif, relevan, dan berorientasi pada audiens. Ia memastikan bahwa setiap kampanye, materi konten, dan komunikasi perusahaan selalu konsisten, menarik, dan selaras dengan karakter brand.
                </p>
              </div>
            </div>

            {/* Supervisor */}
            <div className="flex flex-col lg:flex-row-reverse items-center gap-12">
              <div className="flex-1">
                <ImageWithFallback
                  src="/assets/image/User Photo (3).png"
                  alt="Ruth Tiara Sinaga - Supervisor"
                  width={400}
                  height={200}
                  className="w-80 h-100 object-cover rounded-2x2 shadow-x1"
                />
              </div>
              <div className="flex-1">
                <h3 className="text-2xl font-bold mb-4 text-[#a15e3f] font-['Quantico'] flex items-center gap-3">
                  <span>👤</span> Supervisor
                </h3>
                <p className="text-gray-700 leading-relaxed">
                  Sebagai Supervisor, <strong>Ruth Tiara Sinaga</strong> menjadi pusat ketertiban dan konsistensi operasional. Ia memastikan setiap detail mulai dari persiapan, kebersihan area kerja, hingga pengelolaan inventaris berjalan sesuai standar. Dengan gaya kerja yang sistematis dan perhatian penuh pada kualitas, Ruth memperkuat ritme kerja tim.
                </p>
              </div>
            </div>
          </div>

          {/* Get to Know Section */}
          <div className="bg-white/60 backdrop-blur-sm rounded-3xl p-12 mb-16 shadow-xl">
            <h2 className="text-3xl font-bold text-center mb-2 italic">Get To Know</h2>
            <h1 className="text-5xl font-bold text-center mb-8 font-['Russo_One']">
              Daily<span className="text-[#a15e3f]">Cup</span>
            </h1>
            <div className="max-w-4xl mx-auto text-center space-y-4">
              <p className="text-lg leading-relaxed">
                <strong>DailyCup</strong> merupakan sebuah coffee shop yang berkomitmen menghadirkan pengalaman menikmati kopi dengan standar kualitas tinggi. Dengan proses penyajian yang terukur dan pemilihan bahan baku terbaik, DailyCup menawarkan cita rasa yang konsisten, elegan, dan berkarakter.
              </p>
              <p className="text-lg leading-relaxed">
                Beroperasi sebagai ruang yang nyaman dan berorientasi pada pelayanan, DailyCup dirancang untuk menjadi tempat di mana pelanggan dapat bekerja, berdiskusi, maupun beristirahat dengan suasana yang tenang. Filosofi kami sederhana: setiap cangkir harus memberikan kesan mendalam, mencerminkan profesionalisme serta dedikasi terhadap kualitas.
              </p>
            </div>
          </div>

          {/* Mission & Value */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-16">
            <div className="bg-white p-8 rounded-2xl shadow-xl">
              <h3 className="text-2xl font-bold mb-4 text-[#a15e3f] font-['Russo_One']">Our Mission</h3>
              <p className="text-gray-700 leading-relaxed">
                Menciptakan pengalaman menikmati kopi yang autentik dan berkualitas tinggi melalui perpaduan rasa, suasana, dan pelayanan terbaik. Kami berkomitmen menghadirkan kopi yang fresh, dibuat dengan standar barista profesional, serta disajikan dalam lingkungan yang hangat dan nyaman.
              </p>
            </div>
            <div className="bg-white p-8 rounded-2xl shadow-xl">
              <h3 className="text-2xl font-bold mb-4 text-[#a15e3f] font-['Russo_One']">Our Value</h3>
              <p className="text-gray-700 leading-relaxed">
                Kami menjunjung nilai kualitas, keaslian, dan kenyamanan dalam setiap proses penyajian kopi. Kami memilih bahan terbaik, menjaga standar pembuatan yang konsisten, dan menghadirkan suasana yang hangat untuk setiap pelanggan. Nilai kami berakar pada komitmen untuk memberi pengalaman yang tulus.
              </p>
            </div>
          </div>

          {/* Contact Us */}
          <div className="text-center">
            <h3 className="text-3xl font-bold mb-8 text-[#a15e3f] font-['Russo_One']">Contact Us</h3>
            <div className="flex flex-wrap justify-center gap-8">
              <a href="https://instagram.com/dailycup" target="_blank" className="flex flex-col items-center gap-3 text-gray-700 hover:text-[#a15e3f] transition-colors group">
                <div className="w-12 h-12 bg-[#a15e3f] rounded-full flex items-center justify-center text-white group-hover:scale-110 transition-transform">
                  📷
                </div>
                <span className="font-semibold">@dailycup</span>
              </a>
              <a href="https://wa.me/628123456789" target="_blank" className="flex flex-col items-center gap-3 text-gray-700 hover:text-[#a15e3f] transition-colors group">
                <div className="w-12 h-12 bg-[#a15e3f] rounded-full flex items-center justify-center text-white group-hover:scale-110 transition-transform">
                  💬
                </div>
                <span className="font-semibold">+62 812-3456-789</span>
              </a>
              <a href="mailto:info@dailycup.com" className="flex flex-col items-center gap-3 text-gray-700 hover:text-[#a15e3f] transition-colors group">
                <div className="w-12 h-12 bg-[#a15e3f] rounded-full flex items-center justify-center text-white group-hover:scale-110 transition-transform">
                  ✉️
                </div>
                <span className="font-semibold">info@dailycup.com</span>
              </a>
            </div>
          </div>
        </div>
      </section>

      {/* Newsletter Section */}
      <section className="py-16 bg-[#a15e3f] text-white">
        <div className="max-w-4xl mx-auto px-6 text-center">
          <h2 className="text-3xl font-bold mb-4 font-['Russo_One']">Stay Updated</h2>
          <p className="text-lg mb-8 opacity-90">Get the latest news about our special offers and new menu items</p>
          <form onSubmit={handleNewsletterSubscribe} className="flex flex-col sm:flex-row gap-4 max-w-md mx-auto">
            <input
              type="email"
              value={newsletterEmail}
              onChange={(e) => setNewsletterEmail(e.target.value)}
              placeholder="Enter your email"
              className="flex-1 px-4 py-3 rounded-full text-gray-800 focus:outline-none focus:ring-2 focus:ring-white"
              suppressHydrationWarning
              required
            />
            <button 
              type="submit"
              disabled={isSubscribing}
              className="bg-white text-[#a15e3f] px-8 py-3 rounded-full font-semibold hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              suppressHydrationWarning
            >
              {mounted ? (isSubscribing ? "Subscribing..." : "Subscribe") : "Subscribe"}
            </button>
          </form>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-white/95 backdrop-blur-sm border-t border-gray-200 py-8" suppressHydrationWarning>
        <div className="max-w-7xl mx-auto px-6" suppressHydrationWarning>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-6" suppressHydrationWarning>
            {/* Brand */}
            <div suppressHydrationWarning>
              <div className="font-bold text-[#a15e3f] font-['Russo_One'] text-xl mb-2">DailyCup</div>
              <p className="text-gray-600 text-sm">Kopi premium untuk hari yang lebih baik</p>
            </div>
            
            {/* Quick Links */}
            <div suppressHydrationWarning>
              <h3 className="font-semibold text-gray-800 mb-3">Menu</h3>
              <ul className="space-y-2 text-sm">
                <li><Link href="/products" className="text-gray-600 hover:text-[#a15e3f] transition-colors">Produk</Link></li>
                <li><Link href="/about" className="text-gray-600 hover:text-[#a15e3f] transition-colors">Tentang Kami</Link></li>
                <li><Link href="/contact" className="text-gray-600 hover:text-[#a15e3f] transition-colors">Kontak</Link></li>
              </ul>
            </div>
            
            {/* Customer Service */}
            <div suppressHydrationWarning>
              <h3 className="font-semibold text-gray-800 mb-3">Layanan</h3>
              <ul className="space-y-2 text-sm">
                <li><Link href="/customer/orders" className="text-gray-600 hover:text-[#a15e3f] transition-colors">Pesanan Saya</Link></li>
                <li><Link href="/customer/favorites" className="text-gray-600 hover:text-[#a15e3f] transition-colors">Favorit</Link></li>
                <li><Link href="/faq" className="text-gray-600 hover:text-[#a15e3f] transition-colors">FAQ</Link></li>
              </ul>
            </div>
            
            {/* Careers */}
            <div suppressHydrationWarning>
              <h3 className="font-semibold text-gray-800 mb-3">Karir</h3>
              <ul className="space-y-2 text-sm">
                <li>
                  <Link href="/kurir/info" className="text-gray-600 hover:text-[#a15e3f] transition-colors flex items-center gap-1">
                    <i className="bi bi-bicycle"></i>
                    <span>Bergabung Jadi Kurir</span>
                  </Link>
                </li>
              </ul>
            </div>
          </div>
          
          <div className="border-t border-gray-200 pt-6 flex flex-col md:flex-row justify-between items-center text-sm text-gray-600" suppressHydrationWarning>
            <div>© 2025 DailyCup. All rights reserved.</div>
            <div className="flex gap-4 mt-3 md:mt-0">
              <Link href="/privacy" className="hover:text-[#a15e3f] transition-colors">Kebijakan Privasi</Link>
              <Link href="/terms" className="hover:text-[#a15e3f] transition-colors">Syarat & Ketentuan</Link>
            </div>
          </div>
        </div>
      </footer>

      {/* Back to Top Button */}
      {mounted && showBackToTop && (
        <button
          onClick={scrollToTop}
          className="fixed bottom-6 right-6 bg-[#a15e3f] text-white p-4 rounded-full shadow-lg hover:bg-[#8b4d31] transition-all transform hover:scale-110 z-50"
          aria-label="Back to top"
          suppressHydrationWarning
        >
          <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 10l7-7m0 0l7 7m-7-7v18" />
          </svg>
        </button>
      )}
    </div>
  );
}
