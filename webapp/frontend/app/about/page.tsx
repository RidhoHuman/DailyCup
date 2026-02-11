"use client";

import Header from "../../components/Header";

export default function AboutPage() {
  return (
    <div className="min-h-screen bg-[#f5f0ec]">
      <Header />

      <div className="max-w-6xl mx-auto px-4 py-16">
        <div className="text-center mb-16">
          <h1 className="text-5xl font-bold text-[#a97456] mb-6 font-['Quantico']">
            About DailyCup
          </h1>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            Where every pour meets precision and every sip tells a story of
            excellence. Discover our journey in crafting the perfect coffee
            experience.
          </p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-16">
          <div>
            <h2 className="text-3xl font-bold text-[#a97456] mb-6">
              Our Story
            </h2>
            <p className="text-gray-700 mb-4">
              Founded in 2022, DailyCup began as a passion project to bring
              exceptional coffee experiences to coffee lovers everywhere. What
              started as a small café has grown into a beloved destination for
              premium coffee and beverages.
            </p>
            <p className="text-gray-700 mb-4">
              We believe that great coffee is about more than just the beans –
              it&apos;s about the connection, the moment, and the experience. Every
              cup we serve is crafted with care, using only the finest
              ingredients and traditional brewing methods.
            </p>
            <p className="text-gray-700">
              Join us in celebrating the art of coffee, one perfect cup at a
              time.
            </p>
          </div>

          <div className="relative h-96 bg-gradient-to-br from-[#a97456] to-[#8a5a3d] rounded-lg overflow-hidden">
            <div className="absolute inset-0 flex items-center justify-center">
              <i className="bi bi-cup text-8xl text-white"></i>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
          <div className="text-center">
            <div className="w-16 h-16 bg-[#a97456] rounded-full flex items-center justify-center mx-auto mb-4">
              <i className="bi bi-star text-white text-2xl"></i>
            </div>
            <h3 className="text-xl font-semibold text-[#a97456] mb-2">
              Premium Quality
            </h3>
            <p className="text-gray-600">
              We source only the finest beans and ingredients for an unmatched
              coffee experience.
            </p>
          </div>

          <div className="text-center">
            <div className="w-16 h-16 bg-[#a97456] rounded-full flex items-center justify-center mx-auto mb-4">
              <i className="bi bi-heart text-white text-2xl"></i>
            </div>
            <h3 className="text-xl font-semibold text-[#a97456] mb-2">
              Made with Love
            </h3>
            <p className="text-gray-600">
              Every drink is prepared with passion and attention to detail by
              our skilled baristas.
            </p>
          </div>

          <div className="text-center">
            <div className="w-16 h-16 bg-[#a97456] rounded-full flex items-center justify-center mx-auto mb-4">
              <i className="bi bi-people text-white text-2xl"></i>
            </div>
            <h3 className="text-xl font-semibold text-[#a97456] mb-2">
              Community First
            </h3>
            <p className="text-gray-600">
              We&apos;re more than a coffee shop – we&apos;re a gathering place for coffee
              enthusiasts.
            </p>
          </div>
        </div>

        <div className="text-center">
          <h2 className="text-3xl font-bold text-[#a97456] mb-6">
            Visit Us Today
          </h2>
          <p className="text-gray-600 mb-8">
            Experience the DailyCup difference. Come taste the perfection for
            yourself.
          </p>
          <button className="bg-[#a97456] text-white px-8 py-3 rounded-lg font-semibold hover:bg-[#8a5a3d] transition-colors">
            Find Our Location
          </button>
        </div>
      </div>
    </div>
  );
}
